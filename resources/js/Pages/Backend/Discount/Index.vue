<script setup>
import Master from "@/Layouts/Master.vue"; import { ref, computed, onMounted, onUpdated, onUnmounted, reactive } from "vue";
import { Percent, Calendar, AlertTriangle, XCircle, Pencil, Plus, Trash2 } from "lucide-vue-next";
import { toast } from "vue3-toastify";
import axios from "axios";
import Select from "primevue/select";
import ConfirmModal from "@/Components/ConfirmModal.vue";
import { useFormatters } from '@/composables/useFormatters'
import { nextTick } from "vue";
import { Head } from "@inertiajs/vue3";

// Import PrimeVue Tabs
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';

import { jsPDF } from "jspdf";
import autoTable from "jspdf-autotable";
import * as XLSX from "xlsx";
import ImportFile from "@/Components/importFile.vue";
import FilterModal from "@/Components/FilterModal.vue";
const { formatMoney, formatCurrencySymbol, formatNumber, dateFmt } = useFormatters()

/* ============================================================
   DATA STATE
   ============================================================ */

// Discounts list and editing state
const discounts = ref([]);
const editingDiscount = ref(null);
const submitting = ref(false);
const discountFormErrors = ref({});

// Discount Approvals
const pendingRequests = ref([]);
const loadingApprovals = ref(false);
const processing = reactive({});
const approvalNotes = reactive({});
const showOrderItems = reactive({});
const refreshInterval = ref(null);
const confirmModalKey = ref(0);

// Discount type and status options for dropdowns
const discountOptions = [
    // { label: "Flat Amount", value: "flat" },
    { label: "Percentage", value: "percent" },
];

const statusOptions = [
    { label: "Active", value: "active" },
    { label: "Deactive", value: "inactive" },
];

// Form state for creating/editing discounts
const discountForm = ref({
    name: "",
    type: "percent",
    status: "active",
    start_date: "",
    end_date: "",
    min_purchase: 0,
    max_discount: null,
    description: "",
    discount_amount: null,
});

// Search and filter state
const q = ref("");
const searchKey = ref(Date.now());
const inputId = `search-${Math.random().toString(36).substr(2, 9)}`;
const isReady = ref(false);

const discountTypesForFilter = computed(() => [
    { id: "flat", name: "Flat" },
    { id: "percent", name: "Percentage" }
]);

const filters = ref({
    sortBy: "",
    stockStatus: "",
    priceMin: null,
    priceMax: null,
    dateFrom: null,
    dateTo: null
});

const filtered = computed(() => {
    let result = discounts.value;
    const t = q.value.trim().toLowerCase();
    if (t) {
        result = result.filter((d) => d.name.toLowerCase().includes(t));
    }

    if (filters.value.stockStatus) {
        result = result.filter((d) => d.status === filters.value.stockStatus);
    }

    if (filters.value.category) {
        result = result.filter((d) => d.type === filters.value.category);
    }

    if (filters.value.priceMin !== null && filters.value.priceMin !== "") {
        result = result.filter((d) => parseFloat(d.discount_amount) >= parseFloat(filters.value.priceMin));
    }
    if (filters.value.priceMax !== null && filters.value.priceMax !== "") {
        result = result.filter((d) => parseFloat(d.discount_amount) <= parseFloat(filters.value.priceMax));
    }

    if (filters.value.dateFrom) {
        const fromDate = new Date(filters.value.dateFrom);
        result = result.filter((d) => new Date(d.start_date) >= fromDate);
    }
    if (filters.value.dateTo) {
        const toDate = new Date(filters.value.dateTo);
        result = result.filter((d) => new Date(d.start_date) <= toDate);
    }

    if (filters.value.sortBy) {
        switch (filters.value.sortBy) {
            case "discount_asc":
                result.sort((a, b) => parseFloat(a.discount_amount) - parseFloat(b.discount_amount));
                break;
            case "discount_desc":
                result.sort((a, b) => parseFloat(b.discount_amount) - parseFloat(a.discount_amount));
                break;
            case "name_asc":
                result.sort((a, b) => a.name.localeCompare(b.name));
                break;
            case "name_desc":
                result.sort((a, b) => b.name.localeCompare(a.name));
                break;
            case "date_asc":
                result.sort((a, b) => new Date(a.start_date) - new Date(b.start_date));
                break;
            case "date_desc":
                result.sort((a, b) => new Date(b.start_date) - new Date(a.start_date));
                break;
        }
    }

    return result;
});


const handleFilterApply = (appliedFilters) => {
    filters.value = { ...filters.value, ...appliedFilters };
    console.log("Filters applied:", filters.value);
};


const handleFilterClear = () => {
    filters.value = {
        sortBy: "",
        stockStatus: "",
        category: "",
        priceMin: null,
        priceMax: null,
        dateFrom: null,
        dateTo: null
    };
    console.log("Filters cleared");
};


