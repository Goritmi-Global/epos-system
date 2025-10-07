<script setup>
import { ref, computed, watch, toRaw } from "vue";
import { toast } from "vue3-toastify";
import Select from "primevue/select";

const props = defineProps({
    suppliers: {
        type: Array,
        default: () => [],
    },
    items: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["refresh-data"]);

const b_supplier = ref(null);
const b_submitting = ref(false);
const formErrors = ref({});


// reactive table rows
const bulkItems = ref([]);

// 👇 Build bulkItems whenever items change
watch(
    () => props.items,
    (newItems) => {
        bulkItems.value = newItems.map((it) => ({
            ...it,
            qty: 0,           // 👈 start as 0 instead of null
            unitPrice: 0,     // 👈 start as 0 instead of null
            expiry: null,
            subtotal: 0,
        }));
    },
    { immediate: true }
);


function updateSubtotal(it) {
    const qty = Number(it.qty) || 0;
    const price = Number(it.unitPrice) || 0;
    it.subtotal = qty * price;
}

function delRow(idx) {
    bulkItems.value[idx].qty = 0;
    bulkItems.value[idx].unitPrice = 0;
    bulkItems.value[idx].expiry = null;
    bulkItems.value[idx].subtotal = 0;
}


const b_total = computed(() =>
    bulkItems.value.reduce((sum, it) => sum + (Number(it.subtotal) || 0), 0)
);

async function bulkSubmit() {
    formErrors.value = {}; // reset

    if (!b_supplier.value) {
        formErrors.value.supplier = "Please select a supplier";
    }

    const today = new Date().toISOString().split("T")[0];
    const validItems = [];

    bulkItems.value.forEach((it, idx) => {
        const hasAnyValue = it.qty || it.unitPrice || it.expiry;
        const errors = {};

        // Only validate if the row has data
        if (hasAnyValue) {
            if (!it.qty || it.qty <= 0) {
                errors.qty = "Quantity is required";
            }
            if (!it.unitPrice || it.unitPrice <= 0) {
                errors.unitPrice = "Unit Price is required";
            }
            if (!it.expiry) {
                errors.expiry = "Expiry date is required";
            } else if (new Date(it.expiry) < new Date(today)) {
                errors.expiry = "Expiry must be today or later";
            }

            if (Object.keys(errors).length > 0) {
                formErrors.value[idx] = errors;
            } else {
                // ✅ Only push if fully valid
                validItems.push({
                    product_id: it.id,
                    quantity: it.qty,
                    unit_price: it.unitPrice,
                    expiry: it.expiry,
                });
            }
        }
    });

    if (!b_supplier.value) {
        toast.error("Please select a supplier");
        return;
    }

    // If no valid items to submit
    if (validItems.length === 0) {
        toast.error("No valid items to submit");
        return;
    }

    // Stop if there are any row-level errors
    if (Object.keys(formErrors.value).length > 0) {
        toast.error("Please fix the highlighted errors");
        return;
    }

    const payload = {
        supplier_id: b_supplier.value,
        purchase_date: today,
        status: "completed",
        items: validItems,
    };

    b_submitting.value = true;
    try {
        await axios.post("/purchase-orders", payload);
        toast.success("Bulk order created successfully!");
        emit("refresh-data");

        // Reset form
        b_supplier.value = null;
        bulkItems.value.forEach((it) => {
            it.qty = 0;
            it.unitPrice = 0;
            it.expiry = null;
            it.subtotal = 0;
        });

        // Close modal
        const m = bootstrap.Modal.getInstance(
            document.getElementById("bulkOrderModal")
        );
        m?.hide();
    } catch (err) {
        console.error(err);
        toast.error("Failed to save bulk order");
    } finally {
        b_submitting.value = false;
    }
}



</script>

<template>
    <div class="modal fade" id="bulkOrderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Bulk Orders</h5>
                    <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Preferred Supplier</label>
                        <Select v-model="b_supplier" :options="suppliers" optionLabel="name" optionValue="id"
                            placeholder="Select Supplier" class="w-100" appendTo="self" :autoZIndex="true"
                            :class="{ 'is-invalid': formErrors.supplier }" :baseZIndex="2000" />

                        <small v-if="formErrors.supplier" class="text-danger">
                            {{ formErrors.supplier }}
                        </small>

                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Qty</th>
                                    <th>Unit Price</th>
                                    <th>Expiry</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(it, idx) in bulkItems" :key="it.id">
                                    <td>{{ it.name }}</td>
                                    <td>{{ it.category?.name || "-" }}</td>
                                    <td>
                                        <input type="number" min="0" v-model.number="it.qty" class="form-control"
                                            @input="updateSubtotal(it)" />
                                        <small v-if="formErrors[idx]?.qty" class="text-danger">
                                            {{ formErrors[idx].qty }}
                                        </small>
                                    </td>
                                    <td>
                                        <input type="number" min="0" v-model.number="it.unitPrice" class="form-control"
                                            @input="updateSubtotal(it)" />
                                        <small v-if="formErrors[idx]?.unitPrice" class="text-danger">
                                            {{ formErrors[idx].unitPrice }}
                                        </small>
                                    </td>
                                    <td>
                                        <input type="date" v-model="it.expiry" class="form-control" />
                                        <small v-if="formErrors[idx]?.expiry" class="text-danger">
                                            {{ formErrors[idx].expiry }}
                                        </small>
                                    </td>
                                    <td>{{ it.subtotal.toFixed(2) }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger" @click="delRow(idx)">
                                            Clear
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="text-end fw-bold mt-3">
                        Total: {{ b_total.toFixed(2) }}
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-primary rounded-pill px-4 py-2" :disabled="b_submitting" @click="bulkSubmit">
                        <span v-if="!b_submitting">Save Bulk Order</span>
                        <span v-else>Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
