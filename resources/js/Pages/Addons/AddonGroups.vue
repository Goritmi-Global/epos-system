<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted, nextTick } from "vue";
import { Layers, CheckCircle, XCircle, AlertCircle, Pencil, Plus } from "lucide-vue-next";
import { toast } from "vue3-toastify";
import axios from "axios";
import Select from "primevue/select";
import ConfirmModal from "@/Components/ConfirmModal.vue";
import { Head } from "@inertiajs/vue3";
import { jsPDF } from "jspdf";
import autoTable from "jspdf-autotable";
import * as XLSX from "xlsx";
import FilterModal from "@/Components/FilterModal.vue";
import ImportFile from "@/Components/importFile.vue";

/* ============================================
   DATA & STATE MANAGEMENT
============================================ */

// Main data store for addon groups
const addonGroups = ref([]);
// Store the last applied filters
const appliedFilters = ref({
    sortBy: "",
    stockStatus: "",
    priceMin: null,
    priceMax: null,
});

// Form state for create/edit modal
const addonGroupForm = ref({
    name: "",
    min_select: 0,
    max_select: 1,
    description: "",
    status: "active",
});

const filters = ref({
    sortBy: "",
    stockStatus: "", // This will be "Group Status"
    priceMin: null,
    priceMax: null,
});

// Track if we're editing (null = create mode, object = edit mode)
const editingGroup = ref(null);

// Loading states
const submitting = ref(false);
const loading = ref(false);

const confirmModalKey = ref(0);

// Validation errors from backend
const formErrors = ref({});

// Status options for dropdown
const statusOptions = [
    { label: "Active", value: "active" },
    { label: "Inactive", value: "inactive" },
];

/* ============================================
   FETCH ADDON GROUPS FROM API
============================================ */

/**
 * Fetch all addon groups from the backend
 * Called on component mount and after create/update/delete operations
 */
const fetchAddonGroups = async () => {
    loading.value = true;
    try {
        const res = await axios.get("/api/addon-groups/all");
        addonGroups.value = res.data.data;
    } catch (err) {
        console.error("Failed to fetch addon groups:", err);
        toast.error("Failed to load addon groups");
    } finally {
        loading.value = false;
    }
};

/* ============================================
   LIFECYCLE HOOKS
============================================ */

onMounted(async () => {
    // Reset search on mount
    q.value = "";
    searchKey.value = Date.now();
    await nextTick();

    // Delay to prevent browser autofill
    setTimeout(() => {
        isReady.value = true;

        // Force clear any autofill
        const input = document.getElementById(inputId);
        if (input) {
            input.value = '';
            q.value = '';
        }
    }, 100);

    // Fetch initial data
    fetchAddonGroups();
   const filterModal = document.getElementById('addonGroupsFilterModal');
    if (filterModal) {
        filterModal.addEventListener('hidden.bs.modal', () => {
            // Reset filters to last applied state when modal closes
            filters.value = {
                sortBy: appliedFilters.value.sortBy || "",
                stockStatus: appliedFilters.value.stockStatus || "",
                priceMin: appliedFilters.value.priceMin || null,
                priceMax: appliedFilters.value.priceMax || null,
            };
        });
    }
});

/* ============================================
   KPI STATISTICS CARDS
============================================ */

/**
 * Computed statistics for dashboard cards
 * Updates automatically when addonGroups changes
 */
const groupStats = computed(() => [
    {
        label: "Total Groups",
        value: addonGroups.value.length,
        icon: Layers,
        iconBg: "bg-light-primary",
        iconColor: "text-primary",
    },
    {
        label: "Active Groups",
        value: addonGroups.value.filter((g) => g.status === "active").length,
        icon: CheckCircle,
        iconBg: "bg-light-success",
        iconColor: "text-success",
    },
    {
        label: "Inactive Groups",
        value: addonGroups.value.filter((g) => g.status === "inactive").length,
        icon: XCircle,
        iconBg: "bg-light-danger",
        iconColor: "text-danger",
    },
    {
        label: "Total Addons",
        value: addonGroups.value.reduce((sum, g) => sum + (g.addons_count || 0), 0),
        icon: AlertCircle,
        iconBg: "bg-light-warning",
        iconColor: "text-warning",
    },
]);

