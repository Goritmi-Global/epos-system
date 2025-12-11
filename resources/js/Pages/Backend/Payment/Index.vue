<script setup>
import Master from "@/Layouts/Master.vue";
import { Head } from "@inertiajs/vue3";
import { ref, computed, onMounted, onUpdated } from "vue";
import Select from "primevue/select";
import { useFormatters } from '@/composables/useFormatters'
import FilterModal from "@/Components/FilterModal.vue";
import { nextTick } from "vue";
import { toast } from "vue3-toastify";
import { jsPDF } from "jspdf";
import autoTable from "jspdf-autotable";
import * as XLSX from "xlsx";


const { formatMoney, formatCurrencySymbol, formatNumber, dateFmt } = useFormatters()
const orders = ref([]);

const fetchOrdersWithPayment = async () => {
    try {
        const response = await axios.get("/api/orders/all");
        orders.value = response.data.data;
    } catch (error) {
        console.error("Error fetching orders:", error);
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
    const filterModal = document.getElementById('paymentFilterModal');
    if (filterModal) {
        filterModal.addEventListener('hidden.bs.modal', () => {
            filters.value = {
                sortBy: appliedFilters.value.sortBy || "",
                paymentType: appliedFilters.value.paymentType || "",
                dateFrom: appliedFilters.value.dateFrom || "",
                dateTo: appliedFilters.value.dateTo || "",
                priceMin: appliedFilters.value.priceMin || null,
                priceMax: appliedFilters.value.priceMax || null,
            };
        });
    }
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
            user: o.user?.name || "—",
            type: o.payment?.payment_type || "—",
            serviceCharges: Number(o.service_charges || 0),
            deliveryCharges: Number(o.delivery_charges || 0),
            tax: Number(o.tax || 0),
            amountReceived: Number(o.sub_total || 0),
            promoDiscount: promo ? Number(promo.discount_amount || 0) : 0,
            salesDiscount: Number(o.sales_discount || 0),
            approvedDiscount: Number(o.approved_discounts || 0),
            grandTotal: Number(o.total_amount || 0),
            promoName: promo ? promo.promo_name : "—",
            paidAt: o.payment?.payment_date || null,
            status: o.status,
        };
    })
);

const filtered = computed(() => {
    const term = q.value.trim().toLowerCase();
    let result = [...payments.value];

    // Text search
    if (term) {
        result = result.filter((p) =>
            [
                String(p.orderId),
                p.customer || "",
                p.user || "",
                p.type || "",
                String(p.grandTotal),
                formatDateTime(p.paidAt),
            ]
                .join(" ")
                .toLowerCase()
                .includes(term)
        );
    }

    // Filter by payment type
    if (filters.value.paymentType) {
        result = result.filter(
            (p) =>
                (p.type ?? "").toLowerCase() ===
                filters.value.paymentType.toLowerCase()
        );
    }

    // Filter by price range (grand total)
    if (filters.value.priceMin !== null && filters.value.priceMin !== "") {
        result = result.filter((p) => {
            const amount = Number(p.grandTotal) || 0;
            return amount >= Number(filters.value.priceMin);
        });
    }
    if (filters.value.priceMax !== null && filters.value.priceMax !== "") {
        result = result.filter((p) => {
            const amount = Number(p.grandTotal) || 0;
            return amount <= Number(filters.value.priceMax);
        });
    }

    // Date range filter
    if (filters.value.dateFrom) {
        result = result.filter((p) => {
            if (!p.paidAt) return false;
            const paymentDate = new Date(p.paidAt);
            const filterDate = new Date(filters.value.dateFrom);
            filterDate.setHours(0, 0, 0, 0);
            return paymentDate >= filterDate;
        });
    }

    if (filters.value.dateTo) {
        result = result.filter((p) => {
            if (!p.paidAt) return false;
            const paymentDate = new Date(p.paidAt);
            const filterDate = new Date(filters.value.dateTo);
            filterDate.setHours(23, 59, 59, 999);
            return paymentDate <= filterDate;
        });
    }

    // Apply sorting
    if (filters.value.sortBy) {
        result = [...result]; // Create new array for sorting
        switch (filters.value.sortBy) {
            case "date_desc":
                result.sort((a, b) => new Date(b.paidAt) - new Date(a.paidAt));
                break;
            case "date_asc":
                result.sort((a, b) => new Date(a.paidAt) - new Date(b.paidAt));
                break;
            case "amount_desc":
                result.sort((a, b) => Number(b.grandTotal) - Number(a.grandTotal));
                break;
            case "amount_asc":
                result.sort((a, b) => Number(a.grandTotal) - Number(b.grandTotal));
                break;
            case "order_asc":
                result.sort((a, b) => Number(a.orderId) - Number(b.orderId));
                break;
            case "order_desc":
                result.sort((a, b) => Number(b.orderId) - Number(a.orderId));
                break;
        }
    }

    return result;
});

