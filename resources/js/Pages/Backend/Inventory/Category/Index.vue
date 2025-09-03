<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted, onUpdated } from "vue";
import MultiSelect from "primevue/multiselect";
import { toast } from "vue3-toastify";
import { jsPDF } from "jspdf";
import autoTable from "jspdf-autotable";
import * as XLSX from 'xlsx';


/* ---------------- Demo data (swap with API later) ---------------- */
const manualCategories = ref([]);
const categories = ref([]);
const editingCategory = ref(null);
const manualSubcategories = ref([]);

const fetchCategories = async () => {
    try {
        const res = await axios.get("/categories");
        categories.value = res.data.data;
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
        label: "Categories",
        value: parentCategories.value.length, // total categories
    },
    {
        label: "Active Categories",
        value: parentCategories.value.filter((c) => c.active).length, // count of active categories
    },
    {
        label: "Deactive Categories",
        value: parentCategories.value.filter((c) => !c.active).length, // count of inactive categories
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

const selectAll = () => {
    const allOn = commonChips.value.every((c) => c.selected);
    commonChips.value = commonChips.value.map((c) => ({
        ...c,
        selected: !allOn,
    }));
};

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
};

/* ---------------- Submit (console + Promise then/catch) ---------------- */
const submitting = ref(false);
import axios from "axios";

const submitCategory = async () => {
    if (isSub.value && !selectedParentId.value) {
        alert("Please select a parent category for subcategory.");
        return;
    }

    submitting.value = true;

    try {
        if (editingCategory.value) {
            // UPDATE MODE - Handle both main category and subcategories

            const updatePayload = {
                name: manualCategories.value[0]?.label?.trim(),
                icon: manualIcon.value.value,
                active: manualActive.value,
                parent_id: selectedParentId.value || null,
            };

            // Only include subcategories if we're updating a main category (not a subcategory)
            if (
                !updatePayload.parent_id &&
                manualSubcategories.value.length > 0
            ) {
                updatePayload.subcategories = manualSubcategories.value.map(
                    (selectedValue) => {
                        // Find the corresponding option to get the label
                        const matchingOption = options.value.find(
                            (opt) => opt.value === selectedValue
                        );

                        if (matchingOption) {
                            // This is an existing subcategory from options
                            const parsedId = selectedValue.startsWith("temp_")
                                ? null
                                : parseInt(selectedValue);
                            return {
                                id: isNaN(parsedId) ? null : parsedId,
                                name: matchingOption.label,
                                active: true,
                            };
                        } else {
                            // This is a new subcategory added via custom input
                            return {
                                id: null,
                                name: selectedValue,
                                active: true,
                            };
                        }
                    }
                );
            }

            if (!updatePayload.name) {
                alert("Category name cannot be empty.");
                submitting.value = false;
                return;
            }

            await axios.put(
                `/categories/${editingCategory.value.id}`,
                updatePayload
            );
            toast.success("Category updated successfully ✅");
        } else {
            // CREATE MODE - Use the existing structure
            let categoriesPayload = [];

            if (isSub.value) {
                if (manualSubcategories.value.length === 0) {
                    alert("Please add at least one subcategory.");
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
                if (manualCategories.value.length === 0) {
                    toast.warning("Please add at least one category.");
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
            await axios.post("/categories", createPayload);
            toast.success("Category created successfully ✅");
        }

        resetModal();
        editingCategory.value = null;
        await fetchCategories();
    } catch (err) {
        console.error("❌ Error:", err.response?.data || err.message);

        // ✅ ENHANCED ERROR HANDLING FOR DUPLICATES
        if (err.response?.status === 422 && err.response?.data?.errors) {
            const errors = err.response.data.errors;
            let errorMessages = [];

            // Process validation errors
            Object.keys(errors).forEach((key) => {
                if (Array.isArray(errors[key])) {
                    errors[key].forEach((message) => {
                        errorMessages.push(message);
                    });
                }
            });

            if (errorMessages.length > 0) {
                // Show specific validation errors
                const errorText = errorMessages.join("\n");
                toast.error(errorText);
            } else {
                toast.error("Validation failed. Please check your input.");
            }
        } else {
            // Generic error message
            const errorMessage =
                err.response?.data?.message || "Failed to save category";
            toast.error(errorMessage + " ❌");
        }
    } finally {
        submitting.value = false;
        const m = bootstrap.Modal.getInstance(
            document.getElementById("addCatModal")
        );
        m?.hide();
    }
};

// ✅ OPTIONAL: Add client-side duplicate check for better UX
const checkForDuplicates = () => {
    if (!editingCategory.value) {
        // Only for create mode
        const newNames = manualCategories.value.map((cat) =>
            typeof cat === "string"
                ? cat.toLowerCase()
                : cat.label.toLowerCase()
        );

        const duplicates = newNames.filter(
            (name, index) => newNames.indexOf(name) !== index
        );

        if (duplicates.length > 0) {
            toast.warning("Duplicate category names detected in your input.");
            return false;
        }
    }
    return true;
};

// Also update your editRow function to better handle subcategory IDs
const editRow = (row) => {
    console.log("Editing row:", row);
    editingCategory.value = row;

    // Is sub
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

    // Handle subcategories - ensure proper ID mapping
    if (row.subcategories && row.subcategories.length > 0) {
        // Build options with proper IDs
        options.value = row.subcategories.map((sub) => ({
            label: sub.name,
            value: sub.id
                ? sub.id.toString()
                : `temp_${Date.now()}_${Math.random()}`, // fallback for missing IDs
        }));

        // ✅ FIX: Preselect by VALUE only (not objects)
        // Since optionValue="value", manualSubcategories should be an array of strings/values
        manualSubcategories.value = row.subcategories.map((sub) =>
            sub.id ? sub.id.toString() : `temp_${Date.now()}_${Math.random()}`
        );
    } else {
        options.value = [];
        manualSubcategories.value = [];
    }

    console.log("Options:", options.value);
    console.log("Selected values:", manualSubcategories.value);

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
        await axios.delete(`/categories/${row.id}`);
        toast.success("Category deleted successfully ✅");
        await fetchCategories(); // refresh the table
    } catch (err) {
        console.error("❌ Delete error:", err.response?.data || err.message);
        toast.error("Failed to delete category ❌");
    }
};

// =================== View category ====================
const viewingCategory = ref(null);

const viewCategory = async (row) => {
    viewingCategory.value = null;

    try {
        const { data } = await axios.get(`/categories/${row.id}`);
        if (data.success) {
            viewingCategory.value = data.data;

            // Show modal
            const modalEl = document.getElementById("viewCatModal");
            const bsModal = new bootstrap.Modal(modalEl);
            bsModal.show();
        }
    } catch (err) {
        console.error("Failed to fetch category:", err);
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

const onDownload = (type) => {
    if (!parentCategories.value || parentCategories.value.length === 0) {
        toast.error("No Units data to download", { autoClose: 3000 });
        return;
    }

    // Use filtered data if there's a search query, otherwise use all suppliers
    const dataToExport = q.value.trim() ? filtered.value : parentCategories.value;

    if (dataToExport.length === 0) {
        toast.error("No Units found to download", { autoClose: 3000 });
        return;
    }

    try {
        if (type === 'pdf') {
            downloadPDF(dataToExport);
        } else if (type === 'excel') {
            downloadExcel(dataToExport);
        }
        else if (type === 'csv') {
            downloadCSV(dataToExport);
        }
        else {
            toast.error("Invalid download type", { autoClose: 3000 });
        }
    } catch (error) {
        console.error('Download failed:', error);
        toast.error(`Download failed: ${error.message}`, { autoClose: 3000 });
    }
};

const downloadCSV = (data) => {
    try {
        // Define headers
        const headers = ["Category", "SubCategory", "Total Value", "Total Items", "Out of Stock", "Low Stock", "In Stock"];

        // Build CSV rows
        const rows = data.map(s => [
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
            ...rows.map(r => r.join(",")) // data rows
        ].join("\n");

        // Create blob
        const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
        const url = URL.createObjectURL(blob);

        // Create download link
        const link = document.createElement("a");
        link.setAttribute("href", url);
        link.setAttribute("download", `Categories_${new Date().toISOString().split("T")[0]}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        toast.success("CSV downloaded successfully ✅", { autoClose: 2500 });
    } catch (error) {
        console.error("CSV generation error:", error);
        toast.error(`CSV generation failed: ${error.message}`, { autoClose: 5000 });
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
        const tableColumns = ["Category", "SubCategory", "Total Value", "Total Items", "Out of Stock", "Low Stock", "In Stock"];
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
                lineWidth: 0.1
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
        const fileName = `Categories_${new Date().toISOString().split("T")[0]}.pdf`;
        doc.save(fileName);

        toast.success("PDF downloaded successfully ✅", { autoClose: 2500 });
    } catch (error) {
        console.error("PDF generation error:", error);
        toast.error(`PDF generation failed: ${error.message}`, { autoClose: 5000 });
    }
};


const downloadExcel = (data) => {
    try {
        // Check if XLSX is available
        if (typeof XLSX === 'undefined') {
            throw new Error('XLSX library is not loaded');
        }

        // Prepare worksheet data
        const worksheetData = data.map(category => ({
            'Category': category.name || '',
            'SubCategory': category.parent_id || '',
            'Total Value': category.total_value || '',
            'Total Item': category.total_items || '',
            'Out of Stock': category.out_of_stock || '',
            'Low Stock': category.low_stock || '',
            'In Stock': category.in_stock || '',

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
            { wch: 10 }  // ID
        ];
        worksheet['!cols'] = colWidths;

        // Add worksheet to workbook
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Units');

        // Add metadata sheet
        const metaData = [
            { Info: 'Generated On', Value: new Date().toLocaleString() },
            { Info: 'Total Records', Value: data.length },
            { Info: 'Exported By', Value: 'Units Management System' }
        ];
        const metaSheet = XLSX.utils.json_to_sheet(metaData);
        XLSX.utils.book_append_sheet(workbook, metaSheet, 'Report Info');

        // Generate file name
        const fileName = `Units_${new Date().toISOString().split('T')[0]}.xlsx`;

        // Save the file
        XLSX.writeFile(workbook, fileName);

        toast.success("Excel file downloaded successfully ✅", { autoClose: 2500 });

    } catch (error) {
        console.error('Excel generation error:', error);
        toast.error(`Excel generation failed: ${error.message}`, { autoClose: 5000 });
    }
};

</script>

<template>
    <Master>
        <div class="page-wrapper">
            <div class="container-fluid py-3">
                <h4 class="fw-semibold mb-3">Manu Categories</h4>

                <!-- KPI -->
                <div class="row g-3">
                    <div v-for="c in CategoriesDetails" :key="c.label" class="col-6 col-md-4">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="icon-wrap mb-2">
                                    <i :class="c.icon"></i>
                                </div>
                                <div class="text-muted">{{ c.label }}</div>
                                <div class="kpi-value">{{ c.value }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table card -->
                <div class="card border-0 shadow-lg rounded-4 mt-0">
                    <div class="card-body">
                        <!-- Toolbar -->
                        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                            <h5 class="mb-0 fw-semibold">Categories</h5>

                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <div class="search-wrap">
                                    <i class="bi bi-search"></i>
                                    <input v-model="q" type="text" class="form-control search-input"
                                        placeholder="Search" />
                                </div>

                                <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal"
                                    data-bs-target="#addCatModal">
                                    Add Category
                                </button>

                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary rounded-pill px-4 dropdown-toggle"
                                        data-bs-toggle="dropdown">
                                        Download all
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow rounded-4 py-2">
                                        <li>
                                            <a class="dropdown-item py-2" href="javascript:;"
                                                @click="onDownload('pdf')">Download as
                                                PDF</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item py-2" href="javascript:;"
                                                @click="onDownload('excel')">Download
                                                as Excel</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item py-2" href="javascript:;"
                                                @click="onDownload('csv')">
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
                                        <th>Image</th>
                                        <th>Total value</th>
                                        <th>Total Item</th>
                                        <th>Out of Stock</th>
                                        <th>Low Stock</th>
                                        <th>In Stock</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, i) in filtered" :key="row.id">
                                        <td>{{ i + 1 }}</td>
                                        <td class="fw-semibold">
                                            {{ row.name }}
                                        </td>
                                        <td class="text-truncate" style="max-width: 260px">
                                            {{
                                                row.subcategories
                                                    ?.map((sub) => sub.name)
                                                    .join(", ") || "–"
                                            }}
                                        </td>
                                        <td>
                                            <div
                                                class="rounded d-inline-flex align-items-center justify-content-center img-chip">
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
                                        <td class="text-end">
                                            <div class="dropdown">
                                                <button class="btn btn-link text-secondary p-0 fs-5"
                                                    data-bs-toggle="dropdown" title="Actions">
                                                    ⋮
                                                </button>
                                                <ul
                                                    class="dropdown-menu dropdown-menu-end shadow rounded-4 overflow-hidden">
                                                    <li>
                                                        <a class="dropdown-item py-2" href="javascript:void(0)" @click="
                                                            viewCategory(
                                                                row
                                                            )
                                                            ">
                                                            <i data-feather="eye" class="me-2"></i>
                                                            View
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item py-2" href="javascript:void(0)" @click="
                                                            editRow(row)
                                                            ">
                                                            <i data-feather="edit-2" class="me-2"></i>
                                                            Edit
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item py-2 text-danger"
                                                            href="javascript:void(0)" @click="
                                                                deleteCategory(
                                                                    row
                                                                )
                                                                ">
                                                            <i data-feather="trash-2" class="me-2"></i>
                                                            Delete
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr v-if="filtered.length === 0">
                                        <td colspan="10" class="text-center text-muted py-4">
                                            No categories found.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- =================== View Modal of Category =================== -->
                <div class="modal fade" id="viewCatModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content rounded-4">
                            <div class="modal-header">
                                <h5 class="modal-title">Category Details</h5>
                                <button type="button" class="btn btn-close" data-bs-dismiss="modal" aria-label="Close">
                                    ×
                                </button>
                            </div>
                            <div class="modal-body" v-if="viewingCategory">
                                <p>
                                    <strong>Name:</strong>
                                    {{ viewingCategory.name }}
                                </p>
                                <p>
                                    <strong>Icon:</strong>
                                    <span class="fs-4">{{
                                        viewingCategory.icon
                                        }}</span>
                                </p>
                                <p>
                                    <strong>Status:</strong>
                                    {{
                                        viewingCategory.active
                                            ? "Active"
                                            : "Inactive"
                                    }}
                                </p>
                                <p v-if="viewingCategory.parent">
                                    <strong>Parent Category:</strong>
                                    {{ viewingCategory.parent.name }}
                                </p>
                                <p>
                                    <strong>Sub-Categories: </strong>
                                    <span v-if="
                                        viewingCategory.subcategories.length
                                    ">
                                        {{
                                            viewingCategory.subcategories
                                                .map((sub) => sub.name)
                                                .join(", ")
                                        }}
                                    </span>
                                    <span v-else>None</span>
                                </p>
                            </div>
                            <div class="modal-body text-center" v-else>
                                Loading category details...
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================== Add Category Modal ================== -->
                <div class="modal fade" id="addCatModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content rounded-4">
                            <div class="modal-header">
                                <h5 class="modal-title fw-semibold">
                                    Add Raw Material Categories
                                </h5>
                                <button type="button" class="btn btn-close" data-bs-dismiss="modal" aria-label="Close">
                                    ×
                                </button>
                            </div>

                            <div class="modal-body">
                                <!-- Row 1 -->
                                <div class="row g-3">
                                    <!-- Show "Is this a subcategory?" only when creating -->
                                    <template v-if="!editingCategory">
                                        <div class="col-lg-6">
                                            <label class="form-label d-block mb-2">Is this a subcategory?</label>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" :checked="isSub"
                                                        @change="
                                                            isSub = true;
                                                        selectedParentId =
                                                            null;
                                                        " name="isSub" />
                                                    <label class="form-check-label">Yes</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" :checked="!isSub"
                                                        @change="
                                                            isSub = false;
                                                        selectedParentId =
                                                            null;
                                                        " name="isSub" />
                                                    <label class="form-check-label">No</label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Parent Category Dropdown - Only show when subcategory is Yes -->
                                        <div class="col-lg-6" v-if="isSub">
                                            <label class="form-label d-block mb-2">Select Parent Category</label>
                                            <select v-model="selectedParentId" class="form-select" required>
                                                <option disabled :value="null">
                                                    -- Choose Parent Category --
                                                </option>
                                                <option v-for="cat in parentCategories" :key="cat.id" :value="cat.id">
                                                    {{ cat.name }}
                                                </option>
                                            </select>
                                        </div>
                                    </template>

                                    <!-- Manual Icon (always show) -->
                                    <div class="col-lg-6">
                                        <label class="form-label d-block mb-2">Manual Icon</label>
                                        <div class="dropdown w-100">
                                            <button
                                                class="btn btn-outline-secondary w-100 d-flex justify-content-between align-items-center rounded-3"
                                                data-bs-toggle="dropdown">
                                                <span>
                                                    <span class="me-2">{{
                                                        manualIcon.value
                                                        }}</span>
                                                    {{ manualIcon.label }}
                                                </span>
                                                <i class="bi bi-caret-down-fill"></i>
                                            </button>
                                            <ul class="dropdown-menu w-100 shadow rounded-3">
                                                <li v-for="opt in iconOptions" :key="opt.label">
                                                    <a class="dropdown-item" href="javascript:void(0)" @click="
                                                        manualIcon = opt
                                                        ">
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
                                    <div class="col-12" v-if="!isSub || editingCategory">
                                        <label class="form-label">Category Name</label>

                                        <!-- Single input when editing -->
                                        <input v-if="editingCategory" type="text" v-model="manualCategories[0].label"
                                            class="form-control" placeholder="Enter category name" />

                                        <!-- MultiSelect when creating -->
                                        <MultiSelect v-else v-model="manualCategories" :options="commonChips"
                                            optionLabel="label" optionValue="value" :filter="true" display="chip"
                                            placeholder="Select or add categories..." class="w-100" appendTo="self"
                                            @keydown.enter.prevent="
                                                addCustomCategory
                                            " @blur="addCustomCategory" @filter="
                                                (e) => (filterText = e.value)
                                            ">
                                            <template #option="{ option }">
                                                {{ option.label }}
                                            </template>
                                        </MultiSelect>
                                    </div>

                                    <!-- Subcategory MultiSelect (always in edit, only when isSub=false in create) -->
                                    <div class="col-12" v-if="editingCategory || isSub">
                                        <label class="form-label">Subcategory Name(s)</label>

                                        <MultiSelect v-model="manualSubcategories" :options="options"
                                            optionLabel="label" optionValue="value" :filter="true" display="chip"
                                            placeholder="Select or add subcategories..." class="w-100" appendTo="self"
                                            @filter="
                                                (e) =>
                                                (currentFilterValue =
                                                    e.value || '')
                                            " @keydown.enter.prevent="
                                                addCustomSubcategory
                                            " @blur="addCustomSubcategory">
                                            <template #option="{ option }">
                                                <div>{{ option.label }}</div>
                                            </template>

                                            <template #footer>
                                                <div class="p-3 d-flex justify-content-between">
                                                    <Button label="Add Custom" severity="secondary" variant="text"
                                                        size="small" icon="pi pi-plus" @click="
                                                            addCustomSubcategory
                                                        " :disabled="!currentFilterValue.trim()
                                                            " />
                                                    <div class="d-flex gap-2">
                                                        <Button label="Select All" severity="secondary" variant="text"
                                                            size="small" icon="pi pi-check" @click="
                                                                selectAllSubcategories
                                                            " />
                                                        <Button label="Clear All" severity="danger" variant="text"
                                                            size="small" icon="pi pi-times" @click="
                                                                removeAllSubcategories
                                                            " />
                                                    </div>
                                                </div>
                                            </template>
                                        </MultiSelect>
                                    </div>

                                    <!-- Active toggle -->
                                    <div class="col-12">
                                        <label class="form-label d-block mb-2">Active</label>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" :checked="manualActive"
                                                    @change="
                                                        manualActive = true
                                                        " name="active" />
                                                <label class="form-check-label">Yes</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" :checked="!manualActive"
                                                    @change="
                                                        manualActive = false
                                                        " name="active" />
                                                <label class="form-check-label">No</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4" />

                                <div class="mt-4">
                                    <button class="btn btn-primary rounded-pill px-4" :disabled="submitting"
                                        @click="submitCategory()">
                                        <span v-if="!submitting">
                                            {{
                                                editingCategory
                                                    ? "Update Category"
                                                    : "Add Category(ies)"
                                            }}
                                        </span>
                                        <span v-else>Saving...</span>
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


.table-responsive {
    overflow: visible !important;
}

.dropdown-menu {
    position: absolute !important;
    z-index: 1050 !important;
}

/* Ensure the table container doesn't clip the dropdown */
.table-container {
    overflow: visible !important;
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
