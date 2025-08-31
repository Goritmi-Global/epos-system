<script setup>
import Master from "@/Layouts/Master.vue";
import { Head } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import Select from "primevue/select";

/* ===================== Demo Data (swap with API later) ===================== */
const orders = ref([
    // matches your screenshot shape
    {
        id: 1,
        tableNo: 90,
        orderType: "Dine In",
        customer: "Ali Khan",
        total: 2.5,
        status: "paid",
        createdAt: new Date(Date.now() - 23 * 60 * 60 * 1000), // 23 hours ago
    },
    {
        id: 2,
        tableNo: 1,
        orderType: "Dine In",
        customer: "Walk In",
        total: 2.5,
        status: "paid",
        createdAt: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000), // 2 days ago
    },
]);

/* ===================== Toolbar: Search + Filters ===================== */
const q = ref("");
const orderTypeFilter = ref("All");
const statusFilter = ref("All");

const orderTypeOptions = ref(["All", "Dine In", "Delivery"]);
const statusOptions = ref(["All", "paid", "pending", "cancelled"]);

const filtered = computed(() => {
    const term = q.value.trim().toLowerCase();
    return orders.value
        .filter((o) =>
            orderTypeFilter.value === "All"
                ? true
                : o.orderType === orderTypeFilter.value
        )
        .filter((o) =>
            statusFilter.value === "All"
                ? true
                : o.status === statusFilter.value
        )
        .filter((o) => {
            if (!term) return true;
            return [
                String(o.id),
                String(o.tableNo),
                o.orderType,
                formatDate(o.createdAt),
                timeAgo(o.createdAt),
                o.customer,
                o.status,
                String(o.total),
            ]
                .join(" ")
                .toLowerCase()
                .includes(term);
        });
});

/* ===================== KPIs ===================== */
const totalOrders = computed(() => orders.value.length);
const completedOrders = computed(
    () => orders.value.filter((o) => o.status === "paid").length
);
const pendingOrders = computed(
    () => orders.value.filter((o) => o.status !== "paid").length
);

/* ===================== Helpers ===================== */
function formatDate(d) {
    const dt = new Date(d);
    const yyyy = dt.getFullYear();
    const mm = String(dt.getMonth() + 1).padStart(2, "0");
    const dd = String(dt.getDate()).padStart(2, "0");
    return `${yyyy}-${mm}-${dd}`;
}
function timeAgo(d) {
    const diff = Date.now() - new Date(d).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 60) return `${mins} minute${mins === 1 ? "" : "s"} ago`;
    const hrs = Math.floor(mins / 60);
    if (hrs < 24) return `${hrs} hour${hrs === 1 ? "" : "s"} ago`;
    const days = Math.floor(hrs / 24);
    return `${days} day${days === 1 ? "" : "s"} ago`;
}
function money(n) {
    const v = Number(n ?? 0);
    return `£${Number.isInteger(v) ? v.toFixed(0) : v.toFixed(1)}`;
}
</script>

