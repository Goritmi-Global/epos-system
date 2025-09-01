<script setup>
import { ref, computed, onMounted, nextTick } from "vue";
import axios from 'axios'; // Add axios import
import MultiSelect from "primevue/multiselect";
import Button from "primevue/button";
import { toast } from "vue3-toastify";


// API data from database
const units = ref([]);
const unitLoading = ref(false);
const unitPage = ref(1);
const unitPerPage = ref(15);
const unitQ = ref("");

const fetchUnits = () => {
    unitLoading.value = true;

    return axios
        .get("/units", {
            params: { q: unitQ.value, page: unitPage.value, per_page: unitPerPage.value },
        })
        .then(({ data }) => {
            // Handle Laravel paginated response
            units.value = data?.data ?? data?.units?.data ?? data ?? [];
            return nextTick();
        })
        .then(() => {
            window.feather?.replace?.();
        })
        .catch((err) => {
            console.error("Error fetching units:", err);
        })
        .finally(() => {
            unitLoading.value = false;
        });
};

// Call fetchUnits when component mounts
onMounted(() => {
    fetchUnits();
});

// static/common list for dropdown
const options = ref([
    { name: "kilogram (kg)" },
    { name: "litre (L)" },
    { name: "millilitre (ml)" },
    { name: "pound (lb)" },
    { name: "ounce (oz)" },
    { name: "pint" },
    { name: "gallon" },
    { name: "piece (pc)" },
    { name: "dozen (doz)" },
    { name: "crate" },
    { name: "box" },
    { name: "bottle" },
    { name: "pack" },
    { name: "serving" },
    { name: "bunch" },
]);

// select state
const selected = ref([]); // array of strings (option.name)
const filterText = ref(""); // typed text in the filter box

// form mode
const isEditing = ref(false);
const isViewing = ref(false);
const editingRow = ref(null);
const editName = ref("");

// search - now works with units from database
const q = ref("");
const filteredUnits = computed(() => {
    const t = q.value.trim().toLowerCase();
    return t
        ? units.value.filter((u) => u.name.toLowerCase().includes(t))
        : units.value;
});

// actions
const selectAll = () => (selected.value = options.value.map((o) => o.name));
const removeAll = () => (selected.value = []);

const addCustom = () => {
    const label = (filterText.value || "").trim();
    if (!label) return;
    if (
        !options.value.some((o) => o.name.toLowerCase() === label.toLowerCase())
    ) {
        options.value.push({ name: label });
    }
    if (!selected.value.includes(label)) {
        selected.value = [...selected.value, label];
    }
    filterText.value = "";
};

const openAdd = () => {
    isEditing.value = false;
    isViewing.value = false;
    selected.value = [];
    filterText.value = "";
};

const openView = (unit) => {
    isViewing.value = true;
    isEditing.value = false;
    editingRow.value = unit;
    editName.value = unit.name;
};

const openEdit = (unit) => {
    isEditing.value = true;
    isViewing.value = false;
    editingRow.value = unit;
    editName.value = unit.name;
};

const removeUnit = (unit) => {
    runQuery({ action: "delete", row: unit });
};

// API operations
const runQuery = async (payload) => {
    try {
        if (payload.action === "create") {
            for (const row of payload.added) {
                await axios.post("/units", { name: row.name });
            }
            await fetchUnits();
            toast.success("Unit(s) created successfully ✅", { autoClose: 2000 });

        } else if (payload.action === "update") {
            await axios.put(`/units/${payload.row.id}`, {
                name: payload.row.name,
            });
            await fetchUnits();
            toast.success("Unit updated successfully ✅", { autoClose: 2000 });

        } else if (payload.action === "delete") {
            await axios.delete(`/units/${payload.row.id}`);
            await fetchUnits();
            toast.success("Unit deleted successfully ✅", { autoClose: 2000 });
        }
    } catch (err) {
        console.error("[Units] query ERROR:", err.response?.data || err);
        toast.error("Operation failed ❌", { autoClose: 2000 });
        throw err;
    }
};

const onSubmit = async () => {
    try {
        if (isEditing.value) {
            // --- Update one ---
            if (!editName.value?.trim()) return;

            const updatedRow = { ...editingRow.value, name: editName.value.trim() };

            await runQuery({ action: "update", row: updatedRow });

            isEditing.value = false;
            editingRow.value = null;
            editName.value = "";
        } else {
            // --- Add many ---
            const existing = new Set(units.value.map((u) => u.name));
            const added = [];

            selected.value.forEach((n) => {
                if (!existing.has(n)) {
                    added.push({ name: n });
                }
            });

            if (added.length > 0) {
                await runQuery({ action: "create", added });
            }

            selected.value = [];
        }
    } catch (err) {
        console.error("[Units] Submit ERROR:", err.response?.data || err);
    }
};

// Add missing functions
const onView = (unit) => {
    console.log("View unit:", unit);
};

const onEdit = (unit) => {
    openEdit(unit);
};

const onRemove = (unit) => {
    if (confirm(`Are you sure you want to delete "${unit.name}"?`)) {
        removeUnit(unit);
    }
};

