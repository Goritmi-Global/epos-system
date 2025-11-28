<script setup>
import Master from "@/Layouts/Master.vue";
import { Head } from "@inertiajs/vue3";
import { ref, computed, onMounted } from "vue";
import Select from "primevue/select";
import { Pencil, Eye, XCircle } from "lucide-vue-next";
import { useFormatters } from '@/composables/useFormatters'
import FilterModal from "@/Components/FilterModal.vue";
import { nextTick } from "vue";
import { toast } from 'vue3-toastify';
import OrderCancellationModal from "@/Components/OrderCancellationModal.vue";

const { formatMoney, formatCurrencySymbol, formatNumber, dateFmt } = useFormatters()
const cancellingOrderId = ref(null);
const refundingOrderId = ref(null);
const refundAmount = ref(0);
const refundReason = ref('');
const selectedOrderForRefund = ref(null);
const orders = ref([]);

const fetchOrders = async () => {
    try {
        const response = await axios.get("/api/orders/all");
        orders.value = response.data.data;
    } catch (error) {
        console.error("Error fetching inventory:", error);
    }
};
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
    fetchOrders();
});

const q = ref("");
const searchKey = ref(Date.now());
const inputId = `search-${Math.random().toString(36).substr(2, 9)}`;
const isReady = ref(false);
const filters = ref({
    sortBy: "",
    orderType: "",
    paymentType: "",
    status: "",
    priceMin: null,
    priceMax: null,
    dateFrom: "",
    dateTo: "",
});

const orderTypeOptions = ref(["All", "Dine In", "Delivery", "Takeaway"]);
const paymentTypeOptions = ref(["All", "Cash", "Card", "Split"]); 

