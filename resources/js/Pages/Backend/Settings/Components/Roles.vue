<script setup>
import { ref, onMounted, computed, watch } from "vue";
import axios from "axios";
import { toast } from "vue3-toastify";
import { Pencil, Plus } from "lucide-vue-next";
import { nextTick } from "vue";
const show = ref(false);
const editingId = ref(null);

const roles = ref([]);
const allPermissions = ref([]);
const form = ref({ name: "" });
const selectedPermissions = ref([]);
const selectAll = ref(false);
const formErrors = ref({});
const saving = ref(false);

const searchKey = ref(Date.now());
const inputId = `search-${Math.random().toString(36).substr(2, 9)}`;
const isReady = ref(false);

const baseUrl = "/roles";
const listPermissionsUrl = "/permissions-list";


const modules = [
    "allergies",
    "analytics",
    "categories",
    "menu-categories",
    "menu",
    "inventory-categories",
    "inventory",
    "kots",
    "notifications",
    "orders",
    "pos",
    "profile",
    "promos",
    "purchase",
    "suppliers",
    "tags",
    "settings",
    "users",
    "roles",
    "permissions",
    "stock",
    "reference",
];



const groupedPermissions = computed(() => {
    const groups = {};

    filteredPermissions.value.forEach((perm) => {
        const name = perm.name?.toLowerCase() || "";
        const description = perm.description?.toLowerCase() || "";
        let matchedModule = "General";

        if (name.includes("menu-categories") || name.includes("menu.categories")) {
            matchedModule = "menu-categories";
        } else if (name.includes("inventory-categories") || name.includes("inventory.categories")) {
            matchedModule = "inventory-categories";
        } else if (name.includes("categories")) {
            matchedModule = "categories";
        } else if (name.includes("menu")) {
            matchedModule = "menu";
        } else if (name.includes("inventory")) {
            matchedModule = "inventory";
        } else {
            for (const mod of modules) {
                if (name.includes(mod) || description.includes(mod)) {
                    matchedModule = mod;
                    break;
                }
            }
        }

        if (!groups[matchedModule]) groups[matchedModule] = [];
        groups[matchedModule].push(perm);
    });

    // Sort permissions within each group: selected first, then unselected
    const selectedIds = selectedPermissions.value.map(p => p.id);

    Object.keys(groups).forEach(category => {
        groups[category].sort((a, b) => {
            const aSelected = selectedIds.includes(a.id);
            const bSelected = selectedIds.includes(b.id);

            // If both selected or both unselected, maintain original order
            if (aSelected === bSelected) return 0;

            // Selected items come first
            return aSelected ? -1 : 1;
        });
    });

    return groups;
});


const formatCategory = (category) => {

    const specialCases = {
        'menu-categories': 'Menu Categories',
        'inventory-categories': 'Inventory Categories',
        'pos': 'POS (Point of Sale)',
        'kots': 'KOT (Kitchen Order Tickets)',
        'api': 'API',
    };

    if (specialCases[category.toLowerCase()]) {
        return specialCases[category.toLowerCase()];
    }

    return category
        .replace(/-/g, " ")
        .replace(/\b\w/g, (l) => l.toUpperCase());
};

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
    const permissionIds = data.permission_ids || [];

    selectedPermissions.value = allPermissions.value.filter(p =>
        permissionIds.includes(p.id)
    );

    show.value = true;
}

