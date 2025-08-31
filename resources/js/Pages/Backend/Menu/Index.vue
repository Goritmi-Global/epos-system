<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted, onUpdated } from "vue";
import Select from "primevue/select";
import MultiSelect from "primevue/multiselect";
import Chips from "primevue/chips";

/* ===================== Demo Data (swap with API later) ===================== */
const menus = ref([
    {
        id: 1,
        name: "Karak Chaii",
        image: "https://picsum.photos/seed/karak/64",
        category: "Bakery",
        price: 2.5,
        status: "Activate",
        description: "",
        nutrition: { calories: 0, fat: 0, protein: 0, carbs: 0 },
        allergies: ["Celery"],
        tags: ["Gluten-Free"],
        ingredients: [], // {id, name, qty, cost}
    },
]);

/** Category master/child store (simple in-memory demo) */
const categories = ref([
    { id: 1, name: "Bakery", subcats: ["Breads", "Cakes"] },
    { id: 2, name: "Poultry", subcats: ["Chicken", "Wings"] },
    { id: 3, name: "Beverages", subcats: ["Tea", "Coffee"] },
]);

/** Flatten for menu Category Select (masters only for now; you can switch to subcats too) */
const categoryOptions = computed(() =>
    categories.value.map((c) => ({ label: c.name, value: c.name }))
);

/** Allergies & Tags for pickers */
const allergyOptions = ref([
    { label: "Celery", value: "Celery" },
    { label: "Eggs", value: "Eggs" },
    { label: "Fish", value: "Fish" },
    { label: "Milk", value: "Milk" },
    { label: "Soybeans", value: "Soybeans" },
    { label: "Peanuts", value: "Peanuts" },
    { label: "Tree nuts", value: "Tree nuts" },
    { label: "Cereals containing gluten", value: "Cereals containing gluten" },
]);
const tagOptions = ref([
    { label: "Halal", value: "Halal" },
    { label: "Haram", value: "Haram" },
    { label: "Gluten-Free", value: "Gluten-Free" },
    { label: "Vegan", value: "Vegan" },
    { label: "Vegetarian", value: "Vegetarian" },
    { label: "Spicy", value: "Spicy" },
]);

/** Inventory items for Ingredients picker */
const inventoryItems = ref([
    {
        id: 11,
        name: "Basmati rice",
        category: "Poultry",
        stock: 4038.4,
        uom: "gram (g)",
        price: 2.2,
        image: "https://picsum.photos/seed/rice/64",
        nutrition: { cal: 123.0, carbs: 12.0, fats: 12.0, protein: 12.0 },
        tags: ["Celery", "Cereals containing gluten", "Gluten-Free"],
    },
    {
        id: 12,
        name: "Brown sugar",
        category: "Bakery",
        stock: 120,
        uom: "kg",
        price: 3.4,
        image: "https://picsum.photos/seed/sugar/64",
        nutrition: { cal: 80, carbs: 20, fats: 0, protein: 0 },
        tags: [],
    },
]);

/* ===================== KPIs ===================== */
const kpiTotal = computed(() => menus.value.length);
const kpiActive = computed(
    () => menus.value.filter((m) => m.status === "Activate").length
);
const kpiDeactive = computed(
    () => menus.value.filter((m) => m.status !== "Activate").length
);
const kpis = computed(() => [
    { label: "Total Menus", value: kpiTotal.value, icon: "package" },
    { label: "Active Menus", value: kpiActive.value, icon: "check-circle" },
    { label: "Deactivate Menus", value: kpiDeactive.value, icon: "slash" },
]);

/* ===================== Search ===================== */
const q = ref("");
const filteredMenus = computed(() => {
    const t = q.value.trim().toLowerCase();
    if (!t) return menus.value;
    return menus.value.filter((m) =>
        [m.name, m.category].join(" ").toLowerCase().includes(t)
    );
});

onMounted(() => window.feather?.replace());
onUpdated(() => window.feather?.replace());

/* ===================== Helpers ===================== */
const money = (n) =>
    new Intl.NumberFormat("en-GB", {
        style: "currency",
        currency: "GBP",
    }).format(+n || 0);