const filtered = computed(() => {
    const term = q.value.trim().toLowerCase();
    let result = [...orders.value];
    if (term) {
        result = result.filter((o) =>
            [
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
                .includes(term)
        );
    }
    if (filters.value.orderType) {
        result = result.filter(
            (o) =>
                (o.type?.order_type ?? "").toLowerCase() ===
                filters.value.orderType.toLowerCase()
        );
    }
    if (filters.value.paymentType) {
        result = result.filter(
            (o) =>
                (o.payment?.payment_type ?? "").toLowerCase() ===
                filters.value.paymentType.toLowerCase()
        );
    }
    if (filters.value.status) {
        result = result.filter((o) => {
            return o.status?.toLowerCase() === filters.value.status.toLowerCase();
        });
    }
    if (filters.value.priceMin !== null || filters.value.priceMax !== null) {
        result = result.filter((o) => {
            const price = o.total_amount || 0;
            const min = filters.value.priceMin || 0;
            const max = filters.value.priceMax || Infinity;
            return price >= min && price <= max;
        });
    }
    if (filters.value.dateFrom) {
        result = result.filter((o) => {
            const orderDate = new Date(o.created_at);
            const filterDate = new Date(filters.value.dateFrom);
            return orderDate >= filterDate;
        });
    }

    if (filters.value.dateTo) {
        result = result.filter((o) => {
            const orderDate = new Date(o.created_at);
            const filterDate = new Date(filters.value.dateTo);
            filterDate.setHours(23, 59, 59, 999); // End of day
            return orderDate <= filterDate;
        });
    }

    return result;
});

const sortedOrders = computed(() => {
    const arr = [...filtered.value];
    const sortBy = filters.value.sortBy;

    switch (sortBy) {
        case "date_desc":
            return arr.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
        case "date_asc":
            return arr.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
        case "price_desc":
            return arr.sort((a, b) => (b.total_amount || 0) - (a.total_amount || 0));
        case "price_asc":
            return arr.sort((a, b) => (a.total_amount || 0) - (b.total_amount || 0));
        case "customer_asc":
            return arr.sort((a, b) =>
                (a.customer_name || "").localeCompare(b.customer_name || "")
            );
        case "customer_desc":
            return arr.sort((a, b) =>
                (b.customer_name || "").localeCompare(a.customer_name || "")
            );
        default:
            return arr.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    }
});

const filterOptions = computed(() => ({
    sortOptions: [
        { value: "date_desc", label: "Date: Newest First" },
        { value: "date_asc", label: "Date: Oldest First" },
        { value: "price_desc", label: "Price: High to Low" },
        { value: "price_asc", label: "Price: Low to High" },
        { value: "customer_asc", label: "Customer: A to Z" },
        { value: "customer_desc", label: "Customer: Z to A" },
    ],
    orderTypeOptions: [
        { value: "Dine In", label: "Dine In" },
        { value: "Delivery", label: "Delivery" },
        { value: "Takeaway", label: "Takeaway" },
        { value: "Collection", label: "Collection" },
    ],
    paymentTypeOptions: [
        { value: "Cash", label: "Cash" },
        { value: "Card", label: "Card" },
        { value: "Split", label: "Split" },
    ],
    statusOptions: [
        { value: "paid", label: "Paid" },
        { value: "pending", label: "Pending" },
        { value: "cancelled", label: "Cancelled" },
    ],
}));

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


// Get All Connected Printers
const printers = ref([]);
const loadingPrinters = ref(false);

const fetchPrinters = async () => {
    loadingPrinters.value = true;
    try {
        const res = await axios.get("/api/printers");
        printers.value = res.data.data
            .filter(p => p.is_connected === true || p.status === "OK")
            .map(p => ({
                label: `${p.name}`,
                value: p.name,
                driver: p.driver,
                port: p.port,
            }));
    } catch (err) {
        console.error("Failed to fetch printers:", err);
    } finally {
        loadingPrinters.value = false;
    }
};

// 🔹 Fetch once on mount
onMounted(fetchPrinters);

const canRefund = (order) => {
    if (!order || !order.payment) return false;

    const paymentType = order.payment.payment_type?.toLowerCase();
    const status = order.status?.toLowerCase();
    const refundStatus = order.payment.refund_status?.toLowerCase();
    return (
        (status === 'paid' || status === 'cancelled') &&
        (paymentType === 'card' || paymentType === 'split') &&
        (!refundStatus || refundStatus === 'none')
    );
};
const handleRefundPayment = async (order) => {
    if (!order) return;

    const paymentType = order.payment?.payment_type?.toLowerCase();
    const totalAmount = order.total_amount;
    const cardAmount = order.payment?.card_amount || totalAmount;
    selectedOrderForRefund.value = order;
    refundAmount.value = Number(cardAmount).toFixed(2);
    refundReason.value = '';
    const modalEl = document.getElementById('refundModal');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
};
const processRefund = async () => {
    if (!selectedOrderForRefund.value) return;

    const amount = Number(refundAmount.value);

    if (amount <= 0) {
        toast.error('Refund amount must be greater than 0');
        return;
    }

    const paymentType = selectedOrderForRefund.value.payment?.payment_type?.toLowerCase();
    const maxAmount = selectedOrderForRefund.value.payment?.card_amount || selectedOrderForRefund.value.total_amount;

    if (amount > maxAmount) {
        toast.error(`Refund amount cannot exceed ${formatCurrencySymbol(maxAmount)}`);
        return;
    }

    refundingOrderId.value = selectedOrderForRefund.value.id;

    try {
        const response = await axios.post(
            `/api/pos/orders/${selectedOrderForRefund.value.id}/refund`,
            {
                amount: amount,
                reason: refundReason.value || 'Refund requested by admin',
                payment_type: paymentType
            }
        );

        if (response.data.success) {
            toast.success(`Refund of ${formatCurrencySymbol(amount)} processed successfully`);

            // Update local order
            const index = orders.value.findIndex(o => o.id === selectedOrderForRefund.value.id);
            if (index !== -1) {
                orders.value[index] = response.data.order;
            }

            // Close modal
            const modalEl = document.getElementById('refundModal');
            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }

            // Clear selection
            selectedOrderForRefund.value = null;
            refundAmount.value = 0;
            refundReason.value = '';
        }
    } catch (error) {
        console.error('Error processing refund:', error);
        toast.error(error.response?.data?.message || 'Failed to process refund');
    } finally {
        refundingOrderId.value = null;
    }
};

