<script setup>
import Master from "@/Layouts/Master.vue";
import { Head } from "@inertiajs/vue3";
import { ref, computed, onMounted } from "vue";
import Select from "primevue/select";
import { Clock, CheckCircle, XCircle, Printer } from "lucide-vue-next";
import { useFormatters } from '@/composables/useFormatters'
import FilterModal from "@/Components/FilterModal.vue";
import { nextTick } from "vue";
import { toast } from 'vue3-toastify';

const { formatMoney, formatNumber, dateFmt } = useFormatters()

const orders = ref([]);

const fetchOrders = async () => {
    try {
        const response = await axios.get("/api/kots/all-orders");

        orders.value = (response.data.data || []).map(ko => {
            const posOrder = ko.pos_order_type?.order;
            const posOrderItems = posOrder?.items || [];

            return {
                id: posOrder?.id || ko.id,
                created_at: posOrder?.created_at || ko.created_at,
                customer_name: posOrder?.customer_name || 'Walk In',
                total_amount: posOrder?.total_amount || 0,
                sub_total: posOrder?.sub_total || 0,
                status: ko.status || 'Waiting',
                type: {
                    order_type: ko.pos_order_type?.order_type,
                    table_number: ko.pos_order_type?.table_number
                },
                payment: posOrder?.payment,
                items: (ko.items || []).map(kotItem => {
                    // Find matching POS item to get price
                    const matchingPosItem = posOrderItems.find(posItem =>
                        posItem.title === kotItem.item_name ||
                        posItem.product_id === kotItem.product_id
                    );

                    return {
                        ...kotItem,
                        title: kotItem.item_name,
                        price: matchingPosItem?.price || 0,
                        quantity: kotItem.quantity || 1,
                        variant_name: kotItem.variant_name || '-',
                        ingredients: kotItem.ingredients || []
                    };
                }),
                orderIndex: ko.id
            };
        });

        console.log("Transformed orders:", orders.value);
    } catch (error) {
        console.error("Error fetching orders:", error);
        orders.value = [];
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
    status: "",
    dateFrom: "",
    dateTo: "",
});
const orderTypeFilter = ref("All");
const statusFilter = ref("All");

const orderTypeOptions = ref(["All", "Dine In", "Delivery", "Takeaway", "Collection"]);
const statusOptions = ref(["All", "Waiting", "Done", "Cancelled"]);

// ✅ STEP 1: Fix allItems computed to use item.status instead of order.status
const allItems = computed(() => {
    if (!orders.value || orders.value.length === 0) {
        return [];
    }

    const flattened = orders.value.flatMap((order, orderIndex) => {
        return order.items?.map((item, itemIndex) => ({
            ...item,
            status: item.status, // ✅ Use item's own status, not order.status
            orderIndex,
            order,
            uniqueId: `${order.id}-${itemIndex}`
        })) || [];
    });

    return flattened;
});

// const filtered = computed(() => {
//     const term = q.value.trim().toLowerCase();

//     return allItems.value
//         .filter((item) =>
//             orderTypeFilter.value === "All"
//                 ? true
//                 : (item.order?.type?.order_type ?? "").toLowerCase() ===
//                 orderTypeFilter.value.toLowerCase()
//         )
//         .filter((item) =>
//             statusFilter.value === "All"
//                 ? true
//                 : (item.status ?? "").toLowerCase() ===
//                 statusFilter.value.toLowerCase()
//         )
//         .filter((item) => {
//             if (!term) return true;
//             return [
//                 String(item.order?.id),
//                 item.item_name ?? "",
//                 item.variant_name ?? "",
//                 item.ingredients?.join(', ') ?? "",
//                 item.status ?? "",
//             ]
//                 .join(" ")
//                 .toLowerCase()
//                 .includes(term);
//         });
// });

const filtered = computed(() => {
    const term = q.value.trim().toLowerCase();
    let result = [...allItems.value];

    // Text search
    if (term) {
        result = result.filter((item) =>
            [
                String(item.order?.id),
                item.item_name ?? "",
                item.variant_name ?? "",
                item.ingredients?.join(', ') ?? "",
                item.status ?? "",
            ]
                .join(" ")
                .toLowerCase()
                .includes(term)
        );
    }

    // Order Type filter
    if (filters.value.orderType) {
        result = result.filter(
            (item) =>
                (item.order?.type?.order_type ?? "").toLowerCase() ===
                filters.value.orderType.toLowerCase()
        );
    }

    // Status filter
    if (filters.value.status) {
        result = result.filter((item) => {
            return item.status?.toLowerCase() === filters.value.status.toLowerCase();
        });
    }

    // Date range filter
    if (filters.value.dateFrom) {
        result = result.filter((item) => {
            const orderDate = new Date(item.order?.created_at);
            const filterDate = new Date(filters.value.dateFrom);
            return orderDate >= filterDate;
        });
    }

    if (filters.value.dateTo) {
        result = result.filter((item) => {
            const orderDate = new Date(item.order?.created_at);
            const filterDate = new Date(filters.value.dateTo);
            filterDate.setHours(23, 59, 59, 999);
            return orderDate <= filterDate;
        });
    }

    return result;
});


