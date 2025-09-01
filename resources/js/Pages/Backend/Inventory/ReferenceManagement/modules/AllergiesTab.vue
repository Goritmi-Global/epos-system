<script setup>
import { ref, computed, onMounted } from "vue";
import MultiSelect from "primevue/multiselect";
import Button from "primevue/button";
import { toast } from "vue3-toastify";
import axios from 'axios'  // Add this import
const viewingRow = ref(null);

const rows = ref([
    { id: 1, name: "Milk" },
    { id: 2, name: "Eggs" },
]);

const allergies = ref([]);
const allergyLoading = ref(false);
const allergyPage = ref(1);
const allergyPerPage = ref(15);
const allergyQ = ref("");
const loading = ref(false);

const fetchAllergies = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get("/allergies", {
            params: { q: filterText.value } // optional search
        });

        // If paginated: data.data, else: data
        allergies.value = data.data ?? data;
    } catch (err) {
        console.error("Error fetching allergies:", err);
    } finally {
        loading.value = false;
    }
};

onMounted(fetchAllergies);




const options = ref([
    { label: "Crustaceans", value: "Crustaceans" },
    { label: "Eggs", value: "Eggs" },
    { label: "Fish", value: "Fish" },
    { label: "Lupin", value: "Lupin" },
    { label: "Milk", value: "Milk" },
    { label: "Molluscs", value: "Molluscs" },
    { label: "Mustard", value: "Mustard" },
    { label: "Peanuts", value: "Peanuts" },
    { label: "Sesame seeds", value: "Sesame seeds" },
    { label: "Soybeans", value: "Soybeans" },
    {
        label: "Sulphur dioxide / sulphites",
        value: "Sulphur dioxide / sulphites",
    },
    { label: "Tree nuts", value: "Tree nuts" },
]);

const selected = ref([]);
const filterText = ref("");

const isEditing = ref(false);
const editingRow = ref(null);
const editName = ref("");

const q = ref("");
const filtered = computed(() => {
    const t = q.value.trim().toLowerCase();
    return t
        ? allergies.value.filter((a) => a.name.toLowerCase().includes(t))
        : allergies.value;
});


const selectAll = () => (selected.value = options.value.map((o) => o.value));
const removeAll = () => (selected.value = []);

const addCustom = () => {
    const name = filterText.value?.trim();
    if (!name) return;
    if (
        !options.value.some((o) => o.label.toLowerCase() === name.toLowerCase())
    ) {
        options.value.push({ label: name, value: name });
    }
    if (!selected.value.includes(name))
        selected.value = [...selected.value, name];
    filterText.value = "";
};

const openAdd = () => {
    isEditing.value = false;
    selected.value = [];
    filterText.value = "";
};
const openEdit = (row) => {
    isEditing.value = true;
    editingRow.value = row;
    editName.value = row.name;
};
const removeRow = (row) => (rows.value = rows.value.filter((r) => r !== row));

const runQuery = async (payload) => {
    try {
        console.log(payload);
        if (payload.action === "create") {
            await axios.post("/allergies", { names: payload.added.map(row => row.name) });
            await fetchAllergies();
            toast.success("Allergy(s) created successfully ✅", { autoClose: 2000 });

        } else if (payload.action === "update") {
            // Update existing allergy
            await axios.put(`/allergies/${payload.row.id}`, {
                name: payload.row.name,
            });
            await fetchAllergies();
            toast.success("Allergy updated successfully ✅", { autoClose: 2000 });

        } else if (payload.action === "delete") {
            // Delete allergy
            await axios.delete(`/allergies/${payload.row.id}`);
            await fetchAllergies();
            toast.success("Allergy deleted successfully ✅", { autoClose: 2000 });
        }
    } catch (err) {
        console.error("[Allergies] query ERROR:", err.response?.data || err);
        toast.error("Operation failed ❌", { autoClose: 2000 });
        throw err;
    }
};

