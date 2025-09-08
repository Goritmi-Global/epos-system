<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted, onUpdated, watch } from "vue";
import Select from "primevue/select";
import MultiSelect from "primevue/multiselect";
import { toast } from "vue3-toastify";
import { jsPDF } from "jspdf";
import autoTable from "jspdf-autotable";
import * as XLSX from "xlsx";
import {
    Package,
    XCircle,
    AlertTriangle,
    CalendarX2,
    CalendarClock,
    Plus,
    Menu,
    Pencil
} from "lucide-vue-next";

const props = defineProps({
    allergies: {
        type: Array,
    },
    tags: {
        type: Array,
    },
    categories: {
        type: Object,
    },
});

const inventoryItems = ref([]);
// ================== Ingredients =====================
const i_search = ref("");
const i_cart = ref([]); 


const fetchInventory = async () => {
    try {
        const response = await axios.get("/inventory/api-inventories");
        inventoryItems.value = response.data.data;
        console.log("Fetched inventory:", inventoryItems.value);
    } catch (error) {
        console.error("Error fetching inventory:", error);
    }
};


// filter inventory for ingredients
const i_filteredInv = computed(() => {
    const t = i_search.value.trim().toLowerCase();
    if (!t) return inventoryItems.value;
    return inventoryItems.value.filter((i) =>
        [i.name, i.category ?? "", i.subcategory ?? ""]
            .join(" ")
            .toLowerCase()
            .includes(t)
    );
});

function round2(x) {
    return Math.round(x * 100) / 100;
}
// total cost of all ingredients
const i_total = computed(() =>
    round2(i_cart.value.reduce((s, r) => s + Number(r.cost || 0), 0))
);

// add ingredient
function addIngredient(item) {
    const qty = Number(item.qty || 0);
    const price =
        item.unitPrice !== "" && item.unitPrice != null
            ? Number(item.unitPrice)
            : Number(item.defaultPrice || 0);
    const expiry = item.expiry || null;

    if (!qty || qty <= 0) return alert("Enter a valid quantity.");
    if (!price || price <= 0) return alert("Enter a valid unit price.");

    const found = i_cart.value.find(
        (r) => r.id === item.id && r.unitPrice === price && r.expiry === expiry
    );
    if (found) {
        found.qty = round2(found.qty + qty);
        found.cost = round2(found.qty * found.unitPrice);
    } else {
        i_cart.value.push({
            id: item.id,
            name: item.name,
            category: item.category,
            qty,
            unitPrice: price,
            expiry,
            cost: round2(qty * price),
        });
    }

    // reset fields on the left card
    item.qty = null;
    item.unitPrice = null;
    item.expiry = null;
}

// remove ingredient row
function removeIngredient(idx) {
    i_cart.value.splice(idx, 1);
}


onMounted(() => {
    fetchInventory();
});

const expiredCount = 7;
const nearExpireCount = 3;

const inventories = ref(props.inventories?.data || []);
const items = computed(() => inventories.value);

const fetchInventories = async () => {
    try {
        const res = await axios.get("inventory/api-inventories");
        console.log("Stock response data:", res.data);

        const apiItems = res.data.data || [];

        inventories.value = await Promise.all(
            apiItems.map(async (item) => {
                const stockRes = await axios.get(
                    `/stock_entries/total/${item.id}`
                );
                const stockData = stockRes.data.total?.original || {};
                return {
                    ...item,
                    availableStock: stockData.available || 0,
                    stockValue: stockData.stockValue || 0,
                    minAlert: stockData.minAlert || 0,
                };
            })
        );
    } catch (err) {
        console.error(err);
    }
};

onMounted(() => {
    fetchInventories(); 
});
/* ===================== Toolbar: Search + Filter ===================== */
const q = ref("");
const sortBy = ref(""); // 'stock_desc' | 'stock_asc' | 'name_asc' | 'name_desc'

const filteredItems = computed(() => {
    const term = q.value.trim().toLowerCase();
    if (!term) return items.value;
    return items.value.filter((i) =>
        [i.name, i.category, i.unit].some((v) =>
            (v || "").toLowerCase().includes(term)
        )
    );
});