// Close refund modal
const closeRefundModal = () => {
    const modalEl = document.getElementById('refundModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    }
    selectedOrderForRefund.value = null;
    refundAmount.value = 0;
    refundReason.value = '';
};



const showCancelModal = ref(false);
const selectedOrderForCancel = ref(null);

// REPLACE the handleCancelOrder function with this:
const handleCancelOrder = (order) => {
    if (!order) return;
    selectedOrderForCancel.value = order;
    showCancelModal.value = true;
};

// Add this new function to close the modal
const closeCancelModal = () => {
    showCancelModal.value = false;
    selectedOrderForCancel.value = null;
};

// Add this new function to confirm cancellation
const confirmCancelOrder = async (reason) => {
    if (!selectedOrderForCancel.value) return;

    cancellingOrderId.value = selectedOrderForCancel.value.id;

    try {
        const response = await axios.post(`/api/pos/orders/${selectedOrderForCancel.value.id}/cancel`, {
            reason: reason
        });

        if (response.data.success) {
            toast.success('Order cancelled and stock restored successfully');

            // Update local order
            const index = orders.value.findIndex(o => o.id === selectedOrderForCancel.value.id);
            if (index !== -1) {
                orders.value[index] = response.data.order;
            }

            // Close the cancel modal
            closeCancelModal();

            // If payment can be refunded, ask user
            if (canRefund(response.data.order)) {
                setTimeout(() => {
                    if (confirm('Order cancelled successfully!\n\nWould you like to refund the payment as well?')) {
                        handleRefundPayment(response.data.order);
                    }
                }, 500);
            }
        }
    } catch (error) {
        console.error('Error cancelling order:', error);
        toast.error(error.response?.data?.message || 'Failed to cancel order');
    } finally {
        cancellingOrderId.value = null;
    }
};

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
                                <input type="email" name="email" autocomplete="email"
                                    style="position: absolute; left: -9999px; width: 1px; height: 1px;" tabindex="-1"
                                    aria-hidden="true" />

                                <input v-if="isReady" :id="inputId" v-model="q" :key="searchKey"
                                    class="form-control search-input" placeholder="Search" type="search"
                                    autocomplete="new-password" :name="inputId" role="presentation"
                                    @focus="handleFocus" />
                                <input v-else class="form-control search-input" placeholder="Search" disabled
                                    type="text" />
                            </div>

                            <FilterModal v-model="filters" title="Orders" modal-id="orderFilterModal"
                                modal-size="modal-lg" :sort-options="filterOptions.sortOptions"
                                :status-options="filterOptions.statusOptions" :show-price-range="true"
                                :show-date-range="true" price-label="Total Amount Range" @apply="handleFilterApply"
                                @clear="handleFilterClear">
                                <!-- Custom filters slot for Order Type and Payment Type -->
                                <template #customFilters="{ filters }">
                                    <div class="col-md-6">
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

                                    <div class="col-md-6 mt-3">
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
                            <!-- Download -->
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary rounded-pill px-4 dropdown-toggle"
                                    data-bs-toggle="dropdown">
                                    Export
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow rounded-4 py-2">
                                    <li>
                                        <a class="dropdown-item py-2" href="javascript:void(0)">Export as PDF</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2" href="javascript:void(0)">Export as Excel</a>
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
                                    <th>Actual Price</th>
                                    <th>Promo Discount</th>
                                    <th>Sales Discount</th>
                                    <th>Approved Discount</th>
                                    <th>Tax</th>
                                    <th>Service Charges</th>
                                    <th>Delivery Charges</th>
                                    <th>Promo Name</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(o, i) in sortedOrders" :key="o.id">
                                    <!-- {{ o }} -->
                                    <td>{{ i + 1 }}</td>
                                    <td>{{ o.type?.table_number ?? "-" }}</td>
                                    <td>{{ o.type?.order_type ?? "-" }}</td>
                                    <td>{{ dateFmt(o.created_at) }}</td>
                                    <td>{{ timeAgo(o.created_at) }}</td>
                                    <td>{{ o.customer_name ?? "-" }}</td>
                                    <td>
                                        <span class="cursor-pointer" @click="openPaymentModal(o.payment)">
                                            {{ o.payment?.payment_type ?? "-" }}
                                        </span>
                                    </td>

                                    <!-- Actual price before promo -->
                                    <td>{{ formatCurrencySymbol(o.sub_total ?? 0) }}</td>

                                    <!-- Promo discount -->
                                    <td class="text-success">
                                        -{{ formatCurrencySymbol(o?.promo?.[0]?.discount_amount ?? 0) }}
                                    </td>
                                    <td class="text-success">
                                        -{{ formatCurrencySymbol(o.sales_discount ?? 0) }}
                                    </td>
                                    <td class="text-success">
                                        -{{ formatCurrencySymbol(o?.approved_discounts ?? 0) }}
                                    </td>
                                    <td>{{ o.tax ?? "-" }}</td>
                                    <td>{{ o.service_charges ?? "-" }}</td>
                                    <td>{{ o.delivery_charges ?? "-" }}</td>
                                    <td>
                                        {{ o.promo?.[0]?.promo_name || '-' }}
                                    </td>


                                    <!-- Total after discount -->
                                    <td>{{ formatCurrencySymbol(o.total_amount) }}</td>
                                    <td>
                                        <span class="badge px-2 py-2 rounded-pill" :class="{
                                            'bg-danger': o?.status === 'cancelled',
                                            'bg-warning text-dark': o?.status === 'refunded',
                                            'bg-success': o?.status === 'paid'
                                        }">
                                             {{ o?.status.charAt(0).toUpperCase() + o?.status.slice(1) }}
                                        </span>
                                    </td>


                                    <!-- In your orders table, replace the actions cell -->
                                    <td class="text-left">
                                        <div class="d-flex gap-2 justify-content-center align-items-center">
                                            <!-- View Button -->
                                            <button @click="openOrderDetails(o)" title="View Details"
                                                class="p-2 rounded-full text-primary hover:bg-gray-100">
                                                <Eye class="w-4 h-4" />
                                            </button>

                                            <!-- Cancel Button (only if not already cancelled/refunded) -->
                                            <button v-if="o.status !== 'cancelled' && o.status !== 'refunded'"
                                                @click="handleCancelOrder(o)" title="Cancel Order"
                                                :disabled="cancellingOrderId === o.id"
                                                class="p-2 rounded-full text-danger hover:bg-gray-100">
                                                <XCircle class="w-4 h-4" />
                                            </button>

                                            <!-- Refund Button (only for card/split payments that aren't refunded) -->
                                            <button v-if="canRefund(o)" @click="handleRefundPayment(o)"
                                                title="Refund Payment" :disabled="refundingOrderId === o.id"
                                                class="p-2 rounded-full text-warning hover:bg-gray-100">
                                                <i class="bi bi-arrow-counterclockwise" style="font-size: 1rem;"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="filtered.length === 0">
                                    <td colspan="18" class="text-center text-muted py-4">
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
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
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
                                                <th>Actual Price</th>
                                                <th>=</th>
                                                <th>Promo Name</th>
                                                <th>Total Price</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Order Items -->
                                            <tr v-for="(item, idx) in selectedOrder.items ?? []" :key="item.id">
                                                <td class="px-3">{{ idx + 1 }}</td>
                                                <td>{{ item.title }}</td>
                                                <td>{{ item.quantity }}</td>
                                                <td class="fw-semibold">{{ formatCurrencySymbol(item.price) }}</td>
                                                <td></td>
                                                <td>{{ selectedOrder.promo[0]?.promo_name || '-' }}</td>
                                                <td class="fw-bold text-success">
                                                    {{ formatCurrencySymbol(item.price) }}
                                                </td>
                                            </tr>

                                            <!-- Promo Discount Row -->
                                            <tr v-if="selectedOrder.promo" class="no-border">
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td colspan="3" class="text-end text-muted text-adjusting">Promo
                                                    Discount</td>
                                                <td class="text-success fw-semibold" colspan="3">
                                                    -{{ formatCurrencySymbol(selectedOrder.promo[0]?.discount_amount ??
                                                        0) }}
                                                </td>
                                            </tr>

                                            <!-- Tax Row -->
                                            <tr v-if="selectedOrder.tax" class="no-border">
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td colspan="3" class="text-end text-muted text-adjusting">Tax</td>
                                                <td class="text-success fw-semibold" colspan="3">
                                                    +{{ formatCurrencySymbol(selectedOrder.tax ?? 0) }}
                                                </td>
                                            </tr>

                                            <!-- Service Charges Row -->
                                            <tr v-if="selectedOrder.service_charges" class="no-border">
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td colspan="3" class="text-end text-muted text-adjusting">Service
                                                    Charges</td>
                                                <td class="text-success fw-semibold" colspan="3">
                                                    +{{ formatCurrencySymbol(selectedOrder.service_charges ?? 0) }}
                                                </td>
                                            </tr>

                                            <!-- Delivery Charges Row -->
                                            <tr v-if="selectedOrder.delivery_charges" class="no-border">
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td colspan="3" class="text-end text-muted text-adjusting">Delivery
                                                    Charges</td>
                                                <td class="text-success fw-semibold" colspan="3">
                                                    +{{ formatCurrencySymbol(selectedOrder.delivery_charges ?? 0) }}
                                                </td>
                                            </tr>
                                            <!-- Sales Discount Row -->
                                            <tr v-if="selectedOrder.sales_discount" class="no-border">
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td colspan="3" class="text-end text-muted text-adjusting">Sales
                                                    Discount</td>
                                                <td class="text-success fw-semibold" colspan="3">
                                                    +{{ formatCurrencySymbol(selectedOrder.sales_discount ?? 0) }}
                                                </td>
                                            </tr>

                                            <!-- Approved Discounts Row -->
                                            <tr v-if="selectedOrder.approved_discounts" class="no-border">
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td colspan="3" class="text-end text-muted text-adjusting">Approved
                                                    Discounts</td>
                                                <td class="text-success fw-semibold" colspan="3">
                                                    +{{ formatCurrencySymbol(selectedOrder.approved_discounts ?? 0) }}
                                                </td>
                                            </tr>

                                            <!-- Grand Total Row -->
                                            <tr class="border-top-thick">
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td colspan="3" class="text-end text-muted text-adjusting">Grand Total
                                                </td>
                                                <td class="fw-bold text-success">
                                                    {{ formatCurrencySymbol(selectedOrder.total_amount) }}
                                                </td>
                                            </tr>

                                            <!-- No items fallback -->
                                            <tr v-if="(selectedOrder.items ?? []).length === 0">
                                                <td colspan="7" class="text-center text-muted py-4">
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
                            <button class="btn btn-secondary px-2 py-2" data-bs-dismiss="modal">
                                Close
                            </button>
                            <button v-if="printers.length > 0" class="btn btn-primary shadow-sm rounded-pill px-4 py-2"
                                @click="printReceipt(selectedOrder)">
                                <i class="bi bi-printer me-1"></i> Print Receipt
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Refund Modal -->
        <div class="modal fade" id="refundModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 shadow border-0">
                    <!-- Header -->
                    <div class="modal-header border-0">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-arrow-counterclockwise text-warning me-2"></i>
                            Process Refund
                        </h5>
                        <button @click="closeRefundModal"
                            class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                            data-bs-dismiss="modal" aria-label="Close" title="Close">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body">
                        <div class="alert alert-warning d-flex align-items-start gap-2">
                            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                            <div>
                                <strong>Important:</strong> This will process a refund to the customer's card. This
                                action
                                cannot be undone.
                            </div>
                        </div>

                        <!-- Order Details -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Order Details</label>
                            <div class="bg-light p-3 rounded-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Order #:</span>
                                    <span class="fw-semibold">{{ selectedOrderForRefund?.id }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Customer:</span>
                                    <span class="fw-semibold">{{ selectedOrderForRefund?.customer_name || 'Walk In'
                                        }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Total Amount:</span>
                                    <span class="fw-semibold">{{
                                        formatCurrencySymbol(selectedOrderForRefund?.total_amount)
                                        }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Payment Type:</span>
                                    <span class="fw-semibold text-capitalize">{{
                                        selectedOrderForRefund?.payment?.payment_type
                                        }}</span>
                                </div>
                                <div v-if="selectedOrderForRefund?.payment?.payment_type?.toLowerCase() === 'split'"
                                    class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Card Amount:</span>
                                    <span class="fw-semibold text-success">{{
                                        formatCurrencySymbol(selectedOrderForRefund?.payment?.card_amount) }}</span>
                                </div>
                                <div v-if="selectedOrderForRefund?.payment?.brand"
                                    class="d-flex justify-content-between">
                                    <span class="text-muted">Card:</span>
                                    <span class="fw-semibold">
                                        {{ selectedOrderForRefund.payment.brand }} •••• {{
                                            selectedOrderForRefund.payment.last_digits }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Refund Amount -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Refund Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">£</span>
                                <input v-model.number="refundAmount" type="number" class="form-control"
                                    :max="selectedOrderForRefund?.payment?.card_amount || selectedOrderForRefund?.total_amount"
                                    step="0.01" min="0.01" required />
                            </div>
                            <small class="text-muted">
                                Maximum: {{ formatCurrencySymbol(selectedOrderForRefund?.payment?.card_amount ||
                                    selectedOrderForRefund?.total_amount) }}
                            </small>
                        </div>

                        <!-- Refund Reason -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Refund Reason (Optional)</label>
                            <textarea v-model="refundReason" class="form-control" rows="3"
                                placeholder="Enter reason for refund..." maxlength="500"></textarea>
                            <small class="text-muted">{{ refundReason.length }}/500 characters</small>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary px-2 py-2" @click="closeRefundModal"
                            :disabled="refundingOrderId !== null">
                            Cancel
                        </button>
                        <button type="button" class="btn btn-primary px-2 py-2" @click="processRefund"
                            :disabled="refundingOrderId !== null || !refundAmount || refundAmount <= 0">
                            <span v-if="refundingOrderId">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Processing...
                            </span>
                            <span v-else>
                                <i class="bi bi-arrow-counterclockwise me-1"></i>
                                Process Refund
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>


        <OrderCancellationModal
        :show="showCancelModal"
        :order="selectedOrderForCancel"
        :loading="cancellingOrderId === selectedOrderForCancel?.id"
        @confirm="confirmCancelOrder"
        @cancel="closeCancelModal"
    />
    </Master>
</template>

<style scoped>
.dark h4 {
    color: white;
}

.dark .bg-light,
.input-group-text {
    background-color: #212121 !important;
    color: #fff !important;
}

.no-border td {
    border-top: none !important;
    border-bottom: none !important;
    text-align: left;
}

.text-adjusting {
    text-align: left !important;
}

.border-top-thick td {
    border-top: 2px solid #dee2e6 !important;
}

tbody tr:not(.no-border):not(.border-top-thick):nth-child(odd) {
    background-color: rgba(0, 0, 0, 0.02);
}

.dark .card {
    background-color: #181818 !important;
    color: #ffffff !important;
}

.dark .table {
    background-color: #181818 !important;
    color: #f9fafb !important;
}

.dark .table thead {
    background-color: #181818 !important;
    color: #ffffff;
}

.dark .info-card {
    background-color: #212121 !important;
    color: #fff !important;
}

.dark .table thead th {
    background-color: #181818 !important;
    color: #ffffff;
}

:root {
    --brand: #1c0d82;
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
    color: #181818;
}

.dark .kpi-value {
    color: #fff !important;
}
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
.btn-primary {
    background-color: var(--brand);
    border-color: var(--brand);
}


.btn-primary:hover {
    filter: brightness(1.05);
}

.dark .form-label {
    color: #fff !important;
}
.table thead th {
    font-weight: 600;
    border: 0 !important;
}

.table tbody td {
    vertical-align: middle;
    border-color: #eee;
}
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
:deep(.p-multiselect-panel),
:deep(.p-select-panel),
:deep(.p-dropdown-panel) {
    z-index: 2000 !important;
}
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
:deep(.p-multiselect-list) {
    background: #fff !important;
}
:deep(.p-multiselect-option) {
    background: #fff !important;
    color: #000 !important;
}
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
:deep(.p-multiselect-overlay .p-checkbox-box) {
    background: #fff !important;
    border: 1px solid #ccc !important;
    color: #fff !important;
}
:deep(.p-multiselect-filter) {
    background: #fff !important;
    color: #000 !important;
    border: 1px solid #ccc !important;
}
:deep(.p-multiselect-filter-container) {
    background: #fff !important;
}
:deep(.p-multiselect-chip) {
    background: #e9ecef !important;
    color: #000 !important;
    border-radius: 12px !important;
    border: 1px solid #ccc !important;
    padding: 0.25rem 0.5rem !important;
}
:deep(.p-multiselect-chip .p-chip-remove-icon) {
    color: #555 !important;
}

:deep(.p-multiselect-chip .p-chip-remove-icon:hover) {
    color: #dc3545 !important;
}
:deep(.p-multiselect-panel),
:deep(.p-select-panel),
:deep(.p-dropdown-panel) {
    z-index: 2000 !important;
}
:deep(.p-select) {
    background-color: white !important;
    color: black !important;
    border-color: #9b9c9c;
}
:deep(.p-select-list-container) {
    background-color: white !important;
    color: black !important;
}
:deep(.p-select-option) {
    background-color: transparent !important;
    color: black !important;
}
:deep(.p-select-option:hover) {
    background-color: #f0f0f0 !important;
    color: black !important;
}
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
:global(.dark .p-multiselect-list) {
    background: #181818 !important;
}
:global(.dark .p-multiselect-option) {
    background: #181818 !important;
    color: #fff !important;
}
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
:global(.dark .p-multiselect-overlay .p-checkbox-box) {
    background: #181818 !important;
    border: 1px solid #555 !important;
}
:global(.dark .p-multiselect-filter) {
    background: #181818 !important;
    color: #fff !important;
    border: 1px solid #555 !important;
}
:global(.dark .p-multiselect-filter-container) {
    background: #181818 !important;
}
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
:global(.dark .p-multiselect-chip .p-chip-remove-icon) {
    color: #fff !important;
}

:global(.dark .p-multiselect-chip .p-chip-remove-icon:hover) {
    color: #f87171 !important;
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

.dark .form-select {
    background-color: #212121 !important;
    color: #fff !important;
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
