<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, reactive, computed, onMounted, onUpdated, watch } from "vue";
import Select from "primevue/select";
import MultiSelect from "primevue/multiselect";
import { toast } from "vue3-toastify";
import { jsPDF } from "jspdf";
import autoTable from "jspdf-autotable";
import * as XLSX from "xlsx";
import FilterModal from '@/Components/FilterModal.vue'

import {
    Package,
    XCircle,
    AlertTriangle,
    CalendarX2,
    CalendarClock,
    Plus,
    Eye,
    Pencil,
    Download,
    Upload,
} from "lucide-vue-next";
import axios from "axios";

// ===================== Props =====================
const props = defineProps({
    inventories: Array,
    allergies: Array, // [{id,name}]
    tags: Array, // [{id,name}]
    units: Array, // [{id,name}]
    suppliers: Array, // [{id,name}]
    categories: Array, // [{id,name,parent_id?}]
});

// ===================== Category helpers (parent/children) =====================
const parentCategories = computed(() =>
    props.categories.filter((c) => !c.parent_id)
);
const subcatOptions = computed(() =>
    props.categories.filter((c) => c.parent_id === form.value.category_id)
);

// ===================== Data & fetching =====================


const inventories = ref(props.inventories?.data || []);
const items = computed(() => inventories.value);
const loading = ref(false);

const fetchInventories = async () => {
    try {
        loading.value = true;
        const res = await axios.get("inventory/api-inventories");
        const apiItems = res.data.data || [];

        if (apiItems.length === 0) {
            inventories.value = [];
            loading.value = false; // stop loader when no data
            return;
        }

        inventories.value = await Promise.all(
            apiItems.map(async (item) => {
                const stockRes = await axios.get(`/stock_entries/total/${item.id}`);
                const stockData = stockRes.data.total?.original || {};
                return {
                    ...item,
                    availableStock: stockData.available || 0,
                    stockValue: stockData.stockValue || 0,
                    minAlert: stockData.minAlert || 0,
                };
            })
        );

        loading.value = false;
    } catch (err) {
        console.error(err);
        loading.value = false;
    }
};


onMounted(() => {
    fetchInventories();
    fetchStockForCounting();

});

/* ===================== Toolbar: Search + Filter ===================== */
const q = ref("");
const sortBy = ref(""); // 'stock_desc' | 'stock_asc' | 'name_asc' | 'name_desc'


// Add to your components section
const components = {
    FilterModal,
    // ... your other components
}

// Replace your existing filter-related reactive data with:
const filters = ref({
    sortBy: '',
    category: '',
    supplier: '',
    stockStatus: '',
    priceMin: null,
    priceMax: null,
    dateFrom: '',
    dateTo: ''
})

// Update your computed properties
const filteredItems = computed(() => {
    let filtered = [...items.value]
    const term = q.value.trim().toLowerCase()

    // Text search
    if (term) {
        filtered = filtered.filter((i) => {
            const name = (i.name || "").toLowerCase()
            const categoryName = typeof i.category === "object"
                ? (i.category.name || "").toLowerCase()
                : (i.category || "").toString().toLowerCase()
            const unitName = typeof i.unit === "object"
                ? (i.unit.name || "").toLowerCase()
                : (i.unit || "").toString().toLowerCase()

            return name.includes(term) ||
                categoryName.includes(term) ||
                unitName.includes(term)
        })
    }

    // Category filter
    if (filters.value.category) {
        filtered = filtered.filter(item => {
            const categoryId = typeof item.category === 'object'
                ? item.category.id
                : item.category_id
            return categoryId == filters.value.category
        })
    }

    // Supplier filter
    if (filters.value.supplier) {
        filtered = filtered.filter(item => {
            const supplierId = typeof item.supplier === 'object'
                ? item.supplier.id
                : item.supplier_id
            return supplierId == filters.value.supplier
        })
    }

    // Stock status filter
    if (filters.value.stockStatus) {
        filtered = filtered.filter(item => {
            const stock = item.availableStock || 0
            const minAlert = item.minAlert || 5

            switch (filters.value.stockStatus) {
                case 'in_stock':
                    return stock >= minAlert
                case 'low_stock':
                    return stock > 0 && stock < minAlert
                case 'out_of_stock':
                    return stock <= 0
                case 'expired':
                    // You'll need to implement expired logic based on your stock entries
                    return false // placeholder
                case 'near_expire':
                    // You'll need to implement near expire logic based on your stock entries
                    return false // placeholder
                default:
                    return true
            }
        })
    }

    // Price range filter (if you have price data)
    if (filters.value.priceMin !== null || filters.value.priceMax !== null) {
        filtered = filtered.filter(item => {
            const price = item.price || item.stockValue || 0
            const min = filters.value.priceMin || 0
            const max = filters.value.priceMax || Infinity
            return price >= min && price <= max
        })
    }

    return filtered
})

const sortedItems = computed(() => {
    const arr = [...filteredItems.value]
    const sortBy = filters.value.sortBy

    switch (sortBy) {
        case "stock_desc":
            return arr.sort((a, b) => (b.stockValue || 0) - (a.stockValue || 0))
        case "stock_asc":
            return arr.sort((a, b) => (a.stockValue || 0) - (b.stockValue || 0))
        case "name_asc":
            return arr.sort((a, b) => (a.name || '').localeCompare(b.name || ''))
        case "name_desc":
            return arr.sort((a, b) => (b.name || '').localeCompare(a.name || ''))
        default:
            return arr
    }
})

// Filter options for the modal
const filterOptions = computed(() => ({
    sortOptions: [
        { value: 'stock_desc', label: 'Stock: High to Low' },
        { value: 'stock_asc', label: 'Stock: Low to High' },
        { value: 'name_asc', label: 'Name: A to Z' },
        { value: 'name_desc', label: 'Name: Z to A' }
    ],
    categories: props.categories || [],
    suppliers: props.suppliers || [],
    stockStatusOptions: [
        { value: 'in_stock', label: 'In Stock' },
        { value: 'low_stock', label: 'Low Stock' },
        { value: 'out_of_stock', label: 'Out of Stock' },
        { value: 'expired', label: 'Expired' },
        { value: 'near_expire', label: 'Near Expiry' }
    ]
}))

// Filter event handlers
const handleFilterApply = (appliedFilters) => {
    console.log('Filters applied:', appliedFilters)
    // Additional logic if needed
}

const handleFilterClear = () => {
    console.log('Filters cleared')
    // Additional logic if needed
}

// const filteredItems = computed(() => {
//     const term = q.value.trim().toLowerCase();
//     if (!term) return items.value;

//     return items.value.filter((i) => {
//         const name = (i.name || "").toLowerCase();
//         const categoryName =
//             typeof i.category === "object"
//                 ? (i.category.name || "").toLowerCase()
//                 : (i.category || "").toString().toLowerCase();
//         const unitName =
//             typeof i.unit === "object"
//                 ? (i.unit.name || "").toLowerCase()
//                 : (i.unit || "").toString().toLowerCase();

//         return (
//             name.includes(term) ||
//             categoryName.includes(term) ||
//             unitName.includes(term)
//         );
//     });
// });


// const sortedItems = computed(() => {
//     const arr = [...filteredItems.value];
//     switch (sortBy.value) {
//         case "stock_desc":
//             return arr.sort((a, b) => b.stockValue - a.stockValue);
//         case "stock_asc":
//             return arr.sort((a, b) => a.stockValue - b.stockValue);
//         case "name_asc":
//             return arr.sort((a, b) => a.name.localeCompare(b.name));
//         case "name_desc":
//             return arr.sort((a, b) => b.name.localeCompare(a.name));
//         default:
//             return arr;
//     }
// });

