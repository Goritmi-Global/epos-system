<script setup>
import Master from "@/Layouts/Master.vue";
import { Head } from "@inertiajs/vue3";
import { ref, computed, onMounted, watch } from "vue";
import Select from "primevue/select";
import { Clock, CheckCircle, XCircle, Printer, Loader, Eye } from "lucide-vue-next";
import { useFormatters } from '@/composables/useFormatters'
import FilterModal from "@/Components/FilterModal.vue";
import { nextTick } from "vue";
import { toast } from 'vue3-toastify';
import { jsPDF } from "jspdf";
import autoTable from "jspdf-autotable";
import * as XLSX from "xlsx";
import Pagination from "@/Components/Pagination.vue";
import Dropdown from 'primevue/dropdown'
import { useModal } from "@/composables/useModal";

const { closeModal } = useModal();



const { formatMoney, formatNumber, dateFmt } = useFormatters()

const orders = ref([]);
const loading = ref(false);
const selectedOrder = ref(null);
const showOrderItemsModal = ref(false);
const selectedItem = ref(null);

// ==========================================
// ADD THIS SECTION AFTER THE EXISTING REFS (around line 20)
// ==========================================


const activeStatusTab = ref('All');

const statusTabs = ref([
    { label: 'All', value: 'All', icon: 'bi-list-ul' },
    { label: 'Waiting', value: 'Waiting', icon: 'bi-hourglass-split' },
    { label: 'In Progress', value: 'In Progress', icon: 'bi-gear' },
    { label: 'Done', value: 'Done', icon: 'bi-check-circle' },
    { label: 'Cancelled', value: 'Cancelled', icon: 'bi-x-circle' }
]);
const statistics = ref({
    status_counts: {
        'All': 0,
        'Waiting': 0,
        'In Progress': 0,
        'Done': 0,
        'Cancelled': 0
    },
    kpis: {
        total_tables: 0,
        total_items: 0,
        pending_items: 0,
        in_progress_items: 0,
        done_items: 0,
        cancelled_items: 0
    }
});
const handleStatusTabChange = (status) => {
    activeStatusTab.value = status;

    if (status === 'All') {
        appliedFilters.value.status = '';
    } else {
        appliedFilters.value.status = status;
    }

    pagination.value.current_page = 1;

    fetchOrders(1);
}
const exportOption = ref(null)
const exportOptions = [
    { label: 'PDF', value: 'pdf' },
    { label: 'Excel', value: 'excel' },
    { label: 'CSV', value: 'csv' },
]

const onExportChange = (e) => {
    if (e.value) {
        onDownload(e.value)
        exportOption.value = null
    }
}

const statusTabCounts = computed(() => statistics.value.status_counts);

const getStatusTabBadgeClass = (status) => {
    switch (status) {
        case 'Waiting':
            return 'bg-warning text-dark';
        case 'In Progress':
            return 'bg-info text-white';
        case 'Done':
            return 'bg-success';
        case 'Cancelled':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
};

const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
    from: 0,
    to: 0,
    links: []
});

const fetchOrders = async (page = null) => {
    loading.value = true;
    try {
        const params = {
            q: q.value,
            page: page || pagination.value.current_page,
            per_page: pagination.value.per_page,
            sort_by: appliedFilters.value.sortBy || '',
            order_type: appliedFilters.value.orderType || '',
            status: appliedFilters.value.status || '',
            date_from: appliedFilters.value.dateFrom || '',
            date_to: appliedFilters.value.dateTo || '',
        };

        Object.keys(params).forEach(key => {
            if (params[key] === undefined || params[key] === '') {
                delete params[key];
            }
        });

        const response = await axios.get("/api/kots/all-orders", { params });
        console.log("Fetched KOT Orders:", response.data);

        // ✅ UPDATE STATISTICS from backend
        if (response.data.statistics) {
            statistics.value = response.data.statistics;
        }

        // Transform to order-wise structure (not item-wise)
        orders.value = (response.data.data || []).map(ko => {
            const posOrder = ko.pos_order_type?.order;
            const posOrderItems = posOrder?.items || [];

            return {
                id: posOrder?.id || ko.id,
                kot_id: ko.id,
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
                        item_kitchen_note: kotItem.item_kitchen_note || '',
                        ingredients: kotItem.ingredients || []
                    };
                }),
                item_count: ko.items?.length || 0
            };
        });

        // Update pagination
        pagination.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            per_page: response.data.per_page,
            total: response.data.total,
            from: response.data.from,
            to: response.data.to,
            links: response.data.links
        };

    } catch (error) {
        console.error("Error fetching orders:", error);
        orders.value = [];
        toast.error("Failed to load KOT orders");
    } finally {
        loading.value = false;
    }
};

