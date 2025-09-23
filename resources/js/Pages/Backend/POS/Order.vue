<script setup>
import Master from "@/Layouts/Master.vue";
import { Head } from "@inertiajs/vue3";
import { ref, computed, onMounted } from "vue";
import Select from "primevue/select";

/* ===================== Demo Data (swap with API later) ===================== */
// const orders = ref([
//     // matches your screenshot shape
//     {
//         id: 1,
//         tableNo: 90,
//         orderType: "Dine In",
//         customer: "Ali Khan",
//         total: 2.5,
//         status: "paid",
//         createdAt: new Date(Date.now() - 23 * 60 * 60 * 1000), // 23 hours ago
//     },
//     {
//         id: 2,
//         tableNo: 1,
//         orderType: "Dine In",
//         customer: "Walk In",
//         total: 2.5,
//         status: "paid",
//         createdAt: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000), // 2 days ago
//     },
// ]);
const orders = ref([]);

const fetchOrders = async () => {
    try {
        const response = await axios.get("/orders/all-orders");
        orders.value = response.data.data;
        console.log(orders.value);
    } catch (error) {
        console.error("Error fetching inventory:", error);
    }
};
onMounted(() => {
    fetchOrders();
})

/* ===================== Toolbar: Search + Filters ===================== */
const q = ref("");
const orderTypeFilter = ref("All");
const statusFilter = ref("All");

const orderTypeOptions = ref(["All", "Dine In", "Delivery"]);
const statusOptions = ref(["All", "paid", "pending", "cancelled"]);

// const filtered = computed(() => {
//     const term = q.value.trim().toLowerCase();
//     return orders.value
//         .filter((o) =>
//             orderTypeFilter.value === "All"
//                 ? true
//                 : o.orderType === orderTypeFilter.value
//         )
//         .filter((o) =>
//             statusFilter.value === "All"
//                 ? true
//                 : o.status === statusFilter.value
//         )
//         .filter((o) => {
//             if (!term) return true;
//             return [
//                 String(o.id),
//                 String(o.tableNo),
//                 o.orderType,
//                 formatDate(o.createdAt),
//                 timeAgo(o.createdAt),
//                 o.customer,
//                 o.status,
//                 String(o.total),
//             ]
//                 .join(" ")
//                 .toLowerCase()
//                 .includes(term);
//         });
// });


