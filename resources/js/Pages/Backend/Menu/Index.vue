<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted, onUpdated, watch, toRaw } from "vue";
import Select from "primevue/select";
import MultiSelect from "primevue/multiselect";
import { toast } from "vue3-toastify";
import { jsPDF } from "jspdf";
import autoTable from "jspdf-autotable";
import * as XLSX from "xlsx";
import { useFormatters } from '@/composables/useFormatters'
import FilterModal from "@/Components/FilterModal.vue";
import { nextTick } from "vue";
import ImageZoomModal from "@/Components/ImageZoomModal.vue";
import ImportFile from "@/Components/importFile.vue";
import ImageCropperModal from "@/Components/ImageCropperModal.vue";
import { Head } from "@inertiajs/vue3";
import Dropdown from 'primevue/dropdown'

const { formatMoney, formatCurrencySymbol, formatNumber, dateFmt } = useFormatters()
import {
    Package,
    XCircle,
    Plus,
    Pencil,
    CheckCircle,

} from "lucide-vue-next";
import Pagination from "@/Components/Pagination.vue";

const props = defineProps({
    allergies: {
        type: Array,
    },
    tags: {
        type: Array,
    },
    categories: {
        type: Array,
    },
    meals: {
        type: Array,
    },
    variantGroups: {
        type: Array,
        default: () => []
    },
    addonGroups: {
        type: Array,
        default: () => []
    },
    menuItems: {
        type: Array,
        default: () => []
    }
});

const components = {
    FilterModal,
};

const taxableOptions = ref([
    { label: "Yes", value: 1 },
    { label: "No", value: 0 },
]);

const variants = ref([])
const selectedVariants = ref([])

const addons = ref([])
const selectedAddonGroup = ref(null)
const activeTab = ref('simple');
const isVariantMode = ref(false);
const selectedVariantForIngredients = ref(null);
const variantIngredients = ref({});

const variantResaleConfig = ref({});

const exportOption = ref(null)

const exportOptions = [
    { label: 'PDF', value: 'pdf' },
    { label: 'Excel', value: 'excel' },
    { label: 'CSV', value: 'csv' },
    { label: 'Allergen PDF', value: 'allergens' },
]

const onExportChange = (e) => {
    if (e.value) {
        onDownload(e.value)
        exportOption.value = null // reset after click
    }
}
const loadAddons = () => {
    if (form.value.addon_group_ids && form.value.addon_group_ids.length > 0) {
        // Collect all addons from all selected groups
        const allAddons = [];

        form.value.addon_group_ids.forEach(groupId => {
            const group = props.addonGroups?.find(g => g.id === groupId);
            if (group && group.addons) {
                allAddons.push(...group.addons);
            }
        });

        // Remove duplicates based on addon id
        addons.value = allAddons.filter((addon, index, self) =>
            index === self.findIndex((a) => a.id === addon.id)
        );

        if (!Array.isArray(form.value.addon_ids)) {
            form.value.addon_ids = [];
        }
    } else {
        addons.value = [];
        form.value.addon_ids = [];
    }
}

const allSelectedAddons = computed(() => {
    return addons.value;
});

const labelColors = [
    { name: "Meat", value: "#E74C3C" },
    { name: "Vegetables", value: "#27AE60" },
    { name: "Seafood", value: "#2980B9" },
    { name: "Drinks", value: "#8E44AD" },
    { name: "Staples", value: "#E67E22" },
]

const inventoryItems = ref([]);
const showAllergyModal = ref(false);
const selectedTypes = ref({});
const selectedAllergies = ref([]);

// ================== Ingredients =====================
const i_search = ref("");
const i_cart = ref([]);

const fetchInventory = async () => {
    try {
        const response = await axios.get("/inventory/api-inventories?all=1");
        inventoryItems.value = response.data.data;
    } catch (error) {
        console.error("Error fetching inventory:", error);
    }
};



function saveSelectedAllergies() {
    selectedAllergies.value = Object.keys(selectedTypes.value)
        .filter((key) => selectedTypes.value[key])
        .map((key) => ({
            id: parseInt(key),
            name: props.allergies.find((a) => a.id == key)?.name,
            type: selectedTypes.value[key],
        }));

    form.value.allergies = [...selectedAllergies.value];
    showAllergyModal.value = false;

    setTimeout(() => {
        const menuModal = new bootstrap.Modal(
            document.getElementById("addItemModal")
        );
        menuModal.show();
    }, 300);
}

const cancelAllergySelection = () => {
    showAllergyModal.value = false;

    setTimeout(() => {
        const menuModal = new bootstrap.Modal(
            document.getElementById("addItemModal")
        );
        menuModal.show();
    }, 300);
};

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

const openAllergyModal = () => {
    const menuModal = bootstrap.Modal.getInstance(
        document.getElementById("addItemModal")
    );
    if (menuModal) {
        menuModal.hide();
    }

    showAllergyModal.value = true;
};

function round2(x) {
    return Math.round(x * 100) / 100;
}

function addIngredient(item) {
    const qty = Number(item.qty || 0);
    const price =
        item.unitPrice !== "" && item.unitPrice != null
            ? Number(item.unitPrice)
            : Number(item.defaultPrice || 0);

    formErrors.value[item.id] = {};

    if (!qty || qty <= 0) formErrors.value[item.id].qty = "Enter a valid quantity.";

    if (Object.keys(formErrors.value[item.id]).length > 0) {
        const messages = Object.values(formErrors.value[item.id]).join("\n");
        toast.error(messages, { style: { whiteSpace: "pre-line" } });
        return;
    }

    const found = i_cart.value.find((r) => r.id === item.id);

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
            nutrition: item.nutrition || {
                calories: 0,
                protein: 0,
                carbs: 0,
                fat: 0,
            },
        });
    }

    if (!isEditMode.value) {
        item.qty = null;
        item.unitPrice = null;
    }
}

function removeIngredient(idx) {
    const ing = i_cart.value[idx];
    if (!ing) return;

    i_cart.value.splice(idx, 1);

    const found = i_displayInv.value.find((i) => i.id === ing.id);
    if (found) {
        found.qty = null;
        found.unitPrice = null;
        found.cost = null;
    }
}


const i_totalNutrition = computed(() => {
    const totals = i_cart.value.reduce(
        (acc, ing) => {
            const qty = Number(ing.qty) || 0;

            acc.calories += Number(ing.nutrition?.calories || 0) * qty;
            acc.protein += Number(ing.nutrition?.protein || 0) * qty;
            acc.carbs += Number(ing.nutrition?.carbs || 0) * qty;
            acc.fat += Number(ing.nutrition?.fat || 0) * qty;

            return acc;
        },
        { calories: 0, protein: 0, carbs: 0, fat: 0 }
    );

    // Round all values to 2 decimal places
    return {
        calories: round2(totals.calories),
        protein: round2(totals.protein),
        carbs: round2(totals.carbs),
        fat: round2(totals.fat)
    };
});


const showMenuModal = () => {
    const menuModal = new bootstrap.Modal(
        document.getElementById("addItemModal")
    );
    menuModal.show();
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
    fetchInventory();
    fetchMenus(1);
    const filterModal = document.getElementById('menuFilterModal');
    if (filterModal) {
        filterModal.addEventListener('hidden.bs.modal', () => {
            // Reset filters to last applied state when modal closes
            filters.value = { ...appliedFilters.value };
        });
    }
});

const inventories = ref(props.inventories?.data || []);
const items = computed(() => inventories.value);

// const fetchInventories = async () => {
//     try {
//         const res = await axios.get("inventory/api-inventories");

//         const apiItems = res.data.data || [];

//         inventories.value = await Promise.all(
//             apiItems.map(async (item) => {
//                 const stockRes = await axios.get(
//                     `/stock_entries/total/${item.id}`
//                 );
//                 const stockData = stockRes.data.total?.original || {};
//                 return {
//                     ...item,
//                     availableStock: stockData.available || 0,
//                     stockValue: stockData.stockValue || 0,
//                     minAlert: stockData.minAlert || 0,
//                 };
//             })
//         );
//     } catch (err) {
//         console.error(err);
//     }
// };

const fetchInventories = async () => {
    try {
        const res = await axios.get("inventory/api-inventories");
        inventories.value = res.data.data;
    } catch (err) {
        console.error(err);
    }
};


const menuItems = ref([]);
const currentPage = ref(1);
const perPage = ref(10);
const totalItems = ref(0);
const paginationLinks = ref([]);
const isLoading = ref(false);
const counts = ref({
    total: 0,
    active: 0,
    inactive: 0,
});


const fetchMenus = async (page = 1) => {
    isLoading.value = true;
    try {
        // Single API call that returns both menus and deals
        const res = await axios.get("/api/menu/items", {
            params: {
                page: page,
                per_page: perPage.value,
                search: q.value.trim() || null,
                category: filters.value.category || null,
                status: filters.value.status !== "" ? filters.value.status : null,
                sort_by: filters.value.sortBy || null,
                price_min: filters.value.priceMin || null,
                price_max: filters.value.priceMax || null,
                date_from: filters.value.dateFrom || null,
                date_to: filters.value.dateTo || null,
            }
        });

        // Data is already combined on the backend
        menuItems.value = res.data.data || [];

        if (res.data.pagination) {
            currentPage.value = res.data.pagination.current_page;
            totalItems.value = res.data.pagination.total;
            paginationLinks.value = res.data.pagination.links;
        }

        if (res.data.counts) {
            counts.value = res.data.counts;
        }

    } catch (err) {
        console.error("❌ Error fetching items:", err);
        toast.error("Failed to fetch items");
    } finally {
        isLoading.value = false;
    }
};

const handlePageChange = (url) => {
    if (!url || isLoading.value) return;

    const urlParams = new URLSearchParams(url.split('?')[1]);
    const page = urlParams.get('page');

    if (page) {
        fetchMenus(parseInt(page));
    }
};

onMounted(() => {
    fetchInventories();
    fetchMenus();
    // fetchDeals();
});

/* ===================== Toolbar: Search + Filter ===================== */
const q = ref("");
const searchKey = ref(Date.now());
const inputId = `search-${Math.random().toString(36).substr(2, 9)}`;
const isReady = ref(false);

const defaultMenuFilters = {
    sortBy: "",
    category: "",
    status: "",
    priceMin: null,
    priceMax: null,
    dateFrom: "",
    dateTo: "",
};

const filters = ref({ ...defaultMenuFilters });
const appliedFilters = ref({ ...defaultMenuFilters });

// REPLACE THIS
const filteredItems = computed(() => {
    // Backend already handles filtering, just return the data
    return menuItems.value;
});

let searchTimeout = null;

watch(q, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage.value = 1;
        fetchMenus(1);
    }, 500);
});
watch(
    () => appliedFilters.value,
    () => {
        fetchMenus(1);
    },
    { deep: true }
);

const sortedItems = computed(() => {
    return filteredItems.value; // Backend handles sorting
});

const filterOptions = computed(() => ({
    sortOptions: [
        { value: "price_desc", label: "Price: High to Low" },
        { value: "price_asc", label: "Price: Low to High" },
        { value: "name_asc", label: "Name: A to Z" },
        { value: "name_desc", label: "Name: Z to A" },
    ],
    categories: props.categories || [],
    statusOptions: [
        { value: 1, label: "Active" },
        { value: 0, label: "Inactive" },
    ],
}));



const handleFilterApply = () => {
    appliedFilters.value = { ...filters.value };
    currentPage.value = 1;
    fetchMenus(1);
    const modal = bootstrap.Modal.getInstance(
        document.getElementById("menuFilterModal")
    );
    modal?.hide();
};
const handleFilterClear = () => {
    filters.value = { ...defaultMenuFilters };
    appliedFilters.value = { ...defaultMenuFilters };
    currentPage.value = 1;
    fetchMenus(1);
};

/* ===================== KPIs ===================== */
const totalMenuItems = computed(() => counts.value.total);
const activeMenuItems = computed(() => counts.value.active);
const deactiveMenuItems = computed(() => counts.value.inactive);


const kpis = computed(() => [
    {
        label: "Total Items",
        value: totalMenuItems.value ?? 0,
        icon: Package,
        iconBg: "bg-soft-success",
        iconColor: "text-success",
    },
    {
        label: "Active Items",
        value: activeMenuItems.value ?? 0,
        icon: CheckCircle,
        iconBg: "bg-soft-danger",
        iconColor: "text-success",
    },
    {
        label: "Inactive Items",
        value: deactiveMenuItems.value ?? 0,
        icon: XCircle,
        iconBg: "bg-soft-warning",
        iconColor: "text-danger",
    },
]);

onMounted(() => window.feather?.replace());
onUpdated(() => window.feather?.replace());



const subcatMap = ref({
    Poultry: ["Chicken", "Broiler", "Wings", "Breast"],
    Produce: ["Tomatoes", "Onions", "Potatoes"],
    Grains: ["Rice", "Wheat", "Oats"],
    Grocery: ["Oil", "Spices", "Sugar"],
    Meat: ["Beef", "Mutton", "Veal"],
});

const subcatOptions = computed(() =>
    (subcatMap.value[form.value.category] || []).map((s) => ({
        name: s,
        value: s,
    }))
);

const formErrors = ref({});
function resetErrors() {
    formErrors.value = {};
}

const deals = ref([]);

const form = ref({
    name: "",
    category_id: null,
    meals: [],
    label_color: null,
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
    is_taxable: null,
    variant_group_id: '',
    variant_prices: {},
    addon_group_ids: [],
    addon_ids: [],
    addon_group_constraints: null,
    is_saleable: false,
    resale_type: null,
    resale_value: null,

    deal_name: "",
    deal_price: "",
    menu_item_ids: [],
});


const resaleTypeOptions = ref([
    { label: "Flat Amount", value: "flat" },
    { label: "Percentage", value: "percentage" },
]);

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
const handleSubmit = () => {
    if (activeTab.value === 'deals') {
        form.value.id ? submitEditDeal() : submitDeal();
    } else {
        form.value.id ? submitEdit() : submitProduct();
    }
};
// ============================================================
// ADD DEAL SUBMISSION FUNCTION
// ============================================================

const submitDeal = async () => {
    submitting.value = true;
    formErrors.value = {};

    try {
        const formData = new FormData();
        formData.append("name", form.value.deal_name.trim());
        formData.append("price", form.value.deal_price);
        formData.append("description", form.value.description || "");
        formData.append("status", 1);

        if (form.value.is_taxable !== null) {
            formData.append("is_taxable", String(form.value.is_taxable));
        }

        if (form.value.label_color) {
            formData.append("label_color", form.value.label_color);
        }

        if (form.value.category_id) {
            formData.append("category_id", form.value.category_id);
        }

        form.value.meals.forEach((mealId, i) => {
            formData.append(`meals[${i}]`, mealId);
        });

        form.value.allergies.forEach((a, i) => {
            formData.append(`allergies[${i}]`, a.id);
            formData.append(`allergy_types[${i}]`, a.type === 'Contain' ? 1 : 0);
        });

        form.value.tags.forEach((id, i) => formData.append(`tags[${i}]`, id));

        // Addons
        if (form.value.addon_group_ids && form.value.addon_group_ids.length > 0) {
            form.value.addon_group_ids.forEach((groupId, index) => {
                formData.append(`addon_group_ids[${index}]`, groupId);
            });
        }

        if (form.value.addon_ids && form.value.addon_ids.length > 0) {
            form.value.addon_ids.forEach((addonId, index) => {
                formData.append(`addon_ids[${index}]`, addonId);
            });
        }

        // Updated: Send menu items with quantities
        if (Array.isArray(form.value.menu_item_ids)) {
            form.value.menu_item_ids.forEach((item, index) => {
                if (typeof item === 'object') {
                    formData.append(`menu_item_ids[${index}][id]`, item.id);
                    formData.append(`menu_item_ids[${index}][qty]`, item.qty);
                } else {
                    formData.append(`menu_item_ids[${index}][id]`, item);
                    formData.append(`menu_item_ids[${index}][qty]`, 1);
                }
            });
        }

        if (form.value.imageFile) {
            formData.append("image", form.value.imageFile);
        }

        await axios.post("/deals", formData, {
            headers: { "Content-Type": "multipart/form-data" },
        });

        toast.success("Deal created successfully");
        resetForm();
        await fetchDeals();
        bootstrap.Modal.getInstance(document.getElementById("addItemModal"))?.hide();
    } catch (err) {
        console.error("❌ Error saving:", err.response?.data);

        if (err?.response?.status === 422 && err.response.data?.errors) {
            formErrors.value = err.response.data.errors;
            const errorMessages = Object.values(err.response.data.errors)
                .flat()
                .join("\n");
            toast.error(errorMessages || "Validation failed");
        } else {
            toast.error("Failed to save deal: " + (err.response?.data?.message || err.message));
        }
    } finally {
        submitting.value = false;
    }
};


