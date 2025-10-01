<script setup>
import Master from "@/Layouts/Master.vue";
import { Head } from "@inertiajs/vue3";
import { ref, computed, watch, onMounted, onUpdated } from "vue";
import Select from "primevue/select";
import axios from "axios";

/* ---------------- Filters (simple values to match your screenshot) ---------------- */
const range = ref("last30"); // today | last7 | last30 | thisMonth | all
const orderType = ref("All"); // All | dine | delivery
const payType = ref("All"); // All | cash | card | qr | bank

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
                    range: range.value,
                    orderType: orderType.value,
                    payType: payType.value,
                },
            });
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
watch([range, orderType, payType], fetchAnalytics, { immediate: true });

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

const topItemsFiltered = computed(() => {
    const t = qItems.value.trim().toLowerCase();
    return t
        ? (topItems.value || []).filter((i) =>
              (i.name || "").toLowerCase().includes(t)
          )
        : topItems.value || [];
});

onMounted(() => window.feather?.replace());
onUpdated(() => window.feather?.replace());
</script>

<template>
    <Head title="Analytics" />
    <Master>
        <div class="page-wrapper">
            <!-- Filters row -->
            <div
                class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3"
            >
                <div class="d-flex flex-wrap gap-2">
                    <div class="filter">
                        <Select
                            v-model="range"
                            :options="rangeOptions"
                            class="w-100"
                            :appendTo="'body'"
                        >
                            <template #value="{ value, placeholder }"
                                ><span>{{
                                    value || placeholder
                                }}</span></template
                            >
                        </Select>
                    </div>
                    <div class="filter">
                        <Select
                            v-model="orderType"
                            :options="orderTypeOptions"
                            class="w-100"
                            :appendTo="'body'"
                        >
                            <template #value="{ value, placeholder }"
                                ><span>{{
                                    value || placeholder
                                }}</span></template
                            >
                        </Select>
                    </div>
                    <div class="filter">
                        <Select
                            v-model="payType"
                            :options="payTypeOptions"
                            class="w-100"
                            :appendTo="'body'"
                        >
                            <template #value="{ value, placeholder }"
                                ><span>{{
                                    value || placeholder
                                }}</span></template
                            >
                        </Select>
                    </div>
                </div>

                <div class="dropdown">
                    <button
                        class="btn btn-outline-secondary rounded-pill px-4 dropdown-toggle"
                        data-bs-toggle="dropdown"
                    >
                        Download reports
                    </button>
                    <ul
                        class="dropdown-menu dropdown-menu-end shadow rounded-4 py-2"
                    >
                        <li>
                            <a
                                class="dropdown-item py-2"
                                href="javascript:void(0)"
                                >Download as PDF</a
                            >
                        </li>
                        <li>
                            <a
                                class="dropdown-item py-2"
                                href="javascript:void(0)"
                                >Download as Excel</a
                            >
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Error -->
            <div
                v-if="errorMsg"
                class="alert alert-warning border-0 rounded-4 shadow-sm"
            >
                {{ errorMsg }}
            </div>

            <!-- KPIs -->
            <div class="row g-3">
                <div class="col-6 col-lg-3">
                    <div class="card kpi shadow-sm rounded-4 h-100">
                        <div class="card-body">
                            <div class="kpi-top">
                                <div class="kpi-icon">
                                    <i class="bi bi-currency-pound"></i>
                                </div>
                                <svg viewBox="0 0 160 44" class="spark">
                                    <path :d="sparkPath" class="spark-line" />
                                </svg>
                            </div>
                            <div class="kpi-label">Revenue</div>
                            <div class="kpi-value">
                                {{ money(revenue) }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="card kpi shadow-sm rounded-4 h-100">
                        <div class="card-body">
                            <div class="kpi-top">
                                <div class="kpi-icon">
                                    <i class="bi bi-receipt"></i>
                                </div>
                            </div>
                            <div class="kpi-label">Orders</div>
                            <div class="kpi-value">{{ ordersCount }}</div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="card kpi shadow-sm rounded-4 h-100">
                        <div class="card-body">
                            <div class="kpi-top">
                                <div class="kpi-icon">
                                    <i class="bi bi-graph-up"></i>
                                </div>
                            </div>
                            <div class="kpi-label">Avg. Order Value</div>
                            <div class="kpi-value">{{ money(aov) }}</div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="card kpi shadow-sm rounded-4 h-100">
                        <div class="card-body">
                            <div class="kpi-top">
                                <div class="kpi-icon">
                                    <i class="bi bi-basket"></i>
                                </div>
                            </div>
                            <div class="kpi-label">Items Sold</div>
                            <div class="kpi-value">{{ itemsSold }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts row -->
            <div class="row g-3 mt-1">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body">
                            <h6 class="fw-semibold mb-2">Sales Over Time</h6>
                            <div class="chart">
                                <svg viewBox="0 0 840 280">
                                    <!-- Axes -->
                                    <line
                                        x1="40"
                                        y1="16"
                                        x2="40"
                                        y2="252"
                                        class="axis"
                                    />
                                    <line
                                        x1="40"
                                        y1="252"
                                        x2="830"
                                        y2="252"
                                        class="axis"
                                    />
                                    <!-- Grid -->
                                    <g v-for="i in 6" :key="'g' + i">
                                        <line
                                            :x1="40"
                                            :x2="830"
                                            :y1="252 - i * 36"
                                            :y2="252 - i * 36"
                                            class="grid"
                                        />
                                    </g>
                                    <!-- Series -->
                                    <path :d="bigLinePath" class="line" />
                                    <text
                                        v-if="!salesSeries.length"
                                        x="430"
                                        y="140"
                                        text-anchor="middle"
                                        class="muted"
                                    >
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
                                    <span class="stack-val"
                                        >{{ ordersByType.dine }} ({{
                                            ordersByType.dinePct
                                        }}%)</span
                                    >
                                </div>
                                <div class="progress thin">
                                    <div
                                        class="progress-bar bg-success"
                                        :style="{
                                            width: ordersByType.dinePct + '%',
                                        }"
                                    ></div>
                                </div>
                            </div>

                            <div class="stack mt-2">
                                <div class="stack-row">
                                    <span>Delivery</span>
                                    <span class="stack-val"
                                        >{{ ordersByType.delivery }} ({{
                                            ordersByType.deliveryPct
                                        }}%)</span
                                    >
                                </div>
                                <div class="progress thin">
                                    <div
                                        class="progress-bar bg-primary"
                                        :style="{
                                            width:
                                                ordersByType.deliveryPct + '%',
                                        }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body">
                            <h6 class="fw-semibold mb-3">Payments Mix</h6>
                            <div
                                v-for="p in paymentsMix"
                                :key="p.method"
                                class="mb-2"
                            >
                                <div class="stack-row">
                                    <span class="text-capitalize">{{
                                        p.method
                                    }}</span>
                                    <span class="stack-val"
                                        >{{ p.count }} ({{ p.pct }}%)</span
                                    >
                                </div>
                                <div class="progress thin">
                                    <div
                                        class="progress-bar"
                                        :class="{
                                            'bg-secondary': p.method === 'cash',
                                            'bg-info': p.method === 'card',
                                            'bg-warning': p.method === 'qr',
                                            'bg-dark': p.method === 'bank',
                                        }"
                                        :style="{ width: p.pct + '%' }"
                                    />
                                </div>
                            </div>
                            <div
                                v-if="!paymentsMix.length"
                                class="small text-muted"
                            >
                                No payment data.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Items -->
            <div class="card border-0 shadow-sm rounded-4 mt-3">
                <div class="card-body">
                    <div
                        class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3"
                    >
                        <h6 class="fw-semibold mb-0">Top Items</h6>
                        <div class="search-wrap">
                            <i class="bi bi-search"></i>
                            <input
                                v-model="qItems"
                                type="text"
                                class="form-control search-input"
                                placeholder="Search item name"
                            />
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
                                <tr
                                    v-for="(r, i) in topItemsFiltered"
                                    :key="r.name"
                                >
                                    <td>{{ i + 1 }}</td>
                                    <td class="fw-semibold">
                                        {{ r.name }}
                                    </td>
                                    <td>{{ r.qty }}</td>
                                    <td>{{ money(r.revenue) }}</td>
                                </tr>
                                <tr
                                    v-if="
                                        !loading &&
                                        topItemsFiltered.length === 0
                                    "
                                >
                                    <td
                                        colspan="4"
                                        class="text-center text-muted py-4"
                                    >
                                        No items in this range.
                                    </td>
                                </tr>
                                <tr v-if="loading">
                                    <td
                                        colspan="4"
                                        class="text-center text-muted py-4"
                                    >
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
    background-color: #000000 !important; /* gray-800 */
    color: #ffffff !important; /* gray-50 */
}

.dark .table {
    background-color: #000000 !important; /* gray-900 */
    color: #f9fafb !important;
}
.dark .table thead {
    background-color: #000000 !important;
    color: #ffffff;
}

.dark .table thead th {
    background-color: #000000 !important;
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
    color: #000000;
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
