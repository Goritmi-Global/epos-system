ot
<script setup>
import Master from "@/Layouts/Master.vue";
import { Head } from "@inertiajs/vue3";
import { ref, computed, onMounted } from "vue";
import Select from "primevue/select";
import { Pencil, Eye } from "lucide-vue-next";
import { useFormatters } from '@/composables/useFormatters'

const { formatMoney, formatCurrencySymbol, formatNumber, dateFmt } = useFormatters()

const orders = ref([]);

const fetchOrders = async () => {
    try {
        const response = await axios.get("/api/orders/all");
        orders.value = response.data.data;
    } catch (error) {
        console.error("Error fetching inventory:", error);
    }
};
onMounted(() => {
    fetchOrders();
});

const q = ref("");
const orderTypeFilter = ref("All");
const paymentTypeFilter = ref("All"); // <-- renamed

const orderTypeOptions = ref(["All", "Dine In", "Delivery", "Takeaway"]);
const paymentTypeOptions = ref(["All", "Cash", "Card", "Split"]); // <-- new

const filtered = computed(() => {
    const term = q.value.trim().toLowerCase();

    return (
        orders.value
            // Order Type (Dine In / Delivery / Takeaway)
            .filter((o) =>
                orderTypeFilter.value === "All"
                    ? true
                    : (o.type?.order_type ?? "").toLowerCase() ===
                    orderTypeFilter.value.toLowerCase()
            )
            // Payment Type (Cash / Card / Split)
            .filter((o) =>
                paymentTypeFilter.value === "All"
                    ? true
                    : (o.payment?.payment_type ?? "").toLowerCase() ===
                    paymentTypeFilter.value.toLowerCase()
            )
            // Search
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
            })
    );
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
const selectedOrder = ref(null);

function openPaymentModal(payment) {
    selectedPayment.value = payment;
    showPaymentModal.value = true;

    const modal = new bootstrap.Modal(
        document.getElementById("paymentDetailsModal")
    );
    modal.show();
}

function closePaymentModal() {
    showPaymentModal.value = false;
    selectedPayment.value = null;
    const modal = new bootstrap.Modal(
        document.getElementById("orderDetailsModal")
    );
    modal.show();
}

const openOrderDetails = (order) => {
    selectedOrder.value = order;
    const modal = new bootstrap.Modal(
        document.getElementById("orderDetailsModal")
    );
    modal.show();
};

