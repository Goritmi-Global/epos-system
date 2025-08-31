<script setup>
import Master from "@/Layouts/Master.vue";
import { Head } from "@inertiajs/vue3";
import { ref, computed, onMounted, onUpdated } from "vue";
import Select from "primevue/select";

/* ===================== Demo data (swap with API later) ===================== */
/* Orders: used for revenue, orders count, avg order value, items sold, charts */
const orders = ref([
    {
        id: 101,
        createdAt: new Date("2025-08-17T11:15:00"),
        type: "dine",
        customer: "Walk In",
        payment: "cash",
        total: 8.5,
        items: [
            { name: "Karak Chai", qty: 2, price: 2.5 },
            { name: "Cookie", qty: 1, price: 3.5 },
        ],
    },
    {
        id: 102,
        createdAt: new Date("2025-08-20T14:20:00"),
        type: "delivery",
        customer: "Amir",
        payment: "card",
        total: 12.0,
        items: [{ name: "Sandwich", qty: 2, price: 6.0 }],
    },
    {
        id: 103,
        createdAt: new Date("2025-08-22T18:45:00"),
        type: "dine",
        customer: "Walk In",
        payment: "cash",
        total: 5.0,
        items: [{ name: "Karak Chai", qty: 2, price: 2.5 }],
    },
    {
        id: 104,
        createdAt: new Date("2025-08-25T09:02:00"),
        type: "delivery",
        customer: "Sara",
        payment: "qr",
        total: 14.5,
        items: [
            { name: "Latte", qty: 1, price: 3.5 },
            { name: "Bagel", qty: 2, price: 5.5 },
        ],
    },
    {
        id: 105,
        createdAt: new Date("2025-08-26T12:10:00"),
        type: "dine",
        customer: "Ali",
        payment: "cash",
        total: 2.5,
        items: [{ name: "Karak Chai", qty: 1, price: 2.5 }],
    },
    {
        id: 106,
        createdAt: new Date("2025-08-28T16:30:00"),
        type: "dine",
        customer: "Walk In",
        payment: "card",
        total: 7.5,
        items: [
            { name: "Americano", qty: 1, price: 3.0 },
            { name: "Brownie", qty: 1, price: 4.5 },
        ],
    },
    {
        id: 107,
        createdAt: new Date("2025-08-29T00:22:00"),
        type: "dine",
        customer: "Ali Khan",
        payment: "cash",
        total: 2.5,
        items: [{ name: "Karak Chai", qty: 1, price: 2.5 }],
    },
    {
        id: 108,
        createdAt: new Date("2025-08-30T03:05:00"),
        type: "dine",
        customer: "Walk In",
        payment: "cash",
        total: 2.5,
        items: [{ name: "Karak Chai", qty: 1, price: 2.5 }],
    },
]);

/* ===================== Filters ===================== */
const q = ref(""); // table search (Top Items)
const range = ref("last30"); // 'today' | 'last7' | 'last30' | 'thisMonth' | 'all'
const orderType = ref("All"); // 'All' | 'dine' | 'delivery'
const payType = ref("All"); // 'All' | 'cash' | 'card' | 'qr' | 'bank'

const rangeOptions = ["today", "last7", "last30", "thisMonth", "all"];
const orderTypeOptions = ["All", "dine", "delivery"];
const payTypeOptions = ["All", "cash", "card", "qr", "bank"];

function inRange(date) {
    const d = new Date(date);
    const now = new Date();
    if (range.value === "all") return true;

    if (range.value === "today") {
        const start = new Date(
            now.getFullYear(),
            now.getMonth(),
            now.getDate()
        );
        const end = new Date(
            now.getFullYear(),
            now.getMonth(),
            now.getDate() + 1
        );
        return d >= start && d < end;
    }

    if (range.value === "thisMonth") {
        const start = new Date(now.getFullYear(), now.getMonth(), 1);
        const end = new Date(now.getFullYear(), now.getMonth() + 1, 1);
        return d >= start && d < end;
    }

    const days = range.value === "last7" ? 7 : 30;
    const start = new Date(now);
    start.setDate(start.getDate() - days);
    return d >= start && d <= now;
}

const filteredOrders = computed(() =>
    orders.value
        .filter((o) => inRange(o.createdAt))
        .filter((o) =>
            orderType.value === "All" ? true : o.type === orderType.value
        )
        .filter((o) =>
            payType.value === "All" ? true : o.payment === payType.value
        )
);