const onDownload = (format) => {
    console.log(`Download ${format}`);
};

</script>

<template>
    <div class="card border-0 shadow-lg rounded-4">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Units</h4>
                <div class="d-flex gap-2">
                    <div class="search-wrap">
                        <i class="bi bi-search"></i>
                        <input v-model="q" class="form-control search-input" placeholder="Search" />
                    </div>
                    <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal"
                        data-bs-target="#modalUnitForm" @click="openAdd">
                        Add Unit
                    </button>
                    <!-- Download all -->
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary rounded-pill px-4 dropdown-toggle"
                            data-bs-toggle="dropdown">
                            Download all
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow rounded-4 py-2">
                            <li>
                                <a class="dropdown-item py-2" href="javascript:;" @click="onDownload('pdf')">Download as
                                    PDF</a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="javascript:;" @click="onDownload('excel')">Download
                                    as Excel</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="table-responsive" v-if="!unitLoading">
                <table class="table table-hover">
                    <thead class="border-top small text-muted">
                        <tr>
                            <th>S. #</th>
                            <th>Unit</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(unit, i) in filteredUnits" :key="unit.id">
                            <td>{{ i + 1 }}</td>
                            <td class="fw-semibold">{{ unit.name }}</td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-link text-secondary p-0 fs-5" data-bs-toggle="dropdown"
                                        title="Actions">
                                        ⋮
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow rounded-4 overflow-hidden">
                                        <li>
                                            <a class="dropdown-item py-2" href="javascript:;" @click="openView(unit)" data-bs-toggle="modal" data-bs-target="#modalUnitForm"><i
                                                    data-feather="eye" class="me-2"></i>View</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item py-2" href="javascript:;" @click="openEdit(unit)" data-bs-toggle="modal" data-bs-target="#modalUnitForm"><i
                                                    data-feather="edit-2" class="me-2"></i>Edit</a>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider" />
                                        </li>
                                        <li>
                                            <a class="dropdown-item py-2 text-danger" href="javascript:;"
                                                @click="removeUnit(unit)"><i data-feather="trash-2"
                                                    class="me-2"></i>Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="filteredUnits.length === 0">
                            <td colspan="3" class="text-center text-muted py-4">
                                No units found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Loading state -->
            <div v-if="unitLoading" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit/View Modal -->
    <div class="modal fade" id="modalUnitForm" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ isViewing ? "View Unit" : isEditing ? "Edit Unit" : "Add Unit(s)" }}
                    </h5>
                   <button type="button" class="btn btn-close" data-bs-dismiss="modal" aria-label="Close">
                        ×
                    </button>
                </div>

                <div class="modal-body">
                    <!-- View mode -->
                    <div v-if="isViewing">
                        <label class="form-label">Unit Name</label>
                        <input v-model="editName" class="form-control" :disabled="true" />
                    </div>

                    <!-- Edit one -->
                    <div v-else-if="isEditing">
                        <label class="form-label">Unit Name</label>
                        <input v-model="editName" class="form-control" placeholder="e.g., kilogram (kg)" />
                    </div>

                    <!-- Add many -->
                    <div v-else>
                        <MultiSelect v-model="selected" :options="options" optionLabel="name" optionValue="name" filter
                            display="chip" placeholder="Choose units or type to add" class="w-100" appendTo="self"
                            :pt="{ panel: { class: 'pv-overlay-fg' } }" @filter="(e) => (filterText = e.value || '')">
                            <template #option="{ option }">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-basket3 me-2"></i>
                                    <span>{{ option.name }}</span>
                                </div>
                            </template>

                            <template #header>
                                <div class="font-medium px-3 py-2">
                                    Common Units
                                </div>
                            </template>

                            <template #footer>
                                <div class="p-3 d-flex justify-content-between">
                                    <Button label="Add New" severity="secondary" variant="text" size="small"
                                        icon="pi pi-plus" @click="addCustom" />
                                    <div class="d-flex gap-2">
                                        <Button label="Select All" severity="secondary" variant="text" size="small"
                                            icon="pi pi-check" @click="selectAll" />
                                        <Button label="Remove All" severity="danger" variant="text" size="small"
                                            icon="pi pi-times" @click="removeAll" />
                                    </div>
                                </div>
                            </template>
                        </MultiSelect>
                    </div>

                    <!-- Only show submit button for edit/add, not for view -->
                    <button v-if="!isViewing" class="btn btn-primary rounded-pill w-100 mt-4" @click="onSubmit" data-bs-dismiss="modal">
                        {{ isEditing ? "Save Changes" : "Add Unit(s)" }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
:root {
    --brand: #1c0d82;
}

.btn-primary {
    background: var(--brand);
    border-color: var(--brand);
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
}

/* keep PrimeVue overlays above Bootstrap modal/backdrop */
.pv-overlay-fg {
    z-index: 2000 !important;
}

:deep(.p-multiselect-panel),
:deep(.p-select-panel),
:deep(.p-dropdown-panel) {
    z-index: 2000 !important;
}

:deep(.p-multiselect) {
    width: 100%;
}

:deep(.p-multiselect-token) {
    margin: 0.15rem;
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
</style>