function printReceipt(order) {
    // ---- helpers
    const money = (n) => `£${Number(n ?? 0).toFixed(2)}`;
    const safe = (s) => (s ?? "").toString();

    // ---- pick payment info (works with both shapes)
    const paymentObj = order?.payment ?? {};
    const payType = (
        paymentObj.payment_type ??
        order?.payment_method ??
        "Cash"
    ).toLowerCase();

    // split amounts (try payment.*, fallback to flat fields)
    const cashAmt =
        paymentObj.cash_amount ??
        order?.cash_amount ??
        order?.cash_received ??
        0;
    const cardAmt = paymentObj.card_amount ?? order?.card_amount ?? 0;
    const brand = paymentObj.brand ?? order?.card_brand ?? "";
    const last4 =
        paymentObj.last_digits ?? order?.last4 ?? order?.last_digits ?? "";

    let payLine = "";
    if (payType === "split") {
        payLine = `Payment: Split (Cash: ${money(cashAmt)}, Card: ${money(
            cardAmt
        )})`;
    } else if (payType === "card" || payType === "stripe") {
        const brandPart = brand ? ` (${brand})` : "";
        const last4Part = last4 ? ` •••• ${last4}` : "";
        payLine = `Payment: Card${brandPart}${last4Part}`;
    } else {
        // Cash or anything else
        // prefer explicit method if set on order
        const label = safe(order?.payment_method) || "Cash";
        payLine = `Payment: ${label}`;
    }

    // ---- order meta
    const orderId = order?.id ?? "N/A";
    const orderType = order?.type?.order_type ?? order?.order_type ?? "N/A";
    const tableNo = order?.type?.table_number ?? order?.table_number ?? null;
    const customer = order?.customer_name || "Walk In";
    const createdAt = order?.created_at
        ? new Date(order.created_at)
        : new Date();
    const dateStr = `${createdAt.getFullYear()}-${String(
        createdAt.getMonth() + 1
    ).padStart(2, "0")}-${String(createdAt.getDate()).padStart(
        2,
        "0"
    )} ${String(createdAt.getHours()).padStart(2, "0")}:${String(
        createdAt.getMinutes()
    ).padStart(2, "0")}`;

    const items = Array.isArray(order?.items) ? order.items : [];
    const subTotal =
        order?.sub_total ??
        items.reduce(
            (s, i) => s + Number(i.price ?? 0) * Number(i.quantity ?? 0),
            0
        );
    const total = order?.total_amount ?? subTotal;

    // ---- build rows
    const itemRows = items
        .map(
            (it) => `
    <div class="row">
      <span class="w-50 ellips">${safe(it.title)}</span>
      <span class="w-20 right">x${Number(it.quantity ?? 0)}</span>
      <span class="w-30 right">${money(it.price)}</span>
    </div>
  `
        )
        .join("");

    const html = `
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Receipt</title>
<style>
  :root { --w: 58mm; }
  html, body { margin:0; padding:0; }
  body { width: var(--w); font-family: monospace; font-size: 12px; line-height: 1.3; color:#000; }
  .center { text-align:center; }
  .right { text-align:right; }
  .row { display:flex; justify-content:space-between; gap:4px; }
  .w-50 { width:50%; } .w-20 { width:20%; } .w-30 { width:30%; }
  .ellips { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  hr { border:0; border-top:1px dashed #000; margin:4px 0; }
  h3 { margin:0 0 2px 0; font-size: 14px; }
  small { font-size:11px; }
  .mt-4 { margin-top:4px; }
  .mb-4 { margin-bottom:4px; }
  .bold { font-weight:bold; }
  @page { size: var(--w) auto; margin: 3mm; }
  @media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
</style>
</head>
<body>
  <div class="center">
    <h3>Goritmi</h3>
    <small>Order #${orderId}</small><br/>
    <small>${dateStr}</small>
  </div>

  <hr/>
  <div><small>Customer: ${safe(customer)}</small></div>
  <div><small>Order Type: ${safe(orderType)}${tableNo ? ` | Table: ${safe(tableNo)}` : ""
        }</small></div>
  <div><small>${payLine}</small></div>

  <hr/>
  <div class="row bold"><span class="w-50">Item</span><span class="w-20 right">Qty</span><span class="w-30 right">Price</span></div>
  ${itemRows}
  <hr/>

  <div class="row"><span>Subtotal:</span><span class="right">${money(
            subTotal
        )}</span></div>
  ${Number(order?.tax ?? 0)
            ? `<div class="row"><span>Tax:</span><span class="right">${money(
                order.tax
            )}</span></div>`
            : ""
        }
  ${Number(order?.service_charges ?? 0)
            ? `<div class="row"><span>Service:</span><span class="right">${money(
                order.service_charges
            )}</span></div>`
            : ""
        }
  ${Number(order?.delivery_charges ?? 0)
            ? `<div class="row"><span>Delivery:</span><span class="right">${money(
                order.delivery_charges
            )}</span></div>`
            : ""
        }

  <div class="row bold mt-4"><span>Total:</span><span class="right">${money(
            total
        )}</span></div>

  ${payType === "cash" || !payType
            ? `<div class="row"><span>Cash Received:</span><span class="right">${money(
                order?.cash_received ?? paymentObj?.amount_received ?? 0
            )}</span></div>
       <div class="row"><span>Change:</span><span class="right">${money(
                order?.change ?? 0
            )}</span></div>`
            : ""
        }

  <hr/>
  <div class="center">
    <small>Abdara Road, Peshawar</small><br/>
    <small>info@goritmi.com</small><br/>
    <small>Thank you for your visit!</small>
  </div>

<script>
  async function ready() {
    try { if (document.fonts && document.fonts.ready) { await document.fonts.ready; } } catch(e){}
    window.focus();
    const mq = window.matchMedia && window.matchMedia('print');
    if (mq && mq.addEventListener) {
      mq.addEventListener('change', function(e){ if(!e.matches) setTimeout(function(){ window.close(); }, 300); });
    } else if (mq && mq.addListener) { // older browsers
      mq.addListener(function(mql){ if(!mql.matches) setTimeout(function(){ window.close(); }, 300); });
    }
    window.onafterprint = function(){ setTimeout(function(){ window.close(); }, 300); };
    setTimeout(function(){ window.print(); }, 200);
  }
  if (document.readyState === 'complete') ready();
  else window.addEventListener('load', ready);
<\/script>
</body>
</html>
`;

    const w = window.open("", "", "width=420,height=640");
    if (!w) {
        console.error("Popup blocked or failed to open");
        alert("Please allow popups for this site to print receipts");
        return;
    }
    w.document.open();
    w.document.write(html);
    w.document.close();
}
</script>

