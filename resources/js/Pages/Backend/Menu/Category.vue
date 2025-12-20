<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, watch, onMounted, onUpdated } from "vue";
import MultiSelect from "primevue/multiselect";
import Select from "primevue/select";
import { useFormatters } from '@/composables/useFormatters'
import FilterModal from "@/Components/FilterModal.vue";
import { nextTick } from "vue";
import Dropdown from 'primevue/dropdown'

const { formatMoney, formatCurrencySymbol, formatNumber, dateFmt } = useFormatters()

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

const exportOption = ref(null)

// Image cropper states
const iconFile = ref(null);
const iconUrl = ref(null);
const showCropper = ref(false);
const showImageModal = ref(false);
const previewImage = ref(null);

const exportOptions = [
    { label: 'PDF', value: 'pdf' },
    { label: 'Excel', value: 'excel' },
    { label: 'CSV', value: 'csv' },
]

const commonChips = ref([
    { label: "Hot Drinks", value: "Hot Drinks" },
    { label: "Cold Drinks", value: "Cold Drinks" },
    { label: "Breakfast", value: "Breakfast" },
    { label: "Street Snacks", value: "Street Snacks" },
    { label: "Wala Wraps", value: "Wala Wraps" },
    { label: "Bombay Toasties", value: "Bombay Toasties" },
    { label: "Salads", value: "Salads" },
    { label: "Bombay Bowls", value: "Bombay Bowls" },
    { label: "Bakery", value: "Bakery" },
    { label: "Soup", value: "Soup" },
    { label: "Desserts", value: "Desserts" },
    { label: "Ice Cream", value: "Ice Cream" },
]);

// 🔁 Keep function call same
const onExportChange = (e) => {
    if (e.value) {
        onDownload(e.value)
        exportOption.value = null // reset after click
    }
}

const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
    from: 0,
    to: 0,
    links: []
});

const categoryStats = ref({
    total: 0,
    active: 0,
    inactive: 0
});

const defaultCategoryFilters = {
    sortBy: "",
    category: "",
    status: "",
    hasSubcategories: "",
};
const filters = ref({ ...defaultCategoryFilters });
const appliedFilters = ref({ ...defaultCategoryFilters });

const fetchCategories = async (page = null) => {
    try {
        loading.value = true;
        const params = {
            page: page || pagination.value.current_page,
            per_page: pagination.value.per_page,
        };
        const searchQuery = (q.value || '').trim();
        if (searchQuery) {
            params.q = searchQuery;
        }
        if (appliedFilters.value.sortBy) {
            params.sort_by = appliedFilters.value.sortBy;
        }
        if (appliedFilters.value.status) {
            params.status = appliedFilters.value.status;
        }
        if (appliedFilters.value.category) {
            params.category = appliedFilters.value.category;
        }
        if (appliedFilters.value.hasSubcategories) {
            params.has_subcategories = appliedFilters.value.hasSubcategories;
        }

        console.log('📤 Sending params:', params); 

        const res = await axios.get("/api/menu-categories/parents/list", { params });

        categories.value = res.data.data || [];

        if (res.data.stats) {
            categoryStats.value = res.data.stats;
        }

        pagination.value = {
            current_page: res.data.current_page,
            last_page: res.data.last_page,
            per_page: res.data.per_page,
            total: res.data.total,
            from: res.data.from,
            to: res.data.to,
            links: res.data.links
        };

        loading.value = false;
    } catch (err) {
        console.error("Failed to fetch categories:", err);
        toast.error("Failed to load categories");
        loading.value = false;
    }
};

const hasActiveFilters = computed(() => {
    return Object.values(appliedFilters.value).some(value => value !== "");
});

let searchTimeout = null;

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
    fetchCategories();
    const filterModal = document.getElementById('categoryFilterModal');
    if (filterModal) {
        filterModal.addEventListener('hidden.bs.modal', () => {
            filters.value = { ...appliedFilters.value };
        });
    }
});

// Get only parent categories (main categories) for dropdown
const parentCategories = computed(() => {
    return categories.value.filter((cat) => cat.parent_id === null);
});

