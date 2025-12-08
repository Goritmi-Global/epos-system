<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted, onUpdated, onBeforeUnmount, watch } from "vue";
import MultiSelect from "primevue/multiselect";
import {
    Shapes,
    Package,
    AlertTriangle,
    XCircle,
    Pencil,
    Plus,
} from "lucide-vue-next";
import * as lucideIcons from "lucide-vue-next";

import { toast } from "vue3-toastify";
import { jsPDF } from "jspdf";
import autoTable from "jspdf-autotable";
import * as XLSX from "xlsx";
import { useFormatters } from '@/composables/useFormatters'
import { nextTick } from "vue";
import axios from "axios";
import ImportFile from "@/Components/importFile.vue";
import { Head } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";

const { formatMoney, formatCurrencySymbol, formatNumber, dateFmt } = useFormatters()

/* ---------------- Demo data (swap with API later) ---------------- */
const manualCategories = ref([]);
const categories = ref([]);
const allCategories = ref([]);
const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
    from: 0,
    to: 0,
    links: []
});
const editingCategory = ref(null);
const manualSubcategories = ref([]);

const fetchAllCategories = async () => {
    try {
        const { data } = await axios.get("/categories", {
            params: {
                per_page: 10000 // Get all at once
            },
        });
        allCategories.value = data.data || [];
    } catch (err) {
        console.error("Failed to fetch all categories:", err);
    }
};

const fetchCategories = async (page = null) => {
    try {
        const { data } = await axios.get("/categories", {
            params: {
                q: q.value,
                page: page || pagination.value.current_page,
                per_page: pagination.value.per_page
            },
        });
        categories.value = data.data || [];
        pagination.value = {
            current_page: data.current_page,
            last_page: data.last_page,
            per_page: data.per_page,
            total: data.total,
            from: data.from,
            to: data.to,
            links: data.links
        };
    } catch (err) {
        console.error("Failed to fetch categories:", err);
        toast.error("Failed to load categories");
    }
};

const handlePageChange = (url) => {
    if (!url) return;
    const urlParams = new URLSearchParams(url.split('?')[1]);
    const page = urlParams.get('page');

    if (page) {
        fetchCategories(parseInt(page));
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
    fetchAllCategories();
    fetchCategories();
});

// Get only parent categories (main categories) for dropdown
const parentCategories = computed(() => {
    return categories.value.filter((cat) => cat.parent_id === null);
});

const CategoriesDetails = computed(() => {
    const parentCategoriesTotal = allCategories.value.filter((cat) => cat.parent_id === null);

    return [
        {
            label: "Categories",
            value: parentCategoriesTotal.length,
            icon: Shapes,
            iconBg: "bg-light-primary",
            iconColor: "text-primary",
        },
        {
            label: "Total Active",
            value: parentCategoriesTotal.filter((c) => c.active).length,
            icon: Package,
            iconBg: "bg-light-success",
            iconColor: "text-success",
        },
        {
            label: "Low Stock",
            value: parentCategoriesTotal.filter((c) => !c.active).length,
            icon: AlertTriangle,
            iconBg: "bg-light-warning",
            iconColor: "text-warning",
        },
        {
            label: "Out of Stock",
            value: parentCategoriesTotal.filter((c) => !c.parent_id).length,
            icon: XCircle,
            iconBg: "bg-light-danger",
            iconColor: "text-danger",
        },
    ];
});

/* ---------------- Search ---------------- */
const q = ref("");
const searchKey = ref(Date.now());
const inputId = `search-${Math.random().toString(36).substr(2, 9)}`;
const isReady = ref(false);

