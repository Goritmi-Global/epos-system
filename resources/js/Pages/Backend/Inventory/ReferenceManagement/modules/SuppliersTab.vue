<script setup>
import { ref, computed, onMounted, onUpdated } from "vue";

const suppliers = ref([
    {
        id: 1,
        name: "Noor",
        phone: "+447444 444444",
        email: "noor@test.example",
        address: "Testing address",
        items: "Pizza",
    },
    {
        id: 2,
        name: "Wali Khan",
        phone: "+447888 888888",
        email: "wali@kha.com",
        address: "testing",
        items: "tesing",
    },
]);
const q = ref("");

const filtered = computed(() => {
    const t = q.value.trim().toLowerCase();
    if (!t) return suppliers.value;
    return suppliers.value.filter((s) =>
        [s.name, s.phone, s.email, s.address, s.items].some((v) =>
            (v || "").toLowerCase().includes(t)
        )
    );
});

const onDownload = (type) => {
    /* pdf|excel */
};
const onView = (row) => {};
const onEdit = (row) => {};
const onRemove = (row) => {};
const onAdd = () => {}; // submit inside modal

onMounted(() => window.feather?.replace());
onUpdated(() => window.feather?.replace());
</script>

<template>
    <div class="card border-0 shadow-lg rounded-4">
        <div class="card-body">
            <div
                class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3"
            >
                <h4 class="mb-0">Suppliers</h4>

                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <div class="search-wrap">
                        <i class="bi bi-search"></i>
                        <input
                            v-model="q"
                            class="form-control search-input"
                            placeholder="Search"
                        />
                    </div>

                    <!-- Add -->
                    <button
                        class="btn btn-primary rounded-pill px-4"
                        data-bs-toggle="modal"
                        data-bs-target="#modalAddSupplier"
                    >
                        Add Supplier
                    </button>

                    <!-- Download all -->
                    <div class="dropdown">
                        <button
                            class="btn btn-outline-secondary rounded-pill px-4 dropdown-toggle"
                            data-bs-toggle="dropdown"
                        >
                            Download all
                        </button>
                        <ul
                            class="dropdown-menu dropdown-menu-end shadow rounded-4 py-2"
                        >
                            <li>
                                <a
                                    class="dropdown-item py-2"
                                    href="javascript:;"
                                    @click="onDownload('pdf')"
                                    >Download as PDF</a
                                >
                            </li>
                            <li>
                                <a
                                    class="dropdown-item py-2"
                                    href="javascript:;"
                                    @click="onDownload('excel')"
                                    >Download as Excel</a
                                >
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="border-top small text-muted">
                        <tr>
                            <th>S. #</th>
                            <th>Supplier name</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Items Linked</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(s, i) in filtered" :key="s.id">
                            <td>{{ i + 1 }}</td>
                            <td class="fw-semibold">{{ s.name }}</td>
                            <td>{{ s.phone }}</td>
                            <td class="text-break" style="max-width: 240px">
                                {{ s.email }}
                            </td>
                            <td class="text-truncate" style="max-width: 260px">
                                {{ s.address }}
                            </td>
                            <td>{{ s.items }}</td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button
                                        class="btn btn-link text-secondary p-0 fs-5"
                                        data-bs-toggle="dropdown"
                                        title="Actions"
                                    >
                                        ⋮
                                    </button>
                                    <ul
                                        class="dropdown-menu dropdown-menu-end shadow rounded-4 overflow-hidden"
                                    >
                                        <li>
                                            <a
                                                class="dropdown-item py-2"
                                                href="javascript:;"
                                                @click="onView(s)"
                                                ><i
                                                    data-feather="eye"
                                                    class="me-2"
                                                ></i
                                                >View</a
                                            >
                                        </li>
                                        <li>
                                            <a
                                                class="dropdown-item py-2"
                                                href="javascript:;"
                                                @click="onEdit(s)"
                                                ><i
                                                    data-feather="edit-2"
                                                    class="me-2"
                                                ></i
                                                >Edit</a
                                            >
                                        </li>
                                        <li><hr class="dropdown-divider" /></li>
                                        <li>
                                            <a
                                                class="dropdown-item py-2 text-danger"
                                                href="javascript:;"
                                                @click="onRemove(s)"
                                                ><i
                                                    data-feather="trash-2"
                                                    class="me-2"
                                                ></i
                                                >Delete</a
                                            >
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="filtered.length === 0">
                            <td colspan="7" class="text-center text-muted py-4">
                                No suppliers found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Supplier Modal -->
    <div
        class="modal fade"
        id="modalAddSupplier"
        tabindex="-1"
        aria-labelledby="modalAddSupplier"
        aria-hidden="true"
    >
        <div
            class="modal-dialog modal-lg modal-dialog-centered"
            role="document"
        >
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Add Supplier</h5>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label class="form-label">Name</label
                            ><input class="form-control" />
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">Email</label
                            ><input class="form-control" />
                        </div>

                        <div class="col-lg-3">
                            <label class="form-label">+44</label
                            ><input class="form-control" disabled value="+44" />
                        </div>
                        <div class="col-lg-9">
                            <label class="form-label">Phone*</label
                            ><input class="form-control" />
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label">Address</label
                            ><textarea class="form-control" rows="4"></textarea>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">Preferred Items</label
                            ><input class="form-control" />
                        </div>
                    </div>

                    <button
                        class="btn btn-primary rounded-pill w-100 mt-4"
                        @click="onAdd"
                    >
                        Add Supplier
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
</style>