/* ---------------- KPI (fake) ---------------- */
const CategoriesDetails = computed(() => [
    {
        label: "Total Categories",
        value: categoryStats.value.total,
        icon: Shapes,
        iconBg: "bg-light-primary",
        iconColor: "text-primary",
    },
    {
        label: "Active Categories",
        value: categoryStats.value.active,
        icon: Package,
        iconBg: "bg-light-success",
        iconColor: "text-success",
    },
    {
        label: "Inactive Categories",
        value: categoryStats.value.inactive,
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
const loading = ref(false);



const handleFilterApply = () => {
    appliedFilters.value = { ...filters.value };
    pagination.value.current_page = 1; // ✅ Reset to page 1
    fetchCategories(1); // ✅ Fetch with new filters

    const modal = bootstrap.Modal.getInstance(
        document.getElementById("categoryFilterModal")
    );
    modal?.hide();
};

const filterOptions = computed(() => ({
    sortOptions: [
        { value: "name_asc", label: "Name: A to Z" },
        { value: "name_desc", label: "Name: Z to A" },
    ],
    statusOptions: [
        { value: "active", label: "Active" },
        { value: "inactive", label: "Inactive" },
    ],
    hasSubcategoriesOptions: [
        { value: "yes", label: "Has Subcategories" },
        { value: "no", label: "No Subcategories" },
    ],
}));

const handleFilterClear = () => {
    filters.value = { ...defaultCategoryFilters };
    appliedFilters.value = { ...defaultCategoryFilters };
    pagination.value.current_page = 1; // ✅ Reset to page 1
    pagination.value.per_page = 10; // ✅ Reset per_page
    fetchCategories(1); // ✅ Fetch without filters
};

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
// const manualIcon = ref({ label: "Produce (Veg/Fruit)", value: "🥬" });


/* ---------------- Submit (console + Promise then/catch) ---------------- */
const submitting = ref(false);
const catFormErrors = ref({});

import axios from "axios";
import ImportFile from "@/Components/importFile.vue";
import { Head } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";
import ImageCropperModal from "@/Components/ImageCropperModal.vue";
import ImageZoomModal from "@/Components/ImageZoomModal.vue";

const resetFields = () => {
    isSub.value = false;
    manualCategories.value = [];
    manualSubcategories.value = [];
    manualName.value = "";
    manualActive.value = true;
    selectedParentId.value = null;

    if (iconUrl.value && iconUrl.value.startsWith("blob:")) {
        URL.revokeObjectURL(iconUrl.value);
    }
    iconFile.value = null;
    iconUrl.value = null;

    catFormErrors.value = {};
    filterText.value = "";
    currentFilterValue.value = "";
    options.value = [];
    manualSubcategoriesInput.value = "";
    editingCategory.value = null;
    showCropper.value = false;
    showImageModal.value = false;
};

const onCropped = ({ file }) => {
    iconFile.value = file;
    iconUrl.value = URL.createObjectURL(file);
    showCropper.value = false;
};

const openImageModal = () => {
    previewImage.value = iconUrl.value;
    showImageModal.value = true;
};

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
        // ==================== UPDATE MODE ====================
        if (editingCategory.value) {
            const formData = new FormData();

            // Determine name
            let categoryName = "";
            if (isSub.value) {
                categoryName = manualName.value?.trim();
            } else {
                categoryName =
                    manualCategories.value[0]?.label?.trim() ||
                    manualCategories.value[0]?.name?.trim() ||
                    (typeof manualCategories.value[0] === "string"
                        ? manualCategories.value[0].trim()
                        : "");
            }

            if (!categoryName) {
                catFormErrors.value.name = ["Category name cannot be empty"];
                toast.error("Category name cannot be empty");
                submitting.value = false;
                return;
            }

            formData.append("name", categoryName);
            formData.append("active", manualActive.value ? "1" : "0");
            formData.append("parent_id", selectedParentId.value || "");
            formData.append("_method", "PUT");

            // ✅ FIXED: Simple file append like Menu code
            if (iconFile.value instanceof File) {
                formData.append("icon", iconFile.value);
            }

            // Handle subcategories if not a subcategory itself
            if (!selectedParentId.value && manualSubcategoriesInput.value) {
                const subcategoryNames = manualSubcategoriesInput.value
                    .split(",")
                    .map((s) => s.trim())
                    .filter((s) => s.length > 0);

                subcategoryNames.forEach((name, index) => {
                    const existingSub =
                        editingCategory.value.subcategories?.find(
                            (sub) =>
                                sub.name.toLowerCase() === name.toLowerCase()
                        );

                    formData.append(`subcategories[${index}][name]`, name);
                    if (existingSub) {
                        formData.append(`subcategories[${index}][id]`, existingSub.id);
                        formData.append(
                            `subcategories[${index}][active]`,
                            existingSub.active ? "1" : "0"
                        );
                    } else {
                        formData.append(`subcategories[${index}][active]`, "1");
                    }
                });
            }

            await axios.post(
                `/menu-categories/${editingCategory.value.id}`,
                formData,
                {
                    headers: {
                        "Content-Type": "multipart/form-data"
                    }
                }
            );

            toast.success("Category updated successfully");
        }
        // ==================== CREATE MODE ====================
        else {
            const formData = new FormData();
            let categoriesPayload = [];

            if (isSub.value) {
                if (manualSubcategories.value.length === 0) {
                    catFormErrors.value.subcategories = [
                        "Please add at least one subcategory",
                    ];
                    toast.error("Please add at least one subcategory");
                    submitting.value = false;
                    return;
                }

                categoriesPayload = manualSubcategories.value.map((cat) => ({
                    name: typeof cat === "string" ? cat : cat.label,
                    active: manualActive.value,
                    parent_id: selectedParentId.value,
                }));
            } else {
                if (manualCategories.value.length === 0) {
                    catFormErrors.value.name = [
                        "Please add at least one category",
                    ];
                    toast.error("Please add at least one category");
                    submitting.value = false;
                    return;
                }

                categoriesPayload = manualCategories.value.map((cat) => ({
                    name: typeof cat === "string" ? cat : cat.label,
                    active: manualActive.value,
                    parent_id: null,
                }));
            }

            formData.append("isSubCategory", isSub.value ? "1" : "0");

            categoriesPayload.forEach((cat, index) => {
                formData.append(`categories[${index}][name]`, cat.name);
                formData.append(
                    `categories[${index}][active]`,
                    cat.active ? "1" : "0"
                );
                if (cat.parent_id) {
                    formData.append(
                        `categories[${index}][parent_id]`,
                        cat.parent_id
                    );
                }
            });

            // ✅ FIXED: Simple file append like Menu code
            if (iconFile.value instanceof File) {
                formData.append("icon", iconFile.value);
            }

            // ✅ DEBUG: Log FormData contents
            console.log('📤 FormData Contents:');
            for (let pair of formData.entries()) {
                if (pair[1] instanceof File) {
                    console.log(`  ${pair[0]}: [File] ${pair[1].name} (${pair[1].type}, ${pair[1].size} bytes)`);
                } else {
                    console.log(`  ${pair[0]}: ${pair[1]}`);
                }
            }

            await axios.post("/menu-categories", formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });

            toast.success("Category created successfully");
        }

        // Close modal and reset
        const m = bootstrap.Modal.getInstance(
            document.getElementById("addCatModal")
        );
        m?.hide();

        resetModal();
        editingCategory.value = null;
        await fetchCategories();

    } catch (err) {
        console.error("❌ Submit error:", err);

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

// ============= Updated Reset Modal =============
const resetModal = () => {
    isSub.value = false;
    manualCategories.value = [];
    manualActive.value = true;
    selectedParentId.value = null;
    manualName.value = "";

    if (iconUrl.value && iconUrl.value.startsWith("blob:")) {
        URL.revokeObjectURL(iconUrl.value);
    }
    iconFile.value = null;
    iconUrl.value = null;
    editingCategory.value = null;
    manualSubcategoriesInput.value = "";
    showCropper.value = false;
    showImageModal.value = false;
    previewImage.value = null;
};

const resetErrors = () => {
    catFormErrors.value = {};
    subCatErrors.value = "";
};

const manualSubcategoriesInput = ref("");

// Also update your editRow function to better handle subcategory IDs
const editRow = (row) => {

    resetErrors();
    editingCategory.value = row;
    isSub.value = !!row.parent_id;
    selectedParentId.value = row.parent_id || null;


    iconFile.value = null;
    iconUrl.value = row.image_url || null;
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

    // Check if category already exists in commonChips
    if (!commonChips.value.some((o) => o.label.toLowerCase() === name.toLowerCase())) {
        commonChips.value.push({ label: name, value: name });
    }

    // Add just the VALUE (string) to manualCategories, not the object
    if (!manualCategories.value.includes(name)) {
        manualCategories.value = [...manualCategories.value, name]; // ✅ Push the string value
    }

    filterText.value = "";
};
// ================= Delete Category ======================

const deleteCategory = async (row) => {
    if (!row?.id) return;

    try {
        const res = await axios.delete(`/menu-categories/${row.id}`);

        toast.success(res.data.message || "Category deleted successfully");
        await fetchCategories(); // refresh the table

    } catch (err) {
        console.error("❌ Delete error:", err.response?.data || err.message);

        // ✔ Show backend message, if available
        const backendMessage = err.response?.data?.message
            || "Failed to delete category ❌";

        toast.error(backendMessage);
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
    resetErrors();
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
    subCatErrors.value = {};
    submittingSub.value = false;
};

const submitSubCategory = async () => {

    if (!editingSubCategory.value.name.trim()) {
        subCatErrors.value = "Subcategory name cannot be empty";
        toast.error("Please filled all the required fields");
        return;
    }

    submittingSub.value = true;


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

const fetchAllCategories = async () => {
    try {
        const params = {
            per_page: 999999, 
        };
        
        const searchQuery = (q.value || '').trim();
        if (searchQuery) {
            params.q = searchQuery;
        }
        if (appliedFilters.value.sortBy) {
            params.sort_by = appliedFilters.value.sortBy;
        }
        if (appliedFilters.value.status) {
            params.status = appliedFilters.value.status;
        }
        if (appliedFilters.value.category) {
            params.category = appliedFilters.value.category;
        }
        if (appliedFilters.value.hasSubcategories) {
            params.has_subcategories = appliedFilters.value.hasSubcategories;
        }

        const res = await axios.get("/api/menu-categories/parents/list", { params });
        return res.data.data || [];
    } catch (err) {
        console.error("Failed to fetch all categories:", err);
        toast.error("Failed to load all categories for export");
        return [];
    }
};
const onDownload = async (type) => {
    try {
        toast.info("Preparing download...");
        
        // Fetch all categories for export
        const allCategories = await fetchAllCategories();
        
        if (!allCategories || allCategories.length === 0) {
            toast.error("No categories data to download");
            return;
        }

        if (type === "pdf") {
            downloadPDF(allCategories);
        } else if (type === "excel") {
            downloadExcel(allCategories);
        } else if (type === "csv") {
            downloadCSV(allCategories);
        } else {
            toast.error("Invalid download type");
        }
    } catch (error) {
        console.error("Download failed:", error);
        toast.error(`Download failed: ${error.message}`);
    }
};

watch(q, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        pagination.value.current_page = 1;
        pagination.value.per_page = 10; 
        fetchCategories(1);
    }, 500);
});

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
        doc.setFontSize(18);
        doc.setFont("helvetica", "bold");
        doc.text("Menu Categories Report", 60, 20);

        // 🗓️ Metadata
        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        const currentDate = new Date().toLocaleString();
        doc.text(`Generated on: ${currentDate}`, 14, 28);
        doc.text(`Total Categories: ${data.length}`, 14, 34);

        // 📋 Table Columns
        const tableColumns = ["Category", "Subcategories", "Active"];

        // 📊 Table Rows
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

        // 📑 Generate Table
        autoTable(doc, {
            head: [tableColumns],
            body: tableRows,
            startY: 40,
            styles: {
                fontSize: 9,
                cellPadding: 3,
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

        // 💾 Save PDF
        const fileName = `Categories_${new Date().toISOString().split("T")[0]}.pdf`;
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
        if (typeof XLSX === "undefined") {
            throw new Error("XLSX library is not loaded");
        }

        // Prepare data to match CSV format
        const worksheetData = data.map((category) => {
            const subcategoryNames =
                category.subcategories && category.subcategories.length > 0
                    ? category.subcategories.map((sub) => sub.name).join(", ")
                    : "";

            return {
                Category: category.name || "",
                Subcategory: subcategoryNames,
                Active: category.active ? "Yes" : "No",
            }
        });

        // Create workbook & worksheet
        const workbook = XLSX.utils.book_new();
        const worksheet = XLSX.utils.json_to_sheet(worksheetData);

        // Column widths
        worksheet["!cols"] = [
            { wch: 25 }, // Category
            { wch: 40 }, // Subcategory
            { wch: 10 }, // Active
        ];

        // Append worksheet
        XLSX.utils.book_append_sheet(workbook, worksheet, "Menu Categories");

        // Metadata sheet
        const metaData = [
            { Info: "Generated On", Value: new Date().toLocaleString() },
            { Info: "Total Categories", Value: data.length },
            { Info: "Exported By", Value: "Menu Categories Management" },
        ];
        const metaSheet = XLSX.utils.json_to_sheet(metaData);
        XLSX.utils.book_append_sheet(workbook, metaSheet, "Report Info");

        // File name
        const fileName = `Categories_${new Date().toISOString().split("T")[0]}.xlsx`;

        // Save Excel
        XLSX.writeFile(workbook, fileName);

        toast.success("Excel downloaded successfully", {
            autoClose: 2500,
        });
    } catch (error) {
        console.error("Excel generation error:", error);
        toast.error(`Excel generation failed: ${error.message}`, {
            autoClose: 5000,
        });
    }
};


// handle import function for menu categories
const handleImport = (data) => {
    if (!data || data.length <= 1) {
        toast.error("The imported file is empty.");
        return; // Stop execution
    }

    const headers = data[0];
    const rows = data.slice(1);

    const categoriesToImport = rows.map((row) => {
        return {
            category: row[0] || "",       // Parent category
            subcategory: row[1] || null,  // Child category (optional)
            active: row[2] == "0" ? 0 : 1 // Default active=1
        };
    });

    // Check for duplicate parent category names within the CSV
    const parentCategoryNames = categoriesToImport
        .filter(cat => cat.category) // Only check parent categories
        .map(cat => cat.category.trim().toLowerCase());
    const duplicatesInCSV = parentCategoryNames.filter((name, index) => parentCategoryNames.indexOf(name) !== index);

    if (duplicatesInCSV.length > 0) {
        toast.error(`Duplicate parent category names found in CSV: ${[...new Set(duplicatesInCSV)].join(", ")}`);
        return; // Stop execution
    }

    // Check for duplicate parent category names in the existing table
    const existingParentCategoryNames = categories.value
        .filter(cat => !cat.parent_id) // Only parent categories
        .map(cat => cat.name.trim().toLowerCase());
    const duplicatesInTable = categoriesToImport
        .filter(cat => cat.category) // Only check parent categories
        .filter(importCat => existingParentCategoryNames.includes(importCat.category.trim().toLowerCase()));

    if (duplicatesInTable.length > 0) {
        const duplicateNamesList = duplicatesInTable.map(cat => cat.category).join(", ");
        toast.error(`Parent categories already exist in the table: ${duplicateNamesList}`);
        return; // Stop execution
    }

    axios
        .post("/api/menu-categories/import", { categories: categoriesToImport })
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

        <Head title="Menu Category" />
        <div class="page-wrapper">

            <h4 class="mb-3">Categories</h4>

            <!-- KPI -->
            <div class="row g-3">
                <div v-for="c in CategoriesDetails" :key="c.label" class="col-md-6 col-xl-4">
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

                            <FilterModal v-model="filters" title="Menu Categories" modal-id="categoryFilterModal"
                                modal-size="modal-lg" :sort-options="filterOptions.sortOptions"
                                :status-options="filterOptions.statusOptions" :show-stock-status="false"
                                :show-price-range="false" :show-date-range="false" :show-category="false"
                                :suppliers="[]" @apply="handleFilterApply" @clear="handleFilterClear">

                                <!-- Custom filters slot -->
                                <template #customFilters="{ filters }">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold text-dark">
                                            <i class="fas fa-tags me-2 text-muted"></i>Category
                                        </label>
                                        <select v-model="filters.category" class="form-select">
                                            <option value="">All Categories</option>
                                            <option v-for="cat in parentCategories" :key="cat.id" :value="cat.id">
                                                {{ cat.name }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mt-3">
                                        <label class="form-label fw-semibold text-dark">
                                            <i class="fas fa-toggle-on me-2 text-muted"></i>Status
                                        </label>
                                        <select v-model="filters.status" class="form-select">
                                            <option value="">All Status</option>
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>

                                    <div class="col-md-12 mt-3">
                                        <label class="form-label fw-semibold text-dark">
                                            <i class="fas fa-sitemap me-2 text-muted"></i>Has Subcategories
                                        </label>
                                        <select v-model="filters.hasSubcategories" class="form-select">
                                            <option value="">All</option>
                                            <option value="yes">Has Subcategories</option>
                                            <option value="no">No Subcategories</option>
                                        </select>
                                    </div>
                                </template>
                            </FilterModal>

                            <button data-bs-toggle="modal" data-bs-target="#addCatModal" @click="
                                resetErrors;
                            editingCategory = null;
                            resetSubCategoryFields();
                            resetFields();
                            "
                                class="d-flex align-items-center gap-1 px-4 btn-sm py-2 rounded-pill btn btn-primary text-white">
                                <Plus class="w-4 h-4" /> Add Category
                            </button>

                            <ImportFile label="Import" :sampleHeaders="[
                                'category', 'subcategory', 'active'
                            ]" :sampleData="[
                                ['Dairy', 'TestSubCat', 1],
                                ['Bakery', 'Bread', 1],
                                ['Beverages', 'Juices', 0]
                            ]" @on-import="handleImport" />

                            <!-- <ImportFile label="Import" @on-import="handleImport" /> -->
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
                                    <th>S.#</th>
                                    <th>Category</th>
                                    <th>Sub Category</th>
                                    <th>Icon</th>
                                    <!-- <th>Total value</th> -->
                                    <th>Total Item</th>
                                    <!-- <th>Out of Stock</th> -->
                                    <!-- <th>Low Stock</th> -->
                                    <!-- <th>In Stock</th> -->
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, i) in categories" :key="row.id">
                                    <td>{{ i + 1 }}</td>
                                    <td class="fw-semibold">
                                        {{ row.name }}
                                    </td>
                                    <td class="text-truncate" style="max-width: 260px">
                                        <div v-if="
                                            row.subcategories &&
                                            row.subcategories.length
                                        ">
                                            <div v-for="sub in row.subcategories" :key="sub.id"
                                                class="d-flex justify-content-between align-items-center"
                                                style="gap: 5px">
                                                <span>{{ sub.name }}</span>

                                                <button data-bs-toggle="modal" @click="
                                                    () => {
                                                        editSubCategory(
                                                            row,
                                                            sub
                                                        );

                                                    }
                                                " title="Edit"
                                                    class="p-2 rounded-full text-blue-600 hover:bg-blue-100">
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
                                                class="rounded" style="width: 32px; height: 32px; object-fit: cover;" />
                                            <span v-else class="fs-5">📦</span>
                                        </div>
                                    </td>
                                    <!-- <td>{{ formatCurrencySymbol(row.total_value) }}</td> -->
                                    <td>{{ row.total_menu_items }}</td>
                                    <!-- <td>{{ row.out_of_stock }}</td> -->
                                    <!-- <td>{{ row.low_stock }}</td> -->
                                    <!-- <td>{{ row.in_stock }}</td> -->
                                    <td>
                                        <span :class="[
                                            'badge',
                                            row.active ? 'bg-success rounded-pill' : 'bg-danger rounded-pill'
                                        ]">
                                            {{ row.active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center gap-3">
                                            <button data-bs-toggle="modal" data-bs-target="#modalUnitForm" @click="
                                                () => {
                                                    editRow(row);
                                                }
                                            " title="Edit" class="p-2 rounded-full text-blue-600 hover:bg-blue-100">
                                                <Pencil class="w-4 h-4" />
                                            </button>

                                            <ConfirmModal :title="'Confirm Delete'"
                                                :message="`Are you sure you want to delete ${row.name}?`"
                                                :showDeleteButton="true" @confirm="
                                                    () => {
                                                        deleteCategory(row);
                                                    }
                                                " @cancel="() => { }" />
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="categories.length === 0">
                                    <td colspan="10" class="text-center text-muted py-4">
                                        No categories found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="!loading && !hasActiveFilters && pagination.last_page > 1"
                        class="mt-4 d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
                        </div>

                        <Pagination :pagination="pagination.links" :isApiDriven="true"
                            @page-changed="handlePageChange" />
                    </div>
                </div>
            </div>

            <!-- ================== Add Category Modal ================== -->
            <div class="modal fade" id="addCatModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content rounded-4">
                        <div class="modal-header">
                            <h5 class="modal-title fw-semibold">
                                {{ editingCategory ? "Edit Menu Category" : "Add Menu Category" }}
                            </h5>
                            <button
                                class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                                data-bs-dismiss="modal" aria-label="Close" title="Close" @click="resetModal">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="modal-body">
                            <div class="row">
                                <!-- LEFT COLUMN - Form Fields -->
                                <div class="col-md-7">
                                    <div class="row g-3">
                                        <!-- Show "Is this a subcategory?" only when creating -->
                                        <template v-if="!editingCategory">
                                            <div class="col-12">
                                                <label class="form-label d-block mb-2">Is this a subcategory?</label>
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" :checked="isSub"
                                                            @click="resetErrors()"
                                                            @change="isSub = true; selectedParentId = null;"
                                                            name="isSub" />
                                                        <label class="form-check-label">Yes</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" :checked="!isSub"
                                                            @click="resetErrors()"
                                                            @change="isSub = false; selectedParentId = null;"
                                                            name="isSub" />
                                                        <label class="form-check-label">No</label>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Parent Category Dropdown -->
                                            <div class="col-12" v-if="isSub">
                                                <label class="form-label">Category</label>
                                                <Select v-model="selectedParentId" :options="parentCategories"
                                                    optionLabel="name" optionValue="id" placeholder="Select Category"
                                                    class="w-100" appendTo="self" :autoZIndex="true" :baseZIndex="2000"
                                                    :class="{ 'is-invalid': catFormErrors?.parent_id }" />
                                                <small v-if="catFormErrors?.parent_id" class="text-danger">
                                                    {{ catFormErrors.parent_id[0] }}
                                                </small>
                                            </div>
                                        </template>

                                        <!-- Category Name -->
                                        <div class="col-12" v-if="!isSub || editingCategory">
                                            <label class="form-label">Category Name</label>

                                            <!-- Single input when editing -->
                                            <input v-if="editingCategory" type="text"
                                                v-model="manualCategories[0].label" class="form-control"
                                                :class="{ 'is-invalid': catFormErrors.name }"
                                                placeholder="Enter category name" />

                                            <!-- MultiSelect when creating -->
                                            <MultiSelect v-else v-model="manualCategories" :options="commonChips"
                                                optionLabel="label" optionValue="value" :filter="true" display="chip"
                                                :class="{ 'is-invalid': catFormErrors.name }"
                                                placeholder="Select or add categories..." class="w-100" appendTo="self"
                                                @keydown.enter.prevent="addCustomCategory" @blur="addCustomCategory"
                                                @filter="(e) => (filterText = e.value)">
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

                                            <MultiSelect v-model="manualSubcategories" :options="options"
                                                optionLabel="label" optionValue="value" :filter="true" display="chip"
                                                :class="{ 'is-invalid': catFormErrors?.subcategories || catFormErrors?.name }"
                                                placeholder="Select or add subcategories..." class="w-100"
                                                appendTo="self" @filter="(e) => (currentFilterValue = e.value || '')"
                                                @keydown.enter.prevent="addCustomSubcategory"
                                                @blur="addCustomSubcategory">
                                                <template #option="{ option }">
                                                    <div>{{ option.label }}</div>
                                                </template>
                                                <template #footer>
                                                    <div class="p-3 d-flex justify-content-between">
                                                        <Button label="Add Custom" severity="secondary" variant="text"
                                                            size="small" icon="pi pi-plus" @click="addCustomSubcategory"
                                                            :disabled="!currentFilterValue.trim()" />
                                                        <div class="d-flex gap-2">
                                                            <Button label="Select All" severity="secondary"
                                                                variant="text" size="small" icon="pi pi-check"
                                                                @click="selectAllSubcategories" />
                                                            <Button label="Clear All" severity="danger" variant="text"
                                                                size="small" icon="pi pi-times"
                                                                @click="removeAllSubcategories" />
                                                        </div>
                                                    </div>
                                                </template>
                                            </MultiSelect>

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
                                            <input type="text" class="form-control" :value="parentCategory.label"
                                                disabled />
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
                                                    <input class="form-check-input" type="radio"
                                                        :checked="!manualActive" @change="manualActive = false"
                                                        name="active" />
                                                    <label class="form-check-label">No</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- RIGHT COLUMN - Category Image -->
                                <div class="col-md-5">
                                    <label class="form-label d-block mb-2">Category Image</label>
                                    <div class="logo-card" :class="{ 'is-invalid': catFormErrors.icon }"
                                        style="border: 2px dashed #6c757d; border-radius: 8px; padding: 1rem; min-height: 250px;">
                                        <div class="logo-frame d-flex flex-column align-items-center justify-content-center"
                                            style="height: 200px; cursor: pointer;" @click="showCropper = true">
                                            <img v-if="iconUrl" :src="iconUrl" alt="Category Image"
                                                style="max-width: 100%; max-height: 150px; object-fit: contain;" />
                                            <div v-else class="placeholder">
                                                <i class="bi bi-image"></i>
                                            </div>

                                        </div>

                                        <ImageCropperModal :show="showCropper" @close="showCropper = false"
                                            @cropped="onCropped" />

                                        <ImageZoomModal v-if="showImageModal" :show="showImageModal" :image="iconUrl"
                                            @close="showImageModal = false" />
                                    </div>
                                    <small v-if="catFormErrors.icon" class="text-danger">
                                        {{ catFormErrors.icon[0] }}
                                    </small>
                                </div>
                            </div>

                            <hr class="my-4" />

                            <div class="mt-4">
                                <button class="btn btn-primary rounded-pill btn-sm py-2 px-4" :disabled="submitting"
                                    @click="submitCategory()">
                                    <template v-if="submitting">
                                        <span class="spinner-border spinner-border-sm me-2"></span>
                                        {{ editingCategory ? "Saving..." : "Saving..." }}
                                    </template>
                                    <template v-else>
                                        {{ editingCategory ? "Save" : "Save" }}
                                    </template>
                                </button>

                                <button class="btn btn-secondary btn-sm py-2 rounded-pill px-4 ms-2"
                                    data-bs-dismiss="modal" @click="resetModal">
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
                <div class="modal-dialog modal-md modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editSubCatModalLabel">
                                Edit Subcategory
                            </h5>
                            <button
                                class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                                data-bs-dismiss="modal" aria-label="Close" title="Close" @click="resetErrors">
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
                                    v-model="editingSubCategory.name" :disabled="submittingSub" :class="{
                                        'is-invalid': subCatErrors,
                                    }" />
                                <small class="text-danger">{{
                                    subCatErrors
                                    }}</small>
                            </div>
                            <button type="button" class="btn btn-primary rounded-pill px-4" @click="submitSubCategory"
                                :disabled="submittingSub">
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
    </Master>
</template>

<style scoped>
.dark h4 {
    color: white;
}

.side-link {
    border-radius: 55%;
    background-color: #fff !important;
}

:global(.dark .form-control:focus) {
    border-color: #fff !important;
}



.logo-card {
    border: 2px dashed #ddd;
    border-radius: 8px;
    padding: 1rem;
    min-height: 300px;
}

.logo-frame {
    cursor: pointer;
    transition: all 0.3s ease;
}

.logo-frame:hover {
    opacity: 0.8;
}


.dark .side-link {
    border-radius: 55%;
    background-color: #181818 !important;
}

.dark .card {
    background-color: #181818 !important;
    /* gray-800 */
    color: #ffffff !important;
    /* gray-50 */
}

.dark .logo-card {
    background-color: #181818 !important;
}

.dark .logo-frame {
    background-color: #181818 !important;
}

.dark .fw-semibold {
    color: #fff !important;
}


.dark .p-3 {
    background-color: #181818 !important;
    color: #fff !important;
}

:global(.dark .p-multiselect-empty-message) {
    color: #fff !important;
}

.dark .table {
    background-color: #181818 !important;
    /* gray-900 */
    color: #f9fafb !important;
}

.dark .table thead {
    background-color: #181818 !important;
    /* gray-900 */
    color: #f9fafb !important;
}

.dark .table thead th {
    background-color: #181818 !important;
    /* gray-900 */
    color: #f9fafb !important;
}

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
    color: #181818;
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

.dark .form-label {
    color: #fff !important;
}

.dark .form-select {
    background-color: #212121 !important;
    color: #fff !important;
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

/* =========================================== */
/* Mobile */
@media (max-width: 575.98px) {
    .kpi-value {
        font-size: 1.45rem;
    }

    .search-wrap {
        width: 100%;
    }
}

.dark .logo-frame {
    background-color: #181818;
}
</style>