const filtered = computed(() => {
    const term = q.value.trim().toLowerCase();

    return orders.value
        .filter((o) =>
            orderTypeFilter.value === "All"
                ? true
                : o.type?.order_type === orderTypeFilter.value
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
                o.type?.table_number ?? "",
                o.type?.order_type ?? "",
                o.customer_name ?? "",
                o.payment?.payment_type ?? "",
                o.status ?? "",
                String(o.total_amount ?? ""),
                formatDate(o.created_at),
                timeAgo(o.created_at),
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


const showPaymentModal = ref(false);
const selectedPayment = ref(null);

function openPaymentModal(payment) {
    selectedPayment.value = payment;
    showPaymentModal.value = true;
}

function closePaymentModal() {
    showPaymentModal.value = false;
    selectedPayment.value = null;
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
                            <div class="card-body d-flex flex-column justify-content-center text-center">
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
                            <div class="card-body d-flex flex-column justify-content-center text-center">
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
                            <div class="card-body d-flex flex-column justify-content-center text-center">
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
                        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                            <h5 class="mb-0 fw-semibold">Orders</h5>

                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <!-- Search -->
                                <div class="search-wrap">
                                    <i class="bi bi-search"></i>
                                    <input v-model="q" type="text" class="form-control search-input"
                                        placeholder="Search" />
                                </div>

                                <!-- Order Type filter -->
                                <div style="min-width: 170px">
                                    <Select v-model="orderTypeFilter" :options="orderTypeOptions"
                                        placeholder="Order Type" class="w-100" :appendTo="'body'" :autoZIndex="true"
                                        :baseZIndex="2000">
                                        <template #value="{ value, placeholder }">
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
                                    <Select v-model="statusFilter" :options="statusOptions" placeholder="Status"
                                        class="w-100" :appendTo="'body'" :autoZIndex="true" :baseZIndex="2000">
                                        <template #value="{ value, placeholder }">
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
                                    <button class="btn btn-outline-secondary rounded-pill px-4 dropdown-toggle"
                                        data-bs-toggle="dropdown">
                                        Download
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow rounded-4 py-2">
                                        <li>
                                            <a class="dropdown-item py-2" href="javascript:void(0)">Download as PDF</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item py-2" href="javascript:void(0)">Download as
                                                Excel</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="min-height: 320px">
                                <thead class="border-top small text-muted">
                                    <tr>
                                        <th style="width: 70px">S. #</th>
                                        <th>Table No.</th>
                                        <th>Order Type</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Customer</th>
                                        <th>Payment Type</th>
                                        <th>Total Price</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(o, i) in filtered" :key="o.id">
                                        <td>{{ i + 1 }}</td>
                                        <td>{{ o.type?.table_number ?? "-" }}</td>
                                        <td>{{ o.type?.order_type ?? "-" }}</td>
                                        <td>{{ formatDate(o.created_at) }}</td>
                                        <td>{{ timeAgo(o.created_at) }}</td>
                                        <td>{{ o.customer_name ?? "-" }}</td>
                                        <td>
                                            <span class="text-primary cursor-pointer"
                                                @click="openPaymentModal(o.payment)">
                                                {{ o.payment?.payment_type ?? "-" }}
                                            </span>
                                        </td>

                                        <td>{{ money(o.total_amount) }}</td>
                                        <td>
                                            <span class="badge rounded-pill fw-semibold px-3 py-2" :class="o.status === 'paid'
                                                ? 'bg-success-subtle text-success'
                                                : o.status === 'pending'
                                                    ? 'bg-warning-subtle text-warning'
                                                    : o.status === 'cancelled'
                                                        ? 'bg-danger-subtle text-danger'
                                                        : 'bg-secondary-subtle text-secondary'
                                                ">
                                                {{ o.status }}
                                            </span>
                                        </td>
                                    </tr>

                                    <tr v-if="filtered.length === 0">
                                        <td colspan="9" class="text-center text-muted py-4">
                                            No orders found.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>

                <!-- Modern Payment Details Modal -->
                <div v-if="showPaymentModal" class="modal fade show d-block" style="background: rgba(0,0,0,0.5)">
                    <div class="modal-dialog modal-md modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                            <!-- Header -->
                            <div class="modal-header border-0 text-black">
                                <h6 class="modal-title fw-semibold">
                                    <i class="bi bi-credit-card me-2"></i> Payment Details
                                </h6>

                                <button
                                    class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                                    @click="closePaymentModal" aria-label="Close" title="Close">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-danger" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>

                            </div>

                            <!-- Body -->
                            <div class="modal-body p-4">
                                <div class="payment-info">
                                    <div class="row g-3">
                                        <!-- Payment Type -->
                                        <div class="col-6">
                                            <div class="info-card">
                                                <span class="label">Payment Type</span>
                                                <span class="value">{{ selectedPayment?.payment_type ?? "-" }}</span>
                                            </div>
                                        </div>

                                        <!-- Status -->
                                        <div class="col-6">
                                            <div class="info-card">
                                                <span class="label">Status</span>
                                                <span class="value">{{ selectedPayment?.payment_status ?? "-" }}</span>
                                            </div>
                                        </div>

                                        <!-- Amount -->
                                        <div class="col-6">
                                            <div class="info-card">
                                                <span class="label">Amount Received</span>
                                                <span class="value">{{ money(selectedPayment?.amount_received) }}</span>
                                            </div>
                                        </div>

                                        <!-- Date -->
                                        <div class="col-6">
                                            <div class="info-card">
                                                <span class="label">Payment Date</span>
                                                <span class="value">{{ formatDate(selectedPayment?.payment_date)
                                                }}</span>
                                            </div>
                                        </div>

                                        <!-- Card Brand -->
                                        <div class="col-6" v-if="selectedPayment?.brand">
                                            <div class="info-card">
                                                <span class="label">Card Brand</span>
                                                <span class="value text-capitalize">{{ selectedPayment.brand }}</span>
                                            </div>
                                        </div>

                                        <!-- Last Digits -->
                                        <div class="col-6" v-if="selectedPayment?.last_digits">
                                            <div class="info-card">
                                                <span class="label">Last 4 Digits</span>
                                                <span class="value">**** **** **** {{ selectedPayment.last_digits
                                                }}</span>
                                            </div>
                                        </div>

                                        <!-- Expiry -->
                                        <div class="col-6" v-if="selectedPayment?.exp_month">
                                            <div class="info-card">
                                                <span class="label">Expiry</span>
                                                <span class="value">{{ selectedPayment.exp_month }}/{{
                                                    selectedPayment.exp_year
                                                }}</span>
                                            </div>
                                        </div>

                                        <!-- Currency -->
                                        <div class="col-6" v-if="selectedPayment?.currency_code">
                                            <div class="info-card">
                                                <span class="label">Currency</span>
                                                <span class="value">{{ selectedPayment.currency_code }}</span>
                                            </div>
                                        </div>


                                    </div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="modal-footer border-0">
                                <button class="btn btn-primary rounded-pill px-4 py-2" @click="closePaymentModal">
                                    Close
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


/* Modern Modal Styling */
.info-card {
    background: #f9fafb;
    border-radius: 10px;
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
    transition: all 0.2s ease;
}

.info-card:hover {
    background: #eef2ff;
}

.info-card .label {
    font-size: 0.75rem;
    color: #6b7280;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-card .value {
    font-size: 1rem;
    font-weight: 600;
    color: #111827;
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
