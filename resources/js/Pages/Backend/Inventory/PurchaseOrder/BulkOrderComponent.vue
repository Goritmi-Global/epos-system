<script setup>
import { ref, computed, onMounted, watch, toRaw } from "vue";
import { toast } from "vue3-toastify";
import Select from "primevue/select";
import { useFormatters } from '@/composables/useFormatters'

const { formatMoney, formatCurrencySymbol, formatNumber, dateFmt } = useFormatters()

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
const activeTab = ref("single");

const b_supplier = ref(null);
const b_submitting = ref(false);
const formErrors = ref({});
const bulkItems = ref([]);
const m_submitting = ref(false);
const m_formErrors = ref({});
const multipleItems = ref([]);
watch(
    () => props.items,
    (newItems) => {
        bulkItems.value = newItems.map((it) => ({
            ...it,
            qty: 0,           
            unitPrice: 0,     
            expiry: null,
            subtotal: 0,
        }));
    },
    { immediate: true }
);

watch(
    () => props.items,
    (newItems) => {
        multipleItems.value = newItems.map((it) => ({
            ...it,
            qty: 0,
            unitPrice: 0,
            expiry: null,
            subtotal: 0,
            supplier_id: null,
        }));
    },
    { immediate: true }
);



onMounted(() => {
    feather.replace();
    const bulkOrderModal = document.getElementById("bulkOrderModal");
    if (bulkOrderModal) {
        bulkOrderModal.addEventListener("show.bs.modal", () => {
            activeTab.value = "single";
        });

        bulkOrderModal.addEventListener("hide.bs.modal", () => {
            activeTab.value = "single";
        });
    }
});

function updateSubtotal(it) {
    const qty = Number(it.qty) || 0;
    const price = Number(it.unitPrice) || 0;
    it.subtotal = qty * price;
}

function handleQtyInput(it) {
    if (it.qty < 0) {
        it.qty = 0;
    }
    updateSubtotal(it);
}

function handlePriceInput(it) {
    if (it.unitPrice < 0) {
        it.unitPrice = 0;
    }
    updateSubtotal(it);
}

function delRow(idx) {
    bulkItems.value[idx].qty = 0;
    bulkItems.value[idx].unitPrice = 0;
    bulkItems.value[idx].expiry = null;
    bulkItems.value[idx].subtotal = 0;
}

function delMultipleRow(idx) {
    multipleItems.value[idx].qty = 0;
    multipleItems.value[idx].unitPrice = 0;
    multipleItems.value[idx].expiry = null;
    multipleItems.value[idx].subtotal = 0;
    multipleItems.value[idx].supplier_id = null;
}


const b_total = computed(() =>
    bulkItems.value.reduce((sum, it) => sum + (Number(it.subtotal) || 0), 0)
);

const m_total = computed(() =>
    multipleItems.value.reduce((sum, it) => sum + (Number(it.subtotal) || 0), 0)
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
    if (validItems.length === 0) {
        toast.error("No valid items to submit");
        return;
    }
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


async function multipleSubmit() {
    m_formErrors.value = {};

    const today = new Date().toISOString().split("T")[0];
    const validItems = [];
    const groupedBySupplier = {};

    multipleItems.value.forEach((it, idx) => {
        const hasAnyValue = it.qty || it.unitPrice || it.expiry;
        const errors = {};

        if (hasAnyValue) {
            if (!it.supplier_id) {
                errors.supplier_id = "Supplier is required";
            }
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
                m_formErrors.value[idx] = errors;
            } else {
                const supplierId = it.supplier_id;
                if (!groupedBySupplier[supplierId]) {
                    groupedBySupplier[supplierId] = [];
                }
                groupedBySupplier[supplierId].push({
                    product_id: it.id,
                    quantity: it.qty,
                    unit_price: it.unitPrice,
                    expiry: it.expiry,
                });
            }
        }
    });

    if (Object.keys(groupedBySupplier).length === 0) {
        toast.error("No valid items to submit");
        return;
    }

    if (Object.keys(m_formErrors.value).length > 0) {
        toast.error("Please fix the highlighted errors");
        return;
    }

    m_submitting.value = true;
    try {
        for (const [supplierId, items] of Object.entries(groupedBySupplier)) {
            const payload = {
                supplier_id: parseInt(supplierId),
                purchase_date: today,
                status: "completed",
                items: items,
            };
            await axios.post("/purchase-orders", payload);
        }

        toast.success("Multiple orders created successfully!");
        emit("refresh-data");

        multipleItems.value.forEach((it) => {
            it.qty = 0;
            it.unitPrice = 0;
            it.expiry = null;
            it.subtotal = 0;
            it.supplier_id = null;
        });

        const m = bootstrap.Modal.getInstance(
            document.getElementById("bulkOrderModal")
        );
        m?.hide();
    } catch (err) {
        console.error(err);
        toast.error("Failed to save multiple orders");
    } finally {
        m_submitting.value = false;
    }
}


