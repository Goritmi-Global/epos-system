<template>
  <div class="modal fade" id="posOrdersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <!-- Modal Header -->
        <div class="modal-header">
          <h5 class="modal-title">Today's Orders</h5>
          <button class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
            data-bs-dismiss="modal" aria-label="Close" title="Close" @click="$emit('close')">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24"
              stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Modal Body -->
        <div class="modal-body">
          <!-- Loading state -->
          <div v-if="loading" class="d-flex flex-column justify-content-center align-items-center"
            style="height: 200px;">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
              <span class="visually-hidden">Loading...</span>
            </div>
            <div class="mt-3 fw-bold">Loading today orders...</div>
          </div>

          <!-- No orders found -->
          <div v-else-if="!orders || orders.length === 0"
            class="d-flex flex-column justify-content-center align-items-center" style="height: 200px;">
            <div class="fw-bold text-secondary">No orders found for today</div>
          </div>

          <!-- Orders Grid -->
          <div v-else class="row g-3">
            <div class="col-md-6 col-lg-4" v-for="order in orders" :key="order.id">
              <div class="card shadow-sm h-100 border rounded-3" style="border-color: #e5e7eb;">
                <div class="card-body">

                  <!-- Order Header -->
                  <!-- Order Header -->
                  <div class="d-flex justify-content-between align-items-start mb-3 border-bottom pb-2">
                    <div>
                      <h6 class="mb-0 fw-bold">Order #{{ order.id }}</h6>
                      <small class="text-muted">
                        {{ order.pos_order_type.order_type === 'Collection' ? 'Walk In' : order.customer_name }}
                      </small>
                    </div>

                    <!-- Right side: order type + status stacked -->
                    <div class="text-end">
                      <span class="badge bg-secondary rounded-pill d-block mb-1">
                        {{ order.pos_order_type.order_type }}
                      </span>
                      <span class="badge px-3 py-1 d-block rounded-pill" :class="{
                        'bg-success': order.pos_order_type.order.status?.toLowerCase() === 'paid',
                        'bg-warning text-dark': order.pos_order_type.order.status?.toLowerCase() === 'waiting',
                        'bg-danger': order.pos_order_type.order.status?.toLowerCase() === 'cancelled',
                        'bg-secondary': !order.pos_order_type.order.status
                      }">
                        {{ order.pos_order_type.order.status || 'Unknown' }}
                      </span>

                    </div>
                  </div>


                  <!-- Items Table -->
                  <div v-if="order.pos_order_type.order.items && order.pos_order_type.order.items.length > 0"
                    class="mb-3">
                    <table class="table table-sm align-middle mb-0">
                      <thead class="table-light">
                        <tr>
                          <th>Item</th>
                          <th class="text-center">Qty</th>
                          <th class="text-end">Unit Price</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="item in order.pos_order_type.order.items" :key="item.id">
                          <td>{{ item.title }}</td>
                          <td class="text-center">{{ item.quantity }}</td>
                          <td class="text-end">£{{ formatCurrency(item.price) }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <!-- Payment Info -->
                  <div class="bg-light rounded p-2 mb-3">
                    <div class="d-flex justify-content-between">
                      <span class="fw-bold">Total Price:</span>
                      <span>£{{ formatCurrency(order.pos_order_type.order.payment.amount_received) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                      <span class="fw-bold">Payment Type:</span>
                      <span>{{ order.pos_order_type.order.payment.payment_type }}</span>
                    </div>
                    <div class="text-end text-muted small">Order Time: {{ order.order_time }}</div>
                  </div>

                  <!-- Actions -->
                  <!-- <div class="d-flex justify-content-center" v-if="printers.length > 0">
                    <button class="btn btn-sm btn-primary" @click="printOrder(order)">
                      <Printer class="w-5 h-5" />
                    </button>
                  </div> -->

                  <div class="d-flex justify-content-center">
                    <button class="btn btn-sm btn-primary" @click="printOrder(order)">
                      <Printer class="w-5 h-5" />
                    </button>
                  </div>

                </div>
              </div>
            </div>
          </div>

          <!-- End Orders Grid -->
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits, watch, onMounted } from "vue";
import { Eye, Printer } from "lucide-vue-next";
import { ref } from "vue";

// Props
const props = defineProps({
  show: Boolean,
  orders: Array,
  loading: Boolean
});

const emit = defineEmits(["close", "view-details"]);

// Helper: format currency
const formatCurrency = (amount) => {
  return parseFloat(amount).toFixed(2);
};


// // Get All Connected Printers
// const printers = ref([]);
// const loadingPrinters = ref(false);

// const fetchPrinters = async () => {
//   loadingPrinters.value = true;
//   try {
//     const res = await axios.get("/api/printers");
//     console.log("Printers:", res.data.data);

//     // ✅ Only show connected printers (status OK)
//     printers.value = res.data.data
//       .filter(p => p.is_connected === true || p.status === "OK")
//       .map(p => ({
//         label: `${p.name}`,
//         value: p.name,
//         driver: p.driver,
//         port: p.port,
//       }));
//   } catch (err) {
//     console.error("Failed to fetch printers:", err);
//   } finally {
//     loadingPrinters.value = false;
//   }
// };

// // 🔹 Fetch once on mount
// onMounted(fetchPrinters);
// Print function
function printOrder(order) {
  // Make a safe copy
  const plainOrder = JSON.parse(JSON.stringify(order));

  // Payment line
  const type = (plainOrder.pos_order_type?.order?.payment?.payment_type || "").toLowerCase();
  let payLine = "";
  if (type === "split") {
    payLine = `Payment Type: Split 
      (Cash: £${Number(plainOrder.pos_order_type.order.payment.cash_amount ?? 0).toFixed(2)}, 
       Card: £${Number(plainOrder.pos_order_type.order.payment.card_amount ?? 0).toFixed(2)})`;
  } else if (type === "card" || type === "stripe") {
    payLine = `Payment Type: Card`;
  } else {
    payLine = `Payment Type: ${plainOrder.pos_order_type.order.payment.payment_type || "Cash"}`;
  }

  // Build HTML
  const html = `
    <html>
    <head>
      <title>Today Order Receipt</title>
      <style>
        @page { size: 80mm auto; margin: 0; }
        body {
          width: 80mm;
          margin: 0;
          padding: 8px;
          font-family: monospace, Arial, sans-serif;
          font-size: 12px;
          line-height: 1.4;
        }
        .header { text-align: center; margin-bottom: 10px; }
        .order-info { margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 4px 0; text-align: left; }
        td:last-child, th:last-child { text-align: right; }
        .totals { margin-top: 10px; border-top: 1px dashed #000; padding-top: 8px; }
        .footer { text-align: center; margin-top: 10px; font-size: 11px; }
      </style>
    </head>
    <body>
      <div class="header">
        <h2>ORDER RECEIPT</h2>
      </div>
      
      <div class="order-info">
        <div><strong>Order #:</strong> ${plainOrder.id}</div>
        <div><strong>Date:</strong> ${plainOrder.order_date || ""}</div>
        <div><strong>Time:</strong> ${plainOrder.order_time || ""}</div>
        <div><strong>Customer:</strong> ${plainOrder.customer_name || "Walk In"}</div>
        <div><strong>Order Type:</strong> ${plainOrder.pos_order_type?.order_type || ""}</div>
        <div>${payLine}</div>
      </div>

      <table>
        <thead>
          <tr>
            <th>Item</th>
            <th>Qty</th>
            <th>Price</th>
          </tr>
        </thead>
        <tbody>
          ${(plainOrder.pos_order_type?.order?.items || [])
      .map((item) => {
        const qty = Number(item.quantity) || 0;
        const price = Number(item.price) || 0;
        const total = qty * price;
        return `
                <tr>
                  <td>${item.title || "Unknown Item"}</td>
                  <td>${qty}</td>
                  <td>£${total.toFixed(2)}</td>
                </tr>
              `;
      })
      .join("")}
        </tbody>
      </table>

      <div class="totals">
        <div><strong>Total:</strong> £${Number(plainOrder.pos_order_type?.order?.payment?.amount_received ?? 0).toFixed(2)}</div>
        
      </div>

      <div class="footer">
        Customer Copy - Thank you!
      </div>
    </body>
    </html>
  `;

  // Open popup & print
  const w = window.open("", "_blank", "width=400,height=600");
  if (!w) {
    alert("Please allow popups for this site to print");
    return;
  }
  w.document.open();
  w.document.write(html);
  w.document.close();
  w.onload = () => {
    w.print();
    w.close();
  };
}

// Modal show/hide
watch(() => props.show, (newVal) => {
  const el = document.getElementById('posOrdersModal');
  if (!el) return;
  const modal = new bootstrap.Modal(el, { backdrop: 'static', keyboard: false });
  newVal ? modal.show() : modal.hide();
});
</script>


<style scoped>
/* Card styling */
.card {
  border-radius: 12px;
}

/* Badge styling for status and order types */
.badge {
  font-size: 0.8rem;
}

.dark .bg-light {
  background-color: #212121 !important;
  color: #fff !important;
}

/* Button spacing */
.btn {
  font-size: 0.8rem;
}
</style>