const sortedItems = computed(() => {
    const arr = [...filtered.value];
    const sortBy = filters.value.sortBy;

    switch (sortBy) {
        case "date_desc":
            return arr.sort((a, b) => new Date(b.order?.created_at) - new Date(a.order?.created_at));
        case "date_asc":
            return arr.sort((a, b) => new Date(a.order?.created_at) - new Date(b.order?.created_at));
        case "item_asc":
            return arr.sort((a, b) =>
                (a.item_name || "").localeCompare(b.item_name || "")
            );
        case "item_desc":
            return arr.sort((a, b) =>
                (b.item_name || "").localeCompare(a.item_name || "")
            );
        case "order_asc":
            return arr.sort((a, b) => (a.order?.id || 0) - (b.order?.id || 0));
        case "order_desc":
            return arr.sort((a, b) => (b.order?.id || 0) - (a.order?.id || 0));
        default:
            return arr.sort((a, b) => new Date(b.order?.created_at) - new Date(a.order?.created_at));
    }
});

const filterOptions = computed(() => ({
    sortOptions: [
        { value: "date_desc", label: "Date: Newest First" },
        { value: "date_asc", label: "Date: Oldest First" },
        { value: "item_asc", label: "Item: A to Z" },
        { value: "item_desc", label: "Item: Z to A" },
        { value: "order_asc", label: "Order ID: Low to High" },
        { value: "order_desc", label: "Order ID: High to Low" },
    ],
    orderTypeOptions: [
        { value: "Dine In", label: "Dine In" },
        { value: "Delivery", label: "Delivery" },
        { value: "Takeaway", label: "Takeaway" },
        { value: "Collection", label: "Collection" },
    ],
    statusOptions: [
        { value: "Waiting", label: "Waiting" },
        { value: "Done", label: "Done" },
        { value: "Cancelled", label: "Cancelled" },
    ],
}));

const handleFilterApply = (appliedFilters) => {
    console.log("Filters applied:", appliedFilters);
};

const handleFilterClear = () => {
    console.log("Filters cleared");
};


/* ===================== KPIs ===================== */
const totalTables = computed(() => {
    const tables = new Set(
        orders.value
            .map(o => o.type?.table_number)
            .filter(t => t)
    );
    return tables.size;
});

const totalItems = computed(() => allItems.value.length);

const pendingOrders = computed(
    () => orders.value.filter((o) => o.status === "Waiting").length
);