/* ============================================
   SEARCH FUNCTIONALITY
============================================ */

// Search query
const q = ref("");

// Unique key for search input (prevents autofill issues)
const searchKey = ref(Date.now());
const inputId = `search-${Math.random().toString(36).substr(2, 9)}`;
const isReady = ref(false);

/**
 * Filter groups based on search query
 * Searches in: name
 */
const filteredGroups = computed(() => {
    let filtered = addonGroups.value;

    // Filter by search query
    const searchTerm = q.value.trim().toLowerCase();
    if (searchTerm) {
        filtered = filtered.filter((group) =>
            group.name.toLowerCase().includes(searchTerm)
        );
    }

    // Filter by status
    if (filters.value.stockStatus) {
        filtered = filtered.filter((group) => group.status === filters.value.stockStatus);
    }

    // Filter by addons count range (using priceMin/priceMax for count range)
    if (filters.value.priceMin !== null && filters.value.priceMin !== "") {
        filtered = filtered.filter((group) => (group.addons_count || 0) >= parseInt(filters.value.priceMin));
    }
    if (filters.value.priceMax !== null && filters.value.priceMax !== "") {
        filtered = filtered.filter((group) => (group.addons_count || 0) <= parseInt(filters.value.priceMax));
    }

    // Apply sorting
    if (filters.value.sortBy) {
        switch (filters.value.sortBy) {
            case "name_asc":
                filtered.sort((a, b) => a.name.localeCompare(b.name));
                break;
            case "name_desc":
                filtered.sort((a, b) => b.name.localeCompare(a.name));
                break;
            case "addons_asc":
                filtered.sort((a, b) => (a.addons_count || 0) - (b.addons_count || 0));
                break;
            case "addons_desc":
                filtered.sort((a, b) => (b.addons_count || 0) - (a.addons_count || 0));
                break;
            case "newest":
                filtered.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                break;
            case "oldest":
                filtered.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                break;
        }
    }

    return filtered;
});


const handleFilterApply = (appliedFilters) => {
    filters.value = { ...filters.value, ...appliedFilters };
     appliedFilters.value = { ...filters.value };
    console.log("Filters applied:", filters.value);
};

const handleFilterClear = () => {
    filters.value = {
        sortBy: "",
        stockStatus: "",
        priceMin: null,
        priceMax: null,
    };
    appliedFilters.value = {
        sortBy: "",
        stockStatus: "",
        priceMin: null,
        priceMax: null,
    };
    console.log("Filters cleared");
};

/**
 * Handle focus on search input (prevents autofill)
 */
const handleFocus = (event) => {
    event.target.setAttribute('autocomplete', 'off');
};

/* ============================================
   MODAL MANAGEMENT
============================================ */

/**
 * Reset modal form to initial state
 * Called when opening modal for create or after closing
 */
const resetModal = () => {
    addonGroupForm.value = {
        name: "",
        min_select: 0,
        max_select: 1,
        description: "",
        status: "active",
    };
    editingGroup.value = null;
    formErrors.value = {};
};

/**
 * Open modal in edit mode with existing group data
 */
const editRow = (row) => {
    editingGroup.value = row;

    // Populate form with existing data
    addonGroupForm.value = {
        name: row.name,
        min_select: row.min_select,
        max_select: row.max_select,
        description: row.description || "",
        status: row.status,
    };

    // Open Bootstrap modal
    const modalEl = document.getElementById("addonGroupModal");
    const bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();
};

/* ============================================
   CREATE / UPDATE OPERATIONS
============================================ */

/**
 * Submit form (handles both create and update)
 * Validates min_select <= max_select on frontend before sending
 */