const sortedItems = computed(() => {
    const arr = [...filteredItems.value];
    switch (sortBy.value) {
        case "stock_desc":
            return arr.sort((a, b) => b.stockValue - a.stockValue); // High→Low
        case "stock_asc":
            return arr.sort((a, b) => a.stockValue - b.stockValue); // Low→High
        case "name_asc":
            return arr.sort((a, b) => a.name.localeCompare(b.name)); // A→Z
        case "name_desc":
            return arr.sort((a, b) => b.name.localeCompare(a.name)); // Z→A
        default:
            return arr;
    }
});

/* ===================== KPIs ===================== */
const categoriesCount = computed(
    () => new Set(items.value.map((i) => i.category)).size
);
const totalItems = computed(() => items.value.length);
const lowStockCount = computed(
    () =>
        items.value.filter(
            (i) => i.availableStock > 0 && i.availableStock < (i.minAlert || 5) // fallback to 5 if minAlert missing
        ).length
);
const outOfStockCount = computed(
    () => items.value.filter((i) => i.availableStock <= 0).length
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
        value: expiredCount.value ?? 0,
        icon: CalendarX2,
        iconBg: "bg-soft-danger",
        iconColor: "text-danger",
    },
    {
        label: "Near Expire Stock",
        value: nearExpireCount.value ?? 0,
        icon: CalendarClock,
        iconBg: "bg-soft-info",
        iconColor: "text-info",
    },
]);

onMounted(() => window.feather?.replace());
onUpdated(() => window.feather?.replace());

/* ===================== Helpers ===================== */
const money = (amount, currency = "GBP") =>
    new Intl.NumberFormat("en-GB", { style: "currency", currency }).format(
        amount
    );

const subcatMap = ref({
    Poultry: ["Chicken", "Broiler", "Wings", "Breast"],
    Produce: ["Tomatoes", "Onions", "Potatoes"],
    Grains: ["Rice", "Wheat", "Oats"],
    Grocery: ["Oil", "Spices", "Sugar"],
    // Dairy: ["Cheese", "Milk", "Butter"],
    Meat: ["Beef", "Mutton", "Veal"],
});

const subcatOptions = computed(() =>
    (subcatMap.value[form.value.category] || []).map((s) => ({
        name: s,
        value: s,
    }))
);
const categoryOptions = computed(() =>
    Object.keys(subcatMap.value).map((c) => ({ name: c, value: c }))
);
const formErrors = ref({});
const form = ref({
    name: "",
    category: [],
    subcategory: "",
    unit: [],
    minAlert: "",
    supplier: [],
    sku: "",
    description: "",
    nutrition: { calories: "", fat: "", protein: "", carbs: "" },
    allergies: [],
    tags: [],
    imageFile: null,
    imagePreview: "",
});

function handleImage(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    form.value.imageFile = file;
    const reader = new FileReader();
    reader.onload = (ev) =>
        (form.value.imagePreview = String(ev.target?.result || ""));
    reader.readAsDataURL(file);
}

const submitting = ref(false);

const submitProduct = async () => {
  submitting.value = true;
  formErrors.value = {};

  const formData = new FormData();
  formData.append("name", form.value.name.trim());
  formData.append("price", form.value.minAlert || 0); // base price
  formData.append("category_id", form.value.category); // category_id not name
  formData.append("subcategory_id", form.value.subcategory || "");
  formData.append("description", form.value.description || "");

  // nutrition
  formData.append("nutrition[calories]", form.value.nutrition?.calories || 0);
  formData.append("nutrition[fat]", form.value.nutrition?.fat || 0);
  formData.append("nutrition[protein]", form.value.nutrition?.protein || 0);
  formData.append("nutrition[carbs]", form.value.nutrition?.carbs || 0);

  // allergies + tags
  form.value.allergies.forEach((id, i) =>
    formData.append(`allergies[${i}]`, id)
  );
  form.value.tags.forEach((id, i) =>
    formData.append(`tags[${i}]`, id)
  );

  // ingredients cart
  i_cart.value.forEach((ing, i) => {
    formData.append(`ingredients[${i}][inventory_item_id]`, ing.id);
    formData.append(`ingredients[${i}][qty]`, ing.qty);
    formData.append(`ingredients[${i}][unit_price]`, ing.unitPrice);
    formData.append(`ingredients[${i}][cost]`, ing.cost);
    formData.append(`ingredients[${i}][expiry]`, ing.expiry || "");
  });

  // image
  if (form.value.imageFile) {
    formData.append("image", form.value.imageFile);
  }

  try {
    if (form.value.id) {
      await axios.post(`/menu/${form.value.id}?_method=PUT`, formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });
      toast.success("Menu updated successfully");
    } else {
      await axios.post("/menu", formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });
      toast.success("Menu created successfully");
    }

    resetForm();
    await fetchMenus(); // <-- similar to fetchInventories
    bootstrap.Modal.getInstance(
      document.getElementById("addItemModal")
    )?.hide();
  } catch (err) {
    if (err?.response?.status === 422 && err.response.data?.errors) {
      formErrors.value = err.response.data.errors;
      toast.error("Please fix the highlighted fields.");
    } else {
      console.error("❌ Error saving:", err.response?.data || err.message);
      toast.error("Failed to save menu item");
    }
  } finally {
    submitting.value = false;
  }
};