/* ===================== KPIs ===================== */
const totalItems = computed(() => items.value.length);
const stockitems = ref([])
const fetchStockForCounting = async () => {
    try {
        const response = await axios.get("/stock_entries");
        stockitems.value = response.data.data;
        console.log(stockitems.value);
    } catch (error) {
        console.error("Failed to fetch stock entries:", error);
    }
};
const lowStockCount = computed(
    () =>
        items.value.filter(
            (i) => i.availableStock > 0 && i.availableStock < (i.minAlert || 5)
        ).length
);
const outOfStockCount = computed(
    () => items.value.filter((i) => i.availableStock <= 0).length
);

const expiredCount = computed(() =>
    stockitems.value.filter((i) => {
        if (!i.expiry_date) return false;
        const expiry = new Date(i.expiry_date);
        const today = new Date();
        // reset hours to ignore time
        expiry.setHours(0, 0, 0, 0);
        today.setHours(0, 0, 0, 0);
        return expiry < today;
    }).length
);

const nearExpireCount = computed(() =>
    stockitems.value.filter((i) => {
        if (!i.expiry_date) return false;
        const expiry = new Date(i.expiry_date);
        const today = new Date();
        const diffDays = (expiry - today) / (1000 * 60 * 60 * 24);
        return diffDays > 0 && diffDays <= 15;
    }).length
);


const kpis = computed(() => [
    {
        label: "Total Items",
        value: totalItems.value ?? 0,
        icon: Package,
        iconBg: "bg-soft-success",
        iconColor: "text-success",
    },
    {
        label: "Out of Stock",
        value: outOfStockCount.value ?? 0,
        icon: XCircle,
        iconBg: "bg-soft-danger",
        iconColor: "text-danger",
    },
    {
        label: "Low Stock",
        value: lowStockCount.value ?? 0,
        icon: AlertTriangle,
        iconBg: "bg-soft-warning",
        iconColor: "text-warning",
    },
    {
        label: "Expired Stock",
        value: expiredCount ?? 0,
        icon: CalendarX2,
        iconBg: "bg-soft-danger",
        iconColor: "text-danger",
    },
    {
        label: "Near Expire Stock",
        value: nearExpireCount ?? 0,
        icon: CalendarClock,
        iconBg: "bg-soft-info",
        iconColor: "text-info",
    },
]);

onUpdated(() => window.feather?.replace?.());

/* ===================== Helpers ===================== */
const money = (amount, currency = "GBP") =>
    new Intl.NumberFormat("en-GB", { style: "currency", currency }).format(
        amount || 0
    );

// ===================== Form =====================
const formErrors = ref({});
const form = ref({
    id: null,
    name: "",
    // IDs for selects (backend expects *_id integers)
    category_id: null,
    subcategory_id: null,
    unit_id: null,
    supplier_id: null,

    // display-only fields in list remain names; not stored in form
    minAlert: null,
    sku: "",
    description: "",
    nutrition: { calories: 0, fat: 0, protein: 0, carbs: 0 },
    allergies: [],
    tags: [],
    imageFile: null,
    imageUrl: null,
});

const submitting = ref(false);

// ===== Submit (Create/Update) =====
const submitProduct = async () => {
    submitting.value = true;
    formErrors.value = {};

    const fd = new FormData();
    fd.append("name", (form.value.name || "").trim());

    // Category/Subcategory
    if (form.value.category_id) {
        fd.append("category_id", form.value.category_id);
    }
    // Only send subcategory_id if children exist for this category
    if (subcatOptions.value.length && form.value.subcategory_id) {
        fd.append("subcategory_id", form.value.subcategory_id);
    }

    // Unit & Supplier (IDs)
    if (form.value.unit_id) fd.append("unit_id", form.value.unit_id);
    if (form.value.minAlert !== null && form.value.minAlert !== "")
        fd.append("minAlert", form.value.minAlert);
    if (form.value.supplier_id)
        fd.append("supplier_id", form.value.supplier_id);

    fd.append("sku", form.value.sku || "");
    fd.append("description", form.value.description || "");

    // Nutrition
    fd.append("nutrition[calories]", form.value.nutrition.calories);
    fd.append("nutrition[fat]", form.value.nutrition.fat);
    fd.append("nutrition[protein]", form.value.nutrition.protein);
    fd.append("nutrition[carbs]", form.value.nutrition.carbs);

    // Allergies/Tags
    (form.value.allergies || []).forEach((id, i) =>
        fd.append(`allergies[${i}]`, id)
    );
    (form.value.tags || []).forEach((id, i) => fd.append(`tags[${i}]`, id));

    // Image
    if (form.value.imageFile) {
        const ext = (
            form.value.imageFile.type?.split("/")?.[1] || "webp"
        ).replace("jpeg", "jpg");
        fd.append("image", form.value.imageFile, `image.${ext}`);
    }

    try {
        if (form.value.id) {
            await axios.post(`/inventory/${form.value.id}?_method=PUT`, fd, {
                headers: { "Content-Type": "multipart/form-data" },
            });
            toast.success(" Inventory Item updated successfully");
        } else {
            await axios.post("/inventory", fd, {
                headers: { "Content-Type": "multipart/form-data" },
            });
            toast.success(" Inventory Item created successfully");
        }

        resetForm();
        await fetchInventories();
        const modalEl = document.getElementById("addItemModal");
        bootstrap.Modal.getInstance(modalEl)?.hide();
    } catch (err) {
        if (err?.response?.status === 422 && err.response.data?.errors) {
            formErrors.value = err.response.data.errors;

            toast.error("Please fill in all required fields correctly.");
        } else {
            console.error(
                "❌ Error saving:",
                err.response?.data || err.message
            );
            toast.error("Failed to save Inventory Item");
        }
    } finally {
        submitting.value = false;
    }
};

// ===== Clear validation errors when user fixes fields =====
watch(
    form,
    (newVal) => {
        Object.keys(formErrors.value).forEach((key) => {
            if (key.includes(".")) {
                const [parent, child] = key.split(".");
                if (newVal[parent] && newVal[parent][child] !== "") {
                    delete formErrors.value[key];
                }
            } else {
                const value = newVal[key];
                if (
                    value !== null &&
                    value !== undefined &&
                    value !== "" &&
                    (!Array.isArray(value) || value.length > 0)
                ) {
                    delete formErrors.value[key];
                }
            }
        });
    },
    { deep: true }
);

// =============== Edit item ===============
import { nextTick } from "vue";
import ImageZoomModal from "@/Components/ImageZoomModal.vue";
import ImportFile from "@/Components/ImportFile.vue";

const editItem = (item) => {
    const toNum = (v) =>
        v === "" || v === null || v === undefined ? null : Number(v);

    // derive category/subcategory from the category object if present
    let category_id = null;
    let subcategory_id = null;

    if (item.category && typeof item.category === "object") {
        const cat = item.category;
        if (cat.parent_id) {
            // item is saved with a SUBCATEGORY
            subcategory_id = toNum(cat.id);
            category_id = toNum(cat.parent_id);
        } else {
            // item is saved with a PARENT category only
            category_id = toNum(cat.id);
            subcategory_id = null;
        }
    } else {
        // fallback if your payload sometimes sends raw ids
        category_id = toNum(item.category_id);
        subcategory_id = toNum(item.subcategory_id);
    }

    form.value = {
        id: item.id,
        name: item.name ?? "",
        category_id,
        subcategory_id,
        unit_id: toNum(item.unit_id),
        supplier_id: toNum(item.supplier_id),
        minAlert: item.minAlert ?? 0,
        sku: item.sku ?? "",
        description: item.description ?? "",
        nutrition: item.nutrition ?? {
            calories: 0,
            protein: 0,
            fat: 0,
            carbs: 0,
        },
        allergies: item.allergy_ids ?? [],
        tags: item.tag_ids ?? [],
        imageFile: null,
        imageUrl: item.image_url ?? null,
    };

    // ensure subcategory still belongs to the selected category after options render
    nextTick(() => {
        const subOk = subcatOptions.value.some(
            (sc) => sc.id === form.value.subcategory_id
        );
        if (!subOk) form.value.subcategory_id = null;
    });
};

