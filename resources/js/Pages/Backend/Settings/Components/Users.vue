<script setup>
import { ref, onMounted, computed } from "vue";
import axios from "axios";
import { toast } from "vue3-toastify";
import Select from "primevue/select";
import { Pencil, Plus } from "lucide-vue-next";



const rows = ref([]);
const roles = ref([]);

const loading = ref(false);

const show = ref(false);
const editingId = ref(null);

// visibility toggles
const showPassword = ref(false);
const showPasswordConfirm = ref(false);
const showPin = ref(false);

// form
const form = ref({
  name: "",
  email: "",
  password: "",
  password_confirmation: "",
  pin: "",
  status: "Active",
  role_id: null,
});
const formErrors = ref({});

// cannot delete Super Admin/Admin users
const canDelete = (row) => !["Super Admin", "Admin"].includes(row.role ?? "");

function openCreate() {
  editingId.value = null;
  form.value = {
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    pin: "",
    status: "Active",
    role_id: roles.value.find(r => r.name === "Waiter")?.id ?? null,
  };
  formErrors.value = {};
  showPassword.value = showPasswordConfirm.value = showPin.value = false;
  show.value = true;
}

function openEdit(row) {
  editingId.value = row.id;
  form.value = {
    name: row.name,
    email: row.email,
    password: "",
    password_confirmation: "",
    pin: "",
    status: row.status || "Active",
    role_id: row.role_id || roles.value.find(r => r.name === row.role)?.id || null,
  };
  formErrors.value = {};
  showPassword.value = showPasswordConfirm.value = showPin.value = false;
  show.value = true;
}

const statusOptions = [
  { name: "Active", value: "Active" },
  { name: "Inactive", value: "Inactive" },
];
const userRoles = ref([]);
async function fetchAll() {
  try {
    loading.value = true;
    const { data } = await axios.get("/users"); // expects { users: [...], roles: [...] }
    rows.value = data.users || [];
    roles.value = data.roles || [];
    userRoles.value = (data.roles || []).map(role => ({
      name: role.name,
      value: role.id, // 👈 better: use ID as value
    }));

    // console.log("Label Colors:", labelColors);
  } catch (e) {
    console.error(e);
    toast.error("Failed to load users");
  } finally {
    loading.value = false;
  }
}


const roleOptions = computed(() =>
  (roles.value || []).map(r => ({ label: r.name, value: r.id }))
);

const isSaving = ref(false);

async function save() {
  try {
   isSaving.value = true;
    formErrors.value = {};
    if (editingId.value) {
      const { data } = await axios.put(`/users/${editingId.value}`, form.value);
      const i = rows.value.findIndex((x) => x.id === editingId.value);
      if (i !== -1) rows.value[i] = data.user;
      toast.success("User updated");
    } else {
      const { data } = await axios.post("/users", form.value);
      rows.value.push(data.user);
      toast.success("User created Successfully");
    }
    show.value = false;
  } catch (err) {
    if (err?.response?.status === 422) {
      formErrors.value = err.response.data?.errors || {};
      const first = Object.values(formErrors.value)?.[0]?.[0] ?? "Fix validation errors.";
      toast.error(first);
    } else {
      toast.error(err?.response?.data?.message || "Something went wrong");
    }
  }finally {
    isSaving.value = false;
  }
}

async function remove(row) {
  if (!canDelete(row)) return;
 
  try {
    await axios.delete(`/users/${row.id}`);
    rows.value = rows.value.filter((x) => x.id !== row.id);
     toast.success("User deleted successfully"); // Changed message
  } catch (e) {
    toast.error(e?.response?.data?.message || "Delete failed");
  }
}

onMounted(fetchAll);
</script>