//  One deep watcher for the entire form
watch(
    form,
    (newVal) => {
        Object.keys(formErrors.value).forEach((key) => {
            if (key.includes(".")) {
                // handle nested keys e.g. "nutrition.calories"
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
                    (!(Array.isArray(value)) || value.length > 0)
                ) {
                    delete formErrors.value[key];
                }
            }
        });
    },
    { deep: true }
);


// ===============Edit item ==================
const editItem = (item) => {
    console.log(item);
    form.value = {
        id: item.id,
        name: item.name,
        category: item.category,
        subcategory: item.subcategory,
        unit: item.unit,
        minAlert: item.minAlert,
        supplier: item.supplier,
        sku: item.sku,
        description: item.description,
        nutrition: item.nutrition || {
            calories: 0,
            fat: 0,
            protein: 0,
            carbs: 0,
        },
        // Fix: Convert string values directly to numbers
        allergies: item.allergies?.map((a) => Number(a)) || [],
        tags: item.tags?.map((t) => Number(t)) || [],
        imageFile: null,
        imagePreview: item.image ? `/storage/${item.image}` : null,
    };
    console.log("Preselected allergies:", form.value);
    const modal = new bootstrap.Modal(document.getElementById("addItemModal"));
    modal.show();
};
// ============================= reset form =========================

function resetForm() {
    form.value = {
        name: "",
        category: "Poultry",
        subcategory: "",
        unit: "gram (g)",
        minAlert: "",
        supplier: "Noor",
        sku: "",
        description: "",
        nutrition: { calories: "", fat: "", protein: "", carbs: "" },
        allergies: [],
        tags: [],
        imageFile: null,
        imagePreview: "",
    };
}
// =====================view item =========================
const viewItemRef = ref({});

const ViewItem = async (row) => {
    try {
        const res = await axios.get(`/inventory/${row.id}`);
        viewItemRef.value = res.data;

        const modal = new bootstrap.Modal(
            document.getElementById("viewItemModal")
        );
        modal.show();
    } catch (error) {
        console.error("Error fetching item:", error);
    }
};



// code fo download files like  PDF, Excel and CSV

