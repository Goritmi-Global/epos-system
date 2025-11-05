<script setup>
import Master from "@/Layouts/Master.vue";
import { Head } from "@inertiajs/vue3";
import { ref, computed, watch, onMounted, onUpdated } from "vue";
import Select from "primevue/select";
import axios from "axios";
import { useFormatters } from '@/composables/useFormatters'
import FilterModal from "@/Components/FilterModal.vue";
import { nextTick } from "vue";


const { formatMoney, formatNumber, formatCurrencySymbol, dateFmt } = useFormatters()

/* ---------------- Filters (simple values to match your screenshot) ---------------- */
const range = ref("last30"); // today | last7 | last30 | thisMonth | all
const orderType = ref("All"); // All | dine | delivery
const payType = ref("All"); // All | cash | card | qr | bank

const filters = ref({
    sortBy: "",
    range: "last30",
    orderType: "",
    paymentType: "",
    dateFrom: "",
    dateTo: "",
});

const searchKey = ref(Date.now());
const inputId = `search-${Math.random().toString(36).substr(2, 9)}`;
const isReady = ref(false);


const rangeOptions = ["today", "last7", "last30", "thisMonth", "all"];
const orderTypeOptions = ["All", "dine", "delivery"];
const payTypeOptions = ["All", "cash", "card", "qr", "bank"];

/* ---------------- API state ---------------- */
const loading = ref(false);
const errorMsg = ref("");

const revenue = ref(0);
const ordersCount = ref(0);
const aov = ref(0);
const itemsSold = ref(0);
const salesSeries = ref([]); // [{day:'YYYY-MM-DD', total:number}]
const ordersByType = ref({ dine: 0, delivery: 0, dinePct: 0, deliveryPct: 0 });
const paymentsMix = ref([]); // [{method,count,pct}]
const topItems = ref([]); // [{name,qty,revenue}]
const qItems = ref("");


let debounceId;
const fetchAnalytics = () => {
    clearTimeout(debounceId);
    debounceId = setTimeout(async () => {
        loading.value = true;
        errorMsg.value = "";
        try {
            const { data } = await axios.get("/api/analytics", {
                params: {
                    range: filters.value.range,
                    orderType: filters.value.orderType,
                    payType: filters.value.paymentType,
                    dateFrom: filters.value.dateFrom,
                    dateTo: filters.value.dateTo,
                },
            });
            console.log("analytics data", data);
            revenue.value = data.revenue ?? 0;
            ordersCount.value = data.ordersCount ?? 0;
            aov.value = data.aov ?? 0;
            itemsSold.value = data.itemsSold ?? 0;
            salesSeries.value = data.salesSeries ?? [];
            ordersByType.value = data.ordersByType ?? {
                dine: 0,
                delivery: 0,
                dinePct: 0,
                deliveryPct: 0,
            };
            paymentsMix.value = data.paymentsMix ?? [];
            topItems.value = data.topItems ?? [];
        } catch (e) {
            console.error(e);
            errorMsg.value = "Failed to load analytics.";
        } finally {
            loading.value = false;
        }
    }, 180);
};
// watch([range, orderType, payType], fetchAnalytics, { immediate: true });

watch(
    () => [filters.value.range, filters.value.orderType, filters.value.paymentType, filters.value.dateFrom, filters.value.dateTo],
    fetchAnalytics,
    { immediate: true }
);

const filterOptions = computed(() => ({
    sortOptions: [
        { value: "revenue_desc", label: "Revenue: High to Low" },
        { value: "revenue_asc", label: "Revenue: Low to High" },
        { value: "qty_desc", label: "Quantity: High to Low" },
        { value: "qty_asc", label: "Quantity: Low to High" },
        { value: "name_asc", label: "Item Name: A to Z" },
        { value: "name_desc", label: "Item Name: Z to A" },
    ],
    rangeOptions: [
        { value: "today", label: "Today" },
        { value: "last7", label: "Last 7 Days" },
        { value: "last30", label: "Last 30 Days" },
        { value: "thisMonth", label: "This Month" },
        { value: "all", label: "All Time" },
    ],
    orderTypeOptions: [
        { value: "dine", label: "Dine In" },
        { value: "delivery", label: "Delivery" },
    ],
    paymentTypeOptions: [
        { value: "cash", label: "Cash" },
        { value: "card", label: "Card" },
        { value: "qr", label: "QR" },
        { value: "bank", label: "Bank" },
    ],
}));
// ===============================================