const onSubmit = async () => {
    try {
        if (isEditing.value) {
            if (!editName.value?.trim()) return;

            const updatedRow = { ...editingRow.value, name: editName.value.trim() };
            await runQuery({ action: "update", row: updatedRow });

            // reset
            isEditing.value = false;
            editingRow.value = null;
            editName.value = "";
        }
        else {
            // --- Add many ---
            const existing = new Set(allergies.value.map((a) => a.name));
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
        console.error("[Allergies] Submit ERROR:", err.response?.data || err);
    }
};

const onEdit = (row) => {
    isEditing.value = true;
    editingRow.value = { ...row }; // keep original row for id
    editName.value = row.name;     // pre-fill input
    const modal = new bootstrap.Modal(document.getElementById("modalAllergyForm"));
    modal.show();
};

const onRemove = async (row) => {
    if (!confirm(`Delete allergy "${row.name}"?`)) return;
    await runQuery({ action: "delete", row });
};



const onView = (row) => {
    viewingRow.value = row;
    const modal = new bootstrap.Modal(document.getElementById("modalAllergyView"));
    modal.show();
};
</script>

<template>
    <div class="card border-0 shadow-lg rounded-4">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Allergies</h4>
                <div class="d-flex gap-2">
                    <div class="search-wrap">
                        <i class="bi bi-search"></i>
                        <input v-model="q" class="form-control search-input" placeholder="Search" />
                    </div>
                    <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal"
                        data-bs-target="#modalAllergyForm" @click="openAdd">
                        Add Allergy
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

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="border-top small text-muted">
                        <tr>
                            <th>S. #</th>
                            <th>Allergy</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(r, i) in allergies" :key="r.id">
                            <td>{{ i + 1 }}</td>
                            <td class="fw-semibold">{{ r.name }}</td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-link text-secondary p-0 fs-5" data-bs-toggle="dropdown"
                                        title="Actions">
                                        ⋮
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow rounded-4 overflow-hidden">
                                        <li>
                                            <a class="dropdown-item py-2" href="javascript:;" @click="onView(r)"><i
                                                    data-feather="eye" class="me-2"></i>View</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item py-2" href="javascript:;" @click="onEdit(r)"><i
                                                    data-feather="edit-2" class="me-2"></i>Edit</a>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider" />
                                        </li>
                                        <li>
                                            <a class="dropdown-item py-2 text-danger" href="javascript:;"
                                                @click="onRemove(r)"><i data-feather="trash-2"
                                                    class="me-2"></i>Delete</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="filtered.length === 0">
                            <td colspan="3" class="text-center text-muted py-4">
                                No allergies found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="modalAllergyForm" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ isEditing ? "Edit Allergy" : "Add Allergy(s)" }}
                    </h5>
                    <button type="button" class="btn btn-close" data-bs-dismiss="modal" aria-label="Close">
                        ×
                    </button>
                </div>
                <div class="modal-body">
                    <div v-if="isEditing">
                        <label class="form-label">Allergy</label>
                        <input v-model="editName" class="form-control" placeholder="e.g., Milk" />
                    </div>
                    <div v-else>
                        <MultiSelect v-model="selected" :options="options" optionLabel="label" optionValue="value"
                            filter display="chip" placeholder="Choose allergies or type to add" class="w-100"
                            appendTo="self" :pt="{ panel: { class: 'pv-overlay-fg' } }"
                            @filter="(e) => (filterText = e.value || '')">
                            <template #option="{ option }">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-shield-exclamation me-2"></i>
                                    <span>{{ option.label }}</span>
                                </div>
                            </template>
                            <template #header>
                                <div class="font-medium px-3 py-2">
                                    Common Allergens
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

                    <button class="btn btn-primary rounded-pill w-100 mt-4" @click="onSubmit" data-bs-dismiss="modal">
                        {{ isEditing ? "Save Changes" : "Add Allergy(s)" }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Allergy View Modal -->
    <div class="modal fade" id="modalAllergyView" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title">Allergy Details</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div v-if="viewingRow">
                        <p><strong>ID:</strong> {{ viewingRow.id }}</p>
                        <p><strong>Name:</strong> {{ viewingRow.name }}</p>
                        <p v-if="viewingRow.description"><strong>Description:</strong> {{ viewingRow.description }}</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
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
</style>
