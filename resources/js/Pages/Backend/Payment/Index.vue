<script setup>
import Master from "@/Layouts/Master.vue";
import { Head } from "@inertiajs/vue3";
import { ref, computed, onMounted, watch, onUpdated } from "vue";
import Select from "primevue/select";
import { useFormatters } from '@/composables/useFormatters'
import FilterModal from "@/Components/FilterModal.vue";
import { nextTick } from "vue";
import { toast } from "vue3-toastify";
import { jsPDF } from "jspdf";
import autoTable from "jspdf-autotable";
import * as XLSX from "xlsx";
import Pagination from "@/Components/Pagination.vue";
import Dropdown from 'primevue/dropdown'
import { useModal } from "@/composables/useModal";
import { Eye } from "lucide-vue-next";
const { closeModal } = useModal();

const selectedOrder = ref(null);
const showDetailModal = ref(false);

const openDetailModal = (group) => {
    selectedOrder.value = group;
    // We'll use Bootstrap modal directly since useModal only has closeModal
    const modalEl = document.getElementById('paymentDetailModal');
    if (modalEl) {
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalInstance.show();
    }
};

const { formatMoney, formatCurrencySymbol, formatNumber, dateFmt } = useFormatters()
const orders = ref([]);

const loading = ref(false);

const stats = ref({
    total_payments: 0,
    todays_payments: 0,
    total_amount: 0
});

const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
    from: 0,
    to: 0,
    links: []
});

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

const fetchOrdersWithPayment = async (page = null) => {
    loading.value = true;
    try {
        const params = {
            q: q.value,
            page: page || pagination.value.current_page,
            per_page: pagination.value.per_page,
            sort_by: appliedFilters.value.sortBy || '',
            payment_type: appliedFilters.value.paymentType || '',
            date_from: appliedFilters.value.dateFrom || '',
            date_to: appliedFilters.value.dateTo || '',
            price_min: appliedFilters.value.priceMin || '',
            price_max: appliedFilters.value.priceMax || '',
        };

        Object.keys(params).forEach(key => {
            if (params[key] === undefined || params[key] === '') {
                delete params[key];
            }
        });

        const response = await axios.get("/api/payments/all", { params });

        // Store the raw order data from the backend
        orders.value = response.data.data;

        // ✅ Update stats from API response
        if (response.data.stats) {
            stats.value = response.data.stats;
        }

        // ✅ Update pagination
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
        console.error("Error fetching payments:", error);
        orders.value = [];
        toast.error("Failed to load payments");
    } finally {
        loading.value = false;
    }
};


const handlePageChange = (url) => {
    if (!url) return;
    const urlParams = new URLSearchParams(url.split('?')[1]);
    const page = urlParams.get('page');
    if (page) {
        fetchOrdersWithPayment(parseInt(page));
    }
};

onMounted(async () => {
    q.value = "";
    searchKey.value = Date.now();
    await nextTick();
    setTimeout(() => {
        isReady.value = true;
        const input = document.getElementById(inputId);
        if (input) {
            input.value = '';
            q.value = '';
        }
    }, 100);
    fetchOrdersWithPayment();
});

/* ===================== Toolbar: Search + Filter ===================== */
const q = ref("");
const searchKey = ref(Date.now());
const inputId = `search-${Math.random().toString(36).substr(2, 9)}`;
const isReady = ref(false);
const filters = ref({
    sortBy: "",
    paymentType: "",
    dateFrom: "",
    dateTo: "",
    priceMin: null,
    priceMax: null,
})

// Store the last applied filters
const appliedFilters = ref({
    sortBy: "",
    paymentType: "",
    dateFrom: "",
    dateTo: "",
    priceMin: null,
    priceMax: null,
});
const payments = computed(() =>
    orders.value.map((o) => {
        const promo = Array.isArray(o.promo) && o.promo.length > 0 ? o.promo[0] : null;

        return {
            orderId: o.id,
            customer: o.customer_name,
            serviceCharges: Number(o.service_charges || 0),
            deliveryCharges: Number(o.delivery_charges || 0),
            tax: Number(o.tax || 0),
            promoDiscount: promo ? Number(promo.discount_amount || 0) : 0,
            salesDiscount: Number(o.sales_discount || 0),
            approvedDiscount: Number(o.approved_discounts || 0),
            grandTotal: Number(o.total_amount || 0),
            promoName: promo ? promo.promo_name : "—",
            status: o.status,
            payments: o.payments || []
        };
    })
);