<template>
  <div class="card border-0 rounded-4 shadow-sm">
    <div class="card-header d-flex align-items-center justify-content-between">
      <div>
        <h5 class="mb-0">Users</h5>
        <small class="text-muted">View and manage Users</small>
      </div>
      <button class="btn btn-primary rounded-pill px-3 py-2" @click="openCreate">
        <i class="bi bi-plus-lg me-1"></i> Add User
      </button>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-primary">
            <tr>
              <th>Username</th>
              <th>Role</th>
              <th>Status</th>
              <th style="width:120px">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in rows" :key="u.id">
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="rounded-circle d-inline-flex align-items-center justify-content-center"
                    style="width:32px;height:32px;background:#1C0D82;color:#fff;">
                    {{ (u.name || 'U').charAt(0).toUpperCase() }}
                  </div>
                  <div class="fw-semibold">{{ u.name }}</div>
                </div>
              </td>
              <td>{{ u.role || '—' }}</td>
              <td>
                <span
                  :class="['badge rounded-pill py-1 px-2', (u.status === 'Active' ? 'bg-success' : 'bg-secondary')]">
                  {{ u.status }}
                </span>
              </td>
              <td>
                <div class="d-inline-flex align-items-center gap-3">
                  <button class="p-2 rounded-full text-blue-600 hover:bg-blue-100" @click="openEdit(u)" title="Edit">
                    <Pencil class="w-4 h-4" />
                  </button>
                 <!-- ConfirmModal for deletable users -->
<ConfirmModal
  v-if="canDelete(u)"
  :title="'Delete User'"
  :message="`Are you sure you want to delete user '${u.name}'?`"
  :showDeleteButton="true"
  @confirm="() => remove(u)"
  @cancel="() => {}"
/>

<!-- Disabled button for non-deletable users -->
<button
  v-else
  class="inline-flex items-center justify-center p-2 rounded-full text-gray-400 cursor-not-allowed"
  disabled
  title="Cannot delete Super Admin/Admin users"
>
  <i class="bi bi-trash"></i>
</button>
                </div>
              </td>
            </tr>
            <tr v-if="rows.length === 0">
              <td colspan="4" class="text-center text-muted py-4">No users found</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="loading" class="text-center py-3">
        <span class="spinner-border"></span>
      </div>
    </div>
  </div>

  <!-- Modal (no fade) -->
  <div v-if="show" class="modal show d-block" style="background:#0006;transition:none">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content rounded-4">
        <div class="modal-header">
          <h6 class="modal-title">{{ editingId ? 'Edit User' : 'Add New User' }}</h6>

          <button class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
            @click="show = false" data-bs-dismiss="modal" aria-label="Close" title="Close">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24"
              stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">User Name</label>
              <input v-model="form.name" class="form-control" :class="{ 'is-invalid': formErrors.name }" />
              <div v-if="formErrors.name" class="invalid-feedback d-block">{{ formErrors.name[0] }}</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input v-model="form.email" type="email" class="form-control"
                :class="{ 'is-invalid': formErrors.email }" />
              <div v-if="formErrors.email" class="invalid-feedback d-block">{{ formErrors.email[0] }}</div>
            </div>

            <!-- Password with eye -->
            <div class="col-md-6">
              <label class="form-label">Password</label>
              <div class="position-relative">
                <input :type="showPassword ? 'text' : 'password'" v-model="form.password" class="form-control pe-5"
                  :class="{ 'is-invalid': formErrors.password }" placeholder="Enter password" />
                <button type="button" class="btn btn-link p-0 position-absolute top-50 end-0 translate-middle-y me-3"
                  @click="showPassword = !showPassword" :aria-label="showPassword ? 'Hide password' : 'Show password'">
                  <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                </button>
              </div>
              <div v-if="formErrors.password" class="invalid-feedback d-block">
                {{ formErrors.password[0] }}
              </div>
            </div>

            <!-- Confirm Password with eye -->
            <div class="col-md-6">
              <label class="form-label">Confirm Password</label>
              <div class="position-relative">
                <input :type="showPasswordConfirm ? 'text' : 'password'" v-model="form.password_confirmation"
                  class="form-control pe-5" placeholder="Confirm password" />
                <button type="button" class="btn btn-link p-0 position-absolute top-50 end-0 translate-middle-y me-3"
                  @click="showPasswordConfirm = !showPasswordConfirm"
                  :aria-label="showPasswordConfirm ? 'Hide password' : 'Show password'">
                  <i :class="showPasswordConfirm ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                </button>
              </div>
            </div>

            <!-- PIN with centered digits + eye (like your screenshot) -->
            <div class="col-md-6">
              <label class="form-label">Pin</label>
              <div class="position-relative">
                <input :type="showPin ? 'text' : 'password'" v-model="form.pin" maxlength="4" inputmode="numeric"
                  pattern="[0-9]*" class="form-control pe-5 text-center"
                  style="letter-spacing:.5rem;font-variant-numeric:tabular-nums;" placeholder="••••"
                  :class="{ 'is-invalid': formErrors.pin }" @paste.prevent />
                <button type="button" class="btn btn-link p-0 position-absolute top-50 end-0 translate-middle-y me-3"
                  @click="showPin = !showPin" :aria-label="showPin ? 'Hide PIN' : 'Show PIN'"
                  title="Toggle PIN visibility">
                  <i :class="showPin ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                </button>
              </div>
              <div v-if="formErrors.pin" class="invalid-feedback d-block">{{ formErrors.pin[0] }}</div>
            </div>

            <!-- Role (PrimeVue Select) -->
            <div class="col-md-6">
              <label class="form-label">Select Role</label>

              <Select v-model="form.role_id" :options="userRoles" optionLabel="name" optionValue="value"
                placeholder="Select Role" class="w-100" appendTo="self" :autoZIndex="true" :baseZIndex="2000"
                :class="{ 'is-invalid': formErrors.role_id }" />
              <div v-if="formErrors.role_id" class="invalid-feedback d-block">
                {{ formErrors.role_id[0] }}
              </div>
            </div>

            <!-- Status -->
            <div class="col-md-6">
              <label class="form-label">Select Status</label>
              <Select v-model="form.status" :options="statusOptions" optionLabel="name" optionValue="value"
                placeholder="Status" class="w-100" appendTo="self" :autoZIndex="true" :baseZIndex="2000"
                :class="{ 'is-invalid': formErrors.status }" />


            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-primary rounded-pill px-3 py-2 d-flex align-items-center justify-content-center"
            @click="save" :disabled="isSaving">
            <span v-if="isSaving" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            {{ isSaving ? 'Saving...' : 'Save' }}
          </button>

          <button class="btn btn-secondary rounded-pill px-2 py-2" @click="show = false">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal,
