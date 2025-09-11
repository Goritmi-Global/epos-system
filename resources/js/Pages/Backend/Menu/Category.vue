<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted, onUpdated } from "vue";
import MultiSelect from "primevue/multiselect";
import Select from "primevue/select";

import {
    Shapes,
    Package,
    AlertTriangle,
    XCircle,
    Pencil,
    Plus,
} from "lucide-vue-next";

import { toast } from "vue3-toastify";
import { jsPDF } from "jspdf";
import autoTable from "jspdf-autotable";
import * as XLSX from "xlsx";

/* ---------------- Demo data (swap with API later) ---------------- */
const manualCategories = ref([]);
const categories = ref([]);
const editingCategory = ref(null);
const manualSubcategories = ref([]);

const fetchCategories = async () => {
    try {
        const res = await axios.get("/menu-categories/parents/list");
        categories.value = res.data.data;
        console.log("Fetched categories:", categories.value);
    } catch (err) {
        console.error("Failed to fetch categories:", err);
    }
};

onMounted(() => {
    fetchCategories();
});

// Get only parent categories (main categories) for dropdown
const parentCategories = computed(() => {
    return categories.value.filter((cat) => cat.parent_id === null);
});

/* ---------------- KPI (fake) ---------------- */
const CategoriesDetails = computed(() => [
    {
        label: "Total Categories",
        value: parentCategories.value.length,
        icon: Shapes,
        iconBg: "bg-light-primary",
        iconColor: "text-primary",
    },
    {
        label: "Active Categories",
        value: parentCategories.value.filter((c) => c.active).length,
        icon: Package,
        iconBg: "bg-light-success",
        iconColor: "text-success",
    },
    {
        label: "Inactive Categories",
        value: parentCategories.value.filter((c) => !c.active).length,
        icon: XCircle,
        iconBg: "bg-light-danger",
        iconColor: "text-danger",
    },
]);

/* ---------------- Search ---------------- */
const q = ref("");
const filtered = computed(() => {
    const t = q.value.trim().toLowerCase();
    // First, get only parent categories
    const parents = categories.value.filter((c) => c.parent_id === null);

    // If no search term, return all parent categories
    if (!t) return parents;

    // Otherwise, filter parents by search term
    return parents.filter((c) => c.name.toLowerCase().includes(t));
});

/* ---------------- Helpers ---------------- */
const money = (n, currency = "GBP") =>
    new Intl.NumberFormat("en-GB", { style: "currency", currency }).format(n);

onMounted(() => window.feather?.replace());
onUpdated(() => window.feather?.replace());

/* ---------------- Add Category Modal state ---------------- */
const isSub = ref(false);
const manualName = ref("");
const manualActive = ref(true);
const selectedParentId = ref(null); // Changed to null instead of empty string
const manualIcon = ref({ label: "Produce (Veg/Fruit)", value: "🥬" });

const iconOptions = [
    { label: "Produce (Veg/Fruit)", value: "🥬" },
    { label: "Dairy", value: "🧀" },
    { label: "Grains & Rice", value: "🌾" },
    { label: "Spices & Herbs", value: "🧂" },
    { label: "Oils & Fats", value: "🫒" },
    { label: "Sauces & Condiments", value: "🍶" },
    { label: "Nuts & Seeds", value: "🥜" },
    { label: "Other", value: "🧰" },
];

const commonChips = ref([
    {
        label: "Produce (Veg/Fruit)",
        value: "Produce (Veg/Fruit)",
        icon: "🥬",
        selected: false,
    },
    { label: "Dairy", value: "Dairy", icon: "🧀", selected: false },
    {
        label: "Grains & Rice",
        value: "Grains & Rice",
        icon: "🌾",
        selected: false,
    },
    {
        label: "Spices & Herbs",
        value: "Spices & Herbs",
        icon: "🧂",
        selected: false,
    },
    { label: "Oils & Fats", value: "Oils & Fats", icon: "🫒", selected: false },
    {
        label: "Sauces & Condiments",
        value: "Sauces & Condiments",
        icon: "🍶",
        selected: false,
    },
    {
        label: "Nuts & Seeds",
        value: "Nuts & Seeds",
        icon: "🥜",
        selected: false,
    },
    { label: "Other", value: "Other", icon: "🧰", selected: false },
]);

const resetModal = () => {
    isSub.value = false;
    manualCategories.value = [];
    manualActive.value = true;
    selectedParentId.value = null;
    manualName.value = "";
    manualIcon.value = iconOptions[0];
    commonChips.value = commonChips.value.map((c) => ({
        ...c,
        selected: false,
    }));
    editingCategory.value = null;
};

/* ---------------- Submit (console + Promise then/catch) ---------------- */
const submitting = ref(false);
const catFormErrors = ref({});
import axios from "axios";