<template>
    <Head title="Orders" />

    <Master>
        <div class="page-wrapper">
            <div class="container-fluid py-1">
                <!-- Title -->
                <h4 class="fw-semibold mb-3">Overall Orders</h4>

                <!-- KPI Cards (same style as sample) -->
                <div class="row g-3">
                    <div class="col-6 col-md-4">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div
                                class="card-body d-flex flex-column justify-content-center text-center"
                            >
                                <div class="icon-wrap mb-2">
                                    <i class="bi bi-list-task"></i>
                                </div>
                                <div class="kpi-label text-muted">
                                    Total Orders
                                </div>
                                <div class="kpi-value">{{ totalOrders }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div
                                class="card-body d-flex flex-column justify-content-center text-center"
                            >
                                <div class="icon-wrap mb-2">
                                    <i class="bi bi-check2-circle"></i>
                                </div>
                                <div class="kpi-label text-muted">
                                    Completed Orders
                                </div>
                                <div class="kpi-value">
                                    {{ completedOrders }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div
                                class="card-body d-flex flex-column justify-content-center text-center"
                            >
                                <div class="icon-wrap mb-2">
                                    <i class="bi bi-hourglass-split"></i>
                                </div>
                                <div class="kpi-label text-muted">
                                    Pending Orders
                                </div>
                                <div class="kpi-value">{{ pendingOrders }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="card border-0 shadow-lg rounded-4 mt-3">
                    <div class="card-body">
                        <!-- Toolbar -->
                        <div
                            class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3"
                        >
                            <h5 class="mb-0 fw-semibold">Orders</h5>

                            <div
                                class="d-flex flex-wrap gap-2 align-items-center"
                            >
                                <!-- Search -->
                                <div class="search-wrap">
                                    <i class="bi bi-search"></i>
                                    <input
                                        v-model="q"
                                        type="text"
                                        class="form-control search-input"
                                        placeholder="Search"
                                    />
                                </div>

                                <!-- Order Type filter -->
                                <div style="min-width: 170px">
                                    <Select
                                        v-model="orderTypeFilter"
                                        :options="orderTypeOptions"
                                        placeholder="Order Type"
                                        class="w-100"
                                        :appendTo="'body'"
                                        :autoZIndex="true"
                                        :baseZIndex="2000"
                                    >
                                        <template
                                            #value="{ value, placeholder }"
                                        >
                                            <span v-if="value">{{
                                                value
                                            }}</span>
                                            <span v-else>{{
                                                placeholder
                                            }}</span>
                                        </template>
                                    </Select>
                                </div>

                                <!-- Status filter -->
                                <div style="min-width: 160px">
                                    <Select
                                        v-model="statusFilter"
                                        :options="statusOptions"
                                        placeholder="Status"
                                        class="w-100"
                                        :appendTo="'body'"
                                        :autoZIndex="true"
                                        :baseZIndex="2000"
                                    >
                                        <template
                                            #value="{ value, placeholder }"
                                        >
                                            <span v-if="value">{{
                                                value
                                            }}</span>
                                            <span v-else>{{
                                                placeholder
                                            }}</span>
                                        </template>
                                    </Select>
                                </div>

                                <!-- Download -->
                                <div class="dropdown">
                                    <button
                                        class="btn btn-outline-secondary rounded-pill px-4 dropdown-toggle"
                                        data-bs-toggle="dropdown"
                                    >
                                        Download
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
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table
                                class="table table-hover align-middle mb-0"
                                style="min-height: 320px"
                            >
                                <thead class="border-top small text-muted">
                                    <tr>
                                        <th style="width: 70px">S. #</th>
                                        <th>Table No.</th>
                                        <th>Order Type</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Customer</th>
                                        <th>Total Price</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(o, i) in filtered" :key="o.id">
                                        <td>{{ i + 1 }}</td>
                                        <td>{{ o.tableNo }}</td>
                                        <td>{{ o.orderType }}</td>
                                        <td>{{ formatDate(o.createdAt) }}</td>
                                        <td>{{ timeAgo(o.createdAt) }}</td>
                                        <td>{{ o.customer }}</td>
                                        <td>{{ money(o.total) }}</td>
                                        <td>
                                            <span
                                                class="badge rounded-pill fw-semibold px-3 py-2"
                                                :class="
                                                    o.status === 'paid'
                                                        ? 'bg-success-subtle text-success'
                                                        : 'bg-secondary-subtle text-secondary'
                                                "
                                            >
                                                {{ o.status }}
                                            </span>
                                        </td>
                                    </tr>

                                    <tr v-if="filtered.length === 0">
                                        <td
                                            colspan="8"
                                            class="text-center text-muted py-4"
                                        >
                                            No orders found.
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

/* Buttons theme */
.btn-primary {
    background-color: var(--brand);
    border-color: var(--brand);
}
.btn-primary:hover {
    filter: brightness(1.05);
}

/* Table polish */
.table thead th {
    font-weight: 600;
    border: 0 !important;
}
.table tbody td {
    vertical-align: middle;
    border-color: #eee;
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
