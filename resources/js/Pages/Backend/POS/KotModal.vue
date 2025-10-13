<template>
    <div class="modal fade" id="kotModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0">

                <!-- Header -->
                <div class="modal-header position-relative text-black d-flex align-items-center">
                    <!-- Left: Title + KOT count -->
                    <h5 class="modal-title mb-0 d-flex align-items-center">
                        Kitchen Order Ticket
                        <span class="badge bg-primary rounded-pill ms-2 px-2 py-1">{{ kotCount }}</span>
                    </h5>

                    <!-- Centered Search input -->
                    <div class="position-absolute start-50 translate-middle-x" style="width: 250px;">
                        <i class="bi bi-search position-absolute top-50 translate-middle-y ms-3"></i>
                        <input v-model="searchQuery" type="text" class="form-control rounded-pill ps-5"
                            placeholder="Search items..." style="width: 100%; height: 38px;" />
                    </div>

                    <!-- Right: Close button -->
                    <button class="ms-auto p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                        data-bs-dismiss="modal" aria-label="Close" title="Close" @click="$emit('close')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <!-- Body -->
                <div class="modal-body">
                    <!-- Loader -->
                    <div v-if="loading" class="d-flex flex-column justify-content-center align-items-center"
                        style="height: 200px;">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div class="mt-3 fw-bold">Loading today KOT orders...</div>
                    </div>

                    <!-- No orders found -->
                    <div v-else-if="!kot || kot.length === 0"
                        class="d-flex flex-column justify-content-center align-items-center" style="height: 200px;">
                        <div class="fw-bold text-secondary">No orders found for today</div>
                    </div>

                    <!-- Orders table -->
                    <div v-else class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Order ID</th>
                                    <th>Item Name</th>
                                    <th>Variant</th>
                                    <th>Ingredients</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in filteredItems" :key="item.uniqueId || index">
                                    <td>{{ index + 1 }}</td>
                                    <td>{{ item.orderIndex + 1 }}</td>
                                    <td>{{ item.item_name }}</td>
                                    <td>{{ item.variant_name || '-' }}</td>
                                    <td>{{ item.ingredients?.join(', ') || '-' }}</td>
                                    <td>
                                        <span :class="['badge', 'rounded-pill', getStatusBadge(item.status)]"
                                            style="width: 70px; display: inline-flex; justify-content: center; align-items: center; height: 25px;">
                                            {{ item.status }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center align-items-center gap-2">
                                            <button @click="updateKotStatus(item.order, 'Waiting')" title="Waiting"
                                                class="p-2 rounded-full text-warning hover:bg-gray-100">
                                                <Clock class="w-5 h-5" />
                                            </button>
                                            <button @click="updateKotStatus(item.order, 'Done')" title="Done"
                                                class="p-2 rounded-full text-success hover:bg-gray-100">
                                                <CheckCircle class="w-5 h-5" />
                                            </button>
                                            <button @click="updateKotStatus(item.order, 'Cancelled')" title="Cancelled"
                                                class="p-2 rounded-full text-danger hover:bg-gray-100">
                                                <XCircle class="w-5 h-5" />
                                            </button>
                                            <!-- ✅ Only show if printers are available -->
                                            <button v-if="printers.length > 0"
                                                class="p-2 rounded-full text-gray-600 hover:bg-gray-100"
                                                @click.prevent="printOrder(item.order)" title="Print">
                                                <Printer class="w-5 h-5" />
                                            </button>

                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>


                <!-- Footer -->
                <div class="modal-footer">
                    <button class="btn btn-primary rounded-pill py-2" @click="$emit('close')">Close</button>
                </div>

            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import axios from 'axios';
import { toast } from 'vue3-toastify';
import { Clock, CheckCircle, XCircle, Printer } from "lucide-vue-next";


//  Props
const props = defineProps({
    show: Boolean,
    kot: Array,
    loading: Boolean
});

const kotCount = computed(() => props.kot?.length || 0);
const emit = defineEmits(['close', 'status-updated']);


// Flatten all items into one array
// In your KOT modal component
const allItems = computed(() => {

    if (!props.kot || props.kot.length === 0) {
        return [];
    }

    const flattened = props.kot.flatMap((order, orderIndex) => {

        return order.items?.map((item, itemIndex) => ({
            ...item,
            status: order.status,
            orderIndex,
            order,
            uniqueId: `${order.id}-${itemIndex}`
        })) || [];
    });

    return flattened;
});


const searchQuery = ref('');

// Filtered items based on search
const filteredItems = computed(() => {
    if (!searchQuery.value) return allItems.value;

    const query = searchQuery.value.toLowerCase();
    return allItems.value.filter(item =>
        item.item_name.toLowerCase().includes(query) ||
        (item.variant_name?.toLowerCase().includes(query)) ||
        (item.ingredients?.join(', ').toLowerCase().includes(query)) ||
        item.status.toLowerCase().includes(query)
    );
});


const updateKotStatus = async (order, status) => {
    try {
        console.log(`Updating KOT status: Order ID ${order.id} -> ${status}`);
        const response = await axios.put(`/api/pos/kot/${order.id}/status`, { status });
        emit('status-updated', { id: order.id, status: response.data.status, message: response.data.message });
    } catch (err) {
        console.error("Failed to update status:", err);
        toast.error(err.response?.data?.message || 'Failed to update status');
    }
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'Done':
            return 'bg-success';       // green badge
        case 'Cancelled':
            return 'bg-danger';        // red badge
        case 'Waiting':
            return 'bg-warning text-dark'; // yellow badge with dark text
        default:
            return 'bg-secondary';     // gray badge for unknown
    }
};

