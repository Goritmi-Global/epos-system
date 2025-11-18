<script setup>
import Master from "@/Layouts/Master.vue";
import { Head } from "@inertiajs/vue3";
import { ref, computed, watch, onMounted, onUpdated } from "vue";
import axios from "axios";
import { useFormatters } from '@/composables/useFormatters'
import { nextTick } from "vue";
import Select from "primevue/select";
import VueDatePicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import * as XLSX from 'xlsx';

const { formatMoney, formatNumber, formatCurrencySymbol, dateFmt } = useFormatters()

/* ============= Filters State ============= */
const filters = ref({
    type: "sales",
    timeRange: "monthly",
    selectedMonth: "",
    selectedYear: "",
    selectedDate: "",
    selectedYear: "",
    dateFrom: "",
    dateTo: "",
    orderType: "",
    paymentType: "",
});

const analyticsTypeOptions = [
    { label: 'Sales Analytics', value: 'sales' },
    { label: 'Purchase Analytics', value: 'purchase' },
    { label: 'Sales vs Purchase', value: 'comparison' },
    { label: 'Stock Analysis', value: 'stock' },
    { label: 'Cashier Performance', value: 'userSales' },
    { label: 'Category Analysis', value: 'category' }
];

const timeRangeOptions = [
    { label: 'Daily', value: 'daily' },
    { label: 'Monthly', value: 'monthly' },
    { label: 'Yearly', value: 'yearly' },
    { label: 'Custom Range', value: 'custom' }
];

/* ============= API State ============= */
const loading = ref(false);
const errorMsg = ref("");
const searchKey = ref(Date.now());
const inputId = `search-${Math.random().toString(36).substr(2, 9)}`;
const isReady = ref(false);
const searchQuery = ref("");

/* ============= Response Data ============= */
const kpi = ref({});
const chartData = ref([]);
const tableData = ref([]);
const distributionData = ref([]);
const totalsSummary = ref({});

/* ============= Helper State ============= */
const lastUpdated = ref("Just now");
const dataPointsCount = ref(0);

/* ============= Months & Years Lists ============= */
const monthsList = computed(() => {
    const months = [];
    const monthNames = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];

    for (let i = 1; i <= 12; i++) {
        const monthValue = String(i).padStart(2, '0');
        months.push({
            value: monthValue,
            label: monthNames[i - 1]
        });
    }
    return months;
});

const yearsList = computed(() => {
    const currentYear = new Date().getFullYear();
    const years = [];
    for (let i = currentYear; i >= currentYear - 10; i--) {
        years.push(i);
    }
    return years;
});

/* ============= Computed Properties ============= */
const chartTitle = computed(() => {
    const titles = {
        sales: 'Sales Over Time',
        purchase: 'Purchase Over Time',
        comparison: 'Sales vs Purchase',
        stock: 'Stock Trend',
        userSales: 'Cashier Performance',
        category: 'Category Distribution'
    };
    return titles[filters.value.type] || 'Chart';
});

const periodLabel = computed(() => {
    if (filters.value.timeRange === 'daily') {
        return filters.value.selectedDate
            ? `Date: ${filters.value.selectedDate}`
            : 'Select a date';
    }
    if (filters.value.timeRange === 'monthly') {
        const monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        const monthLabel = monthNames[parseInt(filters.value.selectedMonth) - 1] || '';
        return filters.value.selectedMonth && filters.value.selectedYear
            ? `${monthLabel} ${filters.value.selectedYear}`
            : 'Select month and year';
    }
    if (filters.value.timeRange === 'yearly') {
        return filters.value.selectedYear ? `Year: ${filters.value.selectedYear}` : 'Select a year';
    }
    return filters.value.dateFrom && filters.value.dateTo
        ? `${filters.value.dateFrom} to ${filters.value.dateTo}`
        : 'Select dates';
});

/* ============= Chart Building ============= */
/* ============= Chart Building ============= */
function buildLine(series, W, H, m = { l: 50, r: 20, t: 40, b: 50 }) {
    if (!series?.length) return { paths: [], xLabels: [], yLabels: [] };

    const xs = series.map((_, i) => i);
    const ys = series.map((d) => +d.total || +d.sales || +d.purchase || +d.value || 0);
    const ysPurchase = series.map((d) => +d.purchase || 0);

    const minX = 0, maxX = Math.max(1, xs.length - 1);
    const allYValues = [...ys, ...ysPurchase].filter(v => v > 0);
    const minY = 0, maxY = Math.max(1, ...allYValues);

    const iw = Math.max(1, W - m.l - m.r);
    const ih = Math.max(1, H - m.t - m.b);

    const sx = (x) => m.l + ((x - minX) / (maxX - minX)) * iw;
    const sy = (y) => m.t + ih - ((y - minY) / (maxY - minY)) * ih;

    // Build Sales line
    let salesPath = '';
    if (xs.length === 1) {
        const x = sx(0), y = sy(ys[0]);
        salesPath = `M${x},${y} L${x + 0.01},${y}`;
    } else {
        salesPath = xs.map((x, i) => `${i ? "L" : "M"}${sx(x)},${sy(ys[i])}`).join(" ");
    }

    // Build Purchase line (for comparison type)
    let purchasePath = '';
    if (ysPurchase.some(v => v > 0)) {
        if (xs.length === 1) {
            const x = sx(0), y = sy(ysPurchase[0]);
            purchasePath = `M${x},${y} L${x + 0.01},${y}`;
        } else {
            purchasePath = xs.map((x, i) => `${i ? "L" : "M"}${sx(x)},${sy(ysPurchase[i])}`).join(" ");
        }
    }

    // X-axis labels (dates)
    // X-axis labels (dates)
    const xLabels = series.map((d, i) => {
        let label;
        if (d.date) {
            const date = new Date(d.date);
            // Check if it's a month format (YYYY-MM)
            if (d.date.length === 7 && d.date.includes('-')) {
                label = date.toLocaleDateString('en-US', { month: 'short' });
            } else {
                label = date.toLocaleDateString('en-US', { weekday: 'short' });
            }
        } else {
            label = `Day ${i + 1}`;
        }
        return {
            x: sx(i),
            y: m.t + ih + 20,
            text: label
        };
    });

    // Y-axis labels
    const ySteps = 5;
    const yLabels = [];
    for (let i = 0; i <= ySteps; i++) {
        const value = (maxY / ySteps) * i;
        yLabels.push({
            x: m.l - 10,
            y: sy(value),
            text: Math.round(value).toLocaleString()
        });
    }

    return { salesPath, purchasePath, xLabels, yLabels };
}