const groupedPayments = computed(() => {
    return payments.value.map(p => {
        const amountReceived = p.payments.reduce((sum, pay) => sum + Number(pay.amount_received || 0), 0);
        const paymentTypes = [...new Set(p.payments.map(pay => pay.payment_type))];
        const latestPayment = p.payments.length > 0
            ? p.payments.reduce((latest, current) =>
                new Date(current.payment_date) > new Date(latest.payment_date) ? current : latest
            )
            : null;

        return {
            ...p,
            amountReceived,
            paidAt: latestPayment ? latestPayment.payment_date : null,
            type: paymentTypes.length > 0 ? paymentTypes[0] : '—',
            paymentTypeDisplay: paymentTypes.length > 1 ? `Multiple (${p.payments.length})` : (paymentTypes[0] || '—'),
            details: p.payments.map(pay => ({
                paidAt: pay.payment_date,
                amount: pay.amount_received,
                type: pay.payment_type,
                status: pay.status || 'Paid',
                user: pay.user?.name || '—'
            }))
        };
    });
});

const handleFilterApply = (appliedFiltersData) => {
    filters.value = { ...filters.value, ...appliedFiltersData };
    appliedFilters.value = { ...filters.value };
    pagination.value.current_page = 1;
    fetchOrdersWithPayment(1);
};

const handleFilterClear = () => {
    filters.value = {
        sortBy: "",
        paymentType: "",
        dateFrom: "",
        dateTo: "",
        priceMin: null,
        priceMax: null,
    };
    appliedFilters.value = {
        sortBy: "",
        paymentType: "",
        dateFrom: "",
        dateTo: "",
        priceMin: null,
        priceMax: null,
    };
    pagination.value.current_page = 1;
    pagination.value.per_page = 10;
    fetchOrdersWithPayment(1);
    closeModal('paymentFilterModal');
};

const filterOptions = computed(() => ({
    sortOptions: [
        { value: "date_desc", label: "Date: Newest First" },
        { value: "date_asc", label: "Date: Oldest First" },
        { value: "amount_desc", label: "Amount: High to Low" },
        { value: "amount_asc", label: "Amount: Low to High" },
        { value: "order_asc", label: "Order ID: Low to High" },
        { value: "order_desc", label: "Order ID: High to Low" },
    ],
    paymentTypeOptions: [
        { value: "Cash", label: "Cash" },
        { value: "Card", label: "Card" },
        { value: "Split", label: "Split" },
        { value: "QR", label: "QR" },
        { value: "Bank", label: "Bank" },
    ],
}));

/* ===================== KPIs ===================== */
const totalPayments = computed(() => payments.value.length);

const todaysPayments = computed(() => {
    const now = new Date();
    const start = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const end = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1);

    return orders.value.filter((o) => {
        if (!o.created_at || !o.payment) return false;
        const dt = new Date(o.created_at);
        return dt >= start && dt < end;
    }).length;
});

const totalAmount = computed(() =>
    payments.value.reduce((sum, p) => sum + Number(p.grandTotal || 0), 0)
);
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
onMounted(() => window.feather?.replace());
onUpdated(() => window.feather?.replace());

let searchTimeout = null;

watch(q, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        pagination.value.current_page = 1;
        fetchOrdersWithPayment(1);
    }, 500);
});