// =============== Reset form ===============
function resetForm() {
    form.value = {
        id: null,
        name: "",
        category_id: null,
        subcategory_id: null,
        unit_id: null,
        minAlert: "",
        supplier_id: null,
        sku: "",
        description: "",
        nutrition: { calories: "", fat: "", protein: "", carbs: "" },
        allergies: [],
        tags: [],
        imageFile: null,
        imageUrl: null,
    };
}

// ===================== View item =====================
const viewItemRef = ref({});
const ViewItem = async (row) => {
    try {
        const res = await axios.get(`/inventory/${row.id}`);
        viewItemRef.value = res.data;
        await loadStockins(row.id);
    } catch (error) {
        console.error("Error fetching item:", error);
    }
};

// ===================== Stock Item Modal =====================
function calculateValue() {
    const qv = Number(stockForm.value.quantity || 0);
    const pv = Number(stockForm.value.price || 0);
    stockForm.value.value = qv * pv;
}

const stockForm = ref({
    product_id: null,
    name: "",
    category_id: null,
    supplier_id: null,
    available_quantity: 0,
    quantity: 0,
    price: 0,
    value: 0,
    expiry_date: "",
    description: "",
    operation_type: "purchase",
    stock_type: "stockin",
    purchase_date: new Date().toISOString().slice(0, 10),
    user_id: 1,
});



const submittingStock = ref(false);
const processStatus = ref();
function openStockModal(item) {
    resetErrors();
    const supplierObj = props.suppliers.find((s) => s.name === item.supplier);
    stockInItemCategory.value = item.category.name;
    axios.get(`/stock_entries/total/${item.id}`).then((res) => {
        const totalStock = res.data.total?.original || {};
        stockForm.value = {
            product_id: item.id,
            name: item.name,
            category_id: item.category.id,
            supplier_id: supplierObj ? supplierObj.id : null,
            available_quantity: totalStock.available || 0,
            quantity: 0,
            price: 0,
            value: 0,
            expiry_date: "",
            description: "",
            operation_type: "inventory_stockin",
            stock_type: "stockin",
            purchase_date: new Date().toISOString().slice(0, 10),
            user_id: 1,
        };
        console.log("Stock In form:", stockForm.value);
    });
}

function resetStockForm() {
    stockForm.value.quantity = 0;
    stockForm.value.price = 0;
    stockForm.value.value = 0;
    stockForm.value.expiry_date = "";
    stockForm.value.description = "";

}
const resetErrors = () => {
    formErrors.value = {}
}

async function submitStockIn() {
    console.log(stockForm.value);
    submittingStock.value = true;
    calculateValue();
    try {
        await axios.post("/stock_entries", stockForm.value);
        toast.success(" Stock In saved successfully");
        resetStockForm();
        bootstrap.Modal.getInstance(
            document.getElementById("stockInModal")
        )?.hide();
        await fetchInventories();
        await fetchStockForCounting();
    } catch (err) {
        if (err?.response?.status === 422 && err.response.data?.errors) {
            formErrors.value = err.response.data.errors;

            toast.error("Please fill in all required fields correctly.");
        } else {
            // toast.dismiss();
            toast.error("Something went wrong. Please try again.", {
                autoClose: 3000,
            });
            console.error(err);
        }

    } finally {
        submittingStock.value = false;
    }
}

const stockOutItemCategory = ref(null);
const stockInItemCategory = ref(null);
// =========================== Stockout Modal ===========================
function openStockOutModal(item) {
    resetErrors();
    stockOutItemCategory.value = item.category.name;
    axios.get(`/stock_entries/total/${item.id}`).then((res) => {
        const totalStock = res.data.total?.original || {};
        stockForm.value = {
            product_id: item.id,
            name: item.name,
            category_id: item.category.id,
            available_quantity: totalStock.available || 0,
            quantity: 0,
            price: 0,
            value: 0,
            description: "",
            operation_type: "inventory_stockout",
            stock_type: "stockout",
            purchase_date: new Date().toISOString().slice(0, 10),
            user_id: 1,
        };

    });
}

async function submitStockOut() {
    submittingStock.value = true;
    calculateValue();


    if (stockForm.value.quantity > stockForm.value.available_quantity) {
        formErrors.value.quantity = [
            "The quantity should not exceed the available quantity.",
        ];
        toast.error("The quantity should not exceed the available quantity.")
        submittingStock.value = false;
        return; // stop submission
    } else {
        formErrors.value.quantity = null; // clear error if valid
    }

    try {
        await axios.post("/stock_entries", stockForm.value);
        toast.success("Stock Out saved successfully");
        resetStockForm();
        bootstrap.Modal.getInstance(
            document.getElementById("stockOutModal")
        )?.hide();
        await fetchInventories();
        await fetchStockForCounting();
    } catch (err) {
        if (err?.response?.status === 422 && err.response.data?.errors) {
            formErrors.value = err.response.data.errors;
            toast.error("Please fill in all required fields correctly.");
        } else {
            toast.error("Something went wrong. Please try again.", {
                autoClose: 3000,
            });
            console.error(err);
        }
    } finally {
        submittingStock.value = false;
    }
}


// ===================== Downloads (PDF, Excel, CSV) =====================
const onDownload = (type) => {
    if (!inventories.value || inventories.value.length === 0) {
        toast.error("No Allergies data to download");
        return;
    }
    const dataToExport = q.value.trim()
        ? filteredItems.value
        : inventories.value;
    if (!dataToExport.length) {
        toast.error("No Inventory Item found to download");
        return;
    }
    try {
        if (type === "pdf") downloadPDF(dataToExport);
        else if (type === "excel") downloadExcel(dataToExport);
        else if (type === "csv") downloadCSV(dataToExport);
        else toast.error("Invalid download type");
    } catch (error) {
        console.error("Download failed:", error);
        toast.error(`Download failed: ${error.message}`);
    }
};

const downloadCSV = (data) => {
    try {
        const headers = [
            "name",
            "sku",
            "category",
            "min_alert",
            "unit",
            "preferred_supplier",
            "purchase_price",
            "sale_price",
            "stock",
            "active",
            "calories",
            "fat",
            "protein",
            "carbs"
        ];
        const rows = data.map((s) => {

            let nutritionStr = "";
            if (s.nutrition && typeof s.nutrition === "object") {
                nutritionStr = Object.entries(s.nutrition)
                    .map(([k, v]) => `${k}: ${v}`)
                    .join("; ");
            } else if (typeof s.nutrition === "string") {
                nutritionStr = s.nutrition;
            }
            return [
                `"${s.name || ""}"`,
                `"${s.sku || ""}"`,
                `"${s.category.name || ""}"`,
                `"${s.minAlert || ""}"`,
                `"${s.unit_id || ""}"`,
                `"${s.supplier_id || ""}"`,
                `"${s.description || ""}"`,
                `"${s.description || ""}"`,
                `"${s.description || ""}"`,
                `"${s.description || ""}"`,
                `"${s.nutrition.calories}"`,
                `"${s.nutrition.protein}"`,
                `"${s.nutrition.fat}"`,
                `"${s.nutrition.carbs}"`,
                `"${Array.isArray(s.allergies)
                    ? s.allergies.join(", ")
                    : s.allergies || ""
                }"`,
                `"${Array.isArray(s.tags) ? s.tags.join(", ") : s.tags || ""}"`,
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
            `inventory_items_${new Date().toISOString().split("T")[0]}.csv`
        );
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        toast.success("CSV downloaded successfully ", { autoClose: 2500 });
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
        doc.setFontSize(20);
        doc.setFont("helvetica", "bold");
        doc.text("Inventory Item Report", 70, 20);
        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        const currentDate = new Date().toLocaleString();
        doc.text(`Generated on: ${currentDate}`, 70, 28);
        doc.text(`Total Inventory Items: ${data.length}`, 70, 34);

        const tableColumns = [
            "Item Name",
            "Category",
            "Min Alert",
            "Unit",
            "Supplier",
            "SKU",
            "Description",
            "Nutrition",
            "Allergies",
            "Tags",
        ];

        const formatNutrition = (nutri) => {
            if (!nutri) return "";
            if (typeof nutri === "string") {
                try {
                    nutri = JSON.parse(nutri);
                } catch {
                    return nutri;
                }
            }
            if (Array.isArray(nutri)) return nutri.join(", ");
            if (typeof nutri === "object") {
                return Object.entries(nutri)
                    .map(
                        ([k, v]) =>
                            `${k
                                .replace(/[_-]/g, " ")
                                .replace(/\b\w/g, (c) =>
                                    c.toUpperCase()
                                )}: ${v}`
                    )
                    .join(", ");
            }
            return String(nutri ?? "");
        };

        const tableRows = data.map((s) => [
            s.name || "",
            s.category.name || "",
            s.minAlert ?? "",
            s.unit || "",
            s.supplier || "",
            s.sku || "",
            s.description || "",
            s.nutrition_text || formatNutrition(s.nutrition),
            Array.isArray(s.allergies)
                ? s.allergies.join(", ")
                : s.allergies || "",
            Array.isArray(s.tags) ? s.tags.join(", ") : s.tags || "",
        ]);

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
            didDrawPage: (td) => {
                const pageCount = doc.internal.getNumberOfPages();
                const pageHeight = doc.internal.pageSize.height;
                doc.setFontSize(8);
                doc.text(
                    `Page ${td.pageNumber} of ${pageCount}`,
                    td.settings.margin.left,
                    pageHeight - 10
                );
            },
        });

        const fileName = `Inventory_items_${new Date().toISOString().split("T")[0]
            }.pdf`;
        doc.save(fileName);
        toast.success("PDF downloaded successfully ", { autoClose: 2500 });
    } catch (error) {
        console.error("PDF generation error:", error);
        toast.error(`PDF generation failed: ${error.message}`, {
            autoClose: 5000,
        });
    }
};