const handlePageChange = (url) => {
    if (!url) return;
    const urlParams = new URLSearchParams(url.split('?')[1]);
    const page = urlParams.get('page');
    if (page) {
        fetchOrders(parseInt(page));
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

const appliedFilters = ref({
    sortBy: "",
    orderType: "",
    status: "",
    dateFrom: "",
    dateTo: "",
});
const orderTypeFilter = ref("All");
const statusFilter = ref("All");

const orderTypeOptions = ref(["All", "Eat In", "Delivery", "Takeaway", "Collection"]);
const statusOptions = ref(["All", "Waiting", "In Progress", "Done", "Cancelled"]);


const handleFilterApply = (appliedFiltersData) => {
    filters.value = { ...filters.value, ...appliedFiltersData };
    appliedFilters.value = { ...filters.value };
    pagination.value.current_page = 1;
    fetchOrders(1);
};

const handleFilterClear = () => {
    filters.value = {
        sortBy: "",
        orderType: "",
        status: "",
        dateFrom: "",
        dateTo: "",
    };
    appliedFilters.value = {
        sortBy: "",
        orderType: "",
        status: "",
        dateFrom: "",
        dateTo: "",
    };
    pagination.value.current_page = 1;
    pagination.value.per_page = 10;
    fetchOrders(1);
    closeModal('kotFilterModal');
};

const sortedOrders = computed(() => {
    return orders.value; // Sorting is handled by backend
});

const filterOptions = computed(() => ({
    sortOptions: [
        { value: "order_desc", label: "Total Amount: High to Low" },
        { value: "order_asc", label: "Total Amount: Low to High" },
        { value: "date_desc", label: "Date: Newest First" },
        { value: "date_asc", label: "Date: Oldest First" },
    ],
    orderTypeOptions: [
        { value: "Eat In", label: "Eat In" },
        { value: "Delivery", label: "Delivery" },
        { value: "Takeaway", label: "Takeaway" },
        { value: "Collection", label: "Collection" },
    ],
    statusOptions: [
        { value: "Waiting", label: "Waiting" },
        { value: "In Progress", label: "In Progress" },
        { value: "Done", label: "Done" },
        { value: "Cancelled", label: "Cancelled" },
    ],
}));

const totalTables = computed(() => statistics.value.kpis.total_tables);
const totalItems = computed(() => statistics.value.kpis.total_items);
const pendingItems = computed(() => statistics.value.kpis.pending_items);
const inProgressItems = computed(() => statistics.value.kpis.in_progress_items);
const doneItems = computed(() => statistics.value.kpis.done_items);
const cancelledItems = computed(() => statistics.value.kpis.cancelled_items);

const getStatusBadge = (status) => {
    switch (status) {
        case 'Done':
            return 'bg-success';
        case 'Cancelled':
            return 'bg-danger';
        case 'Waiting':
            return 'bg-warning text-dark';
        case 'In Progress':
            return 'bg-info text-white';
        default:
            return 'bg-secondary';
    }
};



const printOrder = (order) => {
    const plainOrder = JSON.parse(JSON.stringify(order));
    const customerName = plainOrder?.customer_name || 'Walk-in Customer';
    const orderType = plainOrder?.type?.order_type || 'Eat In';
    const tableNumber = plainOrder?.type?.table_number;
    const payment = plainOrder?.payment;
    const posOrderItems = plainOrder?.items || [];
    const subTotal = posOrderItems.reduce((sum, item) =>
        sum + (Number(item.price || 0) * Number(item.quantity || 0)), 0
    );
    const totalAmount = plainOrder?.total_amount || subTotal;
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
onMounted(fetchPrinters);


let searchTimeout = null;

watch(q, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        pagination.value.current_page = 1;
        fetchOrders(1);
    }, 500);
});

