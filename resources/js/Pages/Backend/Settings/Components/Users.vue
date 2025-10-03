<script setup>
import { ref } from "vue";

const show = ref(false);
const editingId = ref(null);
const users = ref([
    { id: 1, username: "Bilal", role: "Super Admin", status: "Active" },
    { id: 2, username: "Safi", role: "Cashier", status: "Inactive" },
]);

const form = ref({ username: "", role: "Waiter", status: "Active" });

function openCreate() {
    editingId.value = null;
    form.value = { username: "", role: "Waiter", status: "Active" };
    show.value = true;
}
function openEdit(row) {
    editingId.value = row.id;
    form.value = { ...row };
    show.value = true;
}
function save() {
    if (editingId.value) {
        const i = users.value.findIndex((u) => u.id === editingId.value);
        if (i !== -1) users.value[i] = { ...form.value, id: editingId.value };
    } else {
        users.value.push({ ...form.value, id: Date.now() });
    }
    show.value = false;
}
</script>

<template>
    <div class="card shadow-sm border-0 rounded-4">
        <div
            class="card-header d-flex justify-content-between align-items-center"
        >
            <div>
                <h5 class="mb-0">Users</h5>
                <small class="text-muted">View and manage Users</small>
            </div>
            <button
                class="btn btn-primary btn-sm rounded-pill"
                @click="openCreate"
            >
                <i class="bi bi-plus-lg me-1"></i>Add User
            </button>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-primary">
                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th style="width: 110px">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="u in users" :key="u.id">
                            <td>{{ u.username }}</td>
                            <td>{{ u.role }}</td>
                            <td>
                                <span
                                    :class="[
                                        'badge',
                                        u.status === 'Active'
                                            ? 'bg-success'
                                            : 'bg-secondary',
                                    ]"
                                    >{{ u.status }}</span
                                >
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button
                                        class="btn btn-sm btn-outline-primary rounded-pill"
                                        @click="openEdit(u)"
                                    >
                                        Edit
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="users.length === 0">
                            <td colspan="4" class="text-center text-muted py-4">
                                No users found
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div v-if="show" class="modal fade show d-block" style="background: #0006">
        <div class="modal-dialog modal-dialog-end">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h6 class="modal-title">
                        {{ editingId ? "Edit User" : "Create User" }}
                    </h6>
                    <button
                        type="button"
                        class="btn-close"
                        @click="show = false"
                    ></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Username</label>
                        <input
                            v-model="form.username"
                            class="form-control"
                            placeholder="e.g., Bilal"
                        />
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Role</label>
                        <select v-model="form.role" class="form-select">
                            <option>Super Admin</option>
                            <option>Admin</option>
                            <option>Manager</option>
                            <option>Cashier</option>
                            <option>Waiter</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Status</label>
                        <select v-model="form.status" class="form-select">
                            <option>Active</option>
                            <option>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button
                        class="btn btn-light rounded-pill"
                        @click="show = false"
                    >
                        Cancel
                    </button>
                    <button class="btn btn-primary rounded-pill" @click="save">
                        Save
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