const onDownload = (type) => {
    if (!inventories.value || inventories.value.length === 0) {
        toast.error("No Allergies data to download");
        return;
    }

    // Use filtered data if there's a search query, otherwise use all suppliers
    const dataToExport = q.value.trim() ? filtered.value : inventories.value;

    if (dataToExport.length === 0) {
        toast.error("No Inventory Item found to download");
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
            "Item Name",
            "Category",
            "Min Alert",
            "Unit",
            "Supplier",
            "Sku",
            "Description",
            "Nutrition",
            "Allergies",
            "Tags",
            "Created At",
            "Updated At",
        ];

        // Build CSV rows
        const rows = data.map((s) => {
            //  Format nutrition into key:value pairs
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
                `"${s.category || ""}"`,
                `"${s.minAlert || ""}"`,
                `"${s.unit || ""}"`,
                `"${s.supplier || ""}"`,
                `"${s.sku || ""}"`,
                `"${s.description || ""}"`,
                `"${nutritionStr}"`,
                `"${Array.isArray(s.allergies)
                    ? s.allergies.join(", ")
                    : s.allergies || ""
                }"`,
                `"${Array.isArray(s.tags) ? s.tags.join(", ") : s.tags || ""}"`,
                `"${s.created_at || ""}"`,
                `"${s.updated_at || ""}"`,
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
            s.category || "",
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

// Helper function for safe JSON parsing
function safeParse(value) {
    try {
        return typeof value === "string" ? JSON.parse(value) : value;
    } catch (e) {
        return value;
    }
}

const downloadExcel = (data) => {
    try {
        // Check if XLSX is available
        if (typeof XLSX === "undefined") {
            throw new Error("XLSX library is not loaded");
        }

        // Prepare worksheet data
        const worksheetData = data.map((s) => {
            //  Format nutrition into key:value pairs
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

        // Create workbook and worksheet
        const workbook = XLSX.utils.book_new();
        const worksheet = XLSX.utils.json_to_sheet(worksheetData);

        // Set column widths
        worksheet["!cols"] = [
            { wch: 20 }, // Item Name
            { wch: 15 }, // Category
            { wch: 10 }, // Min Alert
            { wch: 12 }, // Unit
            { wch: 20 }, // Supplier
            { wch: 15 }, // Sku
            { wch: 30 }, // Description
            { wch: 40 }, // Nutrition
            { wch: 25 }, // Allergies
            { wch: 25 }, // Tags
            { wch: 20 }, // Created At
            { wch: 20 }, // Updated At
        ];

        // Add worksheet to workbook
        XLSX.utils.book_append_sheet(workbook, worksheet, "Inventory Items");

        // Add metadata sheet
        const metaData = [
            { Info: "Generated On", Value: new Date().toLocaleString() },
            { Info: "Total Records", Value: data.length },
            { Info: "Exported By", Value: "Inventory Management System" },
        ];
        const metaSheet = XLSX.utils.json_to_sheet(metaData);
        XLSX.utils.book_append_sheet(workbook, metaSheet, "Report Info");

        // Generate file name
        const fileName = `inventory_items_${new Date().toISOString().split("T")[0]
            }.xlsx`;

        // Save the file
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
                            <div class="card-body d-flex align-items-center justify-content-between ">
                                <!-- left: value + label -->
                                <div>
                                    <div class="fw-bold fs-4">
                                        {{ c.value }}
                                    </div>
                                    <div class="text-muted fs-6">
                                        {{ c.label }}
                                    </div>
                                </div>
                                <!-- right: soft icon chip -->
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
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary rounded-pill px-4 dropdown-toggle"
                                        data-bs-toggle="dropdown">
                                        Filter By
                                        <span v-if="sortBy" class="ms-1 text-muted small">
                                            {{
                                                sortBy === "stock_desc"
                                                    ? "High→Low"
                                                    : sortBy === "stock_asc"
                                                        ? "Low→High"
                                                        : sortBy === "name_asc"
                                                            ? "A→Z"
                                                            : sortBy === "name_desc"
                                                                ? "Z→A"
                                                                : ""
                                            }}
                                        </span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow rounded-4 py-2">
                                        <li>
                                            <a class="dropdown-item py-2" href="javascript:void(0)"
                                                @click="sortBy = 'stock_desc'">From High to Low</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item py-2" href="javascript:void(0)"
                                                @click="sortBy = 'stock_asc'">From Low to High</a>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider" />
                                        </li>
                                        <li>
                                            <a class="dropdown-item py-2" href="javascript:void(0)"
                                                @click="sortBy = 'name_asc'">Ascending</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item py-2" href="javascript:void(0)"
                                                @click="sortBy = 'name_desc'">Descending</a>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Add Item -->
                                <button data-bs-toggle="modal" data-bs-target="#addItemModal"
                                    class="d-flex align-items-center gap-1 px-4 py-2 rounded-pill btn btn-primary text-white">
                                    <Plus class="w-4 h-4" /> Add Item
                                </button>

                                <!-- Download all -->
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary rounded-pill px-4 dropdown-toggle"
                                        data-bs-toggle="dropdown">
                                        Download all
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
                            <table class="table table-hover">
                                <thead class="border-top small text-muted">
                                    <tr>
                                        <th>S.#</th>
                                        <th>Items</th>
                                        <th>Image</th>
                                        <th>Unit Price</th>
                                        <th>Category</th>
                                        <th>Unit</th>
                                        <th>Available Stock</th>
                                        <th>Stock Value</th>
                                        <th>Availability</th>

                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, idx) in sortedItems" :key="item.id">
                                        <td>{{ idx + 1 }}</td>
                                        <td class="fw-semibold">
                                            {{ item.name }}
                                        </td>
                                        <td>
                                            <img :src="item.image
                                                ? `/storage/${item.image}`
                                                : '/default.png'
                                                " class="rounded" style="
                                                    width: 40px;
                                                    height: 40px;
                                                    object-fit: cover;
                                                " />
                                        </td>
                                        <td>
                                            {{
                                                money(
                                                    item.unitPrice || 0,
                                                    "GBP"
                                                )
                                            }}
                                        </td>
                                        <td class="text-truncate" style="max-width: 260px">
                                            {{ item.category }}
                                        </td>
                                        <td>{{ item.unit }}</td>
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
                                        <td>
                                            <span v-if="item.availableStock === 0" class="badge bg-red-600">Out of
                                                stock</span>
                                            <span v-else-if="
                                                item.availableStock <=
                                                item.minAlert
                                            " class="badge bg-warning">Low-stock</span>
                                            <span v-else class="badge bg-success">In-stock</span>
                                        </td>

                                        <td class="text-end">
                                            <div class="dropdown">
                                                <button class="btn btn-link text-secondary p-0 fs-5"
                                                    data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                                    <Menu size="20" />
                                                </button>
                                                <ul
                                                    class="dropdown-menu dropdown-menu-end shadow rounded-4 overflow-hidden action-menu">
                                                    <li>
                                                        <a class="dropdown-item py-2" href="javascript:void(0)" @click="
                                                            openStockModal(
                                                                item
                                                            )
                                                            ">
                                                            <i class="bi bi-box-arrow-in-down-right me-2"></i>Stock In
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item py-2" href="javascript:void(0)" @click="
                                                            openStockOutModal(
                                                                item
                                                            )
                                                            ">
                                                            <i class="bi bi-box-arrow-up-right me-2"></i>Stock Out
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item py-2" href="javascript:void(0)" @click="
                                                            ViewItem(item)
                                                            ">
                                                            <i class="bi bi-eye me-2"></i>View Item
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item py-2" href="javascript:void(0)" @click="
                                                            editItem(item)
                                                            ">
                                                            <i class="bi bi-pencil-square me-2"></i>Edit
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr v-if="sortedItems.length === 0">
                                        <td colspan="11" class="text-center text-muted py-4">
                                            No items found.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ===================== Add New menu Item Modal ===================== -->
                <div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                        <div class="modal-content rounded-4">
                            <div class="modal-header">
                                <h5 class="modal-title fw-semibold">
                                    Add New Inventory Item
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
                                        <label class="form-label">Menu Name</label>
                                        <input v-model="form.name" type="text" class="form-control"
                                            :class="{ 'is-invalid': formErrors.name }"
                                            placeholder="e.g., Chicken Breast" />
                                        <small v-if="formErrors.name" class="text-danger">
                                            {{ formErrors.name[0] }}
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label d-block">Base Price</label>
                                        <input v-model="form.minAlert" type="number" min="0" class="form-control"
                                            :class="{ 'is-invalid': formErrors.minAlert }" placeholder="e.g., 0.00" />
                                        <small v-if="formErrors.minAlert" class="text-danger">
                                            {{ formErrors.minAlert[0] }}
                                        </small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Category</label>
                                        <Select v-model="form.category" :options="categories" optionLabel="name"
                                            optionValue="name" placeholder="Select Category" class="w-100"
                                            appendTo="self" :autoZIndex="true" :baseZIndex="2000"
                                            @update:modelValue="form.subcategory = ''"
                                            :class="{ 'is-invalid': formErrors.category }" />
                                        <small v-if="formErrors.category" class="text-danger">
                                            {{ formErrors.category[0] }}
                                        </small>
                                    </div>

                                    <!-- Subcategory -->
                                    <div class="col-md-6" v-if="subcatOptions.length">
                                        <label class="form-label">Subcategory</label>
                                        <Select v-model="form.subcategory" :options="subcatOptions" optionLabel="name"
                                            optionValue="value" placeholder="Select Subcategory" class="w-100"
                                            :appendTo="body" :autoZIndex="true" :baseZIndex="2000"
                                            :class="{ 'is-invalid': formErrors.subcategory }" />
                                        <small v-if="formErrors.subcategory" class="text-danger">
                                            {{ formErrors.subcategory[0] }}
                                        </small>
                                    </div>


                                    <div class="col-12">
                                        <label class="form-label">Description</label>
                                        <textarea v-model="form.description" rows="4" class="form-control"
                                            :class="{ 'is-invalid': formErrors.description }"
                                            placeholder="Notes about this product"></textarea>
                                        <small v-if="formErrors.description" class="text-danger">
                                            {{ formErrors.description[0] }}
                                        </small>
                                    </div>
                                </div>

                                <hr class="my-4" />

                                <div class="row g-4 mt-1">
                                    <!-- Allergies -->
                                    <div class="col-md-6">
                                        <label class="form-label d-block">Allergies</label>
                                        <MultiSelect v-model="form.allergies" :options="allergies" optionLabel="name"
                                            optionValue="id" filter placeholder="Select Allergies"
                                            class="w-full md:w-80" appendTo="self"
                                            :class="{ 'is-invalid': formErrors.allergies }" />
                                        <small v-if="formErrors.allergies" class="text-danger">
                                            {{ formErrors.allergies[0] }}
                                        </small>
                                    </div>

                                    <!-- Tags -->
                                    <div class="col-md-6">
                                        <label class="form-label d-block">Tags (Halal, Haram, etc.)</label>
                                        <MultiSelect v-model="form.tags" :options="tags" optionLabel="name"
                                            optionValue="id" filter placeholder="Select Tags" class="w-full md:w-80"
                                            appendTo="self" :class="{ 'is-invalid': formErrors.tags }" />
                                        <small v-if="formErrors.tags" class="text-danger">
                                            {{ formErrors.tags[0] }}
                                        </small>
                                    </div>
                                </div>

                                <!-- Image -->
                                <div class="row g-3 mt-2 align-items-center">
                                    <div class="col-sm-6 col-md-4">
                                        <div class="img-drop rounded-3 d-flex align-items-center justify-content-center"
                                            :class="{ 'is-invalid': formErrors.image }">
                                            <template v-if="!form.imagePreview">
                                                <div class="text-center small">
                                                    <div class="mb-2">
                                                        <i class="bi bi-image fs-3"></i>
                                                    </div>
                                                    <div>Drag image here</div>
                                                    <div>
                                                        or
                                                        <label class="text-primary fw-semibold"
                                                            style="cursor: pointer;">
                                                            Browse image
                                                            <input type="file" accept="image/*" class="d-none"
                                                                @change="handleImage" />
                                                        </label>
                                                    </div>
                                                </div>
                                            </template>
                                            <template v-else>
                                                <img :src="form.imagePreview" class="w-100 h-100 rounded-3"
                                                    style="object-fit: cover" />
                                            </template>
                                        </div>
                                        <small v-if="formErrors.image" class="text-danger">
                                            {{ formErrors.image[0] }}
                                        </small>
                                    </div>
                                    <div class="mt-4 col-sm-6 col-md-4">
                                        <button class="btn btn-outline-primary rounded-pill px-4" data-bs-toggle="modal"
                                            data-bs-target="#addIngredientModal">
                                            + Add Ingredients
                                        </button>
                                    </div>

                                </div>

                                <div class="mt-4">
                                    <button class="btn btn-primary rounded-pill px-5 py-2" :disabled="submitting"
                                        @click="submitProduct">
                                        <span>Add Product</span>
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

                <!-- View modal  -->
                <div class="modal fade" id="viewItemModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                            <!-- Header -->
                            <div class="modal-header bg-light">
                                <div class="d-flex align-items-center gap-2">
                                    <!-- Optional icon (Lucide/Bootstrap icon) -->
                                    <i class="bi bi-box-seam text-primary fs-4"></i>
                                    <div>
                                        <h5 class="modal-title fw-semibold mb-0">
                                            View Inventory Item
                                        </h5>
                                        <small class="text-muted" v-if="viewItemRef?.sku">SKU: {{ viewItemRef.sku
                                            }}</small>
                                    </div>
                                </div>

                                <!-- Close -->
                                <button type="button" class="btn btn-light btn-sm rounded-circle p-2"
                                    data-bs-dismiss="modal" aria-label="Close" title="Close">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>

                            <!-- Body -->
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
                                                                viewItemRef.category ||
                                                                "—"
                                                            }}
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="text-muted">
                                                            Subcategory
                                                        </div>
                                                        <div class="fw-semibold">
                                                            {{
                                                                viewItemRef.subcategory ||
                                                                "—"
                                                            }}
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="text-muted">
                                                            Unit
                                                        </div>
                                                        <div class="fw-semibold">
                                                            {{
                                                                viewItemRef.unit
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
                                                                viewItemRef.supplier
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

                                                <!-- Nutrition -->
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
                                                    <span class="badge rounded-pill  bg-warning text-white">Fat:
                                                        {{
                                                            viewItemRef
                                                                .nutrition
                                                                ?.fat ?? "—"
                                                        }}</span>
                                                    <span class="badge rounded-pill  bg-secondary text-white">Carbs:
                                                        {{
                                                            viewItemRef
                                                                .nutrition
                                                                ?.carbs ?? "—"
                                                        }}</span>
                                                </div>

                                                <!-- Tags -->
                                                <hr />
                                                <h6 class="fw-semibold">
                                                    Tags
                                                </h6>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <span v-for="tag in viewItemRef.tags" :key="tag"
                                                        class="badge rounded-pill bg-info text-white">
                                                        {{ tag }}
                                                    </span>
                                                </div>

                                                <!-- Allergies -->
                                                <hr />
                                                <h6 class="fw-semibold">
                                                    Allergies
                                                </h6>
                                                <div class="d-flex flex-wrap gap-2">
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
                                                <div v-if="viewItemRef.image" class="w-100">
                                                    <img :src="`/storage/${viewItemRef.image}`" alt="Item Image"
                                                        class="w-100 rounded-3" style="
                                                            max-height: 260px;
                                                            object-fit: cover;
                                                        " />
                                                </div>
                                                <div v-else class="text-center text-muted py-5">
                                                    <i class="bi bi-image fs-1 d-block mb-2"></i>
                                                    <small>No image
                                                        uploaded</small>
                                                </div>
                                            </div>
                                            <div
                                                class="card-footer bg-transparent small d-flex justify-content-between">
                                                <span class="text-muted">Updated On</span>
                                                <span class="fw-semibold">{{
                                                    viewItemRef.formatted_updated_at
                                                    }}</span>
                                            </div>

                                            <div
                                                class="card-footer bg-transparent small d-flex justify-content-between">
                                                <span class="text-muted">
                                                    Stock</span>
                                                <span :class="[
                                                    'fw-semibold',
                                                    viewItemRef.stock > 0
                                                        ? 'text-success'
                                                        : 'text-danger',
                                                ]">
                                                    {{ viewItemRef.stock }}

                                                </span>
                                                <span v-if="
                                                    viewItemRef.stock ===
                                                    0
                                                " class="badge bg-red-600 rounded-pill">Out of stock</span>
                                                <span v-else-if="
                                                    viewItemRef.stock <=
                                                    viewItemRef.minAlert
                                                " class="badge bg-warning rounded-pill">Low-stock</span>
                                                <span v-else class="badge bg-success rounded-pill">In-stock</span>

                                            </div>

                                            <div
                                                class="card-footer bg-transparent small d-flex justify-content-between">
                                                <span class="text-muted">Added By</span>
                                                <span class="fw-semibold">{{
                                                    viewItemRef.user?.name
                                                    }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="modal-footer d-flex justify-content-between">
                                <div class="d-flex gap-2">
                                    <!-- <button
                                        type="button"
                                        class="btn btn-outline-primary rounded-pill"
                                    >
                                        <i class="bi bi-printer me-1"></i> Print
                                    </button> -->

                                    <button data-bs-toggle="modal" data-bs-target="#addItemModal"
                                        class="d-flex align-items-center gap-1 px-5 rounded-pill btn btn-primary text-white"
                                        @click="editItem(viewItemRef)">
                                        <Pencil class="w-4 h-4" /> Edit
                                    </button>



                                </div>

                                <button type="button" class="btn btn-primary rounded-pill" data-bs-dismiss="modal">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Ingredient Modal -->
                <div class="modal fade" id="addIngredientModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content rounded-4">
                            <div class="modal-header">
                                <h5 class="modal-title fw-semibold">Add Ingredients</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">
                                <div class="row g-4">
                                    <!-- Left side -->
                                    <div class="col-lg-5">
                                        <div class="search-wrap mb-2">
                                            <input v-model="i_search" type="text" class="form-control search-input"
                                                placeholder="Search Items..." />
                                        </div>

                                        <div v-for="it in i_filteredInv" :key="it.id"
                                            class="card shadow-sm border-0 rounded-4 mb-3">
                                            <div class="card-body">
                                                <div class="d-flex align-items-start gap-3">
                                                    <img :src="it.image ? `/storage/${it.image}` : '/default.png'"
                                                        class="rounded"
                                                        style="width: 56px; height: 56px; object-fit: cover;" />
                                                    <div class="flex-grow-1">
                                                        <div class="fw-semibold">{{ it.name }}</div>
                                                        <div class="text-muted small">Category: {{ it.category }}</div>
                                                        <div class="text-muted small">Unit: {{ it.unit }}</div>
                                                    </div>
                                                    <button class="btn btn-primary px-3"
                                                        @click="addIngredient(it)">Add</button>
                                                </div>

                                                <div class="row g-2 mt-3">
                                                    <div class="col-4">
                                                        <label class="small text-muted">Quantity</label>
                                                        <input v-model.number="it.qty" type="number" min="0"
                                                            class="form-control form-control-sm" />
                                                    </div>
                                                    <div class="col-4">
                                                        <label class="small text-muted">Unit Price</label>
                                                        <input v-model.number="it.unitPrice" type="number" min="0"
                                                            class="form-control form-control-sm" />
                                                    </div>
                                                    <div class="col-4">
                                                        <label class="small text-muted">Expiry</label>
                                                        <input v-model="it.expiry" type="date"
                                                            class="form-control form-control-sm" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right side -->
                                    <div class="col-lg-7">
                                        <div class="card border rounded-4">
                                            <div class="table-responsive">
                                                <table class="table align-middle mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Name</th>
                                                            <th>Qty</th>
                                                            <th>Unit Price</th>
                                                            <th>Expiry</th>
                                                            <th>Cost</th>
                                                            <th class="text-end">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(ing, idx) in i_cart" :key="idx">
                                                            <td>{{ ing.name }}</td>
                                                            <td>{{ ing.qty }}</td>
                                                            <td>{{ ing.unitPrice }}</td>
                                                            <td>{{ ing.expiry || '—' }}</td>
                                                            <td>{{ ing.cost }}</td>
                                                            <td class="text-end">
                                                                <button class="btn btn-sm btn-danger"
                                                                    @click="removeIngredient(idx)">Remove</button>
                                                            </td>
                                                        </tr>
                                                        <tr v-if="i_cart.length === 0">
                                                            <td colspan="6" class="text-center text-muted py-3">No
                                                                ingredients added.
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="p-3 fw-semibold text-end">
                                                Total Cost: {{ money(i_total) }}
                                            </div>
                                        </div>

                                        <div class="mt-3 text-center">
                                            <button class="btn btn-primary px-5" data-bs-dismiss="modal">Done</button>
                                        </div>
                                    </div>
                                </div>
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

/* Mobile tweaks */
@media (max-width: 575.98px) {
    .kpi-value {
        font-size: 1.45rem;
    }

    .search-wrap {
        width: 100%;
    }
}
</style>