const discountStats = computed(() => [
    {
        label: "Total Discounts",
        value: discounts.value.length,
        icon: Percent,
        iconBg: "bg-light-primary",
        iconColor: "text-primary",
    },
    {
        label: "Active Discounts",
        value: discounts.value.filter((d) => d.status === "active").length,
        icon: Calendar,
        iconBg: "bg-light-success",
        iconColor: "text-success",
    },
    {
        label: "Inactive Discounts",
        value: discounts.value.filter((d) => d.status === "inactive").length,
        icon: XCircle,
        iconBg: "bg-light-danger",
        iconColor: "text-danger",
    },
]);

/* ============================================================
   API CALLS - FETCH DATA
   ============================================================ */

/**
 * Fetch all discounts from the backend
 */
const fetchDiscounts = async () => {
    try {
        const res = await axios.get("/api/discounts/all");
        discounts.value = res.data.data;
        console.log('Fetched discounts:', discounts.value);
    } catch (err) {
        console.error("Failed to fetch discounts:", err);
        toast.error("Failed to load discounts");
    }
};

/**
 * Fetch pending approval requests
 */
const fetchPendingRequests = async (silent = false) => {
    // Prevent multiple simultaneous requests
    if (loadingApprovals.value && !silent) return;

    if (!silent) {
        loadingApprovals.value = true;
    }

    try {
        const response = await axios.get('/api/discount-approvals/pending');
        if (response.data.success) {
            pendingRequests.value = response.data.data;
        }
    } catch (error) {
        console.error('Error fetching pending requests:', error);
        if (!silent) {
            toast.error('Failed to load approval requests');
        }
    } finally {
        if (!silent) {
            loadingApprovals.value = false;
        }
    }
};

/* ============================================================
   MODAL FORM MANAGEMENT
   ============================================================ */

/**
 * Reset the discount form to default values
 */
const resetModal = () => {
    discountForm.value = {
        name: "",
        type: "percent",
        status: "active",
        start_date: "",
        end_date: "",
        min_purchase: 0,
        max_discount: null,
        description: "",
        discount_amount: null,
    };
    editingDiscount.value = null;
    discountFormErrors.value = {};
};

/**
 * Load existing discount data into form for editing
 */
const editRow = (row) => {
    editingDiscount.value = row;
    discountForm.value = {
        name: row.name,
        type: row.type,
        status: row.status,
        start_date: row.start_date ? new Date(row.start_date) : null,
        end_date: row.end_date ? new Date(row.end_date) : null,
        min_purchase: row.min_purchase,
        max_discount: row.max_discount,
        description: row.description || "",
        discount_amount: row.discount_amount,
    };

    const modalEl = document.getElementById("discountModal");
    const bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();
};

/* ============================================================
   FORM SUBMISSION - CREATE & UPDATE
   ============================================================ */

/**
 * Submit discount form (Create or Update)
 */
const submitDiscount = async () => {
    submitting.value = true;
    discountFormErrors.value = {};

    try {
        if (editingDiscount.value) {
            await axios.patch(`/discounts/${editingDiscount.value.id}`, discountForm.value);
            toast.success("Discount updated successfully");
        } else {
            await axios.post("/discounts", discountForm.value);
            toast.success("Discount created successfully");
        }

        const modal = bootstrap.Modal.getInstance(document.getElementById("discountModal"));
        modal?.hide();

        resetModal();
        await fetchDiscounts();
    } catch (err) {
        console.error("❌ Error:", err.response?.data || err.message);

        if (err.response?.status === 422 && err.response?.data?.errors) {
            discountFormErrors.value = err.response.data.errors;
            const errorMessages = Object.values(err.response.data.errors).flat();
            toast.error(errorMessages.join("\n"));
        } else {
            const errorMessage = err.response?.data?.message || "Failed to save discount";
            toast.error(errorMessage);
        }
    } finally {
        submitting.value = false;
    }
};


const sampleHeaders = [
    "Discount Name", "Type", "Discount Amount", "Start Date",
    "End Date", "Min Purchase", "Max Discount", "Status", "Description"
];

const sampleData = [
    ["Summer Sale", "percent", "20", "2025-06-01", "2025-08-31", "50", "100", "active", "Summer discount on all items"],
    ["Flat Discount", "flat", "10", "2025-01-01", "2025-12-31", "30", "", "active", "Flat 10 off on purchase"],
];