const fakeQuery = (payload) =>
    new Promise((resolve) =>
        setTimeout(() => resolve({ ok: true, payload }), 600)
    );

/* ===================== Add/Edit Menu Modal ===================== */
const isEditing = ref(false);
const editingRow = ref(null);
const form = ref({
    name: "",
    price: null,
    category: "",
    description: "",
    nutrition: { calories: null, fat: null, protein: null, carbs: null },
    allergies: [],
    tags: [],
    imageUrl: "",
    ingredients: [],
});

const openAdd = () => {
    isEditing.value = false;
    editingRow.value = null;
    form.value = {
        name: "",
        price: null,
        category: "",
        description: "",
        nutrition: { calories: null, fat: null, protein: null, carbs: null },
        allergies: [],
        tags: [],
        imageUrl: "",
        ingredients: [],
    };
};

const openEdit = (row) => {
    isEditing.value = true;
    editingRow.value = row;
    form.value = {
        name: row.name,
        price: row.price,
        category: row.category,
        description: row.description || "",
        nutrition: { ...row.nutrition },
        allergies: [...(row.allergies || [])],
        tags: [...(row.tags || [])],
        imageUrl: row.image || "",
        ingredients: [...(row.ingredients || [])],
    };
};

const onSubmit = () => {
    const image = form.value.imageUrl || "https://picsum.photos/seed/menu/64";
    if (isEditing.value) {
        Object.assign(editingRow.value, { ...form.value, image });
        console.log("[Menus] Submit (edit):", editingRow.value);
        fakeQuery({ action: "updateMenu", row: { ...editingRow.value } })
            .then((r) => console.log("✅ then():", r))
            .catch((e) => console.error("❌ catch():", e));
        return;
    }
    const newRow = {
        id: Date.now() + Math.random(),
        name: form.value.name?.trim(),
        image,
        category: form.value.category,
        price: +form.value.price || 0,
        status: "Activate",
        description: form.value.description?.trim(),
        nutrition: { ...form.value.nutrition },
        allergies: [...form.value.allergies],
        tags: [...form.value.tags],
        ingredients: [...form.value.ingredients],
    };
    menus.value.unshift(newRow);
    console.log("[Menus] Submit (create):", newRow);
    fakeQuery({ action: "createMenu", row: newRow })
        .then((r) => console.log("✅ then():", r))
        .catch((e) => console.error("❌ catch():", e));
};

/* ===================== Row Actions ===================== */
const toggleActive = (row) =>
    (row.status = row.status === "Activate" ? "Deactivate" : "Activate");
const removeMenu = (row) => {
    menus.value = menus.value.filter((m) => m !== row);
    console.log("[Menus] Deactivated:", row);
};

/* ===================== Allergies/Tags pickers ===================== */
const showAllergyPicker = ref(false);
const showTagPicker = ref(false);
const tempAllergies = ref([]);
const tempTags = ref([]);

const openAllergyPicker = () => {
    tempAllergies.value = [...form.value.allergies];
    showAllergyPicker.value = true;
};
const openTagPicker = () => {
    tempTags.value = [...form.value.tags];
    showTagPicker.value = true;
};
const confirmAllergies = () => {
    form.value.allergies = [...tempAllergies.value];
    showAllergyPicker.value = false;
};
const confirmTags = () => {
    form.value.tags = [...tempTags.value];
    showTagPicker.value = false;
};

/* ===================== Ingredients picker ===================== */
const showIngPicker = ref(false);
const ingSearch = ref("");
const ingQtyMap = ref({});
const pickedIngredients = ref([]);

const openIngPicker = () => {
    pickedIngredients.value = form.value.ingredients.map((p) => ({ ...p }));
    showIngPicker.value = true;
};
const ingFiltered = computed(() => {
    const t = ingSearch.value.trim().toLowerCase();
    if (!t) return inventoryItems.value;
    return inventoryItems.value.filter((i) =>
        [i.name, i.category].join(" ").toLowerCase().includes(t)
    );
});
const addIng = (it) => {
    const qty = +ingQtyMap.value[it.id] || 0;
    if (qty <= 0) return;
    const found = pickedIngredients.value.find((p) => p.id === it.id);
    const cost = +(qty * (it.price || 0)).toFixed(2);
    if (found) {
        found.qty += qty;
        found.cost = +(found.qty * (it.price || 0)).toFixed(2);
    } else {
        pickedIngredients.value.push({ id: it.id, name: it.name, qty, cost });
    }
    ingQtyMap.value[it.id] = 0;
};
const removePickedIng = (id) =>
    (pickedIngredients.value = pickedIngredients.value.filter(
        (p) => p.id !== id
    ));
