<script setup>
import { ref, onMounted, computed } from "vue";
import axios from "axios";
import { toast } from "vue3-toastify";

const show = ref(false);
const editingId = ref(null);

const roles = ref([]);
const allPermissions = ref([]);
const form = ref({ name: "" });
const selectedPermissions = ref([]);
const formErrors = ref({});
const saving = ref(false);

const baseUrl = "/roles";
const listPermissionsUrl = "/permissions-list";

function initAxios() {
    axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
    const token = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");
    if (token) axios.defaults.headers.common["X-CSRF-TOKEN"] = token;
}

async function fetchAllRoles() {
    const { data } = await axios.get(baseUrl);
    roles.value = data || [];
}

async function loadAllPermissions() {
    const { data } = await axios.get(listPermissionsUrl);
    allPermissions.value = data || [];
}

// For the modal: quick filter of permissions
const permSearch = ref("");
const filteredPermissions = computed(() => {
    const q = permSearch.value.trim().toLowerCase();
    if (!q) return allPermissions.value;
    return allPermissions.value.filter(
        (p) =>
            (p.name || "").toLowerCase().includes(q) ||
            (p.description || "").toLowerCase().includes(q)
    );
});

function openCreate() {
    editingId.value = null;
    form.value = { name: "" };
    selectedPermissions.value = [];
    formErrors.value = {};
    show.value = true;
}

async function openEdit(r) {
    editingId.value = r.id;
    form.value = { name: r.name };
    formErrors.value = {};

    const { data } = await axios.get(`${baseUrl}/${r.id}`);
    selectedPermissions.value = Array.from(data.permission_ids || []);
    show.value = true;
}

async function save() {
    try {
        saving.value = true;
        formErrors.value = {};

        const payload = {
            name: form.value.name,
            permissions: selectedPermissions.value,
        };

        if (editingId.value) {
            await axios.put(`${baseUrl}/${editingId.value}`, payload);
            toast.success("Role updated successfully");
        } else {
            await axios.post(baseUrl, payload);
            toast.success("Role created successfully");
        }

        await fetchAllRoles();
        show.value = false;
    } catch (err) {
        if (err?.response?.status === 422) {
            const errs = err.response.data?.errors || {};
            formErrors.value = errs;
            const first =
                Object.values(errs)?.[0]?.[0] ??
                "Please fix the highlighted fields and try again.";
            toast.error(first);
        } else {
            console.error(err);
            toast.error("Something went wrong. Please try again.");
        }
    } finally {
        saving.value = false;
    }
}

onMounted(async () => {
    initAxios();
    await Promise.all([fetchAllRoles(), loadAllPermissions()]);
});
</script>

<template>
    <div class="card shadow-sm border-0 rounded-4">
        <div
            class="card-header d-flex justify-content-between align-items-center"
        >
            <div>
                <h5 class="mb-0">List of Roles</h5>
                <small class="text-muted"
                    >These are all the roles that this system uses.</small
                >
            </div>
            <button
                class="btn btn-primary btn-sm rounded-pill"
                @click="openCreate"
            >
                <i class="bi bi-plus-lg me-1"></i>Add Role
            </button>
        </div>

        <ul class="list-group list-group-flush">
            <li
                v-for="r in roles"
                :key="r.id"
                class="list-group-item d-flex justify-content-between align-items-center"
            >
                <div class="d-flex flex-column">
                    <div class="fw-semibold">{{ r.name }}</div>
                    <small class="text-muted">
                        {{ r.permissions?.length || 0 }} permission(s)
                    </small>
                </div>
                <button
                    class="btn btn-sm btn-outline-primary rounded-pill"
                    @click="openEdit(r)"
                >
                    Edit
                </button>
            </li>
            <li
                v-if="roles.length === 0"
                class="list-group-item text-center text-muted"
            >
                No roles
            </li>
        </ul>
    </div>

    <!-- Modal -->
    <div v-if="show" class="modal show d-block fade">
        <div class="modal-dialog modal-dialog-end modal-lg">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h6 class="modal-title">
                        {{ editingId ? "Edit Role" : "Create Role" }}
                    </h6>
                    <button
                        type="button"
                        class="btn-close"
                        @click="show = false"
                    ></button>
                </div>

                <div class="modal-body">
                    <!-- Role Name -->
                    <div class="mb-3">
                        <label class="form-label">Role Name</label>
                        <input
                            v-model="form.name"
                            class="form-control"
                            placeholder="Admin"
                            :class="{
                                'is-invalid': formErrors.name,
                                'bg-danger bg-opacity-10': formErrors.name,
                            }"
                        />
                        <div
                            v-if="formErrors.name"
                            class="invalid-feedback d-block"
                        >
                            {{ formErrors.name[0] }}
                        </div>
                    </div>

                    <!-- Permissions -->

                    <div
                        class="mb-2 d-flex justify-content-between align-items-center"
                    >
                        <label class="form-label mb-0">Permissions</label>
                        <div class="input-group" style="max-width: 260px">
                            <input
                                v-model.trim="permSearch"
                                type="text"
                                class="form-control rounded-pill shadow-sm"
                                placeholder="Search permissions…"
                            />
                        </div>
                    </div>

                    <!-- single-column: permission label on the left, checkbox on the right -->
                    <div
                        class="list-group list-group-flush border rounded"
                        :class="{ 'border-danger': formErrors.permissions }"
                        style="max-height: 300px; overflow: auto"
                    >
                        <label
                            v-for="perm in filteredPermissions"
                            :key="perm.id"
                            class="list-group-item d-flex justify-content-between align-items-center py-2"
                            :for="'perm-' + perm.id"
                        >
                            <div class="me-3">
                                <span class="fw-semibold">{{ perm.name }}</span>
                            </div>

                            <input
                                class="form-check-input ms-2"
                                type="checkbox"
                                :id="'perm-' + perm.id"
                                :value="perm.id"
                                v-model="selectedPermissions"
                            />
                        </label>
                    </div>
                    <small v-if="formErrors.permissions" class="text-danger">
                        {{ formErrors.permissions[0] }}
                    </small>
                </div>

                <div class="modal-footer">
                    <button
                        class="btn btn-light rounded-pill"
                        @click="show = false"
                    >
                        Cancel
                    </button>
                    <button
                        class="btn btn-primary rounded-pill"
                        :disabled="saving"
                        @click="save"
                    >
                        <span
                            v-if="saving"
                            class="spinner-border spinner-border-sm me-1"
                        ></span>
                        Save
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
<style scoped>

</style>
