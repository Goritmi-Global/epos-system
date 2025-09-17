<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted, onUpdated, watch, toRaw } from "vue";
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
    Pencil,
    CheckCircle,
    Eye,
    EyeOff
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
const showStatusModal = ref(false);
const statusTargetItem = ref(null);
// ================== Ingredients =====================
const i_search = ref("");
const i_cart = ref([]);

const fetchInventory = async () => {
    try {
        const response = await axios.get("/inventory/api-inventories");
        inventoryItems.value = response.data.data;
    } catch (error) {
        console.error("Error fetching inventory:", error);
    }
};

import ImageZoomModal from "@/Components/ImageZoomModal.vue";

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

    formErrors.value = {};

    if (!qty || qty <= 0) formErrors.value.qty = "Enter a valid quantity.";
    if (!price || price <= 0) formErrors.value.unitPrice = "Enter a valid unit price.";

    if (Object.keys(formErrors.value).length > 0) {
        const messages = Object.values(formErrors.value)
            .flat()
            .join("\n");
        toast.error(messages, {
            style: { whiteSpace: "pre-line" }
        });

        return;
    }


    const found = i_cart.value.find(r => r.id === item.id);

    if (found) {
        found.qty = qty;
        found.unitPrice = price;
        found.cost = round2(qty * price);
    } else {
        i_cart.value.push({
            id: item.id,
            name: item.name,
            category: item.category,
            qty,
            unitPrice: price,
            cost: round2(qty * price),
            nutrition: item.nutrition || { calories: 0, protein: 0, carbs: 0, fat: 0 }
        });
    }

    if (!isEditMode.value) {
        item.qty = null
        item.unitPrice = null
    }

}

function removeIngredient(idx) {
    const ing = i_cart.value[idx];
    if (!ing) return;

    // remove from right table (cart)
    i_cart.value.splice(idx, 1);

    // reset fields on left side card
    const found = i_displayInv.value.find(i => i.id === ing.id);
    if (found) {
        found.qty = null;
        found.unitPrice = null;
        found.cost = null;
    }
}


// calulate Ingredient when qty or price changes
const i_totalNutrition = computed(() => {
    return i_cart.value.reduce((totals, ing) => {
        const qty = Number(ing.qty) || 0

        totals.calories += Number(ing.nutrition?.calories || 0) * qty
        totals.protein += Number(ing.nutrition?.protein || 0) * qty
        totals.carbs += Number(ing.nutrition?.carbs || 0) * qty
        totals.fat += Number(ing.nutrition?.fat || 0) * qty

        return totals
    }, { calories: 0, protein: 0, carbs: 0, fat: 0 })
})


// Save ingredients to main form
function saveIngredients() {
    resetErrors();
    form.ingredients = [...i_cart.value];

    // close ingredient modal
    const ingModal = bootstrap.Modal.getInstance(
        document.getElementById("addIngredientModal")
    );
    ingModal.hide();

    // reopen parent menu modal
    const menuModal = new bootstrap.Modal(
        document.getElementById("addItemModal")
    );
    menuModal.show();
}

