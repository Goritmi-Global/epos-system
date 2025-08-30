<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted, onUpdated } from "vue";

/* ---- Demo data (swap with API) ---- */
const items = ref([
  {
    id: 1,
    name: "Basmati Rice",
    image: "https://picsum.photos/seed/rice/64",
    unitPrice: 2.2,
    category: "Grains",
    unit: "kilogram (kg)",
    availableStock: 120.0,
    stockValue: 264.0,
    enteredBy: "Bilal",
  },
  {
    id: 2,
    name: "Chicken Breast",
    image: "https://picsum.photos/seed/chicken/64",
    unitPrice: 5.5,
    category: "Meat",
    unit: "kilogram (kg)",
    availableStock: 80.0,
    stockValue: 440.0,
    enteredBy: "Admin",
  },
  {
    id: 3,
    name: "Fresh Tomatoes",
    image: "https://picsum.photos/seed/tomato/64",
    unitPrice: 1.8,
    category: "Vegetables",
    unit: "kilogram (kg)",
    availableStock: 200.0,
    stockValue: 360.0,
    enteredBy: "Shakir",
  },
  {
    id: 4,
    name: "Olive Oil",
    image: "https://picsum.photos/seed/oliveoil/64",
    unitPrice: 10.0,
    category: "Grocery",
    unit: "liter (L)",
    availableStock: 50.0,
    stockValue: 500.0,
    enteredBy: "Jamal",
  },
  {
    id: 5,
    name: "Cheddar Cheese",
    image: "https://picsum.photos/seed/cheese/64",
    unitPrice: 4.5,
    category: "Dairy",
    unit: "kilogram (kg)",
    availableStock: 60.0,
    stockValue: 270.0,
    enteredBy: "Saf",
  },
  
]);


/* ---- Search ---- */
const q = ref("");
const filteredItems = computed(() => {
    const term = q.value.trim().toLowerCase();
    if (!term) return items.value;
    return items.value.filter((i) =>
        [i.name, i.category, i.unit].some((v) =>
            (v || "").toLowerCase().includes(term)
        )
    );
});

/* ---- KPIs ---- */
const categoriesCount = computed(
    () => new Set(items.value.map((i) => i.category)).size
);
const totalItems = computed(() => items.value.length);
const lowStockCount = computed(
    () => items.value.filter((i) => i.availableStock < 5).length
);
const outOfStockCount = computed(
    () => items.value.filter((i) => i.availableStock <= 0).length
);
const kpis = computed(() => [
    { label: "Categories", value: categoriesCount.value, icon: "layers" },
    { label: "Total Items", value: totalItems.value, icon: "package" },
    { label: "Low Stock", value: lowStockCount.value, icon: "alert-triangle" },
    { label: "Out of Stock", value: outOfStockCount.value, icon: "slash" },
]);
// Ensure Feather replaces <i data-feather> after render:
onMounted(() => window.feather?.replace());
onUpdated(() => window.feather?.replace());

/* ---- Helpers ---- */
const money = (amount, currency = "GBP") =>
    new Intl.NumberFormat("en-GB", { style: "currency", currency }).format(
        amount
    );

/* ---- Actions (wire later) ---- */
const onStockIn = (it) => {};
const onStockOut = (it) => {};
const onViewItem = (it) => {};
const onEditItem = (it) => {};
const onDownload = (type) => {}; // 'pdf' | 'excel'
</script>