<template>

    <Head title="Orders" />

    <Master>
        <div class="page-wrapper">
            <!-- Title -->
            <h4 class="fw-semibold mb-3">Overall Orders</h4>

            <!-- KPI Cards (same style as sample) -->
            <div class="row g-3">
                <div class="col-md-6 col-xl-4">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body d-flex align-items-center justify-content-between">

                            <!-- Left Text Section -->
                            <div>
                                <h3 class="mb-0 fw-bold">{{ totalOrders }}</h3>
                                <p class="text-muted mb-0 small">Total Orders</p>
                            </div>

                            <!-- Right Icon Section -->
                            <div class="rounded-circle p-2 d-flex align-items-center justify-content-center text-primary"
                                style="width: 40px; height: 40px">
                                <i class="bi bi-list-task fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-4">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h3 class="mb-0 fw-bold">{{ completedOrders }}</h3>
                                <p class="text-muted mb-0 small">Completed Orders</p>
                            </div>
                            <div class="rounded-circle p-3 d-flex align-items-center justify-content-center text-success"
                                style="width: 40px; height: 40px">
                                <i class="bi bi-check2-circle fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h3 class="mb-0 fw-bold">{{ pendingOrders }}</h3>
                                <p class="text-muted mb-0 small">Pending Orders</p>
                            </div>
                            <div class="rounded-circle p-3 d-flex align-items-center justify-content-center text-warning"
                                style="width: 40px; height: 40px">
                                <i class="bi bi-hourglass-split fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="card border-0 shadow-lg rounded-4 mt-0">
                <div class="card-body">
                    <!-- Toolbar -->
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 fw-semibold">Orders</h5>

                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <!-- Search -->
                            <div class="search-wrap">
                                <i class="bi bi-search"></i>
                                <input v-model="q" type="text" class="form-control search-input" placeholder="Search" />
                            </div>

                            <!-- Order Type filter -->
                            <div style="min-width: 170px">
                                <Select v-model="orderTypeFilter" :options="orderTypeOptions" placeholder="Order Type"
                                    class="w-100" :appendTo="'body'" :autoZIndex="true" :baseZIndex="2000">
                                    <template #value="{ value, placeholder }">
                                        <span v-if="value">{{ value }}</span>
                                        <span v-else>{{ placeholder }}</span>
                                    </template>
                                </Select>
                            </div>

                            <!-- Payment Type filter (replaces Status) -->
                            <div style="min-width: 160px">
                                <Select v-model="paymentTypeFilter" :options="paymentTypeOptions"
                                    placeholder="Payment Type" class="w-100" :appendTo="'body'" :autoZIndex="true"
                                    :baseZIndex="2000">
                                    <template #value="{ value, placeholder }">
                                        <span v-if="value">{{ value }}</span>
                                        <span v-else>{{ placeholder }}</span>
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
                                        <a class="dropdown-item py-2" href="javascript:void(0)">Download as Excel</a>
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
                                    <!-- <th class="text-center">Status</th> -->
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(o, i) in filtered" :key="o.id">
                                    <td>{{ i + 1 }}</td>
                                    <td>
                                        {{ o.type?.table_number ?? "-" }}
                                    </td>
                                    <td>{{ o.type?.order_type ?? "-" }}</td>
                                    <td>{{ dateFmt(o.created_at) }}</td>
                                    <td>{{ timeAgo(o.created_at) }}</td>
                                    <td>{{ o.customer_name ?? "-" }}</td>
                                    <td>
                                        <span class="text-primary cursor-pointer" @click="openPaymentModal(o.payment)">
                                            {{ o.payment?.payment_type ?? "-" }}
                                        </span>
                                    </td>

                                    <td>{{ formatCurrencySymbol(o.total_amount) }}</td>
                                    <!-- <td class="text-center">
                                            <span
                                                class="badge rounded-pill fw-semibold px-5 py-2 text-capitalize paid-text"
                                                :class="
                                                    o.status === 'paid'
                                                        ? 'bg-success text-white'
                                                        : o.status === 'pending'
                                                        ? 'bg-warning text-dark'
                                                        : o.status ===
                                                          'cancelled'
                                                        ? 'bg-danger text-white'
                                                        : 'bg-secondary text-white'
                                                "
                                            >
                                                {{ o.status }}
                                            </span>
                                        </td> -->
                                    <td class="text-center">
                                        <button class="p-2 rounded-full text-gray-600 hover:bg-blue-100"
                                            @click="openOrderDetails(o)" title="View Order">
                                            <Eye class="w-4 h-4" />
                                        </button>
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

            <!-- Payment Details Modal -->
            <div class="modal fade" id="paymentDetailsModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-md modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                        <!-- Header -->
                        <div class="modal-header border-0 text-black">
                            <h6 class="modal-title fw-semibold">
                                <i class="bi bi-credit-card me-2"></i>
                                Payment Details
                            </h6>

                            <button
                                class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                                data-bs-dismiss="modal" aria-label="Close" title="Close">
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
                                            <span class="value">{{
                                                selectedPayment?.payment_type ??
                                                "-"
                                                }}</span>
                                        </div>
                                    </div>

                                    <!-- Status -->
                                    <!-- <div class="col-6">
                                            <div class="info-card">
                                                <span class="label">Status</span>
                                                <span
                                                    class="badge rounded-pill fw-semibold py-2 d-inline-block small w-1/2 text-uppercase"
                                                    :class="{
                                                        'bg-success text-light': selectedPayment?.payment_status === 'succeeded',
                                                        'bg-danger text-light': selectedPayment?.payment_status === 'failed',
                                                        'bg-warning text-dark': selectedPayment?.payment_status === 'pending',
                                                        'bg-secondary text-light': !['succeeded', 'failed', 'pending'].includes(selectedPayment?.payment_status)
                                                    }">
                                                    {{ selectedPayment?.payment_status ?? '-' }}
                                                </span>
                                            </div>
                                        </div> -->

                                    <!-- Amount -->
                                    <div class="col-6">
                                        <div class="info-card">
                                            <span class="label">Amount Received</span>
                                            <span class="value">{{
                                                money(
                                                    selectedPayment?.amount_received
                                                )
                                            }}</span>
                                        </div>
                                    </div>

                                    <!-- Split amounts -->
                                    <div v-if="
                                        selectedPayment?.payment_type ===
                                        'Split'
                                    " class="col-6">
                                        <div class="info-card">
                                            <span class="label">Cash Amount</span>
                                            <span class="value">{{
                                                money(
                                                    selectedPayment?.cash_amount
                                                )
                                            }}</span>
                                        </div>
                                    </div>

                                    <div v-if="
                                        selectedPayment?.payment_type ===
                                        'Split'
                                    " class="col-6">
                                        <div class="info-card">
                                            <span class="label">Card Amount</span>
                                            <span class="value">{{
                                                money(
                                                    selectedPayment?.card_amount
                                                )
                                            }}</span>
                                        </div>
                                    </div>

                                    <!-- Date -->
                                    <div class="col-6">
                                        <div class="info-card">
                                            <span class="label">Payment Date</span>
                                            <span class="value">{{
                                                formatDate(
                                                    selectedPayment?.payment_date
                                                )
                                            }}</span>
                                        </div>
                                    </div>

                                    <!-- Card Brand -->
                                    <div class="col-6" v-if="selectedPayment?.brand">
                                        <div class="info-card">
                                            <span class="label">Card Brand</span>
                                            <span class="value text-capitalize">{{
                                                selectedPayment.brand
                                            }}</span>
                                        </div>
                                    </div>

                                    <!-- Last Digits -->
                                    <div class="col-6" v-if="selectedPayment?.last_digits">
                                        <div class="info-card">
                                            <span class="label">Last 4 Digits</span>
                                            <span class="value">**** **** ****
                                                {{
                                                    selectedPayment.last_digits
                                                }}</span>
                                        </div>
                                    </div>

                                    <!-- Expiry -->
                                    <div class="col-6" v-if="selectedPayment?.exp_month">
                                        <div class="info-card">
                                            <span class="label">Expiry</span>
                                            <span class="value">{{
                                                selectedPayment.exp_month
                                            }}/{{
                                                    selectedPayment.exp_year
                                                }}</span>
                                        </div>
                                    </div>

                                    <!-- Currency -->
                                    <div class="col-6" v-if="selectedPayment?.currency_code">
                                        <div class="info-card">
                                            <span class="label">Currency</span>
                                            <span class="value">{{
                                                selectedPayment.currency_code
                                                }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order View Modal -->
            <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content rounded-4 shadow border-0">
                        <!-- Header -->
                        <div class="modal-header bg-white border-0 position-relative px-4 pt-4">
                            <div class="d-flex align-items-center gap-3">
                                <span class="badge bg-gradient rounded-circle p-3 shadow-sm" style="
                                        background: linear-gradient(
                                            135deg,
                                            #4e73df,
                                            #224abe
                                        );
                                    ">
                                    <i class="bi bi-receipt fs-5 text-black"></i>
                                </span>
                                <div class="d-flex flex-column">
                                    <h5 class="modal-title fw-bold mb-0">
                                        Order #{{ selectedOrder?.id }}
                                    </h5>
                                    <small class="text-muted">
                                        Customer:
                                        {{
                                            selectedOrder?.customer_name ?? "—"
                                        }}
                                    </small>
                                </div>
                            </div>

                            <!-- Custom Close Button -->
                            <button
                                class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                                data-bs-dismiss="modal" aria-label="Close" title="Close">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-danger" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="modal-body bg-light px-4 pb-4">
                            <div v-if="selectedOrder" class="rounded-3 bg-white shadow-sm p-4">
                                <!-- Order Info -->
                                <div class="mb-4">
                                    <h6 class="fw-bold mb-3 text-primary">
                                        Order Details
                                    </h6>
                                    <div class="row text-muted small">
                                        <div class="col-md-4 mb-2">
                                            <span class="fw-semibold text-dark">Order Type:</span>
                                            {{
                                                selectedOrder?.type
                                                    ?.order_type ?? "—"
                                            }}
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <span class="fw-semibold text-dark">Date:</span>
                                            {{
                                                dateFmt(
                                                    selectedOrder?.created_at
                                                )
                                            }}
                                        </div>
                                        <!-- <div class="col-md-4 mb-2">
                                                <span
                                                    class="fw-semibold text-dark"
                                                    >Status:
                                                </span>
                                                <span
                                                    class="badge rounded-pill"
                                                    :class="
                                                        selectedOrder?.status ===
                                                        'paid'
                                                            ? 'bg-success px-4 py-1 !text-xs uppercase'
                                                            : 'bg-warning text-dark'
                                                    "
                                                >
                                                    {{ selectedOrder?.status }}
                                                </span>
                                            </div> -->
                                    </div>
                                </div>

                                <!-- Order Items -->
                                <h6 class="fw-bold mb-3 text-primary">
                                    Order Items
                                </h6>
                                <div class="table-responsive">
                                    <table
                                        class="table align-middle table-striped table-hover border rounded-3 overflow-hidden shadow-sm">
                                        <thead class="bg-light text-muted small">
                                            <tr>
                                                <th class="px-3">#</th>
                                                <th>Item</th>
                                                <th>Qty</th>
                                                <th>Price</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(
item, idx
                                                ) in selectedOrder?.items ?? []" :key="item.id">
                                                <td class="px-3">
                                                    {{ idx + 1 }}
                                                </td>
                                                <td>{{ item.title }}</td>
                                                <td>{{ item.quantity }}</td>
                                                <td class="fw-semibold">
                                                    {{ formatCurrencySymbol(item.price) }}
                                                </td>
                                            </tr>
                                            <tr v-if="
                                                (selectedOrder?.items ?? [])
                                                    .length === 0
                                            ">
                                                <td colspan="4" class="text-center text-muted py-4">
                                                    No items found
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="modal-footer bg-white border-0 shadow-sm px-4 py-3">
                            <button class="btn btn-light border rounded-pill px-4 p-2" data-bs-dismiss="modal">
                                Close
                            </button>
                            <button class="btn btn-primary shadow-sm rounded-pill px-4 py-2"
                                @click="printReceipt(selectedOrder)">
                                <i class="bi bi-printer me-1"></i> Print Receipt
                            </button>
                        </div>
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

.dark .kpi-value {
    color: #fff !important;
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
    color: #181818;
}

.paid-text {
    font-size: 12px;
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

.dark svg {
    color: #fff !important;
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


.dark .logo-card {
    background-color: #181818 !important;
}

.dark .logo-frame {
    background-color: #181818 !important;
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