const showMenuModal = () => {
    const menuModal = new bootstrap.Modal(
        document.getElementById("addItemModal")
    );
    menuModal.show();
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
const menuItems = ref([]);
const fetchMenus = async () => {
    try {
        const res = await axios.get("/menu/menu-items");
        menuItems.value = res.data.data || [];
    } catch (err) {
        console.error("❌ Error fetching menus:", err);
    }
};


onMounted(() => {
    fetchInventories();
    fetchMenus();
});
/* ===================== Toolbar: Search + Filter ===================== */
const q = ref("");
const sortBy = ref("");

// ✅ Use menuItems here
const filteredItems = computed(() => {
    const term = q.value.trim().toLowerCase();
    if (!term) return menuItems.value;
    return menuItems.value.filter((i) =>
        [i.name, i.category?.name, i.unit].some((v) =>
            (v || "").toLowerCase().includes(term)
        )
    );
});

const sortedItems = computed(() => {
    const arr = [...filteredItems.value];
    switch (sortBy.value) {
        case "stock_desc":
            return arr.sort((a, b) => (b.price || 0) - (a.price || 0));
        case "stock_asc":
            return arr.sort((a, b) => (a.price || 0) - (b.price || 0));
        case "name_asc":
            return arr.sort((a, b) => a.name.localeCompare(b.name));
        case "name_desc":
            return arr.sort((a, b) => b.name.localeCompare(a.name));
        default:
            return arr;
    }
});


/* ===================== KPIs ===================== */
const categoriesCount = computed(
    () => new Set(items.value.map((i) => i.category)).size
);
const totalItems = computed(() => items.value.length);
const totalMenuItems = computed(() => menuItems.value.length);
const activeMenuItems = computed(() =>
    menuItems.value.filter(item => item.status === 1).length
);

const deactiveMenuItems = computed(() =>
    menuItems.value.filter(item => item.status === 0).length
);
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
        label: "Total Menus",
        value: totalMenuItems.value ?? 0,
        icon: Package,
        iconBg: "bg-soft-success",
        iconColor: "text-success",
    },
    {
        label: "Active Menus",
        value: activeMenuItems.value ?? 0,
        icon: CheckCircle,
        iconBg: "bg-soft-danger",
        iconColor: "text-success",
    },
    {
        label: "Inactive Menus",
        value: deactiveMenuItems.value ?? 0,
        icon: XCircle,
        iconBg: "bg-soft-warning",
        iconColor: "text-danger",
    },
    // {
    //     label: "Expired Stock",
    //     value: expiredCount.value ?? 0,
    //     icon: CalendarX2,
    //     iconBg: "bg-soft-danger",
    //     iconColor: "text-danger",
    // },
    // {
    //     label: "Near Expire Stock",
    //     value: nearExpireCount.value ?? 0,
    //     icon: CalendarClock,
    //     iconBg: "bg-soft-info",
    //     iconColor: "text-info",
    // },
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
function resetErrors() {
    formErrors.value = {};
}


const form = ref({
    name: "",
    category_id: null,
    subcategory: "",
    unit: [],
    price: "",
    supplier: [],
    sku: "",
    description: "",
    nutrition: { calories: "", fat: "", protein: "", carbs: "" },
    allergies: [],
    tags: [],
    imageFile: null,
    imageUrl: null,
});


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