const submitAddonGroup = async () => {
    // Frontend validation
    if (addonGroupForm.value.min_select > addonGroupForm.value.max_select) {
        toast.error("Minimum select cannot be greater than maximum select");
        return;
    }

    submitting.value = true;
    formErrors.value = {};

    try {
        if (editingGroup.value) {
            // UPDATE existing group
            await axios.post(
                `/api/addon-groups/${editingGroup.value.id}`,
                addonGroupForm.value
            );
            toast.success("Addon group updated successfully");
        } else {
            // CREATE new group
            await axios.post("/api/addon-groups", addonGroupForm.value);
            toast.success("Addon group created successfully");
        }

        // Close modal
        const modal = bootstrap.Modal.getInstance(
            document.getElementById("addonGroupModal")
        );
        modal?.hide();

        // Reset form and refresh data
        resetModal();
        await fetchAddonGroups();
    } catch (err) {
        console.error("❌ Error:", err.response?.data || err.message);

        // Handle validation errors (422)
        if (err.response?.status === 422 && err.response?.data?.errors) {
            formErrors.value = err.response.data.errors;

            // Show all error messages
            const errorMessages = Object.values(err.response.data.errors).flat();
            toast.error(errorMessages.join("\n"));
        } else {
            // Handle other errors
            const errorMessage = err.response?.data?.message || "Failed to save addon group";
            toast.error(errorMessage);
        }
    } finally {
        submitting.value = false;
    }
     setTimeout(() => {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => backdrop.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }, 100);
};

/* ============================================
   TOGGLE STATUS (ACTIVE/INACTIVE)
============================================ */

/**
 * Toggle group status between active and inactive
 * Updates immediately on success (optimistic UI update)
 */
const toggleStatus = async (row) => {
    const newStatus = row.status === "active" ? "inactive" : "active";

    try {
        await axios.patch(`/api/addon-groups/${row.id}/toggle-status`, {
            status: newStatus,
        });

        // Update local state immediately
        row.status = newStatus;
        toast.success(`Status changed to ${newStatus}`);

        // Force re-render of ConfirmModal to close it
        confirmModalKey.value++;

    } catch (error) {
        console.error("Failed to update status:", error);
        toast.error("Failed to update status");
    }
};

/* ============================================
   DELETE OPERATION
============================================ */

/**
 * Delete addon group
 * Shows confirmation modal before deletion
 */
const deleteGroup = async (row) => {
    if (!row?.id) return;

    try {
        await axios.delete(`/api/addon-groups/${row.id}`);
        toast.success("Addon group deleted successfully");
        await fetchAddonGroups();
    } catch (err) {
        console.error("❌ Delete error:", err.response?.data || err.message);

        // Show specific error message from backend
        const errorMessage = err.response?.data?.message || "Failed to delete addon group";
        toast.error(errorMessage);
    }
};