/* ===================== KPIs ===================== */
const revenue = computed(() =>
    filteredOrders.value.reduce((s, o) => s + o.total, 0)
);
const ordersCount = computed(() => filteredOrders.value.length);
const itemsSold = computed(() =>
    filteredOrders.value.reduce(
        (sum, o) => sum + o.items.reduce((s, it) => s + it.qty, 0),
        0
    )
);
const aov = computed(() =>
    ordersCount.value ? revenue.value / ordersCount.value : 0
);

/* ===================== Series (sales over time) ===================== */
function daysBack(n) {
    const out = [];
    for (let i = n - 1; i >= 0; i--) {
        const d = new Date();
        d.setHours(0, 0, 0, 0);
        d.setDate(d.getDate() - i);
        out.push(d);
    }
    return out;
}
const bucketDays = computed(() => {
    if (range.value === "today")
        return [new Date(new Date().setHours(0, 0, 0, 0))];
    if (range.value === "last7") return daysBack(7);
    if (range.value === "last30" || range.value === "thisMonth")
        return daysBack(30);
    return daysBack(30); // default resolution for "all"
});

const salesSeries = computed(() => {
    const map = new Map(bucketDays.value.map((d) => [d.toDateString(), 0]));
    filteredOrders.value.forEach((o) => {
        const k = new Date(
            new Date(o.createdAt).setHours(0, 0, 0, 0)
        ).toDateString();
        if (map.has(k)) map.set(k, map.get(k) + o.total);
    });
    return bucketDays.value.map((d) => ({
        x: d,
        y: map.get(d.toDateString()) || 0,
    }));
});

/* spark/line path utilities (no external chart libs) */
function buildPath(points, w, h, pad = 8) {
    if (!points.length) return "";
    const xs = points.map((p) => +p.x);
    const ys = points.map((p) => +p.y);
    const minX = +xs[0],
        maxX = +xs[xs.length - 1];
    const maxY = Math.max(1, ...ys);
    const minY = 0;

    const xScale = (x) =>
        pad + ((x - minX) / Math.max(1, maxX - minX)) * (w - pad * 2);
    const yScale = (y) =>
        h - pad - ((y - minY) / Math.max(1, maxY - minY)) * (h - pad * 2);

    return points
        .map((p, i) => `${i ? "L" : "M"}${xScale(+p.x)},${yScale(+p.y)}`)
        .join(" ");
}
const revenueSpark = computed(() =>
    buildPath(
        salesSeries.value.map((d, i) => ({ x: i, y: d.y })),
        160,
        50,
        6
    )
);

/* ===================== Breakdowns ===================== */
const ordersByType = computed(() => {
    const dine = filteredOrders.value.filter((o) => o.type === "dine").length;
    const delivery = filteredOrders.value.filter(
        (o) => o.type === "delivery"
    ).length;
    const total = dine + delivery || 1;
    return {
        dine,
        delivery,
        dinePct: Math.round((dine / total) * 100),
        deliveryPct: Math.round((delivery / total) * 100),
    };
});
const paymentsMix = computed(() => {
    const counts = filteredOrders.value.reduce((m, o) => {
        m[o.payment] = (m[o.payment] || 0) + 1;
        return m;
    }, {});
    const total = Object.values(counts).reduce((a, b) => a + b, 0) || 1;
    const keys = ["cash", "card", "qr", "bank"];
    return keys.map((k) => ({
        method: k,
        count: counts[k] || 0,
        pct: Math.round(((counts[k] || 0) / total) * 100),
    }));
});

/* ===================== Top Items table ===================== */
const itemsAgg = computed(() => {
    const map = new Map();
    filteredOrders.value.forEach((o) =>
        o.items.forEach((it) => {
            const n = it.name;
            const prev = map.get(n) || { name: n, qty: 0, revenue: 0 };
            prev.qty += it.qty;
            prev.revenue += it.qty * it.price;
            map.set(n, prev);
        })
    );
    return Array.from(map.values()).sort((a, b) => b.revenue - a.revenue);
});

const qItems = ref("");
const topItemsFiltered = computed(() => {
    const t = qItems.value.trim().toLowerCase();
    if (!t) return itemsAgg.value;
    return itemsAgg.value.filter((i) => i.name.toLowerCase().includes(t));
});

/* ===================== Helpers & stubs ===================== */
const money = (n, currency = "GBP") =>
    new Intl.NumberFormat("en-GB", { style: "currency", currency }).format(n);
