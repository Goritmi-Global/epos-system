<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed } from "vue";

/* Tabs (Inventory active like screenshot) */
const tabs = [
    { key: "inventory", label: "Inventory" },
    { key: "categories", label: "Inventory Categories" },
    { key: "logs", label: "Stock Logs Moment" },
    { key: "po", label: "Purchase Order" },
    { key: "refs", label: "Reference Management" },
];
const activeTab = ref("inventory");

/* Demo data (replace with API data) */
const items = ref([
    {
        id: 1,
        name: "Tomato",
        image: "https://picsum.photos/seed/tomato/64",
        unitPrice: 1000,
        category: "Vegetable",
        unit: "kilogram (kg)",
        availableStock: 95.0,
        stockValue: 14000,
    },
    {
        id: 2,
        name: "Patato",
        image: "https://picsum.photos/seed/potato/64",
        unitPrice: 200,
        category: "Vegetable",
        unit: "kilogram (kg)",
        availableStock: 94.0,
        stockValue: 17600,
    },
    {
        id: 3,
        name: "Chicken",
        image: "https://picsum.photos/seed/chicken/64",
        unitPrice: 100,
        category: "Chicken",
        unit: "kilogram (kg)",
        availableStock: 49.0,
        stockValue: 18400,
    },
]);

/* Search */
const q = ref("");

const filteredItems = computed(() => {
    const term = q.value.trim().toLowerCase();
    if (!term) return items.value;
    return items.value.filter(
        (i) =>
            i.name.toLowerCase().includes(term) ||
            i.category.toLowerCase().includes(term) ||
            i.unit.toLowerCase().includes(term)
    );
});

/* KPIs */
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

const kpiCards = computed(() => [
    { label: "Categories", value: categoriesCount.value },
    { label: "Total Items", value: totalItems.value },
    { label: "Low Stock", value: lowStockCount.value },
    { label: "Out of Stock", value: outOfStockCount.value },
]);

/* Helpers */
const money = (amount, currency = "GBP") =>
    new Intl.NumberFormat("en-GB", { style: "currency", currency }).format(
        amount
    );

/* Buttons (wire these later) */
const onAddItem = () => {};
const onDownloadAll = () => {};
</script>
<template>
    <Head title="Dashboard" />

    <Master>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Inventory Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="page-wrapper">
                <div class="content">
                    <div class="container-fluid py-3">
                        <!-- Tabs -->
                        <ul class="nav nav-tabs border-0">
                            <li class="nav-item" v-for="t in tabs" :key="t.key">
                                <button
                                    class="nav-link px-3"
                                    :class="{ active: activeTab === t.key }"
                                    @click="activeTab = t.key"
                                >
                                    {{ t.label }}
                                </button>
                            </li>
                        </ul>

                        <div class="mt-3">
                            <h4 class="mb-3">Overall Inventory</h4>

                            <!-- KPI cards -->
                            <div class="row g-3">
                                <div
                                    class="col-sm-6 col-md-3"
                                    v-for="card in kpiCards"
                                    :key="card.label"
                                >
                                    <div
                                        class="card border-0 shadow-sm rounded-4"
                                    >
                                        <div class="card-body text-center">
                                            <div class="text-muted small mb-1">
                                                {{ card.label }}
                                            </div>
                                            <div class="h4 mb-0">
                                                {{ card.value }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Stock table -->
                            <div class="card border-0 shadow-lg rounded-4 mt-4">
                                <div class="card-body">
                                    <div
                                        class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3"
                                    >
                                        <h5 class="mb-0">Stock</h5>

                                        <div class="d-flex flex-wrap gap-2">
                                            <div class="input-group">
                                                <span
                                                    class="input-group-text bg-white"
                                                >
                                                    <i class="bi bi-search"></i>
                                                </span>
                                                <input
                                                    v-model="q"
                                                    type="text"
                                                    class="form-control"
                                                    placeholder="Search"
                                                />
                                            </div>
                                            <button
                                                class="btn btn-primary rounded-pill"
                                                @click="onAddItem"
                                            >
                                                Add Item
                                            </button>
                                            <button
                                                class="btn btn-outline-secondary rounded-pill"
                                                @click="onDownloadAll"
                                            >
                                                Download all
                                            </button>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table
                                            class="table table-hover align-middle"
                                        >
                                            <thead class="border-top">
                                                <tr>
                                                    <th class="text-muted">
                                                        S.#
                                                    </th>
                                                    <th class="text-muted">
                                                        Items
                                                    </th>
                                                    <th class="text-muted">
                                                        Image
                                                    </th>
                                                    <th class="text-muted">
                                                        Unit Price
                                                    </th>
                                                    <th class="text-muted">
                                                        Category
                                                    </th>
                                                    <th class="text-muted">
                                                        Unit
                                                    </th>
                                                    <th class="text-muted">
                                                        Available Stock
                                                    </th>
                                                    <th class="text-muted">
                                                        Stock Value
                                                    </th>
                                                    <th class="text-muted">
                                                        Availability
                                                    </th>
                                                    <th class="text-muted">
                                                        Entered By
                                                    </th>
                                                    <th class="text-muted">
                                                        Action
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr
                                                    v-for="(
                                                        item, idx
                                                    ) in filteredItems"
                                                    :key="item.id"
                                                >
                                                    <td>{{ idx + 1 }}</td>
                                                    <td class="fw-semibold">
                                                        {{ item.name }}
                                                    </td>
                                                    <td>
                                                        <img
                                                            :src="item.image"
                                                            alt=""
                                                            class="rounded"
                                                            style="
                                                                width: 40px;
                                                                height: 40px;
                                                                object-fit: cover;
                                                            "
                                                        />
                                                    </td>
                                                    <td>
                                                        {{
                                                            money(
                                                                item.unitPrice,
                                                                "GBP"
                                                            )
                                                        }}
                                                    </td>
                                                    <td>{{ item.category }}</td>
                                                    <td>{{ item.unit }}</td>
                                                    <td>
                                                        {{
                                                            item.availableStock.toFixed(
                                                                1
                                                            )
                                                        }}
                                                    </td>
                                                    <td>
                                                        {{
                                                            money(
                                                                item.stockValue,
                                                                "EUR"
                                                            )
                                                        }}
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="badge rounded-pill text-bg-success"
                                                            v-if="
                                                                item.availableStock >
                                                                0
                                                            "
                                                            >In-stock</span
                                                        >
                                                        <span
                                                            class="badge rounded-pill text-bg-secondary"
                                                            v-else
                                                            >Out of stock</span
                                                        >
                                                    </td>
                                                    <td>
                                                        <div>Super</div>
                                                        <div>Admin</div>
                                                    </td>
                                                    <td>
                                                        <button
                                                            class="btn btn-link text-secondary p-0"
                                                            title="Actions"
                                                        >
                                                            ⋮
                                                        </button>
                                                    </td>
                                                </tr>

                                                <tr
                                                    v-if="
                                                        filteredItems.length ===
                                                        0
                                                    "
                                                >
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
                </div>
            </div>
        </div>
    </Master>
</template>

<style scoped>
/* Tabs like the screenshot: minimal, underline on active */
.nav-tabs .nav-link {
    border: 0;
    color: #6c757d;
}
.nav-tabs .nav-link.active {
    color: #000;
    border-bottom: 2px solid var(--bs-primary);
    background-color: transparent;
}
</style>
