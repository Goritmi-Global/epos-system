<script setup>
import { toast } from "vue3-toastify";
import Select from "primevue/select";
import { ref, computed } from "vue";

const p_cart = ref([]);
const p_total = computed(() =>
    round2(p_cart.value.reduce((s, r) => s + Number(r.cost || 0), 0))
);
function addPurchaseItem(item) {
    const qty = Number(item.qty || 0);
    const price =
        item.unitPrice !== ""
            ? Number(item.unitPrice)
            : Number(item.defaultPrice || 0);
    const expiry = item.expiry || null;

    if (!qty || qty <= 0) return toast.error("Enter a valid quantity.");
    if (!price || price <= 0) return toast.error("Enter a valid unit price.");
    if (!expiry || expiry <= 0) return toast.error("Enter an expiry date.");

    // MERGE: same product + same unitPrice + same expiry
    const found = p_cart.value.find(
        (r) => r.id === item.id && r.unitPrice === price && r.expiry === expiry
    );
    if (found) {
        found.qty = round2(found.qty + qty);
        found.cost = round2(found.qty * found.unitPrice);
    } else {
        p_cart.value.push({
            id: item.id,
            name: item.name,
            category: item.category,
            qty,
            unitPrice: price,
            expiry,
            cost: round2(qty * price),
        });
    }

    // reset only that item’s fields
    item.qty = null;
    item.unitPrice = null;
    item.expiry = null;
}

function delPurchaseRow(idx) {
    p_cart.value.splice(idx, 1);
}

const p_submitting = ref(false);

async function quickPurchaseSubmit() {
    if (!p_supplier.value) return toast.error("Please select a supplier.");
    if (!p_cart.value.length) return toast.error("No items added.");

    const payload = {
        supplier_id: p_supplier.value,
        purchase_date: new Date().toISOString().split("T")[0], // today
        status: "completed",
        items: p_cart.value.map((item) => ({
            product_id: item.id,
            quantity: item.qty,
            unit_price: item.unitPrice,
            expiry: item.expiry || null,
        })),
    };

    p_submitting.value = true;

    try {
        const res = await axios.post("/purchase-orders", payload);
        console.log("✅ Purchase created:", res.data);

        // reset modal
        p_cart.value = [];
        p_supplier.value = null;

        const modal = bootstrap.Modal.getInstance(
            document.getElementById("addPurchaseModal")
        );
        modal?.hide();
    } catch (err) {
        toast.error(
            "Failed to create purchase:",
            err.response?.data || err.message
        );
        toast.error("Failed to create purchase.");
    } finally {
        p_submitting.value = false;
    }
}
const money = (n, currency = "GBP") =>
    new Intl.NumberFormat("en-GB", { style: "currency", currency }).format(
        Number(n || 0)
    );
const round2 = (n) => Math.round((Number(n) || 0) * 100) / 100;

// const p_qty = ref(1);
// const p_price = ref(""); // unit price
// const p_expiry = ref(""); // expiry date