const downloadExcel = (data) => {
    try {
        if (typeof XLSX === "undefined")
            throw new Error("XLSX library is not loaded");
        const worksheetData = data.map((s) => {
            let nutritionStr = "";
            if (s.nutrition && typeof s.nutrition === "object") {
                nutritionStr = Object.entries(s.nutrition)
                    .map(([k, v]) => `${k}: ${v}`)
                    .join("; ");
            } else if (typeof s.nutrition === "string") {
                nutritionStr = s.nutrition;
            }
            return {
                "Item Name": s.name || "",
                Category: s.category || "",
                "Min Alert": s.minAlert || "",
                Unit: s.unit || "",
                Supplier: s.supplier || "",
                Sku: s.sku || "",
                Description: s.description || "",
                Nutrition: nutritionStr,
                Allergies: Array.isArray(s.allergies)
                    ? s.allergies.join(", ")
                    : s.allergies || "",
                Tags: Array.isArray(s.tags) ? s.tags.join(", ") : s.tags || "",
                "Created At": s.created_at || "",
                "Updated At": s.updated_at || "",
            };
        });

        const workbook = XLSX.utils.book_new();
        const worksheet = XLSX.utils.json_to_sheet(worksheetData);
        worksheet["!cols"] = [
            { wch: 20 },
            { wch: 15 },
            { wch: 10 },
            { wch: 12 },
            { wch: 20 },
            { wch: 15 },
            { wch: 30 },
            { wch: 40 },
            { wch: 25 },
            { wch: 25 },
            { wch: 20 },
            { wch: 20 },
        ];
        XLSX.utils.book_append_sheet(workbook, worksheet, "Inventory Items");

        const metaData = [
            { Info: "Generated On", Value: new Date().toLocaleString() },
            { Info: "Total Records", Value: data.length },
            { Info: "Exported By", Value: "Inventory Management System" },
        ];
        const metaSheet = XLSX.utils.json_to_sheet(metaData);
        XLSX.utils.book_append_sheet(workbook, metaSheet, "Report Info");

        const fileName = `inventory_items_${new Date().toISOString().split("T")[0]
            }.xlsx`;
        XLSX.writeFile(workbook, fileName);
        toast.success("Excel file downloaded successfully ", {
            autoClose: 2500,
        });
    } catch (error) {
        console.error("Excel generation error:", error);
        toast.error(`Excel generation failed: ${error.message}`, {
            autoClose: 5000,
        });
    }
};

// ===================== Image/Cropper stubs (design unchanged) =====================
const showCropper = ref(false);
const showImageModal = ref(false);
const previewImage = ref(null);
function openImageModal(src) {
    previewImage.value = src || form.value.imageUrl;
    if (!previewImage.value) return;
    showImageModal.value = true;
}
function onCropped({ file }) {
    form.value.imageFile = file;
    form.value.imageUrl = URL.createObjectURL(file);
}

const search = ref("");

// new flow for stock in/out
const nearExpireRows = ref(0);
const expiredRows = ref(0);
const nearExpireQty = ref(0);
const expiredQty = ref(0);
// fetch stock-ins for an item and compute counts
const stockedInItems = ref([]);
async function loadStockins(itemId) {
    try {
        const { data } = await axios.get(`/stock_entries/by-item/${itemId}`);
        const payload = data?.data || {};
        console.log(payload);

        stockedInItems.value = payload.records || [];

        nearExpireRows.value = payload.near_count ?? 0;
        expiredRows.value = payload.expired_count ?? 0;
        nearExpireQty.value = payload.near_qty ?? 0;
        expiredQty.value = payload.expired_qty ?? 0;
    } catch (e) {
        console.error(e);
        stockedInItems.value = [];
        nearExpireRows.value = expiredRows.value = 0;
        nearExpireQty.value = expiredQty.value = 0;
    }
}
console.log('stockedInItems', stockedInItems.value);
// helpers for badges
function stockStatusLabel(s) {
    return s === "expired"
        ? "Expired"
        : s === "near"
            ? "Near Expire"
            : "Active";
}
function stockStatusClass(s) {
    return s === "expired"
        ? "bg-danger"
        : s === "near"
            ? "bg-warning"
            : "bg-success";
}
function fmtDate(d) {
    return d ? new Date(d).toLocaleDateString() : "—";
}
// function money(n)        { return (Number(n ?? 0)).toFixed(2); }

// totals for the stock-in table footer
const totals = computed(() => {
    const rows = stockedInItems.value || [];
    let totalQty = 0,
        totalPrice = 0,
        totalValue = 0,
        notExpiredQty = 0; // 👈 new

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    for (const r of rows) {
        const qty = r?.quantity ?? 0;

        totalQty += qty;
        totalPrice += Number(r?.price ?? 0);
        totalValue += Number(r?.value ?? 0);

        // only add qty if not expired
        if (!r?.expiry_date) {
            notExpiredQty += qty;
        } else {
            const expiry = new Date(r.expiry_date);
            expiry.setHours(0, 0, 0, 0);
            if (expiry >= today) {
                notExpiredQty += qty;
            }
        }
    }

    return { totalQty, totalPrice, totalValue, notExpiredQty };
});


// handle import function for items
const handleImport = (data) => {
    console.log("Imported Data:", data);

    const headers = data[0];
    // CSV headers: ["name","sku","category","purchase_price","sale_price","stock","active","calories","fat","protein","carbs"]
    const rows = data.slice(1);

    const itemsToImport = rows.map((row) => {
        return {
            name: row[0] || "",
            sku: row[1] || "",
            category: row[2] || "",
            min_alert: row[3] || "",
            unit: row[4] || "",
            preferred_supplier: row[5] || "",
            purchase_price: parseFloat(row[6]) || 0,
            sale_price: parseFloat(row[7]) || 0,
            stock: parseInt(row[8]) || 0,
            active: row[9] == "0" ? 0 : 1,

            calories: parseFloat(row[10]) || 0,
            fat: parseFloat(row[11]) || 0,
            protein: parseFloat(row[12]) || 0,
            carbs: parseFloat(row[13]) || 0,
        };
    });

    axios
        .post("/items/import", { items: itemsToImport })
        .then(() => {
            toast.success("Items imported successfully");
            fetchInventories();
        })
        .catch((err) => {
            console.error(err);
            toast.error("Import failed");
        });
};