const resetFields = () => {
    isSub.value = false;
    manualCategories.value = [];
    manualSubcategories.value = [];
    manualName.value = "";
    manualActive.value = true;
    selectedParentId.value = null;
    manualIcon.value = iconOptions[0];
    catFormErrors.value = {};
    filterText.value = "";
    currentFilterValue.value = "";
    options.value = [];
    manualSubcategoriesInput.value = "";
    editingCategory.value = null;
};
const submitCategory = async () => {
    if (isSub.value && !selectedParentId.value) {
        catFormErrors.value.parent_id = ["Please select a parent category"];
        submitting.value = false;
        return;
    }

    resetErrors();
    submitting.value = true;

    try {
        if (editingCategory.value) {
            // UPDATE MODE
            const updatePayload = {
                name: isSub.value
                    ? manualName.value?.trim()
                    : manualCategories.value[0]?.label?.trim(),
                icon: manualIcon.value.value,
                active: manualActive.value,
                parent_id: selectedParentId.value || null,
            };

            // ✅ Handle subcategories only if it's a MAIN CATEGORY
            if (!updatePayload.parent_id) {
                if (manualSubcategoriesInput.value) {
                    updatePayload.subcategories = manualSubcategoriesInput.value
                        .split(",")
                        .map((s) => s.trim())
                        .filter((s) => s.length > 0)
                        .map((name) => {
                            // try to match existing subcategories by name
                            const existingSub =
                                editingCategory.value.subcategories?.find(
                                    (sub) =>
                                        sub.name.toLowerCase() ===
                                        name.toLowerCase()
                                );
                            return {
                                id: existingSub ? existingSub.id : null,
                                name,
                                active: existingSub ? existingSub.active : true,
                            };
                        });
                } else {
                    updatePayload.subcategories = [];
                }
            }

            if (!updatePayload.name) {
                catFormErrors.value.name = ["Category name cannot be empty"];
                submitting.value = false;
                return;
            }

            console.log("Update payload:", updatePayload);

            await axios.put(
                `/menu-categories/${editingCategory.value.id}`,
                updatePayload
            );
            toast.success("Category updated successfully");

            // 👇 Close modal after successful update
            const m = bootstrap.Modal.getInstance(
                document.getElementById("addCatModal")
            );
            m?.hide();

            // 👇 Now reset state AFTER closing
            resetModal();
            editingCategory.value = null;
            await fetchCategories();
        } else {
            // CREATE MODE - Use the existing structure
            let categoriesPayload = [];

            if (isSub.value) {
                if (isSub.value && manualSubcategories.value.length === 0) {
                    catFormErrors.value.subcategories = [
                        "Please add at least one subcategory",
                    ];
                    submitting.value = false;
                    return;
                }

                categoriesPayload = manualSubcategories.value.map((cat) => ({
                    id: undefined,
                    name: typeof cat === "string" ? cat : cat.label,
                    icon: manualIcon.value.value,
                    active: manualActive.value,
                    parent_id: selectedParentId.value,
                }));
            } else {
                if (!isSub.value && manualCategories.value.length === 0) {
                    catFormErrors.value.name = [
                        "Please add at least one category",
                    ];
                    submitting.value = false;
                    return;
                }

                categoriesPayload = manualCategories.value.map((cat) => ({
                    id: undefined,
                    name: typeof cat === "string" ? cat : cat.label,
                    icon: manualIcon.value.value,
                    active: manualActive.value,
                    parent_id: null,
                }));
            }

            const createPayload = {
                isSubCategory: isSub.value,
                categories: categoriesPayload,
            };

            console.log("Creating categories with payload:", createPayload);
            await axios.post("/menu-categories", createPayload);
            submitting.value = false;

            const m = bootstrap.Modal.getInstance(
                document.getElementById("addCatModal")
            );
            m?.hide();
            toast.success("Category created successfully");
        }

        resetModal();
        editingCategory.value = null;
        await fetchCategories();
    } catch (err) {
        console.error("❌ Error:", err.response?.data || err.message);

        if (err.response?.status === 422 && err.response?.data?.errors) {
            const errors = err.response.data.errors;
            let errorMessages = [];
            catFormErrors.value = {};

            Object.keys(errors).forEach((key) => {
                let normalizedKey = key.replace(/^categories\.\d+\./, "");
                catFormErrors.value[normalizedKey] = errors[key];

                if (Array.isArray(errors[key])) {
                    errors[key].forEach((message) => {
                        errorMessages.push(message);
                    });
                }
            });

            if (errorMessages.length > 0) {
                toast.error(errorMessages.join("\n"));
            } else {
                toast.error("Validation failed. Please check your input.");
            }
        } else {
            const errorMessage =
                err.response?.data?.message || "Failed to save category";
            toast.error(errorMessage + " ❌");
        }
    } finally {
        submitting.value = false;
    }
};

const resetErrors = () => {
    catFormErrors.value = {};
};

const manualSubcategoriesInput = ref("");