const props = defineProps({
    suppliers: {
        type: Array,
        required: true,
    },
    items: {
        type: Array,
        required: true,
    },
});
const p_supplier = ref(null);
function orderSubmit() {
    // validate supplier
    if (!p_supplier.value) {
        p_errors.supplier = "Please select a supplier.";
        toast.error("Please select a supplier.");
        // optional: focus the field
        nextTick(() => document.getElementById("supplierSelect")?.focus());
        return;
    }
    // if (!p_supplier.value) return toast.error("Please select a supplier.");
    if (!p_cart.value.length) return toast.error("No items added.");

    const payload = {
        supplier_id: p_supplier.value.id,
        purchase_date: new Date().toISOString().split("T")[0],
        status: "pending",
        items: o_cart.value.map(({ id, qty, unitPrice, expiry }) => ({
            product_id: id,
            quantity: qty,
            unit_price: unitPrice,
            expiry: expiry || null,
        })),
    };

    o_submitting.value = true;

    axios
        .post("/purchase-orders", payload)
        .then((res) => {
            // reset
            o_cart.value = [];
            p_supplier.value = null;
            const m = bootstrap.Modal.getInstance(
                document.getElementById("addOrderModal")
            );
            m?.hide();
        })
        .catch((err) => {
            console.error(
                "❌ Failed to create order:",
                err.response?.data || err.message
            );
            alert("Failed to create order.");
        })
        .finally(() => {
            o_submitting.value = false;
        });
}
</script>
<template>
    <div
        class="modal fade"
        id="addPurchaseModal"
        tabindex="-1"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Add Purchase</h5>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-6">
                            <label class="form-label small text-muted d-block"
                                >preferred supplier</label
                            >

                            <Select
                                v-model="p_supplier"
                                :options="suppliers"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select Supplier"
                                class="w-100"
                                appendTo="self"
                                :autoZIndex="true"
                                :baseZIndex="2000"
                            />
                        </div>
                    </div>

                    <div class="row g-4 mt-1">
                        <!-- Left: inventory picker -->
                        <div class="col-lg-5">
                            <div class="search-wrap mb-2">
                                <i class="bi bi-search"></i>
                                <input
                                    v-model="p_search"
                                    type="text"
                                    class="form-control search-input"
                                    placeholder="Search..."
                                />
                            </div>

                            <!-- Scrollable container -->
                            <div class="purchase-scroll">
                                <div
                                    v-for="it in items"
                                    :key="it.id"
                                    class="card shadow-sm border-0 rounded-4 mb-3"
                                >
                                    <div class="card-body">
                                        <div
                                            class="d-flex align-items-start gap-3"
                                        >
                                            <img
                                                :src="it.image_url"
                                                alt=""
                                                style="
                                                    width: 76px;
                                                    height: 76px;
                                                    object-fit: cover;
                                                    border-radius: 6px;
                                                "
                                            />
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold">
                                                    {{ it.name }}
                                                </div>
                                                <div class="text-muted small">
                                                    Category:
                                                    {{ it.category.name }}
                                                </div>
                                                <div class="text-muted small">
                                                    Unit:
                                                    {{ it.unit_name }}
                                                </div>
                                                <div class="text-muted small">
                                                    Stock: 12
                                                </div>
                                            </div>
                                            <button
                                                class="btn btn-primary rounded-pill px-3 py-1 btn-sm"
                                                @click="addPurchaseItem(it)"
                                            >
                                                Add
                                            </button>
                                        </div>

                                        <div class="row g-2 mt-3">
                                            <div class="col-4">
                                                <label class="small text-muted"
                                                    >Quantity</label
                                                >
                                                <input
                                                    v-model.number="it.qty"
                                                    type="number"
                                                    min="0"
                                                    class="form-control"
                                                />
                                            </div>
                                            <div class="col-4">
                                                <label class="small text-muted"
                                                    >Unit Price</label
                                                >
                                                <input
                                                    v-model.number="
                                                        it.unitPrice
                                                    "
                                                    type="number"
                                                    min="0"
                                                    class="form-control"
                                                />
                                            </div>
                                            <div class="col-4">
                                                <label class="small text-muted"
                                                    >Expiry Date</label
                                                >
                                                <input
                                                    v-model="it.expiry"
                                                    type="date"
                                                    class="form-control"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: cart -->
                        <div class="col-lg-7">
                            <div class="cart card border rounded-4">
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Name</th>

                                                <th>Quantity</th>
                                                <th>unit price</th>
                                                <th>expiry</th>
                                                <th>cost</th>
                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="(r, idx) in p_cart"
                                                :key="idx"
                                            >
                                                <td>
                                                    {{ r.name }}
                                                </td>

                                                <td>{{ r.qty }}</td>
                                                <td>
                                                    {{ money(r.unitPrice) }}
                                                </td>
                                                <td>
                                                    {{ r.expiry || "—" }}
                                                </td>
                                                <td>
                                                    {{ money(r.cost) }}
                                                </td>
                                                <td class="text-end">
                                                    <button
                                                        @click="
                                                            delPurchaseRow(idx)
                                                        "
                                                        class="inline-flex items-center justify-center p-2.5 rounded-full text-red-600 hover:bg-red-100"
                                                        title="Delete"
                                                    >
                                                        <svg
                                                            class="w-5 h-5"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            stroke-width="2"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0H7m4-3h2a1 1 0 011 1v1H8V5a1 1 0 011-1z"
                                                            />
                                                        </svg>
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr v-if="p_cart.length === 0">
                                                <td
                                                    colspan="7"
                                                    class="text-center text-muted py-4"
                                                >
                                                    No items added.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-end p-3 fw-semibold">
                                    Total Bill: {{ money(p_total) }}
                                </div>
                            </div>

                            <div class="mt-4 text-center">
                                <button
                                    class="btn btn-primary rounded-pill px-5 py-2"
                                    :disabled="
                                        p_submitting || p_cart.length === 0
                                    "
                                    @click="quickPurchaseSubmit"
                                >
                                    <span v-if="!p_submitting"
                                        >Quick Purchase</span
                                    >
                                    <span v-else>Saving...</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /modal-body -->
            </div>
        </div>
    </div>
</template>