// ========== ADD FILTER HANDLERS ==========
const handleFilterApply = (appliedFilters) => {
    console.log("Filters applied:", appliedFilters);
};

const handleFilterClear = () => {
    console.log("Filters cleared");
};




/* ---------------- Helpers ---------------- */
const money = (n, c = "GBP") =>
    new Intl.NumberFormat("en-GB", { style: "currency", currency: c }).format(
        n
    );

// Build pretty line path (with margins) from salesSeries
// Build pretty line with margins. Draw a teeny segment if only 1 point.
function buildLine(series, W, H, m = { l: 40, r: 10, t: 16, b: 28 }) {
    if (!series?.length) return "";
    const xs = series.map((_, i) => i);
    const ys = series.map((d) => +d.total || 0);

    const minX = 0,
        maxX = Math.max(1, xs.length - 1);
    const minY = 0,
        maxY = Math.max(1, ...ys);

    const iw = Math.max(1, W - m.l - m.r);
    const ih = Math.max(1, H - m.t - m.b);

    const sx = (x) => m.l + ((x - minX) / (maxX - minX)) * iw;
    const sy = (y) => m.t + ih - ((y - minY) / (maxY - minY)) * ih;

    if (xs.length === 1) {
        const x = sx(0),
            y = sy(ys[0]);
        // tiny visible segment
        return `M${x},${y} L${x + 0.01},${y}`;
    }
    return xs.map((x, i) => `${i ? "L" : "M"}${sx(x)},${sy(ys[i])}`).join(" ");
}

// For visible dots on the line (works for any number of points)
function buildMarkers(series, W, H, m = { l: 40, r: 10, t: 16, b: 28 }) {
    if (!series?.length) return [];
    const xs = series.map((_, i) => i);
    const ys = series.map((d) => +d.total || 0);

    const minX = 0,
        maxX = Math.max(1, xs.length - 1);
    const minY = 0,
        maxY = Math.max(1, ...ys);

    const iw = Math.max(1, W - m.l - m.r);
    const ih = Math.max(1, H - m.t - m.b);

    const sx = (x) => m.l + ((x - minX) / (maxX - minX)) * iw;
    const sy = (y) => m.t + ih - ((y - minY) / (maxY - minY)) * ih;

    return xs.map((x, i) => ({ x: sx(x), y: sy(ys[i]) }));
}

// use them
const bigLinePath = computed(() => buildLine(salesSeries.value, 840, 280));
const markerPoints = computed(() => buildMarkers(salesSeries.value, 840, 280));

// const topItemsFiltered = computed(() => {
//     const t = qItems.value.trim().toLowerCase();
//     return t
//         ? (topItems.value || []).filter((i) =>
//             (i.name || "").toLowerCase().includes(t)
//         )
//         : topItems.value || [];
// });

const topItemsFiltered = computed(() => {
    const t = qItems.value.trim().toLowerCase();
    let result = t
        ? (topItems.value || []).filter((i) =>
            (i.name || "").toLowerCase().includes(t)
        )
        : topItems.value || [];

    // Apply sorting
    const sortBy = filters.value.sortBy;
    switch (sortBy) {
        case "revenue_desc":
            return result.sort((a, b) => (Number(b.revenue) || 0) - (Number(a.revenue) || 0));
        case "revenue_asc":
            return result.sort((a, b) => (Number(a.revenue) || 0) - (Number(b.revenue) || 0));
        case "qty_desc":
            return result.sort((a, b) => (Number(b.qty) || 0) - (Number(a.qty) || 0));
        case "qty_asc":
            return result.sort((a, b) => (Number(a.qty) || 0) - (Number(b.qty) || 0));
        case "name_asc":
            return result.sort((a, b) => (a.name || "").localeCompare(b.name || ""));
        case "name_desc":
            return result.sort((a, b) => (b.name || "").localeCompare(a.name || ""));
        default:
            return result;
    }
});
const q = ref('');
onMounted(() => window.feather?.replace());
onUpdated(() => window.feather?.replace());
onMounted(async () => {
    q.value = "";
    searchKey.value = Date.now();
    await nextTick();

    // Delay to prevent autofill
    setTimeout(() => {
        isReady.value = true;

        // Force clear any autofill that happened
        const input = document.getElementById(inputId);
        if (input) {
            input.value = '';
            q.value = '';
        }
    }, 100);
});