const chartPaths = computed(() => buildLine(chartData.value, 840, 280));

const bigLinePath = computed(() => buildLine(chartData.value, 840, 280));

/* ============= Table Filtering ============= */
const tableDataFiltered = computed(() => {
    const t = searchQuery.value.trim().toLowerCase();

    if (filters.value.type !== 'sales') {
        // For non-sales types, use original filter logic
        return t
            ? (tableData.value || []).filter((i) => {
                const searchableText = [
                    i.name, i.cashierName, i.categoryName, i.metric
                ].filter(Boolean).join(' ').toLowerCase();
                return searchableText.includes(t);
            })
            : tableData.value || [];
    }

    // For sales type, filter by customer name or order ID
    return t
        ? (tableData.value || []).filter((i) => {
            const searchableText = [
                String(i.id), i.customer_name
            ].filter(Boolean).join(' ').toLowerCase();
            return searchableText.includes(t);
        })
        : tableData.value || [];
});

/* ============= Event Handlers ============= */
const handleTimeRangeChange = () => {
    filters.value.selectedMonth = "";
    filters.value.selectedYear = "";
    filters.value.selectedDate = "";
    filters.value.dateFrom = "";
    filters.value.dateTo = "";
};

const fetchAnalytics = async () => {
    // Validate required fields
    if (filters.value.timeRange === 'daily' && !filters.value.selectedDate) {
        errorMsg.value = "Please select a date";
        return;
    }

    // UPDATE: For monthly validation - now requires both month AND year
    if (filters.value.timeRange === 'monthly' && (!filters.value.selectedMonth || !filters.value.selectedYear)) {
        errorMsg.value = "Please select both month and year";
        return;
    }
    if (filters.value.timeRange === 'monthly' && !filters.value.selectedMonth) {
        errorMsg.value = "Please select a month";
        return;
    }
    if (filters.value.timeRange === 'yearly' && !filters.value.selectedYear) {
        errorMsg.value = "Please select a year";
        return;
    }
    if (filters.value.timeRange === 'custom' && (!filters.value.dateFrom || !filters.value.dateTo)) {
        errorMsg.value = "Please select both start and end dates";
        return;
    }

    loading.value = true;
    errorMsg.value = "";

    try {
        const { data } = await axios.get("/api/analytics", {
            params: {
                type: filters.value.type,
                timeRange: filters.value.timeRange,
                selectedMonth: filters.value.selectedMonth,
                selectedYear: filters.value.selectedYear,
                selectedDate: filters.value.selectedDate,
                dateFrom: filters.value.dateFrom,
                dateTo: filters.value.dateTo,
                orderType: filters.value.orderType,
                paymentType: filters.value.paymentType,
            },
        });

        console.log("Analytics data:", data);

        // Update state from response
        kpi.value = data.kpi || {};
        chartData.value = data.chartData || [];
        tableData.value = data.tableData || [];
        distributionData.value = data.distributionData || [];
        totalsSummary.value = data.totalsSummary || {};
        dataPointsCount.value = tableData.value.length;
        lastUpdated.value = new Date().toLocaleTimeString();
    } catch (error) {
        console.error(error);
        errorMsg.value = error.response?.data?.message || "Failed to load analytics";
    } finally {
        loading.value = false;
    }
};

/* ============= Watchers ============= */
watch(
    () => [
        filters.value.type,
        filters.value.timeRange,
        filters.value.selectedMonth,
        filters.value.selectedYear,
        filters.value.selectedDate,
    ],
    () => {
        if (filters.value.type && filters.value.timeRange) {

            if (filters.value.timeRange === 'daily' && filters.value.selectedDate) {
                fetchAnalytics();
            }

            else if (filters.value.timeRange === 'monthly' && filters.value.selectedMonth && filters.value.selectedYear) {
                fetchAnalytics();
            }

            else if (filters.value.timeRange === 'yearly' && filters.value.selectedYear) {
                fetchAnalytics();
            }

            else if (filters.value.timeRange === 'custom' && filters.value.dateFrom && filters.value.dateTo) {
                fetchAnalytics();
            }
        }
    }
);

/* ============= Lifecycle ============= */
onMounted(() => window.feather?.replace());
onUpdated(() => window.feather?.replace());

onMounted(async () => {
    searchQuery.value = "";
    searchKey.value = Date.now();
    await nextTick();

    setTimeout(() => {
        isReady.value = true;
        const input = document.getElementById(inputId);
        if (input) {
            input.value = '';
            searchQuery.value = '';
        }
    }, 100);
});


