<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted, onUpdated, reactive, watch } from "vue";
import { toast } from "vue3-toastify";
import Select from "primevue/select";

/* =============== Helpers =============== */
const money = (n, currency = "GBP") =>
    new Intl.NumberFormat("en-GB", { style: "currency", currency }).format(
        Number(n || 0)
    );

const fmtDateTime = (iso) =>
    new Date(iso).toLocaleString("en-GB", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        hour12: true,
    });

const round2 = (n) => Math.round((Number(n) || 0) * 100) / 100;

const showHelp = ref(false);

const props = defineProps({
    orders: Array,
});
const orderData = computed(() => props.orders.data);

const supplierOptions = ref([]);
const p_supplier = ref(null);

const fetchSuppliers = async () => {
    const res = await axios.get("/suppliers/pluck");
    supplierOptions.value = res.data; // [{id, name}]
};

// =========================================================================
// Inventory retrieval for Add Purchase modal
// ========================================================================

const inventoryItems = ref([]); // holds API data
const p_search = ref("");

const fetchInventory = async () => {
    try {
        const response = await axios.get("/inventory/api-inventories");
        inventoryItems.value = response.data.data;
    } catch (error) {
        console.error("Error fetching inventory:", error);
    }
};

onMounted(() => {
    fetchInventory();
    fetchSuppliers();
    fetchOrders();
});
const p_filteredInv = computed(() => {
    const t = p_search.value.trim().toLowerCase();
    if (!t) return inventoryItems.value;
    return inventoryItems.value.filter((i) =>
        [i.name, i.category ?? "", i.subcategory ?? "", String(i.stock ?? "")]
            .join(" ")
            .toLowerCase()
            .includes(t)
    );
});

// ========================================================================
// Purchase Items Retrieval
// ========================================================================
const orders = ref([]);
const loading = ref(false);

// Search & filter
const q = ref(""); // search input
const statusFilter = ref(""); // optional status filter

// Fetch orders from API
async function fetchOrders() {
    loading.value = true;
    try {
        const res = await axios.get("/purchase-orders", {
            params: {
                q: q.value,
                status: statusFilter.value || undefined,
            },
        });
        orders.value = res.data;
    } catch (err) {
        console.error("Failed to fetch orders:", err);
        orders.value = [];
    } finally {
        loading.value = false;
    }
}

// Computed filteredOrders for search
// const filteredOrders = computed(() => {
//   const t = q.value.trim().toLowerCase();
//   return props.orders.value.filter((o) => {
//     const str = [o.supplier, o.status, String(o.total), o.purchasedAt].join(" ").toLowerCase();
//     return str.includes(t);
//   });
// });

// ==========================================================================

// left card inputs (shared across cards for demo)
const p_qty = ref(1);
const p_price = ref(""); // unit price
const p_expiry = ref(""); // expiry date

// cart rows: {id,name,category,qty,unitPrice,expiry,cost}
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

// ===========================Submitting Quick Purchase===========================
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

/* =========================================================================
   ADD ORDER (later delivery) — MERGE on same product+unitPrice+expiry
   =======================================================================*/
const o_supplier = ref(null);
const o_search = ref("");

// cart rows: {id,name,category,qty,unitPrice,expiry,cost}
const o_cart = ref([]);
const o_total = computed(() =>
    round2(o_cart.value.reduce((s, r) => s + Number(r.cost || 0), 0))
);

function addOrderItem(item) {
    const qty = Number(item.qty || 0);
    const price =
        item.unitPrice !== "" && item.unitPrice != null
            ? Number(item.unitPrice)
            : Number(item.defaultPrice || 0);
    const expiry = item.expiry || null;

    if (!qty || qty <= 0) return toast.error("Enter a valid quantity.");
    if (!price || price <= 0) return toast.error("Enter a valid unit price.");
    if (!expiry || expiry <= 0) return toast.error("Enter an expiry date.");

    // MERGE: same product + same unitPrice + same expiry
    const found = o_cart.value.find(
        (r) => r.id === item.id && r.unitPrice === price && r.expiry === expiry
    );

    if (found) {
        found.qty = round2(found.qty + qty);
        found.cost = round2(found.qty * found.unitPrice);
    } else {
        o_cart.value.push({
            id: item.id,
            name: item.name,
            category: item.category,
            qty,
            unitPrice: price,
            expiry,
            cost: round2(qty * price),
        });
    }

    // reset only this item's fields
    item.qty = null;
    item.unitPrice = null;
    item.expiry = null;
}