</script>

<template>
    <Master>
        <div class="page-wrapper">
            <div class="container-fluid py-3">
                <!-- Title -->
                <h4 class="fw-semibold mb-3">Overall Inventory</h4>

                <!-- KPI Cards -->
                <div class="row g-3">
                    <div v-for="c in kpis" :key="c.label" class="col">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="fw-bold fs-4">
                                        {{ c.value }}
                                    </div>
                                    <div class="text-muted fs-6">
                                        {{ c.label }}
                                    </div>
                                </div>
                                <div :class="['icon-chip', c.iconBg]">
                                    <component :is="c.icon" :class="c.iconColor" size="26" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stock Table -->
                <div class="card border-0 shadow-lg rounded-4 mt-3">
                    <div class="card-body">
                        <!-- Toolbar -->
                        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                            <h5 class="mb-0 fw-semibold">Stock</h5>

                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <div class="search-wrap">
                                    <i class="bi bi-search"></i>
                                    <input v-model="q" type="text" class="form-control search-input"
                                        placeholder="Search" />
                                </div>

                                <!-- Filter By -->
                                <!-- Replace your existing filter dropdown with this: -->

                                <FilterModal v-model="filters" title="Inventory Items" modal-id="inventoryFilterModal"
                                    modal-size="modal-lg" :categories="filterOptions.categories"
                                    :suppliers="filterOptions.suppliers" :sort-options="filterOptions.sortOptions"
                                    :stock-status-options="filterOptions.stockStatusOptions" :show-price-range="true"
                                    :show-date-range="false" @apply="handleFilterApply" @clear="handleFilterClear">
                                    <!-- Custom filters slot (optional) -->
                                    <template #customFilters="{ filters }">
                                        <!-- Add any custom filter controls here if needed -->
                                        <div class="col-12">
                                            <label class="form-label fw-semibold text-dark">
                                                <i class="fas fa-star me-2 text-muted"></i>Custom Filter
                                            </label>
                                            <!-- Your custom filter controls -->
                                        </div>
                                    </template>
                                </FilterModal>



                                <!-- Add Item -->
                                <button data-bs-toggle="modal" data-bs-target="#addItemModal"
                                    class="d-flex align-items-center gap-1 px-4 py-2 rounded-pill btn btn-primary text-white"
                                    @click="
                                        resetForm();
                                    formErrors = {};
                                    processStatus = 'Create';
                                    ">
                                    <Plus class="w-4 h-4" /> Add Item
                                </button>
                                <ImportFile label="Import" @on-import="handleImport" />

                                <!-- Download all -->
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary rounded-pill px-4 dropdown-toggle"
                                        data-bs-toggle="dropdown">
                                        Download
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow rounded-4 py-2">
                                        <li>
                                            <a class="dropdown-item py-2" href="javascript:;"
                                                @click="onDownload('pdf')">Download as PDF</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item py-2" href="javascript:;"
                                                @click="onDownload('excel')">Download as Excel</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item py-2" href="javascript:;"
                                                @click="onDownload('csv')">Download as CSV</a>
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
                                        <th>Items</th>
                                        <th>Image</th>

                                        <th>Category</th>
                                        <th>Unit</th>
                                        <th>Available Stock</th>
                                        <th>Stock Value</th>
                                        <th class="text-center">
                                            Availability
                                        </th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>

                                <!-- Loading row -->
                                <tbody v-if="loading">
                                    <tr>
                                        <td colspan="10" class="text-center py-5">
                                            <div class="spinner-border" role="status" aria-label="Loading"></div>
                                        </td>
                                    </tr>
                                </tbody>

                                <!-- Your existing rows -->
                                <tbody v-else>
                                    <tr v-for="(item, idx) in sortedItems" :key="item.id">
                                        <td>{{ idx + 1 }}</td>
                                        <td class="fw-semibold">
                                            {{ item.name }}
                                        </td>
                                        <td>
                                            <ImageZoomModal v-if="item.image_url" :file="item.image_url"
                                                :alt="item.name" :width="50" :height="50"
                                                :custom_class="'cursor-pointer'" />
                                            <!-- <img
                                                :src="item.image_url"
                                                alt=""
                                                style="
                                                    width: 50px;
                                                    height: 50px;
                                                    object-fit: cover;
                                                    border-radius: 6px;
                                                "
                                            /> -->
                                        </td>

                                        <td class="text-truncate" style="max-width: 260px">
                                            {{ item.category?.name }}
                                        </td>
                                        <td>{{ item.unit_name }}</td>
                                        <td>
                                            {{
                                                item.availableStock
                                                    ? item.availableStock.toFixed(
                                                        1
                                                    )
                                                    : 0
                                            }}
                                        </td>
                                        <td>
                                            {{
                                                money(
                                                    item.stockValue || 0,
                                                    "GBP"
                                                )
                                            }}
                                        </td>
                                        <td class="text-center">
                                            <span v-if="item.availableStock === 0"
                                                class="badge bg-red-600 rounded-pill d-inline-block text-center"
                                                style="min-width: 100px">Out of
                                                stock</span>
                                            <span v-else-if="
                                                item.availableStock <=
                                                item.minAlert
                                            " class="badge bg-warning rounded-pill d-inline-block text-center"
                                                style="min-width: 100px">Low-stock</span>
                                            <span v-else
                                                class="badge bg-success rounded-pill d-inline-block text-center"
                                                style="min-width: 100px">In-stock</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-inline-flex align-items-center gap-3">
                                                <button @click="
                                                    openStockModal(item)
                                                    " data-bs-toggle="modal" data-bs-target="#stockInModal"
                                                    title="Stock In"
                                                    class="p-2 rounded-full text-green-600 hover:bg-green-100">
                                                    <Download class="w-4 h-4" />
                                                </button>
                                                <button @click="
                                                    openStockOutModal(item)
                                                    " data-bs-toggle="modal" data-bs-target="#stockOutModal"
                                                    title="Stock Out"
                                                    class="p-2 rounded-full text-red-600 hover:bg-red-100">
                                                    <Upload class="w-4 h-4" />
                                                </button>
                                                <button @click="ViewItem(item)" data-bs-toggle="modal"
                                                    data-bs-target="#viewItemModal" title="View Item"
                                                    class="p-2 rounded-full text-gray-600 hover:bg-gray-100">
                                                    <Eye class="w-4 h-4" />
                                                </button>
                                                <button @click="
                                                    () => {
                                                        editItem(item);
                                                        formErrors = {};
                                                        processStatus =
                                                            'Edit';
                                                    }
                                                " data-bs-toggle="modal" data-bs-target="#addItemModal" title="Edit"
                                                    class="p-2 rounded-full text-blue-600 hover:bg-blue-100">
                                                    <Pencil class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr v-if="sortedItems.length === 0">
                                        <td colspan="10" class="text-center text-muted py-4">
                                            No items found.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ===================== Add / Edit Product Modal ===================== -->
                <div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                        <div class="modal-content rounded-4">
                            <div class="modal-header">
                                <h5 class="modal-title fw-semibold">
                                    {{
                                        processStatus === "Edit"
                                            ? "Edit Item"
                                            : "Add New Item"
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
                                <!-- top row -->
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Product Name</label>
                                        <input v-model="form.name" type="text" class="form-control" :class="{
                                            'is-invalid': formErrors.name,
                                        }" placeholder="e.g., Chicken Breast" />
                                        <small v-if="formErrors.name" class="text-danger">{{ formErrors.name[0]
                                        }}</small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Category</label>
                                        <Select v-model="form.category_id" :options="parentCategories"
                                            optionLabel="name" optionValue="id" placeholder="Select Category"
                                            class="w-100" appendTo="self" :autoZIndex="true" :baseZIndex="2000"
                                            @update:modelValue="
                                                form.subcategory_id = null
                                                " :class="{
                                                    'is-invalid':
                                                        formErrors.category_id,
                                                }" />
                                        <small v-if="formErrors.category_id" class="text-danger">
                                            {{ formErrors.category_id[0] }}
                                        </small>
                                    </div>

                                    <!-- Subcategory (only if selected category has children) -->
                                    <div class="col-md-6" v-if="subcatOptions.length">
                                        <label class="form-label">Subcategory</label>
                                        <Select v-model="form.subcategory_id" :options="subcatOptions"
                                            optionLabel="name" optionValue="id" placeholder="Select Subcategory"
                                            class="w-100" appendTo="self" :autoZIndex="true" :baseZIndex="2000" :class="{
                                                'is-invalid':
                                                    formErrors.subcategory_id,
                                            }" />
                                        <small v-if="formErrors.subcategory_id" class="text-danger">
                                            {{ formErrors.subcategory_id[0] }}
                                        </small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label d-block">Minimum Stock Alert Level</label>
                                        <input v-model="form.minAlert" type="number" min="0" class="form-control"
                                            :class="{
                                                'is-invalid':
                                                    formErrors.minAlert,
                                            }" placeholder="e.g., 5" />
                                        <small v-if="formErrors.minAlert" class="text-danger">{{ formErrors.minAlert[0]
                                        }}</small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Unit Type</label>
                                        <Select v-model="form.unit_id" :options="props.units" optionLabel="name"
                                            optionValue="id" placeholder="Select Unit" class="w-100" appendTo="self"
                                            :autoZIndex="true" :baseZIndex="2000" :class="{
                                                'is-invalid':
                                                    formErrors.unit_id,
                                            }" />
                                        <small v-if="formErrors.unit_id" class="text-danger">{{ formErrors.unit_id[0]
                                        }}</small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Preferred Supplier</label>
                                        <Select v-model="form.supplier_id" :options="props.suppliers" optionLabel="name"
                                            optionValue="id" placeholder="Select Supplier" class="w-100" appendTo="self"
                                            :autoZIndex="true" :baseZIndex="2000" :class="{
                                                'is-invalid':
                                                    formErrors.supplier_id,
                                            }" />
                                        <small v-if="formErrors.supplier_id" class="text-danger">
                                            {{ formErrors.supplier_id[0] }}
                                        </small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">SKU (Optional)</label>
                                        <input v-model="form.sku" type="text" class="form-control" :class="{
                                            'is-invalid': formErrors.sku,
                                        }" placeholder="Stock Keeping Unit" />
                                        <small v-if="formErrors.sku" class="text-danger">{{ formErrors.sku[0] }}</small>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Description</label>
                                        <textarea v-model="form.description" rows="4" class="form-control" :class="{
                                            'is-invalid':
                                                formErrors.description,
                                        }" placeholder="Notes about this product"></textarea>
                                        <small v-if="formErrors.description" class="text-danger">{{
                                            formErrors.description[0]
                                            }}</small>
                                    </div>
                                </div>

                                <hr class="my-4" />

                                <!-- Nutrition -->
                                <h6 class="mb-3">
                                    Nutrition Information (per unit)
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Calories</label>
                                        <input v-model="form.nutrition.calories" type="number" min="0"
                                            class="form-control" :class="{
                                                'is-invalid':
                                                    formErrors[
                                                    'nutrition.calories'
                                                    ],
                                            }" />
                                        <small v-if="
                                            formErrors['nutrition.calories']
                                        " class="text-danger">
                                            {{
                                                formErrors[
                                                "nutrition.calories"
                                                ][0]
                                            }}
                                        </small>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Fat (g)</label>
                                        <input v-model="form.nutrition.fat" type="number" min="0" class="form-control"
                                            :class="{
                                                'is-invalid':
                                                    formErrors['nutrition.fat'],
                                            }" />
                                        <small v-if="formErrors['nutrition.fat']" class="text-danger">
                                            {{ formErrors["nutrition.fat"][0] }}
                                        </small>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Protein (g)</label>
                                        <input v-model="form.nutrition.protein" type="number" min="0"
                                            class="form-control" :class="{
                                                'is-invalid':
                                                    formErrors[
                                                    'nutrition.protein'
                                                    ],
                                            }" />
                                        <small v-if="
                                            formErrors['nutrition.protein']
                                        " class="text-danger">
                                            {{
                                                formErrors[
                                                "nutrition.protein"
                                                ][0]
                                            }}
                                        </small>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Carbs (g)</label>
                                        <input v-model="form.nutrition.carbs" type="number" min="0" class="form-control"
                                            :class="{
                                                'is-invalid':
                                                    formErrors[
                                                    'nutrition.carbs'
                                                    ],
                                            }" />
                                        <small v-if="formErrors['nutrition.carbs']" class="text-danger">
                                            {{
                                                formErrors["nutrition.carbs"][0]
                                            }}
                                        </small>
                                    </div>
                                </div>

                                <div class="row g-4 mt-1">
                                    <!-- Allergies -->
                                    <div class="col-md-6">
                                        <label class="form-label d-block">Allergies</label>
                                        <MultiSelect v-model="form.allergies" :options="props.allergies"
                                            optionLabel="name" optionValue="id" filter placeholder="Select Allergies"
                                            class="w-full md:w-80" appendTo="self" display="chip" :class="{
                                                'is-invalid':
                                                    formErrors.allergies,
                                            }" />
                                        <small v-if="formErrors.allergies" class="text-danger">{{
                                            formErrors.allergies[0]
                                            }}</small>
                                    </div>

                                    <!-- Tags -->
                                    <div class="col-md-6">
                                        <label class="form-label d-block">Tags (Halal, Haram, etc.)</label>
                                        <MultiSelect v-model="form.tags" :options="props.tags" optionLabel="name"
                                            optionValue="id" filter placeholder="Select Tags" class="w-full md:w-80"
                                            appendTo="self" display="chip" :class="{
                                                'is-invalid': formErrors.tags,
                                            }" />
                                        <small v-if="formErrors.tags" class="text-danger">{{ formErrors.tags[0]
                                        }}</small>
                                    </div>
                                </div>

                                <!-- Image -->
                                <div class="col-md-4">
                                    <div class="logo-card" :class="{
                                        'is-invalid': formErrors.image,
                                    }">
                                        <div class="logo-frame" @click="
                                            form.imageUrl &&
                                            openImageModal()
                                            ">
                                            <img v-if="form.imageUrl" :src="form.imageUrl" alt="Image" />
                                            <div v-else class="placeholder">
                                                <i class="bi bi-image"></i>
                                            </div>
                                        </div>

                                        <small class="text-muted mt-2 d-block">Upload Image</small>

                                        <!-- Your cropper modal (kept as-is) -->
                                        <ImageCropperModal :show="showCropper" @close="showCropper = false"
                                            @cropped="onCropped" />


                                    </div>
                                    <small v-if="formErrors.image" class="text-danger">
                                        {{ formErrors.image[0] }}
                                    </small>
                                </div>

                                <div class="mt-4">
                                    <button class="btn btn-primary rounded-pill px-5 py-2" :disabled="submitting"
                                        @click="submitProduct">
                                        <template v-if="submitting">
                                            <span class="spinner-border spinner-border-sm me-2"></span>
                                            {{ processStatus === "Edit" ? "Updating Item..." : "Adding Item..." }}
                                        </template>
                                        <template v-else>
                                            {{ processStatus === "Edit" ? "Update Item" : "Add Item" }}
                                        </template>
                                    </button>

                                    <button class="btn btn-secondary rounded-pill px-4 ms-2" data-bs-dismiss="modal"
                                        @click="resetForm">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /modal -->

                <!-- View modal -->
                <div class="modal fade" id="viewItemModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                            <div class="modal-header bg-light">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-box-seam text-primary fs-4"></i>
                                    <div>
                                        <h5 class="modal-title fw-semibold mb-0">
                                            View Inventory Item
                                        </h5>
                                        <small class="text-muted" v-if="viewItemRef?.sku">SKU: {{ viewItemRef.sku
                                        }}</small>
                                    </div>
                                </div>
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
                                <div v-if="viewItemRef" class="row g-4">
                                    <!-- Left: Details -->
                                    <div class="col-12 col-md-7">
                                        <div class="card border-0 shadow-sm rounded-4">
                                            <div class="card-body">
                                                <h5 class="fw-semibold mb-3">
                                                    {{ viewItemRef.name }}
                                                </h5>
                                                <p class="text-muted small mb-2">
                                                    SKU: {{ viewItemRef.sku }}
                                                </p>

                                                <div class="row g-3 small">
                                                    <div class="col-6">
                                                        <div class="text-muted">
                                                            Category
                                                        </div>
                                                        <div class="fw-semibold">
                                                            {{
                                                                viewItemRef.category_name ||
                                                                "—"
                                                            }}
                                                        </div>
                                                    </div>
                                                    <!-- <div class="col-6">
                                                        <div class="text-muted">
                                                            Subcategory 123
                                                        </div>
                                                        <div
                                                            class="fw-semibold"
                                                        >
                                                            {{
                                                                viewItemRef.subcategory_name ||
                                                                "—"
                                                            }}
                                                        </div>
                                                    </div> -->
                                                    <div class="col-6">
                                                        <div class="text-muted">
                                                            Unit
                                                        </div>
                                                        <div class="fw-semibold">
                                                            {{
                                                                viewItemRef.unit_name
                                                            }}
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="text-muted">
                                                            Min Alert
                                                        </div>
                                                        <div class="fw-semibold">
                                                            {{
                                                                viewItemRef.minAlert
                                                            }}
                                                        </div>
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="text-muted">
                                                            Supplier
                                                        </div>
                                                        <div class="fw-semibold">
                                                            {{
                                                                viewItemRef.supplier_name
                                                            }}
                                                        </div>
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="text-muted">
                                                            Description
                                                        </div>
                                                        <div class="fw-semibold">
                                                            {{
                                                                viewItemRef.description ||
                                                                "—"
                                                            }}
                                                        </div>
                                                    </div>
                                                </div>

                                                <hr />
                                                <h6 class="fw-semibold">
                                                    Nutrition (per 100g)
                                                </h6>
                                                <div class="d-flex flex-wrap gap-2 mt-2">
                                                    <span class="badge rounded-pill bg-primary text-white">Calories:
                                                        {{
                                                            viewItemRef
                                                                .nutrition
                                                                ?.calories ??
                                                            "—"
                                                        }}</span>
                                                    <span class="badge rounded-pill bg-success text-white">Protein:
                                                        {{
                                                            viewItemRef
                                                                .nutrition
                                                                ?.protein ?? "—"
                                                        }}</span>
                                                    <span class="badge rounded-pill bg-warning text-white">Fat:
                                                        {{
                                                            viewItemRef
                                                                .nutrition
                                                                ?.fat ?? "—"
                                                        }}</span>
                                                    <span class="badge rounded-pill bg-secondary text-white">Carbs:
                                                        {{
                                                            viewItemRef
                                                                .nutrition
                                                                ?.carbs ?? "—"
                                                        }}</span>
                                                </div>

                                                <hr />
                                                <h6 class="fw-semibold">
                                                    Tags
                                                </h6>
                                                <div class="d-flex flex-wrap gap-2 mt-2">
                                                    <span v-for="tag in viewItemRef.tags" :key="tag"
                                                        class="badge rounded-pill bg-info text-white">
                                                        {{ tag }}
                                                    </span>
                                                </div>

                                                <hr />
                                                <h6 class="fw-semibold">
                                                    Allergies
                                                </h6>
                                                <div class="d-flex flex-wrap gap-2 mt-2">
                                                    <span v-for="allergy in viewItemRef.allergies" :key="allergy"
                                                        class="badge rounded-pill bg-danger text-white">
                                                        {{ allergy }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right: Image + Meta -->
                                    <div class="col-12 col-md-5">
                                        <div class="card border-0 shadow-sm rounded-4 h-100">
                                            <div
                                                class="card-body d-flex flex-column align-items-center justify-content-center">
                                                <div v-if="viewItemRef.image_url" class="w-100">
                                                    <img :src="viewItemRef.image_url
                                                        " alt="Item Image" class="w-100 rounded-3" style=" 
                                                            object-fit: cover;
                                                        " />
                                                </div>
                                            </div>


                                            <div
                                                class="card-footer bg-transparent small d-flex justify-content-between">
                                                <span class="text-muted">Stocked In</span>
                                                <span class="  badge bg-gray-500 rounded-pill text-white p-2">{{
                                                    totals.notExpiredQty
                                                }}</span>
                                            </div>

                                            <div
                                                class="card-footer bg-transparent small d-flex justify-content-between">
                                                <span class="text-muted">Updated On</span>
                                                <span class="fw-semibold">{{
                                                    viewItemRef.updated_at
                                                }}</span>
                                            </div>

                                            <!-- <div
                                                class="card-footer bg-transparent small d-flex justify-content-between"
                                            >
                                                <span class="text-muted"
                                                    >Stock</span
                                                >
                                                <span
                                                    :class="[
                                                        'fw-semibold',
                                                        (viewItemRef.stock ??
                                                            0) > 0
                                                            ? 'text-success'
                                                            : 'text-danger',
                                                    ]"
                                                >
                                                    {{ viewItemRef.stock ?? 0 }}
                                                </span>
                                                <span
                                                    v-if="
                                                        (viewItemRef.stock ??
                                                            0) === 0
                                                    "
                                                    class="badge bg-red-600 rounded-pill"
                                                    >Out of stock</span
                                                >
                                                <span
                                                    v-else-if="
                                                        (viewItemRef.stock ??
                                                            0) <=
                                                        (viewItemRef.minAlert ??
                                                            0)
                                                    "
                                                    class="badge bg-warning rounded-pill"
                                                    >Low-stock</span
                                                >
                                                <span
                                                    v-else
                                                    class="badge bg-success rounded-pill"
                                                    >In-stock</span
                                                >
                                            </div> -->

                                            <div
                                                class="card-footer bg-transparent small d-flex justify-content-between">
                                                <span class="text-muted">Added By</span>
                                                <span class="fw-semibold">{{
                                                    viewItemRef.user
                                                }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">Stock Details</h5>


                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-striped mb-0">
                                            <thead class="table-primary">
                                                <tr>
                                                    <th style="width: 80px">
                                                        S.#
                                                    </th>
                                                    <th>Qty</th>
                                                    <th>Price</th>
                                                    <th>Value</th>
                                                    <th>Description</th>
                                                    <th>Purchase Date</th>
                                                    <th>Expiry Date</th>
                                                    <th class="text-center">
                                                        Status
                                                    </th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <tr v-for="(row, i) in stockedInItems" :key="row.id">
                                                    <td class="fw-semibold">
                                                        {{ i + 1 }}
                                                    </td>
                                                    <td>{{ row.quantity }}</td>
                                                    <td>
                                                        {{ money(row.price) }}
                                                    </td>
                                                    <td class="fw-semibold">
                                                        {{ money(row.value) }}
                                                    </td>
                                                    <td class="text-muted">
                                                        {{
                                                            row.description ||
                                                            "—"
                                                        }}
                                                    </td>
                                                    <td class="text-muted">
                                                        {{
                                                            fmtDate(
                                                                row.purchase_date
                                                            )
                                                        }}
                                                    </td>
                                                    <td class="text-muted">
                                                        {{
                                                            fmtDate(
                                                                row.expiry_date
                                                            )
                                                        }}
                                                    </td>
                                                    <td class="text-center">
                                                        <span style="min-width: 100px" :class="[
                                                            'badge rounded-pill d-inline-block text-center',
                                                            stockStatusClass(
                                                                row.status
                                                            ),
                                                        ]">
                                                            {{
                                                                stockStatusLabel(
                                                                    row.status
                                                                )
                                                            }}
                                                        </span>
                                                    </td>
                                                </tr>

                                                <tr v-if="
                                                    !stockedInItems.length
                                                ">
                                                    <td colspan="9" class="text-center text-muted py-4">
                                                        No items found.
                                                    </td>
                                                </tr>
                                            </tbody>

                                            <!-- FOOTER TOTALS -->
                                            <tfoot>
                                                <tr>
                                                    <th>Totals</th>
                                                    <th>
                                                        {{ totals.totalQty }}
                                                    </th>
                                                    <th>
                                                        {{
                                                            money(
                                                                totals.totalPrice
                                                            )
                                                        }}
                                                    </th>
                                                    <th class="fw-semibold">
                                                        {{
                                                            money(
                                                                totals.totalValue
                                                            )
                                                        }}
                                                    </th>
                                                    <th colspan="4"></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stockin Modal -->
                <div class="modal fade" id="stockInModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content rounded-4">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    Stock In: {{ stockForm.name }}
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
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Product Name</label>
                                        <input type="text" v-model="stockForm.name" class="form-control"
                                            :class="{ 'is-invalid': formErrors.name }" readonly />

                                        <small v-if="formErrors.name" class="text-danger">
                                            {{ formErrors.name[0] }}
                                        </small>

                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Category</label>
                                        <!-- input readonly jsut show {{props.categories[0].id}}  -->

                                        <!-- Display Category Name -->
                                        <!-- Show category name -->
                                        <input type="text" :value="stockInItemCategory" class="form-control" readonly />





                                        <!-- <Select
                                            v-model="stockForm.category_id"
                                            :options="props.categories"
                                            optionLabel="name"
                                            optionValue="id"
                                            placeholder="Select Category"
                                            class="w-100"
                                            appendTo="self"
                                            :class="{ 'is-invalid': formErrors.category_id }"
                                        /> -->

                                        <small v-if="formErrors.category_id" class="text-danger">
                                            {{ formErrors.category_id[0] }}
                                        </small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Supplier</label>
                                        <Select v-model="stockForm.supplier_id" :options="props.suppliers"
                                            optionLabel="name" optionValue="id" placeholder="Select Supplier"
                                            class="w-100" appendTo="self"
                                            :class="{ 'is-invalid': formErrors.supplier_id }" />
                                        <small v-if="formErrors.supplier_id" class="text-danger">
                                            {{ formErrors.supplier_id[0] }}
                                        </small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Available Quantity</label>
                                        <input type="number" v-model="stockForm.available_quantity
                                            " class="form-control" readonly />
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Quantity</label>
                                        <input type="number" v-model="stockForm.quantity" class="form-control" min="0"
                                            @input="calculateValue()" :class="{ 'is-invalid': formErrors.quantity }" />

                                        <small v-if="formErrors.quantity" class="text-danger">
                                            {{ formErrors.quantity[0] }}
                                        </small>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Price</label>
                                        <input type="number" v-model="stockForm.price" class="form-control" min="0"
                                            @input="calculateValue()" :class="{ 'is-invalid': formErrors.price }" />
                                        <small v-if="formErrors.price" class="text-danger">
                                            {{ formErrors.price[0] }}
                                        </small>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Value</label>
                                        <input type="text" v-model="stockForm.value" class="form-control" readonly />
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label">Expiry Date</label>
                                        
                                        <VueDatePicker 
                                            v-model="stockForm.expiry_date"
                                            :class="{ 'is-invalid': formErrors.expiry_date }"
                                            :enableTimePicker="false"
                                            placeholder="Select date" />
                                        <small v-if="formErrors.expiry_date" class="text-danger">
                                            {{ formErrors.expiry_date[0] }}
                                        </small>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label">Notes / Reason</label>
                                        <textarea v-model="stockForm.description" rows="3"
                                            class="form-control"></textarea>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <button
                                        class="d-flex align-items-center gap-1 px-4 py-2 rounded-pill btn btn-primary text-white"
                                        :disabled="submittingStock" @click="submitStockIn">
                                        Stock In
                                    </button>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stockout Modal -->
                <div class="modal fade" id="stockOutModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content rounded-4">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    Stock Out : {{ stockForm.name }}
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
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Product Name</label>
                                        <input type="text" v-model="stockForm.name" class="form-control" readonly />
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Category</label>
                                        <!-- {{ stockForm }} -->

                                        <input type="text" :value="stockOutItemCategory" class="form-control"
                                            readonly />


                                        <small v-if="formErrors.category_id" class="text-danger">
                                            {{ formErrors.category_id[0] }}
                                        </small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Available Quantity</label>
                                        <input type="number" v-model="stockForm.available_quantity
                                            " class="form-control" readonly />
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Quantity</label>
                                        <input type="number" v-model="stockForm.quantity" class="form-control" min="0"
                                            @input="calculateValue()" :class="{ 'is-invalid': formErrors.quantity }" />

                                        <small v-if="formErrors.quantity" class="text-danger">
                                            {{ formErrors.quantity[0] }}
                                        </small>
                                    </div>

                                    <!-- <div class="col-md-6">
                                        <label class="form-label">Price</label>
                                        <input
                                            type="number"
                                            v-model="stockForm.price"
                                            class="form-control"
                                            min="0"
                                            :class="{ 'is-invalid': formErrors.price }"
                                            @input="calculateValue()"
                                        />
                                        <small v-if="formErrors.price" class="text-danger">
                                {{ formErrors.price[0] }}
                            </small>
                                    </div> -->

                                    <!-- <div class="col-md-6">
                                        <label class="form-label">Value</label>
                                        <input
                                            type="text"
                                            v-model="stockForm.value"
                                            class="form-control"
                                            readonly
                                        />
                                    </div> -->

                                    <div class="col-md-12">
                                        <label class="form-label">Notes / Reason</label>
                                        <textarea v-model="stockForm.description" rows="3"
                                            class="form-control"></textarea>
                                    </div>
                                </div>

                                <div class="mt-4 text-end">
                                    <button
                                        class="d-flex align-items-center gap-1 px-4 py-2 rounded-pill btn btn-danger text-white"
                                        :disabled="submittingStock" @click="submitStockOut">
                                        Stock Out
                                    </button>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /container -->
        </div>
        <!-- /page-wrapper -->
    </Master>
