<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted, onUpdated } from "vue";

/* =============== Demo table data (replace with API later) =============== */
// const orders = ref([
//     {
//         id: 1,
//         supplier: "Wali Khan",
//         purchasedAt: "2025-08-30T02:47:00",
//         status: "completed",
//         total: 26.4,
//     },
//     {
//         id: 2,
//         supplier: "Noor",
//         purchasedAt: "2025-08-28T23:34:00",
//         status: "completed",
//         total: 3000.0,
//     },
// ]);

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

/* =============== Search on main list =============== */


/* =============== Static inventory & suppliers (replace with API) =============== */
const inventory = ref([
    {
        id: 101,
        name: "Basmati rice",
        image: "https://picsum.photos/seed/rice/72",
        category: "Poultry",
        unit: "gram (g)",
        stock: 2472,
        defaultPrice: 2.2,
    },
    {
        id: 102,
        name: "Chicken Breast",
        image: "https://picsum.photos/seed/chicken/72",
        category: "Poultry",
        unit: "gram (g)",
        stock: 680,
        defaultPrice: 5.5,
    },
]);

const showHelp = ref(false);

/* =========================================================================
   ADD PURCHASE (instant stock-in)
   =======================================================================*/
// const p_supplier = ref("Noor");
// const p_search = ref("");
// const p_filteredInv = computed(() => {
//     const t = p_search.value.trim().toLowerCase();
//     if (!t) return inventory.value;
//     return inventory.value.filter((i) =>
//         [i.name, i.category, i.unit].join(" ").toLowerCase().includes(t)
//     );
// });

const supplierOptions = ref([]);
const p_supplier = ref(null);

const fetchSuppliers = async () => {
    try {
        const res = await axios.get("/suppliers");
        supplierOptions.value = res.data.data; // Laravel paginator returns {data, links, meta}
    } catch (err) {
        console.error("Error fetching suppliers:", err);
    }
};

// =========================================================================

// =========================================================================

const inventoryItems = ref([]); // holds API data
const p_search = ref("");

