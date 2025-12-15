<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted, watch, nextTick } from "vue";
import { Package, CheckCircle, XCircle, DollarSign, Pencil, Plus, Filter } from "lucide-vue-next";
import { toast } from "vue3-toastify";
import axios from "axios";
import Select from "primevue/select";
import ConfirmModal from "@/Components/ConfirmModal.vue";
import { useFormatters } from "@/composables/useFormatters";
import { Head } from "@inertiajs/vue3";
import { jsPDF } from "jspdf";
import autoTable from "jspdf-autotable";
import * as XLSX from "xlsx";
import FilterModal from "@/Components/FilterModal.vue";
import ImportFile from "@/Components/importFile.vue";
import Pagination from "@/Components/Pagination.vue";

const { formatCurrencySymbol } = useFormatters();

/* ============================================
   DATA & STATE MANAGEMENT
============================================ */

// Main data stores
const addons = ref([]);
const addonGroups = ref([]);

// Form state for create/edit modal
const addonForm = ref({
    name: "",
    addon_group_id: null,
    price: 0,
    description: "",
    status: "active",
    sort_order: 0,
});

// Store the last applied filters
const appliedFilters = ref({
    sortBy: "",
    stockStatus: "",
    priceMin: null,
    priceMax: null,
});

// Track if we're editing (null = create mode, object = edit mode)
const editingAddon = ref(null);

// Loading states
const submitting = ref(false);
const loading = ref(false);

// Validation errors from backend
const formErrors = ref({});

const confirmModalKey = ref(0);
const currentPage = ref(1);
const perPage = ref(10);
const totalItems = ref(0);
const paginationLinks = ref([]);

// Status options for dropdown
const statusOptions = [
    { label: "Active", value: "active" },
    { label: "Inactive", value: "inactive" },
];

// Filter by addon group (for the filter buttons)
const selectedGroupFilter = ref("all");

/* ============================================
   FETCH DATA FROM API
============================================ */

/**
 * Fetch all addons from the backend
 * Called on component mount and after create/update/delete operations
 */
const fetchAddons = async (page = 1) => {
    loading.value = true;
    try {
        const res = await axios.get("/api/addons/all", {
            params: {
                page: page,
                per_page: perPage.value,
                q: q.value.trim() || null,
                status: appliedFilters.value.stockStatus || null,
                category: appliedFilters.value.category || null,
                sort_by: appliedFilters.value.sortBy || null,
                price_min: appliedFilters.value.priceMin || null,
                price_max: appliedFilters.value.priceMax || null,
            }
        });

        addons.value = res.data.data || [];

        // ✅ Update pagination state
        if (res.data.pagination) {
            currentPage.value = res.data.pagination.current_page;
            totalItems.value = res.data.pagination.total;
            paginationLinks.value = res.data.pagination.links;
        }

    } catch (err) {
        console.error("Failed to fetch addons:", err);
        toast.error("Failed to load addons");
    } finally {
        loading.value = false;
    }
};


const handlePageChange = (url) => {
    if (!url || loading.value) return;

    const urlParams = new URLSearchParams(url.split('?')[1]);
    const page = urlParams.get('page');

    if (page) {
        fetchAddons(parseInt(page));
    }
};

/**
 * Fetch all active addon groups for dropdown
 * Used in the modal to select which group the addon belongs to
 */
const fetchAddonGroups = async () => {
    try {
        const res = await axios.get("/api/addon-groups/active");
        addonGroups.value = res.data.data;
    } catch (err) {
        console.error("Failed to fetch addon groups:", err);
        toast.error("Failed to load addon groups");
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
            input.value = "";
            q.value = "";
        }
    }, 100);

    // Fetch initial data
    await Promise.all([fetchAddons(), fetchAddonGroups()]);

    const filterModal = document.getElementById('addonsFilterModal');
    if (filterModal) {
        filterModal.addEventListener('hidden.bs.modal', () => {
            // Only clear if filters were NOT just applied
            if (!filtersJustApplied.value) {
                handleFilterClear();
            }
            // Reset the flag for next time
            filtersJustApplied.value = false;
        });
    }

});

