<script setup>
import Master from "@/Layouts/Master.vue";
import { Head } from "@inertiajs/vue3";
import { ref, computed, onMounted, onUpdated } from "vue";
import Select from "primevue/select";


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
  orders.value.map(o => ({
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
    .filter(p =>
      typeFilter.value === "All" ? true : p.type.toLowerCase() === typeFilter.value.toLowerCase()
    )
    .filter(p => {
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

  return payments.value.filter(p => {
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
      <div class="container-fluid py-1">
        <!-- Title -->
        <h4 class="fw-semibold mb-3">Overall Payments</h4>

        <!-- KPI Cards -->
        <div class="row g-3">
          <div class="col-6 col-md-4">
            <div class="card border-0 shadow-sm rounded-4">
              <div class="card-body d-flex flex-column justify-content-center text-center">
                <div class="icon-wrap mb-2"><i class="bi bi-list-check"></i></div>
                <div class="kpi-label text-muted">Total Payments</div>
                <div class="kpi-value">{{ totalPayments }}</div>
              </div>
            </div>
          </div>

          <div class="col-6 col-md-4">
            <div class="card border-0 shadow-sm rounded-4">
              <div class="card-body d-flex flex-column justify-content-center text-center">
                <div class="icon-wrap mb-2"><i class="bi bi-calendar-day"></i></div>
                <div class="kpi-label text-muted">Today's Payments</div>
                <div class="kpi-value">{{ todaysPayments }}</div>
              </div>
            </div>
          </div>

          <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4">
              <div class="card-body d-flex flex-column justify-content-center text-center">
                <div class="icon-wrap mb-2"><i class="bi bi-currency-pound"></i></div>
                <div class="kpi-label text-muted">Total Amount</div>
                <div class="kpi-value">{{ money(totalAmount, "GBP") }}</div>
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
                  <input
                    v-model="q"
                    type="text"
                    class="form-control search-input"
                    placeholder="Search by Order ID or Username"
                  />
                </div>

                <!-- Payment type filter -->
                <div style="min-width: 180px">
                  <Select
                    v-model="typeFilter"
                    :options="typeOptions"
                    placeholder="Payment Type"
                    class="w-100"
                    :appendTo="'body'"
                    :autoZIndex="true"
                    :baseZIndex="2000"
                  >
                    <template #value="{ value, placeholder }">
                      <span v-if="value">{{ value }}</span>
                      <span v-else>{{ placeholder }}</span>
                    </template>
                  </Select>
                </div>

                <!-- Download all -->
                <div class="dropdown">
                  <button class="btn btn-outline-secondary rounded-pill px-4 dropdown-toggle" data-bs-toggle="dropdown">
                    Download all
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end shadow rounded-4 py-2">
                    <li><a class="dropdown-item py-2" href="javascript:void(0)" @click="onDownload('pdf')">Download as PDF</a></li>
                    <li><a class="dropdown-item py-2" href="javascript:void(0)" @click="onDownload('excel')">Download as Excel</a></li>
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
                    <td>{{ money(p.amount, "GBP") }}</td>
                    <td>{{ formatDateTime(p.paidAt) }}</td>
                    <td class="text-capitalize">{{ p.type }}</td>
                  </tr>

                  <tr v-if="filtered.length === 0">
                    <td colspan="5" class="text-center text-muted py-4">No payments found.</td>
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
.dark h4{
    color: white;
}
.dark .card {
  background-color: #111827 !important; /* gray-800 */
  color: #ffffff !important;           /* gray-50 */
}

.dark .table {
  background-color: #111827 !important; /* gray-900 */
  color: #f9fafb !important;
}
.dark .table thead{
background-color:#111827 !important; ;
 color: #ffffff;
}

.dark .table thead th{
  background-color:#111827 !important; ;
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
  color: #111827;
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
</style>