const editDeal = (deal) => {
    console.log('Edit Deals', deal);
    if (form.value.imageUrl && form.value.imageUrl.startsWith("blob:")) {
        URL.revokeObjectURL(form.value.imageUrl);
    }

    isEditMode.value = true;
    activeTab.value = 'deals';

    // ✅ Extract addon_group_id from deal_addon_groups array
    const addonGroupIds = deal.deal_addon_groups?.map(
        g => g.group_id
    ) || [];

    const addonIds = [];

    deal.deal_addon_groups?.forEach(group => {
        if (group.addons) {
            addonIds.push(...group.addons.map(a => a.id));
        }
    });

    form.value = {
        id: deal.id,
        deal_name: deal.name,
        deal_price: deal.price,
        description: deal.description || "",
        menu_item_ids: deal.menu_items?.map(item => ({
            id: item.id,
            qty: item.qty ?? 0
        })) || [],
        imageFile: null,
        imageUrl: deal.image_url || null,
        is_taxable: deal.is_taxable ?? 0,
        label_color: deal.label_color || null,
        category_id: deal.category_id || null,
        meals: deal.meals?.map(m => m.id) || [],

        // ✅ Store only IDs for form submission (validation expects IDs)
        allergies: deal.allergies?.map(a => a.id) || [],
        tags: deal.tags?.map(t => t.id) || [],

        // ✅ Addon fields
        addon_group_ids: addonGroupIds,
        addon_ids: addonIds,
        addon_group_constraints: null,

        // Not used for deals
        variant_group_id: '',
        variant_prices: {},
        is_saleable: false,
        resale_type: null,
        resale_value: null,
        name: "",
        price: "",
    };

    // ✅ Load allergies with types for the modal display
    if (deal.allergies && deal.allergies.length > 0) {
        selectedAllergies.value = deal.allergies.map(a => {
            const rawType = a.pivot?.type ?? a.type ?? 1;
            return {
                id: a.id,
                name: a.name,
                type: Number(rawType) === 1 ? 'Contain' : 'Trace',
            };
        });

        // ✅ Populate selectedTypes for radio buttons in modal
        selectedTypes.value = {};
        selectedAllergies.value.forEach(a => {
            selectedTypes.value[a.id] = a.type;
        });

        // ✅ Update form.allergies to match selectedAllergies structure for submission
        form.value.allergies = selectedAllergies.value;
    } else {
        selectedAllergies.value = [];
        selectedTypes.value = {};
    }

    // ✅ Load addons after setting addon_group_id
    if (form.value.addon_group_ids && form.value.addon_group_ids.length > 0) {
        nextTick(() => {
            loadAddons();
        });
    }

    const modal = new bootstrap.Modal(document.getElementById("addItemModal"));
    modal.show();
};


// Submit Edit Deals
const submitEditDeal = async () => {
    submitting.value = true;
    formErrors.value = {};

    try {
        const formData = new FormData();
        formData.append("name", form.value.deal_name.trim());
        formData.append("price", form.value.deal_price);
        formData.append("description", form.value.description || "");
        formData.append("is_taxable", String(form.value.is_taxable ?? 0));

        if (form.value.label_color) {
            formData.append("label_color", form.value.label_color);
        }

        if (form.value.category_id) {
            formData.append("category_id", form.value.category_id);
        }

        // Meals
        form.value.meals.forEach((mealId, i) => {
            formData.append(`meals[${i}]`, mealId);
        });

        // ✅ MISSING: Allergies with types
        form.value.allergies.forEach((a, i) => {
            formData.append(`allergies[${i}]`, a.id);
            formData.append(`allergy_types[${i}]`, a.type === 'Contain' ? 1 : 0);
        });

        // ✅ MISSING: Tags
        form.value.tags.forEach((id, i) => formData.append(`tags[${i}]`, id));

        // ✅ MISSING: Addons
        // ✅ MISSING: Addons
        if (form.value.addon_group_ids && form.value.addon_group_ids.length > 0) {
            form.value.addon_group_ids.forEach((groupId, index) => {
                formData.append(`addon_group_ids[${index}]`, groupId);
            });
        }

        if (form.value.addon_ids && form.value.addon_ids.length > 0) {
            form.value.addon_ids.forEach((addonId, index) => {
                formData.append(`addon_ids[${index}]`, addonId);
            });
        }

        // Menu items with quantities
        if (Array.isArray(form.value.menu_item_ids)) {
            form.value.menu_item_ids.forEach((item, index) => {
                if (typeof item === 'object') {
                    formData.append(`menu_item_ids[${index}][id]`, item.id);
                    formData.append(`menu_item_ids[${index}][qty]`, item.qty);
                } else {
                    formData.append(`menu_item_ids[${index}][id]`, item);
                    formData.append(`menu_item_ids[${index}][qty]`, 1);
                }
            });
        }

        // Image
        if (form.value.imageFile) {
            formData.append("image", form.value.imageFile);
        }

        formData.append("_method", "PUT");

        await axios.post(`/deals/${form.value.id}`, formData, {
            headers: { "Content-Type": "multipart/form-data" },
        });

        toast.success("Deal updated successfully");
        resetForm();
        await fetchDeals();
        await fetchMenus();

        const modal = bootstrap.Modal.getInstance(document.getElementById("addItemModal"));
        modal?.hide();
    } catch (err) {
        console.error("❌ Update failed:", err.response?.data);

        if (err?.response?.status === 422 && err.response.data?.errors) {
            formErrors.value = err.response.data.errors;
            const errorMessages = Object.values(err.response.data.errors)
                .flat()
                .join("\n");
            toast.error(errorMessages || "Validation failed");
        } else {
            toast.error("Failed to update deal: " + (err.response?.data?.message || err.message));
        }
    } finally {
        submitting.value = false;
    }
};

// ==================== TOGGLE DEAL STATUS ====================
const toggleDealStatus = async (deal) => {
    try {
        console.log('Toggling status for deal:', deal);
        const newStatus = deal.status === 1 ? 0 : 1;
        await axios.patch(`/deals/${deal.id}/status`, { status: newStatus });

        const dealIndex = deals.value.findIndex(d => d.id === deal.id);
        if (dealIndex !== -1) {
            deals.value[dealIndex].status = newStatus;
        }

        toast.success(`Deal is now ${newStatus === 1 ? "Active" : "Inactive"}`);
    } catch (err) {
        console.error("Failed to toggle status", err);
        toast.error("Failed to update status");
        await fetchDeals(currentPage.value);
    }
};


// ==================== UPDATED: Submit Product ====================

const submitProduct = async () => {
    submitting.value = true;
    formErrors.value = {};

    try {
        const formData = new FormData();
        formData.append("name", form.value.name.trim());

        if (form.value.category_id) {
            formData.append("category_id", form.value.category_id);
        }

        if (form.value.label_color) {
            formData.append("label_color", form.value.label_color);
        }

        formData.append("description", form.value.description || "");
        formData.append("is_taxable", String(form.value.is_taxable ?? 0));
        if (activeTab.value === 'variant' && Object.keys(variantIngredients.value).length > 0) {
            const totalNutrition = Object.values(variantIngredients.value)
                .flat()
                .reduce(
                    (acc, ing) => {
                        const qty = Number(ing.qty || 0);
                        acc.calories += Number(ing.nutrition?.calories || 0) * qty;
                        acc.protein += Number(ing.nutrition?.protein || 0) * qty;
                        acc.carbs += Number(ing.nutrition?.carbs || 0) * qty;
                        acc.fat += Number(ing.nutrition?.fat || 0) * qty;
                        return acc;
                    },
                    { calories: 0, protein: 0, carbs: 0, fat: 0 }
                );

            formData.append("nutrition[calories]", totalNutrition.calories);
            formData.append("nutrition[fat]", totalNutrition.fat);
            formData.append("nutrition[protein]", totalNutrition.protein);
            formData.append("nutrition[carbs]", totalNutrition.carbs);
            let metadataIndex = 0;
            Object.entries(variantMetadata.value).forEach(([variantId, meta]) => {
                formData.append(`variant_metadata[${metadataIndex}][name]`, meta.name);
                formData.append(`variant_metadata[${metadataIndex}][price]`, meta.price);
                const resaleConfig = variantResaleConfig.value[variantId];

                if (resaleConfig && resaleConfig.is_saleable) {
                    formData.append(`variant_metadata[${metadataIndex}][is_saleable]`, 1);
                    if (resaleConfig.resale_type && resaleConfig.resale_type !== 'null') {
                        formData.append(`variant_metadata[${metadataIndex}][resale_type]`, resaleConfig.resale_type);
                    }
                    if (resaleConfig.resale_value !== null && resaleConfig.resale_value !== '' && !isNaN(resaleConfig.resale_value)) {
                        formData.append(`variant_metadata[${metadataIndex}][resale_value]`, parseFloat(resaleConfig.resale_value));
                    }
                } else {
                    formData.append(`variant_metadata[${metadataIndex}][is_saleable]`, 0);
                }
                const ingredients = variantIngredients.value[variantId] || [];
                ingredients.forEach((ing, ingIndex) => {
                    formData.append(`variant_ingredients[${metadataIndex}][${ingIndex}][inventory_item_id]`, ing.id);
                    formData.append(`variant_ingredients[${metadataIndex}][${ingIndex}][qty]`, ing.qty);
                    formData.append(`variant_ingredients[${metadataIndex}][${ingIndex}][unit_price]`, ing.unitPrice);
                    formData.append(`variant_ingredients[${metadataIndex}][${ingIndex}][cost]`, ing.cost);
                });

                metadataIndex++;
            });

            formData.append("price", 0);
        } else {
            formData.append("nutrition[calories]", i_totalNutrition.value.calories);
            formData.append("nutrition[fat]", i_totalNutrition.value.fat);
            formData.append("nutrition[protein]", i_totalNutrition.value.protein);
            formData.append("nutrition[carbs]", i_totalNutrition.value.carbs);

            if (form.value.price !== "" && form.value.price !== null) {
                formData.append("price", form.value.price);
            }
            if (form.value.is_saleable) {
                formData.append("is_saleable", 1);

                if (form.value.resale_type && form.value.resale_type !== 'null') {
                    formData.append("resale_type", form.value.resale_type);
                }
                if (form.value.resale_value !== null && form.value.resale_value !== '' && !isNaN(form.value.resale_value)) {
                    formData.append("resale_value", parseFloat(form.value.resale_value));
                }
            } else {
                formData.append("is_saleable", 0);
            }
            i_cart.value.forEach((ing, i) => {
                formData.append(`ingredients[${i}][inventory_item_id]`, ing.id);
                formData.append(`ingredients[${i}][qty]`, ing.qty);
                formData.append(`ingredients[${i}][unit_price]`, ing.unitPrice);
                formData.append(`ingredients[${i}][cost]`, ing.cost);
            });
        }
        form.value.allergies.forEach((a, i) => {
            formData.append(`allergies[${i}]`, a.id);
            formData.append(`allergy_types[${i}]`, a.type === 'Contain' ? 1 : 0);
        });

        form.value.meals.forEach((mealId, i) => {
            formData.append(`meals[${i}]`, mealId);
        });

        form.value.tags.forEach((id, i) => formData.append(`tags[${i}]`, id));
        if (form.value.imageFile) {
            formData.append("image", form.value.imageFile);
        }

        // Addons
        // Addons - REPLACE THIS SECTION
        if (form.value.addon_group_ids && form.value.addon_group_ids.length > 0) {
            form.value.addon_group_ids.forEach((groupId, index) => {
                formData.append(`addon_group_ids[${index}]`, groupId);
            });
        }

        if (form.value.addon_ids && form.value.addon_ids.length > 0) {
            form.value.addon_ids.forEach((addonId, index) => {
                formData.append(`addon_ids[${index}]`, addonId);
            });
        }

        try {
            const response = await axios.post("/menu", formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });

            toast.success("Menu created successfully");
            resetForm();
            await fetchMenus();
            bootstrap.Modal.getInstance(
                document.getElementById("addItemModal")
            )?.hide();
        } catch (err) {
            console.error("❌ Error saving:", err.response?.data);

            if (err?.response?.status === 422 && err.response.data?.errors) {
                formErrors.value = err.response.data.errors;
                const errorMessages = Object.values(err.response.data.errors)
                    .flat()
                    .join("\n");
                toast.error(errorMessages || "Validation failed");
            } else {
                toast.error("Failed to save menu item: " + (err.response?.data?.message || err.message));
            }
        }
    } catch (error) {
        console.error("❌ Error:", error);
        toast.error("An error occurred: " + error.message);
    } finally {
        submitting.value = false;
    }
};

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

watch(variantIngredients, (newIngredients) => {
    // Ensure every variant has a resale config entry
    Object.keys(newIngredients).forEach(variantId => {
        if (!variantResaleConfig.value[variantId]) {
            variantResaleConfig.value[variantId] = {
                is_saleable: false,
                resale_type: null,
                resale_value: null
            };
        }
    });
}, { deep: true });

// ===============Edit item ==================
const savedNutrition = ref({ calories: 0, protein: 0, carbs: 0, fat: 0 });
const isEditMode = ref(false);

const i_displayInv = computed(() => {
    return i_filteredInv.value.map((inv) => {
        const found = i_cart.value.find((c) => c.id === inv.id);
        return found
            ? {
                ...inv,
                qty: found.qty,
                unitPrice: found.unitPrice,
                cost: found.cost,
            }
            : {
                ...inv,
                qty: 0,
                unitPrice: 0,
                cost: 0,
            };
    });
});