const handleImport = (data) => {
    if (!data || data.length <= 1) {
        toast.error("The imported file is empty.");
        return;
    }

    const headers = data[0];
    const rows = data.slice(1);

    const discountsToImport = rows.map((row) => {
        return {
            name: row[0] || "",
            type: row[1] || "percent",
            discount_amount: row[2] || "",
            start_date: row[3] || "",
            end_date: row[4] || "",
            min_purchase: row[5] || "0",
            max_discount: row[6] || null,
            status: row[7] || "active",
            description: row[8] || "",
        };
    }).filter(discount => discount.name.trim());

    if (discountsToImport.length === 0) {
        toast.error("No valid discounts found in the file.");
        return;
    }

    // Check for duplicate discount names within the CSV
    const discountNames = discountsToImport.map(d => d.name.trim().toLowerCase());
    const duplicatesInCSV = discountNames.filter((name, index) => discountNames.indexOf(name) !== index);

    if (duplicatesInCSV.length > 0) {
        toast.error(`Duplicate discount names found in CSV: ${[...new Set(duplicatesInCSV)].join(", ")}`);
        return;
    }

    // Check for duplicate discount names in existing table
    const existingDiscountNames = discounts.value.map(d => d.name.trim().toLowerCase());
    const duplicatesInTable = discountsToImport.filter(importDiscount =>
        existingDiscountNames.includes(importDiscount.name.trim().toLowerCase())
    );

    if (duplicatesInTable.length > 0) {
        const duplicateNamesList = duplicatesInTable.map(d => d.name).join(", ");
        toast.error(`Discounts already exist in the table: ${duplicateNamesList}`);
        return;
    }

    // Validate type values
    const validTypes = ["flat", "percent"];
    const invalidTypes = discountsToImport.filter(d => !validTypes.includes(d.type.toLowerCase()));

    if (invalidTypes.length > 0) {
        toast.error("Invalid type found. Type must be either 'flat' or 'percent'.");
        return;
    }

    // Validate status values
    const validStatuses = ["active", "inactive"];
    const invalidStatuses = discountsToImport.filter(d => !validStatuses.includes(d.status.toLowerCase()));

    if (invalidStatuses.length > 0) {
        toast.error("Invalid status found. Status must be either 'active' or 'inactive'.");
        return;
    }

    // Send to API
    axios
        .post("/api/discounts/import", { discounts: discountsToImport })
        .then(() => {
            toast.success("Discounts imported successfully");
            fetchDiscounts();
        })
        .catch((err) => {
            console.error("Import error:", err);
            const errorMessage = err.response?.data?.message || "Import failed";
            toast.error(errorMessage);
        });
};

/* ============================================================
   ACTION HANDLERS - STATUS & DELETE
   ============================================================ */

const toggleStatus = async (row) => {
    const newStatus = row.status === "active" ? "inactive" : "active";

    try {
        await axios.patch(`/api/discounts/${row.id}/toggle-status`, {
            status: newStatus,
        });
        row.status = newStatus;
        toast.success(`Discount status updated to ${newStatus}`);
        confirmModalKey.value++;
    } catch (error) {
        console.error("Failed to update status:", error);
        toast.error("Failed to update status");
    }
};

/**
 * Delete a discount
 */
const deleteDiscount = async (row) => {
    try {
        await axios.delete(`/discounts/${row.id}`);
        toast.success("Discount deleted successfully");
        await fetchDiscounts();
    } catch (error) {
        console.error("Failed to delete discount:", error);
        toast.error("Failed to delete discount");
    }
};

/* ============================================================
   DISCOUNT APPROVAL HANDLERS
   ============================================================ */

/**
 * Respond to approval request (Approve/Reject)
 */
const respondToRequest = async (requestId, status) => {
    processing[requestId] = true;

    try {
        const response = await axios.post(`/api/discount-approvals/${requestId}/respond`, {
            status: status,
            approval_note: approvalNotes[requestId] || null
        });

        if (response.data.success) {
            toast.success(`Discount ${status} successfully!`);

            // Remove from pending list
            pendingRequests.value = pendingRequests.value.filter(r => r.id !== requestId);

            // Clear note
            delete approvalNotes[requestId];
        }
    } catch (error) {
        console.error('Error responding to request:', error);
        toast.error(error.response?.data?.message || `Failed to ${status} discount`);
    } finally {
        processing[requestId] = false;
    }
};

/**
 * Toggle order items visibility
 */
const toggleOrderItems = (requestId) => {
    showOrderItems[requestId] = !showOrderItems[requestId];
};

/**
 * Format date time
 */
const formatDateTime = (timestamp) => {
    if (!timestamp) return '-';
    const date = new Date(timestamp);
    return date.toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

/**
 * Auto-refresh approval requests
 */
const startAutoRefresh = () => {
    // Clear any existing interval
    if (refreshInterval.value) {
        clearInterval(refreshInterval.value);
    }

    refreshInterval.value = setInterval(() => {
        // Use silent mode for background refresh (no loading spinner)
        fetchPendingRequests(true);
    }, 30000); // Every 30 seconds instead of 10
};

const stopAutoRefresh = () => {
    if (refreshInterval.value) {
        clearInterval(refreshInterval.value);
        refreshInterval.value = null;
    }
};

/* ============================================================
   LIFECYCLE HOOKS
   ============================================================ */

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

    await fetchDiscounts();
    await fetchPendingRequests(); // Initial load with spinner
    startAutoRefresh(); // Start background refresh

    // Listen for real-time updates
    if (window.Echo) {
        window.Echo.channel('discount-approvals')
            .listen('.approval.requested', (event) => {
                console.log('New approval request received:', event.approvals);
                fetchPendingRequests(true); // Silent refresh
                toast.info('New discount approval request received!');
            });
    }
});

onUnmounted(() => {
    stopAutoRefresh();
});

onUpdated(() => window.feather?.replace());