const confirmIngredients = () => {
    form.value.ingredients = pickedIngredients.value.map((p) => ({ ...p }));
    showIngPicker.value = false;
};

/* ===================== Category Manager (Master / Child) ===================== */
const showCatMgr = ref(false);
const catMode = ref("master"); // 'master' | 'child'
const catForm = ref({
    masterName: "",
    childParentId: null,
    childNames: [],
});
const masterOptions = computed(() =>
    categories.value.map((c) => ({ label: c.name, value: c.id }))
);

const openCatMgr = () => {
    catMode.value = "master";
    catForm.value = {
        masterName: "",
        childParentId: masterOptions.value[0]?.value ?? null,
        childNames: [],
    };
    showCatMgr.value = true;
};

const saveCategory = () => {
    if (catMode.value === "master") {
        const name = (catForm.value.masterName || "").trim();
        if (!name) return;
        categories.value.push({ id: Date.now(), name, subcats: [] });
        console.log("[Categories] Added master:", name);
    } else {
        const parent = categories.value.find(
            (c) => c.id === catForm.value.childParentId
        );
        if (!parent) return;
        const before = new Set(parent.subcats.map((s) => s.toLowerCase()));
        const added = [];
        (catForm.value.childNames || []).forEach((n) => {
            const v = String(n || "").trim();
            if (!v) return;
            if (!before.has(v.toLowerCase())) {
                parent.subcats.push(v);
                added.push(v);
            }
        });
        console.log("[Categories] Added children:", {
            parent: parent.name,
            added,
        });
    }
    // refresh category select immediately
    console.log(
        "[Categories] Now:",
        JSON.parse(JSON.stringify(categories.value))
    );
    showCatMgr.value = false;
};

/* ===================== Download ===================== */
const downloadAll = () => {
    const header = ["S.#", "Image", "Menu", "Category", "Price", "Status"];
    const lines = menus.value.map((m, i) => [
        i + 1,
        m.image,
        m.name,
        m.category,
        m.price,
        m.status,
    ]);
    const csv = [header, ...lines]
        .map((r) =>
            r.map((v) => `"${String(v ?? "").replace(/"/g, '""')}"`).join(",")
        )
        .join("\n");
    const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = "menus.csv";
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
};
</script>