const fetchInventory = async () => {
    try {
        const response = await axios.get("/inventory/api-inventories");
        inventoryItems.value = response.data.data;
        console.log("Fetched inventory:", inventoryItems.value);
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
const q = ref("");        // search input
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
const filteredOrders = computed(() => {
  const t = q.value.trim().toLowerCase();
  return orders.value.filter((o) => {
    const str = [o.supplier, o.status, String(o.total), o.purchasedAt].join(" ").toLowerCase();
    return str.includes(t);
  });
});




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

    if (!qty || qty <= 0) return alert("Enter a valid quantity.");
    if (!price || price <= 0) return alert("Enter a valid unit price.");

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
    if (!p_supplier.value) return alert("Please select a supplier.");
    if (!p_cart.value.length) return alert("No items added.");

    const payload = {
        supplier_id: p_supplier.value.id,
        purchase_date: new Date().toISOString().split("T")[0], // today
        status: "completed",
        items: p_cart.value.map(item => ({
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
        console.error("❌ Failed to create purchase:", err.response?.data || err.message);
        alert("Failed to create purchase.");
    } finally {
        p_submitting.value = false;
    }
}


/* =========================================================================
   ADD ORDER (later delivery) — MERGE on same product+unitPrice+expiry
   =======================================================================*/
const o_supplier = ref("Noor");
const o_search = ref("");
const o_filteredInv = computed(() => {
    const t = o_search.value.trim().toLowerCase();
    if (!t) return inventory.value;
    return inventory.value.filter((i) =>
        [i.name, i.category, i.unit].join(" ").toLowerCase().includes(t)
    );
});

// card inputs
const o_qty = ref(1);
const o_price = ref(""); // unit price
const o_expiry = ref(""); // expiry date

// cart rows: {id,name,category,qty,unitPrice,expiry,cost}
const o_cart = ref([]);
const o_total = computed(() =>
    round2(o_cart.value.reduce((s, r) => s + Number(r.cost || 0), 0))
);

function addOrderItem(item) {
    const qty = Number(o_qty.value || 0);
    const price =
        o_price.value !== ""
            ? Number(o_price.value)
            : Number(item.defaultPrice);
    const expiry = o_expiry.value || null;

    if (!qty || qty <= 0) return alert("Enter a valid quantity.");
    if (!price || price <= 0) return alert("Enter a valid unit price.");

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

    o_qty.value = 1;
    o_price.value = "";
    o_expiry.value = "";
}
function delOrderRow(idx) {
    o_cart.value.splice(idx, 1);
}

const o_submitting = ref(false);
function orderSubmit() {
    const payload = {
        supplier: o_supplier.value,
        items: o_cart.value.map(({ id, qty, unitPrice, expiry }) => ({
            id,
            qty,
            unitPrice,
            expiry,
        })),
        total: o_total.value,
        kind: "order_later",
    };
    console.log("[Order] payload:", payload);

    o_submitting.value = true;
    fakeApi(payload)
        .then((res) => console.log("✅ then():", res.message))
        .catch((err) => {
            console.error("❌ catch():", err?.message || err);
            alert("Failed (demo).");
        })
        .finally(() => {
            o_submitting.value = false;
            const m = bootstrap.Modal.getInstance(
                document.getElementById("addOrderModal")
            );
            m?.hide();
            o_cart.value = [];
        });
}

/* =============== Fake API =============== */
function fakeApi(data) {
    return new Promise((resolve) =>
        setTimeout(
            () => resolve({ ok: true, message: "Saved (demo)", data }),
            800
        )
    );
}

onMounted(() => window.feather?.replace());
onUpdated(() => window.feather?.replace());
</script>

<template>
    <Master>
        <div class="page-wrapper">
            <div class="container-fluid py-3">
                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <h3 class="fw-semibold mb-0">Purchase Order</h3>

                                <div class="position-relative">
                                    <button class="btn btn-link p-0 ms-2" @click="showHelp = !showHelp" title="Help">
                                        <i class="bi bi-question-circle fs-5"></i>
                                    </button>
                                    <div v-if="showHelp" class="help-popover shadow rounded-4 p-3">
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
                                    <input v-model="q" type="text" class="form-control search-input"
                                        placeholder="Search" />
                                </div>

                                <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal"
                                    data-bs-target="#addPurchaseModal">
                                    Purchase
                                </button>
                                <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal"
                                    data-bs-target="#addOrderModal">
                                    Order
                                </button>
                                <button class="btn btn-outline-secondary rounded-pill px-4">
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
                                    <template v-for="(row, i) in filteredOrders" :key="row.id">
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
                                            <td class="fw-semibold text-success">
                                                {{ row.status }}
                                            </td>
                                            <td>{{ money(row.total) }}</td>
                                            <td class="text-end">
                                                <div class="dropdown">
                                                    <button class="btn btn-link text-secondary p-0 fs-5"
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                        ⋮
                                                    </button>
                                                    <ul
                                                        class="dropdown-menu dropdown-menu-end shadow rounded-4 overflow-hidden">
                                                        <li>
                                                            <a class="dropdown-item py-2"
                                                                href="javascript:void(0)">View</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr class="sep-row">
                                            <td colspan="6"></td>
                                        </tr>
                                    </template>

                                    <tr v-if="filteredOrders.length === 0">
                                        <td colspan="6" class="text-center text-muted py-4">
                                            No purchase orders found.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ================= Add Purchase (Modal) ================= -->
                <div class="modal fade" id="addPurchaseModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content rounded-4">
                            <div class="modal-header">
                                <h5 class="modal-title fw-semibold">
                                    Add Purchase
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="row g-3 align-items-center">
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted d-block">preferred supplier</label>
                                        <div class="dropdown w-100">
                                            <button
                                                class="btn btn-light border rounded-3 w-100 d-flex justify-content-between align-items-center"
                                                data-bs-toggle="dropdown">
                                                {{
                                                    p_supplier
                                                        ? p_supplier.name
                                                        : "Select Supplier"
                                                }}
                                                <i class="bi bi-caret-down-fill"></i>
                                            </button>
                                            <ul class="dropdown-menu w-100 shadow rounded-3">
                                                <li v-for="s in supplierOptions" :key="s.id">
                                                    <a class="dropdown-item" href="javascript:void(0)"
                                                        @click="p_supplier = s">
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
                                            <input v-model="p_search" type="text" class="form-control search-input"
                                                placeholder="Search..." />
                                        </div>

                                        <div v-for="it in p_filteredInv" :key="it.id"
                                            class="card shadow-sm border-0 rounded-4 mb-3">
                                            <div class="card-body">
                                                <div class="d-flex align-items-start gap-3">
                                                    <img :src="it.image
                                                        ? `/storage/${it.image}`
                                                        : '/default.png'
                                                        " class="rounded" style="
                                                            width: 56px;
                                                            height: 56px;
                                                            object-fit: cover;
                                                        " />
                                                    <div class="flex-grow-1">
                                                        <div class="fw-semibold">
                                                            {{ it.name }}
                                                        </div>
                                                        <div class="text-muted small">
                                                            Category:
                                                            {{ it.category }}
                                                        </div>
                                                        <div class="text-muted small">
                                                            Unit: {{ it.unit }}
                                                        </div>
                                                        <div class="text-muted small">
                                                            Stock:
                                                            {{ it.stock }}
                                                        </div>
                                                    </div>
                                                    <button class="btn btn-primary px-3" @click="
                                                        addPurchaseItem(it)
                                                        ">
                                                        Add
                                                    </button>
                                                </div>

                                                <div class="row g-2 mt-3">
                                                    <div class="col-4">
                                                        <label class="small text-muted">Quantity</label>
                                                        <input v-model.number="it.qty
                                                            " type="number" min="0"
                                                            class="form-control form-control-lg" />
                                                    </div>
                                                    <div class="col-4">
                                                        <label class="small text-muted">Unit Price</label>
                                                        <input v-model.number="it.unitPrice
                                                            " type="number" min="0"
                                                            class="form-control form-control-lg" />
                                                    </div>
                                                    <div class="col-4">
                                                        <label class="small text-muted">Expiry Date</label>
                                                        <input v-model="it.expiry" type="date"
                                                            class="form-control form-control-lg" />
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
                                                            <th>Category</th>
                                                            <th>Quantity</th>
                                                            <th>unit price</th>
                                                            <th>expiry</th>
                                                            <th>cost</th>
                                                            <th class="text-end">
                                                                Action
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(r, idx) in p_cart" :key="idx">
                                                            <td>
                                                                {{ r.name }}
                                                            </td>
                                                            <td>
                                                                {{ r.category }}
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
                                                            <td class="text-end">
                                                                <button class="btn btn-sm btn-danger" @click="
                                                                    delPurchaseRow(
                                                                        idx
                                                                    )
                                                                    ">
                                                                    Delete
                                                                </button>
                                                            </td>
                                                        </tr>
                                                        <tr v-if="
                                                            p_cart.length ===
                                                            0
                                                        ">
                                                            <td colspan="7" class="text-center text-muted py-4">
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
                                            <button class="btn btn-primary btn-lg px-5 py-3" :disabled="p_submitting ||
                                                p_cart.length === 0
                                                " @click="quickPurchaseSubmit">
                                                <span v-if="!p_submitting">Quick Purchase</span>
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
                <div class="modal fade" id="addOrderModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content rounded-4">
                            <div class="modal-header">
                                <h5 class="modal-title fw-semibold">Order</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="row g-3 align-items-center">
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted d-block">preferred supplier</label>
                                        <div class="dropdown w-100">
                                            <button
                                                class="btn btn-light border rounded-3 w-100 d-flex justify-content-between align-items-center"
                                                data-bs-toggle="dropdown">
                                                {{ o_supplier }}
                                                <i class="bi bi-caret-down-fill"></i>
                                            </button>
                                            <ul class="dropdown-menu w-100 shadow rounded-3">
                                                <li v-for="s in supplierOptions" :key="s">
                                                    <a class="dropdown-item" href="javascript:void(0)"
                                                        @click="o_supplier = s">{{ s
                                                        }}</a>
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
                                            <input v-model="o_search" type="text" class="form-control search-input"
                                                placeholder="Search..." />
                                        </div>

                                        <div v-for="it in o_filteredInv" :key="it.id"
                                            class="card shadow-sm border-0 rounded-4 mb-3">
                                            <div class="card-body">
                                                <div class="d-flex align-items-start gap-3">
                                                    <img :src="it.image" class="rounded" style="
                                                            width: 56px;
                                                            height: 56px;
                                                            object-fit: cover;
                                                        " />
                                                    <div class="flex-grow-1">
                                                        <div class="fw-semibold">
                                                            {{ it.name }}
                                                        </div>
                                                        <div class="text-muted small">
                                                            Category:
                                                            {{ it.category }}
                                                        </div>
                                                        <div class="text-muted small">
                                                            Unit: {{ it.unit }}
                                                        </div>
                                                        <div class="text-muted small">
                                                            Stock:
                                                            {{ it.stock }}
                                                        </div>
                                                    </div>
                                                    <button class="btn btn-primary px-3" @click="
                                                        addOrderItem(it)
                                                        ">
                                                        Add
                                                    </button>
                                                </div>

                                                <div class="row g-2 mt-3">
                                                    <div class="col-4">
                                                        <label class="small text-muted">Quantity</label>
                                                        <input v-model.number="o_qty
                                                            " type="number" min="0"
                                                            class="form-control form-control-lg" />
                                                    </div>
                                                    <div class="col-4">
                                                        <label class="small text-muted">Unit Price</label>
                                                        <input v-model.number="o_price
                                                            " type="number" min="0"
                                                            class="form-control form-control-lg" />
                                                    </div>
                                                    <div class="col-4">
                                                        <label class="small text-muted">Expiry Date</label>
                                                        <input v-model="o_expiry" type="date"
                                                            class="form-control form-control-lg" />
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
                                                            <th>Category</th>
                                                            <th>Quantity</th>
                                                            <th>unit price</th>
                                                            <th>expiry</th>
                                                            <th>cost</th>
                                                            <th class="text-end">
                                                                Action
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(r, idx) in o_cart" :key="idx">
                                                            <td>
                                                                {{ r.name }}
                                                            </td>
                                                            <td>
                                                                {{ r.category }}
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
                                                            <td class="text-end">
                                                                <button class="btn btn-sm btn-danger" @click="
                                                                    delOrderRow(
                                                                        idx
                                                                    )
                                                                    ">
                                                                    Delete
                                                                </button>
                                                            </td>
                                                        </tr>
                                                        <tr v-if="
                                                            o_cart.length ===
                                                            0
                                                        ">
                                                            <td colspan="7" class="text-center text-muted py-4">
                                                                No items added.
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="text-end p-3 fw-semibold">
                                                Total Bill: {{ money(o_total) }}
                                            </div>
                                        </div>

                                        <div class="mt-4 text-center">
                                            <button class="btn btn-primary btn-lg px-5 py-3" :disabled="o_submitting ||
                                                o_cart.length === 0
                                                " @click="orderSubmit">
                                                <span v-if="!o_submitting">Order</span>
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
</style>