</script>

<template>
    <div class="modal fade" id="bulkOrderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Bulk Purchase</h5>
                    <button
                        class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                        data-bs-dismiss="modal" aria-label="Close" title="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- TAB NAVIGATION -->
                <div class="modal-body">
                    <ul class="nav nav-tabs mb-4" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" :class="{ active: activeTab === 'single' }"
                                @click="activeTab = 'single'" type="button" role="tab">
                                Single Purchase
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" :class="{ active: activeTab === 'multiple' }"
                                @click="activeTab = 'multiple'" type="button" role="tab">
                                Multiple Purchase
                            </button>
                        </li>
                    </ul>


                    <!-- SINGLE PURCHASE TAB -->
                    <div v-if="activeTab === 'single'">
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
                                        <th>Unit</th>
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
                                        <td>{{ it.unit_name }}</td>

                                        <td>
                                            <input type="number" min="0" v-model.number="it.qty" class="form-control"
                                                @input="handleQtyInput(it)" />
                                            <small v-if="formErrors[idx]?.qty" class="text-danger">
                                                {{ formErrors[idx].qty }}
                                            </small>
                                        </td>
                                        <td>
                                            <input type="number" min="0" v-model.number="it.unitPrice"
                                                class="form-control" @input="handlePriceInput(it)" />
                                            <small v-if="formErrors[idx]?.unitPrice" class="text-danger">
                                                {{ formErrors[idx].unitPrice }}
                                            </small>
                                        </td>
                                        <td>
                                            <VueDatePicker v-model="it.expiry" :format="dateFmt" :min-date="new Date()"
                                                :enableTimePicker="false" :teleport="true" placeholder="Select date"
                                                :class="{
                                                    'is-invalid': formErrors[idx]?.expiry
                                                }" />

                                            <small v-if="formErrors[idx]?.expiry" class="text-danger">
                                                {{ formErrors[idx].expiry }}
                                            </small>
                                        </td>
                                        <td>{{ formatCurrencySymbol(it.subtotal.toFixed(2)) }}</td>
                                        <td>

                                            <button
                                                class="btn btn-sm btn-outline-danger d-flex align-items-center justify-content-center"
                                                @click="delRow(idx)" title="Clear" style="width: 36px; height: 36px;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    fill="currentColor" viewBox="0 0 16 16">
                                                    <path fill-rule="evenodd"
                                                        d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z" />
                                                    <path
                                                        d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z" />
                                                </svg>
                                            </button>
                                        </td>

                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="text-end fw-bold mt-3">
                            Total: {{ formatCurrencySymbol(b_total.toFixed(2)) }}
                        </div>
                    </div>

                    <!-- MULTIPLE PURCHASE TAB -->
                    <div v-if="activeTab === 'multiple'">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Unit</th>
                                        <th>Supplier</th>
                                        <th>Qty</th>
                                        <th>Unit Price</th>
                                        <th>Expiry</th>
                                        <th>Subtotal</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(it, idx) in multipleItems" :key="it.id">
                                        <td>{{ it.name }}</td>
                                        <td>{{ it.category?.name || "-" }}</td>
                                        <td>{{ it.unit_name }}</td>

                                        <td>
                                            <select v-model="it.supplier_id" class="form-select w-100"
                                                :class="{ 'is-invalid': m_formErrors[idx]?.supplier_id }">

                                                <option value="" disabled hidden>Select Supplier</option>

                                                <option v-for="supplier in suppliers" :key="supplier.id"
                                                    :value="supplier.id">
                                                    {{ supplier.name }}
                                                </option>
                                            </select>


                                            <small v-if="m_formErrors[idx]?.supplier_id" class="text-danger d-block">
                                                {{ m_formErrors[idx].supplier_id }}
                                            </small>
                                        </td>



                                        <td>
                                            <input type="number" min="0" v-model.number="it.qty" class="form-control"
                                                @input="handleQtyInput(it)" />
                                            <small v-if="formErrors[idx]?.qty" class="text-danger">
                                                {{ formErrors[idx].qty }}
                                            </small>
                                        </td>


                                        <td>
                                            <input type="number" min="0" v-model.number="it.unitPrice"
                                                class="form-control" @input="handlePriceInput(it)" />
                                            <small v-if="formErrors[idx]?.unitPrice" class="text-danger">
                                                {{ formErrors[idx].unitPrice }}
                                            </small>
                                        </td>
                                        <td>
                                            <VueDatePicker v-model="it.expiry" :format="dateFmt" :min-date="new Date()"
                                                :enableTimePicker="false" :teleport="true" placeholder="Select date"
                                                :class="{
                                                    'is-invalid': m_formErrors[idx]?.expiry
                                                }" />

                                            <small v-if="m_formErrors[idx]?.expiry" class="text-danger">
                                                {{ m_formErrors[idx].expiry }}
                                            </small>
                                        </td>
                                        <td>{{ formatCurrencySymbol(it.subtotal.toFixed(2)) }}</td>
                                        <td>
                                            <!-- Clear button with static SVG rotate icon -->
                                            <button
                                                class="btn btn-sm btn-outline-danger d-flex align-items-center justify-content-center"
                                                @click="delMultipleRow(idx)" title="Clear"
                                                style="width: 36px; height: 36px;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    fill="currentColor" viewBox="0 0 16 16">
                                                    <path fill-rule="evenodd"
                                                        d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z" />
                                                    <path
                                                        d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="text-end fw-bold mt-3">
                            Total: {{ formatCurrencySymbol(m_total.toFixed(2)) }}
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button v-if="activeTab === 'single'" class="btn btn-primary rounded-pill px-4 py-2"
                        :disabled="b_submitting" @click="bulkSubmit">
                        <span v-if="!b_submitting">Save</span>
                        <span v-else>Saving...</span>
                    </button>
                    <button v-if="activeTab === 'multiple'" class="btn btn-primary rounded-pill px-4 py-2"
                        :disabled="m_submitting" @click="multipleSubmit">
                        <span v-if="!m_submitting">Save</span>
                        <span v-else>Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>