</script>

<template>

    <Head title="Analytics" />
    <Master>
        <div class="page-wrapper">
            <!-- Filters row -->
            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
                <div class="d-flex flex-wrap gap-2">
                    <FilterModal v-model="filters" title="Analytics" modal-id="analyticsFilterModal"
                        modal-size="modal-lg" :sort-options="filterOptions.sortOptions" :show-date-range="true"
                        @apply="handleFilterApply" @clear="handleFilterClear">
                        <!-- Custom filters slot for Range, Order Type, and Payment Type -->
                        <template #customFilters="{ filters }">
                            <!-- Date Range Selection -->
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold text-dark">
                                    <i class="fas fa-calendar-alt me-2 text-muted"></i>Date Range
                                </label>
                                <select v-model="filters.range" class="form-select">
                                    <option v-for="opt in filterOptions.rangeOptions" :key="opt.value"
                                        :value="opt.value">
                                        {{ opt.label }}
                                    </option>
                                </select>
                            </div>

                            <!-- Order Type -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-dark">
                                    <i class="fas fa-concierge-bell me-2 text-muted"></i>Order Type
                                </label>
                                <select v-model="filters.orderType" class="form-select">
                                    <option value="">All</option>
                                    <option v-for="opt in filterOptions.orderTypeOptions" :key="opt.value"
                                        :value="opt.value">
                                        {{ opt.label }}
                                    </option>
                                </select>
                            </div>

                            <!-- Payment Type -->
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-dark">
                                    <i class="fas fa-credit-card me-2 text-muted"></i>Payment Type
                                </label>
                                <select v-model="filters.paymentType" class="form-select">
                                    <option value="">All</option>
                                    <option v-for="opt in filterOptions.paymentTypeOptions" :key="opt.value"
                                        :value="opt.value">
                                        {{ opt.label }}
                                    </option>
                                </select>
                            </div>
                        </template>
                    </FilterModal>
                </div>

                <div class="dropdown">
                    <button class="btn btn-outline-secondary rounded-pill px-4 dropdown-toggle"
                        data-bs-toggle="dropdown">
                        Download
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow rounded-4 py-2">
                        <li>
                            <a class="dropdown-item py-2" href="javascript:void(0)">Download as PDF</a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="javascript:void(0)">Download as Excel</a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Error -->
            <div v-if="errorMsg" class="alert alert-warning border-0 rounded-4 shadow-sm">
                {{ errorMsg }}
            </div>

            <!-- KPIs -->
            <div class="row g-3">
                <!-- Revenue -->
                <div class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 ">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h4 class="mb-0 fw-bold">{{ formatCurrencySymbol(revenue) }}</h4>
                                <p class="text-muted mb-0 small">Revenue</p>
                            </div>
                            <div class="rounded-circle p-3 bg-success-subtle text-success d-flex align-items-center justify-content-center"
                                style="width: 56px; height: 56px">
                                <i class="bi bi-currency-pound fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Orders -->
                <div class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 ">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h4 class="mb-0 fw-bold">{{ formatCurrencySymbol(ordersCount) }}</h4>
                                <p class="text-muted mb-0 small">Orders</p>
                            </div>
                            <div class="rounded-circle p-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center"
                                style="width: 56px; height: 56px">
                                <i class="bi bi-receipt fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Average Order Value -->
                <div class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 ">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h4 class="mb-0 fw-bold">{{ formatCurrencySymbol(aov) }}</h4>
                                <p class="text-muted mb-0 small">Avg. Order Value</p>
                            </div>
                            <div class="rounded-circle p-3 bg-warning-subtle text-warning d-flex align-items-center justify-content-center"
                                style="width: 56px; height: 56px">
                                <i class="bi bi-graph-up fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items Sold -->
                <div class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 ">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h4 class="mb-0 fw-bold">{{ formatCurrencySymbol(itemsSold) }}</h4>
                                <p class="text-muted mb-0 small">Items Sold</p>
                            </div>
                            <div class="rounded-circle p-3 bg-danger-subtle text-danger d-flex align-items-center justify-content-center"
                                style="width: 56px; height: 56px">
                                <i class="bi bi-basket fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Charts row -->
            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body">
                            <h6 class="fw-semibold mb-2">Sales Over Time</h6>
                            <div class="chart">
                                <svg viewBox="0 0 840 280">
                                    <!-- Axes -->
                                    <line x1="40" y1="16" x2="40" y2="252" class="axis" />
                                    <line x1="40" y1="252" x2="830" y2="252" class="axis" />
                                    <!-- Grid -->
                                    <g v-for="i in 6" :key="'g' + i">
                                        <line :x1="40" :x2="830" :y1="252 - i * 36" :y2="252 - i * 36" class="grid" />
                                    </g>
                                    <!-- Series -->
                                    <path :d="bigLinePath" class="line" />
                                    <text v-if="!salesSeries.length" x="430" y="140" text-anchor="middle" class="muted">
                                        No data
                                    </text>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 mb-3">
                        <div class="card-body">
                            <h6 class="fw-semibold mb-3">Orders by Type</h6>

                            <div class="stack">
                                <div class="stack-row">
                                    <span>Dine In</span>
                                    <span class="stack-val">{{ ordersByType.dine }} ({{
                                        ordersByType.dinePct
                                        }}%)</span>
                                </div>
                                <div class="progress thin">
                                    <div class="progress-bar bg-success" :style="{
                                        width: ordersByType.dinePct + '%',
                                    }"></div>
                                </div>
                            </div>

                            <div class="stack mt-2">
                                <div class="stack-row">
                                    <span>Delivery</span>
                                    <span class="stack-val">{{ ordersByType.delivery }} ({{
                                        ordersByType.deliveryPct
                                        }}%)</span>
                                </div>
                                <div class="progress thin">
                                    <div class="progress-bar bg-primary" :style="{
                                        width:
                                            ordersByType.deliveryPct + '%',
                                    }"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body">
                            <h6 class="fw-semibold mb-3">Payments Mix</h6>
                            <div v-for="p in paymentsMix" :key="p.method" class="mb-2">
                                <div class="stack-row">
                                    <span class="text-capitalize">{{
                                        p.method
                                    }}</span>
                                    <span class="stack-val">{{ p.count }} ({{ p.pct }}%)</span>
                                </div>
                                <div class="progress thin">
                                    <div class="progress-bar" :class="{
                                        'bg-secondary': p.method === 'cash',
                                        'bg-info': p.method === 'card',
                                        'bg-warning': p.method === 'qr',
                                        'bg-dark': p.method === 'bank',
                                    }" :style="{ width: p.pct + '%' }" />
                                </div>
                            </div>
                            <div v-if="!paymentsMix.length" class="small text-muted">
                                No payment data.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Items -->
            <div class="card border-0 shadow-sm rounded-4 mt-3">
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                        <h6 class="fw-semibold mb-0">Top Items</h6>
                        <div class="search-wrap">
                            <i class="bi bi-search"></i>
                            <input type="email" name="email" autocomplete="email"
                                style="position: absolute; left: -9999px; width: 1px; height: 1px;" tabindex="-1"
                                aria-hidden="true" />

                            <input v-if="isReady" :id="inputId" v-model="q" :key="searchKey"
                                class="form-control search-input" placeholder="Search" type="search"
                                autocomplete="new-password" :name="inputId" role="presentation" @focus="handleFocus" />
                            <input v-else class="form-control search-input" placeholder="Search" disabled type="text" />
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="small text-muted">
                                <tr>
                                    <th style="width: 60px">S.#</th>
                                    <th>Item</th>
                                    <th style="width: 140px">Qty Sold</th>
                                    <th style="width: 160px">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(r, i) in topItemsFiltered" :key="r.name">
                                    <td>{{ i + 1 }}</td>
                                    <td class="fw-semibold">
                                        {{ r.name }}
                                    </td>
                                    <td>{{ r.qty }}</td>
                                    <td>{{ formatCurrencySymbol(r.revenue) }}</td>
                                </tr>
                                <tr v-if="
                                    !loading &&
                                    topItemsFiltered.length === 0
                                ">
                                    <td colspan="4" class="text-center text-muted py-4">
                                        No items in this range.
                                    </td>
                                </tr>
                                <tr v-if="loading">
                                    <td colspan="4" class="text-center text-muted py-4">
                                        Loading…
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </Master>
</template>