const filtered = computed(() => {
    const t = q.value.trim().toLowerCase();
    if (!t) return categories.value;

    return categories.value.filter((c) =>
        c.name.toLowerCase().includes(t)
    );
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
const selectedParentId = ref(null);

const manualIcon = ref({
    label: "Produce (Veg/Fruit)",
    value: "/assets/img/vegetable.png",
    file: null
});

const iconOptions = [
    {
        label: "Produce (Veg/Fruit)",
        value: "/assets/img/vegetable.png",
    },
    {
        label: "Meat",
        value: "/assets/img/meat.png",
    },
    {
        label: "Poultry",
        value: "/assets/img/poultry.png",
    },
    {
        label: "Dairy",
        value: "/assets/img/dairy.png",
    },
    {
        label: "Grains & Rice",
        value: "/assets/img/rice.png",
    },
    {
        label: "Spices & Herbs",
        value: "/assets/img/spice.png",
    },
    {
        label: "Oils & Fats",
        value: "/assets/img/olive-oil.png",
    },
    {
        label: "Sauces & Condiments",
        value: "/assets/img/sauces.png",
    },
    {
        label: "Nuts & Seeds",
        value: "/assets/img/nuts.png",
    },
    {
        label: "Other",
        value: "/assets/img/other.png",
    },
];

const commonChips = ref([
    {
        label: "Produce (Veg/Fruit)",
        value: "Produce (Veg/Fruit)",
        icon: "/assets/img/vegetable.png",
        selected: false,
    },
    {
        label: "Meat",
        value: "Meat",
        icon: "/assets/img/meat.png",
        selected: false,
    },
    {
        label: "Poultry",
        value: "Poultry",
        icon: "/assets/img/poultry.png",
        selected: false,
    },
    {
        label: "Dairy",
        value: "Dairy",
        icon: "/assets/img/dairy.png",
        selected: false,
    },
    {
        label: "Grains & Rice",
        value: "Grains & Rice",
        icon: "/assets/img/rice.png",
        selected: false,
    },
    {
        label: "Flour & Baking",
        value: "Flour & Baking",
        icon: "/assets/img/flour.png",
        selected: false,
    },
    {
        label: "Spices & Herbs",
        value: "Spices & Herbs",
        icon: "/assets/img/spice.png",
        selected: false,
    },
    {
        label: "Oils & Fats",
        value: "Oils & Fats",
        icon: "/assets/img/olive-oil.png",
        selected: false,
    },
    {
        label: "Sauces & Condiments",
        value: "Sauces & Condiments",
        icon: "/assets/img/sauces.png",
        selected: false,
    },
    {
        label: "Nuts & Seeds",
        value: "Nuts & Seeds",
        icon: "/assets/img/nuts.png",
        selected: false,
    },
    {
        label: "Other",
        value: "Other",
        icon: "/assets/img/other.png",
        selected: false,
    },
]);

// =================================================
// Images for Categories
// =================================================

const urlToFile = async (url, filename) => {
    try {
        const response = await fetch(url);

        if (!response.ok) {
            console.error('❌ Fetch failed:', response.status, response.statusText);
            return null;
        }

        const blob = await response.blob();
        const file = new File([blob], filename, { type: blob.type });
        return file;
    } catch (error) {
        console.error('❌ Error converting URL to File:', error);
        return null;
    }
};


const resetModal = () => {
    isSub.value = false;
    manualCategories.value = [];
    manualActive.value = true;
    selectedParentId.value = null;
    manualName.value = "";
    manualIcon.value = {
        label: iconOptions[0].label,
        value: iconOptions[0].value,
        file: null
    };
    commonChips.value = commonChips.value.map((c) => ({
        ...c,
        selected: false,
    }));
    editingCategory.value = null;
};

/* ---------------- Submit (console + Promise then/catch) ---------------- */
const submitting = ref(false);
const catFormErrors = ref({});


const submitCategory = async () => {
    // Validation for subcategory mode
    if (isSub.value && !selectedParentId.value) {
        catFormErrors.value.parent_id = ["Please select a parent category"];
        toast.error(catFormErrors.value.parent_id[0]);
        submitting.value = false;
        return;
    }

    resetErrors();
    submitting.value = true;

    try {
        // Wait for icon to be ready if still loading
        if (manualIcon.value.value && !manualIcon.value.file) {
            const filename = manualIcon.value.label.toLowerCase().replace(/[^a-z0-9]/g, '-') + '.jpg';
            const file = await urlToFile(manualIcon.value.value, filename);
            if (file) {
                manualIcon.value.file = file;
            }
        }

        if (editingCategory.value) {
            // ==================== UPDATE MODE ====================
            const formData = new FormData();

            let categoryName = '';
            if (isSub.value) {
                categoryName = manualName.value?.trim();
            } else {
                categoryName =
                    manualCategories.value[0]?.label?.trim() ||
                    manualCategories.value[0]?.name?.trim() ||
                    (typeof manualCategories.value[0] === 'string' ? manualCategories.value[0].trim() : '');
            }

            if (!categoryName) {
                catFormErrors.value.name = ["Category name cannot be empty"];
                toast.error("Category name cannot be empty");
                submitting.value = false;
                return;
            }

            formData.append('name', categoryName);
            formData.append('active', manualActive.value ? '1' : '0');
            formData.append('parent_id', selectedParentId.value || '');
            formData.append('_method', 'PUT');

            if (manualIcon.value.file && manualIcon.value.file instanceof File) {
                formData.append('icon', manualIcon.value.file);
            }

            if (!selectedParentId.value && manualSubcategoriesInput.value) {
                const subcategoryNames = manualSubcategoriesInput.value
                    .split(',')
                    .map(s => s.trim())
                    .filter(s => s.length > 0);

                subcategoryNames.forEach((name, index) => {
                    const existingSub = editingCategory.value.subcategories?.find(
                        sub => sub.name.toLowerCase() === name.toLowerCase()
                    );

                    formData.append(`subcategories[${index}][name]`, name);
                    if (existingSub) {
                        formData.append(`subcategories[${index}][id]`, existingSub.id);
                        formData.append(`subcategories[${index}][active]`, existingSub.active ? '1' : '0');
                    } else {
                        formData.append(`subcategories[${index}][active]`, '1');
                    }
                });
            }

            await axios.post(`/categories/${editingCategory.value.id}`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            toast.success("Category updated successfully");
            await fetchCategories();
            await fetchAllCategories();
        } else {
            // ==================== CREATE MODE ====================
            const formData = new FormData();
            let categoriesPayload = [];

            if (isSub.value) {
                if (manualSubcategories.value.length === 0) {
                    catFormErrors.value.subcategories = ["Please add at least one subcategory"];
                    toast.error("Please add at least one subcategory");
                    submitting.value = false;
                    return;
                }

                categoriesPayload = manualSubcategories.value.map(cat => ({
                    name: typeof cat === "string" ? cat : cat.label,
                    active: manualActive.value,
                    parent_id: selectedParentId.value,
                }));
            } else {
                if (manualCategories.value.length === 0) {
                    catFormErrors.value.name = ["Please add at least one category"];
                    toast.error("Please add at least one category");
                    submitting.value = false;
                    return;
                }

                categoriesPayload = manualCategories.value.map(cat => ({
                    name: typeof cat === "string" ? cat : cat.label,
                    active: manualActive.value,
                    parent_id: null,
                }));
            }

            formData.append('isSubCategory', isSub.value ? '1' : '0');

            categoriesPayload.forEach((cat, index) => {
                formData.append(`categories[${index}][name]`, cat.name);
                formData.append(`categories[${index}][active]`, cat.active ? '1' : '0');
                if (cat.parent_id) {
                    formData.append(`categories[${index}][parent_id]`, cat.parent_id);
                }
            });

            if (manualIcon.value.file && manualIcon.value.file instanceof File) {
                formData.append('icon', manualIcon.value.file);
            }

            await axios.post('/categories', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            toast.success("Category created successfully");
            await fetchCategories();
            await fetchAllCategories();
        }

        const m = bootstrap.Modal.getInstance(document.getElementById("addCatModal"));
        m?.hide();
        resetModal();
        editingCategory.value = null;
        await fetchCategories();
    } catch (err) {
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
            const errorMessage = err.response?.data?.message || "Failed to save category";
            toast.error(errorMessage + " ❌");
        }
    } finally {
        submitting.value = false;
    }
};


const resetErrors = () => {
    // resetModal();
    catFormErrors.value = {};
};

const manualSubcategoriesInput = ref("");
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
        await axios.delete(`/categories/${row.id}`);
        toast.success("Category deleted successfully");
        await fetchCategories();
        await fetchAllCategories();
    } catch (err) {
        console.error("❌ Delete error:", err.response?.data || err.message);
        toast.error("Failed to delete category ❌");
    }
};

// =================== View category ====================
const viewingCategory = ref(null);
const options = ref([]);
const currentFilterValue = ref("");
const addCustomSubcategory = () => {
    const name = currentFilterValue.value?.trim();
    if (!name) return;
    if (
        !options.value.some((o) => o.value.toLowerCase() === name.toLowerCase())
    ) {
        options.value.push({ label: name, value: name });
    }
    if (!manualSubcategories.value.includes(name)) {
        manualSubcategories.value.push(name);
    }

    currentFilterValue.value = "";
};
const selectAllSubcategories = () => {
    manualSubcategories.value = options.value.map((o) => o.value);
};
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

const submitSubCategory = async () => {
    submittingSub.value = true;
    if (!editingSubCategory.value.name.trim()) {
        subCatErrors.value = "Subcategory name cannot be empty";
        return;
    }

    submittingSub.value = true;
    subCatErrors.value = "";

    try {
        const { data } = await axios.put(
            `/api/categories/subcategories/${editingSubCategory.value.id}`,
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

onMounted(() => {
    const modalEl = document.getElementById("editSubCatModal");
    if (modalEl) {
        modalEl.addEventListener("hidden.bs.modal", () => {
            subCatErrors.value = "";
            editingSubCategory.value = { id: null, name: "", parent_id: null };
        });
    }
});

onBeforeUnmount(() => {
    const modalEl = document.getElementById("editSubCatModal");
    if (modalEl) {
        modalEl.removeEventListener("hidden.bs.modal", () => { });
    }
});

let searchTimeout = null;
watch(q, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        pagination.value.current_page = 1;
        fetchCategories(1);
    }, 500);
});

const onDownload = (type) => {
    if (!parentCategories.value || parentCategories.value.length === 0) {
        toast.error("No Units data to download");
        return;
    }
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
        const headers = ["category", "subcategory", "active"];

        const rows = data.map((category) => {

            const subcategoryNames = category.subcategories && category.subcategories.length > 0
                ? category.subcategories.map(sub => sub.name).join(", ")
                : "";

            return [
                `"${category.name || ""}"`,
                `"${subcategoryNames}"`,
                `${category.active ? 'Yes' : 'No'}`,
            ];
        });


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
        const doc = new jsPDF("p", "mm", "a4"); // Portrait, millimeters, A4 size

        // 🏷️ Title
        doc.setFontSize(18);
        doc.setFont("helvetica", "bold");
        doc.text("Inventory Categories Report", 14, 20);

        // 🕒 Metadata
        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        const currentDate = new Date().toLocaleString();
        doc.text(`Generated on: ${currentDate}`, 14, 28);
        doc.text(`Total Categories: ${data.length}`, 14, 34);

        // 🧾 Table Columns (Match CSV)
        const tableColumns = ["Category", "Subcategory", "Active"];

        // 🧮 Table Rows
        const tableRows = data.map((category) => {
            const subcategoryNames =
                category.subcategories && category.subcategories.length > 0
                    ? category.subcategories.map((sub) => sub.name).join(", ")
                    : "";
            return [
                category.name || "",
                subcategoryNames,
                category.active ? "Yes" : "No",
            ];
        });

        // 🪶 Create Styled Table
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

        // 💾 Save File
        const fileName = `Categories_${new Date().toISOString().split("T")[0]}.pdf`;
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

        // 🧾 Prepare data matching CSV fields
        const worksheetData = data.map((category) => {
            const subcategoryNames =
                category.subcategories && category.subcategories.length > 0
                    ? category.subcategories.map((sub) => sub.name).join(", ")
                    : "";

            return {
                Category: category.name || "",
                Subcategory: subcategoryNames,
                Active: category.active ? 'Yes' : 'No',
            };
        });


        const workbook = XLSX.utils.book_new();
        const worksheet = XLSX.utils.json_to_sheet(worksheetData);

        worksheet["!cols"] = [
            { wch: 25 },
            { wch: 40 },
            { wch: 10 },
        ];

        XLSX.utils.book_append_sheet(workbook, worksheet, "Categories");

        const metaData = [
            { Info: "Generated On", Value: new Date().toLocaleString() },
            { Info: "Total Records", Value: data.length },
            { Info: "Exported By", Value: "Inventory Management System" },
        ];
        const metaSheet = XLSX.utils.json_to_sheet(metaData);
        XLSX.utils.book_append_sheet(workbook, metaSheet, "Report Info");
        const fileName = `Categories_${new Date().toISOString().split("T")[0]}.xlsx`;
        XLSX.writeFile(workbook, fileName);

        toast.success("Excel file downloaded successfully", { autoClose: 2500 });
    } catch (error) {
        console.error("Excel generation error:", error);
        toast.error(`Excel generation failed: ${error.message}`, { autoClose: 5000 });
    }
};