</template>

<style scoped>
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
    font-size: 1rem;
}

.search-input {
    padding-left: 38px;
    border-radius: 9999px;
    background: #fff;
}

/* Buttons theme */
.btn-primary {
    background-color: var(--brand);
    border-color: var(--brand);
}

.btn-primary:hover {
    filter: brightness(1.05);
}

/* Action menu */
.action-menu {
    min-width: 220px;
}

.action-menu .dropdown-item {
    font-size: 1rem;
}

/* Table polish */
.table thead th {
    font-weight: 600;
}

.table tbody td {
    vertical-align: middle;
}

/* Image drop */
.img-drop {
    height: 160px;
    border: 2px dashed #cbd5e1;
    background: #f8fafc;
}

/* Optional hint styling */
.dropdown-toggle .small {
    opacity: 0.85;
}

.modal-body .row:not(:last-child) {
    border-bottom: 1px solid #f0f0f0;
    padding-bottom: 1rem;
    margin-bottom: 1rem;
}

.badge {
    font-size: 0.75rem;
    padding: 0.4em 0.6em;
}

.form-label.text-muted {
    font-size: 0.8rem;
    margin-bottom: 0.25rem;
    font-weight: 500;
}

.fw-semibold {
    font-size: 0.95rem;
    color: #333;
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


/* Mobile tweaks */
@media (max-width: 575.98px) {
    .kpi-value {
        font-size: 1.45rem;
    }

    .search-wrap {
        width: 100%;
    }
}

.image-cropper-modal {
    z-index: 2001 !important;
}

.image-cropper-backdrop {
    z-index: 2000 !important;
}
</style>