function delOrderRow(idx) {
    o_cart.value.splice(idx, 1);
}

// ============================================================================
// Submitting Order
// ============================================================================
// Add these to your Vue component's setup() function

// Add these to your Vue component's setup() function

const selectedOrder = ref(null);
const editingOrder = ref(null);
const editItems = ref([]);
const isEditing = ref(false);

// Open modal function
async function openModal(order) {
    try {
        // Fetch full order details with items
        const response = await axios.get(`/purchase-orders/${order.id}`);
        selectedOrder.value = response.data;

        if (order.status === "pending") {
            // For pending orders, make items editable
            isEditing.value = true;
            editItems.value = selectedOrder.value.items.map((item) => ({
                id: item.id,
                product_id: item.product_id,
                name: item.product?.name || "Unknown Product",
                quantity: item.quantity,
                unit_price: item.unit_price,
                expiry: item.expiry || "",
                sub_total: item.sub_total,
            }));

            // Show edit modal
            const modal = new bootstrap.Modal(
                document.getElementById("editOrderModal")
            );
            modal.show();
        } else {
            // For completed orders, show view-only modal
            isEditing.value = false;
            const modal = new bootstrap.Modal(
                document.getElementById("viewOrderModal")
            );
            modal.show();
        }
    } catch (error) {
        console.error("Error fetching order details:", error);
        alert("Failed to load order details");
    }
}

// Update order function
const updating = ref(false);

async function updateOrder() {
    if (!selectedOrder.value) return;

    updating.value = true;
    try {
        const payload = {
            status: "completed",
            items: editItems.value.map((item) => ({
                purchase_id: selectedOrder.value.id,
                product_id: item.product_id,
                qty: item.quantity, // Changed from 'quantity' to 'qty'
                unit_price: item.unit_price,
                expiry: item.expiry || null,
            })),
        };
        console.log("Update payload:", payload);
        await axios.put(`/purchase-orders/${selectedOrder.value.id}`, payload);

        // Close modal and refresh orders
        const modal = bootstrap.Modal.getInstance(
            document.getElementById("editOrderModal")
        );
        modal?.hide();

        // Refresh the orders list
        await fetchOrders();

        alert("Order updated successfully and stock entries created!");
    } catch (error) {
        console.error("Error updating order:", error);
        alert("Failed to update order");
    } finally {
        updating.value = false;
    }
}

// Calculate subtotal for editing
function calculateSubtotal(item) {
    const quantity = parseFloat(item.quantity) || 0;
    const unitPrice = parseFloat(item.unit_price) || 0;
    item.sub_total = (quantity * unitPrice).toFixed(2);
}

// ===========================================================

// ===========================================================
// central place to keep form errors
const p_errors = reactive({
    supplier: "",
});
const o_submitting = ref(false);

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

// clear the supplier error as soon as a value is chosen
watch(p_supplier, (v) => {
    if (v && p_errors.supplier) p_errors.supplier = "";
});
// ===========================================================

onMounted(() => window.feather?.replace());
onUpdated(() => window.feather?.replace());
</script>