let modalInstance = null;

onMounted(() => {
    const el = document.getElementById("kotModal");
    modalInstance = new bootstrap.Modal(el, {
        backdrop: "static",
        keyboard: false
    });
});

watch(
    () => props.show,
    (newVal) => {
        if (!modalInstance) return;
        if (newVal) {
            modalInstance.show();
        } else {
            modalInstance.hide();
        }
    }
);


// Get All Connected Printers
const printers = ref([]);
const loadingPrinters = ref(false);

const fetchPrinters = async () => {
  loadingPrinters.value = true;
  try {
    const res = await axios.get("/api/printers");
    console.log("Printers:", res.data.data);

    // ✅ Only show connected printers (status OK)
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


// Print function for individual orders
const printOrder = (order) => {
    console.log(order);
    // Convert reactive object to plain object
    const plainOrder = JSON.parse(JSON.stringify(order));

    // Get data from related models
    const posOrder = plainOrder?.pos_order_type?.order;
    const posOrderType = plainOrder?.pos_order_type;
    const payment = posOrder?.payment;
    const posOrderItems = posOrder?.items || [];

    const customerName = posOrder?.customer_name || 'Walk-in Customer';
    const orderType = posOrderType?.order_type || 'Dine In';
    const tableNumber = posOrderType?.table_number;
    const subTotal = posOrder?.sub_total || 0;
    const totalAmount = posOrder?.total_amount || 0;

    // Payment method from payment table
    const type = (payment?.payment_method || "cash").toLowerCase();
    let payLine = "";
    if (type === "split") {
        payLine = `Payment Type: Split 
      (Cash: £${Number(payment?.cash_amount ?? 0).toFixed(2)}, 
       Card: £${Number(payment?.card_amount ?? 0).toFixed(2)})`;
    } else if (type === "card" || type === "stripe") {
        payLine = `Payment Type: Card${payment?.card_brand ? ` (${payment.card_brand}` : ""
            }${payment?.last4 ? ` •••• ${payment.last4}` : ""}${payment?.card_brand ? ")" : ""
            }`;
    } else {
        payLine = `Payment Type: ${payment?.payment_method || "Cash"}`;
    }

    // Match KOT items with POS items to get prices
    const itemsWithPrices = (plainOrder.items || []).map(kotItem => {
        const matchingPosItem = posOrderItems.find(posItem =>
            posItem.title === kotItem.item_name ||
            posItem.product_id === kotItem.product_id
        );
        return {
            ...kotItem,
            price: matchingPosItem?.price || 0
        };
    });

    const html = `
    <html>
    <head>
      <title>Kitchen Order Ticket</title>
      <style>
        @page { size: 80mm auto; margin: 0; }
        html, body {
          width: 80mm;
          margin: 0;
          padding: 8px;
          font-family: monospace, Arial, sans-serif;
          font-size: 12px;
          line-height: 1.4;
        }
        .header { text-align: center; margin-bottom: 10px; }
        .order-info { margin: 10px 0; }
        .order-info div { margin-bottom: 3px; }
        .payment-info { margin-top: 8px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 4px 0; text-align: left; }
        th { border-bottom: 1px solid #000; }
        td:last-child, th:last-child { text-align: right; }
        .totals { margin-top: 10px; border-top: 1px dashed #000; padding-top: 8px; }
        .footer { text-align: center; margin-top: 10px; font-size: 11px; }
      </style>
    </head>
    <body>
      <div class="header">
        <h2>KITCHEN ORDER TICKET</h2>
      </div>
      
      <div class="order-info">
        <div>KOT ID: #${plainOrder.id}</div>
        <div>Date: ${plainOrder.order_date}</div>
        <div>Time: ${plainOrder.order_time}</div>
        <div>Customer: ${customerName}</div>
        <div>Order Type: ${orderType}</div>
        ${tableNumber ? `<div>Table: ${tableNumber}</div>` : ''}
        
        <div class="payment-info">
          <div>${payLine}</div>
        </div>
        
        <div>Status: ${plainOrder.status}</div>
        ${plainOrder.note ? `<div>Note: ${plainOrder.note}</div>` : ''}
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
         ${itemsWithPrices.map(item => {
        const qty = Number(item.quantity) || 1;
        const price = Number(item.price) || 0;
        // Show price per item, not total
        return `
      <tr>
        <td>${item.item_name || 'Unknown Item'}</td>
        <td>${qty}</td>
        <td>£${price.toFixed(2)}</td>
      </tr>
    `;
    }).join('')}
        </tbody>
      </table>

      <div class="totals">
        <div>Subtotal: £${Number(subTotal).toFixed(2)}</div>
        <div>Total: £${Number(totalAmount).toFixed(2)}</div>
        ${payment?.cash_received ? `<div>Cash Received: £${Number(payment.cash_received).toFixed(2)}</div>` : ''}
        ${payment?.change ? `<div>Change: £${Number(payment.change).toFixed(2)}</div>` : ''}
      </div>

      <div class="footer">
        Kitchen Copy - Thank you!
      </div>
    </body>
    </html>
  `;

    const w = window.open("", "_blank", "width=400,height=600");
    if (!w) {
        alert("Please allow popups for this site to print KOT");
        return;
    }
    w.document.open();
    w.document.write(html);
    w.document.close();
    w.onload = () => {
        w.print();
        w.close();
    };
};

</script>