// ✅ Open modal to view order items
const viewOrderItems = (order) => {
    selectedOrder.value = order;
    showOrderItemsModal.value = true;

    const modalEl = document.getElementById('orderItemsModal');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
};

// ✅ Close modal
const closeOrderItemsModal = () => {
    showOrderItemsModal.value = false;
    selectedOrder.value = null;

    const modalEl = document.getElementById('orderItemsModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    }
};

const viewIngredients = (item) => {
    selectedItem.value = item;
    const modalEl = document.getElementById('ingredientsModal');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
};

const updateItemStatus = async (order, item, status) => {
    try {
        const response = await axios.put(`/api/kots/pos/kot-item/${item.id}/status`, { status });
        const orderIndex = orders.value.findIndex(o => o.kot_id === order.kot_id);
        if (orderIndex !== -1) {
            const itemIndex = orders.value[orderIndex].items.findIndex(i => i.id === item.id);
            if (itemIndex !== -1) {
                orders.value[orderIndex].items[itemIndex].status = response.data.status;
                orders.value[orderIndex].status = response.data.order_status;
                orders.value = [...orders.value];
            }
        }
        await fetchOrders(pagination.value.current_page);

        toast.success(`"${item.item_name}" marked as ${status}`);
    } catch (err) {
        console.error("Failed to update KOT item status:", err);
        toast.error(err.response?.data?.message || 'Failed to update status');
    }
};

const onDownload = async (type) => {
    if (!orders.value || orders.value.length === 0) {
        toast.error("No kitchen orders data to download");
        return;
    }

    try {
        loading.value = true;

        // Fetch ALL records without pagination
        const params = {
            q: q.value,
            sort_by: appliedFilters.value.sortBy || '',
            order_type: appliedFilters.value.orderType || '',
            status: appliedFilters.value.status || '',
            date_from: appliedFilters.value.dateFrom || '',
            date_to: appliedFilters.value.dateTo || '',
            export: 'all' // Flag to tell backend to return all records
        };

        Object.keys(params).forEach(key => {
            if (params[key] === undefined || params[key] === '') {
                delete params[key];
            }
        });

        const response = await axios.get("/api/kots/all-orders", { params });

        // Transform the data same way as fetchOrders
        const allOrders = (response.data.data || []).map(ko => {
            const posOrder = ko.pos_order_type?.order;
            const posOrderItems = posOrder?.items || [];

            return {
                id: posOrder?.id || ko.id,
                kot_id: ko.id,
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
                        item_kitchen_note: kotItem.item_kitchen_note || '',
                        ingredients: kotItem.ingredients || []
                    };
                }),
                item_count: ko.items?.length || 0
            };
        });

        if (allOrders.length === 0) {
            toast.error("No kitchen orders found to download");
            return;
        }

        // Now download with all records
        if (type === "pdf") {
            downloadPDF(allOrders);
        } else if (type === "excel") {
            downloadExcel(allOrders);
        } else if (type === "csv") {
            downloadCSV(allOrders);
        } else {
            toast.error("Invalid download type");
        }
    } catch (error) {
        console.error("Download failed:", error);
        toast.error(`Download failed: ${error.message}`);
    } finally {
        loading.value = false;
    }
};