/* ============= Download Methods ============= */
const downloadPDF = () => {
    try {
        const doc = new jsPDF("l", "mm", "a4"); // Landscape for more columns

        doc.setFontSize(18);
        doc.setFont("helvetica", "bold");
        doc.text(`${chartTitle.value}`, 14, 20);

        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        const currentDate = new Date().toLocaleString();
        doc.text(`Generated on: ${currentDate}`, 14, 28);
        doc.text(`Period: ${periodLabel.value}`, 14, 34);
        doc.text(`Total Records: ${tableDataFiltered.value.length}`, 14, 40);

        let tableColumns = [];
        let tableRows = [];

        if (filters.value.type === 'sales') {
            tableColumns = ["S.#", "Order ID", "Customer", "Items", "Qty", "Sub Total", "Tax",
                "Service", "Delivery", "Sales Disc.", "Approved Disc.", "Promo Disc.",
                "Promos", "Promo Type", "Total", "Date"];

            tableRows = tableDataFiltered.value.map((r, i) => [
                i + 1,
                r.id,
                r.customer_name || "-",
                r.item_count,
                r.total_qty,
                formatCurrencySymbol(r.sub_total),
                formatCurrencySymbol(r.tax),
                formatCurrencySymbol(r.service_charges),
                formatCurrencySymbol(r.delivery_charges),
                formatCurrencySymbol(r.sales_discount),
                formatCurrencySymbol(r.approved_discounts),
                formatCurrencySymbol(r.promo_discount),
                r.promo_names || "-",
                r.promo_types || "-",
                formatCurrencySymbol(r.total_amount),
                dateFmt(r.order_date)
            ]);

            // Add totals row
            if (totalsSummary.value && tableDataFiltered.value.length > 0) {
                tableRows.push([
                    "TOTAL", "-", "-",
                    totalsSummary.value.item_count,
                    totalsSummary.value.total_qty,
                    formatCurrencySymbol(totalsSummary.value.sub_total),
                    formatCurrencySymbol(totalsSummary.value.tax),
                    formatCurrencySymbol(totalsSummary.value.service_charges),
                    formatCurrencySymbol(totalsSummary.value.delivery_charges),
                    formatCurrencySymbol(totalsSummary.value.sales_discount),
                    formatCurrencySymbol(totalsSummary.value.approved_discounts),
                    formatCurrencySymbol(totalsSummary.value.promo_discount),
                    "-", "-",
                    formatCurrencySymbol(totalsSummary.value.total_amount),
                    "-"
                ]);
            }
        }

        else if (filters.value.type === 'purchase') {
            tableColumns = ["S.#", "Purchase ID", "Supplier", "Product", "Quantity",
                "Unit Price", "Sub Total", "Expiry", "Purchase Date", "Status"];

            tableRows = tableDataFiltered.value.map((r, i) => [
                i + 1,
                r.purchase_id,
                r.supplier_name || "-",
                r.product_name || "-",
                r.quantity,
                formatCurrencySymbol(r.unit_price),
                formatCurrencySymbol(r.sub_total),
                r.expiry_date ? dateFmt(r.expiry_date) : "N/A",
                dateFmt(r.purchase_date),
                r.status || "-"
            ]);

            if (totalsSummary.value && tableDataFiltered.value.length > 0) {
                tableRows.push([
                    "TOTAL", "-", "-", "-",
                    totalsSummary.value.quantity,
                    formatCurrencySymbol(totalsSummary.value.total_unit_price),
                    formatCurrencySymbol(totalsSummary.value.sub_total),
                    "-", "-", "-"
                ]);
            }
        }

        else if (filters.value.type === 'comparison') {
            tableColumns = ["S.#", "Metric", "Sales", "Purchase", "Difference"];
            tableRows = tableDataFiltered.value.map((r, i) => [
                i + 1,
                r.metric,
                formatCurrencySymbol(r.sales),
                formatCurrencySymbol(r.purchase),
                formatCurrencySymbol(r.difference)
            ]);
        }

        else if (filters.value.type === 'stock') {
            tableColumns = ["S.#", "Item", "Current Stock", "Min Level", "Status"];
            tableRows = tableDataFiltered.value.map((r, i) => [
                i + 1,
                r.name,
                r.currentStock,
                r.minLevel,
                r.currentStock > r.minLevel ? "In Stock" :
                    r.currentStock > 0 ? "Low Stock" : "Out of Stock"
            ]);
        }

        else if (filters.value.type === 'userSales') {
            tableColumns = ["S.#", "Cashier Name", "Orders", "Total Sales", "Avg Sale"];
            tableRows = tableDataFiltered.value.map((r, i) => [
                i + 1,
                r.cashierName,
                r.orderCount,
                formatCurrencySymbol(r.totalSales),
                formatCurrencySymbol(r.avgSale)
            ]);
        }

        else if (filters.value.type === 'category') {
            tableColumns = ["S.#", "Category", "Qty Sold", "Revenue", "% of Total"];
            tableRows = tableDataFiltered.value.map((r, i) => [
                i + 1,
                r.categoryName,
                r.qty,
                formatCurrencySymbol(r.revenue),
                r.percentage + "%"
            ]);
        }

        autoTable(doc, {
            head: [tableColumns],
            body: tableRows,
            startY: 46,
            styles: {
                fontSize: 7,
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
            alternateRowStyles: {
                fillColor: [245, 245, 245]
            },
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

        const fileName = `${filters.value.type}_Analytics_${new Date().toISOString().split("T")[0]}.pdf`;
        doc.save(fileName);

        // Optional: Show success message if you have toast
        // toast.success("PDF downloaded successfully");

    } catch (error) {
        console.error("PDF generation error:", error);
        errorMsg.value = `PDF generation failed: ${error.message}`;
        // Optional: Show error message if you have toast
        // toast.error(`PDF generation failed: ${error.message}`);
    }
};
const downloadExcel = () => {
    let data = [];

    if (filters.value.type === 'sales') {
        data = tableDataFiltered.value.map((r, i) => ({
            'S.#': i + 1,
            'Order ID': r.id,
            'Customer Name': r.customer_name,
            'Item Count': r.item_count,
            'Total Qty': r.total_qty,
            'Sub Total': r.sub_total,
            'Tax': r.tax,
            'Service Charge': r.service_charges,
            'Delivery Charge': r.delivery_charges,
            'Sales Discount': r.sales_discount,
            'Approved Discount': r.approved_discounts,
            'Promo Discount': r.promo_discount,
            'Applied Promos': r.promo_names,
            'Promo Type': r.promo_types,
            'Total Amount': r.total_amount,
            'Date': dateFmt(r.order_date)
        }));

        if (totalsSummary.value) {
            data.push({
                'S.#': 'TOTAL',
                'Order ID': '-',
                'Customer Name': '-',
                'Item Count': totalsSummary.value.item_count,
                'Total Qty': totalsSummary.value.total_qty,
                'Sub Total': totalsSummary.value.sub_total,
                'Tax': totalsSummary.value.tax,
                'Service Charge': totalsSummary.value.service_charges,
                'Delivery Charge': totalsSummary.value.delivery_charges,
                'Sales Discount': totalsSummary.value.sales_discount,
                'Approved Discount': totalsSummary.value.approved_discounts,
                'Promo Discount': totalsSummary.value.promo_discount,
                'Applied Promos': '-',
                'Promo Type': '-',
                'Total Amount': totalsSummary.value.total_amount,
                'Date': '-'
            });
        }
    }

    else if (filters.value.type === 'purchase') {
        data = tableDataFiltered.value.map((r, i) => ({
            'S.#': i + 1,
            'Purchase ID': r.purchase_id,
            'Supplier Name': r.supplier_name,
            'Product Name': r.product_name,
            'Quantity': r.quantity,
            'Unit Price': r.unit_price,
            'Sub Total': r.sub_total,
            'Expiry Date': r.expiry_date ? dateFmt(r.expiry_date) : 'N/A',
            'Purchase Date': dateFmt(r.purchase_date),
            'Status': r.status
        }));

        if (totalsSummary.value) {
            data.push({
                'S.#': 'TOTAL',
                'Purchase ID': '-',
                'Supplier Name': '-',
                'Product Name': '-',
                'Quantity': totalsSummary.value.quantity,
                'Unit Price': totalsSummary.value.total_unit_price,
                'Sub Total': totalsSummary.value.sub_total,
                'Expiry Date': '-',
                'Purchase Date': '-',
                'Status': '-'
            });
        }
    }

    else if (filters.value.type === 'comparison') {
        data = tableDataFiltered.value.map((r, i) => ({
            'S.#': i + 1,
            'Metric': r.metric,
            'Sales': r.sales,
            'Purchase': r.purchase,
            'Difference': r.difference
        }));
    }

    else if (filters.value.type === 'stock') {
        data = tableDataFiltered.value.map((r, i) => ({
            'S.#': i + 1,
            'Item': r.name,
            'Current Stock': r.currentStock,
            'Min Level': r.minLevel,
            'Status': r.currentStock > r.minLevel ? 'In Stock' :
                r.currentStock > 0 ? 'Low Stock' : 'Out of Stock'
        }));
    }

    else if (filters.value.type === 'userSales') {
        data = tableDataFiltered.value.map((r, i) => ({
            'S.#': i + 1,
            'Cashier Name': r.cashierName,
            'Orders': r.orderCount,
            'Total Sales': r.totalSales,
            'Avg Sale': r.avgSale
        }));
    }

    else if (filters.value.type === 'category') {
        data = tableDataFiltered.value.map((r, i) => ({
            'S.#': i + 1,
            'Category': r.categoryName,
            'Qty Sold': r.qty,
            'Revenue': r.revenue,
            '% of Total': r.percentage + '%'
        }));
    }

    // Create workbook and worksheet
    const ws = XLSX.utils.json_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, filters.value.type);

    // Save file
    XLSX.writeFile(wb, `${filters.value.type}-analytics-${Date.now()}.xlsx`);
};

</script>

<template>

    <Head title="Analytics" />
    <Master>
        <div class="page-wrapper">

            <!-- Download Section -->
            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
                <h4 class="mb-1">Analytics & Reports</h4>
                <div></div>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary rounded-pill px-4 dropdown-toggle"
                        data-bs-toggle="dropdown">
                        <i class="fas fa-download me-2"></i>Download
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow rounded-4 py-2">
                        <li>
                            <a class="dropdown-item py-2" href="javascript:void(0)" @click="downloadPDF">
                                <i class="fas fa-file-pdf me-2 text-danger"></i>Download as PDF
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="javascript:void(0)" @click="downloadExcel">
                                <i class="fas fa-file-excel me-2 text-success"></i>Download as Excel
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- KPIs - Dynamic based on Analytics Type -->


            <!-- Main Filter Section -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Analytics Type Selector - PrimeVue Select -->
                        <div class="col-md-3">
                            <label class="form-label fw-semibold text-dark">
                                <i class="fas fa-chart-bar me-2 text-muted"></i>Analytics Type
                            </label>
                            <Select v-model="filters.type" :options="analyticsTypeOptions" optionLabel="label"
                                optionValue="value" placeholder="Select Analytics Type" class="w-100" />
                        </div>

                        <!-- Time Range Selector - PrimeVue Select -->
                        <div class="col-md-3">
                            <label class="form-label fw-semibold text-dark">
                                <i class="fas fa-calendar-alt me-2 text-muted"></i>Time Range
                            </label>
                            <Select v-model="filters.timeRange" :options="timeRangeOptions" optionLabel="label"
                                optionValue="value" placeholder="Select Time Range" @change="handleTimeRangeChange"
                                class="w-100" />
                        </div>

                        <!-- DAILY: Date Picker for Daily Time Range -->
                        <!-- NEW: Add this block for daily date selection -->
                        <div v-if="filters.timeRange === 'daily'" class="col-md-3">
                            <label class="form-label fw-semibold text-dark">
                                <i class="fas fa-calendar me-2 text-muted"></i>Select Date
                            </label>
                            <VueDatePicker v-model="filters.selectedDate" placeholder="Choose Date" format="yyyy-MM-dd"
                                :enable-time-picker="false" single-calendar />
                        </div>

                        <!-- MONTHLY: Month Selector -->
                        <div v-if="filters.timeRange === 'monthly'" class="col-md-2">
                            <label class="form-label fw-semibold text-dark">
                                <i class="fas fa-calendar me-2 text-muted"></i>Select Month
                            </label>
                            <Select v-model="filters.selectedMonth" :options="monthsList" optionLabel="label"
                                optionValue="value" placeholder="Choose Month" class="w-100" />
                        </div>

                        <!-- MONTHLY: Year Selector (NEW) -->
                        <!-- NEW: Add this block for year selection in monthly view -->
                        <div v-if="filters.timeRange === 'monthly'" class="col-md-2">
                            <label class="form-label fw-semibold text-dark">
                                <i class="fas fa-calendar me-2 text-muted"></i>Select Year
                            </label>
                            <Select v-model="filters.selectedYear" :options="yearsList" placeholder="Choose Year"
                                class="w-100" />
                        </div>

                        <!-- YEARLY: Year Selector -->
                        <div v-if="filters.timeRange === 'yearly'" class="col-md-3">
                            <label class="form-label fw-semibold text-dark">
                                <i class="fas fa-calendar me-2 text-muted"></i>Select Year
                            </label>
                            <Select v-model="filters.selectedYear" :options="yearsList" placeholder="Choose Year"
                                class="w-100" />
                        </div>

                        <!-- CUSTOM: Start Date - VueDatepicker -->
                        <div v-if="filters.timeRange === 'custom'" class="col-md-2">
                            <label class="form-label fw-semibold text-dark">
                                <i class="fas fa-calendar me-2 text-muted"></i>Start Date
                            </label>
                            <VueDatePicker v-model="filters.dateFrom" placeholder="Select Start Date"
                                format="yyyy-MM-dd" />
                        </div>

                        <!-- CUSTOM: End Date - VueDatepicker -->
                        <div v-if="filters.timeRange === 'custom'" class="col-md-2">
                            <label class="form-label fw-semibold text-dark">
                                <i class="fas fa-calendar me-2 text-muted"></i>End Date
                            </label>
                            <VueDatePicker v-model="filters.dateTo" placeholder="Select End Date" format="yyyy-MM-dd" />
                        </div>

                        <!-- Apply Button - Optional (can keep commented as auto-fetch works) -->
                        <!-- <div class="col-md-2 d-flex gap-2 align-items-end">
                <button @click="fetchAnalytics" class="btn btn-primary w-100" :disabled="loading">
                    <i class="fas fa-search me-2"></i>{{ loading ? 'Loading...' : 'Apply' }}
                </button>
            </div> -->
                    </div>
                </div>
            </div>


            <div class="row g-3 mb-4">
                <!-- Sales KPIs -->
                <template v-if="filters.type === 'sales'">
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0 fw-bold">{{ formatCurrencySymbol(kpi.revenue || 0) }}</h4>
                                    <p class="text-muted mb-0 small">Total Revenue</p>
                                </div>
                                <div class="rounded-circle p-3 bg-success-subtle text-success d-flex align-items-center justify-content-center"
                                    style="width: 56px; height: 56px">
                                    <i class="bi bi-currency-pound fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0 fw-bold">{{ formatNumber(kpi.ordersCount) }}</h4>
                                    <p class="text-muted mb-0 small">Total Orders</p>
                                </div>
                                <div class="rounded-circle p-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center"
                                    style="width: 56px; height: 56px">
                                    <i class="bi bi-receipt fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0 fw-bold">{{ formatCurrencySymbol(kpi.aov || 0) }}</h4>
                                    <p class="text-muted mb-0 small">Avg. Order Value</p>
                                </div>
                                <div class="rounded-circle p-3 bg-warning-subtle text-warning d-flex align-items-center justify-content-center"
                                    style="width: 56px; height: 56px">
                                    <i class="bi bi-graph-up fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0 fw-bold">{{ formatNumber(kpi.itemsSold) }}</h4>
                                    <p class="text-muted mb-0 small">Items Sold</p>
                                </div>
                                <div class="rounded-circle p-3 bg-danger-subtle text-danger d-flex align-items-center justify-content-center"
                                    style="width: 56px; height: 56px">
                                    <i class="bi bi-basket fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Purchase KPIs -->
                <template v-if="filters.type === 'purchase'">
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0 fw-bold">{{ formatCurrencySymbol(kpi.purchaseCost || 0) }}</h4>
                                    <p class="text-muted mb-0 small">Total Purchase Cost</p>
                                </div>
                                <div class="rounded-circle p-3 bg-info-subtle text-info d-flex align-items-center justify-content-center"
                                    style="width: 56px; height: 56px">
                                    <i class="bi bi-bag-check fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0 fw-bold">{{ formatNumber(kpi.purchaseCount) }}</h4>
                                    <p class="text-muted mb-0 small">Total Purchases</p>
                                </div>
                                <div class="rounded-circle p-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center"
                                    style="width: 56px; height: 56px">
                                    <i class="bi bi-box-seam fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0 fw-bold">{{ formatCurrencySymbol(kpi.avgPurchaseValue || 0) }}</h4>
                                    <p class="text-muted mb-0 small">Avg. Purchase Value</p>
                                </div>
                                <div class="rounded-circle p-3 bg-warning-subtle text-warning d-flex align-items-center justify-content-center"
                                    style="width: 56px; height: 56px">
                                    <i class="bi bi-graph-up fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0 fw-bold">{{ formatNumber(kpi.itemsPurchased) }}</h4>
                                    <p class="text-muted mb-0 small">Items Purchased</p>
                                </div>
                                <div class="rounded-circle p-3 bg-secondary-subtle text-secondary d-flex align-items-center justify-content-center"
                                    style="width: 56px; height: 56px">
                                    <i class="bi bi-boxes fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Comparison KPIs -->
                <template v-if="filters.type === 'comparison'">
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0 fw-bold">{{ formatCurrencySymbol(kpi.salesRevenue) }}</h4>
                                    <p class="text-muted mb-0 small">Sales Revenue</p>
                                </div>
                                <div class="rounded-circle p-3 bg-success-subtle text-success d-flex align-items-center justify-content-center"
                                    style="width: 56px; height: 56px">
                                    <i class="bi bi-arrow-up-right fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0 fw-bold">{{ formatCurrencySymbol(kpi.purchaseCost) }}</h4>
                                    <p class="text-muted mb-0 small">Purchase Cost</p>
                                </div>
                                <div class="rounded-circle p-3 bg-danger-subtle text-danger d-flex align-items-center justify-content-center"
                                    style="width: 56px; height: 56px">
                                    <i class="bi bi-arrow-down-left fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0 fw-bold">{{ formatCurrencySymbol(kpi.grossProfit) }}</h4>
                                    <p class="text-muted mb-0 small">Gross Profit</p>
                                </div>
                                <div class="rounded-circle p-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center"
                                    style="width: 56px; height: 56px">
                                    <i class="bi bi-graph-up fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0 fw-bold">{{ kpi.profitMargin }}%</h4>
                                    <p class="text-muted mb-0 small">Profit Margin</p>
                                </div>
                                <div class="rounded-circle p-3 bg-info-subtle text-info d-flex align-items-center justify-content-center"
                                    style="width: 56px; height: 56px">
                                    <i class="bi bi-percent fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Stock KPIs -->
                <template v-if="filters.type === 'stock'">
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0 fw-bold">{{ formatNumber(kpi.totalStock) }}</h4>
                                    <p class="text-muted mb-0 small">Total Items in Stock</p>
                                </div>
                                <div class="rounded-circle p-3 bg-info-subtle text-info d-flex align-items-center justify-content-center"
                                    style="width: 56px; height: 56px">
                                    <i class="bi bi-box-seam fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0 fw-bold">{{ formatNumber(kpi.lowStockItems) }}</h4>
                                    <p class="text-muted mb-0 small">Low Stock Items</p>
                                </div>
                                <div class="rounded-circle p-3 bg-warning-subtle text-warning d-flex align-items-center justify-content-center"
                                    style="width: 56px; height: 56px">
                                    <i class="bi bi-exclamation-triangle fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0 fw-bold">{{ formatNumber(kpi.outOfStockItems) }}</h4>
                                    <p class="text-muted mb-0 small">Out of Stock</p>
                                </div>
                                <div class="rounded-circle p-3 bg-danger-subtle text-danger d-flex align-items-center justify-content-center"
                                    style="width: 56px; height: 56px">
                                    <i class="bi bi-x-circle fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0 fw-bold">{{ formatCurrencySymbol(kpi.stockValue) }}</h4>
                                    <p class="text-muted mb-0 small">Total Stock Value</p>
                                </div>
                                <div class="rounded-circle p-3 bg-success-subtle text-success d-flex align-items-center justify-content-center"
                                    style="width: 56px; height: 56px">
                                    <i class="bi bi-cash-coin fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- User Sales KPIs -->
                <template v-if="filters.type === 'userSales'">
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0 fw-bold">{{ formatCurrencySymbol(kpi.totalUserSales) }}</h4>
                                    <p class="text-muted mb-0 small">Total Sales</p>
                                </div>
                                <div class="rounded-circle p-3 bg-success-subtle text-success d-flex align-items-center justify-content-center"
                                    style="width: 56px; height: 56px">
                                    <i class="bi bi-currency-pound fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0 fw-bold">{{ formatCurrencySymbol(kpi.avgCashierSales) }}</h4>
                                    <p class="text-muted mb-0 small">Avg. per Cashier</p>
                                </div>
                                <div class="rounded-circle p-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center"
                                    style="width: 56px; height: 56px">
                                    <i class="bi bi-person-check fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0 fw-bold">{{ formatNumber(kpi.activeCashiers) }}</h4>
                                    <p class="text-muted mb-0 small">Active Cashiers</p>
                                </div>
                                <div class="rounded-circle p-3 bg-info-subtle text-info d-flex align-items-center justify-content-center"
                                    style="width: 56px; height: 56px">
                                    <i class="bi bi-people fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0 fw-bold">{{ formatCurrencySymbol(kpi.topCashierSales) }}</h4>
                                    <p class="text-muted mb-0 small">Top Cashier Sales</p>
                                </div>
                                <div class="rounded-circle p-3 bg-warning-subtle text-warning d-flex align-items-center justify-content-center"
                                    style="width: 56px; height: 56px">
                                    <i class="bi bi-star fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Category KPIs -->
                <template v-if="filters.type === 'category'">
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0 fw-bold">{{ formatNumber(kpi.totalCategories) }}</h4>
                                    <p class="text-muted mb-0 small">Total Categories</p>
                                </div>
                                <div class="rounded-circle p-3 bg-info-subtle text-info d-flex align-items-center justify-content-center"
                                    style="width: 56px; height: 56px">
                                    <i class="bi bi-tag fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0 fw-bold">{{ formatCurrencySymbol(kpi.categoryRevenue) }}</h4>
                                    <p class="text-muted mb-0 small">Total Revenue</p>
                                </div>
                                <div class="rounded-circle p-3 bg-success-subtle text-success d-flex align-items-center justify-content-center"
                                    style="width: 56px; height: 56px">
                                    <i class="bi bi-currency-pound fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0 fw-bold">{{ kpi.topCategory }}</h4>
                                    <p class="text-muted mb-0 small">Top Category</p>
                                </div>
                                <div class="rounded-circle p-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center"
                                    style="width: 56px; height: 56px">
                                    <i class="bi bi-trophy fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0 fw-bold">{{ formatNumber(kpi.totalCategorySales) }}</h4>
                                    <p class="text-muted mb-0 small">Total Items Sold</p>
                                </div>
                                <div class="rounded-circle p-3 bg-warning-subtle text-warning d-flex align-items-center justify-content-center"
                                    style="width: 56px; height: 56px">
                                    <i class="bi bi-bag-check fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            <!-- Error Alert -->
            <div v-if="errorMsg" class="alert alert-warning border-0 rounded-4 shadow-sm">
                {{ errorMsg }}
            </div>
            <!-- Detailed Table -->
            <div class="card border-0 shadow-sm rounded-4 mt-3">
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                        <h6 class="fw-semibold mb-0">Detailed Data</h6>
                        <div class="search-wrap">
                            <i class="bi bi-search"></i>
                            <input v-if="isReady" :id="inputId" v-model="searchQuery" :key="searchKey"
                                class="form-control search-input" placeholder="Search" type="search"
                                autocomplete="new-password" :name="inputId" role="presentation" />
                            <input v-else class="form-control search-input" placeholder="Search" disabled type="text" />
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="small text-muted">
                                <tr>
                                    <th style="width: 60px">S.#</th>

                                    <template v-if="filters.type === 'sales'">
                                        <th>Order ID</th>
                                        <th>Customer Name</th>
                                        <th style="width: 100px">Item Count</th>
                                        <th style="width: 100px">Total Qty</th>
                                        <th style="width: 120px">Sub Total</th>
                                        <th style="width: 120px">Tax</th>
                                        <th style="width: 120px">Service Charge</th>
                                        <th style="width: 120px">Delivery Charge</th>
                                        <th style="width: 120px">Sale Discount</th>
                                        <th style="width: 120px">Approved Discount</th>
                                        <th style="width: 140px">Promo Discount</th>
                                        <th style="width: 150px">Applied Promos</th>
                                        <th style="width: 100px">Promo Type</th>
                                        <th style="width: 140px">Total Amount</th>
                                        <th style="width: 100px">Date</th>
                                    </template>

                                    <template v-if="filters.type === 'purchase'">
                                        <th style="width: 100px">Purchase ID</th>
                                        <th style="width: 150px">Supplier Name</th>
                                        <th style="width: 150px">Product Name</th>
                                        <th style="width: 100px">Quantity</th>
                                        <th style="width: 120px">Unit Price</th>
                                        <th style="width: 140px">Sub Total</th>
                                        <th style="width: 130px">Expiry Date</th>
                                        <th style="width: 120px">Purchase Date</th>
                                        <th style="width: 100px">Status</th>
                                    </template>

                                    <template v-if="filters.type === 'comparison'">
                                        <th>Metric</th>
                                        <th style="width: 140px">Sales</th>
                                        <th style="width: 140px">Purchase</th>
                                        <th style="width: 140px">Difference</th>
                                    </template>

                                    <template v-if="filters.type === 'stock'">
                                        <th>Item</th>
                                        <th style="width: 100px">Stock</th>
                                        <th style="width: 100px">Min Level</th>
                                        <th style="width: 120px">Status</th>
                                    </template>

                                    <template v-if="filters.type === 'userSales'">
                                        <th>Cashier Name</th>
                                        <th style="width: 120px">Orders</th>
                                        <th style="width: 140px">Total Sales</th>
                                        <th style="width: 120px">Avg Sale</th>
                                    </template>

                                    <template v-if="filters.type === 'category'">
                                        <th>Category</th>
                                        <th style="width: 120px">Qty Sold</th>
                                        <th style="width: 140px">Revenue</th>
                                        <th style="width: 100px">% of Total</th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(r, i) in tableDataFiltered" :key="i">
                                    <td>{{ i + 1 }}</td>
                                    <!-- Sales Row -->
                                    <template v-if="filters.type === 'sales'">
                                        <td class="fw-semibold">{{ r.id }}</td>
                                        <td>{{ r.customer_name }}</td>
                                        <td>{{ formatNumber(r.item_count) }}</td>
                                        <td>{{ formatNumber(r.total_qty) }}</td>
                                        <td>{{ formatCurrencySymbol(r.sub_total) }}</td>
                                        <td>{{ formatCurrencySymbol(r.tax) }}</td>
                                        <td>{{ formatCurrencySymbol(r.service_charges) }}</td>
                                        <td>{{ formatCurrencySymbol(r.delivery_charges) }}</td>
                                        <td>{{ formatCurrencySymbol(r.sales_discount) }}</td>
                                        <td>{{ formatCurrencySymbol(r.approved_discounts) }}</td>
                                        <td>{{ formatCurrencySymbol(r.promo_discount) }}</td>
                                        <td class="small">{{ r.promo_names }}</td>
                                        <td class="small">{{ r.promo_types }}</td>
                                        <td class="fw-bold text-success">{{ formatCurrencySymbol(r.total_amount) }}</td>
                                        <td class="small">{{ dateFmt(r.order_date) }}</td>
                                    </template>
                                    <!-- Purchase Row -->
                                    <template v-if="filters.type === 'purchase'">
                                        <td class="fw-semibold">{{ r.purchase_id }}</td>
                                        <td>{{ r.supplier_name }}</td>
                                        <td class="fw-semibold">{{ r.product_name }}</td>
                                        <td>{{ formatNumber(r.quantity) }}</td>
                                        <td>{{ formatCurrencySymbol(r.unit_price) }}</td>
                                        <td>{{ formatCurrencySymbol(r.sub_total) }}</td>
                                        <td class="small">
                                            <span v-if="r.expiry_date" class="text-muted">
                                                {{ dateFmt(r.expiry_date) }}
                                            </span>
                                            <span v-else class="text-secondary">N/A</span>
                                        </td>
                                        <td class="small">{{ dateFmt(r.purchase_date) }}</td>
                                        <td>
                                            <span v-if="r.status === 'completed'"
                                                class="badge bg-success-subtle text-success px-3 py-2 rounded-pill">
                                                Completed
                                            </span>

                                            <span v-else-if="r.status === 'pending'"
                                                class="badge bg-warning-subtle text-warning px-3 py-2 rounded-pill">
                                                Pending
                                            </span>

                                            <span v-else
                                                class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-pill">
                                                {{ r.status }}
                                            </span>
                                        </td>

                                    </template>
                                    <!-- Comparison Row -->
                                    <template v-if="filters.type === 'comparison'">
                                        <td class="fw-semibold">{{ r.metric }}</td>
                                        <td>{{ formatCurrencySymbol(r.sales) }}</td>
                                        <td>{{ formatCurrencySymbol(r.purchase) }}</td>
                                        <td :style="{ color: r.difference > 0 ? '#10b981' : '#ef4444' }"
                                            class="fw-semibold">
                                            {{ formatCurrencySymbol(r.difference) }}
                                        </td>
                                    </template>
                                    <!-- Stock Row -->
                                    <template v-if="filters.type === 'stock'">
                                        <td class="fw-semibold">{{ r.name }}</td>
                                        <td>{{ formatNumber(r.currentStock) }}</td>
                                        <td>{{ formatNumber(r.minLevel) }}</td>
                                        <td>
                                            <span v-if="r.currentStock > r.minLevel"
                                                class="badge bg-success-subtle text-success">
                                                In Stock
                                            </span>
                                            <span v-else-if="r.currentStock > 0"
                                                class="badge bg-warning-subtle text-warning">
                                                Low Stock
                                            </span>
                                            <span v-else class="badge bg-danger-subtle text-danger">
                                                Out of Stock
                                            </span>
                                        </td>
                                    </template>
                                    <!-- User Sales Row -->
                                    <template v-if="filters.type === 'userSales'">
                                        <td class="fw-semibold">{{ r.cashierName }}</td>
                                        <td>{{ formatNumber(r.orderCount) }}</td>
                                        <td>{{ formatCurrencySymbol(r.totalSales) }}</td>
                                        <td>{{ formatCurrencySymbol(r.avgSale) }}</td>
                                    </template>
                                    <!-- Category Row -->
                                    <template v-if="filters.type === 'category'">
                                        <td class="fw-semibold">{{ r.categoryName }}</td>
                                        <td>{{ formatNumber(r.qty) }}</td>
                                        <td>{{ formatCurrencySymbol(r.revenue) }}</td>
                                        <td>{{ r.percentage }}%</td>
                                    </template>
                                </tr>
                                <tr v-if="filters.type === 'sales' && tableDataFiltered.length > 0"
                                    class="table-active fw-bold border-top border-3">
                                    <td colspan="0" class="text-end">TOTALS</td>
                                    <td colspan="0">-</td>
                                    <td colspan="0">-</td>
                                    <td>{{ formatNumber(totalsSummary.item_count) }}</td>
                                    <td>{{ formatNumber(totalsSummary.total_qty) }}</td>
                                    <td>{{ formatCurrencySymbol(totalsSummary.sub_total) }}</td>
                                    <td>{{ formatCurrencySymbol(totalsSummary.tax) }}</td>
                                    <td>{{ formatCurrencySymbol(totalsSummary.service_charges) }}</td>
                                    <td>{{ formatCurrencySymbol(totalsSummary.delivery_charges) }}</td>
                                    <td>{{ formatCurrencySymbol(totalsSummary.sales_discount +
                                        totalsSummary.approved_discounts) }}
                                    </td>
                                    <td>{{ formatCurrencySymbol(totalsSummary.approved_discounts) }}</td>
                                    <td>{{ formatCurrencySymbol(totalsSummary.promo_discount) }}</td>

                                    <td>-</td>
                                    <td>-</td>
                                    <td class="text-success">{{ formatCurrencySymbol(totalsSummary.total_amount) }}</td>
                                    <td></td>

                                </tr>

                                <tr v-if="filters.type === 'purchase' && tableDataFiltered.length > 0"
                                    class="table-active fw-bold border-top border-3">
                                    <td colspan="0">TOTALS</td>
                                    <td>-</td>
                                    <td>-</td>
                                    <td>-</td>
                                    <td>{{ formatNumber(totalsSummary.quantity) }}</td>
                                    <td>{{ formatCurrencySymbol(totalsSummary.total_unit_price) }}</td>
                                    <td>{{ formatCurrencySymbol(totalsSummary.sub_total) }}</td>
                                    <td colspan="3"></td>
                                </tr>

                                <tr v-if="!loading && tableDataFiltered.length === 0">
                                    <td :colspan="16" class="text-center text-muted py-4">
                                        No data available for this period.
                                    </td>
                                </tr>
                                <tr v-if="loading">
                                    <td :colspan="5" class="text-center text-muted py-4">
                                        <i class="fas fa-spinner fa-spin me-2"></i>Loading…
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Charts row -->
            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body">
                            <h6 class="fw-semibold mb-2">{{ chartTitle }}</h6>
                            <div class="chart">
                                <svg viewBox="0 0 840 280">
                                    <!-- Y-axis -->
                                    <line x1="50" y1="40" x2="50" y2="230" stroke="#4b5563" stroke-width="1" />
                                    <!-- X-axis -->
                                    <line x1="50" y1="230" x2="820" y2="230" stroke="#4b5563" stroke-width="1" />

                                    <!-- Grid lines -->
                                    <g v-for="i in 5" :key="'grid' + i">
                                        <line :x1="50" :x2="820" :y1="230 - i * 38" :y2="230 - i * 38" stroke="#374151"
                                            stroke-width="0.5" opacity="0.3" />
                                    </g>

                                    <!-- Y-axis labels -->
                                    <text v-for="(label, i) in chartPaths.yLabels" :key="'ylabel' + i" :x="label.x"
                                        :y="label.y + 5" text-anchor="end" fill="#9ca3af" font-size="11">
                                        {{ label.text }}
                                    </text>

                                    <!-- X-axis labels -->
                                    <text v-for="(label, i) in chartPaths.xLabels" :key="'xlabel' + i" :x="label.x"
                                        :y="label.y" text-anchor="middle" fill="#9ca3af" font-size="11">
                                        {{ label.text }}
                                    </text>

                                    <!-- Sales line (green) -->
                                    <path v-if="chartPaths.salesPath" :d="chartPaths.salesPath" fill="none"
                                        stroke="#10b981" stroke-width="3" stroke-linecap="round" />

                                    <!-- Purchase line (red) - only for comparison -->
                                    <path v-if="filters.type === 'comparison' && chartPaths.purchasePath"
                                        :d="chartPaths.purchasePath" fill="none" stroke="#ef4444" stroke-width="3"
                                        stroke-linecap="round" />

                                    <!-- Legend -->
                                    <g v-if="filters.type === 'comparison'">
                                        <circle cx="750" cy="20" r="5" fill="#10b981" />
                                        <text x="760" y="25" fill="#9ca3af" font-size="12">Sales</text>

                                        <circle cx="820" cy="20" r="5" fill="#ef4444" />
                                        <text x="830" y="25" fill="#9ca3af" font-size="12">Purchase</text>
                                    </g>

                                    <!-- No data message -->
                                    <text v-if="!chartData.length" x="430" y="140" text-anchor="middle" fill="#6b7280">
                                        No data available
                                    </text>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 mb-3">
                        <div class="card-body">
                            <h6 class="fw-semibold mb-3">Distribution</h6>
                            <div v-for="item in distributionData" :key="item.label" class="stack">
                                <div class="stack-row">
                                    <span>{{ item.label }}</span>
                                    <span class="stack-val">{{ item.value }} ({{ item.percentage }}%)</span>
                                </div>
                                <div class="progress thin">
                                    <div class="progress-bar" :style="{
                                        width: item.percentage + '%',
                                        backgroundColor: item.color
                                    }"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body">
                            <h6 class="fw-semibold mb-3">Summary Info</h6>
                            <div class="stack">
                                <div class="stack-row">
                                    <span>Period</span>
                                    <span class="stack-val">{{ dateFmt(periodLabel) }}</span>
                                </div>
                                <div class="stack-row">
                                    <span>Data Points</span>
                                    <span class="stack-val">{{ dataPointsCount }}</span>
                                </div>
                                <div class="stack-row">
                                    <span>Last Updated</span>
                                    <span class="stack-val">{{ lastUpdated }}</span>
                                </div>
                                <div class="stack-row">
                                    <span>Status</span>
                                    <span class="stack-val" :style="{ color: loading ? '#f59e0b' : '#10b981' }">
                                        {{ loading ? 'Loading...' : 'Active' }}
                                    </span>
                                </div>
                            </div>
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
    color: #ffffff !important;
}

