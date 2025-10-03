<script setup>
import { ref } from "vue";

const show = ref(false);
const editingId = ref(null);

const roles = ref([
    { id: 1, name: "Super Admin" },
    { id: 2, name: "Admin" },
    { id: 3, name: "Manager" },
    { id: 4, name: "Cashier" },
    { id: 5, name: "Waiter" },
]);

const form = ref({ name: "" });

function openCreate() {
    editingId.value = null;
    form.value = { name: "" };
    show.value = true;
}
function openEdit(r) {
    editingId.value = r.id;
    form.value = { ...r };
    show.value = true;
}
function save() {
    if (editingId.value) {
        const i = roles.value.findIndex((x) => x.id === editingId.value);
        if (i > -1) roles.value[i] = { ...form.value, id: editingId.value };
    } else {
        roles.value.push({ ...form.value, id: Date.now() });
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
                class="list-group-item d-flex justify-content-between align-items-center"
                v-for="r in roles"
                :key="r.id"
            >
                <div class="fw-semibold">{{ r.name }}</div>
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
    <div v-if="show" class="modal fade show d-block" style="background: #0006">
        <div class="modal-dialog modal-dialog-end">
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
                    <label class="form-label">Name</label>
                    <input
                        v-model="form.name"
                        class="form-control"
                        placeholder="Admin"
                    />
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
