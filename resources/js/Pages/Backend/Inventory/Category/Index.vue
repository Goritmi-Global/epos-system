<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted, onUpdated } from "vue";

/* ---------------- Demo data (swap with API later) ---------------- */
const categories = ref([
    {
        id: 1,
        name: "Poultry",
        subCategoryText: "Sub Cat. of Poultry, Sub Cat. of ...",
        icon: "🍗",
        totalValue: 4038.4,
        totalItem: 1,
        outOfStock: 0,
        lowStock: 0,
        inStock: 1,
    },
    {
        id: 2,
        name: "Flour & Baking",
        subCategoryText: "–",
        icon: "🥯",
        totalValue: 0,
        totalItem: 0,
        outOfStock: 0,
        lowStock: 0,
        inStock: 0,
    },
    {
        id: 3,
        name: "Category B",
        subCategoryText: "–",
        icon: "🌿",
        totalValue: 0,
        totalItem: 0,
        outOfStock: 0,
        lowStock: 0,
        inStock: 0,
    },
    {
        id: 4,
        name: "Meat",
        subCategoryText: "–",
        icon: "🍔",
        totalValue: 0,
        totalItem: 0,
        outOfStock: 0,
        lowStock: 0,
        inStock: 0,
    },
]);

/* ---------------- KPI (fake) ---------------- */
const kpi = computed(() => [
    { label: "Categories", value: categories.value.length, icon: "layers" },
    {
        label: "Total Items",
        value: categories.value.reduce((a, c) => a + c.totalItem, 0),
        icon: "package",
    },
    {
        label: "Low Stock",
        value: categories.value.reduce((a, c) => a + c.lowStock, 0),
        icon: "alert-triangle",
    },
    {
        label: "Out of Stock",
        value: categories.value.reduce((a, c) => a + c.outOfStock, 0),
        icon: "slash",
    },
]);