<style scoped>
:deep(.p-multiselect-panel),
:deep(.p-select-panel),
:deep(.p-dropdown-panel) {
    z-index: 2000 !important;
}

:global(.dark .nav-tabs .nav-link) {
    background-color: #212121 !important;
    color: #fff !important;
    border: 1px solid #333 !important;
    border-bottom: 1px solid #fff !important;
    transition: all 0.2s ease;
}

:global(.dark .nav-tabs .nav-link.active) {
    background-color: #1C0D82 !important;
    color: #fff !important;
    border: 1px solid #1C0D82 !important;
    border-bottom: 1px solid #fff !important;
}

:global(.dark .nav-tabs .nav-link:hover) {
    background-color: #2a2a2a !important;
    color: #fff !important;
}

.dark .form-select {
    background-color: #212121 !important;
    color: #fff !important;
}

/* ====================Select Styling===================== */
/* Entire select container */
:deep(.p-select) {
    background-color: white !important;
    color: black !important;
    border-color: #9b9c9c;
}
:deep(.p-select-list-container) {
    background-color: white !important;
    color: black !important;
}
:deep(.p-select-option) {
    background-color: transparent !important;
    color: black !important;
}
:deep(.p-select-option:hover) {
    background-color: #f0f0f0 !important;
    color: black !important;
}
:deep(.p-select-option.p-focus) {
    background-color: #f0f0f0 !important;
    color: black !important;
}

