<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";
import { toast } from "vue3-toastify"; 

const show = ref(false);
const editingId = ref(null);
const permissions = ref([]);
const form = ref({ name: "", description: "" });
const saving = ref(false);
const baseUrl = "/permissions";

// collect backend validation errors (422)
const formErrors = ref({});

// Set Laravel-friendly Axios defaults once
function initAxios() {
  axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
  const token = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content");
  if (token) {
    axios.defaults.headers.common["X-CSRF-TOKEN"] = token;
  }
}

async function loadPermissions() {
  const { data } = await axios.get(baseUrl);
  permissions.value = data || [];
}

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

    await loadPermissions();
    show.value = false;
  } catch (err) {
    // Handle validation errors from Laravel
    if (err.response && err.response.status === 422) {
      const errs = err.response.data?.errors || {};
      formErrors.value = errs;

      // show a toast with a concise combined message
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

onMounted(() => {
  initAxios();
  loadPermissions();
});
</script>

<template>
  <div class="card shadow-sm border-0 rounded-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div>
        <h5 class="mb-0">List of Permissions</h5>
        <small class="text-muted">These are all the permissions that this system uses.</small>
      </div>
      <button class="btn btn-primary btn-sm rounded-pill" @click="openCreate">
        <i class="bi bi-plus-lg me-1"></i>Add Permission
      </button>
    </div>

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
          <tr v-for="p in permissions" :key="p.id">
            <td><span class="badge bg-light text-dark">{{ p.name }}</span></td>
            <td>{{ p.description }}</td>
            <td>
              <button class="btn btn-sm btn-outline-primary rounded-pill" @click="openEdit(p)">
                Edit
              </button>
            </td>
          </tr>
          <tr v-if="permissions.length === 0">
            <td colspan="3" class="text-center text-muted py-4">No permissions</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Modal -->
  <div v-if="show" class="modal fade show d-block" style="background:#0006">
    <div class="modal-dialog modal-dialog-end">
      <div class="modal-content rounded-4">
        <div class="modal-header">
          <h6 class="modal-title">{{ editingId ? "Edit Permission" : "Create Permission" }}</h6>
          <button type="button" class="btn-close" @click="show = false"></button>
        </div>

        <div class="modal-body">
          <!-- Name -->
          <div class="mb-2">
            <label class="form-label">Name</label>
            <input
              v-model="form.name"
              class="form-control"
              placeholder="inventory.view"
              :class="{
                'is-invalid': formErrors.name,
                'bg-danger bg-opacity-10': formErrors.name
              }"
            />
            <div v-if="formErrors.name" class="invalid-feedback d-block">
              {{ formErrors.name[0] }}
            </div>
          </div>

          <!-- Description -->
          <div class="mb-2">
            <label class="form-label">Description</label>
            <input
              v-model="form.description"
              class="form-control"
              placeholder="Inventory"
              :class="{
                'is-invalid': formErrors.description,
                'bg-danger bg-opacity-10': formErrors.description
              }"
            />
            <div v-if="formErrors.description" class="invalid-feedback d-block">
              {{ formErrors.description[0] }}
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-light rounded-pill" @click="show = false">Cancel</button>
          <button class="btn btn-primary rounded-pill" :disabled="saving" @click="save">
            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
            Save
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
