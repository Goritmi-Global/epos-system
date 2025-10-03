<script setup>
import { ref, onMounted, computed } from "vue";
import axios from "axios";
import { toast } from "vue3-toastify";

// PrimeVue
import Select from "primevue/select";        // user picker (single)
import MultiSelect from "primevue/multiselect"; // roles picker (multiple)

const users = ref([]);           // [{id,name,email,roles:[name,...]}, ...]
const roles = ref([]);           // [{id,name}, ...]
const loading = ref(false);

const selectedUserId = ref(null);
const selectedRoleIds = ref([]);

// pretty option lists
const userOptions = computed(() =>
  users.value.map(u => ({
    label: `${u.name} — ${u.email ?? ''}`.trim(),
    value: u.id,
  }))
);

const roleOptions = computed(() =>
  roles.value.map(r => ({ label: r.name, value: r.id }))
);

async function loadData() {
  try {
    loading.value = true;
    const [uRes, rRes] = await Promise.all([
      axios.get("/user-roles/users"),
      axios.get("/user-roles/roles"),
    ]);
    users.value = uRes.data || [];
    roles.value = rRes.data || [];
  } catch (e) {
    console.error(e);
    toast.error("Failed to load users/roles");
  } finally {
    loading.value = false;
  }
}

function onPickUser(id) {
  selectedUserId.value = id;
  // seed selectedRoleIds from chosen user's current roles
  const u = users.value.find(x => x.id === id);
  if (!u) {
    selectedRoleIds.value = [];
    return;
  }
  // map user.role names -> ids in roleOptions
  const nameToId = new Map(roles.value.map(r => [r.name, r.id]));
  selectedRoleIds.value = (u.roles || [])
    .map(name => nameToId.get(name))
    .filter(Boolean);
}

async function assign() {
  if (!selectedUserId.value) {
    toast.error("Select a user first");
    return;
  }
  try {
    const { data } = await axios.post(`/user-roles/assign/${selectedUserId.value}`, {
      role_ids: selectedRoleIds.value,
    });
    // update users list in-memory (reflect fresh roles)
    const idx = users.value.findIndex(u => u.id === selectedUserId.value);
    if (idx !== -1) users.value[idx].roles = data.user.roles || [];
    toast.success("Roles updated");
  } catch (e) {
    if (e?.response?.status === 422) {
      const first = Object.values(e.response.data?.errors || {})?.[0]?.[0] ?? "Validation error";
      toast.error(first);
    } else {
      console.error(e);
      toast.error("Could not assign roles");
    }
  }
}

onMounted(loadData);
</script>

<template>
  <div class="row g-3">
    <!-- Left: Assign panel -->
    <div class="col-lg-5">
      <div class="card shadow-lg border-0 rounded-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">Assign Roles to User</h5>
            <small class="text-muted">Pick a user and choose one or more roles.</small>
          </div>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">User</label>
            <!-- PrimeVue Select (single) -->
            <Select
              v-model="selectedUserId"
              :options="userOptions"
              optionLabel="label"
              optionValue="value"
              class="w-100"
              placeholder="Select a user"
              :disabled="loading"
              @update:modelValue="onPickUser"
            />
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Roles</label>
            <!-- PrimeVue MultiSelect for multiple roles -->
            <MultiSelect
              v-model="selectedRoleIds"
              :options="roleOptions"
              optionLabel="label"
              optionValue="value"
              class="w-100"
              placeholder="Select role(s)"
              :disabled="!selectedUserId || loading"
              display="chip"
              :filter="true"
            />
          </div>

          <div class="d-flex justify-content-end">
            <button
              class="btn btn-primary rounded-pill"
              :disabled="!selectedUserId || loading"
              @click="assign"
            >
              <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
              Assign
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Right: Users table -->
    <div class="col-lg-7">
      <div class="card shadow-sm border-0 rounded-4 h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">Users</h5>
            <small class="text-muted">Current users and their roles</small>
          </div>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-primary">
                <tr>
                  <th style="width: 32px;">#</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Roles</th>
                  <th style="width: 110px;">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(u, idx) in users" :key="u.id">
                  <td>{{ idx + 1 }}</td>
                  <td>{{ u.name }}</td>
                  <td>{{ u.email }}</td>
                  <td>
                    <span
                      v-for="r in u.roles"
                      :key="r"
                      class="badge bg-success me-1 mb-1"
                    >{{ r }}</span>
                    <span v-if="!u.roles || u.roles.length === 0" class="text-muted">No roles</span>
                  </td>
                  <td>
                    <button
                      class="btn btn-sm btn-outline-primary rounded-pill"
                      @click="
                        selectedUserId = u.id;
                        onPickUser(u.id);
                      "
                    >
                      Edit Roles
                    </button>
                  </td>
                </tr>
                <tr v-if="users.length === 0">
                  <td colspan="5" class="text-center text-muted py-4">No users found</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-if="loading" class="text-center py-3">
            <span class="spinner-border"></span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