const submitting = ref(false);
const submitProduct = async () => {
    submitting.value = true;
    formErrors.value = {};

    const formData = new FormData();
    formData.append("name", form.value.name.trim());
    if (form.value.price !== "" && form.value.price !== null) {
        formData.append("price", form.value.price);
    }

    if (form.value.category_id) {
        formData.append("category_id", form.value.category_id);
    }
    if (form.value.subcategory_id) {
        formData.append("subcategory_id", form.value.subcategory_id);
    }
    formData.append("description", form.value.description || "");

    // nutrition
    // nutrition from computed total
    formData.append("nutrition[calories]", i_totalNutrition.value.calories);
    formData.append("nutrition[fat]", i_totalNutrition.value.fat);
    formData.append("nutrition[protein]", i_totalNutrition.value.protein);
    formData.append("nutrition[carbs]", i_totalNutrition.value.carbs);


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
    });

    // image
    if (form.value.imageFile) {
        formData.append("image", form.value.imageFile);
    }

    try {
        if (form.value.id) {
            console.log("Updating menu item with ID:", formData);
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
        await fetchMenus();
        bootstrap.Modal.getInstance(
            document.getElementById("addItemModal")
        )?.hide();
    } catch (err) {
        if (err?.response?.status === 422 && err.response.data?.errors) {
            formErrors.value = err.response.data.errors;

            // Collect all error messages in one array
            const allMessages = Object.values(err.response.data.errors)
                .flat()
                .join("\n");

            // Show all messages in one toast
            // toast.error(allMessages);
            toast.error("Please filled all the required fields");
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
const savedNutrition = ref({ calories: 0, protein: 0, carbs: 0, fat: 0 })
const isEditMode = ref(false);

const i_displayInv = computed(() => {
    return i_filteredInv.value.map(inv => {
        const found = i_cart.value.find(c => c.id === inv.id);
        return found ? {
            ...inv,
            qty: found.qty,
            unitPrice: found.unitPrice,
            cost: found.cost
        } : {
            ...inv,
            qty: 0,
            unitPrice: 0,
            cost: 0
        };
    });
});


const editItem = (item) => {
    if (form.value.imageUrl && form.value.imageUrl.startsWith('blob:')) {
        URL.revokeObjectURL(form.value.imageUrl);
    }

    isEditMode.value = true;
    const itemData = toRaw(item);
    console.log(itemData);

    form.value = {
        id: itemData.id,
        name: itemData.name,
        price: itemData.price,
        category_id: itemData.category?.id || null,
        description: itemData.description,
        ingredients: itemData.ingredients || [],
        allergies: itemData.allergies?.map(a => a.id) || [],
        tags: itemData.tags?.map(t => t.id) || [],
        imageFile: null,
        imageUrl: itemData.image_url || null,
    };

    savedNutrition.value = {
        calories: parseFloat(itemData.nutrition?.calories || 0),
        protein: parseFloat(itemData.nutrition?.protein || 0),
        carbs: parseFloat(itemData.nutrition?.carbs || 0),
        fat: parseFloat(itemData.nutrition?.fat || 0)
    };

    // Build i_cart with enriched inventory details
    i_cart.value = (itemData.ingredients || []).map(ing => {
        const quantity = parseFloat(ing.quantity || ing.qty || 0);
        const cost = parseFloat(ing.cost || 0);
        const unitPrice = quantity > 0 ? cost / quantity : 0;

        // find the matching inventory item (for category, unit, nutrition, etc.)
        const inv = i_filteredInv.value.find(inv =>
            inv.id === (ing.inventory_item_id || ing.id || ing.product_id)
        );

        return {
            id: ing.inventory_item_id || ing.id || ing.product_id,
            image_url: inv.image_url || '—',
            name: ing.product_name || ing.name || inv?.name || '—',
            category: inv?.category || ing.category || { name: '' },
            unit: inv?.unit || ing.unit || '',
            qty: quantity,
            unitPrice: unitPrice,
            cost: cost,
            nutrition: ing.nutrition
                ? (typeof ing.nutrition === 'string' ? JSON.parse(ing.nutrition) : ing.nutrition)
                : inv?.nutrition || { calories: 0, protein: 0, carbs: 0, fat: 0 },
        };
    });

    console.log('Hydrated cart with details:', i_cart.value);

    const modal = new bootstrap.Modal(document.getElementById("addItemModal"));
    modal.show();
};



const submitEdit = async () => {
    submitting.value = true;
    formErrors.value = {};

    try {
        // Recalculate nutrition totals
        const totalNutrition = i_cart.value.reduce((acc, ing) => {
            const qty = Number(ing.qty || 0);
            acc.calories += (Number(ing.nutrition?.calories || 0) * qty);
            acc.protein += (Number(ing.nutrition?.protein || 0) * qty);
            acc.carbs += (Number(ing.nutrition?.carbs || 0) * qty);
            acc.fat += (Number(ing.nutrition?.fat || 0) * qty);
            return acc;
        }, { calories: 0, protein: 0, carbs: 0, fat: 0 });

        // Use FormData for proper file upload handling
        const formData = new FormData();

        // Basic fields
        formData.append("name", form.value.name.trim());
        formData.append("price", form.value.price || 0);
        if (form.value.category_id) {
            formData.append("category_id", form.value.category_id);
        }
        formData.append("description", form.value.description || "");

        // Nutrition data
        formData.append("nutrition[calories]", totalNutrition.calories);
        formData.append("nutrition[fat]", totalNutrition.fat);
        formData.append("nutrition[protein]", totalNutrition.protein);
        formData.append("nutrition[carbs]", totalNutrition.carbs);

        // Allergies and tags
        form.value.allergies.forEach((id, i) =>
            formData.append(`allergies[${i}]`, id)
        );
        form.value.tags.forEach((id, i) =>
            formData.append(`tags[${i}]`, id)
        );

        // Ingredients from cart
        i_cart.value.forEach((ing, i) => {
            formData.append(`ingredients[${i}][inventory_item_id]`, ing.id);
            formData.append(`ingredients[${i}][qty]`, ing.qty);
            formData.append(`ingredients[${i}][unit_price]`, ing.unitPrice);
            formData.append(`ingredients[${i}][cost]`, ing.cost);
        });

        // Image handling
        if (form.value.imageFile) {
            // New image selected
            formData.append("image", form.value.imageFile);
        }

        // Method spoofing for Laravel PUT request with file upload
        formData.append("_method", "PUT");

        // Make the API call
        await axios.post(`/menu/${form.value.id}`, formData, {
            headers: {
                "Content-Type": "multipart/form-data"
            },
        });

        toast.success("Menu updated successfully");

        // Reset form and close modal
        resetForm();
        await fetchMenus();

        const modal = bootstrap.Modal.getInstance(document.getElementById("addItemModal"));
        modal?.hide();

    } catch (err) {
        if (err?.response?.status === 422 && err.response.data?.errors) {
            formErrors.value = err.response.data.errors;

            // Collect all error messages in one array
            const allMessages = Object.values(err.response.data.errors)
                .flat()
                .join("\n");

            toast.error(allMessages);
        } else {
            console.error("❌ Update failed:", err.response?.data || err.message);
            toast.error("Failed to update menu item");
        }
    } finally {
        submitting.value = false;
    }
};


const toggleStatus = async (item) => {
    try {
        const newStatus = item.status === 1 ? 0 : 1;
        await axios.patch(`/menu/${item.id}/status`, { status: newStatus });
        toast.success(`Menu item is now ${newStatus === 1 ? "Active" : "Inactive"}`);
        await fetchMenus();
    } catch (err) {
        console.error("Failed to toggle status", err);
        toast.error("Failed to update status");
    }
};


// ============================= reset form =========================

function resetForm() {
    // revoke blob URL if exists
    if (form.value.imageUrl && form.value.imageUrl.startsWith('blob:')) {
        URL.revokeObjectURL(form.value.imageUrl);
    }

    // reset form fields
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
        imageUrl: null,
    };

    // reset UI states
    showCropper.value = false;
    showImageModal.value = false;
    previewImage.value = null;
    formErrors.value = {};
    isEditMode.value = null;

    // ✅ reset ingredients + totals
    i_cart.value = [];
    i_total.value = 0;
    i_totalNutrition.value = {
        calories: 0,
        protein: 0,
        carbs: 0,
        fat: 0,
    };
}


// code fo download files like  PDF, Excel and CSV

const onDownload = (type) => {
    if (!menuItems.value || menuItems.value.length === 0) {
        toast.error("No Allergies data to download");
        return;
    }

    // Use filtered data if there's a search query, otherwise use all suppliers
    const dataToExport = q.value.trim() ? filtered.value : menuItems.value;

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
            "Description",
            "Nutrition",
            "Allergies",
            "Tags",
        ];

        // Build CSV rows
        const rows = data.map((s) => {
            // ✅ Format Nutrition (only calories, protein, fat, carbs)
            let nutritionStr = "";
            if (s.nutrition && typeof s.nutrition === "object") {
                const wantedKeys = ["calories", "protein", "fat", "carbs"];
                nutritionStr = wantedKeys
                    .map((key) =>
                        s.nutrition[key] !== undefined
                            ? `${key}: ${s.nutrition[key]}`
                            : null
                    )
                    .filter(Boolean)
                    .join("; ");
            } else if (typeof s.nutrition === "string") {
                nutritionStr = s.nutrition;
            }

            // ✅ Handle Category (object or string)
            const category =
                typeof s.category === "object"
                    ? s.category?.name || ""
                    : s.category || "";

            // ✅ Handle Allergies (array of objects or strings)
            const allergies = Array.isArray(s.allergies)
                ? s.allergies.map((a) => a.name || a).join(", ")
                : s.allergies || "";

            // ✅ Handle Tags (array of objects or strings)
            const tags = Array.isArray(s.tags)
                ? s.tags.map((t) => t.name || t).join(", ")
                : s.tags || "";

            return [
                `"${s.name || ""}"`,
                `"${category}"`,
                `"${s.description || ""}"`,
                `"${nutritionStr}"`,
                `"${allergies}"`,
                `"${tags}"`,
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
            `menu_items_${new Date().toISOString().split("T")[0]}.csv`
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
        doc.text("Menu Item Report", 70, 20);

        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        const currentDate = new Date().toLocaleString();
        doc.text(`Generated on: ${currentDate}`, 70, 28);
        doc.text(`Total Menu Items: ${data.length}`, 70, 34);

        const tableColumns = [
            "Item Name",
            "Category",
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
                // ✅ Only keep required fields
                const wantedKeys = ["calories", "protein", "fat", "carbs"];
                return wantedKeys
                    .map((key) =>
                        nutri[key] !== undefined ? `${key}: ${nutri[key]}` : null
                    )
                    .filter(Boolean)
                    .join(", ");
            }
            return String(nutri ?? "");
        };


        const tableRows = data.map((s) => [
            s.name || "",
            s.category.name || "",
            s.description || "",
            s.nutrition_text || formatNutrition(s.nutrition),
            // Allergies
            Array.isArray(s.allergies)
                ? s.allergies.map(a => a.name || a).join(", ")
                : s.allergies || "",

            // Tags
            Array.isArray(s.tags)
                ? s.tags.map(t => t.name || t).join(", ")
                : s.tags || "",

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

        const fileName = `menu_items_${new Date().toISOString().split("T")[0]
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
            // ✅ Format Nutrition (only calories, protein, fat, carbs)
            let nutritionStr = "";
            if (s.nutrition && typeof s.nutrition === "object") {
                const wantedKeys = ["calories", "protein", "fat", "carbs"];
                nutritionStr = wantedKeys
                    .map((key) =>
                        s.nutrition[key] !== undefined
                            ? `${key}: ${s.nutrition[key]}`
                            : null
                    )
                    .filter(Boolean)
                    .join("; ");
            } else if (typeof s.nutrition === "string") {
                nutritionStr = s.nutrition;
            }

            // ✅ Handle Category (object or string)
            const category =
                typeof s.category === "object"
                    ? s.category?.name || ""
                    : s.category || "";

            // ✅ Handle Allergies (array of objects or strings)
            const allergies = Array.isArray(s.allergies)
                ? s.allergies.map((a) => a.name || a).join(", ")
                : s.allergies || "";

            // ✅ Handle Tags (array of objects or strings)
            const tags = Array.isArray(s.tags)
                ? s.tags.map((t) => t.name || t).join(", ")
                : s.tags || "";

            return {
                "Item Name": s.name || "",
                Category: category,
                Description: s.description || "",
                Nutrition: nutritionStr,
                Allergies: allergies,
                Tags: tags,
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
        const fileName = `menu_items_${new Date()
            .toISOString()
            .split("T")[0]}.xlsx`;

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
                <h4 class="fw-semibold mb-3">Menus</h4>
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

                <!-- Main Table -->
                <div class="card border-0 shadow-lg rounded-4 mt-3">
                    <div class="card-body">
                        <!-- Toolbar -->
                        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                            <h2 class="mb-0 fw-semibold fs-6">Menu</h2>

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
                                <button data-bs-toggle="modal" @click="resetErrors" data-bs-target="#addItemModal"
                                    class="d-flex align-items-center gap-1 px-4 py-2 rounded-pill btn btn-primary text-white">
                                    <Plus class="w-4 h-4" /> Add Menu
                                </button>

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
                                        <th>Image</th>
                                        <th>Menu Name</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, idx) in sortedItems" :key="item.id">
                                        <!-- S.# -->
                                        <td>{{ idx + 1 }}</td>

                                        <td>
                                            <ImageZoomModal 
                                                v-if="item.image_url"
                                                :file="item.image_url"
                                                :alt="item.name"
                                                :width="50"
                                                :height="50"
                                                :custom_class="'cursor-pointer'"
                                               
                                            />
                                        </td>

                                        <!-- Menu Name -->
                                        <td class="fw-semibold">
                                            {{ item.name }}
                                        </td>

                                        <!-- Category -->
                                        <td class="text-truncate" style="max-width: 260px">
                                            {{ item.category?.name || '—' }}
                                        </td>

                                        <!-- Price -->
                                        <td>
                                            {{ money(item.price || 0, "GBP") }}
                                        </td>

                                        <!-- Status -->
                                        <td>
                                            <span v-if="item.status === 0"
                                                class="badge bg-red-600 rounded-pill d-inline-block text-center px-3 py-1">Inactive</span>
                                            <span v-else
                                                class="badge bg-success rounded-pill d-inline-block text-center px-3 py-1">Active</span>
                                        </td>

                                        <!-- Actions -->
                                        <td class="text-center">
                                            <div class="d-inline-flex align-items-center gap-3">

                                                <button @click="editItem(item)" data-bs-toggle="modal" title="Edit"
                                                    class="p-2 rounded-full text-blue-600 hover:bg-blue-100">
                                                    <Pencil class="w-4 h-4" />
                                                </button>
                                                <!-- <button
  @click="() => { statusTargetItem.value = item; showStatusModal.value = true; }"
  class="flex items-center justify-center w-8 h-8 rounded-full transition-colors duration-200"
  :class="item.status === 1
      ? 'bg-green-50 text-green-600 hover:bg-green-100'
      : 'bg-red-50 text-red-600 hover:bg-red-100'"
  :title="item.status === 1 ? 'Deactivate' : 'Activate'"
>
  <i
    :class="item.status === 1 ? 'bi bi-check-circle' : 'bi bi-x-circle'"
    class="text-lg"
  ></i> -->
                                                <!-- </button> -->


                                                <ConfirmModal :title="'Confirm Status'"
                                                    :message="`Are you sure you want to change status to ${item.status === 1 ? 'Inactive' : 'Active'}?`"
                                                    :showDeleteButton="true" @confirm="() => {
                                                        toggleStatus(item);
                                                    }" @cancel="() => { }" />



                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Empty state -->
                                    <tr v-if="sortedItems.length === 0">
                                        <td colspan="7" class="text-center text-muted py-4">
                                            No Menu items found.
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
                                    {{ isEditMode == true ? "Edit Menu" : "Add Menu" }}
                                </h5>
                                <button @click="resetForm"
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
                                        <input v-model="form.price" type="number" min="0" class="form-control"
                                            :class="{ 'is-invalid': formErrors.price }" placeholder="e.g., 0.00" />
                                        <small v-if="formErrors.price" class="text-danger">
                                            {{ formErrors.price[0] }}
                                        </small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Category</label>
                                        <Select v-model="form.category_id" :options="categories" optionLabel="name"
                                            optionValue="id" placeholder="Select Category" class="w-100" appendTo="self"
                                            :autoZIndex="true" :baseZIndex="2000"
                                            @update:modelValue="form.subcategory = ''"
                                            :class="{ 'is-invalid': formErrors.category_id }" />
                                        <small v-if="formErrors.category_id" class="text-danger">
                                            {{ formErrors.category_id[0] }}
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
                                        <br />
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
                                        <br />
                                        <small v-if="formErrors.tags" class="text-danger">
                                            {{ formErrors.tags[0] }}
                                        </small>
                                    </div>
                                </div>

                                <!-- Image -->
                                <div class="row g-3 mt-2 align-items-center">
                                    <div class="col-md-4">
                                        <div class="logo-card" :class="{
                                            'is-invalid': formErrors.image,
                                        }">
                                            <div class="logo-frame"
                                                @click="form.imageUrl ? openImageModal() : showCropper = true">
                                                <img v-if="form.imageUrl" :src="form.imageUrl" alt="Menu Item Image" />
                                                <div v-else class="placeholder">
                                                    <i class="bi bi-image"></i>
                                                </div>
                                            </div>

                                            <small class="text-muted mt-2 d-block">Upload Image</small>

                                            <!-- Image Cropper Modal -->
                                            <ImageCropperModal :show="showCropper" @close="showCropper = false"
                                                @cropped="onCropped" />

                                            <!-- Image Preview/Zoom Modal (Optional) -->
                                            <ImageZoomModal v-if="showImageModal" :show="showImageModal"
                                                :image="previewImage" @close="showImageModal = false" />


                                        </div>
                                    </div>

                                    <div class="mt-4 col-sm-6 col-md-8">
                                        <button class="btn btn-outline-primary rounded-pill px-4"
                                            :class="{ 'is-invalid': formErrors.ingredients }" data-bs-toggle="modal"
                                            data-bs-target="#addIngredientModal">
                                            {{ isEditMode == true ? "Ingredients" : "+ Add Ingredients" }}
                                        </button>
                                        <small v-if="formErrors.ingredients" class="text-danger d-block mt-1">
                                            {{ formErrors.ingredients[0] }}
                                        </small>

                                        <div v-if="i_cart.length > 0" class="mt-3">

                                            <!-- Nutrition Card -->
                                            <div class="card border rounded-4 mb-3">
                                                <div class="p-3 fw-semibold">
                                                    <div class="mb-2">Total Nutrition (Menu)</div>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <span class="badge bg-primary px-3 py-2 rounded-pill">
                                                            Calories: {{ i_totalNutrition.calories }}
                                                        </span>
                                                        <span class="badge bg-success px-3 py-2 rounded-pill">
                                                            Protein: {{ i_totalNutrition.protein }} g
                                                        </span>
                                                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">
                                                            Carbs: {{ i_totalNutrition.carbs }} g
                                                        </span>
                                                        <span class="badge bg-danger px-3 py-2 rounded-pill">
                                                            Fat: {{ i_totalNutrition.fat }} g
                                                        </span>
                                                    </div>
                                                </div>

                                            </div>

                                            <!-- Ingredients Table -->
                                            <div class="card border rounded-4">
                                                <div class="table-responsive">
                                                    <table class="table align-middle mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th>Name</th>
                                                                <th>Qty</th>
                                                                <th>Unit Price</th>
                                                                <th>Cost</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr v-for="(ing, idx) in i_cart" :key="idx">
                                                                <td>{{ ing.name }}</td>
                                                                <td>{{ ing.qty }}</td>
                                                                <td>{{ ing.unitPrice }}</td>
                                                                <td>{{ ing.cost }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="p-3 fw-semibold text-end">
                                                    Total Cost: {{ money(i_total) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>


                                </div>

                                <div class="mt-4">
                                    <button class="btn btn-primary rounded-pill px-5 py-2" :disabled="submitting"
                                        @click="form.id ? submitEdit() : submitProduct()">
                                        <span>{{ form.id ? 'Update Product' : 'Add Product' }}</span>
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


                <!-- Add Ingredient Modal -->
                <div class="modal fade" id="addIngredientModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content rounded-4">
                            <div class="modal-header">
                                <h5 class="modal-title fw-semibold">Add Ingredients</h5>

                                <button
                                    class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                                    data-bs-dismiss="modal" aria-label="Close"  @click="showMenuModal" title="Close">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                               
                            </div>

                            <div class="modal-body">
                                <div class="row g-4">
                                    <!-- Left side -->
                                    <div class="col-lg-5">
                                        <div class="search-wrap mb-2">
                                            <input v-model="i_search" type="text" class="form-control search-input"
                                                placeholder="Search Items..." />
                                        </div>

                                        <div v-for="it in i_displayInv" :key="it.id"
                                            class="card shadow-sm border-0 rounded-4 mb-3">
                                            <div class="card-body">
                                                <div class="d-flex align-items-start gap-3">
                                                    <img :src="it.image_url ? `${it.image_url}` : '/default.png'"
                                                        class="rounded"
                                                        style="width: 56px; height: 56px; object-fit: cover;" />
                                                    <div class="flex-grow-1">
                                                        <div class="fw-semibold">{{ it.name }}</div>
                                                        <div class="text-muted small">Category: {{ it.category.name }}
                                                        </div>
                                                        <div class="text-muted small">Unit: {{ it.unit }}</div>
                                                        <div class="small mt-2 text-muted">
                                                            Calories: {{ it.nutrition?.calories || 0 }},
                                                            Protein: {{ it.nutrition?.protein || 0 }},
                                                            Carbs: {{ it.nutrition?.carbs || 0 }} ,
                                                            Fat: {{ it.nutrition?.fat || 0 }}
                                                        </div>

                                                    </div>

                                                    <button class="btn btn-primary px-3" @click="addIngredient(it)">{{
                                                        i_cart.some(c => c.id === it.id) ? 'Update' : 'Add'}}</button>
                                                </div>

                                                <div class="row g-2 mt-3">
                                                    <div class="col-4">
                                                        <label class="small text-muted">Quantity</label>
                                                        <input v-model.number="it.qty" type="number" min="0"
                                                            class="form-control form-control-sm" />
                                                        <small v-if="formErrors.qty" class="text-danger">
                                                            {{ formErrors.qty }}
                                                        </small>
                                                    </div>
                                                    <div class="col-4">
                                                        <label class="small text-muted">Unit Price</label>
                                                        <input v-model.number="it.unitPrice" type="number" min="0"
                                                            class="form-control form-control-sm" />
                                                        <small v-if="formErrors.unitPrice" class="text-danger">
                                                            {{ formErrors.unitPrice }}
                                                        </small>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right side -->
                                    <div class="col-lg-7">
                                        <div class="card border rounded-4 mb-3">
                                            <div class="p-3 fw-semibold">
                                                <div>Total Nutrition (Menu)</div>
                                                <div>Calories: {{ i_totalNutrition.calories }}</div>
                                                <div>Protein: {{ i_totalNutrition.protein }} g</div>
                                                <div>Carbs: {{ i_totalNutrition.carbs }} g</div>
                                                <div>Fat: {{ i_totalNutrition.fat }} g</div>
                                            </div>
                                        </div>

                                        <div class="card border rounded-4">
                                            <div class="table-responsive">
                                                <table class="table align-middle mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Name</th>
                                                            <th>Qty</th>
                                                            <th>Unit Price</th>
                                                            <th>Cost</th>
                                                            <th class="text-end">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(ing, idx) in i_cart" :key="idx">
                                                            <td>{{ ing.name }}</td>
                                                            <td>{{ ing.qty }}</td>
                                                            <td>{{ ing.unitPrice }}</td>
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
                                            <button class="btn btn-primary px-5" @click="saveIngredients">Done</button>

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


.dropdown-menu {
    position: absolute !important;
    z-index: 1050 !important;
}

/* Ensure the table container doesn't clip the dropdown */
.table-container {
    overflow: visible !important;
}
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