// Also update your editRow function to better handle subcategory IDs
const editRow = (row) => {
    resetErrors();
    editingCategory.value = row;
    isSub.value = !!row.parent_id;
    selectedParentId.value = row.parent_id || null;

    // Icon
    const iconOption = iconOptions.find((i) => i.value === row.icon);
    manualIcon.value = iconOption || iconOptions[0];

    // Name
    if (isSub.value) {
        manualName.value = row.name;
    } else {
        manualCategories.value = [
            { label: row.name, value: row.name, id: row.id },
        ];
    }

    manualActive.value = row.active;

    // Handle subcategories
    if (row.subcategories && row.subcategories.length > 0) {
        options.value = row.subcategories.map((sub) => ({
            label: sub.name,
            value: sub.id
                ? sub.id.toString()
                : `temp_${Date.now()}_${Math.random()}`,
        }));

        manualSubcategories.value = row.subcategories.map((sub) =>
            sub.id ? sub.id.toString() : `temp_${Date.now()}_${Math.random()}`
        );

        // 👇 For edit: build a comma-separated string of names
        manualSubcategoriesInput.value = row.subcategories
            .map((s) => s.name)
            .join(", ");
    } else {
        options.value = [];
        manualSubcategories.value = [];
        manualSubcategoriesInput.value = "";
    }

    // Show modal
    const modalEl = document.getElementById("addCatModal");
    const bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();
};

// ==================== custom category ====================
const filterText = ref("");

const addCustomCategory = () => {
    const name = (filterText.value || "").trim();
    if (!name) return;

    // Check if category already exists
    if (
        !commonChips.value.some(
            (o) => o.label.toLowerCase() === name.toLowerCase()
        )
    ) {
        commonChips.value.push({ label: name, value: name });
    }

    // Add it to selected categories if not already selected
    if (!manualCategories.value.includes(name)) {
        manualCategories.value = [...manualCategories.value, name];
    }

    filterText.value = "";
};
// ================= Delete Category ======================

const deleteCategory = async (row) => {
    if (!row?.id) return;

    try {
        await axios.delete(`/menu-categories/${row.id}`);
        toast.success("Category deleted successfully");
        await fetchCategories(); // refresh the table
    } catch (err) {
        console.error("❌ Delete error:", err.response?.data || err.message);
        toast.error("Failed to delete category ❌");
    }
};

const options = ref([]); // all subcategory options
const currentFilterValue = ref("");

// Add custom subcategory
const addCustomSubcategory = () => {
    const name = currentFilterValue.value?.trim();
    if (!name) return;

    // Add to options if it doesn't exist
    if (
        !options.value.some((o) => o.value.toLowerCase() === name.toLowerCase())
    ) {
        options.value.push({ label: name, value: name });
    }

    // Add to selected if not already
    if (!manualSubcategories.value.includes(name)) {
        manualSubcategories.value.push(name);
    }

    currentFilterValue.value = "";
};

// Select all
const selectAllSubcategories = () => {
    manualSubcategories.value = options.value.map((o) => o.value);
};

// Clear all
const removeAllSubcategories = () => {
    manualSubcategories.value = [];
};

const editingSubCategory = ref({
    id: null,
    name: "",
    parent_id: null,
});

const editSubCategory = (category, sub) => {
    editingSubCategory.value = {
        id: sub.id,
        name: sub.name,
        parent_id: category.id,
    };

    const modalEl = document.getElementById("editSubCatModal");
    const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
    bsModal.show();
};

const submittingSub = ref(false);
const subCatErrors = ref("");

// generate a function where all the fields to be reset into empty
const resetSubCategoryFields = () => {
    editingSubCategory.value = {
        id: null,
        name: "",
        parent_id: null,
    };
    subCatErrors.value = "";
    submittingSub.value = false;
};

const submitSubCategory = async () => {
    if (!editingSubCategory.value.name.trim()) {
        subCatErrors.value = "Subcategory name cannot be empty";
        return;
    }

    submittingSub.value = true;
    subCatErrors.value = "";

    try {
        const { data } = await axios.put(
            `/menu-categories/subcategories/${editingSubCategory.value.id}`,
            {
                name: editingSubCategory.value.name.trim(),
            }
        );

        if (data.success) {
            toast.success(data.message);

            // Update local data in table (optional)
            const category = categories.value.find(
                (c) => c.id === editingSubCategory.value.parent_id
            );
            if (category) {
                const sub = category.subcategories.find(
                    (s) => s.id === editingSubCategory.value.id
                );
                if (sub) sub.name = editingSubCategory.value.name;
            }

            // Close modal
            const modalEl = document.getElementById("editSubCatModal");
            const bsModal = bootstrap.Modal.getInstance(modalEl);
            bsModal.hide();

            // Reset editing state
            editingSubCategory.value = { id: null, name: "", parent_id: null };
        } else {
            subCatErrors.value = data.message || "Failed to update subcategory";
        }
    } catch (err) {
        console.error(err);
        subCatErrors.value =
            err.response?.data?.message || "An error occurred while updating";
    } finally {
        submittingSub.value = false;
    }
};