const cancelledOrders = computed(
    () => orders.value.filter((o) => o.status === "Cancelled").length
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

const getStatusBadge = (status) => {
    switch (status) {
        case 'Done':
            return 'bg-success';
        case 'Cancelled':
            return 'bg-danger';
        case 'Waiting':
            return 'bg-warning text-dark';
        default:
            return 'bg-secondary';
    }
};




// ✅ STEP 2: Update function to modify the item status in orders.value
const updateKotStatus = async (item, status) => {
    try {
        console.log(`Updating KOT item ID ${item.id} -> ${status}`);
        const response = await axios.put(`/api/pos/kot-item/${item.id}/status`, { status });

        // ✅ Find the order in orders.value and update the item's status
        const order = orders.value.find(o => o.id === item.order.id);
        if (order && order.items) {
            const kotItem = order.items.find(i => i.id === item.id);
            if (kotItem) {
                kotItem.status = response.data.status || status;

                // Force reactivity by creating a new items array
                order.items = [...order.items];
            }
        }

        toast.success(`"${item.item_name}" marked as ${status}`);
    } catch (err) {
        console.error("Failed to update KOT item status:", err);
        toast.error(err.response?.data?.message || 'Failed to update status');
    }
};



const printOrder = (order) => {
    const plainOrder = JSON.parse(JSON.stringify(order));

    // Access order data directly from the order object
    const customerName = plainOrder?.customer_name || 'Walk-in Customer';
    const orderType = plainOrder?.type?.order_type || 'Dine In';
    const tableNumber = plainOrder?.type?.table_number;
    const payment = plainOrder?.payment;
    const posOrderItems = plainOrder?.items || [];

    // Calculate totals from items
    const subTotal = posOrderItems.reduce((sum, item) =>
        sum + (Number(item.price || 0) * Number(item.quantity || 0)), 0
    );
    const totalAmount = plainOrder?.total_amount || subTotal;

    // Format date and time
    const createdDate = plainOrder?.created_at ? new Date(plainOrder.created_at) : new Date();
    const orderDate = createdDate.toISOString().split('T')[0];
    const orderTime = createdDate.toTimeString().split(' ')[0];

    const type = (payment?.payment_method || "cash").toLowerCase();
    let payLine = "";
    if (type === "split") {
        payLine = `Payment Type: Split (Cash: £${Number(payment?.cash_amount ?? 0).toFixed(2)}, Card: £${Number(payment?.card_amount ?? 0).toFixed(2)})`;
    } else if (type === "card" || type === "stripe") {
        payLine = `Payment Type: Card${payment?.card_brand ? ` (${payment.card_brand}` : ""}${payment?.last4 ? ` •••• ${payment.last4}` : ""}${payment?.card_brand ? ")" : ""}`;
    } else {
        payLine = `Payment Type: ${payment?.payment_method || "Cash"}`;
    }

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
        <div>Date: ${orderDate}</div>
        <div>Time: ${orderTime}</div>
        <div>Customer: ${customerName}</div>
        <div>Order Type: ${orderType}</div>
        ${tableNumber ? `<div>Table: ${tableNumber}</div>` : ''}
        
        <div class="payment-info">
          <div>${payLine}</div>
        </div>
        
        <div>Status: ${plainOrder.status}</div>
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
         ${posOrderItems.map(item => {
        const qty = Number(item.quantity) || 1;
        const price = Number(item.price) || 0;
        const itemName = item.title || item.item_name || 'Unknown Item';
        return `
      <tr>
        <td>${itemName}</td>
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
</script>

<template>

    <Head title="Kitchen Orders" />

    <Master>
        <div class="page-wrapper">
            <h4 class="fw-semibold mb-3">Kitchen Order Tickets</h4>

            <!-- KPI Cards -->
            <div class="row g-4">
                <!-- Total Tables -->
                <div class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h3 class="mb-0 fw-bold">{{ totalTables }}</h3>
                                <p class="text-muted mb-0 small">Total Tables</p>
                            </div>
                            <div class="rounded-circle p-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center"
                                style="width: 56px; height: 56px">
                                <i class="bi bi-table fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Items -->
                <div class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h3 class="mb-0 fw-bold">{{ totalItems }}</h3>
                                <p class="text-muted mb-0 small">Total Items</p>
                            </div>
                            <div class="rounded-circle p-3 bg-success-subtle text-success d-flex align-items-center justify-content-center"
                                style="width: 56px; height: 56px">
                                <i class="bi bi-basket fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Orders -->
                <div class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h3 class="mb-0 fw-bold">{{ pendingOrders }}</h3>
                                <p class="text-muted mb-0 small">Pending Orders</p>
                            </div>
                            <div class="rounded-circle p-3 bg-warning-subtle text-warning d-flex align-items-center justify-content-center"
                                style="width: 56px; height: 56px">
                                <i class="bi bi-hourglass-split fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cancelled Orders -->
                <div class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h3 class="mb-0 fw-bold">{{ cancelledOrders }}</h3>
                                <p class="text-muted mb-0 small">Cancelled Orders</p>
                            </div>
                            <div class="rounded-circle p-3 bg-danger-subtle text-danger d-flex align-items-center justify-content-center"
                                style="width: 56px; height: 56px">
                                <i class="bi bi-x-circle fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Orders Table -->
            <div class="card border-0 shadow-lg rounded-4">
                <div class="card-body">
                    <!-- Toolbar -->
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 fw-semibold">Kitchen Orders</h5>

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

                            <FilterModal v-model="filters" title="Kitchen Orders" modal-id="kotFilterModal"
                                modal-size="modal-lg" :sort-options="filterOptions.sortOptions" :show-date-range="true"
                                @apply="handleFilterApply" @clear="handleFilterClear">
                                <!-- Custom filters slot for Order Type and Status -->
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
                                            <i class="fas fa-tasks me-2 text-muted"></i>Order Status
                                        </label>
                                        <select v-model="filters.status" class="form-select">
                                            <option value="">All</option>
                                            <option v-for="opt in filterOptions.statusOptions" :key="opt.value"
                                                :value="opt.value">
                                                {{ opt.label }}
                                            </option>
                                        </select>
                                    </div>
                                </template>
                            </FilterModal>
                            <!-- Order Type filter -->
                            <!-- <div style="min-width: 170px">
                                <Select v-model="orderTypeFilter" :options="orderTypeOptions" placeholder="Order Type"
                                    class="w-100" :appendTo="'body'" :autoZIndex="true" :baseZIndex="2000">
                                    <template #value="{ value, placeholder }">
                                        <span v-if="value">{{ value }}</span>
                                        <span v-else>{{ placeholder }}</span>
                                    </template>
                                </Select>
                            </div> -->

                            <!-- Status filter -->
                            <!-- <div style="min-width: 160px">
                                <Select v-model="statusFilter" :options="statusOptions" placeholder="Status"
                                    class="w-100" :appendTo="'body'" :autoZIndex="true" :baseZIndex="2000">
                                    <template #value="{ value, placeholder }">
                                        <span v-if="value">{{ value }}</span>
                                        <span v-else>{{ placeholder }}</span>
                                    </template>
                                </Select>
                            </div> -->

                            <!-- Download -->
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary rounded-pill px-4 dropdown-toggle"
                                    data-bs-toggle="dropdown">
                                    Export
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow rounded-4 py-2">
                                    <li><a class="dropdown-item py-2" href="javascript:void(0)">Export as PDF</a>
                                    </li>
                                    <li><a class="dropdown-item py-2" href="javascript:void(0)">Export as Excel</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="border-top small text-muted">
                                <tr>
                                    <th>#</th>
                                    <th>Order ID</th>
                                    <th>Item Name</th>
                                    <th>Variant</th>
                                    <th>Order Type</th>
                                    <th>Ingredients</th>
                                    <th>Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in filtered" :key="item.uniqueId || index">
                                    <td>{{ index + 1 }}</td>
                                    <td>{{ item.order?.id }}</td>
                                    <td>{{ item.item_name }}</td>
                                    <td>{{ item.variant_name }}</td>
                                    <td>{{ item.order?.type?.order_type || '-' }}</td>
                                    <td>{{ item.ingredients?.join(', ') || '-' }}</td>
                                    <td>
                                        <span :class="['badge', 'rounded-pill', getStatusBadge(item.status)]"
                                            style="width: 80px; display: inline-flex; justify-content: center; align-items: center; height: 25px;">
                                            {{ item.status }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center align-items-center gap-2">
                                            <button @click="updateKotStatus(item, 'Waiting')" title="Waiting"
                                                class="p-2 rounded-full text-warning hover:bg-gray-100">
                                                <Clock class="w-5 h-5" />
                                            </button>

                                            <button @click="updateKotStatus(item, 'Done')" title="Done"
                                                class="p-2 rounded-full text-success hover:bg-gray-100">
                                                <CheckCircle class="w-5 h-5" />
                                            </button>

                                            <button @click="updateKotStatus(item, 'Cancelled')" title="Cancelled"
                                                class="p-2 rounded-full text-danger hover:bg-gray-100">
                                                <XCircle class="w-5 h-5" />
                                            </button>

                                            <button v-if="printers.length > 0"
                                                class="p-2 rounded-full text-gray-600 hover:bg-gray-100"
                                                @click.prevent="printOrder(item.order)" title="Print">
                                                <Printer class="w-5 h-5" />
                                            </button>
                                        </div>

                                    </td>
                                </tr>

                                <tr v-if="filtered.length === 0">
                                    <td colspan="7" class="text-center text-muted py-4">
                                        No orders found.
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
.icon-wrap {
    font-size: 2rem;
    color: #4e73df;
}

.dark .icon-wrap {
    color: #fff !important;
}

.kpi-label {
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.kpi-value {
    font-size: 1.75rem;
    font-weight: 600;
    color: #2c3e50;
}

.dark .kpi-value {
    color: #fff !important;
}

.search-wrap {
    position: relative;
}

.search-wrap i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
}

.search-input {
    padding-left: 2.5rem;
    border-radius: 50px;
    border: 1px solid #dee2e6;
}

.search-input:focus {
    border-color: #4e73df;
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
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

.dark .form-label {
    color: #fff !important;
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

.dark .form-select {
    background-color: #212121 !important;
    color: #fff !important;
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
</style>