// CSV Download
const downloadCSV = (data) => {
    try {
        const headers = [
            "KOT ID",
            "Order ID",
            "Customer Name",
            "Order Type",
            "Table Number",
            "Items Count",
            "Status",
            "Total Amount",
            "Date"
        ];

        const rows = data.map((order) => {
            return [
                `"${order.kot_id || ""}"`,
                `"${order.id || ""}"`,
                `"${order.customer_name || "Walk In"}"`,
                `"${order.type?.order_type || "-"}"`,
                `"${order.type?.table_number || "-"}"`,
                `"${order.item_count || 0}"`,
                `"${order.status || ""}"`,
                `"${Number(order.total_amount || 0).toFixed(2)}"`,
                `"${order.created_at ? dateFmt(order.created_at) : ""}"`
            ];
        });

        const csvContent = [
            headers.join(","),
            ...rows.map((r) => r.join(","))
        ].join("\n");

        const blob = new Blob([csvContent], {
            type: "text/csv;charset=utf-8;",
        });
        const url = URL.createObjectURL(blob);

        const link = document.createElement("a");
        link.setAttribute("href", url);
        link.setAttribute(
            "download",
            `Kitchen_Orders_${new Date().toISOString().split("T")[0]}.csv`
        );
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        toast.success("CSV downloaded successfully");
    } catch (error) {
        console.error("CSV generation error:", error);
        toast.error(`CSV generation failed: ${error.message}`);
    }
};

// PDF Download
const downloadPDF = (data) => {
    try {
        const doc = new jsPDF("l", "mm", "a4");

        doc.setFontSize(18);
        doc.setFont("helvetica", "bold");
        doc.text("Kitchen Orders Report", 14, 20);

        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        const currentDate = new Date().toLocaleString();
        doc.text(`Generated on: ${currentDate}`, 14, 28);
        doc.text(`Total Orders: ${data.length}`, 14, 34);

        const tableColumns = [
            "KOT ID",
            "Order ID",
            "Customer",
            "Type",
            "Table",
            "Items",
            "Status",
            "Amount",
            "Date"
        ];

        const tableRows = data.map((order) => {
            return [
                order.kot_id || "",
                order.id || "",
                order.customer_name || "Walk In",
                order.type?.order_type || "-",
                order.type?.table_number || "-",
                order.item_count || 0,
                order.status || "",
                `£${Number(order.total_amount || 0).toFixed(2)}`,
                order.created_at ? dateFmt(order.created_at) : ""
            ];
        });

        autoTable(doc, {
            head: [tableColumns],
            body: tableRows,
            startY: 40,
            styles: {
                fontSize: 8,
                cellPadding: 2,
                halign: "left",
                lineColor: [0, 0, 0],
                lineWidth: 0.1,
            },
            headStyles: {
                fillColor: [41, 128, 185],
                textColor: 255,
                fontStyle: "bold",
            },
            alternateRowStyles: { fillColor: [245, 245, 245] },
            margin: { left: 14, right: 14 },
            didDrawPage: (tableData) => {
                const pageCount = doc.internal.getNumberOfPages();
                const pageHeight = doc.internal.pageSize.height;
                doc.setFontSize(8);
                doc.text(
                    `Page ${tableData.pageNumber} of ${pageCount}`,
                    tableData.settings.margin.left,
                    pageHeight - 10
                );
            },
        });

        const fileName = `Kitchen_Orders_${new Date().toISOString().split("T")[0]}.pdf`;
        doc.save(fileName);

        toast.success("PDF downloaded successfully");
    } catch (error) {
        console.error("PDF generation error:", error);
        toast.error(`PDF generation failed: ${error.message}`);
    }
};