async function save() {
    try {
        saving.value = true;
        formErrors.value = {};

        const payload = {
            name: form.value.name,
            permissions: selectedPermissions.value.map(p => p.id),
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
    searchKey.value = Date.now();
    await nextTick();

    // Delay to prevent autofill
    setTimeout(() => {
        isReady.value = true;

        // Force clear any autofill that happened
        const input = document.getElementById(inputId);
        if (input) {
            input.value = '';
            q.value = '';
        }
    }, 100);

    initAxios();
    await Promise.all([fetchAllRoles(), loadAllPermissions()]);
});
function toggleSelectAll() {
    if (selectAll.value) {
        selectedPermissions.value = [...filteredPermissions.value]; // Store full objects
    } else {
        selectedPermissions.value = [];
    }
}

watch(selectedPermissions, (newVal) => {
    const visibleIds = filteredPermissions.value.map((p) => p.id);
    const selectedIds = newVal.map((p) => p.id);
    selectAll.value =
        visibleIds.length > 0 &&
        selectedIds.length === visibleIds.length &&
        visibleIds.every((id) => selectedIds.includes(id));
});

</script>

<template>
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">List of Roles</h5>
                <small class="text-muted">These are all the roles that this system uses.</small>
            </div>
            <button class="btn btn-primary btn-sm rounded-pill px-3 py-2" @click="openCreate">
                <i class="bi bi-plus-lg me-1"></i>Add Role
            </button>
        </div>

        <ul class="list-group list-group-flush">
            <li v-for="r in roles" :key="r.id"
                class="list-group-item d-flex justify-content-between align-items-center">
                <div class="d-flex flex-column">
                    <div class="fw-semibold">{{ r.name }}</div>
                    <small class="text-muted">
                        {{ r.permissions?.length || 0 }} permission(s)
                    </small>
                </div>
                <button class="p-2 rounded-full text-blue-600 hover:bg-blue-100" @click="openEdit(r)">
                    <Pencil class="w-4 h-4" />
                </button>
            </li>
            <li v-if="roles.length === 0" class="list-group-item text-center text-muted">
                No roles
            </li>
        </ul>
    </div>

    <!-- Modal -->
    <div v-if="show" class="modal show d-block fade">
        <div class="modal-dialog modal-dialog-end modal-xl">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h6 class="modal-title">
                        {{ editingId ? "Edit Role" : "Create Role" }}
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
                    <!-- Role Name -->
                    <div class="mb-3">
                        <label class="form-label">Role Name</label>
                        <input v-model="form.name" class="form-control" placeholder="Admin" :class="{
                            'is-invalid': formErrors.name,
                            'bg-danger bg-opacity-10': formErrors.name,
                        }" />
                        <div v-if="formErrors.name" class="invalid-feedback d-block">
                            {{ formErrors.name[0] }}
                        </div>
                    </div>

                    <!-- Permissions -->

                    <div class="mb-2 d-flex justify-content-between align-items-center">
                        <label class="form-label mb-0">Permissions</label>
                        <div class="input-group" style="max-width: 260px">
                            <input type="email" name="email" autocomplete="email"
                                style="position: absolute; left: -9999px; width: 1px; height: 1px;" tabindex="-1"
                                aria-hidden="true" />

                            <input v-if="isReady" :id="inputId" v-model="q" :key="searchKey"
                                class="form-control search-input" placeholder="Search" type="search"
                                autocomplete="new-password" :name="inputId" role="presentation" @focus="handleFocus" />
                            <input v-else class="form-control search-input" placeholder="Search" disabled type="text" />
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="select-all" v-model="selectAll"
                            @change="toggleSelectAll" />
                        <label class="form-check-label fw-bold" for="select-all">
                            Select All Permissions
                        </label>
                    </div>


                    <!-- single-column: permission label on the left, checkbox on the right -->
                    <div v-for="(permissions, category) in groupedPermissions" :key="category" class="mb-3">
                        <h6 style="color: #1B2850;" class="fw-bold border-bottom pb-1 mb-2">
                            {{ formatCategory(category) }}
                        </h6>

                        <div class="row">
                            <div v-for="permission in permissions" :key="permission.id" class="col-md-3 col-sm-6 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" :id="'perm-' + permission.id"
                                        v-model="selectedPermissions" :value="permission" />
                                    <label class="form-check-label" :for="'perm-' + permission.id">
                                        {{ permission.description || permission.name }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>


                    <small v-if="formErrors.permissions" class="text-danger">
                        {{ formErrors.permissions[0] }}
                    </small>
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
.dark .list-group-item {
    background: #181818 !important;
    color: #fff !important;
}

.dark input {
    background-color: #181818 !important;
    color: #f9fafb !important;
    border: 1px solid #fff;
}
</style>