<template>
    <Master>
        <div class="page-wrapper">
            <div class="container-fluid py-3">
                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-body p-4">
                        <div
                            class="d-flex align-items-center justify-content-between mb-3"
                        >
                            <div class="d-flex align-items-center gap-2">
                                <h3 class="fw-semibold mb-0">Purchase Order</h3>

                                <div class="position-relative">
                                    <button
                                        class="btn btn-link p-0 ms-2"
                                        @click="showHelp = !showHelp"
                                        title="Help"
                                    >
                                        <i
                                            class="bi bi-question-circle fs-5"
                                        ></i>
                                    </button>
                                    <div
                                        v-if="showHelp"
                                        class="help-popover shadow rounded-4 p-3"
                                    >
                                        <p class="mb-2">
                                            This screen allows you to view,
                                            manage, and update all purchase
                                            orders.
                                        </p>
                                        <p class="mb-2">
                                            <strong>Add Purchase</strong>:
                                            create a purchase and stock-in
                                            immediately.
                                        </p>
                                        <p class="mb-0">
                                            <strong>Add Order</strong>: create a
                                            purchase order for later delivery.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 align-items-center">
                                <div class="search-wrap me-1">
                                    <i class="bi bi-search"></i>
                                    <input
                                        v-model="q"
                                        type="text"
                                        class="form-control search-input"
                                        placeholder="Search"
                                    />
                                </div>

                                <button
                                    class="btn btn-primary rounded-pill px-4"
                                    data-bs-toggle="modal"
                                    data-bs-target="#addPurchaseModal"
                                >
                                    Purchase
                                </button>
                                <button
                                    class="btn btn-primary rounded-pill px-4"
                                    data-bs-toggle="modal"
                                    data-bs-target="#addOrderModal"
                                >
                                    Order
                                </button>
                                <button
                                    class="btn btn-outline-secondary rounded-pill px-4"
                                >
                                    Download
                                </button>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table align-middle purchase-table">
                                <thead class="small text-muted">
                                    <tr>
                                        <th style="width: 80px">S. #</th>
                                        <th>Supplier Name</th>
                                        <th>Purchase date</th>
                                        <th>Status</th>
                                        <th>Total value</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template
                                        v-for="(row, i) in orderData"
                                        :key="row.id"
                                    >
                                        <tr>
                                            <td>{{ i + 1 }}</td>
                                            <td>{{ row.supplier }}</td>
                                            <td class="text-nowrap">
                                                {{
                                                    fmtDateTime(
                                                        row.purchasedAt
                                                    ).split(",")[0]
                                                }},
                                                <div class="small text-muted">
                                                    {{
                                                        fmtDateTime(
                                                            row.purchasedAt
                                                        )
                                                            .split(",")[1]
                                                            ?.trim()
                                                    }}
                                                </div>
                                            </td>
                                            <td
                                                class="fw-semibold text-success"
                                            >
                                                {{ row.status }}
                                            </td>
                                            <td>{{ money(row.total) }}</td>
                                            <td class="text-end">
                                                <div class="dropdown">
                                                    <button
                                                        class="btn btn-link text-secondary p-0 fs-5"
                                                        data-bs-toggle="dropdown"
                                                        aria-expanded="false"
                                                    >
                                                        ⋮
                                                    </button>
                                                    <ul
                                                        class="dropdown-menu dropdown-menu-end shadow rounded-4 overflow-hidden"
                                                    >
                                                        <li>
                                                            <a
                                                                class="dropdown-item py-2"
                                                                href="javascript:void(0)"
                                                                @click="
                                                                    openModal(
                                                                        row
                                                                    )
                                                                "
                                                                >View</a
                                                            >
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr class="sep-row">
                                            <td colspan="6"></td>
                                        </tr>
                                    </template>

                                    <!-- Fix this line -->
                                    <tr v-if="orderData.length === 0">
                                        <td
                                            colspan="6"
                                            class="text-center text-muted py-4"
                                        >
                                            No purchase orders found.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ================= Add Purchase (Modal) ================= -->
                <div
                    class="modal fade"
                    id="addPurchaseModal"
                    tabindex="-1"
                    aria-hidden="true"
                >
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content rounded-4">
                            <div class="modal-header">
                                <h5 class="modal-title fw-semibold">
                                    Add Purchase
                                </h5>
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
                                        <label
                                            class="form-label small text-muted d-block"
                                            >preferred supplier</label
                                        >

                                        <Select
                                            v-model="p_supplier"
                                            :options="supplierOptions"
                                            optionLabel="name"
                                            optionValue="id"
                                            placeholder="Select Supplier"
                                            class="w-100"
                                            appendTo="self"
                                            :autoZIndex="true"
                                            :baseZIndex="2000"
                                        />

                                       <!-- Tes {{ p_errors }} -->
                                        <!-- <small
                                            v-if="formErrors.supplier_id"
                                            class="text-danger"
                                        >
                                            {{ formErrors.supplier_id[0] }}
                                        </small> -->
                                        <!-- <div class="dropdown w-100">
                                            <button
                                                class="btn btn-light border rounded-3 w-100 d-flex justify-content-between align-items-center"
                                                data-bs-toggle="dropdown"
                                            >
                                                {{
                                                    p_supplier
                                                        ? p_supplier.name
                                                        : "Select Supplier"
                                                }}
                                                <i
                                                    class="bi bi-caret-down-fill"
                                                ></i>
                                            </button>
                                            <ul
                                                class="dropdown-menu w-100 shadow rounded-3"
                                            >
                                                <li
                                                    v-for="s in supplierOptions"
                                                    :key="s.id"
                                                >
                                                    <a
                                                        class="dropdown-item"
                                                        href="javascript:void(0)"
                                                        @click="p_supplier = s"
                                                    >
                                                        {{ s.name }}
                                                    </a>
                                                </li>
                                            </ul>
                                        </div> -->
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
                                                v-for="it in p_filteredInv"
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
                                                        <div
                                                            class="flex-grow-1"
                                                        >
                                                            <div
                                                                class="fw-semibold"
                                                            >
                                                                {{ it.name }}
                                                            </div>
                                                            <div
                                                                class="text-muted small"
                                                            >
                                                                Category:
                                                                {{
                                                                    it.category
                                                                        .name
                                                                }}
                                                            </div>
                                                            <div
                                                                class="text-muted small"
                                                            >
                                                                Unit:
                                                                {{
                                                                    it.unit_name
                                                                }}
                                                            </div>
                                                            <div
                                                                class="text-muted small"
                                                            >
                                                                Stock: 12
                                                            </div>
                                                        </div>
                                                        <button
                                                            class="btn btn-primary rounded-pill px-3 py-1 btn-sm"
                                                            @click="
                                                                addPurchaseItem(
                                                                    it
                                                                )
                                                            "
                                                        >
                                                            Add
                                                        </button>
                                                    </div>

                                                    <div class="row g-2 mt-3">
                                                        <div class="col-4">
                                                            <label
                                                                class="small text-muted"
                                                                >Quantity</label
                                                            >
                                                            <input
                                                                v-model.number="
                                                                    it.qty
                                                                "
                                                                type="number"
                                                                min="0"
                                                                class="form-control"
                                                            />
                                                        </div>
                                                        <div class="col-4">
                                                            <label
                                                                class="small text-muted"
                                                                >Unit
                                                                Price</label
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
                                                            <label
                                                                class="small text-muted"
                                                                >Expiry
                                                                Date</label
                                                            >
                                                            <input
                                                                v-model="
                                                                    it.expiry
                                                                "
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
                                                <table
                                                    class="table align-middle mb-0"
                                                >
                                                    <thead>
                                                        <tr>
                                                            <th>Name</th>

                                                            <th>Quantity</th>
                                                            <th>unit price</th>
                                                            <th>expiry</th>
                                                            <th>cost</th>
                                                            <th
                                                                class="text-end"
                                                            >
                                                                Action
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr
                                                            v-for="(
                                                                r, idx
                                                            ) in p_cart"
                                                            :key="idx"
                                                        >
                                                            <td>
                                                                {{ r.name }}
                                                            </td>

                                                            <td>{{ r.qty }}</td>
                                                            <td>
                                                                {{
                                                                    money(
                                                                        r.unitPrice
                                                                    )
                                                                }}
                                                            </td>
                                                            <td>
                                                                {{
                                                                    r.expiry ||
                                                                    "—"
                                                                }}
                                                            </td>
                                                            <td>
                                                                {{
                                                                    money(
                                                                        r.cost
                                                                    )
                                                                }}
                                                            </td>
                                                            <td
                                                                class="text-end"
                                                            >
                                                                <button
                                                                    @click="
                                                                        delPurchaseRow(
                                                                            idx
                                                                        )
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
                                                        <tr
                                                            v-if="
                                                                p_cart.length ===
                                                                0
                                                            "
                                                        >
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
                                            <div
                                                class="text-end p-3 fw-semibold"
                                            >
                                                Total Bill: {{ money(p_total) }}
                                            </div>
                                        </div>

                                        <div class="mt-4 text-center">
                                            <button
                                                class="btn btn-primary rounded-pill px-5 py-2"
                                                :disabled="
                                                    p_submitting ||
                                                    p_cart.length === 0
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

                <!-- ================= Add Order (Modal) ================= -->
                <div
                    class="modal fade"
                    id="addOrderModal"
                    tabindex="-1"
                    aria-hidden="true"
                >
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content rounded-4">
                            <div class="modal-header">
                                <h5 class="modal-title fw-semibold">Order</h5>
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
                                        <label
                                            class="form-label small text-muted d-block"
                                            >preferred supplier</label
                                        >
                                        <div class="dropdown w-100">
                                            <button
                                                class="btn btn-light border rounded-3 w-100 d-flex justify-content-between align-items-center"
                                                data-bs-toggle="dropdown"
                                            >
                                                {{
                                                    p_supplier
                                                        ? p_supplier.name
                                                        : "Select Supplier"
                                                }}
                                                <i
                                                    class="bi bi-caret-down-fill"
                                                ></i>
                                            </button>
                                            <ul
                                                class="dropdown-menu w-100 shadow rounded-3"
                                            >
                                                <li
                                                    v-for="s in supplierOptions"
                                                    :key="s.id"
                                                >
                                                    <a
                                                        class="dropdown-item"
                                                        href="javascript:void(0)"
                                                        @click="p_supplier = s"
                                                    >
                                                        {{ s.name }}
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-4 mt-1">
                                    <!-- Left: inventory picker -->
                                    <div class="col-lg-5">
                                        <div class="search-wrap mb-2">
                                            <i class="bi bi-search"></i>
                                            <input
                                                v-model="o_search"
                                                type="text"
                                                class="form-control search-input"
                                                placeholder="Search..."
                                            />
                                        </div>

                                        <div
                                            v-for="it in p_filteredInv"
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
                                                        <div
                                                            class="fw-semibold"
                                                        >
                                                            {{ it.name }}
                                                        </div>
                                                        <div
                                                            class="text-muted small"
                                                        >
                                                            Category:
                                                            {{
                                                                it.category.name
                                                            }}
                                                        </div>
                                                        <div
                                                            class="text-muted small"
                                                        >
                                                            Unit: {{ it.unit }}
                                                        </div>
                                                        <div
                                                            class="text-muted small"
                                                        >
                                                            Stock:
                                                            {{ it.stock }}
                                                        </div>
                                                    </div>
                                                    <button
                                                        class="btn btn-primary rounded-pill px-3 py-1"
                                                        @click="
                                                            addOrderItem(it)
                                                        "
                                                    >
                                                        Add
                                                    </button>
                                                </div>

                                                <div class="row g-2 mt-3">
                                                    <div class="col-4">
                                                        <label
                                                            class="small text-muted"
                                                            >Quantity</label
                                                        >
                                                        <input
                                                            v-model.number="
                                                                it.qty
                                                            "
                                                            type="number"
                                                            min="0"
                                                            class="form-control form-control"
                                                        />
                                                    </div>
                                                    <div class="col-4">
                                                        <label
                                                            class="small text-muted"
                                                            >Unit Price</label
                                                        >
                                                        <input
                                                            v-model.number="
                                                                it.unitPrice
                                                            "
                                                            type="number"
                                                            min="0"
                                                            class="form-control form-control"
                                                        />
                                                    </div>
                                                    <div class="col-4">
                                                        <label
                                                            class="small text-muted"
                                                            >Expiry Date</label
                                                        >
                                                        <input
                                                            v-model="it.expiry"
                                                            type="date"
                                                            class="form-control form-control"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right: cart -->
                                    <div class="col-lg-7">
                                        <div class="cart card border rounded-4">
                                            <div class="table-responsive">
                                                <table
                                                    class="table align-middle mb-0"
                                                >
                                                    <thead>
                                                        <tr>
                                                            <th>Name</th>

                                                            <th>Quantity</th>
                                                            <th>unit price</th>
                                                            <th>expiry</th>
                                                            <th>cost</th>
                                                            <th
                                                                class="text-end"
                                                            >
                                                                Action
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr
                                                            v-for="(
                                                                r, idx
                                                            ) in o_cart"
                                                            :key="idx"
                                                        >
                                                            <td>
                                                                {{ r.name }}
                                                            </td>

                                                            <td>{{ r.qty }}</td>
                                                            <td>
                                                                {{
                                                                    r.unitPrice
                                                                }}
                                                            </td>
                                                            <td>
                                                                {{
                                                                    r.expiry ||
                                                                    "—"
                                                                }}
                                                            </td>
                                                            <td>
                                                                {{ r.cost }}
                                                            </td>
                                                            <td
                                                                class="text-end"
                                                            >
                                                                <button
                                                                    @click="
                                                                        delOrderRow(
                                                                            idx
                                                                        )
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
                                                        <tr
                                                            v-if="
                                                                o_cart.length ===
                                                                0
                                                            "
                                                        >
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
                                            <div
                                                class="text-end p-3 fw-semibold"
                                            >
                                                Total Bill: {{ money(o_total) }}
                                            </div>
                                        </div>

                                        <div class="mt-4 text-center">
                                            <button
                                                class="btn btn-primary btn-lg px-5 py-3"
                                                :disabled="
                                                    o_submitting ||
                                                    o_cart.length === 0
                                                "
                                                @click="orderSubmit"
                                            >
                                                <span v-if="!o_submitting"
                                                    >Order</span
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

                <!-- ====================View Modal either Purchase or Order ==================== -->
                <!-- View Order Modal (Read-only) -->
                <div
                    class="modal fade"
                    id="viewOrderModal"
                    tabindex="-1"
                    aria-hidden="true"
                >
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content rounded-4">
                            <div class="modal-header">
                                <h5 class="modal-title fw-semibold">
                                    Purchase Order Details
                                </h5>
                                <button
                                    type="button"
                                    class="btn-close"
                                    data-bs-dismiss="modal"
                                    aria-label="Close"
                                ></button>
                            </div>

                            <div class="modal-body" v-if="selectedOrder">
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <p>
                                            <strong>Supplier:</strong>
                                            {{
                                                selectedOrder.supplier?.name ||
                                                "N/A"
                                            }}
                                        </p>
                                        <p>
                                            <strong>Purchase Date:</strong>
                                            {{
                                                fmtDateTime(
                                                    selectedOrder.purchase_date
                                                ).split(",")[0]
                                            }}
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p>
                                            <strong>Status:</strong>
                                            <span class="badge bg-success">{{
                                                selectedOrder.status
                                            }}</span>
                                        </p>
                                        <p>
                                            <strong>Total:</strong>
                                            {{
                                                money(
                                                    selectedOrder.total_amount
                                                )
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Items Table -->
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Product Name</th>
                                                <th>Quantity</th>
                                                <th>Unit Price</th>
                                                <th>Subtotal</th>
                                                <th>Expiry Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="item in selectedOrder.items"
                                                :key="item.id"
                                            >
                                                <td>
                                                    {{
                                                        item.product?.name ||
                                                        "Unknown Product"
                                                    }}
                                                </td>
                                                <td>{{ item.quantity }}</td>
                                                <td>
                                                    {{ money(item.unit_price) }}
                                                </td>
                                                <td>
                                                    {{ money(item.sub_total) }}
                                                </td>
                                                <td>
                                                    {{ item.expiry || "—" }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button
                                    type="button"
                                    class="btn btn-secondary"
                                    data-bs-dismiss="modal"
                                >
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Order Modal (Editable) -->
                <div
                    class="modal fade"
                    id="editOrderModal"
                    tabindex="-1"
                    aria-hidden="true"
                >
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content rounded-4">
                            <div class="modal-header">
                                <h5 class="modal-title fw-semibold">
                                    Edit Purchase Order
                                </h5>
                                <button
                                    type="button"
                                    class="btn-close"
                                    data-bs-dismiss="modal"
                                    aria-label="Close"
                                ></button>
                            </div>

                            <div class="modal-body" v-if="selectedOrder">
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <p>
                                            <strong>Supplier:</strong>
                                            {{
                                                selectedOrder.supplier?.name ||
                                                "N/A"
                                            }}
                                        </p>
                                        <p>
                                            <strong>Purchase Date:</strong>
                                            {{
                                                fmtDateTime(
                                                    selectedOrder.purchase_date
                                                ).split(",")[0]
                                            }}
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p>
                                            <strong>Current Status:</strong>
                                            <span
                                                class="badge bg-warning text-dark"
                                                >{{
                                                    selectedOrder.status
                                                }}</span
                                            >
                                        </p>
                                        <p>
                                            <strong>Total:</strong>
                                            {{
                                                money(
                                                    selectedOrder.total_amount
                                                )
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Editable Items Table -->
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th style="width: 25%">
                                                    Product Name
                                                </th>
                                                <th style="width: 15%">
                                                    Quantity
                                                </th>
                                                <th style="width: 15%">
                                                    Unit Price
                                                </th>
                                                <th style="width: 15%">
                                                    Subtotal
                                                </th>
                                                <th style="width: 20%">
                                                    Expiry Date
                                                </th>
                                                <th style="width: 10%">
                                                    Action
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="(
                                                    item, index
                                                ) in editItems"
                                                :key="item.id || index"
                                            >
                                                <td>
                                                    <input
                                                        v-model="item.name"
                                                        type="text"
                                                        class="form-control"
                                                        readonly
                                                        style="
                                                            background-color: #f8f9fa;
                                                        "
                                                    />
                                                </td>
                                                <td>
                                                    <input
                                                        v-model.number="
                                                            item.quantity
                                                        "
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        class="form-control"
                                                        @input="
                                                            calculateSubtotal(
                                                                item
                                                            )
                                                        "
                                                    />
                                                </td>
                                                <td>
                                                    <input
                                                        v-model.number="
                                                            item.unit_price
                                                        "
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        class="form-control"
                                                        @input="
                                                            calculateSubtotal(
                                                                item
                                                            )
                                                        "
                                                    />
                                                </td>
                                                <td>
                                                    <input
                                                        v-model="item.sub_total"
                                                        type="text"
                                                        class="form-control"
                                                        readonly
                                                        style="
                                                            background-color: #f8f9fa;
                                                        "
                                                    />
                                                </td>
                                                <td>
                                                    <input
                                                        v-model="item.expiry"
                                                        type="date"
                                                        class="form-control"
                                                    />
                                                </td>
                                                <td>
                                                    <button
                                                        class="btn btn-danger btn-sm"
                                                        @click="
                                                            editItems.splice(
                                                                index,
                                                                1
                                                            )
                                                        "
                                                        type="button"
                                                    >
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr v-if="editItems.length === 0">
                                                <td
                                                    colspan="6"
                                                    class="text-center text-muted py-3"
                                                >
                                                    No items found
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td
                                                    colspan="3"
                                                    class="text-end fw-bold"
                                                >
                                                    Total:
                                                </td>
                                                <td class="fw-bold">
                                                    {{
                                                        money(
                                                            editItems.reduce(
                                                                (sum, item) =>
                                                                    sum +
                                                                    parseFloat(
                                                                        item.sub_total ||
                                                                            0
                                                                    ),
                                                                0
                                                            )
                                                        )
                                                    }}
                                                </td>
                                                <td colspan="2"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <div class="alert alert-info mt-3">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Note:</strong> Updating this order
                                    will change its status to "completed" and
                                    create stock entries for all items.
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button
                                    type="button"
                                    class="btn btn-secondary"
                                    data-bs-dismiss="modal"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-success"
                                    @click="updateOrder"
                                    :disabled="
                                        updating || editItems.length === 0
                                    "
                                >
                                    <span v-if="updating">
                                        <span
                                            class="spinner-border spinner-border-sm me-2"
                                        ></span>
                                        Updating...
                                    </span>
                                    <span v-else
                                        >Complete Order & Update Stock</span
                                    >
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Master>
</template>

<style scoped>
/* Search pill */
.search-wrap {
    position: relative;
    width: clamp(240px, 30vw, 420px);
}

.search-wrap .bi-search {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
}

.search-input {
    padding-left: 38px;
    border-radius: 9999px;
    background: #fff;
}

/* Brand buttons */
.btn-primary {
    background: #1c0d82;
    border-color: #1c0d82;
}

.btn-primary:hover {
    filter: brightness(1.05);
}

/* Table look */
.purchase-table thead th {
    font-weight: 600;
    border-bottom: 2px solid #111;
}

.sep-row td {
    border-bottom: 2px solid #111;
    height: 12px;
    padding: 0;
}

/* Help popover */
.help-popover {
    position: absolute;
    left: 12px;
    top: 34px;
    width: min(360px, 70vw);
    background: #fff;
    z-index: 2000;
}

/* Cart */
.cart thead th {
    font-weight: 600;
}

/* Responsive */
@media (max-width: 575.98px) {
    .search-wrap {
        width: 100%;
    }
}

/* card scrollig */
.purchase-scroll {
    max-height: 65vh; /* adjust as you like */
    overflow-y: auto;
    padding-right: 0.25rem; /* prevent content jump near scrollbar */
    overscroll-behavior: contain;
}

/* Optional pretty scrollbar */
.purchase-scroll::-webkit-scrollbar {
    width: 8px;
}
.purchase-scroll::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 999px;
}
.purchase-scroll::-webkit-scrollbar-track {
    background: transparent;
}
</style>