.chart svg text {
    fill: #9ca3af;
}

.dark .chart svg text {
    fill: #ffffff;
}

.dark .chart svg line {
    stroke: #6b7280;
}

.dark .chart svg line.grid {
    stroke: #4b5563;
}

.dark .table {
    background-color: #181818 !important;
    color: #f9fafb !important;
}

.dark .table thead {
    background-color: #181818 !important;
    color: #ffffff;
}

.dark .form-label {
    color: #fff !important;
}

.dark .form-select {
    background-color: #212121 !important;
    color: #fff !important;
}

:root {
    --brand: #1c0d82;
}

.chart {
    width: 100%;
}

.axis {
    stroke: #0f172a;
    stroke-width: 1.5;
}

.grid {
    stroke: #e5e7eb;
    stroke-width: 1;
}

.line {
    fill: none;
    stroke: #1c0d82;
    stroke-width: 2.5;
}

.muted {
    fill: #6b7280;
    font-size: 12px;
}

.progress.thin {
    height: 8px;
    background: #eef2ff;
}

.stack-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.stack-val {
    color: #6b7280;
}

.search-wrap {
    position: relative;
    width: clamp(220px, 30vw, 360px);
}

.search-wrap .bi-search {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
}

.search-input {
    padding-left: 36px;
    border-radius: 9999px;
}

.table thead th {
    font-weight: 600;
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

/* .dark svg{
    color: #fff !important;
} */


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
    background: #212121 !important;
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



/* Chip remove (x) icon */
:global(.dark .p-multiselect-chip .p-chip-remove-icon) {
    color: #fff !important;
}

:global(.dark .p-multiselect-chip .p-chip-remove-icon:hover) {
    color: #f87171 !important;
    /* lighter red */
}

/* ==================== Dark Mode Select Styling ====================== */
:deep(.p-select) {
    background-color: white !important;
    color: black !important;
    border-color: #9b9c9c
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


.dark .logo-card {
    background-color: #181818 !important;
}

.dark .logo-frame {
    background-color: #181818 !important;
}


@media (max-width: 576px) {
    .search-wrap {
        width: 100%;
    }
}
</style>