<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted, onUpdated } from "vue";

import Select from "primevue/select";

/* ===================== Demo Data (swap with API later) ===================== */
const items = ref([
    {
        id: 1,
        name: "Basmati Rice",
        image: "https://picsum.photos/seed/rice/64",
        unitPrice: 2.2,
        category: "Grains",
        unit: "gram (g)",
        availableStock: 120.0,
        stockValue: 264.0,
        enteredBy: "Bilal",
    },
    {
        id: 2,
        name: "Chicken Breast",
        image: "https://picsum.photos/seed/chicken/64",
        unitPrice: 5.5,
        category: "Poultry",
        unit: "gram (g)",
        availableStock: 80.0,
        stockValue: 440.0,
        enteredBy: "Admin",
    },
    {
        id: 3,
        name: "Fresh Tomatoes",
        image: "https://picsum.photos/seed/tomato/64",
        unitPrice: 1.8,
        category: "Produce",
        unit: "gram (g)",
        availableStock: 200.0,
        stockValue: 360.0,
        enteredBy: "Shakir",
    },
    {
        id: 4,
        name: "Olive Oil",
        image: "https://picsum.photos/seed/oliveoil/64",
        unitPrice: 10.0,
        category: "Grocery",
        unit: "liter (L)",
        availableStock: 50.0,
        stockValue: 500.0,
        enteredBy: "Jamal",
    },
    {
        id: 5,
        name: "Cheddar Cheese",
        image: "https://picsum.photos/seed/cheese/64",
        unitPrice: 4.5,
        category: "Dairy",
        unit: "gram (g)",
        availableStock: 60.0,
        stockValue: 270.0,
        enteredBy: "Saf",
    },
]);


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

const props = defineProps({
    allergies: {
        type: Object,
    },
    suppliers: {
        type: Object
    },
    units: {
        type: Object
    },
    tags: {
        type: Object
    },
    categories: {
        type: Object
    }
});


/* ===================== KPIs ===================== */
const categoriesCount = computed(
    () => new Set(items.value.map((i) => i.category)).size
);
const totalItems = computed(() => items.value.length);
const lowStockCount = computed(
    () => items.value.filter((i) => i.availableStock < 5).length
);
const outOfStockCount = computed(
    () => items.value.filter((i) => i.availableStock <= 0).length
);
const kpis = computed(() => [
    { label: "Categories", value: categoriesCount.value, icon: "layers" },
    { label: "Total Items", value: totalItems.value, icon: "package" },
    { label: "Low Stock", value: lowStockCount.value, icon: "alert-triangle" },
    { label: "Out of Stock", value: outOfStockCount.value, icon: "slash" },
]);

onMounted(() => window.feather?.replace());
onUpdated(() => window.feather?.replace());

/* ===================== Helpers ===================== */
const money = (amount, currency = "GBP") =>
    new Intl.NumberFormat("en-GB", { style: "currency", currency }).format(
        amount
    );

/* ===================== Add New Product Modal ===================== */
const categoryOptions = ref([
    { value: "poultry", label: "Poultry" },
    { value: "produce", label: "Produce" },
    { value: "grains", label: "Grains" },
    { value: "grocery", label: "Grocery" },
    { value: "dairy", label: "Dairy" },
    { value: "meat", label: "Meat" },
]);

const subcatMap = ref({
    Poultry: ["Chicken", "Broiler", "Wings", "Breast"],
    Produce: ["Tomatoes", "Onions", "Potatoes"],
    Grains: ["Rice", "Wheat", "Oats"],
    Grocery: ["Oil", "Spices", "Sugar"],
    // Dairy: ["Cheese", "Milk", "Butter"],
    Meat: ["Beef", "Mutton", "Veal"],
});
const unitOptions = ref([
    "gram (g)",
    "kilogram (kg)",
    "millilitre (ml)",
    "liter (L)",
    "piece (pc)",
]);
// const supplierOptions = ref(["Noor", "Metro", "ChaseUp", "Al-Fatah"]);
// computed subcategory options based on selected category
const subcatOptions = computed(() =>
    (subcatMap.value[form.value.category] || []).map((s) => ({
        name: s,
        value: s,
    }))
);
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