/* ============================================
   KPI STATISTICS CARDS
============================================ */

/**
 * Computed statistics for dashboard cards
 * Updates automatically when addons changes
 */
const addonStats = computed(() => {
    // Calculate average price of all addons
    const avgPrice =
        addons.value.length > 0
            ? addons.value.reduce((sum, a) => sum + parseFloat(a.price), 0) / addons.value.length
            : 0;

    return [
        {
            label: "Total Addons",
            value: addons.value.length,
            icon: Package,
            iconBg: "bg-light-primary",
            iconColor: "text-primary",
        },
        {
            label: "Active Addons",
            value: addons.value.filter((a) => a.status === "active").length,
            icon: CheckCircle,
            iconBg: "bg-light-success",
            iconColor: "text-success",
        },
        {
            label: "Inactive Addons",
            value: addons.value.filter((a) => a.status === "inactive").length,
            icon: XCircle,
            iconBg: "bg-light-danger",
            iconColor: "text-danger",
        },
        {
            label: "Average Price",
            value: formatCurrencySymbol(avgPrice),
            icon: DollarSign,
            iconBg: "bg-light-warning",
            iconColor: "text-warning",
        },
    ];
});

/* ============================================
   SEARCH & FILTER FUNCTIONALITY
============================================ */

// Search query
const q = ref("");

const filters = ref({
    sortBy: "",
    stockStatus: "",
    priceMin: null,
    priceMax: null
});

// Unique key for search input (prevents autofill issues)
const searchKey = ref(Date.now());
const inputId = `search-${Math.random().toString(36).substr(2, 9)}`;
const isReady = ref(false);

/**
 * Get unique group names for filter buttons
 * Returns "All" + all unique addon group names
 */
const uniqueGroups = computed(() => {
    const groups = ["All"];
    const groupNames = [...new Set(addons.value.map((a) => a.addon_group?.name).filter(Boolean))];
    return [...groups, ...groupNames];
});


const filtersJustApplied = ref(false);

const handleFilterApply = (appliedFiltersData) => {
    appliedFilters.value = { ...appliedFiltersData };
    filters.value = { ...appliedFiltersData };
    currentPage.value = 1;
    selectedGroupFilter.value = "all";
    filtersJustApplied.value = true;
    fetchAddons(1);
};

const handleFilterClear = () => {
    filters.value = {
        sortBy: "",
        stockStatus: "",
        priceMin: null,
        priceMax: null,
        category: "",
    };
    appliedFilters.value = {
        sortBy: "",
        stockStatus: "",
        priceMin: null,
        priceMax: null,
        category: "",
    };
    currentPage.value = 1;
    selectedGroupFilter.value = "all";
    fetchAddons(1);
};

let searchTimeout = null;

watch(q, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage.value = 1;
        fetchAddons(1);
    }, 500);
});

const setGroupFilter = (group) => {
    selectedGroupFilter.value = group === "All" ? "all" : group;
    if (group === "All") {
        filters.value.category = "";
        appliedFilters.value.category = "";
    } else {
        const selectedGroup = addonGroups.value.find(g => g.name === group);
        if (selectedGroup) {
            filters.value.category = selectedGroup.id;
            appliedFilters.value.category = selectedGroup.id;
        }
    }

    currentPage.value = 1; // ✅ Reset to page 1
    fetchAddons(1); // ✅ Fetch with new group filter
};

/**
 * Handle focus on search input (prevents autofill)
 */
const handleFocus = (event) => {
    event.target.setAttribute("autocomplete", "off");
};

/* ============================================
   MODAL MANAGEMENT
============================================ */

/**
 * Reset modal form to initial state
 * Called when opening modal for create or after closing
 */
const resetModal = () => {
    addonForm.value = {
        name: "",
        addon_group_id: null,
        price: 0,
        description: "",
        status: "active",
        sort_order: 0,
    };
    editingAddon.value = null;
    formErrors.value = {};
};