const onDownload = (type) => {

    if (!addonGroups.value || addonGroups.value.length === 0) {
        toast.error("No Addon Groups data to download");
        return;
    }


    const dataToExport = q.value.trim()
        ? filteredGroups.value
        : addonGroups.value;


    if (dataToExport.length === 0) {
        toast.error("No Addon Groups found to download");
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
    console.log("Data to export:", data);
    try {
        // Define CSV column headers
        const headers = ["Group Name", "Min Select", "Max Select", "Status", "Description", "Total Addons", "Addons List"];

        // Map addon groups data to CSV rows
        const rows = data.map((group) => {
            // Get only addon names (no price, no special characters)
            const addonsList = group.addons && group.addons.length > 0
                ? group.addons.map(addon => addon.name).join(" | ")
                : "No addons";

            return [
                `"${group.name || ""}"`,                              // Group Name
                `${group.min_select || 0}`,                           // Min Select
                `${group.max_select || 0}`,                           // Max Select
                `"${group.status || "active"}"`,                      // Status
                `"${(group.description || "").replace(/"/g, '""')}"`, // Description (escape quotes)
                `${group.addons_count || 0}`,                         // Total Addons Count
                `"${addonsList}"`,                                    // Addons List with prices
            ];
        });

        // Build CSV content: headers + data rows
        const csvContent = [
            headers.join(","),                    // Header row
            ...rows.map((r) => r.join(",")),      // Data rows
        ].join("\n");

        // Create blob from CSV content
        const blob = new Blob([csvContent], {
            type: "text/csv;charset=utf-8;",
        });
        const url = URL.createObjectURL(blob);

        // Create temporary download link
        const link = document.createElement("a");
        link.setAttribute("href", url);
        link.setAttribute(
            "download",
            `Addon_Groups_${new Date().toISOString().split("T")[0]}.csv`
        );

        // Trigger download
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        toast.success("CSV downloaded successfully");
    } catch (error) {
        console.error("CSV generation error:", error);
        toast.error(`CSV generation failed: ${error.message}`, {
            autoClose: 5000,
        });
    }
};

const downloadPDF = (data) => {
    try {
        // Initialize PDF document (Portrait, millimeters, A4 size)
        const doc = new jsPDF("p", "mm", "a4");

        // Add title
        doc.setFontSize(18);
        doc.setFont("helvetica", "bold");
        doc.text("Addon Groups with Addons Report", 14, 20);

        // Add metadata (generation date and total records)
        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        const currentDate = new Date().toLocaleString();
        doc.text(`Generated on: ${currentDate}`, 14, 28);
        doc.text(`Total Groups: ${data.length}`, 14, 34);

        // Define table columns
        const tableColumns = ["Group Name", "Min", "Max", "Status", "Total Addons", "Addons"];

        // Map addon groups data to table rows
        const tableRows = data.map((group) => {
            // Get only addon names (no price, no special characters)
            const addonsList = group.addons && group.addons.length > 0
                ? group.addons.map(addon => addon.name).join(", ")
                : "No addons";

            return [
                group.name || "",                           // Group Name
                group.min_select || 0,                      // Min Select
                group.max_select || 0,                      // Max Select
                (group.status || "active").toUpperCase(),   // Status
                group.addons_count || 0,                    // Total Addons Count
                addonsList,                                 // Addons list with prices
            ];
        });

        // Create styled table using autoTable plugin
        autoTable(doc, {
            head: [tableColumns],
            body: tableRows,
            startY: 40,
            styles: {
                fontSize: 8,
                cellPadding: 3,
                halign: "left",
                valign: "top",
                lineColor: [0, 0, 0],
                lineWidth: 0.1,
                overflow: "linebreak",
            },
            headStyles: {
                fillColor: [41, 128, 185],    // Blue header background
                textColor: 255,                // White text
                fontStyle: "bold",
            },
            alternateRowStyles: {
                fillColor: [245, 245, 245]    // Light gray alternate rows
            },
            columnStyles: {
                5: { cellWidth: 50 }           // Make addons column wider
            },
            margin: { left: 14, right: 14 },

            // Add page numbers at bottom
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

        // Save PDF file with timestamp
        const fileName = `Addon_Groups_${new Date().toISOString().split("T")[0]}.pdf`;
        doc.save(fileName);

        toast.success("PDF downloaded successfully");
    } catch (error) {
        console.error("PDF generation error:", error);
        toast.error(`PDF generation failed: ${error.message}`, { autoClose: 5000 });
    }
};

const downloadExcel = (data) => {
    try {
        // Validate XLSX library is available
        if (typeof XLSX === "undefined") {
            throw new Error("XLSX library is not loaded");
        }

        // ========== SHEET 1: MAIN ADDON GROUPS DATA ==========
        const worksheetData = data.map((group) => {
            // Format addons list with names only (no price, no special characters)
            const addonsList = group.addons && group.addons.length > 0
                ? group.addons.map(addon => addon.name).join(" | ")
                : "No addons";

            return {
                "Group Name": group.name || "",
                "Min Select": group.min_select || 0,
                "Max Select": group.max_select || 0,
                "Status": (group.status || "active").toUpperCase(),
                "Total Addons": group.addons_count || 0,
                "Description": group.description || "",
                "Addons List": addonsList,
            };
        });

        // Create workbook and first worksheet (Main Data sheet)
        const workbook = XLSX.utils.book_new();
        const worksheet = XLSX.utils.json_to_sheet(worksheetData);

        // Set column widths for better readability
        worksheet["!cols"] = [
            { wch: 20 }, // Group Name
            { wch: 12 }, // Min Select
            { wch: 12 }, // Max Select
            { wch: 12 }, // Status
            { wch: 14 }, // Total Addons
            { wch: 30 }, // Description
            { wch: 50 }, // Addons List
        ];

        // Add main data sheet to workbook
        XLSX.utils.book_append_sheet(workbook, worksheet, "Addon Groups");

        // ========== SHEET 2: DETAILED ADDONS BREAKDOWN ==========
        const addonDetailData = [];

        data.forEach((group) => {
            if (group.addons && group.addons.length > 0) {
                group.addons.forEach((addon) => {
                    addonDetailData.push({
                        "Group Name": group.name,
                        "Addon Name": addon.name,
                        "Price": addon.price || 0,
                        "Status": (addon.status || "active").toUpperCase(),
                        "Description": addon.description || "",
                        "Sort Order": addon.sort_order || 0,
                    });
                });
            }
        });

        const detailSheet = XLSX.utils.json_to_sheet(addonDetailData);
        detailSheet["!cols"] = [
            { wch: 20 }, // Group Name
            { wch: 20 }, // Addon Name
            { wch: 12 }, // Price
            { wch: 12 }, // Status
            { wch: 30 }, // Description
            { wch: 12 }, // Sort Order
        ];
        XLSX.utils.book_append_sheet(workbook, detailSheet, "Addons Details");

        // ========== SHEET 3: METADATA ==========
        const metaData = [
            { Info: "Report", Value: "Addon Groups with Addons Export" },
            { Info: "Generated On", Value: new Date().toLocaleString() },
            { Info: "Total Groups", Value: data.length },
            { Info: "Total Addons", Value: data.reduce((sum, g) => sum + (g.addons_count || 0), 0) },
            { Info: "Exported By", Value: "Inventory Management System" },
        ];
        const metaSheet = XLSX.utils.json_to_sheet(metaData);

        // Add metadata sheet to workbook
        XLSX.utils.book_append_sheet(workbook, metaSheet, "Report Info");

        // Save Excel file with timestamp
        const fileName = `Addon_Groups_${new Date().toISOString().split("T")[0]}.xlsx`;
        XLSX.writeFile(workbook, fileName);

        toast.success("Excel file downloaded successfully", { autoClose: 2500 });
    } catch (error) {
        console.error("Excel generation error:", error);
        toast.error(`Excel generation failed: ${error.message}`, { autoClose: 5000 });
    }
};


const sampleHeaders = ["Group Name", "Min Select", "Max Select", "Status", "Description"];
const sampleData = [
    ["Toppings", "0", "5", "active", "Pizza toppings selection"],
    ["Extras", "0", "3", "active", "Extra items"],
    ["Sauces", "1", "1", "active", "Choose one sauce"],
];

/* ============================================
   IMPORT HANDLER (ADD AFTER downloadExcel FUNCTION)
============================================ */

const handleImport = (data) => {
    if (!data || data.length <= 1) {
        toast.error("The imported file is empty.");
        return;
    }

    // Extract headers and rows
    const headers = data[0];
    const rows = data.slice(1);

    // Parse each row into group object
    const groupsToImport = rows.map((row) => {
        return {
            name: (row[0] || "").trim(),
            min_select: parseInt(row[1]) || 0,
            max_select: parseInt(row[2]) || 1,
            status: (row[3] || "active").toLowerCase().trim(),
            description: (row[4] || "").trim(),
        };
    }).filter(group => group.name.length > 0); // Remove empty rows

    if (groupsToImport.length === 0) {
        toast.error("No valid addon groups found in the file.");
        return;
    }

    // VALIDATION 1: Check min_select <= max_select
    const invalidSelects = groupsToImport.filter(g => g.min_select > g.max_select);
    if (invalidSelects.length > 0) {
        const names = invalidSelects.map(g => g.name).join(", ");
        toast.error(`Invalid: Min select > Max select for: ${names}`);
        return;
    }

    // VALIDATION 2: Check for duplicates in CSV
    const groupNames = groupsToImport.map(g => g.name.toLowerCase());
    const duplicatesInCSV = groupNames.filter((name, index) => groupNames.indexOf(name) !== index);

    if (duplicatesInCSV.length > 0) {
        toast.error(`Duplicate names in CSV: ${[...new Set(duplicatesInCSV)].join(", ")}`);
        return;
    }

    // VALIDATION 3: Check for duplicates in existing table
    const existingNames = addonGroups.value.map(g => g.name.toLowerCase());
    const duplicatesInTable = groupsToImport.filter(importGroup =>
        existingNames.includes(importGroup.name.toLowerCase())
    );

    if (duplicatesInTable.length > 0) {
        const names = duplicatesInTable.map(g => g.name).join(", ");
        toast.error(`Already exist in table: ${names}`);
        return;
    }

    // VALIDATION 4: Check status values
    const validStatuses = ["active", "inactive"];
    const invalidStatuses = groupsToImport.filter(g => !validStatuses.includes(g.status));

    if (invalidStatuses.length > 0) {
        toast.error("Status must be 'active' or 'inactive'.");
        return;
    }

    // All validations passed - send to API
    axios.post("/api/addon-groups/import", { groups: groupsToImport })
        .then((response) => {
            toast.success(response.data.message || "Import successful!");
            fetchAddonGroups();
        })
        .catch((error) => {
            const message = error.response?.data?.message || "Import failed";
            toast.error(message);
            console.error("Import error:", error);
             setTimeout(() => {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => backdrop.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }, 100);
        });
};
</script>

<template>
    <Master>

        <Head title="Addon Groups" />

        <div class="page-wrapper">
            <!-- Page Header -->
            <h4 class="fw-semibold mb-3">Addon Groups Management</h4>

            <!-- KPI Statistics Cards -->
            <div class="row g-3 mb-4">
                <div v-for="stat in groupStats" :key="stat.label" class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body d-flex align-items-center">
                            <!-- Icon -->
                            <div :class="[stat.iconBg, stat.iconColor]"
                                class="rounded-circle p-3 d-flex align-items-center justify-content-center me-3"
                                style="width: 56px; height: 56px">
                                <component :is="stat.icon" class="w-6 h-6" />
                            </div>

                            <!-- Value & Label -->
                            <div>
                                <h3 class="mb-0 fw-bold">{{ stat.value }}</h3>
                                <p class="text-muted mb-0 small">{{ stat.label }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Table Card -->
            <div class="card border-0 shadow-lg rounded-4">
                <div class="card-body">
                    <!-- Toolbar: Search & Add Button -->
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 fw-semibold">Addon Groups</h5>

                        <div class="d-flex flex-wrap gap-2 align-items-center">


                            <FilterModal v-model="filters" title="Addon Groups" modalId="addonGroupsFilterModal"
                                modalSize="modal-lg" :sortOptions="[
                                    { value: 'name_asc', label: 'Name: A to Z' },
                                    { value: 'name_desc', label: 'Name: Z to A' },
                                    { value: 'addons_asc', label: 'Addons Count: Low to High' },
                                    { value: 'addons_desc', label: 'Addons Count: High to Low' },
                                    { value: 'newest', label: 'Newest First' },
                                    { value: 'oldest', label: 'Oldest First' },
                                ]" :showPriceRange="false" :showStockStatus="false" :showDateRange="false" :showCategory="false"
                                statusLabel="Group Status" priceLabel="Addons Count Range"
                                priceMinPlaceholder="Min Count" priceMaxPlaceholder="Max Count"
                                @apply="handleFilterApply" @clear="handleFilterClear" />
                            <!-- Search Input -->
                            <div class="search-wrap">
                                <i class="bi bi-search"></i>

                                <!-- Hidden email input to prevent autofill -->
                                <input type="email" name="email" autocomplete="email"
                                    style="position: absolute; left: -9999px; width: 1px; height: 1px;" tabindex="-1"
                                    aria-hidden="true" />

                                <!-- Actual search input -->
                                <input v-if="isReady" :id="inputId" v-model="q" :key="searchKey"
                                    class="form-control search-input  rounded-pill" placeholder="Search groups..."
                                    type="search" autocomplete="new-password" :name="inputId" role="presentation"
                                    @focus="handleFocus" />
                                <input v-else class="form-control search-input  rounded-pill"
                                    placeholder="Search groups..." disabled type="text" />
                            </div>

                            <!-- Add Group Button -->
                            <button data-bs-toggle="modal" data-bs-target="#addonGroupModal" @click="resetModal"
                                class="d-flex align-items-center gap-1 px-4 py-2 rounded-pill btn btn-primary text-white">
                                <Plus class="w-4 h-4" /> Add Group
                            </button>

                            <ImportFile label="Import Groups" :sampleHeaders="sampleHeaders" :sampleData="sampleData"
                                @on-import="handleImport" />

                            <div class="dropdown">
                                <button class="btn btn-outline-secondary rounded-pill py-2 btn-sm px-4 dropdown-toggle"
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
                        <table class="table table-striped">
                            <thead class="border-top small text-muted">
                                <tr>
                                    <th>S.#</th>
                                    <th>Name</th>
                                    <th>Min Select</th>
                                    <th>Max Select</th>
                                    <th>Addons Count</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Loading State -->
                                <tr v-if="loading">
                                    <td colspan="7" class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Data Rows -->
                                <tr v-else v-for="(row, i) in filteredGroups" :key="row.id">
                                    <!-- Serial Number -->
                                    <td>{{ i + 1 }}</td>

                                    <!-- Group Name -->
                                    <td class="fw-semibold">{{ row.name }}</td>

                                    <!-- Min Select -->
                                    <td>

                                        {{ row.min_select }}

                                    </td>

                                    <!-- Max Select -->
                                    <td>

                                        {{ row.max_select }}

                                    </td>

                                    <!-- Addons Count -->
                                    <td>
                                        <span class="badge bg-primary px-3 py-2 rounded-pill">
                                            {{ row.addons_count || 0 }} addons
                                        </span>
                                    </td>

                                    <!-- Status Badge -->
                                    <td class="text-center">
                                        <span :class="row.status === 'active'
                                            ? 'badge bg-success px-4 py-2 rounded-pill'
                                            : 'badge bg-danger px-4 py-2 rounded-pill'
                                            ">
                                            {{ row.status === "active" ? "Active" : "Inactive" }}
                                        </span>
                                    </td>

                                    <!-- Action Buttons -->
                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center gap-3">
                                            <!-- Edit Button -->
                                            <button @click="editRow(row)" title="Edit"
                                                class="p-2 rounded-full text-blue-600 hover:bg-blue-100">
                                                <Pencil class="w-4 h-4" />
                                            </button>

                                            <!-- Toggle Status Switch -->
                                            <ConfirmModal :key="`confirm-${row.id}-${confirmModalKey}`"
                                                :title="'Confirm Status Change'"
                                                :message="`Are you sure you want to set ${row.name} to ${row.status === 'active' ? 'Inactive' : 'Active'}?`"
                                                :showStatusButton="true" confirmText="Yes, Change" cancelText="Cancel"
                                                :status="row.status" @confirm="toggleStatus(row)">
                                                <template #trigger>
                                                    <button
                                                        class="relative inline-flex items-center w-8 h-4 rounded-full transition-colors duration-300 focus:outline-none"
                                                        :class="row.status === 'active'
                                                            ? 'bg-green-500 hover:bg-green-600'
                                                            : 'bg-red-400 hover:bg-red-500'
                                                            "
                                                        :title="row.status === 'active' ? 'Set Inactive' : 'Set Active'">
                                                        <span
                                                            class="absolute left-0.5 top-0.5 w-3 h-3 bg-white rounded-full shadow transform transition-transform duration-300"
                                                            :class="row.status === 'active'
                                                                ? 'translate-x-4'
                                                                : 'translate-x-0'
                                                                "></span>
                                                    </button>
                                                </template>
                                            </ConfirmModal>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="!loading && filteredGroups.length === 0">
                                    <td colspan="7" class="text-center text-muted py-4">
                                        No addon groups found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ================== Add/Edit Modal ================== -->
            <div class="modal fade" id="addonGroupModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content rounded-4">
                        <!-- Modal Header -->
                        <div class="modal-header">
                            <h5 class="modal-title fw-semibold">
                                {{ editingGroup ? "Edit Addon Group" : "Add New Addon Group" }}
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

                        <!-- Modal Body -->
                        <div class="modal-body">
                            <div class="row g-3">
                                <!-- Group Name -->
                                <div class="col-12">
                                    <label class="form-label">Group Name *</label>
                                    <input v-model="addonGroupForm.name" type="text" class="form-control"
                                        :class="{ 'is-invalid': formErrors.name }"
                                        placeholder="e.g., Toppings, Extras, Sauces" />
                                    <small v-if="formErrors.name" class="text-danger">
                                        {{ formErrors.name[0] }}
                                    </small>
                                </div>

                                <!-- Min Select -->
                                <div class="col-md-6">
                                    <label class="form-label">Minimum Select *</label>
                                    <input v-model.number="addonGroupForm.min_select" type="number" min="0"
                                        class="form-control" :class="{ 'is-invalid': formErrors.min_select }"
                                        placeholder="0" />
                                    <small class="text-muted">
                                        Minimum items customer must select
                                    </small>
                                    <small v-if="formErrors.min_select" class="text-danger d-block">
                                        {{ formErrors.min_select[0] }}
                                    </small>
                                </div>

                                <!-- Max Select -->
                                <div class="col-md-6">
                                    <label class="form-label">Maximum Select *</label>
                                    <input v-model.number="addonGroupForm.max_select" type="number" min="1"
                                        class="form-control" :class="{ 'is-invalid': formErrors.max_select }"
                                        placeholder="1" />
                                    <small class="text-muted">
                                        Maximum items customer can select
                                    </small>
                                    <small v-if="formErrors.max_select" class="text-danger d-block">
                                        {{ formErrors.max_select[0] }}
                                    </small>
                                </div>

                                <!-- Status -->
                                <div class="col-12">
                                    <label class="form-label">Status *</label>
                                    <Select v-model="addonGroupForm.status" :options="statusOptions" optionLabel="label"
                                        optionValue="value" class="form-select" appendTo="self" :autoZIndex="true"
                                        :baseZIndex="2000" :class="{ 'is-invalid': formErrors.status }" />
                                    <small v-if="formErrors.status" class="text-danger">
                                        {{ formErrors.status[0] }}
                                    </small>
                                </div>

                                <!-- Description -->
                                <div class="col-12">
                                    <label class="form-label">Description (Optional)</label>
                                    <textarea v-model="addonGroupForm.description" class="form-control" rows="3"
                                        :class="{ 'is-invalid': formErrors.description }"
                                        placeholder="Enter group description..."></textarea>
                                    <small v-if="formErrors.description" class="text-danger">
                                        {{ formErrors.description[0] }}
                                    </small>
                                </div>
                            </div>

                            <hr class="my-4" />

                            <!-- Modal Actions -->
                            <div class="mt-4">
                                <button class="btn btn-primary rounded-pill px-4" :disabled="submitting"
                                    @click="submitAddonGroup">
                                    <template v-if="submitting">
                                        <span class="spinner-border spinner-border-sm me-2"></span>
                                        Saving...
                                    </template>
                                    <template v-else>
                                        {{ editingGroup ? "Save" : "Save" }}
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
            <!-- /modal -->
        </div>
    </Master>
</template>

<style scoped>
/* Custom styles for search input */
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
    min-width: 250px;
}

/* Hover effects for action buttons */
.p-2:hover {
    background-color: rgba(59, 130, 246, 0.1);
    border-radius: 50%;
}

.dark .p-select {
    background-color: #181818 !important;
    color: #fff !important;
}

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

:global(.dark .p-select) {
    background-color: #000 !important;
    color: #fff !important;
    border-color: #555 !important;
}

/* Options container */
:global(.dark .p-select-list-container) {
    background-color: #000 !important;
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

:global(.dark .p-select-dropdown) {
    background-color: #212121 !important;
    color: #fff !important;

}

:global(.dark .p-select-label) {
    color: #fff !important;
    background-color: #212121 !important;
}

:global(.dark .p-select-list) {
    background-color: #212121 !important;
}

:global(.dark .p-placeholder) {
    color: #aaa !important;
}

:global(.dark .dark .p-select) {
    background-color: #212121 !important;
}
</style>