const selectableAllergies = [
    "Milk",
    "Eggs",
    "Peanuts",
    "Tree Nuts",
    "Soy",
    "Wheat",
    "Fish",
    "Shellfish",
];
const selectableTags = [
    "Halal",
    "Haram",
    "Vegan",
    "Vegetarian",
    "Gluten-Free",
    "Keto",
];

const toggleSelect = (list, value) => {
    const i = list.indexOf(value);
    if (i === -1) list.push(value);
    else list.splice(i, 1);
};

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
function submitProduct() {
    const payload = {
        name: form.value.name.trim(),
        category: form.value.category,
        subcategory: form.value.subcategory,
        unit: form.value.unit,
        minAlert: form.value.minAlert ? Number(form.value.minAlert) : null,
        supplier: form.value.supplier,
        sku: form.value.sku || null,
        description: form.value.description || null,
        nutrition: { ...form.value.nutrition },
        allergies: [...form.value.allergies],
        tags: [...form.value.tags],
        hasImage: !!form.value.imageFile,
    };

    console.log("[Inventory] Submitting product payload:", payload);

    submitting.value = true;
    fakeApi(payload)
        .then((res) => {
            console.log("✅ then():", res.message);
            // push into list as a new row (demo)
            items.value.unshift({
                id: Date.now(),
                name: payload.name || "Untitled",
                image:
                    form.value.imagePreview ||
                    "https://picsum.photos/seed/new/64",
                unitPrice: 0,
                category: payload.category || "-",
                unit: payload.unit || "-",
                availableStock: 0,
                stockValue: 0,
                enteredBy: "You",
            });
        })
        .catch((err) => {
            console.error("❌ catch():", err?.message || err);
            alert("Failed to add product (demo). Check console.");
        })
        .finally(() => {
            submitting.value = false;
            const m = bootstrap.Modal.getInstance(
                document.getElementById("addItemModal")
            );
            m?.hide();
            resetForm();
        });
}

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

function fakeApi(data) {
    return new Promise((resolve) => {
        setTimeout(
            () => resolve({ ok: true, message: "Saved (demo)", data }),
            800
        );
    });
}

/* ===================== Row Actions (stubs) ===================== */
const onStockIn = (it) => console.log("Stock In:", it);
const onStockOut = (it) => console.log("Stock Out:", it);
const onViewItem = (it) => console.log("View:", it);
const onEditItem = (it) => console.log("Edit:", it);
const onDownload = (type) => console.log("Download:", type);
</script>