const onDownload = async (type) => {
    if (!orders.value || orders.value.length === 0) {
        toast.error("No payment data to download");
        return;
    }

    try {
        loading.value = true;

        // Fetch ALL records without pagination
        const params = {
            q: q.value,
            sort_by: appliedFilters.value.sortBy || '',
            payment_type: appliedFilters.value.paymentType || '',
            date_from: appliedFilters.value.dateFrom || '',
            date_to: appliedFilters.value.dateTo || '',
            price_min: appliedFilters.value.priceMin || '',
            price_max: appliedFilters.value.priceMax || '',
            export: 'all' // Flag to tell backend to return all records
        };

        Object.keys(params).forEach(key => {
            if (params[key] === undefined || params[key] === '') {
                delete params[key];
            }
        });

        const response = await axios.get("/api/payments/all", { params });

        // Transform the data same way as fetchOrdersWithPayment
        const allPayments = response.data.data.map(order => {
            const promo = Array.isArray(order.promo) && order.promo.length > 0 ? order.promo[0] : null;
            const payments = order.payments || [];
            const amountReceived = payments.reduce((sum, pay) => sum + Number(pay.amount_received || 0), 0);
            const paymentTypes = [...new Set(payments.map(pay => pay.payment_type))];
            const latestPayment = payments.length > 0
                ? payments.reduce((latest, current) =>
                    new Date(current.payment_date) > new Date(latest.payment_date) ? current : latest
                )
                : null;

            return {
                orderId: order.id,
                customer_name: order.customer_name,
                promo_discount: promo ? Number(promo.discount_amount || 0) : 0,
                promo_name: promo ? promo.promo_name : "—",
                service_charges: Number(order.service_charges || 0),
                delivery_charges: Number(order.delivery_charges || 0),
                tax: Number(order.tax || 0),
                sub_total: Number(order.sub_total || 0),
                sales_discount: Number(order.sales_discount || 0),
                approved_discounts: Number(order.approved_discounts || 0),
                total_amount: Number(order.total_amount || 0),
                status: order.status,
                payment_type: paymentTypes.join(', '),
                amount_received: amountReceived,
                payment_date: latestPayment ? latestPayment.payment_date : null,
                username: latestPayment ? (latestPayment.user?.name || "—") : "—"
            };
        });

        if (allPayments.length === 0) {
            toast.error("No payments found to download");
            return;
        }

        // Now download with transformed records (already grouped by backend)
        const groupedData = allPayments;

        // Now download with grouped records
        if (type === "pdf") {
            downloadPaymentsPDF(groupedData);
        } else if (type === "excel") {
            downloadPaymentsExcel(groupedData);
        } else if (type === "csv") {
            downloadPaymentsCSV(groupedData);
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

const groupDataForDownload = (data) => {
    const groups = {};
    data.forEach(p => {
        if (!groups[p.orderId]) {
            groups[p.orderId] = {
                ...p,
                amount_received: 0,
                payment_types: []
            };
        }
        groups[p.orderId].amount_received += p.amount_received;
        if (!groups[p.orderId].payment_types.includes(p.payment_type)) {
            groups[p.orderId].payment_types.push(p.payment_type);
        }
    });
    return Object.values(groups).map(g => ({
        ...g,
        payment_type: g.payment_types.join(', ')
    }));
};


const downloadPaymentsCSV = (data) => {
    try {
        const headers = [
            "Order ID",
            "Date",
            "Customer",
            "Payment Type",
            "Amount Received",
            "Promo Discount",
            "Sales Discount",
            "Approved Discount",
            "Tax",
            "Service Charges",
            "Delivery Charges",
            "Grand Total",
            "Status"
        ];

        const rows = data.map((order) => {
            return [
                `"${order.orderId || ""}"`,
                `"${dateFmt(order.payment_date)}"`,
                `"${order.customer_name || "Walk In"}"`,
                `"${order.payment_type || "-"}"`,
                `"${Number(order.amount_received || 0).toFixed(2)}"`,
                `"${Number(order.promo_discount || 0).toFixed(2)}"`,
                `"${Number(order.sales_discount || 0).toFixed(2)}"`,
                `"${Number(order.approved_discounts || 0).toFixed(2)}"`,
                `"${Number(order.tax || 0).toFixed(2)}"`,
                `"${Number(order.service_charges || 0).toFixed(2)}"`,
                `"${Number(order.delivery_charges || 0).toFixed(2)}"`,
                `"${Number(order.total_amount || 0).toFixed(2)}"`,
                `"${order.status || ""}"`
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
            `Payments_${new Date().toISOString().split("T")[0]}.csv`
        );
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        toast.success("Payments CSV downloaded successfully");
    } catch (error) {
        console.error("CSV generation error:", error);
        toast.error(`CSV generation failed: ${error.message}`);
    }
};

const downloadPaymentsPDF = (data) => {
    try {
        const doc = new jsPDF("l", "mm", "a4");

        doc.setFontSize(18);
        doc.setFont("helvetica", "bold");
        doc.text("Payments Report", 14, 20);

        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        const currentDate = new Date().toLocaleString();
        doc.text(`Generated on: ${currentDate}`, 14, 28);
        doc.text(`Total Payments: ${data.length}`, 14, 34);
        const totalAmount = data.reduce((sum, o) => sum + Number(o.total_amount || 0), 0);
        const totalReceived = data.reduce((sum, o) => sum + Number(o.amount_received || 0), 0);

        doc.text(`Total Revenue: £${totalAmount.toFixed(2)}`, 14, 40);
        doc.text(`Total Received: £${totalReceived.toFixed(2)}`, 14, 46);

        const tableColumns = [
            "Order ID",
            "Date",
            "Customer",
            "Payment Type",
            "Amount Received",
            "Promo Disc.",
            "Sales Disc.",
            "Tax",
            "Grand Total",
            "Status"
        ];

        const tableRows = data.map((order) => {
            return [
                order.orderId || "",
                dateFmt(order.payment_date),
                order.customer_name || "Walk In",
                order.payment_type || "-",
                `£${Number(order.amount_received || 0).toFixed(2)}`,
                `£${Number(order.promo_discount || 0).toFixed(2)}`,
                `£${Number(order.sales_discount || 0).toFixed(2)}`,
                `£${Number(order.tax || 0).toFixed(2)}`,
                `£${Number(order.total_amount || 0).toFixed(2)}`,
                order.status || ""
            ];
        });

        autoTable(doc, {
            head: [tableColumns],
            body: tableRows,
            startY: 52,
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

        const fileName = `Payments_${new Date().toISOString().split("T")[0]}.pdf`;
        doc.save(fileName);

        toast.success("Payments PDF downloaded successfully");
    } catch (error) {
        console.error("PDF generation error:", error);
        toast.error(`PDF generation failed: ${error.message}`);
    }
};
const downloadPaymentsExcel = (data) => {
    try {
        if (typeof XLSX === "undefined") {
            throw new Error("XLSX library is not loaded");
        }

        const worksheetData = data.map((order) => {
            return {
                "Order ID": order.orderId || "",
                "Date": dateFmt(order.payment_date),
                "Customer": order.customer_name || "Walk In",
                "Order Type": order.type || "-",
                "Payment Type": order.payment_type || "-",
                "Amount Received": Number(order.amount_received || 0).toFixed(2),
                "Promo Name": order.promo_name || "-",
                "Promo Discount": Number(order.promo_discount || 0).toFixed(2),
                "Sales Discount": Number(order.sales_discount || 0).toFixed(2),
                "Approved Discount": Number(order.approved_discounts || 0).toFixed(2),
                "Tax": Number(order.tax || 0).toFixed(2),
                "Service Charges": Number(order.service_charges || 0).toFixed(2),
                "Delivery Charges": Number(order.delivery_charges || 0).toFixed(2),
                "Grand Total": Number(order.total_amount || 0).toFixed(2),
                "Status": order.status || "",
            };
        });

        const workbook = XLSX.utils.book_new();
        const worksheet = XLSX.utils.json_to_sheet(worksheetData);

        worksheet["!cols"] = [
            { wch: 10 },
            { wch: 12 },
            { wch: 20 },
            { wch: 12 },
            { wch: 12 },
            { wch: 15 },
            { wch: 20 },
            { wch: 15 },
            { wch: 15 },
            { wch: 15 },
            { wch: 10 },
            { wch: 15 },
            { wch: 15 },
            { wch: 12 },
            { wch: 10 },
            { wch: 15 }
        ];

        XLSX.utils.book_append_sheet(workbook, worksheet, "Payments");

        const totalRevenue = data.reduce((sum, o) => sum + Number(o.total_amount || 0), 0);
        const totalReceived = data.reduce((sum, o) => sum + Number(o.amount_received || 0), 0);
        const totalPromoDiscount = data.reduce((sum, o) => sum + Number(o.promo_discount || 0), 0);
        const totalSalesDiscount = data.reduce((sum, o) => sum + Number(o.sales_discount || 0), 0);

        const metaData = [
            { Info: "Generated On", Value: new Date().toLocaleString() },
            { Info: "Total Payments", Value: data.length },
            { Info: "Total Revenue", Value: `£${totalRevenue.toFixed(2)}` },
            { Info: "Total Amount Received", Value: `£${totalReceived.toFixed(2)}` },
            { Info: "Total Promo Discounts", Value: `£${totalPromoDiscount.toFixed(2)}` },
            { Info: "Total Sales Discounts", Value: `£${totalSalesDiscount.toFixed(2)}` },
            { Info: "Exported By", Value: "Payment Management System" }
        ];
        const metaSheet = XLSX.utils.json_to_sheet(metaData);
        XLSX.utils.book_append_sheet(workbook, metaSheet, "Summary");

        const fileName = `Payments_${new Date().toISOString().split("T")[0]}.xlsx`;
        XLSX.writeFile(workbook, fileName);

        toast.success("Payments Excel file downloaded successfully");
    } catch (error) {
        console.error("Excel generation error:", error);
        toast.error(`Excel generation failed: ${error.message}`);
    }
};
</script>

<template>
    <Master>

        <Head title="Payment" />
        <div class="page-wrapper">
            <!-- Title -->
            <h4 class="fw-semibold mb-3">Overall Payments</h4>

            <!-- KPI Cards -->
            <div class="row g-3">
                <!-- Total Payments -->
                <div class="col-6 col-md-4">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h3 class="mb-0 fw-bold">{{ stats.total_payments }}</h3>
                                <p class="text-muted mb-0 small">Total Paid Orders</p>
                            </div>
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
                                <h3 class="mb-0 fw-bold">{{ stats.todays_payments }}</h3>
                                <p class="text-muted mb-0 small">Today's Paid Orders</p>
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
                                <h3 class="mb-0 fw-bold">{{ formatCurrencySymbol(stats.total_amount, 'GBP') }}</h3>
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

            <div class="card border-0 shadow-lg rounded-4 mt-3">
                <div class="card-body">
                    <!-- Toolbar -->
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 fw-semibold">Payments</h5>

                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <!-- Search -->
                            <div class="search-wrap">
                                <i class="bi bi-search"></i>
                                <input type="email" name="email" autocomplete="email"
                                    style="position: absolute; left: -9999px; width: 1px; height: 1px;" tabindex="-1"
                                    aria-hidden="true" />

                                <input v-if="isReady" :id="inputId" v-model="q" :key="searchKey"
                                    class="form-control search-input" placeholder="Search by Order ID" type="search"
                                    autocomplete="new-password" :name="inputId" role="presentation"
                                    @focus="handleFocus" />
                                <input v-else class="form-control search-input" placeholder="Search by Order ID"
                                    disabled type="text" />
                            </div>
                            <FilterModal v-model="filters" title="Payments" modal-id="paymentFilterModal"
                                modal-size="modal-lg" :sort-options="filterOptions.sortOptions" :show-price-range="true"
                                :show-date-range="true" :show-stock-status="false" price-label="Amount Range"
                                @apply="handleFilterApply" @clear="handleFilterClear">
                                <!-- Custom filters slot for Payment Type -->
                                <template #customFilters="{ filters }">
                                    <div class="col-md-6">
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
                            <!-- Download all -->
                            <Dropdown v-model="exportOption" :options="exportOptions" optionLabel="label"
                                optionValue="value" placeholder="Export" class="export-dropdown"
                                @change="onExportChange" />

                        </div>
                    </div>

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="border-top small text-muted">
                                <tr>
                                    <th>S. #</th>
                                    <th>Order ID</th>
                                    <th>Actual Payment</th>
                                    <th>Promo Name</th>
                                    <th>Promo Discount</th>
                                    <th>Sales Discount</th>
                                    <th>Approved Discount</th>
                                    <th>Tax</th>
                                    <th>Service Charges</th>
                                    <th>Delivery Charges</th>
                                    <th>Grand Total</th>
                                    <th>Payment Date</th>
                                    <th>Payment Type</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>

                                <tr v-if="loading">
                                    <td colspan="14" class="text-center py-5">
                                        <div class="d-flex flex-column align-items-center justify-content-center">
                                            <div class="spinner-border mb-3" role="status"
                                                style="color: #1B1670; width: 3rem; height: 3rem; border-width: 0.3em;">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <div class="fw-semibold text-muted">Loading Payments...</div>
                                        </div>
                                    </td>
                                </tr>

                                <template v-else>
                                    <tr v-for="(p, idx) in groupedPayments" :key="p.orderId">
                                        <td>{{ (pagination.current_page - 1) * pagination.per_page + idx + 1 }}</td>
                                        <td>{{ p.orderId }}</td>
                                        <td>{{ formatCurrencySymbol(p.amountReceived) }}</td>
                                        <td>{{ p.promoName }}</td>
                                        <td class="text-success">
                                            {{ p.promoDiscount ? formatCurrencySymbol(p.promoDiscount) : '—' }}
                                        </td>
                                        <td class="text-success">
                                            {{ p.salesDiscount ? formatCurrencySymbol(p.salesDiscount) : '—' }}
                                        </td>
                                        <td class="text-success">
                                            {{ p.approvedDiscount ? formatCurrencySymbol(p.approvedDiscount) : '—' }}
                                        </td>
                                        <td>{{ formatCurrencySymbol(p.tax) }}</td>
                                        <td>{{ formatCurrencySymbol(p.serviceCharges) }}</td>
                                        <td>{{ formatCurrencySymbol(p.deliveryCharges) }}</td>
                                        <td>{{ formatCurrencySymbol(p.grandTotal) }}</td>

                                        <td>{{ dateFmt(p.paidAt) }}</td>
                                        <td class="text-capitalize">
                                            <span v-if="p.paymentTypeDisplay?.includes('Multiple')"
                                                class="badge bg-info-subtle text-info">
                                                {{ p.paymentTypeDisplay }}
                                            </span>
                                            <span v-else>{{ p.paymentTypeDisplay }}</span>
                                        </td>
                                        <td>
                                            <button @click="openDetailModal(p)" title="View Details"
                                                class="p-2 rounded-full text-primary hover:bg-gray-100">
                                                <Eye class="w-4 h-4" />
                                            </button>
                                        </td>
                                    </tr>

                                    <tr v-if="!loading && groupedPayments.length === 0">
                                        <td colspan="14" class="text-center text-muted py-4">
                                            No orders found.
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

        <!-- Detail Modal -->
        <div class="modal fade" id="paymentDetailModal" tabindex="-1" aria-labelledby="paymentDetailModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header border-0 pb-0">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-primary-subtle text-primary p-3 d-flex align-items-center justify-content-center"
                                style="width: 56px; height: 56px">
                                <i class="bi bi-credit-card fs-4"></i>
                            </div>
                            <div>
                                <h5 class="modal-title fw-bold mb-0" id="paymentDetailModalLabel">Order #{{
                                    selectedOrder?.orderId }} Payments</h5>
                                <p class="text-muted small mb-0">Total Received: {{
                                    formatCurrencySymbol(selectedOrder?.amountReceived || 0) }}</p>
                            </div>
                        </div>
                        <button type="button"
                            class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110 border-0 bg-transparent"
                            data-bs-dismiss="modal" aria-label="Close" title="Close">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                                style="width: 24px; height: 24px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="small text-muted">
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(detail, dIdx) in selectedOrder?.details" :key="dIdx">
                                        <td class="py-3">
                                            <div class="fw-semibold">{{ dateFmt(detail.paidAt) }}</div>
                                        </td>
                                        <td class="py-3 fw-bold">
                                            {{ formatCurrencySymbol(detail.amount) }}
                                        </td>
                                        <td class="py-3">
                                            <span
                                                class="badge rounded-pill bg-light dark:bg-neutral-800 text-dark dark:text-light px-3 py-2 border text-capitalize method-badge">
                                                {{ detail.type }}
                                            </span>
                                        </td>
                                        <td class="py-3">
                                            <span class="badge rounded-pill px-3 py-2 text-capitalize"
                                                :class="detail.status?.toLowerCase() === 'paid' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'">
                                                {{ detail.status }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer border-0 px-4 py-2">
                        <button type="button" class="btn btn-secondary px-4 py-2 close-btn"
                            data-bs-dismiss="modal">Close</button>
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

.close-btn:hover {
    color: #fff !important;
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

.btn-primary {
    background-color: var(--brand);
    border-color: var(--brand);
}

.btn-primary:hover {
    filter: brightness(1.05);
}

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

.dark .form-label {
    color: #fff !important;
}

.dark .form-select {
    background-color: #212121 !important;
    color: #fff !important;
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

.dark .method-badge {
    background-color: #212121 !important;
    color: #fff !important;
    border-color: #333 !important;
}

.absolute {
    position: absolute;
}

.top-2 {
    top: 0.5rem;
}

.right-2 {
    right: 0.5rem;
}

.rounded-full {
    border-radius: 9999px;
}

.p-2 {
    padding: 0.5rem;
}

.transition {
    transition-property: all;
    transition-duration: 150ms;
}

.transform {
    transform: var(--tw-transform);
}

.hover\:scale-110:hover {
    transform: scale(1.1);
}

.hover\:bg-gray-100:hover {
    background-color: #f3f4f6;
}

.dark .dark\:hover\:bg-neutral-800:hover {
    background-color: #262626;
}

:global(.dark .form-control:focus) {
    border-color: #fff !important;
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