const editItem = (item) => {
    console.log('Edit Item', item);
    if (form.value.imageUrl && form.value.imageUrl.startsWith("blob:")) {
        URL.revokeObjectURL(form.value.imageUrl);
    }

    isEditMode.value = true;
    const itemData = toRaw(item);
    const hasVariants = itemData.variants &&
        Array.isArray(itemData.variants) &&
        itemData.variants.length > 0;

    // Set the active tab based on menu type
    activeTab.value = hasVariants ? 'variant' : 'simple';

    console.log("Item data: " , itemData);
    form.value = {
        id: itemData.id,
        name: itemData.name,
        price: itemData.price,
        category_id: itemData.category?.id || null,
        meals: itemData.meals?.map((m) => m.id) || [],
        description: itemData.description,
        label_color: itemData.label_color,
        is_taxable: itemData.is_taxable ?? 0,
        ingredients: itemData.ingredients || [],
        allergies: itemData.allergies?.map((a) => a.id) || [],
        tags: itemData.tags?.map((t) => t.id) || [],
        imageFile: null,
        imageUrl: itemData.image_url || null,
        addon_group_ids: itemData.addon_group_relations?.map(
            rel => Number(rel.addon_group_id)
        ) || [],

        addon_ids: itemData.addons?.map(a => a.id) || [],

        is_saleable: itemData.is_saleable === 1 || itemData.is_saleable === true ? true : false,
        resale_type: itemData.resale_type || null,
        resale_value: itemData.resale_value || null,
    };
    if (hasVariants) {
        variantIngredients.value = {};
        variantMetadata.value = {};
        variantResaleConfig.value = {};
        let maxVariantId = 0;

        itemData.variants.forEach(variant => {
            const variantId = variant.id;
            maxVariantId = Math.max(maxVariantId, variantId);
            variantMetadata.value[variantId] = {
                name: variant.name,
                price: parseFloat(variant.price || 0)
            };

            variantResaleConfig.value[variantId] = {
                is_saleable: variant.is_saleable === 1 || variant.is_saleable === true ? true : false,
                resale_type: variant.resale_type || null,
                resale_value: variant.resale_value ? parseFloat(variant.resale_value) : null
            };
            if (variant.ingredients && Array.isArray(variant.ingredients)) {
                variantIngredients.value[variantId] = variant.ingredients.map(ing => {
                    const quantity = parseFloat(ing.quantity || ing.qty || 0);
                    const cost = parseFloat(ing.cost || 0);
                    const unitPrice = quantity > 0 ? cost / quantity : 0;

                    const inv = inventoryItems.value.find(
                        item => item.id === (ing.inventory_item_id || ing.id)
                    );

                    return {
                        id: ing.inventory_item_id || ing.id,
                        name: ing.product_name || ing.name || inv?.name || "—",
                        category: inv?.category || ing.category || { name: "" },
                        qty: quantity,
                        unitPrice: unitPrice,
                        cost: cost,
                        nutrition: ing.nutrition || inv?.nutrition || {
                            calories: 0,
                            protein: 0,
                            carbs: 0,
                            fat: 0,
                        },
                    };
                });
            } else {
                variantIngredients.value[variantId] = [];
            }
        });
        variantIdCounter.value = maxVariantId + 1;
    }

    if (itemData.addon_group_ids && itemData.addon_group_ids.length > 0) {
        loadAddons();
    }

    savedNutrition.value = {
        calories: parseFloat(itemData.nutrition?.calories || 0),
        protein: parseFloat(itemData.nutrition?.protein || 0),
        carbs: parseFloat(itemData.nutrition?.carbs || 0),
        fat: parseFloat(itemData.nutrition?.fat || 0),
    };

    // Load allergies
    if (itemData.allergies && itemData.allergies.length > 0) {
        selectedAllergies.value = itemData.allergies.map(a => {
            const rawType = a.pivot?.type ?? a.type ?? 1;
            return {
                id: a.id,
                name: a.name,
                type: Number(rawType) === 1 ? 'Contain' : 'Trace',
            };
        });

        selectedTypes.value = {};
        selectedAllergies.value.forEach(a => {
            selectedTypes.value[a.id] = a.type;
        });
    } else {
        selectedAllergies.value = [];
        selectedTypes.value = {};
    }
    if (!hasVariants) {
        i_cart.value = (itemData.ingredients || []).map((ing) => {
            const quantity = parseFloat(ing.quantity || ing.qty || 0);
            const cost = parseFloat(ing.cost || 0);
            const unitPrice = quantity > 0 ? cost / quantity : 0;

            const inv = inventoryItems.value.find(
                (inv) => inv.id === (ing.inventory_item_id || ing.id)
            );

            return {
                id: ing.inventory_item_id || ing.id,
                name: ing.product_name || ing.name || inv?.name || "—",
                category: inv?.category || ing.category || { name: "" },
                qty: quantity,
                unitPrice: unitPrice,
                cost: cost,
                nutrition: ing.nutrition
                    ? typeof ing.nutrition === "string"
                        ? JSON.parse(ing.nutrition)
                        : ing.nutrition
                    : inv?.nutrition || {
                        calories: 0,
                        protein: 0,
                        carbs: 0,
                        fat: 0,
                    },
            };
        });
    } else {
        i_cart.value = [];
    }

    const modal = new bootstrap.Modal(document.getElementById("addItemModal"));
    modal.show();
};
// ==================== UPDATED submitEdit FUNCTION ====================
const submitEdit = async () => {
    submitting.value = true;
    formErrors.value = {};

    try {
        const formData = new FormData();

        formData.append("name", form.value.name.trim());

        if (form.value.category_id) {
            formData.append("category_id", form.value.category_id);
        }

        formData.append("description", form.value.description || "");
        formData.append("label_color", form.value.label_color || "");
        formData.append("is_taxable", String(form.value.is_taxable ?? 0));

        // Handle nutrition and ingredients based on active tab
        if (activeTab.value === 'variant' && Object.keys(variantIngredients.value).length > 0) {
            const totalNutrition = Object.values(variantIngredients.value)
                .flat()
                .reduce(
                    (acc, ing) => {
                        const qty = Number(ing.qty || 0);
                        acc.calories += Number(ing.nutrition?.calories || 0) * qty;
                        acc.protein += Number(ing.nutrition?.protein || 0) * qty;
                        acc.carbs += Number(ing.nutrition?.carbs || 0) * qty;
                        acc.fat += Number(ing.nutrition?.fat || 0) * qty;
                        return acc;
                    },
                    { calories: 0, protein: 0, carbs: 0, fat: 0 }
                );

            formData.append("nutrition[calories]", totalNutrition.calories);
            formData.append("nutrition[fat]", totalNutrition.fat);
            formData.append("nutrition[protein]", totalNutrition.protein);
            formData.append("nutrition[carbs]", totalNutrition.carbs);
            let metadataIndex = 0;
            Object.entries(variantMetadata.value).forEach(([variantId, meta]) => {
                formData.append(`variant_metadata[${metadataIndex}][id]`, variantId);
                formData.append(`variant_metadata[${metadataIndex}][name]`, meta.name);
                formData.append(`variant_metadata[${metadataIndex}][price]`, meta.price);

                // FIX: Get resale config and send only if is_saleable is true
                const resaleConfig = variantResaleConfig.value[variantId];

                if (resaleConfig && resaleConfig.is_saleable) {
                    // Send as 1 for boolean
                    formData.append(`variant_metadata[${metadataIndex}][is_saleable]`, 1);

                    // Only send resale_type and resale_value if they have valid values
                    if (resaleConfig.resale_type && resaleConfig.resale_type !== 'null') {
                        formData.append(`variant_metadata[${metadataIndex}][resale_type]`, resaleConfig.resale_type);
                    }
                    if (resaleConfig.resale_value !== null && resaleConfig.resale_value !== '' && !isNaN(resaleConfig.resale_value)) {
                        formData.append(`variant_metadata[${metadataIndex}][resale_value]`, parseFloat(resaleConfig.resale_value));
                    }
                } else {
                    // Not saleable
                    formData.append(`variant_metadata[${metadataIndex}][is_saleable]`, 0);
                }

                // Add ingredients for this variant
                const ingredients = variantIngredients.value[variantId] || [];
                ingredients.forEach((ing, ingIndex) => {
                    formData.append(`variant_ingredients[${metadataIndex}][${ingIndex}][inventory_item_id]`, ing.id);
                    formData.append(`variant_ingredients[${metadataIndex}][${ingIndex}][qty]`, ing.qty);
                    formData.append(`variant_ingredients[${metadataIndex}][${ingIndex}][unit_price]`, ing.unitPrice);
                    formData.append(`variant_ingredients[${metadataIndex}][${ingIndex}][cost]`, ing.cost);
                });

                metadataIndex++;
            });

            formData.append("price", 0); // Don't need base price for variants
        } else {
            //  SIMPLE MENU SECTION
            formData.append("price", form.value.price || 0);

            formData.append("nutrition[calories]", i_totalNutrition.value.calories);
            formData.append("nutrition[fat]", i_totalNutrition.value.fat);
            formData.append("nutrition[protein]", i_totalNutrition.value.protein);
            formData.append("nutrition[carbs]", i_totalNutrition.value.carbs);

            // Simple menu resale
            if (form.value.is_saleable) {
                formData.append("is_saleable", 1);

                if (form.value.resale_type && form.value.resale_type !== 'null') {
                    formData.append("resale_type", form.value.resale_type);
                }
                if (form.value.resale_value !== null && form.value.resale_value !== '' && !isNaN(form.value.resale_value)) {
                    formData.append("resale_value", parseFloat(form.value.resale_value));
                }
            } else {
                formData.append("is_saleable", 0);
            }

            // Simple ingredients
            i_cart.value.forEach((ing, i) => {
                formData.append(`ingredients[${i}][inventory_item_id]`, ing.id);
                formData.append(`ingredients[${i}][qty]`, ing.qty);
                formData.append(`ingredients[${i}][unit_price]`, ing.unitPrice);
                formData.append(`ingredients[${i}][cost]`, ing.cost);
            });
        }

        // Allergies
        selectedAllergies.value.forEach((a, i) => {
            formData.append(`allergies[${i}]`, a.id);
            const typeValue = a.type === 'Contain' ? 1 : 0;
            formData.append(`allergy_types[${i}]`, typeValue);
        });

        // Tags
        form.value.tags.forEach((id, i) => formData.append(`tags[${i}]`, id));

        // Meals
        form.value.meals.forEach((mealId, i) => {
            formData.append(`meals[${i}]`, mealId);
        });

        // Image
        if (form.value.imageFile) {
            formData.append("image", form.value.imageFile);
        }

        // Addons
        // Addons
        if (form.value.addon_group_ids && form.value.addon_group_ids.length > 0) {
            form.value.addon_group_ids.forEach((groupId, index) => {
                formData.append(`addon_group_ids[${index}]`, groupId);
            });
        }

        if (form.value.addon_ids && form.value.addon_ids.length > 0) {
            form.value.addon_ids.forEach((addonId, index) => {
                formData.append(`addon_ids[${index}]`, addonId);
            });
        }

        formData.append("_method", "PUT");

        try {
            const response = await axios.post(`/menu/${form.value.id}`, formData, {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            });

            toast.success("Menu updated successfully");
            resetForm();
            await fetchMenus();

            const modal = bootstrap.Modal.getInstance(
                document.getElementById("addItemModal")
            );
            modal?.hide();
        } catch (err) {
            console.error("❌ Update failed:", err.response?.data);

            if (err?.response?.status === 422 && err.response.data?.errors) {
                formErrors.value = err.response.data.errors;
                const errorMessages = Object.values(err.response.data.errors)
                    .flat()
                    .join("\n");
                toast.error(errorMessages || "Validation failed");
            } else {
                toast.error("Failed to update menu item: " + (err.response?.data?.message || err.message));
            }
        }
    } catch (error) {
        console.error("❌ Error:", error);
        toast.error("An error occurred: " + error.message);
    } finally {
        submitting.value = false;
    }
};
const toggleStatus = async (item) => {
    try {
        const newStatus = item.status === 1 ? 0 : 1;
        await axios.patch(`/menu/${item.id}/status`, { status: newStatus });
        toast.success(
            `Menu item is now ${newStatus === 1 ? "Active" : "Inactive"}`
        );
        await fetchMenus(currentPage.value);
    } catch (err) {
        console.error("Failed to toggle status", err);
        toast.error("Failed to update status");
    }
};

// ==================== UPDATED: Reset Form ====================
function resetForm() {
    if (form.value.imageUrl && form.value.imageUrl.startsWith("blob:")) {
        URL.revokeObjectURL(form.value.imageUrl);
    }
    form.value = {
        name: "",
        category_id: null,
        meals: [],
        label_color: null,
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
        is_taxable: null,
        variant_group_id: '',
        variant_prices: {},
        addon_group_ids: [],
        addon_ids: [],
        addon_group_constraints: null,
        is_saleable: false,
        resale_type: null,
        resale_value: null,

        deal_name: "",
        deal_price: "",
        menu_item_ids: [],
    };
    showCropper.value = false;
    showImageModal.value = false;
    previewImage.value = null;
    formErrors.value = {};
    isEditMode.value = false;
    selectedAllergies.value = [];
    selectedTypes.value = {};
    i_cart.value = [];
    activeTab.value = 'simple';
    isVariantMode.value = false;
    selectedVariantForIngredients.value = null;
    variantIngredients.value = {};
    variantMetadata.value = {};
    variantResaleConfig.value = {};
    variantForm.value = { name: '', price: null };
    variants.value = [];
    addons.value = [];
    m_cart.value = [];
}
const openAddMenuModal = () => {
    resetForm();
    resetErrors();
};