/* ---------------- Search ---------------- */
const q = ref("");
const filtered = computed(() => {
    const t = q.value.trim().toLowerCase();
    if (!t) return categories.value;
    return categories.value.filter((c) =>
        [c.name, c.subCategoryText].some((v) =>
            (v || "").toLowerCase().includes(t)
        )
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
    manualName.value = "";
    manualActive.value = true;
    manualIcon.value = iconOptions[0];
    commonChips.value = commonChips.value.map((c) => ({
        ...c,
        selected: false,
    }));
};

/* ---------------- Submit (console + Promise then/catch) ---------------- */
const submitting = ref(false);

const submitCategory = () => {
    // build payload
    const selectedCommons = commonChips.value.filter((c) => c.selected);
    const payload = {
        isSubCategory: isSub.value,
        manual: manualName.value
            ? [
                  {
                      name: manualName.value,
                      icon: manualIcon.value.value,
                      active: manualActive.value,
                  },
              ]
            : [],
        selectedCommons: selectedCommons.map((c) => ({
            name: c.label,
            icon: c.icon,
            active: true,
        })),
    };

    console.log("[InventoryCategory] Submit payload:", payload);

    // fake API
    submitting.value = true;
    fakeApi(payload)
        .then((resp) => {
            console.log("✅ then():", resp.message);
            // mock add to table so user sees something change
            const toAdd = [...payload.manual, ...payload.selectedCommons];
            if (toAdd.length) {
                toAdd.forEach((c) => {
                    categories.value.push({
                        id: Date.now() + Math.random(),
                        name: c.name,
                        subCategoryText: isSub.value ? "Sub Category" : "–",
                        icon: c.icon || "🧰",
                        totalValue: 0,
                        totalItem: 0,
                        outOfStock: 0,
                        lowStock: 0,
                        inStock: 0,
                    });
                });
            }
        })
        .catch((err) => {
            console.error("❌ catch():", err?.message || err);
            alert("Failed to add categories (demo). See console for details.");
        })
        .finally(() => {
            submitting.value = false;
            // close modal
            const m = bootstrap.Modal.getInstance(
                document.getElementById("addCatModal")
            );
            m?.hide();
            resetModal();
        });
};

function fakeApi(data) {
    return new Promise((resolve, reject) => {
        setTimeout(() => {
            // flip this to `reject(new Error("Network down"))` to test catch
            resolve({ ok: true, message: "Saved (demo)", data });
        }, 800);
    });
}

/* ---------------- Row actions (wire later) ---------------- */
const editRow = (row) => {
    console.log("Edit row:", row);
};
</script>

<template>
    <Master>
        <div class="page-wrapper">
            <div class="container-fluid py-3">
                <h4 class="fw-semibold mb-3">Inventory Categories</h4>

                <!-- KPI -->
                <div class="row g-3">
                    <div v-for="c in kpi" :key="c.label" class="col-6 col-md-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div
                                class="card-body d-flex flex-column justify-content-center text-center"
                            >
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
                                    class="btn btn-primary rounded-pill px-4"
                                    data-bs-toggle="modal"
                                    data-bs-target="#addCatModal"
                                >
                                    Add Category
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
                                                href="#"
                                                >Download as PDF</a
                                            >
                                        </li>
                                        <li>
                                            <a
                                                class="dropdown-item py-2"
                                                href="#"
                                                >Download as Excel</a
                                            >
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
                                            {{ row.subCategoryText }}
                                        </td>
                                        <td>
                                            <div
                                                class="rounded d-inline-flex align-items-center justify-content-center img-chip"
                                            >
                                                <span class="fs-5">{{
                                                    row.icon
                                                }}</span>
                                            </div>
                                        </td>
                                        <td>{{ money(row.totalValue) }}</td>
                                        <td>{{ row.totalItem }}</td>
                                        <td>{{ row.outOfStock }}</td>
                                        <td>{{ row.lowStock }}</td>
                                        <td>{{ row.inStock }}</td>
                                        <td class="text-end">
                                            <div class="dropdown">
                                                <button
                                                    class="btn btn-link text-secondary p-0 fs-5"
                                                    data-bs-toggle="dropdown"
                                                    title="Actions"
                                                >
                                                    ⋮
                                                </button>
                                                <ul
                                                    class="dropdown-menu dropdown-menu-end shadow rounded-4 overflow-hidden"
                                                >
                                                    <li>
                                                        <a
                                                            class="dropdown-item py-2"
                                                            href="javascript:void(0)"
                                                            @click="
                                                                editRow(row)
                                                            "
                                                        >
                                                            <i
                                                                class="bi bi-pencil-square me-2"
                                                            ></i>
                                                            Edit
                                                        </a>
                                                    </li>
                                                </ul>
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
                                    Add Raw Material Categories
                                </h5>
                                <button
                                    type="button"
                                    class="btn-close"
                                    data-bs-dismiss="modal"
                                    aria-label="Close"
                                    @click="resetModal"
                                ></button>
                            </div>

                            <div class="modal-body">
                                <!-- Row 1 -->
                                <div class="row g-3">
                                    <div class="col-lg-6">
                                        <label class="form-label d-block mb-2"
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
                                                    @change="isSub = true"
                                                    name="isSub"
                                                />
                                                <label class="form-check-label"
                                                    >Yes</label
                                                >
                                            </div>
                                            <div class="form-check">
                                                <input
                                                    class="form-check-input"
                                                    type="radio"
                                                    :checked="!isSub"
                                                    @change="isSub = false"
                                                    name="isSub"
                                                />
                                                <label class="form-check-label"
                                                    >No</label
                                                >
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <label class="form-label d-block mb-2"
                                            >Manual Icon</label
                                        >
                                        <div class="dropdown w-100">
                                            <button
                                                class="btn btn-outline-secondary w-100 d-flex justify-content-between align-items-center rounded-3"
                                                data-bs-toggle="dropdown"
                                            >
                                                <span
                                                    ><span class="me-2">{{
                                                        manualIcon.value
                                                    }}</span>
                                                    {{ manualIcon.label }}</span
                                                >
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

                                    <div class="col-12">
                                        <label class="form-label"
                                            >Category Name (manual
                                            add/edit)</label
                                        >
                                        <input
                                            v-model="manualName"
                                            type="text"
                                            class="form-control"
                                            placeholder="e.g., Produce (Veg/Fruit)"
                                        />
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label d-block mb-2"
                                            >Active (for manual entry)</label
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

                                <!-- Common categories -->
                                <div
                                    class="d-flex align-items-center justify-content-between mb-2"
                                >
                                    <h6 class="mb-0">
                                        Common Raw Material Categories:
                                    </h6>
                                    <button
                                        type="button"
                                        class="btn btn-outline-primary btn-sm rounded-pill"
                                        @click="selectAll"
                                    >
                                        Select All
                                    </button>
                                </div>

                                <div class="d-flex flex-wrap gap-2">
                                    <button
                                        v-for="chip in commonChips"
                                        :key="chip.value"
                                        type="button"
                                        class="btn rounded-pill chip"
                                        :class="
                                            chip.selected
                                                ? 'btn-primary'
                                                : 'btn-outline-secondary'
                                        "
                                        @click="chip.selected = !chip.selected"
                                    >
                                        <span class="me-2">{{ chip.icon }}</span
                                        >{{ chip.label }}
                                    </button>
                                </div>

                                <div class="mt-4">
                                    <button
                                        class="btn btn-primary rounded-pill px-4"
                                        :disabled="submitting"
                                        @click="submitCategory"
                                    >
                                        <span v-if="!submitting"
                                            >Add Category(ies)</span
                                        >
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