.modal .modal-dialog,
.modal .modal-content {
  transition: none !important;
}


/* keep PrimeVue overlays above Bootstrap modal/backdrop */
:deep(.p-multiselect-panel),
:deep(.p-select-panel),
:deep(.p-dropdown-panel) {
  z-index: 2000 !important;
}

/* ====================================================== */

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
  background: #000 !important;
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
  background: #181818 !important;
  color: #fff !important;
}

:global(.dark .p-multiselect),
:global(.dark .p-multiselect-panel),
:global(.dark .p-multiselect-token) {
  background: #212121 !important;
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
  background: #181818 !important;
  color: #fff !important;
  border: 1px solid #555 !important;
  border-radius: 12px !important;
  padding: 0.25rem 0.5rem !important;
}

.dark .p-inputtext {
  background-color: #181818 !important;
  color: #fff !important;
}

.dark .p-checkbox-icon {
  color: #fff !important;
}

.dark .p-checkbox-input {
  color: #fff !important;
}

.dark .p-component {
  color: #fff !important;
}

.side-link{
  border-radius: 50%;
  background-color: #fff !important;
}

.dark .side-link{
  border-radius: 55%;
  background-color: #181818!important;
}
/* Chip remove (x) icon */
:global(.dark .p-multiselect-chip .p-chip-remove-icon) {
  color: #fff !important;
}

:global(.dark .p-multiselect-chip .p-chip-remove-icon:hover) {
  color: #f87171 !important;
  /* lighter red */
}

/* ==================== Dark Mode Select Styling ====================== */
:global(.dark .p-select) {
  background-color: #000 !important;
  color: #fff !important;
  border-color: #555 !important;
}

/* Options container */
:global(.dark .p-select-list-container) {
  background-color: #000 !important;
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

:global(.dark .p-select-dropdown) {
  background-color: #212121 !important;
  color: #fff !important;

}

:global(.dark .p-select-label) {
  color: #fff !important;
  background-color: #212121 !important;
}

:global(.dark .p-select-list) {
  background-color: #212121 !important;
}

:global(.dark .p-placeholder) {
  color: #aaa !important;
}
</style>