const onDownload = (type) => console.log("Download analytics:", type);

onMounted(() => window.feather?.replace());
onUpdated(() => window.feather?.replace());
</script>

<template>
    <Head title="Analytics" />
    <Master>
        <div class="page-wrapper">
            <div class="container-fluid py-1">
                <!-- Title -->
                <h4 class="fw-semibold mb-3">Analytics</h4>

                <!-- Filters -->
                <div class="card border-0 shadow-sm rounded-4 mb-3">
                    <div
                        class="card-body d-flex flex-wrap gap-2 align-items-center justify-content-between"
                    >
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <div style="min-width: 180px">
                                <Select
                                    v-model="range"
                                    :options="rangeOptions"
                                    placeholder="Range"
                                    class="w-100"
                                    :appendTo="'body'"
                                    :autoZIndex="true"
                                    :baseZIndex="2000"
                                >
                                    <template #value="{ value, placeholder }">
                                        <span v-if="value">{{ value }}</span
                                        ><span v-else>{{ placeholder }}</span>
                                    </template>
                                </Select>
                            </div>

                            <div style="min-width: 160px">
                                <Select
                                    v-model="orderType"
                                    :options="orderTypeOptions"
                                    placeholder="Order Type"
                                    class="w-100"
                                    :appendTo="'body'"
                                    :autoZIndex="true"
                                    :baseZIndex="2000"
                                >
                                    <template #value="{ value, placeholder }">
                                        <span v-if="value">{{ value }}</span
                                        ><span v-else>{{ placeholder }}</span>
                                    </template>
                                </Select>
                            </div>

                            <div style="min-width: 160px">
                                <Select
                                    v-model="payType"
                                    :options="payTypeOptions"
                                    placeholder="Payment Type"
                                    class="w-100"
                                    :appendTo="'body'"
                                    :autoZIndex="true"
                                    :baseZIndex="2000"
                                >
                                    <template #value="{ value, placeholder }">
                                        <span v-if="value">{{ value }}</span
                                        ><span v-else>{{ placeholder }}</span>
                                    </template>
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
                                        @click="onDownload('pdf')"
                                        >Download as PDF</a
                                    >
                                </li>
                                <li>
                                    <a
                                        class="dropdown-item py-2"
                                        href="javascript:void(0)"
                                        @click="onDownload('excel')"
                                        >Download as Excel</a
                                    >
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body text-center">
                                <div class="icon-wrap mb-1">
                                    <i class="bi bi-currency-pound"></i>
                                </div>
                                <div class="kpi-label text-muted">Revenue</div>
                                <div class="kpi-value">
                                    {{ money(revenue) }}
                                </div>
                                <svg
                                    viewBox="0 0 160 50"
                                    class="spark mt-2"
                                    aria-hidden="true"
                                >
                                    <path
                                        :d="revenueSpark"
                                        class="spark-line"
                                    ></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body text-center">
                                <div class="icon-wrap mb-1">
                                    <i class="bi bi-receipt"></i>
                                </div>
                                <div class="kpi-label text-muted">Orders</div>
                                <div class="kpi-value">{{ ordersCount }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body text-center">
                                <div class="icon-wrap mb-1">
                                    <i class="bi bi-graph-up"></i>
                                </div>
                                <div class="kpi-label text-muted">
                                    Avg. Order Value
                                </div>
                                <div class="kpi-value">{{ money(aov) }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body text-center">
                                <div class="icon-wrap mb-1">
                                    <i class="bi bi-basket"></i>
                                </div>
                                <div class="kpi-label text-muted">
                                    Items Sold
                                </div>
                                <div class="kpi-value">{{ itemsSold }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts row -->
                <div class="row g-3 mt-1">
                    <!-- Sales over time -->
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body">
                                <div
                                    class="d-flex justify-content-between align-items-center mb-2"
                                >
                                    <h5 class="mb-0 fw-semibold">
                                        Sales Over Time
                                    </h5>
                                </div>

                                <div class="chart">
                                    <svg
                                        viewBox="0 0 720 220"
                                        class="w-100 h-100"
                                    >
                                        <!-- axes -->
                                        <line
                                            x1="40"
                                            y1="10"
                                            x2="40"
                                            y2="190"
                                            class="axis"
                                        />
                                        <line
                                            x1="40"
                                            y1="190"
                                            x2="710"
                                            y2="190"
                                            class="axis"
                                        />
                                        <!-- grid -->
                                        <g v-for="i in 5" :key="i">
                                            <line
                                                :x1="40"
                                                :x2="710"
                                                :y1="190 - i * 30"
                                                :y2="190 - i * 30"
                                                class="grid"
                                            />
                                        </g>
                                        <!-- path -->
                                        <path
                                            :d="
                                                buildPath(
                                                    salesSeries.map((d, i) => ({
                                                        x: i,
                                                        y: d.y,
                                                    })),
                                                    670,
                                                    180,
                                                    10
                                                )
                                                    .replace(/(^M| L)/g, (m) =>
                                                        m === ' L' ? ' L' : 'M'
                                                    )
                                                    .replace(
                                                        /(^| )M/,
                                                        `M 40,190 `
                                                    )
                                            "
                                            class="line"
                                        />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Breakdown -->
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm rounded-4 mb-3">
                            <div class="card-body">
                                <h6 class="fw-semibold mb-2">Orders by Type</h6>
                                <div
                                    class="d-flex justify-content-between small mb-1"
                                >
                                    <span>Dine In</span
                                    ><span
                                        >{{ ordersByType.dine }} ({{
                                            ordersByType.dinePct
                                        }}%)</span
                                    >
                                </div>
                                <div
                                    class="progress rounded-pill mb-2"
                                    style="height: 12px"
                                >
                                    <div
                                        class="progress-bar bg-success"
                                        role="progressbar"
                                        :style="{
                                            width: ordersByType.dinePct + '%',
                                        }"
                                    ></div>
                                </div>
                                <div
                                    class="d-flex justify-content-between small mb-1"
                                >
                                    <span>Delivery</span
                                    ><span
                                        >{{ ordersByType.delivery }} ({{
                                            ordersByType.deliveryPct
                                        }}%)</span
                                    >
                                </div>
                                <div
                                    class="progress rounded-pill"
                                    style="height: 12px"
                                >
                                    <div
                                        class="progress-bar bg-primary"
                                        role="progressbar"
                                        :style="{
                                            width:
                                                ordersByType.deliveryPct + '%',
                                        }"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body">
                                <h6 class="fw-semibold mb-2">Payments Mix</h6>
                                <div
                                    v-for="p in paymentsMix"
                                    :key="p.method"
                                    class="mb-2"
                                >
                                    <div
                                        class="d-flex justify-content-between small"
                                    >
                                        <span class="text-capitalize">{{
                                            p.method
                                        }}</span>
                                        <span
                                            >{{ p.count }} ({{ p.pct }}%)</span
                                        >
                                    </div>
                                    <div
                                        class="progress rounded-pill"
                                        style="height: 10px"
                                    >
                                        <div
                                            class="progress-bar"
                                            :class="{
                                                'bg-secondary':
                                                    p.method === 'cash',
                                                'bg-info': p.method === 'card',
                                                'bg-warning': p.method === 'qr',
                                                'bg-dark': p.method === 'bank',
                                            }"
                                            :style="{ width: p.pct + '%' }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Items table -->
                <div class="card border-0 shadow-lg rounded-4 mt-3">
                    <div class="card-body">
                        <div
                            class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3"
                        >
                            <h5 class="mb-0 fw-semibold">Top Items</h5>

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
                            <table
                                class="table table-hover align-middle"
                                style="min-height: 320px"
                            >
                                <thead class="border-top small text-muted">
                                    <tr>
                                        <th>S.#</th>
                                        <th>Item</th>
                                        <th>Qty Sold</th>
                                        <th>Revenue</th>
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
                                    <tr v-if="topItemsFiltered.length === 0">
                                        <td
                                            colspan="4"
                                            class="text-center text-muted py-4"
                                        >
                                            No items in this range.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
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

/* Sparkline */
.spark {
    width: 160px;
    height: 50px;
}
.spark-line {
    fill: none;
    stroke: var(--brand);
    stroke-width: 2;
    opacity: 0.9;
}

/* Chart (SVG) */
.chart {
    width: 100%;
    min-height: 240px;
}
.chart svg {
    display: block;
}
.axis {
    stroke: #111;
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

/* Progress overrides */
.progress {
    background: #eef2ff;
}

/* Table polish */
.table thead th {
    font-weight: 600;
}
.table tbody td {
    vertical-align: middle;
}

/* Buttons theme */
.btn-primary {
    background-color: var(--brand);
    border-color: var(--brand);
}
.btn-primary:hover {
    filter: brightness(1.05);
}
</style>
