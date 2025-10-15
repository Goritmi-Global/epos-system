<script setup>
import { ref, onMounted, computed, onBeforeUnmount } from "vue";
import axios from "axios";
import { toast } from "vue3-toastify";
import { Pencil, Plus } from "lucide-vue-next";

const show = ref(false);
const editingId = ref(null);
const permissions = ref([]);
const form = ref({ name: "", description: "" });
const formErrors = ref({});
const saving = ref(false);

// Search
const searchTerm = ref("");

// Fetch list
const baseUrl = "/permissions";
async function fetchAllPermissions() {
    const { data } = await axios.get(baseUrl);
    permissions.value = data || [];
}

// Computed filter
const filteredPermissions = computed(() => {
    const q = searchTerm.value.trim().toLowerCase();
    if (!q) return permissions.value;
    return permissions.value.filter((p) => {
        const n = (p.name || "").toLowerCase();
        const d = (p.description || "").toLowerCase();
        return n.includes(q) || d.includes(q);
    });
});

// ESC clears search
function handleKeydown(e) {
    if (e.key === "Escape" && searchTerm.value) searchTerm.value = "";
}

// Modal open helpers
function openCreate() {
    editingId.value = null;
    form.value = { name: "", description: "" };
    formErrors.value = {};
    show.value = true;
}

function openEdit(p) {
    editingId.value = p.id;
    form.value = { name: p.name, description: p.description ?? "" };
    formErrors.value = {};
    show.value = true;
}

// Save (create/update)
async function save() {
    try {
        saving.value = true;
        formErrors.value = {};

        if (editingId.value) {
            await axios.put(`${baseUrl}/${editingId.value}`, form.value);
            toast.success("Permission updated successfully");
        } else {
            await axios.post(baseUrl, form.value);
            toast.success("Permission created successfully");
        }

        await fetchAllPermissions();
        show.value = false;
    } catch (err) {
        if (err?.response?.status === 422) {
            const errs = err.response.data?.errors || {};
            formErrors.value = errs;
            const firstMsg =
                Object.values(errs)?.[0]?.[0] ??
                "Please fix the highlighted fields and try again.";
            toast.error(firstMsg);
        } else {
            console.error("Save failed", err);
            toast.error("Something went wrong. Please try again.");
        }
    } finally {
        saving.value = false;
    }
}

// Lifecycle
onMounted(() => {
    fetchAllPermissions();
    window.addEventListener("keydown", handleKeydown);
});
onBeforeUnmount(() => {
    window.removeEventListener("keydown", handleKeydown);
});
</script>

<template>
    <div class="card shadow-sm border-0 rounded-4">
        <!-- Header -->
        <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">List of Permissions</h5>
                <small class="text-muted">These are all the permissions that this system uses.</small>
            </div>

            <div class="d-flex align-items-center gap-2">
                <!-- Search -->
                <div class="input-group">
                    <input v-model.trim="searchTerm" type="text" class="form-control rounded-pill shadow-sm"
                        placeholder="Search permissions…" style="width: 360px" />
                </div>

                <!-- Add -->
                <button class="btn btn-primary btn-sm rounded-pill w-100" @click="openCreate">
                    <i class="bi bi-plus-lg me-1"></i> Add Permission
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-primary">
                    <tr>
                        <th>Permission</th>
                        <th>Description</th>
                        <th style="width: 110px">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in filteredPermissions" :key="p.id">
                        <td>
                            <span class="badge bg-light py-2 text-dark">{{
                                p.name
                                }}</span>
                        </td>
                        <td>{{ p.description }}</td>
                        <td>
                            <button class="p-2 rounded-full text-blue-600 hover:bg-blue-100" @click="openEdit(p)">
                               <Pencil class="w-4 h-4" />
                            </button>
                        </td>
                    </tr>

                    <!-- Empty states -->
                    <tr v-if="!permissions.length">
                        <td colspan="3" class="text-center text-muted py-4">
                            No permissions
                        </td>
                    </tr>
                    <tr v-else-if="!filteredPermissions.length">
                        <td colspan="3" class="text-center text-muted py-4">
                            No results for “{{ searchTerm }}”
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div v-if="show" class="modal fade show d-block" style="background: #0006">
        <div class="modal-dialog modal-dialog-end">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h6 class="modal-title">
                        {{
                            editingId ? "Edit Permission" : "Create Permission"
                        }}
                    </h6>


                    <button
                        class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                        @click="show = false" data-bs-dismiss="modal" aria-label="Close" title="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                </div>

                <div class="modal-body">
                    <!-- Name -->
                    <div class="mb-2">
                        <label class="form-label">Name</label>
                        <input v-model="form.name" class="form-control" placeholder="inventory.view" :class="{
                            'is-invalid': formErrors.name,
                            'bg-danger bg-opacity-10': formErrors.name,
                        }" />
                        <div v-if="formErrors.name" class="invalid-feedback d-block">
                            {{ formErrors.name[0] }}
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-2">
                        <label class="form-label">Description</label>
                        <input v-model="form.description" class="form-control" placeholder="Inventory" :class="{
                            'is-invalid': formErrors.description,
                            'bg-danger bg-opacity-10':
                                formErrors.description,
                        }" />
                        <div v-if="formErrors.description" class="invalid-feedback d-block">
                            {{ formErrors.description[0] }}
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                     <button class="btn btn-primary rounded-pill px-2 py-2" :disabled="saving" @click="save">
                        <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                        Save
                    </button>
                    <button class="btn btn-secondary rounded-pill px-2 py-2" @click="show = false">
                        Cancel
                    </button>
                   
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.dark .bg-light{
    background-color: #212121 !important;
}

.dark .btn-secondary{
    background-color: #212121 !important;
}

</style>