const handleFilterApply = (appliedFilters) => {
    filters.value = { ...filters.value, ...appliedFilters };
    appliedFilters.value = { ...filters.value };
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

    return payments.value.filter((p) => {
        if (!p.paidAt) return false;
        const dt = new Date(p.paidAt);
        return dt >= start && dt < end;
    }).length;
});

const totalAmount = computed(() =>
    payments.value.reduce((sum, p) => sum + Number(p.amount || 0), 0)
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

const onDownload = (type) => {
    if (!orders.value || orders.value.length === 0) {
        toast.error("No payment data to download");
        return;
    }

    // Filter only orders with payment information
    const paymentsData = orders.value.filter(o => o.payment);

    if (paymentsData.length === 0) {
        toast.error("No payments found to download");
        return;
    }

    try {
        if (type === "pdf") {
            downloadPaymentsPDF(paymentsData);
        } else if (type === "excel") {
            downloadPaymentsExcel(paymentsData);
        } else if (type === "csv") {
            downloadPaymentsCSV(paymentsData);
        } else {
            toast.error("Invalid download type");
        }
    } catch (error) {
        console.error("Download failed:", error);
        toast.error(`Download failed: ${error.message}`);
    }
};

// CSV Download for Payments
const downloadPaymentsCSV = (data) => {
    try {
        const headers = [
            "Order ID",
            "Date",
            "Customer",
            "Order Type",
            "Payment Type",
            "Amount Received",
            "Cash Amount",
            "Card Amount",
            "Card Brand",
            "Last 4 Digits",
            "Currency",
            "Order Total",
            "Status"
        ];

        const rows = data.map((order) => {
            const payment = order.payment || {};
            return [
                `"${order.id || ""}"`,
                `"${dateFmt(order.created_at)}"`,
                `"${order.customer_name || "Walk In"}"`,
                `"${order.type?.order_type || "-"}"`,
                `"${payment.payment_type || "-"}"`,
                `"${Number(payment.amount_received || 0).toFixed(2)}"`,
                `"${Number(payment.cash_amount || 0).toFixed(2)}"`,
                `"${Number(payment.card_amount || 0).toFixed(2)}"`,
                `"${payment.brand || "-"}"`,
                `"${payment.last_digits || "-"}"`,
                `"${payment.currency_code || "GBP"}"`,
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

// PDF Download for Payments
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

        // Calculate totals
        const totalAmount = data.reduce((sum, o) => sum + Number(o.total_amount || 0), 0);
        const totalCash = data.reduce((sum, o) => sum + Number(o.payment?.cash_amount || 0), 0);
        const totalCard = data.reduce((sum, o) => sum + Number(o.payment?.card_amount || 0), 0);

        doc.text(`Total Revenue: £${totalAmount.toFixed(2)}`, 14, 40);
        doc.text(`Cash: £${totalCash.toFixed(2)} | Card: £${totalCard.toFixed(2)}`, 14, 46);

        const tableColumns = [
            "Order ID",
            "Date",
            "Customer",
            "Order Type",
            "Payment Type",
            "Amount",
            "Cash",
            "Card",
            "Brand",
            "Status"
        ];

        const tableRows = data.map((order) => {
            const payment = order.payment || {};
            return [
                order.id || "",
                dateFmt(order.created_at),
                order.customer_name || "Walk In",
                order.type?.order_type || "-",
                payment.payment_type || "-",
                `£${Number(payment.amount_received || 0).toFixed(2)}`,
                `£${Number(payment.cash_amount || 0).toFixed(2)}`,
                `£${Number(payment.card_amount || 0).toFixed(2)}`,
                payment.brand || "-",
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

// Excel Download for Payments
const downloadPaymentsExcel = (data) => {
    try {
        if (typeof XLSX === "undefined") {
            throw new Error("XLSX library is not loaded");
        }

        const worksheetData = data.map((order) => {
            const payment = order.payment || {};
            return {
                "Order ID": order.id || "",
                "Date": dateFmt(order.created_at),
                "Time": (order.created_at),
                "Customer": order.customer_name || "Walk In",
                "Order Type": order.type?.order_type || "-",
                "Table Number": order.type?.table_number || "-",
                "Payment Type": payment.payment_type || "-",
                "Amount Received": Number(payment.amount_received || 0).toFixed(2),
                "Cash Amount": Number(payment.cash_amount || 0).toFixed(2),
                "Card Amount": Number(payment.card_amount || 0).toFixed(2),
                "Card Brand": payment.brand || "-",
                "Last 4 Digits": payment.last_digits || "-",
                "Exp Month": payment.exp_month || "-",
                "Exp Year": payment.exp_year || "-",
                "Currency": payment.currency_code || "GBP",
                "Order Total": Number(order.total_amount || 0).toFixed(2),
                "Status": order.status || "",
                "Refund Status": payment.refund_status || "None"
            };
        });

        const workbook = XLSX.utils.book_new();
        const worksheet = XLSX.utils.json_to_sheet(worksheetData);

        worksheet["!cols"] = [
            { wch: 10 },  // Order ID
            { wch: 12 },  // Date
            { wch: 15 },  // Time
            { wch: 20 },  // Customer
            { wch: 12 },  // Order Type
            { wch: 12 },  // Table Number
            { wch: 12 },  // Payment Type
            { wch: 15 },  // Amount Received
            { wch: 12 },  // Cash Amount
            { wch: 12 },  // Card Amount
            { wch: 12 },  // Card Brand
            { wch: 12 },  // Last 4 Digits
            { wch: 10 },  // Exp Month
            { wch: 10 },  // Exp Year
            { wch: 10 },  // Currency
            { wch: 12 },  // Order Total
            { wch: 10 },  // Status
            { wch: 15 }   // Refund Status
        ];

        XLSX.utils.book_append_sheet(workbook, worksheet, "Payments");

        // Add summary sheet
        const totalRevenue = data.reduce((sum, o) => sum + Number(o.total_amount || 0), 0);
        const totalCash = data.reduce((sum, o) => sum + Number(o.payment?.cash_amount || 0), 0);
        const totalCard = data.reduce((sum, o) => sum + Number(o.payment?.card_amount || 0), 0);
        const cashPayments = data.filter(o => o.payment?.payment_type?.toLowerCase() === 'cash').length;
        const cardPayments = data.filter(o => o.payment?.payment_type?.toLowerCase() === 'card').length;
        const splitPayments = data.filter(o => o.payment?.payment_type?.toLowerCase() === 'split').length;

        const metaData = [
            { Info: "Generated On", Value: new Date().toLocaleString() },
            { Info: "Total Payments", Value: data.length },
            { Info: "Total Revenue", Value: `£${totalRevenue.toFixed(2)}` },
            { Info: "Total Cash", Value: `£${totalCash.toFixed(2)}` },
            { Info: "Total Card", Value: `£${totalCard.toFixed(2)}` },
            { Info: "Cash Payments Count", Value: cashPayments },
            { Info: "Card Payments Count", Value: cardPayments },
            { Info: "Split Payments Count", Value: splitPayments },
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
                                <h3 class="mb-0 fw-bold">{{ formatCurrencySymbol(totalAmount, 'GBP') }}</h3>
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
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary rounded-pill px-4 dropdown-toggle"
                                    data-bs-toggle="dropdown">
                                    Export
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow rounded-4 py-2">
                                    <li>
                                        <a class="dropdown-item py-2" href="javascript:;" @click="onDownload('pdf')">
                                            Export as PDF
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2" href="javascript:;" @click="onDownload('excel')">
                                            Export as Excel
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2" href="javascript:;" @click="onDownload('csv')">
                                            Export as CSV
                                        </a>
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
                                </tr>
                            </thead>

                            <tbody>
                                <tr v-for="(p, idx) in filtered" :key="p.orderId">
                                    <td>{{ idx + 1 }}</td>
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
                                    <td class="text-capitalize">{{ p.type }}</td>
                                </tr>

                                <tr v-if="filtered.length === 0">
                                    <td colspan="13" class="text-center text-muted py-4">
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
