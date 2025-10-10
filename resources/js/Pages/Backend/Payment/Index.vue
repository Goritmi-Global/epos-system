<script setup>
import Master from "@/Layouts/Master.vue";
import { Head } from "@inertiajs/vue3";
import { ref, computed, onMounted, onUpdated } from "vue";
import Select from "primevue/select";
import { useFormatters } from '@/composables/useFormatters'

const { formatMoney, formatNumber, dateFmt } = useFormatters()
const orders = ref([]); // rename from payment -> orders for clarity

const fetchOrdersWithPayment = async () => {
    try {
        const response = await axios.get("/api/orders/all");
        orders.value = response.data.data;
        console.log(orders.value);
    } catch (error) {
        console.error("Error fetching orders:", error);
    }
};
onMounted(() => {
    fetchOrdersWithPayment();
});

/* ===================== Toolbar: Search + Filter ===================== */
const q = ref("");
const typeFilter = ref("All"); // 'All' | 'Cash' | 'Card' | 'QR' | 'Bank'
const typeOptions = ref(["All", "Cash", "Card", "QR", "Bank"]);

// Map orders → payments shape for easier UI handling
const payments = computed(() =>
    orders.value.map((o) => ({
        orderId: o.id,
        customer: o.customer_name,
        user: o.user?.name || "—",
        type: o.payment?.payment_type || "—",
        amount: o.payment?.amount_received || 0,
        paidAt: o.payment?.payment_date || null,
        status: o.status,
    }))
);

const filtered = computed(() => {
    const term = q.value.trim().toLowerCase();
    return payments.value
        .filter((p) =>
            typeFilter.value === "All"
                ? true
                : p.type.toLowerCase() === typeFilter.value.toLowerCase()
        )
        .filter((p) => {
            if (!term) return true;
            return [
                String(p.orderId),
                p.customer || "",
                p.user || "",
                p.type || "",
                String(p.amount),
                formatDateTime(p.paidAt),
            ]
                .join(" ")
                .toLowerCase()
                .includes(term);
        });
});

/* ===================== KPIs ===================== */
const totalPayments = computed(() => payments.value.length);

const todaysPayments = computed(() => {
    const now = new Date();
    const start = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const end = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1);

    return payments.value.filter((p) => {
        if (!p.paidAt) return false;
        const dt = new Date(p.paidAt);
        return dt >= start && dt < end;
    }).length;
});

const totalAmount = computed(() =>
    payments.value.reduce((sum, p) => sum + Number(p.amount || 0), 0)
);

/* ===================== Helpers ===================== */
const money = (n, currency = "PKR") =>
    new Intl.NumberFormat("en-PK", { style: "currency", currency }).format(n);

function formatDateTime(d) {
    if (!d) return "—";
    const dt = new Date(d);
    return dt.toLocaleString("en-GB", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
    });
}

/* ===================== Stubs ===================== */
const onDownload = (type) => console.log("Download:", type);

onMounted(() => window.feather?.replace());
onUpdated(() => window.feather?.replace());
</script>