const onDownload = (type) => {
    if (!discounts.value || discounts.value.length === 0) {
        toast.error("No Discounts data to download");
        return;
    }


    const dataToExport = q.value.trim() ? filtered.value : discounts.value;


    if (dataToExport.length === 0) {
        toast.error("No Discounts found to download");
        return;
    }

    try {
        if (type === "pdf") {
            downloadPDF(dataToExport);
        } else if (type === "excel") {
            downloadExcel(dataToExport);
        } else if (type === "csv") {
            downloadCSV(dataToExport);
        } else {
            toast.error("Invalid download type");
        }
    } catch (error) {
        console.error("Download failed:", error);
        toast.error(`Download failed: ${error.message}`);
    }
};


const downloadCSV = (data) => {
    try {
        const headers = [
            "ID", "Discount Name", "Type", "Discount Amount",
            "Start Date", "End Date", "Min Purchase", "Max Discount",
            "Status", "Description"
        ];

        const rows = data.map((discount) => {
            return [
                `${discount.id || ""}`,
                `"${discount.name || ""}"`,
                `"${discount.type || ""}"`,
                `${discount.discount_amount || ""}`,
                `"${dateFmt(discount.start_date) || ""}"`,
                `"${dateFmt(discount.end_date) || ""}"`,
                `${discount.min_purchase || ""}`,
                `${discount.max_discount || ""}`,
                `"${discount.status || ""}"`,
                `"${discount.description || ""}"`,
            ];
        });

        const csvContent = [
            headers.join(","),
            ...rows.map((r) => r.join(",")),
        ].join("\n");

        const blob = new Blob([csvContent], {
            type: "text/csv;charset=utf-8;",
        });
        const url = URL.createObjectURL(blob);

        const link = document.createElement("a");
        link.setAttribute("href", url);
        link.setAttribute(
            "download",
            `Discounts_${new Date().toISOString().split("T")[0]}.csv`
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


const downloadPDF = (data) => {
    try {
        const doc = new jsPDF("l", "mm", "a4");

        doc.setFontSize(18);
        doc.setFont("helvetica", "bold");
        doc.text("Discounts Report", 14, 20);

        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        const currentDate = new Date().toLocaleString();
        doc.text(`Generated on: ${currentDate}`, 14, 28);
        doc.text(`Total Discounts: ${data.length}`, 14, 34);

        const tableColumns = [
            "ID", "Name", "Type", "Amount", "Start Date",
            "End Date", "Min Purchase", "Max Discount", "Status"
        ];

        const tableRows = data.map((discount) => {
            return [
                discount.id || "",
                discount.name || "",
                discount.type === "flat" ? "Flat" : "Percent",
                discount.type === "flat"
                    ? formatCurrencySymbol(discount.discount_amount)
                    : discount.discount_amount + "%",
                dateFmt(discount.start_date) || "",
                dateFmt(discount.end_date) || "",
                formatCurrencySymbol(discount.min_purchase) || "",
                discount.max_discount ? formatCurrencySymbol(discount.max_discount) : "N/A",
                discount.status === "active" ? "Active" : "Inactive",
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

        const fileName = `Discounts_${new Date().toISOString().split("T")[0]}.pdf`;
        doc.save(fileName);

        toast.success("PDF downloaded successfully");
    } catch (error) {
        console.error("PDF generation error:", error);
        toast.error(`PDF generation failed: ${error.message}`);
    }
};


const downloadExcel = (data) => {
    try {
        if (typeof XLSX === "undefined") {
            throw new Error("XLSX library is not loaded");
        }

        const worksheetData = data.map((discount) => {
            return {
                "ID": discount.id || "",
                "Discount Name": discount.name || "",
                "Type": discount.type === "flat" ? "Flat" : "Percent",
                "Discount Amount": discount.type === "flat"
                    ? discount.discount_amount
                    : discount.discount_amount + "%",
                "Start Date": dateFmt(discount.start_date) || "",
                "End Date": dateFmt(discount.end_date) || "",
                "Min Purchase": discount.min_purchase || "",
                "Max Discount": discount.max_discount || "",
                "Status": discount.status === "active" ? "Active" : "Inactive",
                "Description": discount.description || "",
            };
        });

        const workbook = XLSX.utils.book_new();
        const worksheet = XLSX.utils.json_to_sheet(worksheetData);

        worksheet["!cols"] = [
            { wch: 8 },
            { wch: 25 },
            { wch: 12 },
            { wch: 15 },
            { wch: 15 },
            { wch: 15 },
            { wch: 15 },
            { wch: 15 },
            { wch: 12 },
            { wch: 30 },
        ];

        XLSX.utils.book_append_sheet(workbook, worksheet, "Discounts");

        const metaData = [
            { Info: "Report", Value: "Discounts Export" },
            { Info: "Generated On", Value: new Date().toLocaleString() },
            { Info: "Total Records", Value: data.length },
            { Info: "Exported By", Value: "Inventory Management System" },
        ];
        const metaSheet = XLSX.utils.json_to_sheet(metaData);

        XLSX.utils.book_append_sheet(workbook, metaSheet, "Report Info");

        const fileName = `Discounts_${new Date().toISOString().split("T")[0]}.xlsx`;
        XLSX.writeFile(workbook, fileName);

        toast.success("Excel file downloaded successfully");
    } catch (error) {
        console.error("Excel generation error:", error);
        toast.error(`Excel generation failed: ${error.message}`);
    }
};
</script>

<template>
    <Master>

        <Head title="Discount Management" />

        <div class="page-wrapper">
            <h4 class="fw-semibold mb-3">Discount Management</h4>

            <!-- Tabs Component -->
            <Tabs value="0" class="w-100">
                <!-- Tab Headers -->
                <TabList>
                    <Tab value="0">Discounts</Tab>
                    <Tab value="1">Discount Approvals</Tab>
                </TabList>

                <!-- Tab Panels -->
                <TabPanels>
                    <!-- ========== DISCOUNTS TAB ========== -->
                    <TabPanel value="0">
                        <div class="mt-3">
                            <!-- KPI Statistics Cards -->
                            <div class="row g-3 mb-4">
                                <div v-for="stat in discountStats" :key="stat.label" class="col-md-6 col-xl-3">
                                    <div class="card border-0 shadow-sm rounded-4">
                                        <div class="card-body d-flex align-items-center">
                                            <div :class="[stat.iconBg, stat.iconColor]"
                                                class="rounded-circle p-3 d-flex align-items-center justify-content-center me-3"
                                                style="width: 56px; height: 56px">
                                                <component :is="stat.icon" class="w-6 h-6" />
                                            </div>
                                            <div>
                                                <h3 class="mb-0 fw-bold">{{ stat.value }}</h3>
                                                <p class="text-muted mb-0 small">{{ stat.label }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Discounts Table Card -->
                            <div class="card border-0 shadow-lg rounded-4">
                                <div class="card-body">
                                    <!-- Table Toolbar -->
                                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0 fw-semibold">Discounts</h5>

                                        <div class="d-flex flex-wrap gap-2 align-items-center">
                                            <FilterModal v-model="filters" title="Discounts"
                                                modalId="discountsFilterModal" modalSize="modal-lg" :sortOptions="[
                                                    { value: 'name_asc', label: 'Name: A to Z' },
                                                    { value: 'name_desc', label: 'Name: Z to A' },
                                                    { value: 'discount_asc', label: 'Discount: Low to High' },
                                                    { value: 'discount_desc', label: 'Discount: High to Low' },
                                                    { value: 'date_asc', label: 'Start Date: Oldest First' },
                                                    { value: 'date_desc', label: 'Start Date: Newest First' },
                                                ]" :categories="discountTypesForFilter" categoryLabel="Discount Type"
                                                statusLabel="Discount Status" :showPriceRange="true"
                                                priceRangeLabel="Discount Amount Range" :showDateRange="true"
                                                @apply="handleFilterApply" @clear="handleFilterClear" />

                                            <!-- Search Input -->
                                            <div class="search-wrap">
                                                <i class="bi bi-search"></i>

                                                <input type="email" name="email" autocomplete="email"
                                                    style="position: absolute; left: -9999px; width: 1px; height: 1px;"
                                                    tabindex="-1" aria-hidden="true" />

                                                <input v-if="isReady" :id="inputId" v-model="q" :key="searchKey"
                                                    class="form-control search-input" placeholder="Search discounts"
                                                    type="search" autocomplete="new-password" :name="inputId"
                                                    role="presentation" />
                                                <input v-else class="form-control search-input"
                                                    placeholder="Search discounts" disabled type="text" />
                                            </div>




                                            <!-- Add New Button -->
                                            <button data-bs-toggle="modal" data-bs-target="#discountModal"
                                                @click="resetModal"
                                                class="d-flex align-items-center gap-1 px-4 py-2 rounded-pill btn btn-primary text-white">
                                                <Plus class="w-4 h-4" /> Add Discount
                                            </button>

                                            <ImportFile label="Import Discounts" :sampleHeaders="sampleHeaders"
                                                :sampleData="sampleData" @on-import="handleImport" />

                                            <!-- Export Dropdown -->
                                            <div class="dropdown">
                                                <button
                                                    class="btn btn-outline-secondary rounded-pill py-2 btn-sm px-4 dropdown-toggle"
                                                    data-bs-toggle="dropdown">
                                                    Export
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow rounded-4 py-2">
                                                    <li><a class="dropdown-item py-2" href="javascript:;"
                                                            @click="onDownload('pdf')">Export as PDF</a></li>
                                                    <li><a class="dropdown-item py-2" href="javascript:;"
                                                            @click="onDownload('excel')">Export as Excel</a></li>
                                                    <li><a class="dropdown-item py-2" href="javascript:;"
                                                            @click="onDownload('csv')">Export as CSV</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Discounts Table -->
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead class="border-top small text-muted">
                                                <tr>
                                                    <th>S.#</th>
                                                    <th>Name</th>
                                                    <th>Type</th>
                                                    <th>Discount (%)</th>
                                                    <th>Start Date</th>
                                                    <th>End Date</th>
                                                    <th>Min Purchase</th>
                                                    <th>Max Discount</th>
                                                    <th class="text-center">Status</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(row, i) in filtered" :key="row.id">
                                                    <td>{{ i + 1 }}</td>
                                                    <td class="fw-semibold">{{ row.name }}</td>

                                                    <td>
                                                        <span :class="row.type === 'flat'
                                                            ? 'badge bg-primary px-4 py-2 rounded-pill'
                                                            : 'badge bg-warning px-4 py-2 rounded-pill'
                                                            ">
                                                            {{ row.type === "flat" ? "Flat" : "Percent" }}
                                                        </span>
                                                    </td>

                                                    <td>
                                                        {{ row.type === "flat"
                                                            ? formatCurrencySymbol(row.discount_amount)
                                                            : row.discount_amount + "%"
                                                        }}
                                                    </td>

                                                    <td>{{ dateFmt(row.start_date) }}</td>
                                                    <td>{{ dateFmt(row.end_date) }}</td>
                                                    <td>{{ formatCurrencySymbol(row.min_purchase) }}</td>
                                                    <td>
                                                        {{ row.max_discount ? formatCurrencySymbol(row.max_discount) :
                                                            "N/A" }}
                                                    </td>

                                                    <td class="text-center">
                                                        <span :class="row.status === 'active'
                                                            ? 'badge bg-success px-4 py-2 rounded-pill'
                                                            : 'badge bg-danger px-4 py-2 rounded-pill'
                                                            ">
                                                            {{ row.status === "active" ? "Active" : "Inactive" }}
                                                        </span>
                                                    </td>

                                                    <td class="text-center">
                                                        <div class="d-inline-flex align-items-center gap-3">
                                                            <button @click="editRow(row)" title="Edit"
                                                                class="p-2 rounded-full text-blue-600 hover:bg-blue-100">
                                                                <Pencil class="w-4 h-4" />
                                                            </button>

                                                            <ConfirmModal :key="`confirm-${row.id}-${confirmModalKey}`"
                                                                :title="'Confirm Status Change'"
                                                                :message="`Are you sure you want to set ${row.name} to ${row.status === 'active' ? 'Inactive' : 'Active'}?`"
                                                                :showStatusButton="true" confirmText="Yes, Change"
                                                                cancelText="Cancel" :status="row.status"
                                                                @confirm="toggleStatus(row)">
                                                                <template #trigger>
                                                                    <button
                                                                        class="relative inline-flex items-center w-8 h-4 rounded-full transition-colors duration-300 focus:outline-none"
                                                                        :class="row.status === 'active' ? 'bg-green-500 hover:bg-green-600' : 'bg-red-400 hover:bg-red-500'"
                                                                        :title="row.status === 'active' ? 'Set Inactive' : 'Set Active'">
                                                                        <span
                                                                            class="absolute left-0.5 top-0.5 w-3 h-3 bg-white rounded-full shadow transform transition-transform duration-300"
                                                                            :class="row.status === 'active' ? 'translate-x-4' : 'translate-x-0'"></span>
                                                                    </button>
                                                                </template>
                                                            </ConfirmModal>

                                                            <ConfirmModal :title="'Confirm Delete'"
                                                                :message="`Are you sure you want to delete ${row.name}? This action cannot be undone.`"
                                                                confirmText="Yes, Delete" cancelText="Cancel"
                                                                @confirm="deleteDiscount(row)">
                                                                <template #trigger>
                                                                    <button title="Delete"
                                                                        class="p-2 rounded-full text-red-600 hover:bg-red-100">
                                                                        <Trash2 class="w-4 h-4" />
                                                                    </button>
                                                                </template>
                                                            </ConfirmModal>
                                                        </div>
                                                    </td>
                                                </tr>

                                                <tr v-if="filtered.length === 0">
                                                    <td colspan="10" class="text-center text-muted py-4">
                                                        No discounts found.
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </TabPanel>

                    <!-- ========== DISCOUNT APPROVALS TAB ========== -->
                    <TabPanel value="1">
                        <div class="mt-3">
                            <div class="card border-0 shadow-sm rounded-4">
                                <div
                                    class="card-header bg-white border-0 d-flex justify-content-between align-items-center py-3">
                                    <h4 class="mb-0 fw-bold">
                                        <i class="bi bi-shield-check text-primary me-2"></i>
                                        Discount Approval Requests
                                    </h4>
                                    <button class="btn btn-outline-primary btn-sm" @click="fetchPendingRequests">
                                        <i class="bi bi-arrow-clockwise me-1"></i>
                                        Refresh
                                    </button>
                                </div>

                                <div class="card-body">
                                    <!-- Loading State -->
                                    <div v-if="loadingApprovals" class="text-center py-5">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-3 text-muted">Loading approval requests...</p>
                                    </div>

                                    <!-- No Pending Requests -->
                                    <div v-else-if="pendingRequests.length === 0" class="text-center py-5">
                                        <i class="bi bi-inbox" style="font-size: 4rem; opacity: 0.3;"></i>
                                        <p class="mt-3 text-muted fw-semibold">No pending discount approval requests</p>
                                    </div>

                                    <!-- Pending Requests List -->
                                    <div v-else class="row g-3">
                                        <div v-for="request in pendingRequests" :key="request.id" class="col-md-4">
                                            <div class="card border border-warning rounded-4 h-100">
                                                <div class="card-body">
                                                    <!-- Header -->
                                                    <!-- Header -->
                                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                                        <div>
                                                            <h5 class="fw-bold mb-1">{{ request.discount_name ||
                                                                request.discount.name }}
                                                            </h5>
                                                            <span class="badge bg-warning">
                                                                <i class="bi bi-clock-history me-1"></i>
                                                                Pending Approval
                                                            </span>
                                                        </div>

                                                        <!-- ✅ SHOW PERCENTAGE -->
                                                        <div class="text-end">
                                                            <div class="h3 text-primary mb-0 fw-bold">
                                                                {{ parseFloat(request.discount_percentage).toFixed(0)
                                                                }}%
                                                            </div>
                                                            <small class="text-muted d-block">Discount Rate</small>
                                                            <!-- <div class="small text-success mt-1">
                                                                Will save: -£{{ ((parseFloat(request.order_subtotal) *
                                                                parseFloat(request.discount_percentage)) /
                                                                100).toFixed(2) }}
                                                            </div> -->
                                                        </div>
                                                    </div>

                                                    <!-- Request Details -->
                                                    <div class="mb-3">
                                                        <div class="d-flex align-items-center gap-2 mb-2">
                                                            <i class="bi bi-person-circle text-muted"></i>
                                                            <small class="text-muted">
                                                                Requested by: <span class="fw-semibold">{{
                                                                    request.requested_by.name }}</span>
                                                            </small>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-2 mb-2">
                                                            <i class="bi bi-clock text-muted"></i>
                                                            <small class="text-muted">
                                                                {{ formatDateTime(request.requested_at) }}
                                                            </small>
                                                        </div>
                                                        <!-- <div class="d-flex align-items-center gap-2">
                                                            <i class="bi bi-cart text-muted"></i>
                                                            <small class="text-muted">
                                                                Order Subtotal: <span class="fw-semibold">£{{
                                                                    parseFloat(request.order_subtotal).toFixed(2)
                                                                }}</span>
                                                            </small>
                                                        </div> -->
                                                    </div>


                                                    <!-- Order Items Preview -->
                                                    <!-- <div class="mb-3">

                                                        <div class="text-muted small mb-1">
                                                            Order Items ({{ request.order_items?.length || 0 }})
                                                        </div>

                                                        <div class="mt-2">
                                                            <div class="list-group list-group-flush small">
                                                                <div v-for="item in request.order_items" :key="item.id"
                                                                    class="list-group-item px-0 py-2 d-flex justify-content-between">
                                                                    <span>{{ item.title }} × {{ item.qty }}</span>
                                                                    <span class="fw-semibold">£{{
                                                                        parseFloat(item.price).toFixed(2)
                                                                        }}</span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                    </div> -->

                                                    <!-- Approval Note Input -->
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Response Note
                                                            (Optional)</label>
                                                        <textarea v-model="approvalNotes[request.id]"
                                                            class="form-control form-control-sm" rows="2"
                                                            placeholder="Add a note for the cashier..."></textarea>
                                                    </div>

                                                    <!-- Action Buttons -->
                                                    <div class="d-flex gap-2">
                                                        <button class="btn btn-success flex-fill"
                                                            @click="respondToRequest(request.id, 'approved')"
                                                            :disabled="processing[request.id]">
                                                            <i class="bi bi-check-circle me-1"></i>
                                                            Approve
                                                        </button>
                                                        <button class="btn btn-danger flex-fill"
                                                            @click="respondToRequest(request.id, 'rejected')"
                                                            :disabled="processing[request.id]">
                                                            <i class="bi bi-x-circle me-1"></i>
                                                            Reject
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </TabPanel>
                </TabPanels>
            </Tabs>

            <!-- ================== ADD/EDIT DISCOUNT MODAL ================== -->
            <div class="modal fade" id="discountModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content rounded-4">
                        <div class="modal-header">
                            <h5 class="modal-title fw-semibold">
                                {{ editingDiscount ? "Edit Discount" : "Add New Discount" }}
                            </h5>
                            <button
                                class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                                @click="resetModal" data-bs-dismiss="modal" aria-label="Close" title="Close">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="modal-body">
                            <div class="row g-3">
                                <!-- Discount Name -->
                                <div class="col-12">
                                    <label class="form-label">Discount Name *</label>
                                    <input v-model="discountForm.name" type="text" class="form-control"
                                        :class="{ 'is-invalid': discountFormErrors.name }"
                                        placeholder="Enter discount name" />
                                    <small v-if="discountFormErrors.name" class="text-danger">
                                        {{ discountFormErrors.name[0] }}
                                    </small>
                                </div>

                                <!-- Discount Type -->
                                <div class="col-md-6">
                                    <label class="form-label">Discount Type *</label>
                                    <Select v-model="discountForm.type" :options="discountOptions" optionLabel="label"
                                        appendTo="self" :autoZIndex="true" :baseZIndex="2000" optionValue="value"
                                        class="form-select" :class="{ 'is-invalid': discountFormErrors.type }" />
                                    <small v-if="discountFormErrors.type" class="text-danger">
                                        {{ discountFormErrors.type[0] }}
                                    </small>
                                </div>

                                <!-- Status -->
                                <div class="col-md-6">
                                    <label class="form-label">Status *</label>
                                    <Select v-model="discountForm.status" :options="statusOptions" optionLabel="label"
                                        optionValue="value" class="form-select" appendTo="self" :autoZIndex="true"
                                        :baseZIndex="2000" :class="{ 'is-invalid': discountFormErrors.status }" />
                                    <small v-if="discountFormErrors.status" class="text-danger">
                                        {{ discountFormErrors.status[0] }}
                                    </small>
                                </div>

                                <!-- Discount Amount -->
                                <div class="col-12">
                                    <label class="form-label">Discount Amount *</label>
                                    <input v-model="discountForm.discount_amount" type="number" class="form-control"
                                        :class="{ 'is-invalid': discountFormErrors.discount_amount }"
                                        placeholder="Enter discount amount" />
                                    <small v-if="discountFormErrors.discount_amount" class="text-danger">
                                        {{ discountFormErrors.discount_amount[0] }}
                                    </small>
                                </div>

                                <!-- Start Date -->
                                <div class="col-md-6">
                                    <label class="form-label">Start Date *</label>
                                    <VueDatePicker v-model="discountForm.start_date" :format="dateFmt"
                                        :min-date="new Date()" :enableTimePicker="false" placeholder="Select start date"
                                        :class="{ 'is-invalid': discountFormErrors.start_date }" />
                                    <small v-if="discountFormErrors.start_date" class="text-danger">
                                        {{ discountFormErrors.start_date[0] }}
                                    </small>
                                </div>

                                <!-- End Date -->
                                <div class="col-md-6">
                                    <label class="form-label">End Date *</label>
                                    <VueDatePicker v-model="discountForm.end_date" :format="dateFmt"
                                        :min-date="new Date()" :enableTimePicker="false" placeholder="Select end date"
                                        :class="{ 'is-invalid': discountFormErrors.end_date }" />
                                    <small v-if="discountFormErrors.end_date" class="text-danger">
                                        {{ discountFormErrors.end_date[0] }}
                                    </small>
                                </div>

                                <!-- Minimum Purchase Amount -->
                                <div class="col-md-6">
                                    <label class="form-label">Minimum Purchase Amount *</label>
                                    <input v-model="discountForm.min_purchase" type="number" step="0.01"
                                        class="form-control" :class="{ 'is-invalid': discountFormErrors.min_purchase }"
                                        placeholder="0.00" />
                                    <small v-if="discountFormErrors.min_purchase" class="text-danger">
                                        {{ discountFormErrors.min_purchase[0] }}
                                    </small>
                                </div>

                                <!-- Maximum Discount (Optional) -->
                                <div class="col-md-6">
                                    <label class="form-label">Maximum Discount (Optional)</label>
                                    <input v-model="discountForm.max_discount" type="number" step="0.01"
                                        class="form-control" :class="{ 'is-invalid': discountFormErrors.max_discount }"
                                        placeholder="0.00" />
                                    <small v-if="discountFormErrors.max_discount" class="text-danger">
                                        {{ discountFormErrors.max_discount[0] }}
                                    </small>
                                </div>

                                <!-- Description (Optional) -->
                                <div class="col-12">
                                    <label class="form-label">Description (Optional)</label>
                                    <textarea v-model="discountForm.description" class="form-control" rows="3"
                                        :class="{ 'is-invalid': discountFormErrors.description }"
                                        placeholder="Enter discount description"></textarea>
                                    <small v-if="discountFormErrors.description" class="text-danger">
                                        {{ discountFormErrors.description[0] }}
                                    </small>
                                </div>
                            </div>

                            <hr class="my-4" />

                            <!-- Modal Footer -->
                            <div class="mt-4">
                                <button class="btn btn-primary rounded-pill px-4" :disabled="submitting"
                                    @click="submitDiscount()">
                                    <template v-if="submitting">
                                        <span class="spinner-border spinner-border-sm me-2"></span>
                                        Saving...
                                    </template>
                                    <template v-else>
                                        {{ editingDiscount ? "Save" : "Save" }}
                                    </template>
                                </button>

                                <button class="btn btn-secondary rounded-pill px-4 ms-2" data-bs-dismiss="modal"
                                    @click="resetModal">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Master>
</template>

<style scoped>
/* Search Input Styling */
.search-wrap {
    position: relative;
}

.list-group {
    color: #121212 !important;
}

.search-wrap i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
}

.search-input {
    padding-left: 40px;
    border-radius: 50px;
}

.p-tablist {
    background: #fff !important;
}

.dark .p-tablist {
    background: #212121 !important;
    color: #fff !important;
}


.p-tabpanels {
    background-color: #fff !important;
}



.dark .p-tabpanels {
    background-color: #212121 !important;
    color: #fff !important;
}

/* ======================== PrimeVue Select Styling ======================== */

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
    color: black !important;
}

/* Hovered/focused option */
:deep(.p-select-option:hover),
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

/* Keep PrimeVue overlays above Bootstrap modal */
:deep(.p-select-panel) {
    z-index: 2000 !important;
}

/* ======================== Dark Mode Select Styling ======================== */

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

:global(.dark .p-select-panel) {
    z-index: 2000 !important;
}
</style>