:deep(.p-select-label) {
    color: #000 !important;
}

:deep(.p-placeholder) {
    color: #80878e !important;
}


/* ======================== Dark Mode MultiSelect ============================= */
:global(.dark .p-multiselect-header) {
    background-color: #181818 !important;
    color: #fff !important;
}

:global(.dark .p-multiselect-label) {
    color: #fff !important;
}

:global(.dark .p-select .p-component .p-inputwrapper) {
    background: #000 !important;
    color: #fff !important;
    border-bottom: 1px solid #555 !important;
}
:global(.dark .p-multiselect-list) {
    background: #181818 !important;
}
:global(.dark .p-multiselect-option) {
    background: #181818 !important;
    color: #fff !important;
}
:global(.dark .p-multiselect-option.p-highlight),
:global(.dark .p-multiselect-option:hover) {
    background: #181818 !important;
    color: #fff !important;
}

:global(.dark .p-multiselect),
:global(.dark .p-multiselect-panel),
:global(.dark .p-multiselect-token) {
    background: #212121 !important;
    color: #fff !important;
    border-color: #555 !important;
}
:global(.dark .p-multiselect-overlay .p-checkbox-box) {
    background: #181818 !important;
    border: 1px solid #555 !important;
}
:global(.dark .p-multiselect-filter) {
    background: #181818 !important;
    color: #fff !important;
    border: 1px solid #555 !important;
}
:global(.dark .p-multiselect-filter-container) {
    background: #181818 !important;
}
:global(.dark .p-multiselect-chip) {
    background: #181818 !important;
    color: #fff !important;
    border: 1px solid #555 !important;
    border-radius: 12px !important;
    padding: 0.25rem 0.5rem !important;
}

.dark .p-inputtext {
    background-color: #181818 !important;
    color: #fff !important;
}

.dark .p-checkbox-icon {
    color: #fff !important;
}

.dark .p-checkbox-input {
    color: #fff !important;
}

.dark .p-component {
    color: #fff !important;
}
:global(.dark .p-multiselect-chip .p-chip-remove-icon) {
    color: #fff !important;
}

:global(.dark .p-multiselect-chip .p-chip-remove-icon:hover) {
    color: #f87171 !important;
}

/* ==================== Dark Mode Select Styling ====================== */
:global(.dark .p-select) {
    background-color: #000 !important;
    color: #fff !important;
    border-color: #555 !important;
}
:global(.dark .p-select-list-container) {
    background-color: #000 !important;
    color: #fff !important;
}
:global(.dark .p-select-option) {
    background-color: transparent !important;
    color: #fff !important;
}
:global(.dark .p-select-option:hover),
:global(.dark .p-select-option.p-focus) {
    background-color: #222 !important;
    color: #fff !important;
}

:global(.dark .p-select-dropdown) {
    background-color: #212121 !important;
    color: #fff !important;

}

:global(.dark .p-select-label) {
    color: #fff !important;
    background-color: #212121 !important;
}

:global(.dark .p-select-list) {
    background-color: #212121 !important;
}

:global(.dark .p-placeholder) {
    color: #aaa !important;
}
</style>