<template>
    <Master>
        <div class="page-wrapper">
            <div class="container-fluid py-3">
                <!-- Title -->
                <h4 class="fw-semibold mb-3">Overall Inventory</h4>

                <!-- KPI Cards -->
                <div class="row g-3">
                    <div
                        v-for="c in kpis"
                        :key="c.label"
                        class="col-6 col-md-3"
                    >
                        <div
                            class="kpi-card card border-0 shadow-sm rounded-4"
                        >
                            <div
                                class="card-body d-flex flex-column justify-content-center text-center"
                            >
                                <div class="icon-wrap mb-2">
                                    <i :class="c.icon"></i>
                                </div>
                                <div class="kpi-label text-muted">
                                    {{ c.label }}
                                </div>
                                <div class="kpi-value">{{ c.value }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stock Table -->
                <div class="card border-0 shadow-lg rounded-4 mt-0">
                    <div class="card-body">
                        <!-- Toolbar -->
                        <div
                            class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3"
                        >
                            <h5 class="mb-0 fw-semibold">Stock</h5>

                            <div
                                class="d-flex flex-wrap gap-2 align-items-center"
                            >
                                <div class="search-wrap">
                                    <i class="bi bi-search"></i>
                                    <input
                                        v-model="q"
                                        type="text"
                                        class="form-control search-input"
                                        placeholder="Search"
                                    />
                                </div>

                                <!-- Add Item -> open modal -->
                                <button
                                    class="btn btn-primary rounded-pill px-4"
                                    data-bs-toggle="modal"
                                    data-bs-target="#createItemModal"
                                >
                                    Add Item
                                </button>

                                <!-- Download all dropdown -->
                                <div class="dropdown">
                                    <button
                                        class="btn btn-outline-secondary rounded-pill px-4 dropdown-toggle"
                                        data-bs-toggle="dropdown"
                                    >
                                        Download all
                                    </button>
                                    <ul
                                        class="dropdown-menu dropdown-menu-end shadow rounded-4 py-2"
                                    >
                                        <li>
                                            <a
                                                class="dropdown-item py-2"
                                                href="javascript:void(0)"
                                                @click="onDownload('pdf')"
                                            >
                                                Download as PDF
                                            </a>
                                        </li>
                                        <li>
                                            <a
                                                class="dropdown-item py-2"
                                                href="javascript:void(0)"
                                                @click="onDownload('excel')"
                                            >
                                                Download as Excel
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table table-hover align-middle" style="min-height: 320px;">
                                <thead class="border-top small text-muted">
                                    <tr>
                                        <th>S.#</th>
                                        <th>Items</th>
                                        <th>Image</th>
                                        <th>Unit Price</th>
                                        <th>Category</th>
                                        <th>Unit</th>
                                        <th>Available Stock</th>
                                        <th>Stock Value</th>
                                        <th>Availability</th>
                                        <th>Entered By</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(item, idx) in filteredItems"
                                        :key="item.id"
                                    >
                                        <td>{{ idx + 1 }}</td>
                                        <td class="fw-semibold">
                                            {{ item.name }}
                                        </td>
                                        <td>
                                            <img
                                                :src="item.image"
                                                class="rounded"
                                                style="
                                                    width: 40px;
                                                    height: 40px;
                                                    object-fit: cover;
                                                "
                                            />
                                        </td>
                                        <td>
                                            {{ money(item.unitPrice, "GBP") }}
                                        </td>
                                        <td
                                            class="text-truncate"
                                            style="max-width: 260px"
                                        >
                                            {{ item.category }}
                                        </td>
                                        <td>{{ item.unit }}</td>
                                        <td>
                                            {{ item.availableStock.toFixed(1) }}
                                        </td>
                                        <td>
                                            {{ money(item.stockValue, "GBP") }}
                                        </td>
                                        <td>
                                            <span
                                                v-if="item.availableStock > 0"
                                                class="badge rounded-pill bg-success-subtle text-success fw-semibold px-3 py-2"
                                                >In-stock</span
                                            >
                                            <span
                                                v-else
                                                class="badge rounded-pill bg-secondary-subtle text-secondary fw-semibold px-3 py-2"
                                                >Out of stock</span
                                            >
                                        </td>
                                        <td>{{ item.enteredBy }}</td>

                                        <!-- 3-dots menu -->
                                        <td class="text-end">
                                            <div class="dropdown">
                                                <button
                                                    class="btn btn-link text-secondary p-0 fs-5"
                                                    data-bs-toggle="dropdown"
                                                    aria-expanded="false"
                                                    title="Actions"
                                                >
                                                    ⋮
                                                </button>
                                                <ul
                                                    class="dropdown-menu dropdown-menu-end shadow rounded-4 overflow-hidden action-menu"
                                                >
                                                    <li>
                                                        <a
                                                            class="dropdown-item py-2"
                                                            href="javascript:void(0)"
                                                            @click="
                                                                onStockIn(item)
                                                            "
                                                        >
                                                            <i
                                                                class="bi bi-box-arrow-in-down-right me-2"
                                                            ></i>
                                                            Stock In
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a
                                                            class="dropdown-item py-2"
                                                            href="javascript:void(0)"
                                                            @click="
                                                                onStockOut(item)
                                                            "
                                                        >
                                                            <i
                                                                class="bi bi-box-arrow-up-right me-2"
                                                            ></i>
                                                            Stock Out
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a
                                                            class="dropdown-item py-2"
                                                            href="javascript:void(0)"
                                                            @click="
                                                                onViewItem(item)
                                                            "
                                                        >
                                                            <i
                                                                class="bi bi-eye me-2"
                                                            ></i>
                                                            View Item
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a
                                                            class="dropdown-item py-2"
                                                            href="javascript:void(0)"
                                                            @click="
                                                                onEditItem(item)
                                                            "
                                                        >
                                                            <i
                                                                class="bi bi-pencil-square me-2"
                                                            ></i>
                                                            Edit
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr v-if="filteredItems.length === 0">
                                        <td
                                            colspan="11"
                                            class="text-center text-muted py-4"
                                        >
                                            No items found.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== Add Item Modal (your provided form, wrapped in Bootstrap modal) ===== -->
        <div
            class="modal fade"
            id="createItemModal"
            tabindex="-1"
            aria-labelledby="createItemModal"
            aria-hidden="true"
        >
            <div
                class="modal-dialog modal-lg modal-dialog-centered"
                role="document"
            >
                <div class="modal-content rounded-4">
                    <div class="modal-header">
                        <h5 class="modal-title fw-semibold">Create</h5>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                        ></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-lg-6 col-sm-12">
                                <div class="form-group">
                                    <label class="form-label"
                                        >Customer Name</label
                                    >
                                    <input type="text" class="form-control" />
                                </div>
                            </div>
                            <div class="col-lg-6 col-sm-12">
                                <div class="form-group">
                                    <label class="form-label">Email</label>
                                    <input type="text" class="form-control" />
                                </div>
                            </div>
                            <div class="col-lg-6 col-sm-12">
                                <div class="form-group">
                                    <label class="form-label">Phone</label>
                                    <input type="text" class="form-control" />
                                </div>
                            </div>
                            <div class="col-lg-6 col-sm-12">
                                <div class="form-group">
                                    <label class="form-label">Country</label>
                                    <input type="text" class="form-control" />
                                </div>
                            </div>
                            <div class="col-lg-6 col-sm-12">
                                <div class="form-group">
                                    <label class="form-label">City</label>
                                    <input type="text" class="form-control" />
                                </div>
                            </div>
                            <div class="col-lg-6 col-sm-12">
                                <div class="form-group">
                                    <label class="form-label">Address</label>
                                    <input type="text" class="form-control" />
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <a class="btn btn-primary rounded-pill px-4"
                                >Submit</a
                            >
                            <a
                                class="btn btn-secondary rounded-pill px-4"
                                data-bs-dismiss="modal"
                                >Cancel</a
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Master>
</template>

<style scoped>
:root {
    --brand: #1c0d82;
}

/* KPI cards */
.kpi-card {
    background: #fff;
}
.icon-wrap {
    font-size: 2rem;
    color: var(--brand);
}
.kpi-label {
    font-size: 0.95rem;
}
.kpi-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: #111827;
}

/* Search pill */
.search-wrap {
    position: relative;
    width: clamp(220px, 28vw, 360px);
}
.search-wrap .bi-search {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
    font-size: 1rem;
}
.search-input {
    padding-left: 38px;
    border-radius: 9999px;
    background: #fff;
}

/* Buttons theme */
.btn-primary {
    background-color: var(--brand);
    border-color: var(--brand);
}
.btn-primary:hover {
    filter: brightness(1.05);
}

/* Action menu look like screenshot */
.action-menu {
    min-width: 220px;
}
.action-menu .dropdown-item {
    font-size: 1rem;
}

/* Table polish */
.table thead th {
    font-weight: 600;
}
.table tbody td {
    vertical-align: middle;
}

/* Mobile tweaks */
@media (max-width: 575.98px) {
    .kpi-value {
        font-size: 1.45rem;
    }
    .search-wrap {
        width: 100%;
    }
}
</style>
