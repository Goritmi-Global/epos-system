<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted, onUpdated, watch } from "vue";
import { Percent, Calendar, AlertTriangle, XCircle, Pencil, Plus, CheckCircle } from "lucide-vue-next";
import { toast } from "vue3-toastify";
import axios from "axios";
import Select from "primevue/select";
import ConfirmModal from "@/Components/ConfirmModal.vue";
import { useFormatters } from '@/composables/useFormatters'
import { nextTick } from "vue";
import { Head } from "@inertiajs/vue3";
import MultiSelect from 'primevue/multiselect';
import { jsPDF } from "jspdf";
import autoTable from "jspdf-autotable";
import * as XLSX from "xlsx";
import ImportFile from "@/Components/importFile.vue";
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import FilterModal from "@/Components/FilterModal.vue";

const { formatMoney, formatCurrencySymbol, formatNumber, dateFmt } = useFormatters()

const props = defineProps({
    meals: {
        type: Array,
        required: true,
    },
});

// Don't override props with ref - use computed or direct reference
const mealsData = computed(() => props.meals || []);

// Flatten all menu items for the menu items MultiSelect
const menuItemsData = computed(() => {
    if (!props.meals || !Array.isArray(props.meals)) return [];

    const items = props.meals.flatMap(meal =>
        (meal.menu_items || []).map(item => ({
            ...item,
            meal_name: meal.name
        }))
    );

    // Remove duplicates based on item.id
    const uniqueItems = [];
    const seenIds = new Set();

    for (const item of items) {
        if (!seenIds.has(item.id)) {
            seenIds.add(item.id);
            uniqueItems.push(item);
        }
    }

    return uniqueItems;
});


/* ---------------- Data ---------------- */
const promos = ref([]);
const editingPromo = ref(null);
const submitting = ref(false);
const promoFormErrors = ref({});

// Promo Scope
const promoScopes = ref([]);
const editingPromoScope = ref(null);
const promoScopeFormErrors = ref({});

const discountOptions = [
    { label: "Flat Amount", value: "flat" },
    { label: "Percentage", value: "percent" },
];

const statusOptions = [
    { label: "Active", value: "active" },
    { label: "Deactive", value: "inactive" },
];

// reactive for form
const promoScopeForm = ref({
    promo_id: null,
    meals: [],
    menu_items: [],
});

// Debug: Watch the form values
watch(() => promoScopeForm.value.meals, (newVal) => {
    console.log('Selected meals changed:', newVal);
}, { deep: true });

watch(() => promoScopeForm.value.menu_items, (newVal) => {
    console.log('Selected menu items changed:', newVal);
}, { deep: true });


/* ============= IMPORT CONFIGURATION ============= */
const sampleHeaders = [
    "Promo Name", "Type", "Discount Amount", "Start Date", 
    "End Date", "Min Purchase", "Max Discount", "Status", "Description"
];

const sampleData = [
    ["Summer Sale", "percent", "20", "2025-06-01", "2025-08-31", "50", "100", "active", "Summer discount"],
    ["Flat Discount", "flat", "10", "2025-01-01", "2025-12-31", "30", "", "active", "Flat 10 off"],
];