<template>
    <Master>
        <div class="page-wrapper">
            <div class="container-fluid py-1">
                <!-- Title -->
                <h4 class="fw-semibold mb-3">Overall Menus</h4>

                <!-- KPI Cards -->
                <div class="row g-3">
                    <div
                        v-for="c in kpis"
                        :key="c.label"
                        class="col-6 col-md-4"
                    >
                        <div class="card border-0 shadow-sm rounded-4">
                            <div
                                class="card-body d-flex flex-column justify-content-center text-center"
                            >
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

                <!-- Menus Table -->
                <div class="card border-0 shadow-lg rounded-4 mt-3">
                    <div class="card-body">
                        <div
                            class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3"
                        >
                            <h5 class="mb-0 fw-semibold">Menus</h5>

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
                                    class="btn btn-primary rounded-pill px-4"
                                    data-bs-toggle="modal"
                                    data-bs-target="#menuModal"
                                    @click="openAdd"
                                >
                                    Add Menu
                                </button>

                                <button
                                    class="btn btn-outline-secondary rounded-pill px-4"
                                    @click="downloadAll"
                                >
                                    Download all
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="border-top small text-muted">
                                    <tr>
                                        <th>S.#</th>
                                        <th>Image</th>
                                        <th>Menu</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(m, idx) in filteredMenus"
                                        :key="m.id"
                                    >
                                        <td>{{ idx + 1 }}</td>
                                        <td>
                                            <img
                                                :src="m.image"
                                                class="rounded"
                                                style="
                                                    width: 40px;
                                                    height: 40px;
                                                    object-fit: cover;
                                                "
                                            />
                                        </td>
                                        <td class="fw-semibold">
                                            {{ m.name }}
                                        </td>
                                        <td
                                            class="text-truncate"
                                            style="max-width: 260px"
                                        >
                                            {{ m.category }}
                                        </td>
                                        <td>{{ money(m.price) }}</td>
                                        <td>
                                            <a
                                                href="javascript:void(0)"
                                                class="text-success text-decoration-none"
                                                @click.prevent="toggleActive(m)"
                                            >
                                                {{ m.status }}
                                            </a>
                                        </td>
                                        <td class="text-end">
                                            <div class="dropdown">
                                                <button
                                                    class="btn btn-link text-secondary p-0 fs-5"
                                                    data-bs-toggle="dropdown"
                                                    aria-expanded="false"
                                                    title="Actions"
                                                >
                                                    ⋮
                                                </button>
                                                <ul
                                                    class="dropdown-menu dropdown-menu-end shadow rounded-4 overflow-hidden action-menu"
                                                >
                                                    <li>
                                                        <a
                                                            class="dropdown-item py-2"
                                                            href="javascript:void(0)"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#menuModal"
                                                            @click.prevent="
                                                                openEdit(m)
                                                            "
                                                        >
                                                            <i
                                                                class="bi bi-pencil-square me-2"
                                                            ></i
                                                            >Edit
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a
                                                            class="dropdown-item py-2 text-danger"
                                                            href="javascript:void(0)"
                                                            @click="
                                                                removeMenu(m)
                                                            "
                                                        >
                                                            <i
                                                                class="bi bi-slash-circle me-2"
                                                            ></i
                                                            >Deactivate
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr v-if="filteredMenus.length === 0">
                                        <td
                                            colspan="7"
                                            class="text-center text-muted py-4"
                                        >
                                            No menus found.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ===================== Add/Edit Menu Modal ===================== -->
                <div
                    class="modal fade"
                    id="menuModal"
                    tabindex="-1"
                    aria-hidden="true"
                >
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content rounded-4">
                            <div class="modal-header">
                                <h5 class="modal-title fw-semibold">
                                    Add Menu Item
                                </h5>
                                <button
                                    type="button"
                                    class="btn-close"
                                    data-bs-dismiss="modal"
                                ></button>
                            </div>

                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label"
                                            >Menu Name</label
                                        >
                                        <input
                                            v-model="form.name"
                                            type="text"
                                            class="form-control"
                                            placeholder="Menu Name"
                                        />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"
                                            >Base Price</label
                                        >
                                        <input
                                            v-model.number="form.price"
                                            type="number"
                                            step="0.01"
                                            class="form-control"
                                            placeholder="0.00"
                                        />
                                    </div>

                                    <div class="col-12">
                                        <div
                                            class="d-flex justify-content-between align-items-end"
                                        >
                                            <div class="flex-grow-1 me-2">
                                                <label class="form-label"
                                                    >Category</label
                                                >
                                                <Select
                                                    v-model="form.category"
                                                    :options="categoryOptions"
                                                    optionLabel="label"
                                                    optionValue="value"
                                                    :filter="true"
                                                    placeholder="Select category"
                                                    class="w-100"
                                                    :appendTo="body"
                                                    :autoZIndex="true"
                                                    :baseZIndex="2000"
                                                />
                                            </div>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary rounded-pill"
                                                @click="openCatMgr"
                                            >
                                                Manage
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label"
                                            >Description</label
                                        >
                                        <input
                                            v-model="form.description"
                                            type="text"
                                            class="form-control"
                                            placeholder="Description"
                                        />
                                    </div>
                                </div>

                                <!-- Nutrition -->
                                <div class="mt-4">
                                    <div class="fw-semibold mb-2">
                                        Nutrition Information
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label"
                                                >Calories</label
                                            ><input
                                                v-model.number="
                                                    form.nutrition.calories
                                                "
                                                type="number"
                                                class="form-control"
                                            />
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label"
                                                >Fat (g)</label
                                            ><input
                                                v-model.number="
                                                    form.nutrition.fat
                                                "
                                                type="number"
                                                class="form-control"
                                            />
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label"
                                                >Protein (g)</label
                                            ><input
                                                v-model.number="
                                                    form.nutrition.protein
                                                "
                                                type="number"
                                                class="form-control"
                                            />
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label"
                                                >Carbs (g)</label
                                            ><input
                                                v-model.number="
                                                    form.nutrition.carbs
                                                "
                                                type="number"
                                                class="form-control"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Allergies & Tags -->
                                <div class="row g-4 mt-2">
                                    <div class="col-md-6">
                                        <label class="form-label d-block"
                                            >Allergies</label
                                        >
                                        <button
                                            class="btn btn-outline-secondary btn-sm"
                                            @click="openAllergyPicker"
                                        >
                                            + Select
                                        </button>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label d-block"
                                            >Tags (Halal, Haram, etc.)</label
                                        >
                                        <button
                                            class="btn btn-outline-secondary btn-sm"
                                            @click="openTagPicker"
                                        >
                                            + Select
                                        </button>
                                    </div>
                                </div>

                                <!-- Image + Ingredients -->
                                <div class="row g-3 mt-2 align-items-end">
                                    <div class="row g-3 mt-2 align-items-center">
                                    <div class="col-sm-6 col-md-4">
                                        <div
                                            class="img-drop rounded-3 d-flex align-items-center justify-content-center"
                                        >
                                            <template v-if="!form.imagePreview">
                                                <div class="text-center small">
                                                    <div class="mb-2">
                                                        <i
                                                            class="bi bi-image fs-3"
                                                        ></i>
                                                    </div>
                                                    <div>Drag image here</div>
                                                    <div>
                                                        or
                                                        <label
                                                            class="text-primary fw-semibold"
                                                            style="
                                                                cursor: pointer;
                                                            "
                                                        >
                                                            Browse image
                                                            <input
                                                                type="file"
                                                                accept="image/*"
                                                                class="d-none"
                                                                @change="
                                                                    handleImage
                                                                "
                                                            />
                                                        </label>
                                                    </div>
                                                </div>
                                            </template>
                                            <template v-else>
                                                <img
                                                    :src="form.imagePreview"
                                                    class="w-100 h-100 rounded-3"
                                                    style="object-fit: cover"
                                                />
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                    <div class="col-md-6">
                                        <button
                                            type="button"
                                            class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-2"
                                            @click="openIngPicker"
                                        >
                                            <i class="bi bi-plus-lg"></i>
                                            Ingredients
                                        </button>
                                    </div>
                                </div>

                                <div class="d-flex gap-3 mt-4">
                                    <button
                                        class="btn btn-outline-secondary rounded-pill px-4"
                                    >
                                        Discard
                                    </button>
                                    <button
                                        class="btn btn-primary rounded-pill px-5"
                                        @click="onSubmit"
                                        data-bs-dismiss="modal"
                                    >
                                        {{ isEditing ? "Save" : "Add" }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Allergies Picker -->
                <div
                    class="modal fade"
                    :class="{ show: showAllergyPicker }"
                    tabindex="-1"
                    style="display: none"
                    v-show="showAllergyPicker"
                >
                    <div class="modal-dialog">
                        <div class="modal-content rounded-4">
                            <div class="modal-header">
                                <h5 class="modal-title">Select Allergies</h5>
                                <button
                                    class="btn-close"
                                    @click="showAllergyPicker = false"
                                ></button>
                            </div>
                            <div class="modal-body">
                                <MultiSelect
                                    v-model="tempAllergies"
                                    :options="allergyOptions"
                                    optionLabel="label"
                                    optionValue="value"
                                    filter
                                    display="chip"
                                    class="w-100"
                                    appendTo="body"
                                    :pt="{ panel: { class: 'pv-overlay-fg' } }"
                                />
                                <button
                                    class="btn btn-primary w-100 mt-3"
                                    @click="confirmAllergies"
                                >
                                    Confirm
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tags Picker -->
                <div
                    class="modal fade"
                    :class="{ show: showTagPicker }"
                    tabindex="-1"
                    style="display: none"
                    v-show="showTagPicker"
                >
                    <div class="modal-dialog">
                        <div class="modal-content rounded-4">
                            <div class="modal-header">
                                <h5 class="modal-title">Select Tags</h5>
                                <button
                                    class="btn-close"
                                    @click="showTagPicker = false"
                                ></button>
                            </div>
                            <div class="modal-body">
                                <MultiSelect
                                    v-model="tempTags"
                                    :options="tagOptions"
                                    optionLabel="label"
                                    optionValue="value"
                                    filter
                                    display="chip"
                                    class="w-100"
                                    appendTo="body"
                                    :pt="{ panel: { class: 'pv-overlay-fg' } }"
                                />
                                <button
                                    class="btn btn-primary w-100 mt-3"
                                    @click="confirmTags"
                                >
                                    Confirm
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ingredients Picker -->
                <div
                    class="modal fade"
                    :class="{ show: showIngPicker }"
                    tabindex="-1"
                    style="display: none"
                    v-show="showIngPicker"
                >
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content rounded-4">
                            <div class="modal-header">
                                <h5 class="modal-title">Add Ingredients</h5>
                                <button
                                    class="btn-close"
                                    @click="showIngPicker = false"
                                ></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-lg-5">
                                        <div class="mb-3 position-relative">
                                            <i
                                                class="bi bi-search position-absolute"
                                                style="
                                                    left: 12px;
                                                    top: 50%;
                                                    transform: translateY(-50%);
                                                    color: #6b7280;
                                                "
                                            ></i>
                                            <input
                                                v-model="ingSearch"
                                                class="form-control ps-5"
                                                placeholder="Search..."
                                            />
                                        </div>
                                        <div class="vstack gap-3">
                                            <div
                                                class="inv-card p-3 border rounded-3"
                                                v-for="it in ingFiltered"
                                                :key="it.id"
                                            >
                                                <div class="d-flex gap-3">
                                                    <img
                                                        :src="it.image"
                                                        class="rounded"
                                                        width="54"
                                                        height="54"
                                                    />
                                                    <div class="flex-grow-1">
                                                        <div
                                                            class="fw-semibold"
                                                        >
                                                            {{ it.name }}
                                                        </div>
                                                        <div
                                                            class="small text-muted"
                                                        >
                                                            Category:
                                                            {{ it.category }} •
                                                            Stock:
                                                            {{ it.stock }} •
                                                            {{ it.uom }} •
                                                            {{
                                                                money(it.price)
                                                            }}
                                                        </div>
                                                        <div
                                                            class="d-flex flex-wrap gap-1 mt-2"
                                                        >
                                                            <span
                                                                class="badge rounded-pill text-bg-warning"
                                                                v-if="
                                                                    it.nutrition
                                                                        ?.cal
                                                                "
                                                                >Calories:
                                                                {{
                                                                    it.nutrition
                                                                        .cal
                                                                }}</span
                                                            >
                                                            <span
                                                                class="badge rounded-pill text-bg-success"
                                                                v-if="
                                                                    it.nutrition
                                                                        ?.carbs
                                                                "
                                                                >Carbs:
                                                                {{
                                                                    it.nutrition
                                                                        .carbs
                                                                }}</span
                                                            >
                                                            <span
                                                                class="badge rounded-pill text-bg-primary"
                                                                v-if="
                                                                    it.nutrition
                                                                        ?.fats
                                                                "
                                                                >Fats:
                                                                {{
                                                                    it.nutrition
                                                                        .fats
                                                                }}</span
                                                            >
                                                            <span
                                                                class="badge rounded-pill text-bg-info"
                                                                v-if="
                                                                    it.nutrition
                                                                        ?.protein
                                                                "
                                                                >Protein:
                                                                {{
                                                                    it.nutrition
                                                                        .protein
                                                                }}</span
                                                            >
                                                        </div>
                                                        <div
                                                            class="d-flex align-items-center gap-2 mt-3"
                                                        >
                                                            <span>Qty:</span>
                                                            <input
                                                                v-model.number="
                                                                    ingQtyMap[
                                                                        it.id
                                                                    ]
                                                                "
                                                                type="number"
                                                                min="0"
                                                                class="form-control"
                                                                style="
                                                                    width: 90px;
                                                                "
                                                            />
                                                            <button
                                                                class="btn btn-success d-flex align-items-center gap-2"
                                                                @click="
                                                                    addIng(it)
                                                                "
                                                            >
                                                                <i
                                                                    class="bi bi-plus-lg"
                                                                ></i>
                                                                Add
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-7">
                                        <div class="border rounded-3">
                                            <table class="table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th
                                                            style="width: 120px"
                                                        >
                                                            Qty
                                                        </th>
                                                        <th
                                                            style="width: 140px"
                                                        >
                                                            Cost
                                                        </th>
                                                        <th
                                                            class="text-end"
                                                            style="width: 120px"
                                                        >
                                                            Action
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr
                                                        v-for="p in pickedIngredients"
                                                        :key="p.id"
                                                    >
                                                        <td>{{ p.name }}</td>
                                                        <td>
                                                            <input
                                                                v-model.number="
                                                                    p.qty
                                                                "
                                                                type="number"
                                                                class="form-control form-control-sm"
                                                            />
                                                        </td>
                                                        <td>
                                                            {{ money(p.cost) }}
                                                        </td>
                                                        <td class="text-end">
                                                            <button
                                                                class="btn btn-sm btn-link text-danger"
                                                                @click="
                                                                    removePickedIng(
                                                                        p.id
                                                                    )
                                                                "
                                                            >
                                                                Remove
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <tr
                                                        v-if="
                                                            pickedIngredients.length ===
                                                            0
                                                        "
                                                    >
                                                        <td
                                                            colspan="4"
                                                            class="text-center text-muted py-4"
                                                        >
                                                            No ingredients
                                                            selected
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    class="btn btn-primary w-100 mt-4"
                                    @click="confirmIngredients"
                                >
                                    Confirm
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category Manager (Master / Child) -->
                <div
                    class="modal fade"
                    :class="{ show: showCatMgr }"
                    tabindex="-1"
                    style="display: none"
                    v-show="showCatMgr"
                >
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content rounded-4">
                            <div class="modal-header">
                                <h5 class="modal-title">Manage Categories</h5>
                                <button
                                    class="btn-close"
                                    @click="showCatMgr = false"
                                ></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label d-block"
                                        >Type</label
                                    >
                                    <div class="d-flex gap-4">
                                        <div class="form-check">
                                            <input
                                                class="form-check-input"
                                                type="radio"
                                                id="catMaster"
                                                value="master"
                                                v-model="catMode"
                                            />
                                            <label
                                                class="form-check-label"
                                                for="catMaster"
                                                >Master</label
                                            >
                                        </div>
                                        <div class="form-check">
                                            <input
                                                class="form-check-input"
                                                type="radio"
                                                id="catChild"
                                                value="child"
                                                v-model="catMode"
                                            />
                                            <label
                                                class="form-check-label"
                                                for="catChild"
                                                >Child of (Master)</label
                                            >
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="catMode === 'master'"
                                    class="row g-3"
                                >
                                    <div class="col-md-8">
                                        <label class="form-label"
                                            >Master Name</label
                                        >
                                        <input
                                            v-model="catForm.masterName"
                                            class="form-control"
                                            placeholder="e.g., Bakery"
                                        />
                                    </div>
                                </div>

                                <div v-else class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Master</label>
                                        <Select
                                            v-model="catForm.childParentId"
                                            :options="masterOptions"
                                            optionLabel="label"
                                            optionValue="value"
                                            :filter="true"
                                            class="w-100"
                                            appendTo="body"
                                        />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label"
                                            >Child Name(s)</label
                                        >
                                        <Chips
                                            v-model="catForm.childNames"
                                            separator=","
                                            :addOnBlur="true"
                                            placeholder="Type & Enter (comma for many)"
                                        />
                                    </div>
                                </div>

                                <button
                                    class="btn btn-primary w-100 mt-4"
                                    @click="saveCategory"
                                >
                                    Save
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

/* Image drop */
.img-drop {
    height: 120px;
    border: 2px dashed #cbd5e1;
    background: #f8fafc;
}

/* PrimeVue overlays above Bootstrap modal */
.pv-overlay-fg {
    z-index: 2000 !important;
}
:deep(.p-select-panel),
:deep(.p-multiselect-panel),
:deep(.p-dropdown-panel) {
    z-index: 2000 !important;
}
</style>