const onDownload = (type) => {
    if (!parentCategories.value || parentCategories.value.length === 0) {
        toast.error("No Units data to download");
        return;
    }

    // Use filtered data if there's a search query, otherwise use all suppliers
    const dataToExport = q.value.trim()
        ? filtered.value
        : parentCategories.value;

    if (dataToExport.length === 0) {
        toast.error("No Units found to download");
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
        // Define headers
        const headers = [
            "Category",
            "SubCategory",
            "Total Value",
            "Total Items",
            "Out of Stock",
            "Low Stock",
            "In Stock",
        ];

        // Build CSV rows
        const rows = data.map((s) => [
            `"${s.name || ""}"`,
            `"${s.parent_id || ""}"`,
            `"${s.total_value || ""}"`,
            `"${s.total_items || ""}"`,
            `"${s.out_of_stock || ""}"`,
            `"${s.low_stock || ""}"`,
            `"${s.in_stock || ""}"`,
        ]);

        // Combine into CSV string
        const csvContent = [
            headers.join(","), // header row
            ...rows.map((r) => r.join(",")), // data rows
        ].join("\n");

        // Create blob
        const blob = new Blob([csvContent], {
            type: "text/csv;charset=utf-8;",
        });
        const url = URL.createObjectURL(blob);

        // Create download link
        const link = document.createElement("a");
        link.setAttribute("href", url);
        link.setAttribute(
            "download",
            `Categories_${new Date().toISOString().split("T")[0]}.csv`
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
        const doc = new jsPDF("p", "mm", "a4"); // portrait, millimeters, A4

        // 🌟 Title
        doc.setFontSize(20);
        doc.setFont("helvetica", "bold");
        doc.text("Category Report", 70, 20);

        // 🗓️ Metadata
        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        const currentDate = new Date().toLocaleString();
        doc.text(`Generated on: ${currentDate}`, 70, 28);
        doc.text(`Total Units: ${data.length}`, 70, 34);

        // 📋 Table Data
        const tableColumns = [
            "Category",
            "SubCategory",
            "Total Value",
            "Total Items",
            "Out of Stock",
            "Low Stock",
            "In Stock",
        ];
        const tableRows = data.map((s) => [
            s.name || "",
            s.parent_id || "",
            s.total_value || "",
            s.total_items || "",
            s.out_of_stock || "",
            s.low_stock || "",
            s.in_stock || "",
        ]);

        // 📑 Styled table
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
            alternateRowStyles: { fillColor: [240, 240, 240] },
            margin: { left: 14, right: 14 },
            didDrawPage: (tableData) => {
                // Footer with page numbers
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

        // 💾 Save file
        const fileName = `Categories_${
            new Date().toISOString().split("T")[0]
        }.pdf`;
        doc.save(fileName);

        toast.success("PDF downloaded successfully");
    } catch (error) {
        console.error("PDF generation error:", error);
        toast.error(`PDF generation failed: ${error.message}`, {
            autoClose: 5000,
        });
    }
};

const downloadExcel = (data) => {
    try {
        // Check if XLSX is available
        if (typeof XLSX === "undefined") {
            throw new Error("XLSX library is not loaded");
        }

        // Prepare worksheet data
        const worksheetData = data.map((category) => ({
            Category: category.name || "",
            SubCategory: category.parent_id || "",
            "Total Value": category.total_value || "",
            "Total Item": category.total_items || "",
            "Out of Stock": category.out_of_stock || "",
            "Low Stock": category.low_stock || "",
            "In Stock": category.in_stock || "",
        }));

        // Create workbook and worksheet
        const workbook = XLSX.utils.book_new();
        const worksheet = XLSX.utils.json_to_sheet(worksheetData);

        // Set column widths
        const colWidths = [
            { wch: 20 }, // Name
            { wch: 25 }, // Email
            { wch: 15 }, // Phone
            { wch: 30 }, // Address
            { wch: 25 }, // Preferred Items
            { wch: 10 }, // ID
        ];
        worksheet["!cols"] = colWidths;

        // Add worksheet to workbook
        XLSX.utils.book_append_sheet(workbook, worksheet, "Units");

        // Add metadata sheet
        const metaData = [
            { Info: "Generated On", Value: new Date().toLocaleString() },
            { Info: "Total Records", Value: data.length },
            { Info: "Exported By", Value: "Units Management System" },
        ];
        const metaSheet = XLSX.utils.json_to_sheet(metaData);
        XLSX.utils.book_append_sheet(workbook, metaSheet, "Report Info");

        // Generate file name
        const fileName = `Units_${new Date().toISOString().split("T")[0]}.xlsx`;

        // Save the file
        XLSX.writeFile(workbook, fileName);

        toast.success("Excel file downloaded successfully", {
            autoClose: 2500,
        });
    } catch (error) {
        console.error("Excel generation error:", error);
        toast.error(`Excel generation failed: ${error.message}`, {
            autoClose: 5000,
        });
    }
};
</script>

<template>
    <Master>
        <div class="page-wrapper">
            <div class="container-fluid py-3">
                <h4 class="fw-semibold mb-3">Categories</h4>

                <!-- KPI -->
                <div class="row g-3">
                    <div
                        v-for="c in CategoriesDetails"
                        :key="c.label"
                        class="col"
                    >
                        <div class="card border-0 shadow-sm rounded-4">
                            <div
                                class="card-body d-flex align-items-center justify-content-between"
                            >
                                <!-- Text -->
                                <div>
                                    <div class="fw-bold fs-4">
                                        {{ c.value }}
                                    </div>
                                    <div class="text-muted fs-6">
                                        {{ c.label }}
                                    </div>
                                </div>
                                <!-- Icon -->
                                <div
                                    :class="[
                                        'p-3 rounded-3 d-flex align-items-center justify-content-center',
                                        c.iconBg,
                                    ]"
                                >
                                    <component
                                        :is="c.icon"
                                        :class="c.iconColor"
                                        size="28"
                                    />
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
                            class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3"
                        >
                            <h5 class="mb-0 fw-semibold">Categories</h5>

                            <div
                                class="d-flex flex-wrap gap-2 align-items-center"
                            >
                                <div class="search-wrap">
                                    <i class="bi bi-search"></i>
                                    <input
                                        v-model="q"
                                        type="text"
                                        class="form-control search-input"
                                        placeholder="Search"
                                    />
                                </div>

                                <button
                                    data-bs-toggle="modal"
                                    data-bs-target="#addCatModal"
                                    @click="
                                        resetErrors;
                                        editingCategory = null;
                                        resetSubCategoryFields();
                                        resetFields();
                                    "
                                    class="d-flex align-items-center gap-1 px-4 py-2 rounded-pill btn btn-primary text-white"
                                >
                                    <Plus class="w-4 h-4" /> Add Category
                                </button>

                                <div class="dropdown">
                                    <button
                                        class="btn btn-outline-secondary rounded-pill px-4 dropdown-toggle"
                                        data-bs-toggle="dropdown"
                                    >
                                        Download all
                                    </button>
                                    <ul
                                        class="dropdown-menu dropdown-menu-end shadow rounded-4 py-2"
                                    >
                                        <li>
                                            <a
                                                class="dropdown-item py-2"
                                                href="javascript:;"
                                                @click="onDownload('pdf')"
                                                >Download as PDF</a
                                            >
                                        </li>
                                        <li>
                                            <a
                                                class="dropdown-item py-2"
                                                href="javascript:;"
                                                @click="onDownload('excel')"
                                                >Download as Excel</a
                                            >
                                        </li>
                                        <li>
                                            <a
                                                class="dropdown-item py-2"
                                                href="javascript:;"
                                                @click="onDownload('csv')"
                                            >
                                                Download as CSV
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="border-top small text-muted">
                                    <tr>
                                        <th>S.#</th>
                                        <th>Category</th>
                                        <th>Sub Category</th>
                                        <th>Icon</th>
                                        <th>Total value</th>
                                        <th>Total Item</th>
                                        <th>Out of Stock</th>
                                        <th>Low Stock</th>
                                        <th>In Stock</th>
                                        <th>Status</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(row, i) in filtered"
                                        :key="row.id"
                                    >
                                        <td>{{ i + 1 }}</td>
                                        <td class="fw-semibold">
                                            {{ row.name }}
                                        </td>
                                        <td
                                            class="text-truncate"
                                            style="max-width: 260px"
                                        >
                                            <div
                                                v-if="
                                                    row.subcategories &&
                                                    row.subcategories.length
                                                "
                                            >
                                                <div
                                                    v-for="sub in row.subcategories"
                                                    :key="sub.id"
                                                    class="d-flex justify-content-between align-items-center"
                                                    style="gap: 5px"
                                                >
                                                    <span>{{ sub.name }}</span>

                                                    <button
                                                        data-bs-toggle="modal"
                                                        @click="
                                                            () => {
                                                                editSubCategory(
                                                                    row,
                                                                    sub
                                                                );
                                                            }
                                                        "
                                                        title="Edit"
                                                        class="p-2 rounded-full text-blue-600 hover:bg-blue-100"
                                                    >
                                                        <Pencil
                                                            class="w-4 h-4"
                                                        />
                                                    </button>
                                                </div>
                                            </div>
                                            <span v-else>–</span>
                                        </td>

                                        <td>
                                            <div
                                                class="rounded d-inline-flex align-items-center justify-content-center img-chip"
                                            >
                                                <span class="fs-5">{{
                                                    row.icon || "📦"
                                                }}</span>
                                            </div>
                                        </td>
                                        <td>{{ money(row.total_value) }}</td>
                                        <td>{{ row.total_items }}</td>
                                        <td>{{ row.out_of_stock }}</td>
                                        <td>{{ row.low_stock }}</td>
                                        <td>{{ row.in_stock }}</td>
                                        <td>
                                            {{
                                                row.active
                                                    ? "Active"
                                                    : "Inactive"
                                            }}
                                        </td>
                                        <td class="text-center">
                                            <div
                                                class="d-inline-flex align-items-center gap-3"
                                            >
                                                <button
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalUnitForm"
                                                    @click="
                                                        () => {
                                                            editRow(row);
                                                        }
                                                    "
                                                    title="Edit"
                                                    class="p-2 rounded-full text-blue-600 hover:bg-blue-100"
                                                >
                                                    <Pencil class="w-4 h-4" />
                                                </button>

                                                <ConfirmModal
                                                    :title="'Confirm Delete'"
                                                    :message="`Are you sure you want to delete ${row.name}?`"
                                                    :showDeleteButton="true"
                                                    @confirm="
                                                        () => {
                                                            deleteCategory(row);
                                                        }
                                                    "
                                                    @cancel="() => {}"
                                                />
                                            </div>
                                        </td>
                                    </tr>

                                    <tr v-if="filtered.length === 0">
                                        <td
                                            colspan="10"
                                            class="text-center text-muted py-4"
                                        >
                                            No categories found.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ================== Add Category Modal ================== -->
                <div
                    class="modal fade"
                    id="addCatModal"
                    tabindex="-1"
                    aria-hidden="true"
                >
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content rounded-4">
                            <div class="modal-header">
                                <h5 class="modal-title fw-semibold">
                                    Category
                                    {{ editingCategory ? "Edit" : "Add" }}
                                </h5>
                                <button
                                    class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                                    data-bs-dismiss="modal"
                                    aria-label="Close"
                                    title="Close"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-6 w-6 text-red-500"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M6 18L18 6M6 6l12 12"
                                        />
                                    </svg>
                                </button>
                            </div>

                            <div class="modal-body">
                                <!-- Row 1 -->
                                <div class="row g-3">
                                    <!-- Show "Is this a subcategory?" only when creating -->
                                    <template v-if="!editingCategory">
                                        <div class="col-lg-12">
                                            <label
                                                class="form-label d-block mb-2"
                                                >Is this a subcategory?</label
                                            >
                                            <div
                                                class="d-flex align-items-center gap-3"
                                            >
                                                <div class="form-check">
                                                    <input
                                                        class="form-check-input"
                                                        type="radio"
                                                        :checked="isSub"
                                                        @click="resetErrors()"
                                                        @change="
                                                            isSub = true;
                                                            selectedParentId =
                                                                null;
                                                        "
                                                        name="isSub"
                                                    />
                                                    <label
                                                        class="form-check-label"
                                                        >Yes</label
                                                    >
                                                </div>
                                                <div class="form-check">
                                                    <input
                                                        class="form-check-input"
                                                        type="radio"
                                                        :checked="!isSub"
                                                        @click="resetErrors()"
                                                        @change="
                                                            isSub = false;
                                                            selectedParentId =
                                                                null;
                                                        "
                                                        name="isSub"
                                                    />
                                                    <label
                                                        class="form-check-label"
                                                        >No</label
                                                    >
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Parent Category Dropdown - Only show when subcategory is Yes -->

                                        <div class="col-lg-12" v-if="isSub">
                                            <label class="form-label"
                                                >Category</label
                                            >
                                            <Select
                                                v-model="selectedParentId"
                                                :options="parentCategories"
                                                optionLabel="name"
                                                optionValue="id"
                                                placeholder="Select Category"
                                                class="w-100"
                                                appendTo="self"
                                                :autoZIndex="true"
                                                :baseZIndex="2000"
                                                :class="{
                                                    'is-invalid':
                                                        catFormErrors?.parent_id,
                                                }"
                                            />
                                            <small
                                                v-if="catFormErrors?.parent_id"
                                                class="text-danger"
                                            >
                                                {{ catFormErrors.parent_id[0] }}
                                            </small>
                                        </div>
                                        <!-- <div class="col-lg-6" v-if="isSub">
                                            <label class="form-label d-block mb-2">Select Parent Category</label>
                                            <select v-model="selectedParentId" class="form-select"
                                                :class="{ 'is-invalid': catFormErrors?.parent_id }" required>
                                                <option disabled :value="null">
                                                    -- Choose Parent Category --
                                                </option>
                                                <option v-for="cat in parentCategories" :key="cat.id" :value="cat.id">
                                                    {{ cat.name }}
                                                </option>
                                            </select>
                                        </div> -->
                                        <!-- <small v-if="catFormErrors?.parent_id" class="text-danger text-right">
                                            {{ catFormErrors.parent_id[0] }}
                                        </small> -->
                                    </template>

                                    <!-- Manual Icon (always show) -->
                                    <div class="col-lg-12">
                                        <label class="form-label d-block mb-2"
                                            >Manual Icon</label
                                        >
                                        <div class="dropdown w-100">
                                            <button
                                                class="btn btn-outline-secondary w-100 d-flex justify-content-between align-items-center rounded-3"
                                                data-bs-toggle="dropdown"
                                            >
                                                <span>
                                                    <span class="me-2">{{
                                                        manualIcon.value
                                                    }}</span>
                                                    {{ manualIcon.label }}
                                                </span>
                                                <i
                                                    class="bi bi-caret-down-fill"
                                                ></i>
                                            </button>
                                            <ul
                                                class="dropdown-menu w-100 shadow rounded-3"
                                            >
                                                <li
                                                    v-for="opt in iconOptions"
                                                    :key="opt.label"
                                                >
                                                    <a
                                                        class="dropdown-item"
                                                        href="javascript:void(0)"
                                                        @click="
                                                            manualIcon = opt
                                                        "
                                                    >
                                                        <span class="me-2">{{
                                                            opt.value
                                                        }}</span>
                                                        {{ opt.label }}
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>

                                    <!-- Category Name -->
                                    <div
                                        class="col-12"
                                        v-if="!isSub || editingCategory"
                                    >
                                        <label class="form-label"
                                            >Category Name</label
                                        >

                                        <!-- Single input when editing -->
                                        <input
                                            v-if="editingCategory"
                                            type="text"
                                            v-model="manualCategories[0].label"
                                            class="form-control"
                                            :class="{
                                                'is-invalid':
                                                    catFormErrors.name,
                                            }"
                                            placeholder="Enter category name"
                                        />

                                        <!-- MultiSelect when creating -->
                                        <MultiSelect
                                            v-else
                                            v-model="manualCategories"
                                            :options="commonChips"
                                            optionLabel="label"
                                            optionValue="value"
                                            :filter="true"
                                            display="chip"
                                            :class="{
                                                'is-invalid':
                                                    catFormErrors.name,
                                            }"
                                            placeholder="Select or add categories..."
                                            class="w-100"
                                            appendTo="self"
                                            @keydown.enter.prevent="
                                                addCustomCategory
                                            "
                                            @blur="addCustomCategory"
                                            @filter="
                                                (e) => (filterText = e.value)
                                            "
                                        >
                                            <template #option="{ option }">
                                                {{ option.label }}
                                            </template>
                                        </MultiSelect>
                                        <small
                                            v-if="catFormErrors.name"
                                            class="text-danger"
                                        >
                                            {{ catFormErrors.name[0] }}
                                        </small>
                                    </div>

                                    <!-- Subcategory Section -->
                                    <div
                                        class="col-12"
                                        v-if="isSub && !editingCategory"
                                    >
                                        <label class="form-label"
                                            >Subcategory Name(s)</label
                                        >

                                        <!-- CREATE: MultiSelect -->
                                        <MultiSelect
                                            v-model="manualSubcategories"
                                            :options="options"
                                            optionLabel="label"
                                            optionValue="value"
                                            :filter="true"
                                            display="chip"
                                            :class="{
                                                'is-invalid':
                                                    catFormErrors?.subcategories ||
                                                    catFormErrors?.name,
                                            }"
                                            placeholder="Select or add subcategories..."
                                            class="w-100"
                                            appendTo="self"
                                            @filter="
                                                (e) =>
                                                    (currentFilterValue =
                                                        e.value || '')
                                            "
                                            @keydown.enter.prevent="
                                                addCustomSubcategory
                                            "
                                            @blur="addCustomSubcategory"
                                        >
                                            <template #option="{ option }">
                                                <div>{{ option.label }}</div>
                                            </template>
                                            <template #footer>
                                                <div
                                                    class="p-3 d-flex justify-content-between"
                                                >
                                                    <Button
                                                        label="Add Custom"
                                                        severity="secondary"
                                                        variant="text"
                                                        size="small"
                                                        icon="pi pi-plus"
                                                        @click="
                                                            addCustomSubcategory
                                                        "
                                                        :disabled="
                                                            !currentFilterValue.trim()
                                                        "
                                                    />
                                                    <div class="d-flex gap-2">
                                                        <Button
                                                            label="Select All"
                                                            severity="secondary"
                                                            variant="text"
                                                            size="small"
                                                            icon="pi pi-check"
                                                            @click="
                                                                selectAllSubcategories
                                                            "
                                                        />
                                                        <Button
                                                            label="Clear All"
                                                            severity="danger"
                                                            variant="text"
                                                            size="small"
                                                            icon="pi pi-times"
                                                            @click="
                                                                removeAllSubcategories
                                                            "
                                                        />
                                                    </div>
                                                </div>
                                            </template>
                                        </MultiSelect>
                                        <!-- Errors -->
                                        <div
                                            v-if="catFormErrors?.subcategories"
                                            class="text-danger"
                                        >
                                            {{ catFormErrors.subcategories[0] }}
                                        </div>
                                        <div
                                            v-if="catFormErrors?.name"
                                            class="text-danger"
                                        >
                                            {{ catFormErrors.name[0] }}
                                        </div>
                                    </div>

                                    <!-- EDIT MODE: Show parent category only -->
                                    <div
                                        class="col-12"
                                        v-if="editingCategory && parentCategory"
                                    >
                                        <label class="form-label"
                                            >Parent Category</label
                                        >
                                        <input
                                            type="text"
                                            class="form-control"
                                            :value="parentCategory.label"
                                            disabled
                                        />
                                    </div>

                                    <!-- Active toggle -->
                                    <div class="col-12">
                                        <label class="form-label d-block mb-2"
                                            >Active</label
                                        >
                                        <div
                                            class="d-flex align-items-center gap-3"
                                        >
                                            <div class="form-check">
                                                <input
                                                    class="form-check-input"
                                                    type="radio"
                                                    :checked="manualActive"
                                                    @change="
                                                        manualActive = true
                                                    "
                                                    name="active"
                                                />
                                                <label class="form-check-label"
                                                    >Yes</label
                                                >
                                            </div>
                                            <div class="form-check">
                                                <input
                                                    class="form-check-input"
                                                    type="radio"
                                                    :checked="!manualActive"
                                                    @change="
                                                        manualActive = false
                                                    "
                                                    name="active"
                                                />
                                                <label class="form-check-label"
                                                    >No</label
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4" />

                                <div class="mt-4">
                                    <button
                                        class="btn btn-primary rounded-pill px-4"
                                        :disabled="submitting"
                                        @click="submitCategory()"
                                    >
                                        <span v-if="!submitting">
                                            {{
                                                editingCategory
                                                    ? "Update Category"
                                                    : "Add Category(ies)"
                                            }}
                                        </span>
                                        <span v-else>Saving...</span>
                                    </button>
                                    <button
                                        class="btn btn-secondary rounded-pill px-4 ms-2"
                                        data-bs-dismiss="modal"
                                        @click="resetModal"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /modal -->

                <!-- Edit Subcategory Modal -->
                <div
                    class="modal fade"
                    id="editSubCatModal"
                    tabindex="-1"
                    aria-labelledby="editSubCatModalLabel"
                    aria-hidden="true"
                >
                    <div class="modal-dialog modal-md modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5
                                    class="modal-title"
                                    id="editSubCatModalLabel"
                                >
                                    Edit Subcategory
                                </h5>
                                <button
                                    class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                                    data-bs-dismiss="modal"
                                    aria-label="Close"
                                    title="Close"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-6 w-6 text-red-500"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M6 18L18 6M6 6l12 12"
                                        />
                                    </svg>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label
                                        for="subCategoryName"
                                        class="form-label"
                                        >Subcategory Name</label
                                    >
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="subCategoryName"
                                        v-model="editingSubCategory.name"
                                        :disabled="submittingSub"
                                        :class="{
                                            'is-invalid': subCatErrors,
                                        }"
                                    />
                                    <small class="text-danger">{{
                                        subCatErrors
                                    }}</small>
                                </div>
                                <button
                                    type="button"
                                    class="btn btn-primary rounded-pill px-4"
                                    @click="submitSubCategory"
                                    :disabled="submittingSub"
                                >
                                    {{
                                        submittingSub
                                            ? "Saving..."
                                            : "Save"
                                    }}
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
:root {
    --brand: #1c0d82;
}

.icon-wrap {
    font-size: 2rem;
    color: var(--brand);
}

.kpi-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: #111827;
}

/* Search pill */
.search-wrap {
    position: relative;
    width: clamp(220px, 28vw, 360px);
}

.search-wrap .bi-search {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
}

.search-input {
    padding-left: 38px;
    border-radius: 9999px;
    background: #fff;
}

/* Buttons */
.btn-primary {
    background-color: var(--brand);
    border-color: var(--brand);
}

.btn-primary:hover {
    filter: brightness(1.05);
}

/* Table */
.table thead th {
    font-weight: 600;
}

.img-chip {
    width: 40px;
    height: 40px;
    background: #f1f5f9;
}

/* Chips */
.chip {
    padding: 8px 14px;
    font-weight: 600;
}

 
.dropdown-menu {
    position: absolute !important;
    z-index: 1050 !important;
}

/* Ensure the table container doesn't clip the dropdown */
.table-container {
    overflow: visible !important;
}

/* Whole dropdown overlay */
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

/* Mobile */
@media (max-width: 575.98px) {
    .kpi-value {
        font-size: 1.45rem;
    }

    .search-wrap {
        width: 100%;
    }
}
</style>