/* ============= IMPORT HANDLER ============= */
const handleImport = (data) => {
    if (!data || data.length <= 1) {
        toast.error("The imported file is empty.");
        return;
    }

    const headers = data[0];
    const rows = data.slice(1);

    const promosToImport = rows.map((row) => {
        // Normalize dates to YYYY-MM-DD format
        const normalizeDate = (dateStr) => {
            if (!dateStr) return "";
            
            const dateObj = new Date(dateStr);
            if (isNaN(dateObj.getTime())) {
                return dateStr; // Return as-is if can't parse
            }
            
            // Return in YYYY-MM-DD format
            const year = dateObj.getFullYear();
            const month = String(dateObj.getMonth() + 1).padStart(2, '0');
            const day = String(dateObj.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };

        return {
            name: row[0] || "",
            type: row[1] || "percent",
            discount_amount: row[2] || "",
            start_date: normalizeDate(row[3]),
            end_date: normalizeDate(row[4]),
            min_purchase: row[5] || "0",
            max_discount: row[6] || null,
            status: row[7] || "active",
            description: row[8] || "",
        };
    }).filter(promo => promo.name.trim());

    if (promosToImport.length === 0) {
        toast.error("No valid promos found in the file.");
        return;
    }

    // Check for duplicate promo names within the CSV
    const promoNames = promosToImport.map(p => p.name.trim().toLowerCase());
    const duplicatesInCSV = promoNames.filter((name, index) => promoNames.indexOf(name) !== index);

    if (duplicatesInCSV.length > 0) {
        toast.error(`Duplicate promo names found in CSV: ${[...new Set(duplicatesInCSV)].join(", ")}`);
        return;
    }

    // Check for duplicate promo names in existing table
    const existingPromoNames = promos.value.map(p => p.name.trim().toLowerCase());
    const duplicatesInTable = promosToImport.filter(importPromo =>
        existingPromoNames.includes(importPromo.name.trim().toLowerCase())
    );

    if (duplicatesInTable.length > 0) {
        const duplicateNamesList = duplicatesInTable.map(p => p.name).join(", ");
        toast.error(`Promos already exist in the table: ${duplicateNamesList}`);
        return;
    }

    // Validate type values
    const validTypes = ["flat", "percent"];
    const invalidTypes = promosToImport.filter(p => !validTypes.includes(p.type.toLowerCase()));

    if (invalidTypes.length > 0) {
        toast.error("Invalid type found. Type must be either 'flat' or 'percent'.");
        return;
    }

    // Validate status values
    const validStatuses = ["active", "inactive"];
    const invalidStatuses = promosToImport.filter(p => !validStatuses.includes(p.status.toLowerCase()));

    if (invalidStatuses.length > 0) {
        toast.error("Invalid status found. Status must be either 'active' or 'inactive'.");
        return;
    }

    // Send to API
    axios
        .post("/api/promos/import", { promos: promosToImport })
        .then(() => {
            toast.success("Promos imported successfully");
            fetchPromos();
        })
        .catch((err) => {
            console.error("Import error:", err);
            const errorMessage = err.response?.data?.message || "Import failed";
            const errors = err.response?.data?.errors || [];
            
            if (errors.length > 0) {
                console.error("Detailed errors:", errors);
                toast.error(`${errorMessage}: ${errors.join(", ")}`);
            } else {
                toast.error(errorMessage);
            }
        });
};

/* ---------------- Fetch Promos ---------------- */
const fetchPromos = async () => {
    try {
        const res = await axios.get("/api/promos/all");
        promos.value = res.data.data;
        console.log('Fetched promos:', promos.value);
    } catch (err) {
        console.error("Failed to fetch promos:", err);
        toast.error("Failed to load promos");
    }
};

/* ---------------- Fetch Promo Scopes ---------------- */
const fetchPromoScopes = async () => {
    try {
        const res = await axios.get("/promo-scopes");
        promoScopes.value = res.data.data || [];
        console.log('Fetched promo scopes:', promoScopes.value);
    } catch (err) {
        console.error("Failed to fetch promo scopes:", err);
        toast.error("Failed to load promo scopes");
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

    await fetchPromos();
    await fetchPromoScopes();

    // Debug log
    console.log('Props meals on mount:', props.meals);
    console.log('Computed mealsData:', mealsData.value);
    console.log('Computed menuItemsData:', menuItemsData.value);
});

/* ---------------- KPI Cards ---------------- */
const promoStats = computed(() => [
    {
        label: "Total Promos",
        value: promos.value.length,
        icon: Percent,
        iconBg: "bg-light-primary",
        iconColor: "text-primary",
    },
    {
        label: "Active Promos",
        value: promos.value.filter((p) => p.status === "active").length,
        icon: Calendar,
        iconBg: "bg-light-success",
        iconColor: "text-success",
    },
    {
        label: "Flat Discount",
        value: promos.value.filter((p) => p.type === "flat").length,
        icon: AlertTriangle,
        iconBg: "bg-light-warning",
        iconColor: "text-warning",
    },
    {
        label: "Percentage",
        value: promos.value.filter((p) => p.type === "percent").length,
        icon: XCircle,
        iconBg: "bg-light-danger",
        iconColor: "text-danger",
    },
]);

/* ---------------- Search ---------------- */
const q = ref("");
const searchKey = ref(Date.now());
const inputId = `search-${Math.random().toString(36).substr(2, 9)}`;
const isReady = ref(false);

const filters = ref({
    sortBy: "",
    stockStatus: "",
    priceMin: null,
    priceMax: null,
    dateFrom: null,
    dateTo: null
});

const promoTypesForFilter = computed(() => [
    { id: "flat", name: "Flat" },
    { id: "percent", name: "Percentage" }
]);


/* ---------------- Search & Filter ---------------- */
const filtered = computed(() => {
    let result = promos.value;

    // Search by name
    const t = q.value.trim().toLowerCase();
    if (t) {
        result = result.filter((p) => p.name.toLowerCase().includes(t));
    }

    // Filter by status
    if (filters.value.stockStatus) {
        result = result.filter((p) => p.status === filters.value.stockStatus);
    }

    // Filter by type (using category field for type)
    if (filters.value.category) {
        result = result.filter((p) => p.type === filters.value.category);
    }

    // Filter by discount amount range (using price fields)
    if (filters.value.priceMin !== null && filters.value.priceMin !== "") {
        result = result.filter((p) => parseFloat(p.discount_amount) >= parseFloat(filters.value.priceMin));
    }
    if (filters.value.priceMax !== null && filters.value.priceMax !== "") {
        result = result.filter((p) => parseFloat(p.discount_amount) <= parseFloat(filters.value.priceMax));
    }

    // Filter by date range (start_date)
    if (filters.value.dateFrom) {
        const fromDate = new Date(filters.value.dateFrom);
        result = result.filter((p) => new Date(p.start_date) >= fromDate);
    }
    if (filters.value.dateTo) {
        const toDate = new Date(filters.value.dateTo);
        result = result.filter((p) => new Date(p.start_date) <= toDate);
    }

    // Apply sorting
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

/* ---------------- Form State ---------------- */
const promoForm = ref({
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

const resetModal = () => {
    promoForm.value = {
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
    editingPromo.value = null;
    promoFormErrors.value = {};
};

const resetPromoScopeModal = () => {
    promoScopeForm.value = {
        promo_id: null,
        meals: [],
        menu_items: [],
    };
    editingPromoScope.value = null;
    promoScopeFormErrors.value = {};
};

/* ---------------- Submit Promo (Create/Update) ---------------- */
const submitPromo = async () => {
    submitting.value = true;
    promoFormErrors.value = {};

    try {
        if (editingPromo.value) {
            await axios.post(`/promos/${editingPromo.value.id}`, promoForm.value);
            toast.success("Promo updated successfully");
        } else {
            await axios.post("/promos", promoForm.value);
            toast.success("Promo created successfully");
        }

        const modal = bootstrap.Modal.getInstance(document.getElementById("promoModal"));
        modal?.hide();

        resetModal();
        await fetchPromos();
    } catch (err) {
        console.error("❌ Error:", err.response?.data || err.message);

        if (err.response?.status === 422 && err.response?.data?.errors) {
            promoFormErrors.value = err.response.data.errors;
            const errorMessages = Object.values(err.response.data.errors).flat();
            toast.error(errorMessages.join("\n"));
        } else {
            const errorMessage = err.response?.data?.message || "Failed to save promo";
            toast.error(errorMessage);
        }
    } finally {
        submitting.value = false;
    }
};

/* ---------------- Submit Promo Scope (Create/Update) ---------------- */
const submitPromoScope = async () => {
    submitting.value = true;
    promoScopeFormErrors.value = {};

    console.log('Submitting promo scope:', promoScopeForm.value);

    try {
        const payload = {
            promos: promoScopeForm.value.promos || [],
            meals: promoScopeForm.value.meals || [],
            menu_items: promoScopeForm.value.menu_items || [],
        };

        console.log('Payload:', payload);

        if (editingPromoScope.value) {
            await axios.put(`/promo-scopes/${editingPromoScope.value.id}`, payload);
            toast.success("Promo scope updated successfully");
        } else {
            await axios.post("/promo-scopes", payload);
            toast.success("Promo scope created successfully");
        }

        const modal = bootstrap.Modal.getInstance(document.getElementById("promoScopeModal"));
        modal?.hide();

        resetPromoScopeModal();
        await fetchPromoScopes();
    } catch (err) {
        console.error("❌ Error:", err.response?.data || err.message);

        if (err.response?.status === 422 && err.response?.data?.errors) {
            promoScopeFormErrors.value = err.response.data.errors;
            const errorMessages = Object.values(err.response.data.errors).flat();
            toast.error("Please filled all the required fields.");
        } else {
            const errorMessage = err.response?.data?.message || "Failed to save promo scope";
            toast.error(errorMessage);
        }
    } finally {
        submitting.value = false;
    }
};

/* ---------------- Edit Promo ---------------- */
const editRow = (row) => {
    editingPromo.value = row;
    promoForm.value = {
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

    const modalEl = document.getElementById("promoModal");
    const bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();
};

/* ---------------- Edit Promo Scope ---------------- */
const editPromoScope = (scope) => {
    editingPromoScope.value = scope;
    promoScopeForm.value = {
        promos: scope.promos ? scope.promos.map(p => p.id) : [],
        meals: scope.meals ? scope.meals.map(m => m.id) : [],
        menu_items: scope.menu_items ? scope.menu_items.map(m => m.id) : [],
    };

    const modalEl = document.getElementById("promoScopeModal");
    const bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();
};

/* ---------------- Toggle Status ---------------- */
const toggleStatus = async (row) => {
    const newStatus = row.status === "active" ? "inactive" : "active";

    try {
        await axios.patch(`/api/promos/${row.id}/toggle-status`, {
            status: newStatus,
        });
        row.status = newStatus;
        toast.success(`Promo status updated to ${newStatus}`);
    } catch (error) {
        console.error("Failed to update status:", error);
        toast.error("Failed to update status");
    }
};

/* ---------------- Helpers ---------------- */
const money = (n, currency = "GBP") =>
    new Intl.NumberFormat("en-GB", { style: "currency", currency }).format(n);

const formatDate = (date) => {
    if (!date) return "N/A";
    return new Date(date).toLocaleDateString("en-GB");
};

onUpdated(() => window.feather?.replace());

const onDownload = (type) => {
    if (!promos.value || promos.value.length === 0) {
        toast.error("No Promos data to download");
        return;
    }

    // Use filtered data if search query exists, otherwise use all promos
    const dataToExport = q.value.trim() ? filtered.value : promos.value;

    // Validate that there's data to export after filtering
    if (dataToExport.length === 0) {
        toast.error("No Promos found to download");
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
        const headers = ["ID", "Promo Name", "Type", "Discount Amount", "Start Date", "End Date", "Min Purchase", "Max Discount", "Status", "Description"];

        const rows = data.map((promo) => {
            return [
                `${promo.id || ""}`,
                `"${promo.name || ""}"`,
                `"${promo.type || ""}"`,
                `${promo.discount_amount || ""}`,
                `"${dateFmt(promo.start_date) || ""}"`,
                `"${dateFmt(promo.end_date) || ""}"`,
                `${promo.min_purchase || ""}`,
                `${promo.max_discount || ""}`,
                `"${promo.status || ""}"`,
                `"${promo.description || ""}"`,
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
            `Promos_${new Date().toISOString().split("T")[0]}.csv`
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
        const doc = new jsPDF("l", "mm", "a4"); // Landscape for more columns

        doc.setFontSize(18);
        doc.setFont("helvetica", "bold");
        doc.text("Promos Report", 14, 20);

        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        const currentDate = new Date().toLocaleString();
        doc.text(`Generated on: ${currentDate}`, 14, 28);
        doc.text(`Total Promos: ${data.length}`, 14, 34);

        const tableColumns = ["ID", "Name", "Type", "Discount", "Start Date", "End Date", "Min Purchase", "Max Discount", "Status"];

        const tableRows = data.map((promo) => {
            return [
                promo.id || "",
                promo.name || "",
                promo.type === "flat" ? "Flat" : "Percent",
                promo.discount_amount || "",
                dateFmt(promo.start_date) || "",
                dateFmt(promo.end_date) || "",
                formatCurrencySymbol(promo.min_purchase) || "",
                promo.max_discount ? formatCurrencySymbol(promo.max_discount) : "N/A",
                promo.status === "active" ? "Active" : "Inactive",
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

        const fileName = `Promos_${new Date().toISOString().split("T")[0]}.pdf`;
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

        const worksheetData = data.map((promo) => {
            return {
                "ID": promo.id || "",
                "Promo Name": promo.name || "",
                "Type": promo.type === "flat" ? "Flat" : "Percent",
                "Discount Amount": promo.discount_amount || "",
                "Start Date": dateFmt(promo.start_date) || "",
                "End Date": dateFmt(promo.end_date) || "",
                "Min Purchase": promo.min_purchase || "",
                "Max Discount": promo.max_discount || "",
                "Status": promo.status === "active" ? "Active" : "Inactive",
                "Description": promo.description || "",
            };
        });

        const workbook = XLSX.utils.book_new();
        const worksheet = XLSX.utils.json_to_sheet(worksheetData);

        worksheet["!cols"] = [
            { wch: 8 },  // ID
            { wch: 25 }, // Promo Name
            { wch: 12 }, // Type
            { wch: 15 }, // Discount Amount
            { wch: 15 }, // Start Date
            { wch: 15 }, // End Date
            { wch: 15 }, // Min Purchase
            { wch: 15 }, // Max Discount
            { wch: 12 }, // Status
            { wch: 30 }, // Description
        ];

        XLSX.utils.book_append_sheet(workbook, worksheet, "Promos");

        const metaData = [
            { Info: "Report", Value: "Promos Export" },
            { Info: "Generated On", Value: new Date().toLocaleString() },
            { Info: "Total Records", Value: data.length },
            { Info: "Exported By", Value: "Inventory Management System" },
        ];
        const metaSheet = XLSX.utils.json_to_sheet(metaData);

        XLSX.utils.book_append_sheet(workbook, metaSheet, "Report Info");

        const fileName = `Promos_${new Date().toISOString().split("T")[0]}.xlsx`;
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

        <Head title="Promo" />
        <div class="page-wrapper">
            <h4 class="fw-semibold mb-3">Promo Management</h4>

            <Tabs value="0" class="w-100">
                <!-- ====== TAB HEADERS ====== -->
                <TabList>
                    <Tab value="0">Promos</Tab>
                    <Tab value="1">Promo Scope</Tab>
                </TabList>

                <!-- ====== TAB PANELS ====== -->
                <TabPanels>
                    <!-- === PROMOS TAB === -->
                    <TabPanel value="0">
                        <!-- === PROMOS TAB === -->
                        <TabPanel value="0">
                            <div class="mt-3">
                                <!-- KPI Cards -->
                                <div class="row g-3">
                                    <div v-for="stat in promoStats" :key="stat.label" class="col-md-6 col-xl-3">
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

                                <!-- Table card -->
                                <div class="card border-0 shadow-lg rounded-4 mt-0">
                                    <div class="card-body">
                                        <!-- Toolbar -->
                                        <div
                                            class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                                            <h5 class="mb-0 fw-semibold">Promos</h5>

                                            <div class="d-flex flex-wrap gap-2 align-items-center">

                                                <FilterModal v-model="filters" title="Promos"
                                                    modalId="promosFilterModal" modalSize="modal-lg" :sortOptions="[
                                                        { value: 'name_asc', label: 'Name: A to Z' },
                                                        { value: 'name_desc', label: 'Name: Z to A' },
                                                        { value: 'discount_asc', label: 'Discount: Low to High' },
                                                        { value: 'discount_desc', label: 'Discount: High to Low' },
                                                        { value: 'date_asc', label: 'Start Date: Oldest First' },
                                                        { value: 'date_desc', label: 'Start Date: Newest First' },
                                                    ]" :categories="promoTypesForFilter" categoryLabel="Promo Type" statusLabel="Promo Status"
                                                    :showPriceRange="true" priceRangeLabel="Discount Amount Range"
                                                    :showDateRange="true" @apply="handleFilterApply"
                                                    @clear="handleFilterClear" />

                                                <div class="search-wrap">
                                                    <i class="bi bi-search"></i>
                                                    <input type="email" name="email" autocomplete="email"
                                                        style="position: absolute; left: -9999px; width: 1px; height: 1px;"
                                                        tabindex="-1" aria-hidden="true" />

                                                    <input v-if="isReady" :id="inputId" v-model="q" :key="searchKey"
                                                        class="form-control search-input" placeholder="Search"
                                                        type="search" autocomplete="new-password" :name="inputId"
                                                        role="presentation" @focus="handleFocus" />
                                                    <input v-else class="form-control search-input" placeholder="Search"
                                                        disabled type="text" />
                                                </div>



                                                <button data-bs-toggle="modal" data-bs-target="#promoModal"
                                                    @click="resetModal"
                                                    class="d-flex align-items-center gap-1 px-4 py-2 rounded-pill btn btn-primary text-white">
                                                    <Plus class="w-4 h-4" /> Add Promo
                                                </button>

                                                <ImportFile label="Import Promos" :sampleHeaders="sampleHeaders"
                                                    :sampleData="sampleData" @on-import="handleImport" />

                                                <div class="dropdown">
                                                    <button
                                                        class="btn btn-outline-secondary rounded-pill py-2 btn-sm px-4 dropdown-toggle"
                                                        data-bs-toggle="dropdown">
                                                        Export
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end shadow rounded-4 py-2">
                                                        <li>
                                                            <a class="dropdown-item py-2" href="javascript:;"
                                                                @click="onDownload('pdf')">Export as PDF</a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item py-2" href="javascript:;"
                                                                @click="onDownload('excel')">Export as Excel</a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item py-2" href="javascript:;"
                                                                @click="onDownload('csv')">
                                                                Export as CSV
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Table -->
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead class="border-top small text-muted">
                                                    <tr>
                                                        <th>S.#</th>
                                                        <th>Name</th>
                                                        <th>Type</th>
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
                                                        <td>{{ dateFmt(row.start_date) }}</td>
                                                        <td>{{ dateFmt(row.end_date) }}</td>
                                                        <td>{{ formatCurrencySymbol(row.min_purchase) }}</td>
                                                        <td>
                                                            {{ row.max_discount ? formatCurrencySymbol(row.max_discount)
                                                                :
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

                                                                <ConfirmModal :title="'Confirm Status Change'"
                                                                    :message="`Are you sure you want to set ${row.name} to ${row.status === 'active' ? 'Inactive' : 'Active'}?`"
                                                                    :showStatusButton="true" confirmText="Yes, Change"
                                                                    cancelText="Cancel" :status="row.status"
                                                                    @confirm="toggleStatus(row)">
                                                                    <template #trigger>
                                                                        <!-- Toggle Switch -->
                                                                        <button
                                                                            class="relative inline-flex items-center w-8 h-4 rounded-full transition-colors duration-300 focus:outline-none"
                                                                            :class="row.status === 'active' ? 'bg-green-500 hover:bg-green-600' : 'bg-red-400 hover:bg-red-500'"
                                                                            :title="row.status === 'active' ? 'Set Inactive' : 'Set Active'">
                                                                            <!-- Circle -->
                                                                            <span
                                                                                class="absolute left-0.5 top-0.5 w-3 h-3 bg-white rounded-full shadow transform transition-transform duration-300"
                                                                                :class="row.status === 'active' ? 'translate-x-4' : 'translate-x-0'"></span>
                                                                        </button>
                                                                    </template>
                                                                </ConfirmModal>
                                                            </div>
                                                        </td>
                                                    </tr>

                                                    <tr v-if="filtered.length === 0">
                                                        <td colspan="9" class="text-center text-muted py-4">
                                                            No promos found.
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- ================== Add/Edit Promo Modal ================== -->
                                <div class="modal fade" id="promoModal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg modal-dialog-centered">
                                        <div class="modal-content rounded-4">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-semibold">
                                                    {{ editingPromo ? "Edit Promo" : "Add New Promo" }}
                                                </h5>
                                                <button
                                                    class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                                                    @click="resetModal" data-bs-dismiss="modal" aria-label="Close"
                                                    title="Close">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                        stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>


                                            </div>

                                            <div class="modal-body">
                                                <div class="row g-3">
                                                    <!-- Name -->
                                                    <div class="col-12">
                                                        <label class="form-label">Promo Name</label>
                                                        <input v-model="promoForm.name" type="text" class="form-control"
                                                            :class="{
                                                                'is-invalid': promoFormErrors.name,
                                                            }" placeholder="Enter promo name" />
                                                        <small v-if="promoFormErrors.name" class="text-danger">
                                                            {{ promoFormErrors.name[0] }}
                                                        </small>
                                                    </div>

                                                    <!-- Type -->
                                                    <div class="col-md-6">
                                                        <label class="form-label">Discount Type</label>
                                                        <Select v-model="promoForm.type" :options="discountOptions"
                                                            optionLabel="label" appendTo="self" :autoZIndex="true"
                                                            :baseZIndex="2000" optionValue="value" class="form-select"
                                                            :class="{ 'is-invalid': promoFormErrors.type }" />
                                                        <small v-if="promoFormErrors.type" class="text-danger">
                                                            {{ promoFormErrors.type[0] }}
                                                        </small>
                                                    </div>


                                                    <!-- Status -->
                                                    <div class="col-md-6">
                                                        <label class="form-label">Status</label>
                                                        <Select v-model="promoForm.status" :options="statusOptions"
                                                            optionLabel="label" optionValue="value" class="form-select"
                                                            appendTo="self" :autoZIndex="true" :baseZIndex="2000"
                                                            :class="{ 'is-invalid': promoFormErrors.status }">
                                                        </Select>
                                                        <small v-if="promoFormErrors.status" class="text-danger">
                                                            {{ promoFormErrors.status[0] }}
                                                        </small>
                                                    </div>
                                                    <!-- Discount Amount -->
                                                    <div class="col-12">
                                                        <label class="form-label">Discount Amount</label>
                                                        <input v-model="promoForm.discount_amount" type="number"
                                                            class="form-control" :class="{
                                                                'is-invalid': promoFormErrors.discount_amount,
                                                            }" placeholder="Enter discount amount" />
                                                        <small v-if="promoFormErrors.discount_amount"
                                                            class="text-danger">
                                                            {{ promoFormErrors.discount_amount[0] }}
                                                        </small>
                                                    </div>
                                                    <small v-if="promoFormErrors.name" class="text-danger">
                                                        {{ promoFormErrors.name[0] }}
                                                    </small>
                                                    <!-- Start Date -->
                                                    <div class="col-md-6">
                                                        <label class="form-label">Start Date</label>

                                                        <VueDatePicker v-model="promoForm.start_date" :format="dateFmt"
                                                            :min-date="new Date()" :enableTimePicker="false"
                                                            placeholder="Select Start date" :class="{
                                                                'is-invalid': promoFormErrors.start_date,
                                                            }" />
                                                        <small v-if="promoFormErrors.start_date" class="text-danger">
                                                            {{ promoFormErrors.start_date[0] }}
                                                        </small>
                                                    </div>

                                                    <!-- End Date -->
                                                    <div class="col-md-6">
                                                        <label class="form-label">End Date</label>
                                                        <VueDatePicker v-model="promoForm.end_date" :format="dateFmt"
                                                            :min-date="new Date()" :enableTimePicker="false"
                                                            placeholder="Select End date" :class="{
                                                                'is-invalid': promoFormErrors.end_date,
                                                            }" />
                                                        <small v-if="promoFormErrors.end_date" class="text-danger">
                                                            {{ promoFormErrors.end_date[0] }}
                                                        </small>
                                                    </div>

                                                    <!-- Min Purchase -->
                                                    <div class="col-md-6">
                                                        <label class="form-label">Minimum Purchase Amount</label>
                                                        <input v-model="promoForm.min_purchase" type="number"
                                                            step="0.01" class="form-control" :class="{
                                                                'is-invalid': promoFormErrors.min_purchase,
                                                            }" placeholder="0.00" />
                                                        <small v-if="promoFormErrors.min_purchase" class="text-danger">
                                                            {{ promoFormErrors.min_purchase[0] }}
                                                        </small>
                                                    </div>

                                                    <!-- Max Discount -->
                                                    <div class="col-md-6">
                                                        <label class="form-label">Maximum Discount (Optional)</label>
                                                        <input v-model="promoForm.max_discount" type="number"
                                                            step="0.01" class="form-control" :class="{
                                                                'is-invalid': promoFormErrors.max_discount,
                                                            }" placeholder="0.00" />
                                                        <small v-if="promoFormErrors.max_discount" class="text-danger">
                                                            {{ promoFormErrors.max_discount[0] }}
                                                        </small>
                                                    </div>

                                                    <!-- Description -->
                                                    <div class="col-12">
                                                        <label class="form-label">Description (Optional)</label>
                                                        <textarea v-model="promoForm.description" class="form-control"
                                                            rows="3" :class="{
                                                                'is-invalid': promoFormErrors.description,
                                                            }" placeholder="Enter promo description"></textarea>
                                                        <small v-if="promoFormErrors.description" class="text-danger">
                                                            {{ promoFormErrors.description[0] }}
                                                        </small>
                                                    </div>
                                                </div>

                                                <hr class="my-4" />

                                                <div class="mt-4">
                                                    <button class="btn btn-primary rounded-pill px-4"
                                                        :disabled="submitting" @click="submitPromo()">
                                                        <template v-if="submitting">
                                                            <span class="spinner-border spinner-border-sm me-2"></span>
                                                            Saving...
                                                        </template>
                                                        <template v-else>
                                                            {{ editingPromo ? "Save" : "Save" }}
                                                        </template>
                                                    </button>

                                                    <button class="btn btn-secondary rounded-pill px-4 ms-2"
                                                        data-bs-dismiss="modal" @click="resetModal">
                                                        Cancel
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- /modal -->
                            </div>
                        </TabPanel>
                    </TabPanel>

                    <!-- === PROMO SCOPE TAB === -->
                    <TabPanel value="1">
                        <div class="mt-3">

                            <!-- Table card -->
                            <div class="card border-0 shadow-lg rounded-4 mt-0">
                                <div class="card-body">
                                    <!-- Toolbar -->
                                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0 fw-semibold">Promo Scope</h5>

                                        <div class="d-flex flex-wrap gap-2 align-items-center">
                                            <button data-bs-toggle="modal" data-bs-target="#promoScopeModal"
                                                @click="resetPromoScopeModal"
                                                class="d-flex align-items-center gap-1 px-4 py-2 rounded-pill btn btn-primary text-white">
                                                <Plus class="w-4 h-4" /> Add Promo Scope
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Table -->
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead class="border-top small text-muted">
                                                <tr>
                                                    <th>S.#</th>
                                                    <th>Promo Name</th>
                                                    <th>Meals</th>
                                                    <th>Menu Items</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(scope, i) in promoScopes" :key="scope.id">
                                                    <td>{{ i + 1 }}</td>
                                                    <td>
                                                        <span v-if="scope.promos && scope.promos.length > 0">
                                                            {{scope.promos.map(p => p.name).join(', ')}}
                                                        </span>
                                                        <span v-else class="text-muted">N/A</span>
                                                    </td>

                                                    <td>
                                                        <span v-if="scope.meals && scope.meals.length > 0">
                                                            {{scope.meals.map(m => m.name).join(', ')}}
                                                        </span>
                                                        <span v-else class="text-muted">None</span>
                                                    </td>
                                                    <td>
                                                        <span v-if="scope.menu_items && scope.menu_items.length > 0">
                                                            {{scope.menu_items.map(m => m.name).join(', ')}}
                                                        </span>
                                                        <span v-else class="text-muted">None</span>
                                                    </td>
                                                    <td class="text-center">
                                                        <button @click="editPromoScope(scope)" title="Edit"
                                                            class="p-2 rounded-full text-blue-600 hover:bg-blue-100">
                                                            <Pencil class="w-4 h-4" />
                                                        </button>
                                                    </td>
                                                </tr>

                                                <tr v-if="promoScopes.length === 0">
                                                    <td colspan="5" class="text-center text-muted py-4">
                                                        No promo scopes found.
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- ================== Add/Edit Promo Scope Modal ================== -->
                            <div class="modal fade" id="promoScopeModal" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <div class="modal-content rounded-4">
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-semibold">
                                                {{ editingPromoScope ? "Edit Promo Scope" : "Add New Promo Scope" }}
                                            </h5>

                                            <button
                                                class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                                                data-bs-dismiss="modal" aria-label="Close" title="Close"
                                                @click="resetPromoScopeModal">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>

                                        <div class="modal-body">


                                            <div class="row g-3">
                                                <!-- Select Promos (multiple) -->
                                                <div class="col-12">
                                                    <label class="form-label">Select Promos *</label>
                                                    <MultiSelect v-model="promoScopeForm.promos" :options="promos"
                                                        optionLabel="name" optionValue="id" placeholder="Select promos"
                                                        class="w-100" display="chip" appendTo="self" :autoZIndex="true"
                                                        :baseZIndex="2000" :filter="true"
                                                        :class="{ 'is-invalid': promoScopeFormErrors.promos }" />
                                                    <small v-if="promoScopeFormErrors.promos" class="text-danger">
                                                        {{ promoScopeFormErrors.promos[0] }}
                                                    </small>
                                                </div>


                                                <!-- Select Meals -->
                                                <div class="col-md-6">
                                                    <label class="form-label">Select Meals</label>
                                                    <MultiSelect v-model="promoScopeForm.meals" :options="mealsData"
                                                        optionLabel="name" optionValue="id" placeholder="Select meals"
                                                        class="w-100" display="chip" appendTo="self" :autoZIndex="true"
                                                        :baseZIndex="2000" :filter="true"
                                                        :class="{ 'is-invalid': promoScopeFormErrors.meals }" />
                                                    <small v-if="promoScopeFormErrors.meals" class="text-danger">
                                                        {{ promoScopeFormErrors.meals[0] }}
                                                    </small>

                                                </div>

                                                <!-- Select Menu Items -->
                                                <div class="col-md-6">
                                                    <label class="form-label">Select Menu Items</label>
                                                    <MultiSelect v-model="promoScopeForm.menu_items"
                                                        :options="menuItemsData" optionLabel="name" optionValue="id"
                                                        placeholder="Select menu items" class="w-100" display="chip"
                                                        appendTo="self" :autoZIndex="true" :baseZIndex="2000"
                                                        :filter="true"
                                                        :class="{ 'is-invalid': promoScopeFormErrors.menu_items }" />
                                                    <small v-if="promoScopeFormErrors.menu_items" class="text-danger">
                                                        {{ promoScopeFormErrors.menu_items[0] }}
                                                    </small>

                                                </div>
                                            </div>

                                            <hr class="my-4" />

                                            <div class="mt-4 text-end">
                                                <button class="btn btn-secondary rounded-pill px-4 me-2"
                                                    data-bs-dismiss="modal" @click="resetPromoScopeModal">
                                                    Cancel
                                                </button>
                                                <button class="btn btn-primary rounded-pill px-4" :disabled="submitting"
                                                    @click="submitPromoScope">
                                                    <template v-if="submitting">
                                                        <span class="spinner-border spinner-border-sm me-2"></span>
                                                        Saving...
                                                    </template>
                                                    <template v-else>
                                                        Save
                                                    </template>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- /modal -->
                        </div>
                    </TabPanel>
                </TabPanels>
            </Tabs>
        </div>
    </Master>
</template>

<style scoped>
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
    padding-left: 40px;
    border-radius: 50px;
}

.img-chip {
    width: 40px;
    height: 40px;
    background: #f8f9fa;
}


.dark .p-select {
    background-color: #121212 !important;
    color: #fff !important;
}

.dark .p-select-label {
    color: #fff !important;
}

.dark .p-select-list {
    background-color: #121212 !important;
    color: #fff !important;
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

.dark .p-tabpanels {
    background-color: #222 !important;
}

:global(.dark .p-tablist-tab-list) {
    background: #212121 !important;
}

.p-tabpanels {
    background-color: #fff !important;
}

:global(.dark .p-tablist-content) {
    background: #212121 !important;
    color: #fff !important;
}


.p-tab {
    color: #000 !important;
}

:global(.dark .p-tab) {
    color: #fff !important;
}

.p-tablist-active-bar {
    background-color: #1c0d82;
}
</style>