// Excel Download
const downloadExcel = (data) => {
    try {
        if (typeof XLSX === "undefined") {
            throw new Error("XLSX library is not loaded");
        }

        const worksheetData = data.map((order) => {
            return {
                "KOT ID": order.kot_id || "",
                "Order ID": order.id || "",
                "Customer Name": order.customer_name || "Walk In",
                "Order Type": order.type?.order_type || "-",
                "Table Number": order.type?.table_number || "-",
                "Items Count": order.item_count || 0,
                "Status": order.status || "",
                "Total Amount": Number(order.total_amount || 0).toFixed(2),
                "Date": order.created_at || ""
            };
        });

        const workbook = XLSX.utils.book_new();
        const worksheet = XLSX.utils.json_to_sheet(worksheetData);

        worksheet["!cols"] = [
            { wch: 10 },  // KOT ID
            { wch: 10 },  // Order ID
            { wch: 20 },  // Customer Name
            { wch: 12 },  // Order Type
            { wch: 12 },  // Table Number
            { wch: 12 },  // Items Count
            { wch: 12 },  // Status
            { wch: 12 },  // Total Amount
            { wch: 18 }   // Date
        ];

        XLSX.utils.book_append_sheet(workbook, worksheet, "Kitchen Orders");

        const metaData = [
            { Info: "Generated On", Value: new Date().toLocaleString() },
            { Info: "Total Orders", Value: data.length },
            { Info: "Exported By", Value: "Kitchen Management System" }
        ];
        const metaSheet = XLSX.utils.json_to_sheet(metaData);
        XLSX.utils.book_append_sheet(workbook, metaSheet, "Report Info");

        const fileName = `Kitchen_Orders_${new Date().toISOString().split("T")[0]}.xlsx`;
        XLSX.writeFile(workbook, fileName);

        toast.success("Excel file downloaded successfully");
    } catch (error) {
        console.error("Excel generation error:", error);
        toast.error(`Excel generation failed: ${error.message}`);
    }
};

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
                <!-- Pending Items -->
                <div class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h3 class="mb-0 fw-bold">{{ pendingItems }}</h3>
                                <p class="text-muted mb-0 small">Pending Items</p>
                            </div>
                            <div class="rounded-circle p-3 bg-warning-subtle text-warning d-flex align-items-center justify-content-center"
                                style="width: 56px; height: 56px">
                                <i class="bi bi-hourglass-split fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- In Progress Items -->
                <div class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h3 class="mb-0 fw-bold">{{ inProgressItems }}</h3>
                                <p class="text-muted mb-0 small">In Progress</p>
                            </div>
                            <div class="rounded-circle p-3 bg-info-subtle text-info d-flex align-items-center justify-content-center"
                                style="width: 56px; height: 56px">
                                <i class="bi bi-gear fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Done Items -->
                <div class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h3 class="mb-0 fw-bold">{{ doneItems }}</h3>
                                <p class="text-muted mb-0 small">Completed Items</p>
                            </div>
                            <div class="rounded-circle p-3 bg-success-subtle text-success d-flex align-items-center justify-content-center"
                                style="width: 56px; height: 56px">
                                <i class="bi bi-check-circle fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cancelled Items -->
                <div class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h3 class="mb-0 fw-bold">{{ cancelledItems }}</h3>
                                <p class="text-muted mb-0 small">Cancelled Items</p>
                            </div>
                            <div class="rounded-circle p-3 bg-danger-subtle text-danger d-flex align-items-center justify-content-center"
                                style="width: 56px; height: 56px">
                                <i class="bi bi-x-circle fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- ========================================== -->
            <!-- REPLACE THE ENTIRE CARD SECTION (around line 300) -->
            <!-- ========================================== -->

            <div class="card border-0 shadow-lg rounded-4">
                <div class="card-body">
                    <!-- Header with Search and Export -->
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
                                @apply="handleFilterApply" :showDateRange="false" :show-stock-status="false"
                                @clear="handleFilterClear">
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

                            <Dropdown v-model="exportOption" :options="exportOptions" optionLabel="label"
                                optionValue="value" placeholder="Export" class="export-dropdown"
                                @change="onExportChange" />

                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex gap-2 flex-wrap">
                            <button v-for="tab in statusTabs" :key="tab.value" @click="handleStatusTabChange(tab.value)"
                                :class="[
                                    'btn btn-sm rounded-pill px-4 py-2 d-flex align-items-center gap-2',
                                    activeStatusTab === tab.value
                                        ? 'btn-primary text-white shadow-sm'
                                        : 'btn-outline-secondary'
                                ]" style="min-width: 120px; transition: all 0.2s ease;">
                                <i :class="tab.icon"></i>
                                <span>{{ tab.label }}</span>
                                <span v-if="tab.value !== 'All'" :class="[
                                    'badge rounded-pill ms-1',
                                    activeStatusTab === tab.value
                                        ? 'bg-white text-primary'
                                        : getStatusTabBadgeClass(tab.value)
                                ]" style="min-width: 24px;">
                                    {{ statusTabCounts[tab.value] || 0 }}
                                </span>
                                <span v-else :class="[
                                    'badge rounded-pill ms-1',
                                    activeStatusTab === tab.value
                                        ? 'bg-white text-primary'
                                        : 'bg-secondary'
                                ]" style="min-width: 24px;">
                                    {{ statusTabCounts.All }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="border-top small text-muted">
                                <tr>
                                    <th>#</th>
                                    <th>KOT ID</th>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Order Type</th>
                                    <th>Table</th>
                                    <th>Items Count</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Total Amount</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="loading">
                                    <td colspan="11" class="text-center py-5">
                                        <div class="d-flex flex-column align-items-center justify-content-center">
                                            <div class="spinner-border mb-3" role="status"
                                                style="color: #1B1670; width: 3rem; height: 3rem; border-width: 0.3em;">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <div class="fw-semibold text-muted">Loading KOT orders...</div>
                                        </div>
                                    </td>
                                </tr>
                                <template v-else>
                                    <tr v-for="(order, index) in sortedOrders" :key="order.kot_id">
                                        <td>{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
                                        <td class="fw-semibold">{{ order.kot_id }}</td>
                                        <td>{{ order.id }}</td>
                                        <td>{{ order.customer_name }}</td>
                                        <td>{{ order.type?.order_type || '-' }}</td>
                                        <td>{{ order.type?.table_number || '-' }}</td>
                                        <td>
                                            <span class="badge bg-info text-white rounded-pill">
                                                {{ order.item_count }} items
                                            </span>
                                        </td>
                                        <td>
                                            <span :class="['badge', 'rounded-pill', getStatusBadge(order.status)]"
                                                style="min-width: 90px; display: inline-flex; justify-content: center; align-items: center; height: 25px;">
                                                {{ order.status }}
                                            </span>
                                        </td>
                                        <td>{{ dateFmt(order.created_at) }}</td>
                                        <td>{{ order?.payment?.amount_received }}</td>
                                        <td>
                                            <div class="d-flex justify-content-center align-items-center gap-2">
                                                <!-- View Items Button -->
                                                <button @click="viewOrderItems(order)" title="View Items"
                                                    class="p-2 rounded-full text-primary hover:bg-gray-100">
                                                    <Eye class="w-5 h-5" />
                                                </button>

                                                <!-- Print Button -->
                                                <button v-if="printers.length > 0"
                                                    class="p-2 rounded-full text-gray-600 hover:bg-gray-100"
                                                    @click.prevent="printOrder(order)" title="Print">
                                                    <Printer class="w-5 h-5" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="!loading && sortedOrders.length === 0">
                                        <td colspan="11" class="text-center text-muted py-4">
                                            <div
                                                class="d-flex flex-column align-items-center justify-content-center py-3">
                                                <i class="bi bi-inbox"
                                                    style="font-size: 2rem; opacity: 0.5; margin-bottom: 0.5rem;"></i>
                                                <div class="fw-semibold">No KOT orders found</div>
                                                <div class="small text-secondary mt-1">
                                                    {{ activeStatusTab === 'All'
                                                        ? 'Try adjusting your filters or date range'
                                                        : `No orders with status "${activeStatusTab}"`
                                                    }}
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="!loading && pagination.last_page > 1"
                        class="mt-4 d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} items
                        </div>

                        <Pagination :pagination="pagination.links" :isApiDriven="true"
                            @page-changed="handlePageChange" />
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="orderItemsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content rounded-4 shadow border-0 dark:bg-[#212121]">
                    <!-- Header -->
                    <div class="modal-header border-0 dark:bg-[#181818]">
                        <div class="d-flex align-items-center gap-3 dark:text-white">
                            <div class="rounded-circle p-3 bg-white bg-opacity-25 dark:bg-white dark:bg-opacity-10">
                                <i class="bi bi-list-task fs-4"></i>
                            </div>
                            <div>
                                <h5 class="modal-title fw-bold mb-1">
                                    Order Items - KOT #{{ selectedOrder?.kot_id }}
                                </h5>
                                <small class="opacity-75">
                                    Order #{{ selectedOrder?.id }} | {{ selectedOrder?.customer_name }}
                                </small>
                            </div>
                        </div>
                        <button
                            class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition transform hover:scale-110"
                            @click="closeOrderItemsModal" data-bs-dismiss="modal" aria-label="Close" title="Close">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body p-4">
                        <div v-if="selectedOrder" class="rounded-3 p-4">
                            <!-- Order Info -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <small class="text-muted d-block dark:text-gray-400">Order Type</small>
                                    <strong class="dark:text-white">{{ selectedOrder.type?.order_type || '-' }}</strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block dark:text-gray-400">Table Number</small>
                                    <strong class="dark:text-white">{{ selectedOrder.type?.table_number || '-'
                                    }}</strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block dark:text-gray-400">Order Status</small>
                                    <span :class="['badge', 'rounded-pill', getStatusBadge(selectedOrder.status)]">
                                        {{ selectedOrder.status }}
                                    </span>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block dark:text-gray-400">Date</small>
                                    <strong class="dark:text-white">{{ dateFmt(selectedOrder.created_at) }}</strong>
                                </div>
                            </div>

                            <hr class="my-3 dark:border-gray-600" />

                            <!-- Items Table -->
                            <h6 class="fw-bold mb-3 text-primary dark:text-blue-400">
                                <i class="bi bi-basket me-2"></i>Order Items ({{ selectedOrder.item_count }})
                            </h6>
                            <div class="table-responsive">
                                <table
                                    class="table table-hover align-middle rounded-3 overflow-hidden dark:text-gray-200">
                                    <thead>
                                        <tr>
                                            <th class="px-3">#</th>
                                            <th>Item Name</th>
                                            <th>Variant</th>
                                            <th>Quantity</th>
                                            <th>Kitchen Note</th>
                                            <th>Status</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(item, idx) in selectedOrder.items" :key="item.id"
                                            class="dark:border-gray-700">
                                            <td class="px-3">{{ idx + 1 }}</td>
                                            <td class="fw-semibold">{{ item.item_name }}</td>
                                            <td>{{ item.variant_name }}</td>
                                            <td>
                                                <span class="badge bg-secondary">{{ item.quantity }}</span>
                                            </td>
                                            <td>
                                                <small class="text-muted dark:text-gray-400">{{ item.item_kitchen_note.substring(item.item_kitchen_note.indexOf('No')) }}</small>
                                            </td>
                                            <td>
                                                <span :class="['badge', 'rounded-pill', getStatusBadge(item.status)]"
                                                    style="min-width: 80px;">
                                                    {{ item.status }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center align-items-center gap-2">
                                                    <button @click="updateItemStatus(selectedOrder, item, 'Waiting')"
                                                        title="Waiting"
                                                        class="btn btn-sm p-1 text-warning dark:text-yellow-400 dark:hover:bg-gray-700">
                                                        <Clock class="w-4 h-4" />
                                                    </button>

                                                    <button
                                                        @click="updateItemStatus(selectedOrder, item, 'In Progress')"
                                                        title="In Progress"
                                                        class="btn btn-sm p-1 text-info dark:text-blue-400 dark:hover:bg-gray-700">
                                                        <Loader class="w-4 h-4" />
                                                    </button>

                                                    <button @click="updateItemStatus(selectedOrder, item, 'Done')"
                                                        title="Done"
                                                        class="btn btn-sm p-1 text-success dark:text-green-400 dark:hover:bg-gray-700">
                                                        <CheckCircle class="w-4 h-4" />
                                                    </button>

                                                    <button @click="updateItemStatus(selectedOrder, item, 'Cancelled')"
                                                        title="Cancelled"
                                                        class="btn btn-sm p-1 text-danger dark:text-red-400 dark:hover:bg-gray-700">
                                                        <XCircle class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer border-0 dark:bg-[#212121]">
                        <button type="button" class="btn btn-secondary rounded-pill px-4 py-2"
                            @click="closeOrderItemsModal">
                            Close
                        </button>
                        <button v-if="printers.length > 0" class="btn btn-primary rounded-pill px-4"
                            @click="printOrder(selectedOrder)">
                            <Printer class="w-4 h-4 me-2" style="display: inline-block;" />
                            Print Order
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ingredients Modal -->
        <div class="modal fade" id="ingredientsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 shadow border-0 dark:bg-[#212121]">
                    <!-- Header -->
                    <div class="modal-header border-0 dark:bg-[#181818]">
                        <div class="d-flex align-items-center gap-3 dark:text-white">
                            <div class="rounded-circle p-2 bg-white bg-opacity-25 dark:bg-white dark:bg-opacity-10">
                                <i class="bi bi-egg-fried fs-5"></i>
                            </div>
                            <div>
                                <h5 class="modal-title fw-bold mb-0">Ingredients</h5>
                                <small class="opacity-75">{{ selectedItem?.item_name }}</small>
                            </div>
                        </div>
                        <!-- <button type="button" class="btn-close dark:filter dark:invert" data-bs-dismiss="modal"
                            aria-label="Close"></button> -->

                        <button
                            class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition transform hover:scale-110"
                            data-bs-dismiss="modal" aria-label="Close" title="Close">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body p-4">
                        <div v-if="selectedItem && selectedItem.ingredients && selectedItem.ingredients.length > 0">
                            <ul class="list-group list-group-flush">
                                <li v-for="(ingredient, index) in selectedItem.ingredients" :key="index"
                                    class="list-group-item d-flex align-items-center gap-2 dark:bg-transparent dark:text-gray-200 dark:border-gray-700">
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                    <span>{{ ingredient }}</span>
                                </li>
                            </ul>
                        </div>
                        <div v-else class="text-center text-muted py-4 dark:text-gray-400">
                            <i class="bi bi-info-circle fs-3 mb-2"></i>
                            <p class="mb-0">No ingredients available</p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer border-0 dark:bg-[#212121]">
                        <button type="button" class="btn btn-secondary rounded-pill px-4 py-2" data-bs-dismiss="modal">
                            Close
                        </button>
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

.dark .modal-header {
    background-color: #181818 !important;
}

:global(.dark .form-control:focus) {
    border-color: #fff !important;
}


.dark .list-group-item {
    background-color: #181818 !important;
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

.btn:hover {
    transform: translateY(-1px);
}

.btn-primary {
    background: linear-gradient(135deg, #142985 0%, #390072 100%);
    border: none;
}

.btn-outline-secondary:hover {
    background-color: rgba(108, 117, 125, 0.1);
    border-color: #6c757d;
}

/* Tab Badge Animation */
.badge {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.8);
    }

    to {
        opacity: 1;
        transform: scale(1);
    }
}

* {
    transition: all 0.2s ease;
}

:deep(.p-multiselect-header) {
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

.dark .form-label {
    color: #fff !important;
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
}

:deep(.p-multiselect-overlay .p-checkbox-box) {
    background: #fff !important;
    border: 1px solid #ccc !important;
}

:deep(.p-multiselect-overlay .p-checkbox-box.p-highlight) {
    background: #007bff !important;
    border-color: #007bff !important;
}

:deep(.p-multiselect-filter) {
    background: #fff !important;
    color: #000 !important;
    border: 1px solid #ccc !important;
}

.dark .form-select {
    background-color: #212121 !important;
    color: #fff !important;
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

:deep(.p-multiselect-label) {
    color: #000 !important;
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
    background: #181818 !important;
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
    background: #111 !important;
    color: #fff !important;
    border: 1px solid #555 !important;
    border-radius: 12px !important;
    padding: 0.25rem 0.5rem !important;
}

:global(.dark .p-multiselect-chip .p-chip-remove-icon) {
    color: #ccc !important;
}

:global(.dark .p-multiselect-chip .p-chip-remove-icon:hover) {
    color: #f87171 !important;
}

:global(.dark .p-select) {
    background-color: #181818 !important;
    color: #fff !important;
    border-color: #555 !important;
}

:global(.dark .p-select-list-container) {
    background-color: #181818 !important;
    color: #fff !important;
}

:global(.dark .p-select-option) {
    background-color: transparent !important;
    color: #fff !important;
}

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