<template>
    <Master>
        <div class="page-wrapper">
            <div class="container-fluid py-1">
                <!-- Title -->
                <h4 class="fw-semibold mb-3">Overall Inventory</h4>

                <!-- KPI Cards -->
                <div class="row g-3">
                    <div v-for="c in kpis" :key="c.label" class="col-6 col-md-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="icon-wrap mb-2">
                                    <i :class="c.icon"></i>
                                </div>
                                <div class="kpi-label text-muted">
                                    {{ c.label }}
                                </div>
                                <div class="kpi-value">{{ c.value }}</div>
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
                                <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal"
                                    data-bs-target="#addItemModal">
                                    Add Item
                                </button>

                                <!-- Download all -->
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary rounded-pill px-4 dropdown-toggle"
                                        data-bs-toggle="dropdown">
                                        Download all
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow rounded-4 py-2">
                                        <li>
                                            <a class="dropdown-item py-2" href="javascript:void(0)"
                                                @click="onDownload('pdf')">Download as PDF</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item py-2" href="javascript:void(0)"
                                                @click="onDownload('excel')">Download as Excel</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table table-hover align-middle" style="min-height: 320px">
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
                                        <th>Entered By</th>
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
                                            <img :src="item.image" class="rounded" style="
                                                    width: 40px;
                                                    height: 40px;
                                                    object-fit: cover;
                                                " />
                                        </td>
                                        <td>
                                            {{ money(item.unitPrice, "GBP") }}
                                        </td>
                                        <td class="text-truncate" style="max-width: 260px">
                                            {{ item.category }}
                                        </td>
                                        <td>{{ item.unit }}</td>
                                        <td>
                                            {{ item.availableStock.toFixed(1) }}
                                        </td>
                                        <td>
                                            {{ money(item.stockValue, "GBP") }}
                                        </td>
                                        <td>
                                            <span v-if="item.availableStock > 0"
                                                class="badge rounded-pill bg-success-subtle text-success fw-semibold px-3 py-2">In-stock</span>
                                            <span v-else
                                                class="badge rounded-pill bg-secondary-subtle text-secondary fw-semibold px-3 py-2">Out
                                                of stock</span>
                                        </td>
                                        <td>{{ item.enteredBy }}</td>

                                        <td class="text-end">
                                            <div class="dropdown">
                                                <button class="btn btn-link text-secondary p-0 fs-5"
                                                    data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                                    ⋮
                                                </button>
                                                <ul
                                                    class="dropdown-menu dropdown-menu-end shadow rounded-4 overflow-hidden action-menu">
                                                    <li>
                                                        <a class="dropdown-item py-2" href="javascript:void(0)" @click="
                                                            onStockIn(item)
                                                            "><i class="bi bi-box-arrow-in-down-right me-2"></i>Stock
                                                            In</a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item py-2" href="javascript:void(0)" @click="
                                                            onStockOut(item)
                                                            "><i class="bi bi-box-arrow-up-right me-2"></i>Stock
                                                            Out</a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item py-2" href="javascript:void(0)" @click="
                                                            onViewItem(item)
                                                            "><i class="bi bi-eye me-2"></i>View Item</a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item py-2" href="javascript:void(0)" @click="
                                                            onEditItem(item)
                                                            "><i class="bi bi-pencil-square me-2"></i>Edit</a>
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

                <!-- ===================== Add New Product Modal ===================== -->
                <div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
                        <div class="modal-content rounded-4">
                            <div class="modal-header">
                                <h5 class="modal-title fw-semibold">
                                    Add New Inventory Item
                                </h5>
                                <button type="button" class="btn btn-close" data-bs-dismiss="modal" aria-label="Close">
                                    ×
                                </button>
                            </div>

                            <div class="modal-body">
                                <!-- top row -->
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Product Name</label>
                                        <input v-model="form.name" type="text" class="form-control"
                                            placeholder="e.g., Chicken Breast" />
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Category</label>
                                        <Select v-model="form.category" :options="categories" optionLabel="name"
                                            optionValue="name" placeholder="Select Category" class="w-100"
                                            appendTo="self" :autoZIndex="true" :baseZIndex="2000" @update:modelValue="
                                                form.subcategory = ''
                                                ">
                                            <template #value="{ value, placeholder }">
                                                <span v-if="value">{{
                                                    value
                                                    }}</span>
                                                <span v-else>{{
                                                    placeholder
                                                    }}</span>
                                            </template>
                                        </Select>
                                    </div>

                                    <!-- Subcategory (only if exists) -->
                                    <div class="col-md-6" v-if="subcatOptions.length">
                                        <label class="form-label">Subcategory</label>
                                        <Select v-model="form.subcategory" :options="subcatOptions" optionLabel="name"
                                            optionValue="value" placeholder="Select Subcategory" class="w-100"
                                            :appendTo="body" :autoZIndex="true" :baseZIndex="2000">
                                            <template #value="{ value, placeholder }">
                                                <span v-if="value">{{
                                                    value
                                                    }}</span>
                                                <span v-else>{{
                                                    placeholder
                                                    }}</span>
                                            </template>
                                        </Select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label d-block">Minimum Stock Alert Level</label>
                                        <input v-model="form.minAlert" type="number" min="0" class="form-control"
                                            placeholder="e.g., 5" />
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Unit Type</label>
                                        <Select v-model="form.unit" :options="units" optionLabel="name"
                                            optionValue="name" placeholder="Select Unit" class="w-100" appendTo="self"
                                            :autoZIndex="true" :baseZIndex="2000">
                                            <template #value="{ value, placeholder }">
                                                <span v-if="value">{{
                                                    value
                                                    }}</span>
                                                <span v-else>{{
                                                    placeholder
                                                    }}</span>
                                            </template>
                                        </Select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Preferred Supplier</label>
                                        <Select v-model="form.supplier" :options="suppliers" optionLabel="name"
                                            optionValue="name" placeholder="Select Supplier" class="w-100"
                                            appendTo="self" :autoZIndex="true" :baseZIndex="2000">
                                            <template #value="{ value, placeholder }">
                                                <span v-if="value">{{
                                                    value
                                                    }}</span>
                                                <span v-else>{{
                                                    placeholder
                                                    }}</span>
                                            </template>
                                        </Select>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label">SKU (Optional)</label>
                                        <input v-model="form.sku" type="text" class="form-control"
                                            placeholder="Stock Keeping Unit" />
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Description</label>
                                        <textarea v-model="form.description" rows="4" class="form-control"
                                            placeholder="Notes about this product"></textarea>
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
                                            class="form-control" />
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Fat (g)</label>
                                        <input v-model="form.nutrition.fat" type="number" min="0"
                                            class="form-control" />
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Protein (g)</label>
                                        <input v-model="form.nutrition.protein" type="number" min="0"
                                            class="form-control" />
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Carbs (g)</label>
                                        <input v-model="form.nutrition.carbs" type="number" min="0"
                                            class="form-control" />
                                    </div>
                                </div>

                                <div class="row g-4 mt-1">
                                    <!-- Allergies -->
                                    <div class="col-md-6">
                                        <label class="form-label d-block">Allergies</label>
                                        <div class="d-flex flex-wrap gap-2">
                                            <button v-for="a in allergies" :key="a" type="button"
                                                class="btn btn-sm rounded-pill" :class="form.allergies.includes(a)
                                                        ? 'btn-primary'
                                                        : 'btn-outline-secondary'
                                                    " @click="
                                                    toggleSelect(
                                                        form.allergies,
                                                        a
                                                    )
                                                    ">
                                                {{ a.name }}
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Tags -->
                                    <div class="col-md-6">
                                        <label class="form-label d-block">Tags (Halal, Haram, etc.)</label>
                                        <div class="d-flex flex-wrap gap-2">
                                            <button v-for="t in tags" :key="t" type="button"
                                                class="btn btn-sm rounded-pill" :class="form.tags.includes(t)
                                                        ? 'btn-primary'
                                                        : 'btn-outline-secondary'
                                                    " @click="
                                                    toggleSelect(form.tags, t)
                                                    ">
                                                {{ t.name }}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <MultiSelect v-model="form.allergies" :options="allergyOptions" optionLabel="label"
                                    optionValue="value" filter display="chip"
                                    placeholder="Choose allergies or type to add custom" class="w-100" appendTo="self"
                                    :pt="{ panel: { class: 'pv-overlay-fg' } }"
                                    @filter="(e) => (currentFilterValue = e.value || '')">
                                    <template #option="{ option }">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-shield-exclamation me-2"></i>
                                            <span>{{ option.label }}</span>
                                        </div>
                                    </template>
                                    <template #header>
                                        <div class="font-medium px-3 py-2">Common Allergens</div>
                                    </template>
                                    <template #footer>
                                        <div class="p-3 d-flex justify-content-between">
                                            <Button label="Add Custom" severity="secondary" variant="text" size="small"
                                                icon="pi pi-plus" @click="addCustom"
                                                :disabled="!currentFilterValue.trim()" />
                                            <div class="d-flex gap-2">
                                                <Button label="Select All" severity="secondary" variant="text"
                                                    size="small" icon="pi pi-check" @click="selectAll" />
                                                <Button label="Clear All" severity="danger" variant="text" size="small"
                                                    icon="pi pi-times" @click="removeAll" />
                                            </div>
                                        </div>
                                    </template>
                                </MultiSelect>

                                <!-- Image -->
                                <div class="row g-3 mt-2 align-items-center">
                                    <div class="col-sm-6 col-md-4">
                                        <div
                                            class="img-drop rounded-3 d-flex align-items-center justify-content-center">
                                            <template v-if="!form.imagePreview">
                                                <div class="text-center small">
                                                    <div class="mb-2">
                                                        <i class="bi bi-image fs-3"></i>
                                                    </div>
                                                    <div>Drag image here</div>
                                                    <div>
                                                        or
                                                        <label class="text-primary fw-semibold" style="
                                                                cursor: pointer;
                                                            ">
                                                            Browse image
                                                            <input type="file" accept="image/*" class="d-none" @change="
                                                                handleImage
                                                            " />
                                                        </label>
                                                    </div>
                                                </div>
                                            </template>
                                            <template v-else>
                                                <img :src="form.imagePreview" class="w-100 h-100 rounded-3"
                                                    style="object-fit: cover" />
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <button class="btn btn-primary rounded-pill px-5 py-2" :disabled="submitting"
                                        @click="submitProduct">
                                        <span v-if="!submitting">Add Product</span>
                                        <span v-else>Saving...</span>
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