// handle import function for categories
const handleImport = (data) => {
    if (!data || data.length <= 1) {
        toast.error("The imported file is empty.");
        return;
    }

    const headers = data[0];
    const rows = data.slice(1);

    const categoriesToImport = rows.map((row) => {
        return {
            category: row[0] || "",
            subcategory: row[1] || null,
            active: row[2] == "no" ? 0 : 1,
        };
    });
    const categoryNames = categoriesToImport
        .filter(cat => cat.category)
        .map(cat => cat.category.trim().toLowerCase());
    const duplicatesInCSV = categoryNames.filter((name, index) => categoryNames.indexOf(name) !== index);

    if (duplicatesInCSV.length > 0) {
        toast.error(`Duplicate category names found in CSV: ${[...new Set(duplicatesInCSV)].join(", ")}`);
        return;
    }

    // Check for duplicate category names in the existing table
    const existingCategoryNames = parentCategories.value.map(cat => cat.name.trim().toLowerCase());
    const duplicatesInTable = categoriesToImport
        .filter(cat => cat.category) // Only check parent categories
        .filter(importCat => existingCategoryNames.includes(importCat.category.trim().toLowerCase()));

    if (duplicatesInTable.length > 0) {
        const duplicateNamesList = duplicatesInTable.map(cat => cat.category).join(", ");
        toast.error(`Categories already exist in the table: ${duplicateNamesList}`);
        return;
    }

    axios
        .post("/api/categories/import", { categories: categoriesToImport })
        .then(() => {
            toast.success("Categories imported successfully");
            const importModal = document.querySelector('.modal.show');
            if (importModal) {
                const bsModal = bootstrap.Modal.getInstance(importModal);
                if (bsModal) {
                    bsModal.hide();
                }
            }
            
            // ✅ Force remove any lingering backdrops
            setTimeout(() => {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => backdrop.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }, 100);
            fetchCategories();
        })
        .catch((err) => {
            console.error(err);
            toast.error("Import failed");
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

        <Head title="Inventory Category" />
        <div class="page-wrapper">
            <h4 class="mb-3">Inventory Categories</h4>

            <!-- KPI -->
            <div class="row g-3">
                <div v-for="c in CategoriesDetails" :key="c.label" class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body d-flex align-items-center justify-content-between">
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
                            <div :class="[
                                'p-3 rounded-3 d-flex align-items-center justify-content-center',
                                c.iconBg,
                            ]">
                                <component :is="c.icon" :class="c.iconColor" size="28" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table card -->
            <div class="card border-0 shadow-lg rounded-4 mt-0">
                <div class="card-body">
                    <!-- Toolbar -->
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">Categories</h4>

                        <div class="d-flex flex-wrap gap-2 align-items-center">
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

                            <button data-bs-toggle="modal" data-bs-target="#addCatModal" @click="
                                () => {
                                    resetErrors?.();
                                    if (editingCategory) editingCategory.value = null;
                                    resetModal?.();
                                }
                            "
                                class="d-flex align-items-center gap-1 px-4 py-2 btn-sm rounded-pill btn btn-primary text-white">
                                <Plus class="w-4 h-4" /> Add Category
                            </button>


                            <ImportFile label="Import" :sampleHeaders="['category', 'subcategory', 'active']"
                                :sampleData="[
                                    ['Dairy', 'TestSubCat', 'yes'],
                                    ['Bakery', 'Bread', 'yes'],
                                    ['Beverages', 'Juices', 'no']
                                ]" @on-import="handleImport" />

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
                                    <th>Category</th>
                                    <th>Sub Category</th>
                                    <th>Icon</th>
                                    <th>Total value</th>
                                    <th>Total Item</th>
                                    <th>Out of Stock</th>
                                    <th>Low Stock</th>
                                    <th>In Stock</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Loading State -->
                                <tr v-if="!categories.length && pagination.total === 0">
                                    <td colspan="11" class="text-center py-5">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="text-muted mt-2 mb-0">Loading categories...</p>
                                    </td>
                                </tr>

                                <!-- Data Rows -->
                                <template v-else>
                                    <tr v-for="(row, i) in filtered" :key="row.id">
                                        <td>{{ pagination.from + i }}</td>
                                        <td class="fw-semibold">{{ row.name }}</td>
                                        <td class="text-truncate" style="max-width: 260px">
                                            <div v-if="row.subcategories && row.subcategories.length">
                                                <div v-for="sub in row.subcategories" :key="sub.id"
                                                    class="d-flex justify-content-between align-items-center"
                                                    style="gap: 5px">
                                                    <span>{{ sub.name }}</span>
                                                    <button class="p-2 rounded-full text-blue-600 hover:bg-blue-100"
                                                        @click="editSubCategory(row, sub)" title="Edit Subcategory">
                                                        <Pencil class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </div>
                                            <span v-else>–</span>
                                        </td>
                                        <td>
                                            <div
                                                class="rounded d-inline-flex align-items-center justify-content-center img-chip">
                                                <img v-if="row.image_url" :src="row.image_url" :alt="row.name"
                                                    class="rounded"
                                                    style="width: 32px; height: 32px; object-fit: cover;" />
                                                <span v-else class="fs-5">📦</span>
                                            </div>
                                        </td>
                                        <td>{{ formatCurrencySymbol(row.total_value) }}</td>
                                        <td>{{ row.primary_inventory_items_count }}</td>
                                        <td>{{ row.out_of_stock }}</td>
                                        <td>{{ row.low_stock }}</td>
                                        <td>{{ row.in_stock }}</td>
                                        <td class="text-center">
                                            <span
                                                :class="row.active
                                                    ? 'inline-block px-3 py-1 text-xs font-semibold text-white bg-success rounded-full text-center w-20'
                                                    : 'inline-block px-3 py-1 text-xs font-semibold text-white bg-danger rounded-full text-center w-20'">
                                                {{ row.active ? "Active" : "Inactive" }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-inline-flex align-items-center gap-3">
                                                <button data-bs-toggle="modal" data-bs-target="#modalUnitForm"
                                                    @click="() => { editRow(row); }" title="Edit"
                                                    class="p-2 rounded-full text-blue-600 hover:bg-blue-100">
                                                    <Pencil class="w-4 h-4" />
                                                </button>
                                                <ConfirmModal :title="'Confirm Delete'"
                                                    :message="`Are you sure you want to delete ${row.name}?`"
                                                    :showDeleteButton="true" @confirm="() => { deleteCategory(row); }"
                                                    @cancel="() => { }" />
                                            </div>
                                        </td>
                                    </tr>

                                    <tr v-if="filtered.length === 0">
                                        <td colspan="11" class="text-center text-muted py-4">
                                            {{ q.trim() ? "No categories found matching your search." : "No categories found." }}
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>


                    </div>
                    <div v-if="pagination.last_page > 1" class="mt-4 d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
                        </div>

                        <Pagination :pagination="pagination.links" :isApiDriven="true"
                            @page-changed="handlePageChange" />
                    </div>
                </div>
            </div>

            <!-- =================== View Modal of Category =================== -->
            <div class="modal fade" id="viewCatModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content rounded-4">
                        <div class="modal-header">
                            <h5 class="modal-title">Category Details</h5>
                            <button
                                class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                                data-bs-dismiss="modal" aria-label="Close" title="Close">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
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
                                <span v-if="viewingCategory.subcategories.length">
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
                                {{
                                    editingCategory
                                        ? "Edit Category"
                                        : "Add Raw Material Category"
                                }}
                            </h5>
                            <button
                                class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                                data-bs-dismiss="modal" aria-label="Close" title="Close">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
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
                                                    @click="resetErrors()" @change="
                                                        isSub = true;
                                                    selectedParentId = null;
                                                    " name="isSub" />
                                                <label class="form-check-label">Yes</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" :checked="!isSub"
                                                    @click="resetErrors()" @change="
                                                        isSub = false;
                                                    selectedParentId = null;
                                                    " name="isSub" />
                                                <label class="form-check-label">No</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Parent Category Dropdown - Only show when subcategory is Yes -->
                                    <div class="col-lg-6" v-if="isSub">
                                        <label class="form-label d-block mb-2">Select Parent Category</label>
                                        <select v-model="selectedParentId" class="form-select" :class="{
                                            'is-invalid':
                                                catFormErrors?.parent_id,
                                        }" required>
                                            <option disabled :value="null">
                                                Choose Parent Category
                                            </option>
                                            <option v-for="cat in parentCategories" :key="cat.id" :value="cat.id">
                                                {{ cat.name }}
                                            </option>
                                        </select>
                                        <small v-if="catFormErrors?.parent_id" class="text-danger">
                                            {{ catFormErrors.parent_id[0] }}
                                        </small>
                                    </div>
                                </template>

                                <div class="col-lg-6">
                                    <label class="form-label d-block mb-2">Manual Icon</label>
                                    <div class="dropdown w-100">
                                        <button
                                            class="btn btn-outline-secondary w-100 d-flex justify-content-between align-items-center rounded-3"
                                            data-bs-toggle="dropdown">
                                            <span class="d-flex align-items-center">
                                                <img :src="manualIcon.value" alt="icon" class="me-2 rounded"
                                                    style="width: 24px; height: 24px; object-fit: cover;" />
                                                {{ manualIcon.label }}
                                            </span>
                                            <i class="bi bi-caret-down-fill"></i>
                                        </button>

                                        <ul class="dropdown-menu w-100 shadow rounded-3">
                                            <li v-for="opt in iconOptions" :key="opt.label">
                                                <a class="dropdown-item d-flex align-items-center"
                                                    href="javascript:void(0)" @click="manualIcon = opt">
                                                    <img :src="opt.value" alt="icon" class="me-2 rounded"
                                                        style="width: 24px; height: 24px; object-fit: cover;" />
                                                    {{ opt.label }}
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>


                                <!-- Category Name -->
                                <div class="col-12" v-if="!isSub || editingCategory">
                                    <label class="form-label">Category Name</label>
                                    <input v-if="editingCategory" type="text" v-model="manualCategories[0].label"
                                        class="form-control" :class="{
                                            'is-invalid': catFormErrors.name,
                                        }" placeholder="Enter category name" />

                                    <MultiSelect v-else v-model="manualCategories" :options="commonChips"
                                        optionLabel="label" optionValue="value" :filter="true" display="chip" :class="{
                                            'is-invalid': catFormErrors.name,
                                        }" placeholder="Select or add categories..." class="w-100 select"
                                        appendTo="self" @keydown.enter.prevent="
                                            addCustomCategory
                                        " @blur="addCustomCategory" @filter="(e) => (filterText = e.value)">
                                        <template #option="{ option }">
                                            {{ option.label }}
                                        </template>
                                    </MultiSelect>
                                    <small v-if="catFormErrors.name" class="text-danger">
                                        {{ catFormErrors.name[0] }}
                                    </small>
                                </div>

                                <!-- Subcategory Section -->
                                <div class="col-12" v-if="isSub && !editingCategory">
                                    <label class="form-label">Subcategory Name(s)</label>

                                    <!-- CREATE: MultiSelect -->
                                    <MultiSelect v-model="manualSubcategories" :options="options" optionLabel="label"
                                        optionValue="value" :filter="true" display="chip" :class="{
                                            'is-invalid':
                                                catFormErrors?.subcategories ||
                                                catFormErrors?.name,
                                        }" placeholder="Select or add subcategories..." class="w-100" appendTo="self"
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
                                    <!-- Errors -->
                                    <div v-if="catFormErrors?.subcategories" class="text-danger">
                                        {{ catFormErrors.subcategories[0] }}
                                    </div>
                                    <div v-if="catFormErrors?.name" class="text-danger">
                                        {{ catFormErrors.name[0] }}
                                    </div>
                                </div>

                                <!-- EDIT MODE: Show parent category only -->
                                <div class="col-12" v-if="editingCategory && parentCategory">
                                    <label class="form-label">Parent Category</label>
                                    <input type="text" class="form-control" :value="parentCategory.label" disabled />
                                </div>

                                <!-- Active toggle -->
                                <div class="col-12">
                                    <label class="form-label d-block mb-2">Active</label>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" :checked="manualActive"
                                                @change="manualActive = true" name="active" />
                                            <label class="form-check-label">Yes</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" :checked="!manualActive"
                                                @change="manualActive = false" name="active" />
                                            <label class="form-check-label">No</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4" />

                            <div class="mt-4">
                                <button class="btn btn-primary btn-sm rounded-pill px-4" :disabled="submitting"
                                    @click="submitCategory()">
                                    <template v-if="submitting">
                                        <span class="spinner-border spinner-border-sm me-2"></span>
                                        Saving...
                                    </template>
                                    <template v-else>
                                        {{
                                            editingCategory
                                                ? "Save"
                                                : "Save"
                                        }}
                                    </template>
                                </button>

                                <button class="btn btn-secondary rounded-pill btn-sm px-4 ms-2" data-bs-dismiss="modal"
                                    @click="resetModal">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /modal -->

            <!-- Edit Subcategory Modal -->
            <div class="modal fade" id="editSubCatModal" tabindex="-1" aria-labelledby="editSubCatModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 @click="subCatErrors = ''" class="modal-title" id="editSubCatModalLabel">
                                Edit Subcategory
                            </h5>

                            <button
                                class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                                data-bs-dismiss="modal" aria-label="Close" title="Close">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="subCategoryName" class="form-label">Subcategory Name</label>
                                <input type="text" class="form-control" id="subCategoryName"
                                    v-model="editingSubCategory.name" :disabled="submittingSub" />
                                <small class="text-danger">{{
                                    subCatErrors
                                    }}</small>
                            </div>
                            <button type="button"
                                class="px-4 py-2 rounded-pill btn btn-primary text-white text-center d-flex align-items-center justify-content-center gap-2"
                                @click="submitSubCategory" :disabled="submittingSub">
                                <span v-if="submittingSub" class="spinner-border spinner-border-sm text-light"
                                    role="status"></span>
                                <span>{{ submittingSub ? "Saving..." : "Save" }}</span>
                            </button>

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
    color: #000000;
}