/**
 * Open modal in edit mode with existing addon data
 */
const editRow = (row) => {
    editingAddon.value = row;

    // Populate form with existing data
    addonForm.value = {
        name: row.name,
        addon_group_id: row.addon_group_id,
        price: parseFloat(row.price),
        description: row.description || "",
        status: row.status,
        sort_order: row.sort_order || 0,
    };

    // Open Bootstrap modal
    const modalEl = document.getElementById("addonModal");
    const bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();
};

/* ============================================
   CREATE / UPDATE OPERATIONS
============================================ */

/**
 * Submit form (handles both create and update)
 * Validates required fields on frontend before sending
 */
const submitAddon = async () => {
    // Frontend validation
    if (!addonForm.value.name.trim()) {
        toast.error("Addon name is required");
        return;
    }

    if (!addonForm.value.addon_group_id) {
        toast.error("Please select an addon group");
        return;
    }

    if (addonForm.value.price < 0) {
        toast.error("Price cannot be negative");
        return;
    }

    submitting.value = true;
    formErrors.value = {};

    try {
        if (editingAddon.value) {
            // UPDATE existing addon
            await axios.post(`/api/addons/${editingAddon.value.id}`, addonForm.value);
            toast.success("Addon updated successfully");
        } else {
            // CREATE new addon
            await axios.post("/api/addons", addonForm.value);
            toast.success("Addon created successfully");
        }

        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById("addonModal"));
        modal?.hide();

        // Reset form and refresh data
        resetModal();
        await fetchAddons();
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
            const errorMessage = err.response?.data?.message || "Failed to save addon";
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
 * Toggle addon status between active and inactive
 * Updates immediately on success (optimistic UI update)
 */
const toggleStatus = async (row) => {
    const newStatus = row.status === "active" ? "inactive" : "active";

    try {
        await axios.patch(`/api/addons/${row.id}/toggle-status`, {
            status: newStatus,
        });

        // Update local state immediately
        row.status = newStatus;
        toast.success(`Status changed to ${newStatus}`);

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
 * Delete addon
 * Shows confirmation modal before deletion
 */
const deleteAddon = async (row) => {
    if (!row?.id) return;

    try {
        await axios.delete(`/api/addons/${row.id}`);
        toast.success("Addon deleted successfully");
        await fetchAddons();
    } catch (err) {
        console.error("❌ Delete error:", err.response?.data || err.message);

        // Show specific error message from backend
        const errorMessage = err.response?.data?.message || "Failed to delete addon";
        toast.error(errorMessage);
    }
};


const addonGroupOptions = computed(() => {
    return addonGroups.value.map((group) => ({
        label: `${group.name} (Min: ${group.min_select}, Max: ${group.max_select})`,
        value: group.id,
    }));
});

const addonGroupsForFilter = computed(() => {
    return addonGroups.value.map((group) => ({
        id: group.id,
        name: group.name,
    }));
});

const fetchAllAddonsForExport = async () => {
    try {
        loading.value = true;

        const res = await axios.get("/api/addons/all", {
            params: {
                export: 'all',
                q: q.value.trim() || null,
                status: appliedFilters.value.stockStatus || null,
                category: appliedFilters.value.category || null,
                sort_by: appliedFilters.value.sortBy || null,
                price_min: appliedFilters.value.priceMin || null,
                price_max: appliedFilters.value.priceMax || null,
            }
        });

        return res.data.data || [];
    } catch (err) {
        console.error('❌ Error fetching export data:', err);
        toast.error("Failed to load data for export");
        return [];
    } finally {
        loading.value = false;
    }
};

const onDownload = async (type) => {
    if (!addons.value || addons.value.length === 0) {
        toast.error("No Addons data to download");
        return;
    }

    try {
        loading.value = true;
        const allData = await fetchAllAddonsForExport();

        if (!allData.length) {
            toast.error("No addons found to download");
            loading.value = false;
            return;
        }

        if (type === "pdf") {
            downloadPDF(allData);
        } else if (type === "excel") {
            downloadExcel(allData);
        } else if (type === "csv") {
            downloadCSV(allData);
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

const downloadCSV = (data) => {
    console.log("Data to export:", data);
    try {
        const headers = ["Addon Name", "Addon Group", "Price", "Status", "Description"];
        const rows = data.map((addon) => {
            return [
                `"${addon.name || ""}"`,
                `"${addon.addon_group?.name || "N/A"}"`,
                `${addon.price || 0}`,
                `"${addon.status || "active"}"`,
                `"${(addon.description || "").replace(/"/g, '""')}"`,
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
            `Addons_${new Date().toISOString().split("T")[0]}.csv`
        );
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
        const doc = new jsPDF("p", "mm", "a4");

        // Add title
        doc.setFontSize(18);
        doc.setFont("helvetica", "bold");
        doc.text("Addons Report", 14, 20);
        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        const currentDate = new Date().toLocaleString();
        doc.text(`Generated on: ${currentDate}`, 14, 28);
        doc.text(`Total Addons: ${data.length}`, 14, 34);
        const tableColumns = ["Addon Name", "Addon Group", "Price", "Status", "Description"];
        const tableRows = data.map((addon) => {
            return [
                addon.name || "",
                addon.addon_group?.name || "N/A",
                addon.price || 0,
                (addon.status || "active").toUpperCase(),
                addon.description || "",
            ];
        });
        autoTable(doc, {
            head: [tableColumns],
            body: tableRows,
            startY: 40,
            styles: {
                fontSize: 9,
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
        const fileName = `Addons_${new Date().toISOString().split("T")[0]}.pdf`;
        doc.save(fileName);

        toast.success("PDF downloaded successfully");
    } catch (error) {
        console.error("PDF generation error:", error);
        toast.error(`PDF generation failed: ${error.message}`, { autoClose: 5000 });
    }
};

const downloadExcel = (data) => {
    try {
        if (typeof XLSX === "undefined") {
            throw new Error("XLSX library is not loaded");
        }
        const worksheetData = data.map((addon) => {
            return {
                "Addon Name": addon.name || "",
                "Addon Group": addon.addon_group?.name || "N/A",
                "Price": addon.price || 0,
                "Status": (addon.status || "active").toUpperCase(),
                "Description": addon.description || "",
            };
        });
        const workbook = XLSX.utils.book_new();
        const worksheet = XLSX.utils.json_to_sheet(worksheetData);
        worksheet["!cols"] = [
            { wch: 20 },
            { wch: 18 },
            { wch: 12 },
            { wch: 12 },
            { wch: 30 },
        ];
        XLSX.utils.book_append_sheet(workbook, worksheet, "Addons");
        const metaData = [
            { Info: "Report", Value: "Addons Export" },
            { Info: "Generated On", Value: new Date().toLocaleString() },
            { Info: "Total Records", Value: data.length },
            { Info: "Exported By", Value: "Inventory Management System" },
        ];
        const metaSheet = XLSX.utils.json_to_sheet(metaData);
        XLSX.utils.book_append_sheet(workbook, metaSheet, "Report Info");
        const fileName = `Addons_${new Date().toISOString().split("T")[0]}.xlsx`;
        XLSX.writeFile(workbook, fileName);

        toast.success("Excel file downloaded successfully", { autoClose: 2500 });
    } catch (error) {
        console.error("Excel generation error:", error);
        toast.error(`Excel generation failed: ${error.message}`, { autoClose: 5000 });
    }
};


const sampleHeaders = ["Addon Name", "Addon Group Name", "Price", "Status", "Description"];
const sampleData = [
    ["Extra Cheese", "Toppings", "0.50", "active", "Additional cheese"],
    ["Pepperoni", "Toppings", "0.75", "active", "Spicy pepperoni slices"],
    ["BBQ Sauce", "Sauces", "0.25", "active", "Smoky BBQ sauce"],
    ["Ranch Dressing", "Sauces", "0.25", "active", "Classic ranch"],
];

/* ============================================
   IMPORT HANDLER (ADD AFTER downloadExcel FUNCTION)
============================================ */

const handleImport = (data) => {
    if (!data || data.length <= 1) {
        toast.error("The imported file is empty.");
        return;
    }
    const headers = data[0];
    const rows = data.slice(1);
    const addonsToImport = rows.map((row) => {
        return {
            name: (row[0] || "").trim(),
            addon_group_name: (row[1] || "").trim(),
            price: parseFloat(row[2]) || 0,
            status: (row[3] || "active").toLowerCase().trim(),
            description: (row[4] || "").trim(),
        };
    }).filter(addon => addon.name.length > 0);

    if (addonsToImport.length === 0) {
        toast.error("No valid addons found in the file.");
        return;
    }
    const invalidPrices = addonsToImport.filter(a => a.price < 0);
    if (invalidPrices.length > 0) {
        const names = invalidPrices.map(a => a.name).join(", ");
        toast.error(`Invalid price (must be >= 0) for: ${names}`);
        return;
    }
    const groupNames = addonsToImport.map(a => a.addon_group_name);
    const existingGroupNames = addonGroups.value.map(g => g.name.toLowerCase());
    const missingGroups = [...new Set(groupNames.filter(name =>
        !existingGroupNames.includes(name.toLowerCase())
    ))];

    if (missingGroups.length > 0) {
        toast.error(`Addon groups do not exist: ${missingGroups.join(", ")}`);
        return;
    }
    const addonKeys = addonsToImport.map(a => `${a.name.toLowerCase()}-${a.addon_group_name.toLowerCase()}`);
    const duplicatesInCSV = addonKeys.filter((key, index) => addonKeys.indexOf(key) !== index);

    if (duplicatesInCSV.length > 0) {
        toast.error("Duplicate addon names found in the same group within CSV");
        return;
    }
    const existingKeys = addons.value.map(a =>
        `${a.name.toLowerCase()}-${a.addon_group?.name.toLowerCase()}`
    );
    const duplicatesInTable = addonsToImport.filter(importAddon =>
        existingKeys.includes(`${importAddon.name.toLowerCase()}-${importAddon.addon_group_name.toLowerCase()}`)
    );

    if (duplicatesInTable.length > 0) {
        const names = duplicatesInTable.map(a => `${a.name} (${a.addon_group_name})`).join(", ");
        toast.error(`Already exist in table: ${names}`);
        return;
    }
    const validStatuses = ["active", "inactive"];
    const invalidStatuses = addonsToImport.filter(a => !validStatuses.includes(a.status));

    if (invalidStatuses.length > 0) {
        toast.error("Status must be 'active' or 'inactive'.");
        return;
    }

    axios.post("/api/addons/import", { addons: addonsToImport })
        .then((response) => {
            toast.success(response.data.message || "Import successful!");
            const importModal = document.querySelector('.modal.show');
            if (importModal) {
                const bsModal = bootstrap.Modal.getInstance(importModal);
                if (bsModal) {
                    bsModal.hide();
                }
            }

            setTimeout(() => {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => backdrop.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }, 300);

            fetchAddons();
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

        <Head title="Addons" />

        <div class="page-wrapper">
            <!-- Page Header -->
            <h4 class="fw-semibold mb-3">Addons Management</h4>

            <!-- KPI Statistics Cards -->
            <div class="row g-3 mb-4">
                <div v-for="stat in addonStats" :key="stat.label" class="col-md-6 col-xl-3">
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
                    <!-- Toolbar: Filter, Search & Add Button -->
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 fw-semibold">Addons</h5>

                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <FilterModal v-model="filters" title="Addons" modalId="addonsFilterModal"
                                modalSize="modal-lg" :sortOptions="[
                                    { value: 'name_asc', label: 'Name: A to Z' },
                                    { value: 'name_desc', label: 'Name: Z to A' },
                                    { value: 'price_asc', label: 'Price: Low to High' },
                                    { value: 'price_desc', label: 'Price: High to Low' },
                                    { value: 'newest', label: 'Newest First' },
                                    { value: 'oldest', label: 'Oldest First' },
                                ]" :showStockStatus="false" :categories="addonGroupsForFilter"
                                categoryLabel="Addon Group" statusLabel="Addon Status" :showPriceRange="true"
                                :showDateRange="false" @apply="handleFilterApply" @clear="handleFilterClear" />
                            <!-- Search Input -->
                            <div class="search-wrap">
                                <i class="bi bi-search"></i>

                                <!-- Hidden email input to prevent autofill -->
                                <input type="email" name="email" autocomplete="email" style="
                                        position: absolute;
                                        left: -9999px;
                                        width: 1px;
                                        height: 1px;
                                    " tabindex="-1" aria-hidden="true" />

                                <!-- Actual search input -->
                                <input v-if="isReady" :id="inputId" v-model="q" :key="searchKey"
                                    class="form-control search-input  rounded-pill" placeholder="Search addons..."
                                    type="search" autocomplete="new-password" :name="inputId" role="presentation"
                                    @focus="handleFocus" />
                                <input v-else class="form-control search-input  rounded-pill"
                                    placeholder="Search addons..." disabled type="text" />
                            </div>



                            <!-- Add Addon Button -->
                            <button data-bs-toggle="modal" data-bs-target="#addonModal" @click="resetModal"
                                class="d-flex align-items-center gap-1 px-4 py-2 rounded-pill btn btn-primary text-white">
                                <Plus class="w-4 h-4" /> Add Addon
                            </button>

                            <ImportFile label="Import Addons" :sampleHeaders="sampleHeaders" :sampleData="sampleData"
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

                    <!-- Group Filter Buttons -->
                    <div class="mb-3 d-flex flex-wrap gap-2">
                        <button v-for="group in uniqueGroups" :key="group" @click="setGroupFilter(group)"
                            class="btn rounded-pill border-dark" :class="selectedGroupFilter === (group === 'All' ? 'all' : group)
                                ? 'custom-bg-color'
                                : ''">
                            {{ group }}
                        </button>
                    </div>


                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="border-top small text-muted">
                                <tr>
                                    <th>S.#</th>
                                    <th>Name</th>
                                    <th>Addon Group</th>
                                    <th>Price</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Loading State -->
                                <tr v-if="loading">
                                    <td colspan="6" class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Data Rows -->
                                <tr v-else v-for="(row, i) in addons" :key="row.id">
                                    <!-- Serial Number -->
                                    <td>{{ (currentPage - 1) * perPage + i + 1 }}</td>

                                    <!-- Addon Name -->
                                    <td class="fw-semibold">{{ row.name }}</td>

                                    <!-- Addon Group -->
                                    <td>
                                        <span class="badge bg-primary px-3 py-2 rounded-pill">
                                            {{ row.addon_group?.name || "N/A" }}
                                        </span>
                                    </td>

                                    <!-- Price -->
                                    <td class="fw-semibold text-success">
                                        {{ formatCurrencySymbol(row.price) }}
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
                                                            " :title="row.status === 'active'
                                                                ? 'Set Inactive'
                                                                : 'Set Active'
                                                                ">
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
                                <tr v-if="!loading && addons.length === 0">
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No addons found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- ✅ Pagination Section (Safe version) -->
                    <div v-if="paginationLinks && paginationLinks.length > 0 && !loading"
                        class="mt-4 d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Showing {{ (currentPage - 1) * perPage + 1 }} to
                            {{ Math.min(currentPage * perPage, totalItems) }} of
                            {{ totalItems }} entries
                        </div>

                        <Pagination :pagination="paginationLinks" :isApiDriven="true"
                            @page-changed="handlePageChange" />
                    </div>
                </div>
            </div>

            <!-- ================== Add/Edit Modal ================== -->
            <div class="modal fade" id="addonModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content rounded-4">
                        <!-- Modal Header -->
                        <div class="modal-header">
                            <h5 class="modal-title fw-semibold">
                                {{ editingAddon ? "Edit Addon" : "Add New Addon" }}
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
                                <!-- Addon Name -->
                                <div class="col-12">
                                    <label class="form-label">Addon Name *</label>
                                    <input v-model="addonForm.name" type="text" class="form-control"
                                        :class="{ 'is-invalid': formErrors.name }"
                                        placeholder="e.g., Extra Cheese, Pepperoni, BBQ Sauce" />
                                    <small v-if="formErrors.name" class="text-danger">
                                        {{ formErrors.name[0] }}
                                    </small>
                                </div>

                                <!-- Addon Group -->
                                <div class="col-12">
                                    <label class="form-label">Addon Group *</label>
                                    <Select v-model="addonForm.addon_group_id" :options="addonGroupOptions"
                                        optionLabel="label" optionValue="value" placeholder="Select addon group"
                                        class="form-select" appendTo="self" :autoZIndex="true" :baseZIndex="2000"
                                        :class="{ 'is-invalid': formErrors.addon_group_id }" />
                                    <small v-if="formErrors.addon_group_id" class="text-danger">
                                        {{ formErrors.addon_group_id[0] }}
                                    </small>
                                </div>

                                <!-- Price -->
                                <div class="col-md-6">
                                    <label class="form-label">Price *</label>
                                    <input v-model.number="addonForm.price" type="number" step="0.01" min="0"
                                        class="form-control" :class="{ 'is-invalid': formErrors.price }"
                                        placeholder="0.00" />

                                    <small v-if="formErrors.price" class="text-danger d-block">
                                        {{ formErrors.price[0] }}
                                    </small>
                                </div>

                                <!-- Status -->
                                <div class="col-md-6">
                                    <label class="form-label">Status *</label>
                                    <Select v-model="addonForm.status" :options="statusOptions" optionLabel="label"
                                        optionValue="value" class="form-select" appendTo="self" :autoZIndex="true"
                                        :baseZIndex="2000" :class="{ 'is-invalid': formErrors.status }" />
                                    <small v-if="formErrors.status" class="text-danger">
                                        {{ formErrors.status[0] }}
                                    </small>
                                </div>

                                <!-- Sort Order (Hidden in UI, auto-calculated) -->
                                <!-- <div class="col-12">
                                    <label class="form-label">Sort Order</label>
                                    <input
                                        v-model.number="addonForm.sort_order"
                                        type="number"
                                        min="0"
                                        class="form-control"
                                        :class="{ 'is-invalid': formErrors.sort_order }"
                                        placeholder="0"
                                    />
                                    <small class="text-muted">
                                        Lower numbers appear first in the list
                                    </small>
                                    <small v-if="formErrors.sort_order" class="text-danger d-block">
                                        {{ formErrors.sort_order[0] }}
                                    </small>
                                </div> -->

                                <!-- Description -->
                                <div class="col-12">
                                    <label class="form-label">Description (Optional)</label>
                                    <textarea v-model="addonForm.description" class="form-control" rows="3"
                                        :class="{ 'is-invalid': formErrors.description }"
                                        placeholder="Enter addon description..."></textarea>
                                    <small v-if="formErrors.description" class="text-danger">
                                        {{ formErrors.description[0] }}
                                    </small>
                                </div>
                            </div>

                            <hr class="my-4" />

                            <!-- Modal Actions -->
                            <div class="mt-4">
                                <button class="btn btn-primary rounded-pill px-4" :disabled="submitting"
                                    @click="submitAddon">
                                    <template v-if="submitting">
                                        <span class="spinner-border spinner-border-sm me-2"></span>
                                        Saving...
                                    </template>
                                    <template v-else>
                                        {{ editingAddon ? "Save" : "Save" }}
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

.dark .border-dark {
    border: 1px solid #fff !important;
    color: #fff !important;
}

.dark .custom-bg-color {
    background-color: #1C0D82 !important;
    color: #fff !important;

}

.custom-bg-color {
    background-color: #1C0D82 !important;
    color: #fff !important;
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