<style scoped>
.dark h4 {
    color: white;
}

.dark .card {
    background-color: #181818 !important;
    /* gray-800 */
    color: #ffffff !important;
    /* gray-50 */
}

.dark .table {
    background-color: #181818 !important;
    /* gray-900 */
    color: #f9fafb !important;
}

.dark .table thead {
    background-color: #181818 !important;
    color: #ffffff;
}

.dark .table thead th {
    background-color: #181818 !important;
    color: #ffffff;
}

:root {
    --brand: #1c0d82;
}

/* Filters: small width like screenshot */
.filter {
    min-width: 140px;
}

/* KPI */
.kpi .card-body {
    padding: 1rem 1.25rem;
}

.kpi-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.kpi-icon {
    font-size: 1.8rem;
    color: var(--brand);
}

.kpi-label {
    font-size: 0.9rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.kpi-value {
    font-size: 1.6rem;
    font-weight: 700;
    color: #181818;
}

/* Spark */
.spark {
    width: 160px;
    height: 44px;
}

.spark-line {
    fill: none;
    stroke: var(--brand);
    stroke-width: 2;
    opacity: 0.9;
}

/* Chart */
.chart {
    width: 100%;
}

.axis {
    stroke: #0f172a;
    stroke-width: 1.5;
}

.grid {
    stroke: #e5e7eb;
    stroke-width: 1;
}

.line {
    fill: none;
    stroke: #1c0d82;
    stroke-width: 2.5;
}

.muted {
    fill: #6b7280;
    font-size: 12px;
}

.dark .form-label {
    color: #fff !important;
}

.dark .form-select {
    background-color: #212121 !important;
    color: #fff !important;
}

/* Progress blocks */
.progress.thin {
    height: 8px;
    background: #eef2ff;
}

.stack-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.stack-val {
    color: #6b7280;
}

/* Search pill */
.search-wrap {
    position: relative;
    width: clamp(220px, 30vw, 360px);
}

.search-wrap .bi-search {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
}

.search-input {
    padding-left: 36px;
    border-radius: 9999px;
}

/* Table */
.table thead th {
    font-weight: 600;
}

/* keep PrimeVue overlays above Bootstrap modal/backdrop */
:deep(.p-multiselect-panel),
:deep(.p-select-panel),
:deep(.p-dropdown-panel) {
    z-index: 2000 !important;
}

/* ========================  MultiSelect Styling   ============================= */
:deep(.p-multiselect-header) {
    background-color: white !important;
    color: black !important;
}

:deep(.p-multiselect-label) {
    color: #000 !important;
}

:deep(.p-select .p-component .p-inputwrapper) {
    background: #fff !important;
    color: #000 !important;
    border-bottom: 1px solid #ddd;
}

/* Options list container */
:deep(.p-multiselect-list) {
    background: #fff !important;
}

/* Each option */
:deep(.p-multiselect-option) {
    background: #fff !important;
    color: #000 !important;
}

/* Hover/selected option */
:deep(.p-multiselect-option.p-highlight) {
    background: #f0f0f0 !important;
    color: #000 !important;
}

:deep(.p-multiselect),
:deep(.p-multiselect-panel),
:deep(.p-multiselect-token) {
    background: #fff !important;
    color: #000 !important;
    border-color: #a4a7aa;
}

/* Checkbox box in dropdown */
:deep(.p-multiselect-overlay .p-checkbox-box) {
    background: #fff !important;
    border: 1px solid #ccc !important;
    color: #fff !important;
}

/* .dark svg{
    color: #fff !important;
} */


/* Search filter input */
:deep(.p-multiselect-filter) {
    background: #fff !important;
    color: #000 !important;
    border: 1px solid #ccc !important;
}

/* Optional: adjust filter container */
:deep(.p-multiselect-filter-container) {
    background: #fff !important;
}

/* Selected chip inside the multiselect */
:deep(.p-multiselect-chip) {
    background: #e9ecef !important;
    color: #000 !important;
    border-radius: 12px !important;
    border: 1px solid #ccc !important;
    padding: 0.25rem 0.5rem !important;
}

/* Chip remove (x) icon */
:deep(.p-multiselect-chip .p-chip-remove-icon) {
    color: #555 !important;
}

:deep(.p-multiselect-chip .p-chip-remove-icon:hover) {
    color: #dc3545 !important;
    /* red on hover */
}

/* keep PrimeVue overlays above Bootstrap modal/backdrop */
:deep(.p-multiselect-panel),
:deep(.p-select-panel),
:deep(.p-dropdown-panel) {
    z-index: 2000 !important;
}

/* ====================================================== */

/* ====================Select Styling===================== */
/* Entire select container */
:deep(.p-select) {
    background-color: white !important;
    color: black !important;
    border-color: #9b9c9c;
}

/* Options container */
:deep(.p-select-list-container) {
    background-color: white !important;
    color: black !important;
}

/* Each option */
:deep(.p-select-option) {
    background-color: transparent !important;
    /* instead of 'none' */
    color: black !important;
}

/* Hovered option */
:deep(.p-select-option:hover) {
    background-color: #f0f0f0 !important;
    color: black !important;
}

/* Focused option (when using arrow keys) */
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

/* Options list container */
:global(.dark .p-multiselect-list) {
    background: #181818 !important;
}

/* Each option */
:global(.dark .p-multiselect-option) {
    background: #181818 !important;
    color: #fff !important;
}

/* Hover/selected option */
:global(.dark .p-multiselect-option.p-highlight),
:global(.dark .p-multiselect-option:hover) {
    background: #181818 !important;
    color: #fff !important;
}

:global(.dark .p-multiselect),
:global(.dark .p-multiselect-panel),
:global(.dark .p-multiselect-token) {
    background: #181818 !important;
    color: #fff !important;
    border-color: #555 !important;
}

/* Checkbox box in dropdown */
:global(.dark .p-multiselect-overlay .p-checkbox-box) {
    background: #181818 !important;
    border: 1px solid #555 !important;
}

/* Search filter input */
:global(.dark .p-multiselect-filter) {
    background: #181818 !important;
    color: #fff !important;
    border: 1px solid #555 !important;
}

/* Optional: adjust filter container */
:global(.dark .p-multiselect-filter-container) {
    background: #181818 !important;
}

/* Selected chip inside the multiselect */
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

/* Chip remove (x) icon */
:global(.dark .p-multiselect-chip .p-chip-remove-icon) {
    color: #fff !important;
}

:global(.dark .p-multiselect-chip .p-chip-remove-icon:hover) {
    color: #f87171 !important;
    /* lighter red */
}

/* ==================== Dark Mode Select Styling ====================== */
:global(.dark .p-select) {
    background-color: #000 !important;
    color: #fff !important;
    border-color: #555 !important;
}

/* Options container */
:global(.dark .p-select-list-container) {
    background-color: #000 !important;
    color: #fff !important;
}

/* Each option */
:global(.dark .p-select-option) {
    background-color: transparent !important;
    color: #fff !important;
}

/* Hovered option */
:global(.dark .p-select-option:hover),
:global(.dark .p-select-option.p-focus) {
    background-color: #222 !important;
    color: #fff !important;
}

:global(.dark .p-select-label) {
    color: #fff !important;
}

:global(.dark .p-placeholder) {
    color: #aaa !important;
}

:global(.dark .p-select-list-container) {
    background-color: #181818 !important;
    color: #fff !important;
}



/* Responsive polish */
@media (max-width: 576px) {
    .kpi-value {
        font-size: 1.3rem;
    }

    .filter {
        min-width: 46%;
    }

    .search-wrap {
        width: 100%;
    }
}
</style>