const fetchAllMenusForExport = async () => {
    try {
        isLoading.value = true;

        console.log('🔄 Fetching all menu items for export...');

        const res = await axios.get("/api/menu/items", {
            params: {
                export: 'all',
                search: q.value.trim() || null,
                category: filters.value.category || null,
                status: filters.value.status !== "" ? filters.value.status : null,
                sort_by: filters.value.sortBy || null,
                price_min: filters.value.priceMin || null,
                price_max: filters.value.priceMax || null,
                date_from: filters.value.dateFrom || null,
                date_to: filters.value.dateTo || null,
            }
        });
        const allData = res.data.data || [];
        console.log(`✅ Fetched ${allData.length} menu items for export`);
        return allData;
    } catch (err) {
        console.error('❌ Error fetching export data:', err);
        toast.error("Failed to load data for export");
        return [];
    } finally {
        isLoading.value = false;
    }
};
const onDownload = async (type) => {
    console.log('🔍 Starting download process...');
    console.log('🔍 Current menuItems.value length:', menuItems.value?.length);
    console.log('🔍 Total items in system:', totalItems.value);

    if (!menuItems.value || menuItems.value.length === 0) {
        toast.error("No menu data to download");
        return;
    }

    try {
        isLoading.value = true;
        toast.info("Fetching all menu items for export...", { autoClose: 2000 });
        const allData = await fetchAllMenusForExport();
        console.log('✅ Data ready for export:', allData.length, 'items');
        if (!allData.length) {
            toast.error("No menu items found to download");
            isLoading.value = false;
            return;
        }
        if (type === "pdf") {
            downloadPDF(allData);
        } else if (type === "excel") {
            downloadExcel(allData);
        } else if (type === "csv") {
            downloadCSV(allData);
        } else if (type === "allergens") {
            downloadAllergen(allData);
        } else {
            toast.error("Invalid download type");
        }

    } catch (error) {
        console.error("❌ Download failed:", error);
        toast.error(`Download failed: ${error.message}`);
    } finally {
        isLoading.value = false;
    }
};
const downloadCSV = (data) => {
    try {
        const headers = [
            "Item Name",
            "Category",
            "Description",
            "Price",
            "Nutrition",
            "Allergies",
            "Tags",
        ];
        const rows = data.map((s) => {
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
            const category =
                typeof s.category === "object"
                    ? s.category?.name || ""
                    : s.category || "";
            const allergies = Array.isArray(s.allergies)
                ? s.allergies
                    .map((a) => {
                        const name = a.name || a;
                        const type = a.pivot?.type ?? a.type ?? null;

                        if (type !== null && type !== undefined) {
                            const typeLabel =
                                String(type) === "1" || type === 1
                                    ? "Contain"
                                    : "Trace";
                            return `${name} (${typeLabel})`;
                        }
                        return name;
                    })
                    .join(", ")
                : s.allergies || "";
            const tags = Array.isArray(s.tags)
                ? s.tags.map((t) => t.name || t).join(", ")
                : s.tags || "";

            return [
                `"${(s.name || "").replace(/"/g, '""')}"`,
                `"${category.replace(/"/g, '""')}"`,
                `"${(s.description || "").replace(/"/g, '""')}"`,
                `"${s.price || ""}"`,
                `"${nutritionStr.replace(/"/g, '""')}"`,
                `"${allergies.replace(/"/g, '""')}"`,
                `"${tags.replace(/"/g, '""')}"`,
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
            `menu_items_${new Date().toISOString().split("T")[0]}.csv`
        );
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);

        toast.success(`CSV downloaded successfully (${data.length} items)`, { autoClose: 2500 });
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
        doc.setFontSize(18);
        doc.setFont("helvetica", "bold");
        doc.text("Menu Items Report", 65, 20);
        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        const currentDate = new Date().toLocaleString();
        doc.text(`Generated on: ${currentDate}`, 14, 28);
        doc.text(`Total Menu Items: ${data.length}`, 14, 34);
        const tableColumns = [
            "Item Name",
            "Category",
            "Description",
            "Price",
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
            if (typeof nutri === "object") {
                const wantedKeys = ["calories", "protein", "fat", "carbs"];
                return wantedKeys
                    .map((key) =>
                        nutri[key] !== undefined ? `${key}: ${nutri[key]}` : null
                    )
                    .filter(Boolean)
                    .join("; ");
            }
            return String(nutri ?? "");
        };
        const tableRows = data.map((s) => {
            const category =
                typeof s.category === "object"
                    ? s.category?.name || ""
                    : s.category || "";

            const allergies = Array.isArray(s.allergies)
                ? s.allergies
                    .map((a) => {
                        const name = a.name || a;
                        const type = a.pivot?.type ?? a.type ?? null;

                        if (type !== null && type !== undefined) {
                            const typeLabel =
                                String(type) === "1" || type === 1
                                    ? "Contain"
                                    : "Trace";
                            return `${name} (${typeLabel})`;
                        }
                        return name;
                    })
                    .join(", ")
                : s.allergies || "";

            const tags = Array.isArray(s.tags)
                ? s.tags.map((t) => t.name || t).join(", ")
                : s.tags || "";

            const nutritionStr = formatNutrition(s.nutrition);

            return [
                s.name || "",
                category,
                s.description || "",
                s.price || "",
                nutritionStr,
                allergies,
                tags,
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
            alternateRowStyles: { fillColor: [245, 245, 245] },
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
        const fileName = `menu_items_${new Date()
            .toISOString()
            .split("T")[0]}.pdf`;
        doc.save(fileName);
        toast.success(`PDF downloaded successfully (${data.length} items)`, { autoClose: 2500 });
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
        const worksheetData = data.map((s) => {
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

            const category =
                typeof s.category === "object"
                    ? s.category?.name || ""
                    : s.category || "";

            const allergies = Array.isArray(s.allergies)
                ? s.allergies
                    .map((a) => {
                        const name = a.name || a;
                        const type = a.pivot?.type ?? a.type ?? null;

                        if (type !== null && type !== undefined) {
                            const typeLabel =
                                String(type) === "1" || type === 1
                                    ? "Contain"
                                    : "Trace";
                            return `${name} (${typeLabel})`;
                        }
                        return name;
                    })
                    .join(", ")
                : s.allergies || "";

            const tags = Array.isArray(s.tags)
                ? s.tags.map((t) => t.name || t).join(", ")
                : s.tags || "";

            return {
                "Item Name": s.name || "",
                Category: category,
                Description: s.description || "",
                Price: s.price || "",
                Nutrition: nutritionStr,
                Allergies: allergies,
                Tags: tags,
            };
        });
        const workbook = XLSX.utils.book_new();
        const worksheet = XLSX.utils.json_to_sheet(worksheetData);
        worksheet["!cols"] = [
            { wch: 20 },
            { wch: 20 },
            { wch: 30 },
            { wch: 10 },
            { wch: 30 },
            { wch: 25 },
            { wch: 25 },
        ];
        XLSX.utils.book_append_sheet(workbook, worksheet, "Menu Items");
        const metaData = [
            { Info: "Generated On", Value: new Date().toLocaleString() },
            { Info: "Total Records", Value: data.length },
            { Info: "Exported By", Value: "Menu Management System" },
        ];
        const metaSheet = XLSX.utils.json_to_sheet(metaData);
        XLSX.utils.book_append_sheet(workbook, metaSheet, "Report Info");
        const fileName = `menu_items_${new Date()
            .toISOString()
            .split("T")[0]}.xlsx`;
        XLSX.writeFile(workbook, fileName);

        toast.success(`Excel file downloaded successfully (${data.length} items)`, { autoClose: 2500 });
    } catch (error) {
        console.error("Excel generation error:", error);
        toast.error(`Excel generation failed: ${error.message}`, {
            autoClose: 5000,
        });
    }
};


const downloadAllergen = (data) => {
    try {
        const doc = new jsPDF("l", "mm", "a4");
        doc.setFontSize(20);
        doc.setFont("helvetica", "bold");
        doc.text("allergen", 14, 15);
        doc.setFontSize(16);
        doc.text("information", 14, 22);
        doc.setFontSize(18);
        doc.setFont("helvetica", "bold");
        doc.setTextColor(0, 102, 204);
        doc.text("10XGLOBAL", 65, 22);
        doc.setTextColor(0, 0, 0);
        const pageWidth = doc.internal.pageSize.width;
        const legendX = pageWidth - 125;
        const legendY = 8;

        doc.setFillColor(240, 240, 240);
        doc.rect(legendX - 2, legendY, 45, 18, 'F');

        doc.setFontSize(7);
        doc.setFont("helvetica", "bold");
        doc.text("SYMBOL", legendX, legendY + 4);
        doc.text("MEANING", legendX + 12, legendY + 4);

        doc.setFont("helvetica", "normal");
        doc.setFontSize(6);
        const checkX = legendX + 1;
        const checkY = legendY + 7;
        doc.setLineWidth(0.5);
        doc.setDrawColor(0, 0, 0);
        doc.line(checkX, checkY, checkX + 1, checkY + 1.5);
        doc.line(checkX + 1, checkY + 1.5, checkX + 3, checkY - 1);

        doc.text("contains allergen", legendX + 12, legendY + 9);

        doc.setFontSize(9);
        doc.text("*", legendX + 1, legendY + 14);
        doc.setFontSize(6);
        doc.text("may contain traces of allergen", legendX + 12, legendY + 14);

        const infoX = legendX + 48;
        doc.setFontSize(6);
        doc.text("• Always speak to a member of staff if you have any allergies", infoX, legendY + 3);
        doc.text("• Items are prepared in the same kitchen,", infoX, legendY + 7);
        doc.text("  therefore we cannot guarantee that products are", infoX, legendY + 10);
        doc.text("  free from allergen and cross-contamination", infoX, legendY + 13);
        const groupedData = {};
        data.forEach(item => {
            const category = typeof item.category === "object"
                ? item.category?.name || "Other"
                : item.category || "Other";

            if (!groupedData[category]) {
                groupedData[category] = [];
            }
            groupedData[category].push(item);
        });
        const allergenSet = new Set();
        data.forEach(item => {
            if (Array.isArray(item.allergies)) {
                item.allergies.forEach(a => {
                    const name = a.name || a;
                    allergenSet.add(name);
                });
            }
        });
        const allAllergens = Array.from(allergenSet).sort();

        const marginLeft = 14;
        const marginRight = 14;
        const availableWidth = pageWidth - marginLeft - marginRight;

        const categoryColWidth = 30;
        const itemNameColWidth = 45;
        const fixedColumnsWidth = categoryColWidth + itemNameColWidth;
        const allergenColumnsWidth = availableWidth - fixedColumnsWidth;
        const allergenColWidth = allergenColumnsWidth / allAllergens.length;
        const minAllergenColWidth = 6;
        const maxAllergenColWidth = 12;
        const finalAllergenColWidth = Math.max(minAllergenColWidth, Math.min(allergenColWidth, maxAllergenColWidth));


        const allergenFontSize = finalAllergenColWidth < 8 ? 6 : 7;
        const headerFontSize = finalAllergenColWidth < 8 ? 5 : 6;
        const tableData = [];

        Object.keys(groupedData).sort().forEach((category, categoryIndex) => {
            const items = groupedData[category];
            items.forEach((item, itemIndex) => {
                const row = [];
                if (itemIndex === 0) {
                    row.push({
                        content: category.toLowerCase(),
                        rowSpan: items.length,
                        styles: {
                            fontStyle: 'bold',
                            fontSize: 10,
                            halign: 'left',
                            valign: 'middle',
                            fillColor: [255, 255, 255]
                        }
                    });
                }
                row.push({
                    content: item.name || '',
                    styles: {
                        fontStyle: 'bold',
                        fontSize: 9
                    }
                });
                allAllergens.forEach(allergen => {
                    let symbol = '';
                    if (Array.isArray(item.allergies)) {
                        const allergyMatch = item.allergies.find(a => {
                            const name = a.name || a;
                            return name === allergen;
                        });

                        if (allergyMatch) {
                            const type = allergyMatch.pivot?.type ?? allergyMatch.type ?? null;
                            if (type !== null && type !== undefined) {
                                symbol = (String(type) === "1" || type === 1) ? 'TICK' : 'STAR';
                            } else {
                                symbol = '';
                            }
                        }
                    }
                    row.push(symbol);
                });

                tableData.push(row);
            });
        });
        const headerRow = ['', '', ...allAllergens];
        autoTable(doc, {
            head: [headerRow],
            body: tableData,
            startY: 35,
            styles: {
                fontSize: allergenFontSize,
                cellPadding: 1.5,
                halign: 'center',
                valign: 'middle',
                lineColor: [0, 0, 0],
                lineWidth: 0.1,
            },
            columnStyles: {
                0: {
                    halign: 'left',
                    cellWidth: categoryColWidth,
                    fontStyle: 'bold',
                    fontSize: 10
                },
                1: {
                    halign: 'left',
                    cellWidth: itemNameColWidth,
                    fontStyle: 'bold',
                    fontSize: 9
                },

                ...Object.fromEntries(
                    allAllergens.map((_, index) => [
                        index + 2,
                        { cellWidth: finalAllergenColWidth }
                    ])
                )
            },
            headStyles: {
                fillColor: [255, 255, 255],
                textColor: [0, 0, 0],
                fontStyle: 'bold',
                fontSize: headerFontSize,
                cellPadding: 1,
            },
            alternateRowStyles: {
                fillColor: [250, 250, 250]
            },
            margin: { left: marginLeft, right: marginRight, top: 28 },
            didParseCell: function (data) {

                if (data.section === 'head' && data.column.index > 1) {
                    data.cell.styles.minCellHeight = 45;

                    data.cell.text = [];
                }
                if (data.section === 'head' && (data.column.index === 0 || data.column.index === 1)) {
                    data.cell.text = [];
                }
                if (data.section === 'body' && (data.cell.raw === 'TICK' || data.cell.raw === 'STAR')) {
                    data.cell.text = [];
                }
            },
            didDrawCell: function (data) {

                if (data.section === 'body' && data.cell.raw === 'TICK') {
                    const { x, y, width, height } = data.cell;
                    const centerX = x + width / 2;
                    const centerY = y + height / 2;
                    const tickX = centerX - 1.5;
                    const tickY = centerY - 0.5;

                    doc.setDrawColor(0, 0, 0);
                    doc.setLineWidth(0.4);
                    doc.line(tickX, tickY, tickX + 0.8, tickY + 1.2);
                    doc.line(tickX + 0.8, tickY + 1.2, tickX + 2.5, tickY - 1);
                }
                if (data.section === 'body' && data.cell.raw === 'STAR') {
                    const { x, y, width, height } = data.cell;
                    const centerX = x + width / 2;
                    const centerY = y + height / 2;

                    doc.setFontSize(10);
                    doc.setFont("helvetica", "bold");
                    doc.setTextColor(0, 0, 0);
                    doc.text('*', centerX, centerY + 1, { align: 'center' });
                }
                if (data.section === 'head' && data.column.index > 1) {
                    const allergenName = allAllergens[data.column.index - 2];
                    if (allergenName) {
                        const x = data.cell.x + data.cell.width / 2;
                        const y = data.cell.y + data.cell.height - 3;

                        doc.saveGraphicsState();
                        doc.setFontSize(allergenFontSize);
                        doc.setFont("helvetica", "bold");
                        doc.text(allergenName, x, y, {
                            angle: 90,
                            align: 'left'
                        });
                        doc.restoreGraphicsState();
                    }
                }
            },
            didDrawPage: (td) => {
                const pageCount = doc.internal.getNumberOfPages();
                const pageHeight = doc.internal.pageSize.height;
                doc.setFontSize(7);
                doc.text(
                    `Page ${td.pageNumber} of ${pageCount}`,
                    14,
                    pageHeight - 8
                );
            },
        });
        const fileName = `allergen_information_${new Date()
            .toISOString()
            .split("T")[0]}.pdf`;
        doc.save(fileName);
        toast.success("PDF downloaded successfully", { autoClose: 2500 });
    } catch (error) {
        console.error("PDF generation error:", error);
        toast.error(`PDF generation failed: ${error.message}`, {
            autoClose: 5000,
        });
    }
};

const handleImport = (data) => {
    if (!data || data.length <= 1) {
        toast.error("The imported file is empty.");
        return;
    }

    const headers = data[0];

    const rows = data.slice(1);

    const itemsToImport = rows.map((row) => {
        let calories = 0, protein = 0, fat = 0, carbs = 0;
        if (row[4]) {
            const nutritionStr = row[4];
            const parts = nutritionStr.split(';').map(s => s.trim());
            parts.forEach(part => {
                const [key, value] = part.split(':').map(s => s.trim());
                const numValue = parseFloat(value) || 0;
                if (key === 'calories') calories = numValue;
                else if (key === 'protein') protein = numValue;
                else if (key === 'fat') fat = numValue;
                else if (key === 'carbs') carbs = numValue;
            });
        }

        const item = {
            name: row[0] || "",
            category: row[1] || "",
            description: row[2] || "",
            price: row[3] ? parseFloat(row[3]) : 0,
            calories: calories,
            protein: protein,
            fat: fat,
            carbs: carbs,
            allergies: row[5] ? row[5].trim() : "",
            tags: row[6] ? row[6].trim() : "",
            label_color: row[7] || null,
            is_taxable: row[8] ? parseInt(row[8]) : 0,
            is_saleable: row[9] ? parseInt(row[9]) : 0,
            resale_type: row[10] || null,
            resale_value: row[11] ? parseFloat(row[11]) : null,
            status: 1,
        };
        if (row[12] && row[12].trim() !== '') {
            try {
                item.variant_data = JSON.parse(row[12]);
            } catch (e) {
                console.error("Error parsing variant data:", e);
                item.variant_data = null;
            }
        }

        return item;
    });
    const itemNames = itemsToImport.map(item => item.name.trim().toLowerCase());
    const duplicatesInCSV = itemNames.filter((name, index) => itemNames.indexOf(name) !== index);

    if (duplicatesInCSV.length > 0) {
        toast.error(`Duplicate item names found in CSV: ${[...new Set(duplicatesInCSV)].join(", ")}`);
        return;
    }
    const existingMenuItemNames = menuItems.value.map(item => item.name.trim().toLowerCase());
    const duplicatesInTable = itemsToImport.filter(importItem =>
        existingMenuItemNames.includes(importItem.name.trim().toLowerCase())
    );

    if (duplicatesInTable.length > 0) {
        const duplicateNamesList = duplicatesInTable.map(item => item.name).join(", ");
        toast.error(`Menu items already exist in the table: ${duplicateNamesList}`);
        return;
    }

    axios
        .post("/api/menu/menu_items/import", { items: itemsToImport })
        .then(() => {
            toast.success("Items imported successfully");
            const importModal = document.querySelector('.modal.show');
            if (importModal) {
                const bsModal = bootstrap.Modal.getInstance(importModal);
                if (bsModal) {
                    bsModal.hide();
                }
            }

            // Force remove any lingering backdrops
            setTimeout(() => {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => backdrop.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }, 100);

            fetchInventories();
            fetchMenus();
        })
        .catch((err) => {
            console.error(err);
            toast.error(err.response?.data?.message || "Import failed");
            setTimeout(() => {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => backdrop.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }, 100);
        });
};




// ===============================================================
// COMPLETE VARIANT MANAGEMENT CODE - REPLACE YOUR EXISTING CODE
// ===============================================================
const variantForm = ref({
    name: '',
    price: null
});
const variantMetadata = ref({});
const variantIdCounter = ref(1);
function saveIngredients() {
    resetErrors();

    if (isVariantMode.value) {
        // Validate variant form
        if (!variantForm.value.name || !variantForm.value.name.trim()) {
            toast.error("Please enter a variant name");
            return;
        }
        if (variantForm.value.price === null || variantForm.value.price === '' || variantForm.value.price < 0) {
            toast.error("Please enter a valid variant price");
            return;
        }
        if (i_cart.value.length === 0) {
            toast.error("Please add at least one ingredient");
            return;
        }
        let variantId;
        if (selectedVariantForIngredients.value !== null) {
            variantId = selectedVariantForIngredients.value;
        } else {
            variantId = variantIdCounter.value++;
        }
        variantIngredients.value[variantId] = [...i_cart.value];
        if (!variantMetadata.value) {
            variantMetadata.value = {};
        }
        variantMetadata.value[variantId] = {
            name: variantForm.value.name,
            price: variantForm.value.price
        };


        if (!variantResaleConfig.value[variantId]) {
            variantResaleConfig.value[variantId] = {
                is_saleable: false,
                resale_type: null,
                resale_value: null
            };
        }

        toast.success(`Variant "${variantForm.value.name}" ingredients saved!`);
        i_cart.value = [];
        variantForm.value = { name: '', price: null };
        selectedVariantForIngredients.value = null;
    } else {
        form.value.ingredients = [...i_cart.value];
    }
    const ingModal = bootstrap.Modal.getInstance(
        document.getElementById("addIngredientModal")
    );
    ingModal.hide();
    setTimeout(() => {
        const menuModal = new bootstrap.Modal(document.getElementById("addItemModal"));
        menuModal.show();
    }, 300);
}
const openIngredientModal = (variantMode = false) => {
    isVariantMode.value = variantMode;

    if (variantMode) {
        // Only reset if not editing existing variant
        if (!selectedVariantForIngredients.value) {
            variantForm.value = {
                name: '',
                price: null
            };
            i_cart.value = [];
        }
    } else {
        if (isEditMode.value && form.value.ingredients && form.value.ingredients.length > 0) {
            i_cart.value = form.value.ingredients.map((ing) => {
                const quantity = parseFloat(ing.quantity || ing.qty || 0);
                const cost = parseFloat(ing.cost || 0);
                const unitPrice = quantity > 0 ? cost / quantity : 0;

                const inv = inventoryItems.value.find(
                    (item) => item.id === (ing.inventory_item_id || ing.id)
                );

                return {
                    id: ing.inventory_item_id || ing.id,
                    name: ing.product_name || ing.name || inv?.name || "—",
                    category: inv?.category || ing.category || { name: "" },
                    qty: quantity,
                    unitPrice: unitPrice,
                    cost: cost,
                    nutrition: ing.nutrition || inv?.nutrition || {
                        calories: 0,
                        protein: 0,
                        carbs: 0,
                        fat: 0,
                    },
                };
            });
        } else {
            i_cart.value = [];
        }
    }
    const menuModal = bootstrap.Modal.getInstance(document.getElementById("addItemModal"));
    if (menuModal) menuModal.hide();
    setTimeout(() => {
        const ingModal = new bootstrap.Modal(document.getElementById("addIngredientModal"));
        ingModal.show();
    }, 300);
};
const editVariantIngredients = (variantId) => {
    selectedVariantForIngredients.value = variantId;
    if (variantIngredients.value[variantId]) {
        i_cart.value = [...variantIngredients.value[variantId]];
    } else {
        i_cart.value = [];
    }
    if (variantMetadata.value[variantId]) {
        variantForm.value = {
            name: variantMetadata.value[variantId].name || '',
            price: variantMetadata.value[variantId].price || null
        };
    } else {
        variantForm.value = {
            name: getVariantName(variantId),
            price: getVariantPrice(variantId)
        };
    }
    isVariantMode.value = true;
    const menuModal = bootstrap.Modal.getInstance(document.getElementById("addItemModal"));
    if (menuModal) menuModal.hide();
    setTimeout(() => {
        const ingModal = new bootstrap.Modal(document.getElementById("addIngredientModal"));
        ingModal.show();
    }, 300);
};
const getVariantName = (variantId) => {
    if (variantMetadata.value[variantId]) {
        return variantMetadata.value[variantId].name;
    }
    const variant = variants.value.find(v => v.id === variantId);
    if (variant) {
        return variant.name;
    }

    return 'Unknown Variant';
};
const getVariantPrice = (variantId) => {
    if (variantMetadata.value[variantId]) {
        return variantMetadata.value[variantId].price || 0;
    }
    if (form.value.variant_prices && form.value.variant_prices[variantId]) {
        return form.value.variant_prices[variantId];
    }

    return 0;
};
const deleteVariantIngredients = (variantId) => {
    const variantName = getVariantName(variantId);

    if (confirm(`Are you sure you want to delete ingredients for "${variantName}"?`)) {
        delete variantIngredients.value[variantId];
        if (variantMetadata.value[variantId]) {
            delete variantMetadata.value[variantId];
        }

        toast.success("Variant ingredients deleted");
    }
};

const sampleMenuHeaders = [
    'Item Name',
    'Category',
    'Description',
    'Price',
    'Nutrition',
    'Allergies',
    'Tags',
    'Label Color',
    'Is Taxable',
    'Is Saleable',
    'Resale Type',
    'Resale Value',
    'Variant Data'
];

const sampleMenuData = [
    [
        'Chicken Burger',
        'Burgers',
        'Delicious chicken burger',
        '150',
        'calories: 250.00; protein: 20.00; fat: 10.00; carbs: 30.00',
        'Gluten',
        'Halal',
        '#E74C3C',
        '1',
        '0',
        '',
        '',
        ''
    ],
    [
        'Grilled Chicken',
        'Main Course',
        'Grilled chicken with spices',
        '0',
        'calories: 300.00; protein: 35.00; fat: 15.00; carbs: 0.00',
        'None',
        'Halal',
        '#27AE60',
        '1',
        '1',
        'flat',
        '50',
        JSON.stringify([
            {
                name: 'Small',
                price: 200,
                is_saleable: 1,
                resale_type: 'flat',
                resale_value: 40,
                ingredients: [
                    {
                        inventory_item_id: 1,
                        product_name: 'Chicken Breast',
                        quantity: 0.5,
                        cost: 100
                    }
                ]
            },
            {
                name: 'Large',
                price: 300,
                is_saleable: 1,
                resale_type: 'percentage',
                resale_value: 20,
                ingredients: [
                    {
                        inventory_item_id: 1,
                        product_name: 'Chicken Breast',
                        quantity: 0.75,
                        cost: 150
                    }
                ]
            }
        ])
    ]
];

const format = (val) => {
    return Number(val || 0).toFixed(2);
};


// menu code

// Add new state for menu cart (similar to ingredients cart)
const m_search = ref(""); // menu search
const m_cart = ref([]); // menu cart for deals

// Computed for filtered menu items
const m_filteredMenus = computed(() => {
    const t = m_search.value.trim().toLowerCase();
    if (!t) return menuItems.value;
    return menuItems.value.filter((m) =>
        [m.name, m.category?.name ?? ""]
            .join(" ")
            .toLowerCase()
            .includes(t)
    );
});

// Computed for display menu items with cart quantities
const m_displayMenus = computed(() => {
    return m_filteredMenus.value.map((menu) => {
        const found = m_cart.value.find((c) => c.id === menu.id);
        return found
            ? {
                ...menu,
                qty: found.qty,
            }
            : {
                ...menu,
                qty: 0,
            };
    });
});

// Computed total for selected menus
const m_totalPrice = computed(() => {
    return m_cart.value.reduce((sum, item) => {
        return sum + (Number(item.price) * Number(item.qty));
    }, 0);
});

// Add menu to cart
function addMenuToCart(menu) {
    const qty = Number(menu.qty || 0);

    formErrors.value[menu.id] = {};

    if (!qty || qty <= 0) {
        formErrors.value[menu.id].qty = "Enter a valid quantity.";
        toast.error("Enter a valid quantity.");
        return;
    }

    const found = m_cart.value.find((r) => r.id === menu.id);

    if (found) {
        found.qty = qty;
    } else {
        m_cart.value.push({
            id: menu.id,
            name: menu.name,
            category: menu.category,
            price: menu.price,
            qty: qty,
        });
    }

    if (!isEditMode.value) {
        menu.qty = null;
    }

    toast.success(`${menu.name} added to deal!`);
}

// Remove menu from cart
function removeMenuFromCart(idx) {
    const menu = m_cart.value[idx];
    if (!menu) return;

    m_cart.value.splice(idx, 1);

    const found = m_displayMenus.value.find((m) => m.id === menu.id);
    if (found) {
        found.qty = null;
    }
}

// Open Menu Selection Modal
const openMenuSelectionModal = () => {
    const mainModal = bootstrap.Modal.getInstance(
        document.getElementById("addItemModal")
    );
    if (mainModal) {
        mainModal.hide();
    }

    console.log('menuItems', menuItems.value);
    console.log('menu_item_ids', form.value.menu_item_ids);

    if (
        isEditMode.value &&
        Array.isArray(form.value.menu_item_ids) &&
        form.value.menu_item_ids.length > 0
    ) {
        m_cart.value = form.value.menu_item_ids
            .map((item) => {
                // ✅ Handle both formats
                const menuId = typeof item === 'object' ? item.id : item;
                const qty = typeof item === 'object' ? item.qty || 1 : 1;

                const menu = menuItems.value.find(m => m.id === menuId);

                // ✅ SAFETY CHECK
                if (!menu) return null;

                return {
                    id: menu.id,
                    name: menu.name,
                    category: menu.category,
                    price: menu.price,
                    qty,
                };
            })
            .filter(Boolean); // remove nulls
    }

    setTimeout(() => {
        const menuModalEl = document.getElementById("addMenuSelectionModal");
        if (!menuModalEl) {
            console.error("Menu modal element not found");
            return;
        }

        const menuModal = new bootstrap.Modal(menuModalEl);
        menuModal.show();
    }, 300);
};


// Save selected menus
function saveMenusToCart() {
    if (m_cart.value.length === 0) {
        toast.error("Please add at least one menu item");
        return;
    }

    // Store menu IDs in form
    form.value.menu_item_ids = m_cart.value.map(item => ({
        id: item.id,
        qty: item.qty
    }));

    toast.success("Menu items saved successfully!");

    // Close menu modal
    const menuModal = bootstrap.Modal.getInstance(
        document.getElementById("addMenuSelectionModal")
    );
    menuModal.hide();

    // Reopen main modal
    setTimeout(() => {
        const mainModal = new bootstrap.Modal(
            document.getElementById("addItemModal")
        );
        mainModal.show();
    }, 300);
}
</script>

<template>
    <Master>

        <Head title="Menus" />
        <div class="page-wrapper">
            <!-- Title -->
            <h4 class="mb-3">Items</h4>
            <!-- KPI Cards -->
            <div class="row g-3">
                <div v-for="c in kpis" :key="c.label" class="col-md-6 col-xl-4">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body d-flex align-items-center justify-content-between">
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
            <div class="card border-0 shadow-lg rounded-4 mt-0">
                <div class="card-body">
                    <!-- Toolbar -->
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">Items</h4>

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

                            <!-- Filter By -->
                            <div class="dropdown">
                                <FilterModal v-model="filters" title="Menu Items" modal-id="menuFilterModal"
                                    modal-size="modal-lg" :categories="filterOptions.categories"
                                    :sort-options="filterOptions.sortOptions"
                                    :status-options="filterOptions.statusOptions" :show-price-range="true"
                                    :show-date-range="false" :show-category="false" :show-stock-status="false"
                                    @apply="handleFilterApply" @clear="handleFilterClear" />
                            </div>

                            <!-- Add Item -->
                            <button data-bs-toggle="modal" @click="openAddMenuModal" data-bs-target="#addItemModal"
                                class="d-flex align-items-center gap-1 px-4 btn-sm py-2 rounded-pill btn btn-primary text-white">
                                <Plus class="w-4 h-4" /> Add Item
                            </button>

                            <ImportFile label="Import" :sampleHeaders="sampleMenuHeaders" :sampleData="sampleMenuData"
                                @on-import="handleImport" />

                            <!-- Download all -->
                            <Dropdown v-model="exportOption" :options="exportOptions" optionLabel="label"
                                optionValue="value" placeholder="Export" class="export-dropdown"
                                @change="onExportChange" />

                        </div>
                    </div>

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="border-top small text-muted">
                                <tr>
                                    <th>S.#</th>
                                    <th>Image</th>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- ✅ Loading State -->
                                <tr v-if="isLoading">
                                    <td colspan="8" class="text-center py-5">
                                        <div class="d-flex flex-column align-items-center justify-content-center">
                                            <div class="spinner-border text-primary mb-3" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <p class="text-muted mb-0">Loading Items...</p>
                                        </div>
                                    </td>
                                </tr>

                                <!-- ✅ Data Rows -->
                                <template v-else>
                                    <tr v-for="(item, idx) in sortedItems" :key="`${item.type}-${item.id}`">
                                        <td>
                                            {{ (currentPage - 1) * perPage + idx + 1 }}
                                        </td>
                                        <td>
                                            <ImageZoomModal :file="item.image_url || '/assets/img/default.png'"
                                                :alt="item.name" :width="50" :height="50"
                                                :custom_class="'cursor-pointer'" />
                                        </td>
                                        <td class="fw-semibold">
                                            {{ item.name }}
                                            <!-- Type Badge -->
                                            <span class="badge ms-2"
                                                :class="item.type === 'deal' ? 'bg-info' : 'bg-secondary'">
                                                {{ item.type === 'deal' ? 'Deal' : 'Menu' }}
                                            </span>
                                        </td>
                                        <td class="text-truncate" style="max-width: 260px">
                                            {{ item.category?.name || "—" }}
                                        </td>
                                        <td>
                                            {{ formatCurrencySymbol(item.price || 0, "GBP") }}
                                        </td>
                                        <td>
                                            <span v-if="item.status === 0"
                                                class="badge bg-red-600 rounded-pill d-inline-block text-center px-3 py-1">
                                                Inactive
                                            </span>
                                            <span v-else
                                                class="badge bg-success rounded-pill d-inline-block text-center px-3 py-1">
                                                Active
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-inline-flex align-items-center gap-3">
                                                <!-- Edit button - call different functions based on type -->
                                                <button @click="item.type === 'deal' ? editDeal(item) : editItem(item)"
                                                    data-bs-toggle="modal" title="Edit"
                                                    class="p-2 rounded-full text-blue-600 hover:bg-blue-100">
                                                    <Pencil class="w-4 h-4" />
                                                </button>

                                                <!-- Status toggle - call different functions based on type -->
                                                <ConfirmModal :title="'Confirm Status Change'"
                                                    :message="`Are you sure you want to ${item.status === 1 ? 'deactivate' : 'activate'} this ${item.type}?`"
                                                    :showStatusButton="true"
                                                    :status="item.status === 1 ? 'active' : 'inactive'"
                                                    @confirm="() => item.type === 'deal' ? toggleDealStatus(item) : toggleStatus(item)">
                                                    <template #trigger>
                                                        <button
                                                            class="relative inline-flex items-center w-8 h-4 rounded-full transition-colors duration-300 focus:outline-none"
                                                            :class="item.status === 1 ? 'bg-green-500 hover:bg-green-600' : 'bg-red-400 hover:bg-red-500'"
                                                            :title="item.status === 1 ? 'Set Inactive' : 'Set Active'">
                                                            <span
                                                                class="absolute left-0.5 top-0.5 w-3 h-3 bg-white rounded-full shadow transform transition-transform duration-300"
                                                                :class="item.status === 1 ? 'translate-x-4' : 'translate-x-0'"></span>
                                                        </button>
                                                    </template>
                                                </ConfirmModal>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="sortedItems.length === 0">
                                        <td colspan="8" class="text-center text-muted py-4">
                                            No items found.
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="paginationLinks.length > 0 && !isLoading" class="mt-4 d-flex justify-between">
                        <div class="text-center mt-3 text-sm text-gray-600">
                            Showing {{ (currentPage - 1) * perPage + 1 }} to
                            {{ Math.min(currentPage * perPage, totalItems) }} of
                            {{ totalItems }} results
                        </div>

                        <Pagination :pagination="paginationLinks" :isApiDriven="true"
                            @page-changed="handlePageChange" />


                    </div>

                </div>
            </div>

            <!-- ===================== Add New menu Item Modal ===================== -->
            <div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content rounded-4">
                        <div class="modal-header">
                            <h5 class="modal-title fw-semibold">
                                {{ isEditMode == true ? "Edit Item" : "Add Item" }}
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
                            <!-- Tabs Navigation -->
                            <ul class="nav nav-tabs mb-4" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" :class="{ active: activeTab === 'simple' }"
                                        @click="activeTab = 'simple'" type="button">
                                        Simple Item
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" :class="{ active: activeTab === 'variant' }"
                                        @click="activeTab = 'variant'" type="button">
                                        Variant Item
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" :class="{ active: activeTab === 'deals' }"
                                        @click="activeTab = 'deals'" type="button">
                                        Deals
                                    </button>
                                </li>
                            </ul>

                            <!-- Tab Content -->
                            <div class="tab-content">
                                <!-- ==================== SIMPLE MENU TAB ==================== -->
                                <div v-show="activeTab === 'simple'">
                                    <!-- top row -->
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Item Name</label>
                                            <input v-model="form.name" type="text" class="form-control" :class="{
                                                'is-invalid': formErrors.name,
                                            }" placeholder="e.g., Chicken Breast" />
                                            <small v-if="formErrors.name" class="text-danger">
                                                {{ formErrors.name[0] }}
                                            </small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label d-block">Base Price</label>
                                            <input v-model="form.price" type="number" min="0" class="form-control"
                                                :class="{
                                                    'is-invalid': formErrors.price,
                                                }" placeholder="e.g., 0.00" />
                                            <small v-if="formErrors.price" class="text-danger">
                                                {{ formErrors.price[0] }}
                                            </small>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Is this Taxable Item?</label>
                                            <Select v-model="form.is_taxable" :options="taxableOptions"
                                                optionLabel="label" optionValue="value" placeholder="Select Option"
                                                class="w-100" appendTo="self" :autoZIndex="true" :baseZIndex="2000"
                                                :class="{ 'is-invalid': formErrors.is_taxable }" />
                                            <small v-if="formErrors.is_taxable" class="text-danger">
                                                {{ formErrors.is_taxable[0] }}
                                            </small>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Is this Saleable Item?</label>
                                            <div class="d-flex gap-3">
                                                <div class="form-check">
                                                    <input v-model="form.is_saleable" :value="true" type="radio"
                                                        class="form-check-input" id="saleable_yes" name="is_saleable"
                                                        :checked="form.is_saleable === true" />
                                                    <label class="form-check-label" for="saleable_yes">
                                                        Yes
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input v-model="form.is_saleable" :value="false" type="radio"
                                                        class="form-check-input" id="saleable_no" name="is_saleable"
                                                        :checked="form.is_saleable === false" />
                                                    <label class="form-check-label" for="saleable_no">
                                                        No
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Resale Type Dropdown - Shows when "Yes" is selected -->
                                        <div v-if="form.is_saleable" class="col-md-6">
                                            <label class="form-label">Sale Type</label>
                                            <Select v-model="form.resale_type" :options="resaleTypeOptions"
                                                optionLabel="label" optionValue="value" placeholder="Select Resale Type"
                                                class="w-100" appendTo="self" :autoZIndex="true" :baseZIndex="2000"
                                                :class="{ 'is-invalid': formErrors.resale_type }" />
                                            <small v-if="formErrors.resale_type" class="text-danger">
                                                {{ formErrors.resale_type[0] }}
                                            </small>
                                        </div>

                                        <!-- Resale Value Input - Shows when resale type is selected -->
                                        <div v-if="form.is_saleable && form.resale_type" class="col-md-6">
                                            <label class="form-label">
                                                {{ form.resale_type === 'flat' ? 'Flat Amount' : 'Percentage' }}
                                            </label>
                                            <div class="input-group">
                                                <input v-model.number="form.resale_value" type="number" min="0"
                                                    :step="form.resale_type === 'flat' ? '0.01' : '0.1'"
                                                    class="form-control"
                                                    :class="{ 'is-invalid': formErrors.resale_value }"
                                                    :placeholder="`Enter ${form.resale_type === 'flat' ? 'flat amount' : 'percentage'}...`" />
                                                <span v-if="form.resale_type === 'percentage'" class="input-group-text">
                                                    %
                                                </span>
                                                <span v-else class="input-group-text">
                                                    {{ form.price ? '£' : '£' }}
                                                </span>
                                            </div>
                                            <small v-if="formErrors.resale_value" class="text-danger">
                                                {{ formErrors.resale_value[0] }}
                                            </small>
                                        </div>




                                        <div class="col-md-6">
                                            <label class="form-label d-block"> Label Color </label>
                                            <Select v-model="form.label_color" :options="labelColors" optionLabel="name"
                                                optionValue="value" placeholder="Select Label Color" class="w-100"
                                                appendTo="self" :autoZIndex="true" :baseZIndex="2000"
                                                :class="{ 'is-invalid': formErrors.label_color }" />
                                            <br />
                                            <small v-if="formErrors.label_color" class="text-danger">
                                                {{ formErrors.label_color[0] }}
                                            </small>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Category</label>
                                            <Select v-model="form.category_id" :options="categories" optionLabel="name"
                                                optionValue="id" placeholder="Select Category" class="w-100"
                                                appendTo="self" :autoZIndex="true" :baseZIndex="2000"
                                                @update:modelValue="
                                                    form.subcategory = ''
                                                    " :class="{
                                                        'is-invalid':
                                                            formErrors.category_id,
                                                    }" />
                                            <small v-if="formErrors.category_id" class="text-danger">
                                                {{ formErrors.category_id[0] }}
                                            </small>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label d-block">Meals</label>
                                            <MultiSelect v-model="form.meals" :options="meals" optionLabel="name"
                                                optionValue="id" filter placeholder="Select Meals"
                                                class="w-full md:w-80" appendTo="self" :autoZIndex="true"
                                                :baseZIndex="2000" :class="{ 'is-invalid': formErrors.meals }" />
                                            <small v-if="formErrors.meals" class="text-danger">
                                                {{ formErrors.meals[0] }}
                                            </small>
                                        </div>

                                        <!-- Subcategory -->
                                        <div class="col-md-6" v-if="subcatOptions.length">
                                            <label class="form-label">Subcategory</label>
                                            <Select v-model="form.subcategory" :options="subcatOptions"
                                                optionLabel="name" optionValue="value" placeholder="Select Subcategory"
                                                class="w-100" :appendTo="body" :autoZIndex="true" :baseZIndex="2000"
                                                :class="{
                                                    'is-invalid':
                                                        formErrors.subcategory,
                                                }" />
                                            <small v-if="formErrors.subcategory" class="text-danger">
                                                {{ formErrors.subcategory[0] }}
                                            </small>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Description</label>
                                            <textarea v-model="form.description" rows="4" class="form-control" :class="{
                                                'is-invalid':
                                                    formErrors.description,
                                            }" placeholder="Notes about this product"></textarea>
                                            <small v-if="formErrors.description" class="text-danger">
                                                {{ formErrors.description[0] }}
                                            </small>
                                        </div>
                                    </div>

                                    <div class="row g-4 mt-1">
                                        <!-- Allergies -->
                                        <div class="col-md-6">
                                            <label class="form-label d-block">Allergies</label>
                                            <div @click="openAllergyModal" class="form-control py-2 px-3"
                                                style="cursor:pointer;">
                                                <span v-if="selectedAllergies.length === 0" class="text-muted">
                                                    Select Allergies
                                                </span>
                                                <span v-else>
                                                    <span v-for="(item, index) in selectedAllergies" :key="index"
                                                        class="badge  text-dark mx-1 d-inline-flex align-items-center">
                                                        {{ item.name }}
                                                        <span v-if="item.type === 'Contain'" class="ms-1"
                                                            style="color: #22c55e; font-weight: bold;">
                                                            (✔️)
                                                        </span>
                                                        <span v-else class="ms-1"
                                                            style="color: #f97316; font-weight: bold;">
                                                            (*)
                                                        </span>
                                                    </span>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label d-block">Tags</label>
                                            <MultiSelect v-model="form.tags" :options="tags" optionLabel="name"
                                                optionValue="id" filter placeholder="Select Tags" class="w-full md:w-80"
                                                appendTo="self" :class="{
                                                    'is-invalid': formErrors.tags,
                                                }" />
                                            <br />
                                            <small v-if="formErrors.tags" class="text-danger">
                                                {{ formErrors.tags[0] }}
                                            </small>
                                        </div>

                                        <!-- REPLACE THIS SECTION -->
                                        <div class="col-md-12">
                                            <label class="form-label">Addon Groups</label>
                                            <MultiSelect v-model="form.addon_group_ids" :options="addonGroups"
                                                optionLabel="name" optionValue="id" filter
                                                placeholder="Select Addon Groups" class="w-100" appendTo="self"
                                                :autoZIndex="true" :baseZIndex="2000" @update:modelValue="loadAddons"
                                                :class="{ 'is-invalid': formErrors.addon_group_ids }" />
                                            <small v-if="formErrors.addon_group_ids" class="text-danger">
                                                {{ formErrors.addon_group_ids[0] }}
                                            </small>
                                        </div>

                                        <!-- Show addons from all selected groups -->
                                        <div v-if="addons && addons.length > 0" class="col-md-12">
                                            <label class="form-label">Select Addons</label>
                                            <MultiSelect v-model="form.addon_ids" :options="addons" optionLabel="name"
                                                optionValue="id" filter placeholder="Select Addons" class="w-full"
                                                appendTo="self" :autoZIndex="true" :baseZIndex="2000"
                                                :class="{ 'is-invalid': formErrors.addon_ids }">
                                                <template #option="slotProps">
                                                    <div
                                                        class="d-flex justify-content-between align-items-center w-100">
                                                        <span>{{ slotProps.option.name }}</span>
                                                        <span class="badge bg-primary">{{
                                                            formatCurrencySymbol(slotProps.option.price) }}</span>
                                                    </div>
                                                </template>
                                            </MultiSelect>
                                            <small v-if="formErrors.addon_ids" class="text-danger">
                                                {{ formErrors.addon_ids[0] }}
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Image -->
                                    <div class="row g-3 mt-2 align-items-center">
                                        <div class="col-md-4">
                                            <div class="logo-card" :class="{
                                                'is-invalid': formErrors.image,
                                            }">
                                                <div class="logo-frame" @click="
                                                    form.imageUrl
                                                        ? openImageModal()
                                                        : (showCropper = true)
                                                    ">
                                                    <img v-if="form.imageUrl" :src="form.imageUrl"
                                                        alt="Menu Item Image" />
                                                    <div v-else class="placeholder">
                                                        <i class="bi bi-image"></i>
                                                    </div>
                                                </div>
                                                <small class="text-muted mt-2 d-block">Upload Image</small>
                                                <ImageCropperModal :show="showCropper" @close="showCropper = false"
                                                    @cropped="onCropped" />
                                                <ImageZoomModal v-if="showImageModal" :show="showImageModal"
                                                    :image="previewImage" @close="showImageModal = false" />
                                            </div>
                                        </div>

                                        <div class="mt-4 col-sm-6 col-md-8">
                                            <button class="btn btn-primary rounded-pill px-4" :class="{
                                                'is-invalid':
                                                    formErrors.ingredients,
                                            }" @click="openIngredientModal(false)">
                                                {{ isEditMode == true ? "Ingredients" : "+ Add Ingredients" }}
                                            </button>
                                            <small v-if="formErrors.ingredients" class="text-danger d-block mt-1">
                                                {{ formErrors.ingredients[0] }}
                                            </small>

                                            <div v-if="i_cart.length > 0" class="mt-3">
                                                <!-- Nutrition Card -->
                                                <div class="card border rounded-4 mb-3">
                                                    <div class="p-3 fw-semibold">
                                                        <div class="mb-2">
                                                            Total Nutrition (Item)
                                                        </div>
                                                        <div class="d-flex flex-wrap gap-2">
                                                            <span class="badge bg-primary px-3 py-2 rounded-pill">
                                                                Calories: {{ i_totalNutrition.calories }}
                                                            </span>
                                                            <span class="badge bg-success px-3 py-2 rounded-pill">
                                                                Protein: {{ i_totalNutrition.protein }} g
                                                            </span>
                                                            <span
                                                                class="badge bg-warning text-dark px-3 py-2 rounded-pill">
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
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr v-for="(ing, idx) in i_cart" :key="idx">
                                                                    <td>{{ ing.name }}</td>
                                                                    <td>{{ ing.qty }}</td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                                <!-- ======================================================== -->
                                <!--                       VARIANT MENU TAB                                           -->
                                <!-- ======================================================== -->
                                <div v-show="activeTab === 'variant'">
                                    <!-- Same form fields as Simple Menu -->
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Item Name</label>
                                            <input v-model="form.name" type="text" class="form-control" :class="{
                                                'is-invalid': formErrors.name,
                                            }" placeholder="e.g., Chicken Breast" />
                                            <small v-if="formErrors.name" class="text-danger">
                                                {{ formErrors.name[0] }}
                                            </small>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Is this Taxable Item?</label>
                                            <Select v-model="form.is_taxable" :options="taxableOptions"
                                                optionLabel="label" optionValue="value" placeholder="Select Option"
                                                class="w-100" appendTo="self" :autoZIndex="true" :baseZIndex="2000"
                                                :class="{ 'is-invalid': formErrors.is_taxable }" />
                                            <small v-if="formErrors.is_taxable" class="text-danger">
                                                {{ formErrors.is_taxable[0] }}
                                            </small>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label d-block">Label Color</label>
                                            <Select v-model="form.label_color" :options="labelColors" optionLabel="name"
                                                optionValue="value" placeholder="Select Label Color" class="w-100"
                                                appendTo="self" :autoZIndex="true" :baseZIndex="2000"
                                                :class="{ 'is-invalid': formErrors.label_color }" />
                                            <small v-if="formErrors.label_color" class="text-danger">
                                                {{ formErrors.label_color[0] }}
                                            </small>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Category</label>
                                            <Select v-model="form.category_id" :options="categories" optionLabel="name"
                                                optionValue="id" placeholder="Select Category" class="w-100"
                                                appendTo="self" :autoZIndex="true" :baseZIndex="2000" :class="{
                                                    'is-invalid': formErrors.category_id,
                                                }" />
                                            <small v-if="formErrors.category_id" class="text-danger">
                                                {{ formErrors.category_id[0] }}
                                            </small>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label d-block">Meals</label>
                                            <MultiSelect v-model="form.meals" :options="meals" optionLabel="name"
                                                optionValue="id" filter placeholder="Select Meals"
                                                class="w-full md:w-80" appendTo="self" :autoZIndex="true"
                                                :baseZIndex="2000" :class="{ 'is-invalid': formErrors.meals }" />
                                            <small v-if="formErrors.meals" class="text-danger">
                                                {{ formErrors.meals[0] }}
                                            </small>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Description</label>
                                            <textarea v-model="form.description" rows="4" class="form-control"
                                                placeholder="Notes about this product"></textarea>
                                        </div>
                                    </div>

                                    <!-- Second Row: Allergies, Tags, Addons -->
                                    <div class="row g-4 mt-1">
                                        <!-- Allergies -->
                                        <div class="col-md-6">
                                            <label class="form-label d-block">Allergies</label>
                                            <div @click="openAllergyModal" class="form-control py-2 px-3"
                                                style="cursor:pointer;">
                                                <span v-if="selectedAllergies.length === 0" class="text-muted">
                                                    Select Allergies
                                                </span>
                                                <span v-else>
                                                    <span v-for="(item, index) in selectedAllergies" :key="index"
                                                        class="badge text-dark mx-1 d-inline-flex align-items-center">
                                                        {{ item.name }}
                                                        <span v-if="item.type === 'Contain'" class="ms-1"
                                                            style="color: #22c55e; font-weight: bold;">
                                                            (✔️)
                                                        </span>
                                                        <span v-else class="ms-1"
                                                            style="color: #f97316; font-weight: bold;">
                                                            (*)
                                                        </span>
                                                    </span>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label d-block">Tags</label>
                                            <MultiSelect v-model="form.tags" :options="tags" optionLabel="name"
                                                optionValue="id" filter placeholder="Select Tags" class="w-full md:w-80"
                                                appendTo="self" :class="{
                                                    'is-invalid': formErrors.tags,
                                                }" />
                                            <small v-if="formErrors.tags" class="text-danger">
                                                {{ formErrors.tags[0] }}
                                            </small>
                                        </div>

                                        <!-- Addon Group -->
                                        <!-- REPLACE Addon Group section -->
                                        <div class="col-md-12">
                                            <label class="form-label">Addon Groups</label>
                                            <MultiSelect v-model="form.addon_group_ids" :options="addonGroups"
                                                optionLabel="name" optionValue="id" filter
                                                placeholder="Select Addon Groups" class="w-100" appendTo="self"
                                                :autoZIndex="true" :baseZIndex="2000" @update:modelValue="loadAddons"
                                                :class="{ 'is-invalid': formErrors.addon_group_ids }" />
                                            <small v-if="formErrors.addon_group_ids" class="text-danger">
                                                {{ formErrors.addon_group_ids[0] }}
                                            </small>
                                        </div>

                                        <!-- Show addons from all selected groups -->
                                        <div v-if="addons && addons.length > 0" class="col-md-12">
                                            <label class="form-label">Select Addons</label>
                                            <MultiSelect v-model="form.addon_ids" :options="addons" optionLabel="name"
                                                optionValue="id" filter placeholder="Select Addons" class="w-full"
                                                appendTo="self" :autoZIndex="true" :baseZIndex="2000"
                                                :class="{ 'is-invalid': formErrors.addon_ids }">
                                                <template #option="slotProps">
                                                    <div
                                                        class="d-flex justify-content-between align-items-center w-100">
                                                        <span>{{ slotProps.option.name }}</span>
                                                        <span class="badge bg-primary">{{
                                                            formatCurrencySymbol(slotProps.option.price) }}</span>
                                                    </div>
                                                </template>
                                            </MultiSelect>
                                            <small v-if="formErrors.addon_ids" class="text-danger">
                                                {{ formErrors.addon_ids[0] }}
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Variant-specific Ingredients Section -->
                                    <div class="mt-4">
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-12">
                                                <button @click="openIngredientModal(true)"
                                                    class="d-flex align-items-center gap-1 px-4 py-2 rounded-pill btn btn-primary text-white">
                                                    <Plus class="w-4 h-4 me-1" /> Add Variant Ingredients
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Display Variant Ingredients Cards -->
                                        <div v-if="Object.keys(variantIngredients).length > 0">
                                            <h6 class="fw-semibold mb-3">
                                                <i class="bi bi-card-list me-2"></i>Saved Variant Ingredients
                                            </h6>

                                            <div class="row g-3">
                                                <div v-for="(ingredients, variantId) in variantIngredients"
                                                    :key="variantId" class="col-lg-6 col-md-12">
                                                    <div class="card border rounded-4 shadow-sm h-100">
                                                        <div class="card-header custom-card-header bg-light">
                                                            <div
                                                                class="d-flex justify-content-between align-items-center">
                                                                <div class="d-flex gap-4">
                                                                    <h6 class="mb-0 fw-semibold">
                                                                        <i class="bi bi-tag me-2"></i>
                                                                        {{ getVariantName(variantId) }}
                                                                    </h6>
                                                                    <small class="text-muted">
                                                                        Price: {{
                                                                            formatCurrencySymbol(getVariantPrice(variantId))
                                                                        }}
                                                                    </small>
                                                                </div>
                                                                <div class="d-flex gap-2">
                                                                    <button @click="editVariantIngredients(variantId)"
                                                                        class="btn btn-primary btn-sm"
                                                                        style="height: 32px !important;"
                                                                        title="Edit ingredients">
                                                                        <Pencil class="w-4 h-4" />
                                                                    </button>
                                                                    <button @click="deleteVariantIngredients(variantId)"
                                                                        class="btn btn-danger btn-sm"
                                                                        style="height: 32px !important;"
                                                                        title="Delete variant">
                                                                        <XCircle class="w-4 h-4" />
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="card-body custom-card-body">
                                                            <!-- Nutrition Summary -->
                                                            <div class="mb-3">
                                                                <div class="d-flex flex-wrap gap-2">
                                                                    <span
                                                                        class="badge bg-primary px-1 py-1 rounded-pill small">
                                                                        Calories: {{
                                                                            ingredients.reduce(
                                                                                (sum, ing) =>
                                                                                    sum + (Number(ing.nutrition?.calories || 0) *
                                                                                        Number(ing.qty || 0)),
                                                                                0
                                                                            ).toFixed(2)
                                                                        }}
                                                                    </span>
                                                                    <span
                                                                        class="badge bg-success px-1 py-1 rounded-pill small">
                                                                        Protein: {{
                                                                            ingredients.reduce(
                                                                                (sum, ing) =>
                                                                                    sum + (Number(ing.nutrition?.protein || 0) *
                                                                                        Number(ing.qty || 0)),
                                                                                0
                                                                            ).toFixed(2)
                                                                        }} g
                                                                    </span>
                                                                    <span
                                                                        class="badge bg-warning text-dark px-1 py-1 rounded-pill small">
                                                                        Carbs: {{
                                                                            ingredients.reduce(
                                                                                (sum, ing) =>
                                                                                    sum + (Number(ing.nutrition?.carbs || 0) *
                                                                                        Number(ing.qty || 0)),
                                                                                0
                                                                            ).toFixed(2)
                                                                        }} g
                                                                    </span>
                                                                    <span
                                                                        class="badge bg-danger px-1 py-1 rounded-pill small">
                                                                        Fat: {{
                                                                            ingredients.reduce(
                                                                                (sum, ing) =>
                                                                                    sum + (Number(ing.nutrition?.fat || 0) *
                                                                                        Number(ing.qty || 0)),
                                                                                0
                                                                            ).toFixed(2)
                                                                        }} g
                                                                    </span>
                                                                </div>
                                                            </div>

                                                            <!-- Ingredients Table -->
                                                            <div class="table-responsive">
                                                                <table class="table table-sm align-middle mb-0">
                                                                    <thead class="table-light">
                                                                        <tr>
                                                                            <th class="small">Name</th>
                                                                            <th class="small">Qty</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <tr v-for="(ing, idx) in ingredients"
                                                                            :key="idx">
                                                                            <td class="small">{{ ing.name }}</td>
                                                                            <td class="small">{{ ing.qty }}</td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Image Upload for Variant Menu -->
                                    <div class="row g-3 mt-3 align-items-center">
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold">Item Image</label>
                                            <div class="logo-card" :class="{ 'is-invalid': formErrors.image }">
                                                <div class="logo-frame"
                                                    @click="form.imageUrl ? openImageModal() : (showCropper = true)">
                                                    <img v-if="form.imageUrl" :src="form.imageUrl"
                                                        alt="Menu Item Image" />
                                                    <div v-else class="placeholder">
                                                        <i class="bi bi-image"></i>
                                                    </div>
                                                </div>
                                                <small class="text-muted mt-2 d-block">Upload Image</small>
                                                <ImageCropperModal :show="showCropper" @close="showCropper = false"
                                                    @cropped="onCropped" />
                                                <ImageZoomModal v-if="showImageModal" :show="showImageModal"
                                                    :image="previewImage" @close="showImageModal = false" />
                                            </div>
                                        </div>
                                    </div>


                                    <div class="mt-4">
                                        <h6 class="fw-semibold mb-3">
                                            <i class="bi bi-cash-coin me-2"></i>Sale Settings
                                        </h6>

                                        <!-- Info Card -->

                                        <!-- Variants Resale Settings -->
                                        <div v-if="Object.keys(variantIngredients).length > 0" class="row g-3">
                                            <div v-for="(ingredients, variantId) in variantIngredients" :key="variantId"
                                                class="col-md-6">
                                                <div class="card border rounded-3 shadow-sm">
                                                    <div class="card-header bg-light py-2">
                                                        <h6 class="mb-0 fw-semibold small">
                                                            {{ getVariantName(variantId) }}
                                                        </h6>
                                                    </div>

                                                    <div class="card-body">
                                                        <!-- Is Saleable Radio -->
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold">Enable
                                                                Sale?</label>
                                                            <div class="d-flex gap-3">
                                                                <div class="form-check">
                                                                    <input :id="`variant_saleable_yes_${variantId}`"
                                                                        type="radio" class="form-check-input"
                                                                        :value="true"
                                                                        :name="`variant_saleable_${variantId}`"
                                                                        v-model="variantResaleConfig[variantId].is_saleable"
                                                                        :checked="variantResaleConfig[variantId].is_saleable === true" />
                                                                    <label class="form-check-label small"
                                                                        :for="`variant_saleable_yes_${variantId}`">
                                                                        Yes
                                                                    </label>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input :id="`variant_saleable_no_${variantId}`"
                                                                        type="radio" class="form-check-input"
                                                                        :value="false"
                                                                        :name="`variant_saleable_${variantId}`"
                                                                        v-model="variantResaleConfig[variantId].is_saleable"
                                                                        :checked="variantResaleConfig[variantId].is_saleable === false" />
                                                                    <label class="form-check-label small"
                                                                        :for="`variant_saleable_no_${variantId}`">
                                                                        No
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Resale Type & Value (shown only if saleable) -->
                                                        <div v-if="variantResaleConfig[variantId].is_saleable"
                                                            class="row g-2">
                                                            <!-- Resale Type -->
                                                            <div class="col-6">
                                                                <label class="form-label small">Sale Type</label>
                                                                <Select
                                                                    v-model="variantResaleConfig[variantId].resale_type"
                                                                    :options="resaleTypeOptions" optionLabel="label"
                                                                    optionValue="value" placeholder="Select"
                                                                    class="w-100" :style="{ fontSize: '0.875rem' }"
                                                                    appendTo="self" :autoZIndex="true"
                                                                    :baseZIndex="2000" />
                                                            </div>

                                                            <!-- Resale Value -->
                                                            <div class="col-6">
                                                                <label class="form-label small">
                                                                    {{ variantResaleConfig[variantId].resale_type ===
                                                                        'flat' ?
                                                                        'Flat Amount' : 'Percentage' }}
                                                                </label>
                                                                <div class="input-group input-group-sm">
                                                                    <input
                                                                        v-model.number="variantResaleConfig[variantId].resale_value"
                                                                        type="number" min="0"
                                                                        :step="variantResaleConfig[variantId].resale_type === 'flat' ? '0.01' : '0.1'"
                                                                        class="form-control py-2"
                                                                        :placeholder="`Enter ${variantResaleConfig[variantId].resale_type === 'flat' ? 'amount' : '%'}`" />
                                                                    <span class="input-group-text small">
                                                                        {{ variantResaleConfig[variantId].resale_type
                                                                            ===
                                                                            'percentage' ? '%' : '£' }}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div v-else class="alert alert-info small" role="alert">
                                            <i class="bi bi-info-circle me-2"></i>
                                            Add variant ingredients first to configure resale settings.
                                        </div>
                                    </div>
                                </div>


                                <!-- ==================== Deals MENU TAB ==================== -->
                                <div v-show="activeTab === 'deals'">
                                    <!-- Top row -->
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Deal Name</label>
                                            <input v-model="form.deal_name" type="text" class="form-control"
                                                :class="{ 'is-invalid': formErrors.deal_name || formErrors.name }"
                                                placeholder="e.g., Family Deal" />
                                            <small v-if="formErrors.deal_name || formErrors.name" class="text-danger">
                                                {{ formErrors.deal_name?.[0] || formErrors.name?.[0] }}
                                            </small>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Deal Price</label>
                                            <input v-model="form.deal_price" type="number" min="0" step="0.01"
                                                class="form-control"
                                                :class="{ 'is-invalid': formErrors.deal_price || formErrors.price }"
                                                placeholder="e.g., 29.99" />
                                            <small v-if="formErrors.deal_price || formErrors.price" class="text-danger">
                                                {{ formErrors.deal_price?.[0] || formErrors.price?.[0] }}
                                            </small>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Is this Taxable Deal?</label>
                                            <Select v-model="form.is_taxable" :options="taxableOptions"
                                                optionLabel="label" optionValue="value" placeholder="Select Option"
                                                class="w-100" appendTo="self" :autoZIndex="true" :baseZIndex="2000"
                                                :class="{ 'is-invalid': formErrors.is_taxable }" />
                                            <small v-if="formErrors.is_taxable" class="text-danger">
                                                {{ formErrors.is_taxable[0] }}
                                            </small>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label d-block">Label Color</label>
                                            <Select v-model="form.label_color" :options="labelColors" optionLabel="name"
                                                optionValue="value" placeholder="Select Label Color" class="w-100"
                                                appendTo="self" :autoZIndex="true" :baseZIndex="2000"
                                                :class="{ 'is-invalid': formErrors.label_color }" />
                                            <small v-if="formErrors.label_color" class="text-danger">
                                                {{ formErrors.label_color[0] }}
                                            </small>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Category</label>
                                            <Select v-model="form.category_id" :options="categories" optionLabel="name"
                                                optionValue="id" placeholder="Select Category" class="w-100"
                                                appendTo="self" :autoZIndex="true" :baseZIndex="2000"
                                                @update:modelValue="form.subcategory = ''"
                                                :class="{ 'is-invalid': formErrors.category_id }" />
                                            <small v-if="formErrors.category_id" class="text-danger">
                                                {{ formErrors.category_id[0] }}
                                            </small>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label d-block">Meals</label>
                                            <MultiSelect v-model="form.meals" :options="meals" optionLabel="name"
                                                optionValue="id" filter placeholder="Select Meals"
                                                class="w-full md:w-80" appendTo="self" :autoZIndex="true"
                                                :baseZIndex="2000" :class="{ 'is-invalid': formErrors.meals }" />
                                            <small v-if="formErrors.meals" class="text-danger">
                                                {{ formErrors.meals[0] }}
                                            </small>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Description</label>
                                            <textarea v-model="form.description" rows="4" class="form-control"
                                                :class="{ 'is-invalid': formErrors.description }"
                                                placeholder="Deal description"></textarea>
                                            <small v-if="formErrors.description" class="text-danger">
                                                {{ formErrors.description[0] }}
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Second Row -->
                                    <div class="row g-4 mt-1">
                                        <div class="col-md-6">
                                            <label class="form-label d-block">Allergies</label>
                                            <div @click="openAllergyModal" class="form-control py-2 px-3"
                                                style="cursor:pointer;">
                                                <span v-if="selectedAllergies.length === 0" class="text-muted">
                                                    Select Allergies
                                                </span>
                                                <span v-else>
                                                    <span v-for="(item, index) in selectedAllergies" :key="index"
                                                        class="badge text-dark mx-1 d-inline-flex align-items-center">
                                                        {{ item.name }}
                                                        <span v-if="item.type === 'Contain'" class="ms-1"
                                                            style="color: #22c55e; font-weight: bold;">(✔️)</span>
                                                        <span v-else class="ms-1"
                                                            style="color: #f97316; font-weight: bold;">(*)</span>
                                                    </span>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label d-block">Tags</label>
                                            <MultiSelect v-model="form.tags" :options="tags" optionLabel="name"
                                                optionValue="id" filter placeholder="Select Tags" class="w-full md:w-80"
                                                appendTo="self" :class="{ 'is-invalid': formErrors.tags }" />
                                            <small v-if="formErrors.tags" class="text-danger">
                                                {{ formErrors.tags[0] }}
                                            </small>
                                        </div>

                                        <!-- REPLACE THIS SECTION -->
                                        <div class="col-md-12">
                                            <label class="form-label">Addon Groups</label>
                                            <MultiSelect v-model="form.addon_group_ids" :options="addonGroups"
                                                optionLabel="name" optionValue="id" filter
                                                placeholder="Select Addon Groups" class="w-100" appendTo="self"
                                                :autoZIndex="true" :baseZIndex="2000" @update:modelValue="loadAddons"
                                                :class="{ 'is-invalid': formErrors.addon_group_ids }" />
                                            <small v-if="formErrors.addon_group_ids" class="text-danger">
                                                {{ formErrors.addon_group_ids[0] }}
                                            </small>
                                        </div>

                                        <div v-if="addons && addons.length > 0" class="col-md-12">
                                            <label class="form-label">Select Addons</label>
                                            <MultiSelect v-model="form.addon_ids" :options="addons" optionLabel="name"
                                                optionValue="id" filter placeholder="Select Addons" class="w-full"
                                                appendTo="self" :autoZIndex="true" :baseZIndex="2000"
                                                :class="{ 'is-invalid': formErrors.addon_ids }">
                                                <template #option="slotProps">
                                                    <div
                                                        class="d-flex justify-content-between align-items-center w-100">
                                                        <span>{{ slotProps.option.name }}</span>
                                                        <span class="badge bg-primary">{{
                                                            formatCurrencySymbol(slotProps.option.price) }}</span>
                                                    </div>
                                                </template>
                                            </MultiSelect>
                                            <small v-if="formErrors.addon_ids" class="text-danger">
                                                {{ formErrors.addon_ids[0] }}
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Image and Menu Selection -->
                                    <div class="row g-3 mt-2 align-items-center">
                                        <div class="col-md-4">
                                            <div class="logo-card" :class="{ 'is-invalid': formErrors.image }">
                                                <div class="logo-frame"
                                                    @click="form.imageUrl ? openImageModal() : (showCropper = true)">
                                                    <img v-if="form.imageUrl" :src="form.imageUrl" alt="Deal Image" />
                                                    <div v-else class="placeholder">
                                                        <i class="bi bi-image"></i>
                                                    </div>
                                                </div>
                                                <small class="text-muted mt-2 d-block">Upload Image</small>
                                                <ImageCropperModal :show="showCropper" @close="showCropper = false"
                                                    @cropped="onCropped" />
                                                <ImageZoomModal v-if="showImageModal" :show="showImageModal"
                                                    :image="previewImage" @close="showImageModal = false" />
                                            </div>
                                        </div>

                                        <!-- Menu Selection Button and Cart Display -->
                                        <div class="mt-4 col-sm-6 col-md-8">
                                            <button class="btn btn-primary rounded-pill px-4"
                                                :class="{ 'is-invalid': formErrors.menu_item_ids }"
                                                @click="openMenuSelectionModal">
                                                {{ isEditMode ? "Menu Items" : "+ Add Menu Items" }}
                                            </button>
                                            <small v-if="formErrors.menu_item_ids" class="text-danger d-block mt-1">
                                                {{ formErrors.menu_item_ids[0] }}
                                            </small>

                                            <!-- Display Selected Menus -->
                                            <div v-if="Array.isArray(form.menu_item_ids) && form.menu_item_ids.length > 0"
                                                class="mt-3">
                                                <!-- Total Price Card -->
                                                <div class="card border rounded-4 mb-3">
                                                    <div class="p-3 fw-semibold">
                                                        <div class="mb-2">Deal Summary</div>
                                                        <div class="d-flex flex-wrap gap-2">
                                                            <span class="badge bg-primary px-3 py-2 rounded-pill">
                                                                Items: {{ form.menu_item_ids.length }}
                                                            </span>
                                                            <span class="badge bg-success px-3 py-2 rounded-pill">
                                                                Total: {{formatCurrencySymbol(
                                                                    form.menu_item_ids.reduce((sum, item) => {
                                                                        const menu = menuItems.find(m => m.id === (item.id ||
                                                                            item));
                                                                        const qty = item.qty || 1;
                                                                        return sum + ((menu?.price || 0) * qty);
                                                                    }, 0)
                                                                )}}
                                                            </span>
                                                            <span
                                                                class="badge bg-warning text-dark px-3 py-2 rounded-pill"
                                                                v-if="form.deal_price">
                                                                Deal: {{ formatCurrencySymbol(form.deal_price) }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Menus Table -->
                                                <div class="card border rounded-4">
                                                    <div class="table-responsive">
                                                        <table class="table align-middle mb-0">
                                                            <thead>
                                                                <tr>
                                                                    <th>Item Name</th>
                                                                    <th>Qty</th>
                                                                    <th>Price</th>
                                                                    <th>Total</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr v-for="(item, idx) in form.menu_item_ids"
                                                                    :key="idx">
                                                                    <td>{{menuItems.find(m => m.id === (item.id ||
                                                                        item))?.name}}
                                                                    </td>
                                                                    <td>{{ item.qty || 1 }}</td>
                                                                    <td>{{formatCurrencySymbol(menuItems.find(m => m.id
                                                                        ===
                                                                        (item.id || item))?.price || 0)}}</td>
                                                                    <td>{{formatCurrencySymbol((menuItems.find(m =>
                                                                        m.id ===
                                                                        (item.id || item))?.price || 0) * (item.qty ||
                                                                            1))}}</td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End Tab Content -->

                            <div class="mt-4">
                                <button class="btn btn-primary rounded-pill btn-sm px-5 py-2" :disabled="submitting"
                                    @click="handleSubmit">
                                    <template v-if="submitting">
                                        <span class="spinner-border spinner-border-sm me-2"></span>
                                        Saving...
                                    </template>
                                    <template v-else>
                                        Save
                                    </template>
                                </button>

                                <button class="btn btn-secondary btn-sm rounded-pill py-2 px-4 ms-2"
                                    data-bs-dismiss="modal" @click="resetForm">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /modal -->

            <!-- Allergy Modal -->
            <div class="modal fade" id="allergyModal" tabindex="-1" :class="{ show: showAllergyModal }"
                :style="{ display: showAllergyModal ? 'block' : 'none' }" v-if="showAllergyModal" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content rounded-4 shadow-lg border border-secondary">
                        <div class="modal-header bg-light border-bottom">
                            <h5 class="modal-title fw-bold">Select Allergies</h5>
                            <button
                                class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                                @click="cancelAllergySelection" data-bs-dismiss="modal" aria-label="Close"
                                title="Close">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="modal-body p-4"
                            style="border: 2px solid #dee2e6; border-radius: 10px; background-color: #f8f9fa;">
                            <table class="table table-bordered text-center align-middle mb-0"
                                style="border: 1px solid #dee2e6;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="fw-semibold">Allergy</th>
                                        <th class="fw-semibold">Contain</th>
                                        <th class="fw-semibold">Trace</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="allergy in allergies" :key="allergy.id">
                                        <td class="text-start ps-4">{{ allergy.name }}</td>
                                        <td>
                                            <input type="radio" :name="'allergy-' + allergy.id" value="Contain"
                                                v-model="selectedTypes[allergy.id]" class="form-check-input"
                                                style="width: 20px; height: 20px;" />
                                        </td>
                                        <td>
                                            <input type="radio" :name="'allergy-' + allergy.id" value="Trace"
                                                v-model="selectedTypes[allergy.id]" class="form-check-input"
                                                style="width: 20px; height: 20px;" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="modal-footer bg-light border-top">
                            <button class="btn btn-secondary px-2 py-2" @click="cancelAllergySelection">
                                Cancel
                            </button>
                            <button class="btn btn-primary px-2 py-2" @click="saveSelectedAllergies">
                                Save
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add backdrop -->
            <div v-if="showAllergyModal" class="modal-backdrop fade show"></div>

            <!-- Add Ingredient Modal -->
            <div class="modal fade" id="addIngredientModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content rounded-4">
                        <div class="modal-header">
                            <h5 class="modal-title fw-semibold">
                                {{ isEditMode == true ? "Edit Ingredients" : "Add Ingredients" }}
                                <span v-if="isVariantMode && selectedVariantForIngredients"
                                    class="badge bg-primary ms-2">
                                    {{ getVariantName(selectedVariantForIngredients) }}
                                </span>
                            </h5>

                            <button
                                class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                                data-bs-dismiss="modal" aria-label="Close" @click="showMenuModal" title="Close">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="modal-body">
                            <div class="row g-4">
                                <div v-if="isVariantMode" class=" rounded-4 mb-3">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Variant Name</label>
                                            <input v-model="variantForm.name" type="text" class="form-control"
                                                placeholder="Enter custom variant name" />
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Variant Price</label>
                                            <input v-model.number="variantForm.price" type="number" min="0" step="0.01"
                                                class="form-control" placeholder="Enter variant price" />
                                        </div>
                                    </div>
                                </div>

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
                                                <img :src="it.image_url ? `${it.image_url}` : '/assets/img/default.png'"
                                                    class="rounded"
                                                    style="width: 56px; height: 56px; object-fit: cover; border: 1px solid white;" />
                                                <div class="flex-grow-1">
                                                    <div class="fw-semibold">{{ it?.name }}</div>
                                                    <div class="text-muted small">Category: {{ it.category?.name }}
                                                    </div>
                                                    <div class="text-muted small">Unit: {{ it?.unit_name }}</div>
                                                    <div class="small mt-2 text-muted">
                                                        Calories: {{ it.nutrition?.calories || 0 }},
                                                        Protein: {{ it.nutrition?.protein || 0 }},
                                                        Carbs: {{ it.nutrition?.carbs || 0 }},
                                                        Fat: {{ it.nutrition?.fat || 0 }}
                                                    </div>
                                                </div>

                                                <button class="btn btn-primary btn-sm py-2 px-4 rounded-pill"
                                                    @click="addIngredient(it)">
                                                    {{i_cart.some((c) => c.id === it.id) ? "Update" : "Add"}}
                                                </button>
                                            </div>

                                            <div class="row g-2 mt-3">
                                                <div class="col-4">
                                                    <label class="small text-muted">Quantity</label>
                                                    <input v-model.number="it.qty" type="number" min="0"
                                                        class="form-control form-control-sm" />
                                                    <small v-if="formErrors[it.id]?.qty" class="text-danger">
                                                        {{ formErrors[it.id].qty }}
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
                                            <div>Total Nutrition ({{ isVariantMode ? 'Variant' : 'Menu' }})</div>
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
                                                        <th class="text-end">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="(ing, idx) in i_cart" :key="idx">
                                                        <td>{{ ing.name }}</td>
                                                        <td>{{ ing.qty }}</td>
                                                        <td class="text-end">
                                                            <button class="btn btn-sm btn-danger"
                                                                @click="removeIngredient(idx)">
                                                                Remove
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <tr v-if="i_cart.length === 0">
                                                        <td colspan="6" class="text-center text-muted py-3">
                                                            No ingredients added.
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="mt-3 text-center">
                                        <button class="btn btn-primary btn-sm py-2 px-5 rounded-pill"
                                            @click="saveIngredients">
                                            Done
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add menu Modal -->

            <div class="modal fade" id="addMenuSelectionModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content rounded-4">
                        <div class="modal-header">
                            <h5 class="modal-title fw-semibold">
                                {{ isEditMode ? "Edit Menu Items" : "Add Menu Items" }}
                            </h5>
                            <button
                                class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                                data-bs-dismiss="modal" aria-label="Close" @click="showMenuModal" title="Close">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="modal-body">
                            <div class="row g-4">
                                <!-- Left side: Available Menus -->
                                <div class="col-lg-5">
                                    <div class="search-wrap mb-2">
                                        <input v-model="m_search" type="text" class="form-control search-input"
                                            placeholder="Search Menus..." />
                                    </div>

                                    <div v-for="menu in m_displayMenus" :key="menu.id"
                                        class="card shadow-sm border-0 rounded-4 mb-3">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start gap-3">
                                                <img :src="menu.image_url ? menu.image_url : '/assets/img/default.png'"
                                                    class="rounded"
                                                    style="width: 56px; height: 56px; object-fit: cover; border: 1px solid white;" />
                                                <div class="flex-grow-1">
                                                    <div class="fw-semibold">{{ menu.name }}</div>
                                                    <div class="text-muted small">Category: {{ menu.category?.name }}
                                                    </div>
                                                    <div class="text-muted small">Price: {{
                                                        formatCurrencySymbol(menu.price) }}
                                                    </div>
                                                </div>

                                                <button class="btn btn-primary btn-sm py-2 px-4 rounded-pill"
                                                    @click="addMenuToCart(menu)">
                                                    {{m_cart.some((c) => c.id === menu.id) ? "Update" : "Add"}}
                                                </button>
                                            </div>

                                            <div class="row g-2 mt-3">
                                                <div class="col-6">
                                                    <label class="small text-muted">Quantity</label>
                                                    <input v-model.number="menu.qty" type="number" min="1"
                                                        class="form-control form-control-sm" placeholder="Enter qty" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right side: Cart -->
                                <div class="col-lg-7">
                                    <div class="card border rounded-4 mb-3">
                                        <div class="p-3 fw-semibold">
                                            <div>Deal Summary</div>
                                            <div>Total Menu Items: {{ m_cart.length }}</div>
                                            <div>Total Price: {{ formatCurrencySymbol(m_totalPrice) }}</div>
                                        </div>
                                    </div>

                                    <div class="card border rounded-4">
                                        <div class="table-responsive">
                                            <table class="table align-middle mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Qty</th>
                                                        <th>Price</th>
                                                        <th>Total</th>
                                                        <th class="text-end">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="(menu, idx) in m_cart" :key="idx">
                                                        <td>{{ menu.name }}</td>
                                                        <td>{{ menu.qty }}</td>
                                                        <td>{{ formatCurrencySymbol(menu.price) }}</td>
                                                        <td>{{ formatCurrencySymbol(menu.price * menu.qty) }}</td>
                                                        <td class="text-end">
                                                            <button class="btn btn-sm btn-danger"
                                                                @click="removeMenuFromCart(idx)">
                                                                Remove
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <tr v-if="m_cart.length === 0">
                                                        <td colspan="5" class="text-center text-muted py-3">
                                                            No menu items added.
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="mt-3 text-center">
                                        <button class="btn btn-primary btn-sm py-2 px-5 rounded-pill"
                                            @click="saveMenusToCart">
                                            Done
                                        </button>
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
.dark h4 {
    color: white;
}

.custom-card-header {
    padding: 5px 20px !important;
}

:global(.dark .form-control:focus) {
    border-color: #fff !important;
}


.dark .bg-light {
    background-color: #212121 !important;
    color: #fff !important;
}

.custom-card-body {
    padding: 7px 8px !important;
}

:global(.dark .nav-link) {
    background-color: #212121 !important;
    color: #fff !important;
    border-bottom: 1px solid #fff !important;
}

:global(.dark .nav-link.active) {
    background-color: #1C0D82 !important;
    color: #fff !important;
    border-bottom: 1px solid #fff !important;
}

.custom-card-cost {
    padding: 8px 10px !important;
}

.dark .card {
    background-color: #181818 !important;
    /* gray-800 */
    color: #ffffff !important;
    /* gray-50 */
}

.dark .input-group-text {
    background-color: #212121 !important;
    color: #fff !important;
}

:global(.dark .p-multiselect-empty-message) {
    color: #fff !important;
}

.dark .bg-white {
    border: 1px solid #fff !important;
}

.dark .table {
    background-color: #181818 !important;
    /* gray-900 */
    color: #f9fafb !important;
}

.dark .table thead {
    background-color: #181818 !important;
    color: #ffffff;
}

.dark .table thead th {
    background-color: #181818 !important;
    color: #ffffff;
}

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
    font-size: 1rem;
}

.dark .select {
    background-color: #181818 !important;
    color: #f9fafb !important;
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
    font-size: 0.65rem;
    padding: 0.4em 0.6em;
}

.form-label.text-muted {
    font-size: 0.8rem;
    margin-bottom: 0.25rem;
    font-weight: 500;
}

.dark .form-label.text-muted {
    color: #fff !important;
}

.dark .form-control {
    background-color: #212121 !important;
    color: #fff !important;
}

.side-link {
    border-radius: 55%;
    background-color: #fff !important;
}

.dark .side-link {
    border-radius: 55%;
    background-color: #181818 !important;
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

:deep(.p-multiselect-label) {
    color: #000 !important;
}

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


.dark .logo-card {
    background-color: #181818 !important;
}

.dark .logo-frame {
    background-color: #181818 !important;
}

.dark .fw-semibold {
    color: #fff !important;
}

/* ======================================================== */

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