.side-link {
    border-radius: 55%;
    background-color: #fff !important;
}


:global(.dark .p-multiselect-empty-message){
    color: #fff !important;
}
.dark .side-link {
    border-radius: 55%;
    background-color: #181818 !important;
}

.dark .p-3 {
    background-color: #181818 !important;
    color: #fff !important;
}

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

.dark .p-multiselect-empty-message {
    color: #fff !important;
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

.img-chip {
    width: 40px;
    height: 40px;
    background: #f1f5f9;
    color: #000 !important;
}

.chip {
    padding: 8px 14px;
    font-weight: 600;
}

.dropdown-menu {
    position: absolute !important;
    z-index: 1050 !important;
}

.dark .dropdown-menu {
    background-color: #181818 !important;
    border: 1px solid #555 !important;
}

.dark .dropdown-menu li:hover {
    background-color: #555 !important;
}

.table-container {
    overflow: visible !important;
}

:deep(.p-multiselect-overlay) {
    background: #fff !important;
    color: #000 !important;
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

/* ==================== Dark Mode Select Styling ====================== */
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

@media only screen and (min-device-width: 1024px) and (max-device-width: 1366px) and (orientation: portrait) {

    .page-wrapper {
        padding: 12px !important;
    }

    .card {
        border-radius: 16px !important;
    }

    .d-flex.align-items-center.justify-content-between {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 10px;
    }

    .d-flex.gap-2.align-items-center {
        flex-wrap: wrap;
        gap: 10px;
        justify-content: flex-start;
    }

    .search-wrap {
        width: 30% !important;
        margin-bottom: 10px;
    }

    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .kpi-cards {
        border-bottom: 1px !important;
    }

    table.table th,
    table.table td {
        font-size: 14px;
        white-space: nowrap;
    }
}

/* 🎯 iPad Pro 12.9" Landscape (1366 x 1024) */
@media only screen and (min-device-width: 1024px) and (max-device-width: 1366px) and (orientation: landscape) {

    .page-wrapper {
        padding: 16px !important;
    }

    .card-body {
        padding: 20px !important;
    }

    .d-flex.align-items-center.justify-content-between {
        flex-direction: row;
        flex-wrap: wrap;
        gap: 15px;
    }

    .d-flex.gap-2.align-items-center {
        flex-wrap: wrap;
        gap: 10px;
    }

    .search-wrap {
        min-width: 250px;
    }

    .table-responsive {
        overflow-x: auto;
    }

    table.table th,
    table.table td {
        font-size: 15px;
    }
}
</style>