<template>
    <Master>
        <div class="page-wrapper">
            <!-- Title -->
            <h4 class="fw-semibold mb-3">Overall Payments</h4>

            <!-- KPI Cards -->
            <div class="row g-3">
                <!-- Total Payments -->
                <div class="col-6 col-md-4">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <!-- Left Section (Text) -->
                            <div>
                                <h3 class="mb-0 fw-bold">{{ totalPayments }}</h3>
                                <p class="text-muted mb-0 small">Total Payments</p>
                            </div>

                            <!-- Right Section (Icon) -->
                            <div class="rounded-circle p-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center"
                                style="width: 56px; height: 56px">
                                <i class="bi bi-list-check fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Today's Payments -->
                <div class="col-6 col-md-4">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h3 class="mb-0 fw-bold">{{ todaysPayments }}</h3>
                                <p class="text-muted mb-0 small">Today's Payments</p>
                            </div>
                            <div class="rounded-circle p-3 bg-success-subtle text-success d-flex align-items-center justify-content-center"
                                style="width: 56px; height: 56px">
                                <i class="bi bi-calendar-day fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Amount -->
                <div class="col-12 col-md-4">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h3 class="mb-0 fw-bold">{{ formatMoney(totalAmount, 'GBP') }}</h3>
                                <p class="text-muted mb-0 small">Total Amount</p>
                            </div>
                            <div class="rounded-circle p-3 bg-warning-subtle text-warning d-flex align-items-center justify-content-center"
                                style="width: 56px; height: 56px">
                                <i class="bi bi-currency-pound fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Payments Table -->
            <div class="card border-0 shadow-lg rounded-4 mt-3">
                <div class="card-body">
                    <!-- Toolbar -->
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 fw-semibold">Payments</h5>

                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <!-- Search -->
                            <div class="search-wrap">
                                <i class="bi bi-search"></i>
                                <input v-model="q" type="text" class="form-control search-input"
                                    placeholder="Search by Order ID or Username" />
                            </div>

                            <!-- Payment type filter -->
                            <div style="min-width: 180px">
                                <Select v-model="typeFilter" :options="typeOptions" placeholder="Payment Type"
                                    class="w-100" :appendTo="'body'" :autoZIndex="true" :baseZIndex="2000">
                                    <template #value="{ value, placeholder }">
                                        <span v-if="value">{{ value }}</span>
                                        <span v-else>{{ placeholder }}</span>
                                    </template>
                                </Select>
                            </div>

                            <!-- Download all -->
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary rounded-pill px-4 dropdown-toggle"
                                    data-bs-toggle="dropdown">
                                    Download
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow rounded-4 py-2">
                                    <li>
                                        <a class="dropdown-item py-2" href="javascript:void(0)"
                                            @click="onDownload('pdf')">Download as PDF</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2" href="javascript:void(0)"
                                            @click="onDownload('excel')">Download as Excel</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" style="min-height: 320px">
                            <thead class="border-top small text-muted">
                                <tr>
                                    <th>S. #</th>
                                    <th>Order ID</th>
                                    <th>Amount Received</th>
                                    <th>Payment Date</th>
                                    <th>Payment Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(p, idx) in filtered" :key="p.id">
                                    <td>{{ idx + 1 }}</td>
                                    <td>{{ p.orderId }}</td>
                                    <td>{{ formatMoney(p.amount, "GBP") }}</td>
                                    <td>{{ dateFmt(p.paidAt) }}</td>
                                    <td class="text-capitalize">
                                        {{ p.type }}
                                    </td>
                                </tr>

                                <tr v-if="filtered.length === 0">
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No payments found.
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
    color: #181818;
}

/* Search pill */
.search-wrap {
    position: relative;
    width: clamp(260px, 36vw, 420px);
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
}

.table tbody td {
    vertical-align: middle;
}

:deep(.p-multiselect-overlay) {
    background: #fff !important;
    color: #000 !important;
}

/* Header area (filter + select all) */
:deep(.p-multiselect-header) {
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
}

/* Checkbox box in dropdown */
:deep(.p-multiselect-overlay .p-checkbox-box) {
    background: #fff !important;
    border: 1px solid #ccc !important;
}

:deep(.p-multiselect-overlay .p-checkbox-box.p-highlight) {
    background: #007bff !important;
    /* blue when checked */
    border-color: #007bff !important;
}

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
    /* light gray, like Bootstrap badge */
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
:deep(.p-multiselect-label) {
    color: #000 !important;
}

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
    background: #181818 !important;
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
    background: #222 !important;
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
    background: #111 !important;
    color: #fff !important;
    border: 1px solid #555 !important;
    border-radius: 12px !important;
    padding: 0.25rem 0.5rem !important;
}

/* Chip remove (x) icon */
:global(.dark .p-multiselect-chip .p-chip-remove-icon) {
    color: #ccc !important;
}

:global(.dark .p-multiselect-chip .p-chip-remove-icon:hover) {
    color: #f87171 !important; /* lighter red */
}

/* ==================== Dark Mode Select Styling ====================== */
:global(.dark .p-select) {
    background-color: #181818 !important;
    color: #fff !important;
    border-color: #555 !important;
}

/* Options container */
:global(.dark .p-select-list-container) {
    background-color: #181818 !important;
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

</style>
