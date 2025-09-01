<script setup>
import { ref, computed, onMounted, onUpdated } from "vue";
import axios from "axios";
import { toast } from "vue3-toastify";
import { nextTick } from "vue";
const suppliers = ref([]);

const page = ref(1);
const perPage = ref(15);

const fetchSuppliers = () => {
    loading.value = true;

    return axios
        .get("/suppliers", {
            params: { q: q.value, page: page.value, per_page: perPage.value },
        })
        .then(({ data }) => {
            // console.log("Fetched suppliers:", data);
            // paginator or plain array—handle both
            suppliers.value = data?.data ?? data?.suppliers?.data ?? data ?? [];
            // wait for DOM to update, then refresh feather icons
            return nextTick();
        })
        .then(() => {
            window.feather?.replace();
        })
        .catch((err) => {
            console.error(err);
        })
        .finally(() => {
            loading.value = false;
        });
};

const q = ref("");

const filtered = computed(() => {
    const t = q.value.trim().toLowerCase();
    if (!t) return suppliers.value;
    return suppliers.value.filter((s) =>
        [s.name, s.phone, s.email, s.address, s.preferred_items].some((v) =>
            (v || "").toLowerCase().includes(t)
        )
    );
});

const onDownload = (type) => {
    /* pdf|excel */
};

const form = ref({
    name: "",
    email: "",
    phone: "",
    address: "",
    preferred_items: "", // Preferred Items
});
const loading = ref(false);
const errors = ref({});

// helper to close a Bootstrap modal by id
const closeModal = (id) => {
    const el = document.getElementById(id);
    if (!el) return;
    const modal =
        window.bootstrap?.Modal.getInstance(el) ||
        new window.bootstrap.Modal(el);
    modal.hide();
};

// reset form after submit or when needed
const resetForm = () => {
    form.value = {
        name: "",
        email: "",
        phone: "",
        address: "",
        preferred_items: "",
    };
    errors.value = {};
};

const submit = () => {
    loading.value = true;
    errors.value = {};

    axios
        .post("/suppliers", form.value)
        .then((res) => {
            // optional: push into local table without refetch
            // const created = res?.data?.data || {
            //     id: Date.now(),
            //     name: form.value.name,
            //     phone: form.value.phone,
            //     email: form.value.email,
            //     address: form.value.address,
            //     preferred_items: form.value.preferred_items,
            // };
            // suppliers.value.unshift(created);

            toast.success("Supplier added successfully ✅", {
                autoClose: 2500,
            });
            resetForm();
            closeModal("modalAddSupplier");
        })
        .catch((err) => {
            if (err?.response?.status === 422 && err.response.data?.errors) {
                errors.value = err.response.data.errors;
                toast.error("Validation failed. Please check the fields.", {
                    autoClose: 3000,
                });
            } else {
                toast.error("Something went wrong. Please try again.", {
                    autoClose: 3000,
                });
                console.error(err);
            }
        })
        .finally(() => {
            loading.value = false;
        });
};

onMounted(() => window.feather?.replace());
onUpdated(() => window.feather?.replace());
// Run on page load
onMounted(async () => {
    await fetchSuppliers();
    // Also safe to call once on mount (e.g., for static icons on the page)
    window.feather?.replace();
});

// code for other functionalities
// helpers (you already have closeModal)
const openModal = (id) => {
    const el = document.getElementById(id);
    if (!el) return;
    const modal = new window.bootstrap.Modal(el);
    modal.show();
};

const processStatus = ref();
// Editing supplier record
const selectedSupplier = ref(null);
const onEdit = (row) => {
    processStatus.value = "Edit";
    selectedSupplier.value = row;
    // map backend -> form fields
    form.value = {
        name: row.name || "",
        email: row.email || "",
        phone: row.phone ?? row.contact ?? "",
        address: row.address || "",
        preferred_items: row.preferred_items ?? row.preferred_items ?? "",
    };
    openModal("modalAddSupplier");
};

const updateSupplier = () => {
    if (!selectedSupplier.value) return;
    loading.value = true;
    errors.value = {};

    const payload = {
        id: selectedSupplier.value.id,
        name: form.value.name,
        email: form.value.email,
        contact: form.value.phone || null,
        address: form.value.address || null,
        preferred_items: form.value.preferred_items || null,
    };

    axios
        .post("/suppliers/update", payload)
        .then((res) => {
            const updated = res?.data?.data ?? res?.data ?? payload;

            const idx = suppliers.value.findIndex(
                (x) => x.id === selectedSupplier.value.id
            );
            if (idx !== -1) {
                suppliers.value[idx] = {
                    ...suppliers.value[idx],
                    ...updated,
                    phone:
                        updated.contact ??
                        payload.contact ??
                        suppliers.value[idx].phone,
                    preferred_items:
                        updated.preferred_items ??
                        payload.preferred_items ??
                        suppliers.value[idx].preferred_items,
                };
            }

            toast.success("Supplier updated successfully ✅", {
                autoClose: 2500,
            });

            // close whichever modal you're using for edit
            closeModal("modalAddSupplier"); // or "modalAddSupplier" if reusing it
            return nextTick();
        })
        .then(() => window.feather?.replace())
        .catch((err) => {
            if (err?.response?.status === 422 && err.response.data?.errors) {
                errors.value = err.response.data.errors;
                toast.error("Validation failed. Please check the fields.", {
                    autoClose: 3000,
                });
            } else {
                toast.error("Update failed. Please try again.", {
                    autoClose: 3000,
                });
                console.error(err);
            }
        })
        .finally(() => {
            loading.value = false;
        });
};

// ---- Deleting the supplier record----
const toDelete = ref(null);

// const onRemove = (row) => {
//     toDelete.value = row;
//     openModal("modalDeleteSupplier");
// };

const onRemove = () => {
    if (!toDelete.value) return;
    loading.value = true;

    axios
        .delete(`/suppliers/${toDelete.value.id}`)
        .then(() => {
            suppliers.value = suppliers.value.filter(
                (s) => s.id !== toDelete.value.id
            );
            toast.success("Supplier deleted ✅", { autoClose: 2000 });
            closeModal("modalDeleteSupplier");
            toDelete.value = null;
            return nextTick();
        })
        .then(() => window.feather?.replace())
        .catch((err) => {
            toast.error("Delete failed. Please try again.", {
                autoClose: 3000,
            });
            console.error(err);
        })
        .finally(() => {
            loading.value = false;
        });
};
</script>

<template>
    <div class="card border-0 shadow-lg rounded-4">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Suppliers</h4>

                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <div class="search-wrap">
                        <i class="bi bi-search"></i>
                        <input v-model="q" class="form-control search-input" placeholder="Search" />
                    </div>

                    <!-- Add -->
                    <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal"
                        data-bs-target="#modalAddSupplier">
                        Add Supplier
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
                            <td>{{ s.preferred_items }}</td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-link text-secondary p-0 fs-5" data-bs-toggle="dropdown"
                                        title="Actions">
                                        ⋮
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow rounded-4 overflow-hidden">
                                        <li>
                                            <a class="dropdown-item py-2" href="javascript:;" @click="onEdit(s)">
                                                Edit</a>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider" />
                                        </li>
                                        <li>
                                            <a class="dropdown-item py-2 text-danger" href="javascript:;"
                                                @click="onRemove(s)">
                                                Delete
                                            </a>
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
    <div class="modal fade" id="modalAddSupplier" tabindex="-1" aria-labelledby="modalAddSupplier" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Add Supplier</h5>
                    <button type="button" class="btn btn-close" data-bs-dismiss="modal" aria-label="Close">
                        ×
                    </button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Name -->
                        <div class="col-lg-6">
                            <label class="form-label">Name</label>
                            <input class="form-control" v-model="form.name" />
                            <small v-if="errors.name" class="text-danger">{{
                                errors.name[0]
                            }}</small>
                        </div>

                        <!-- Email -->
                        <div class="col-lg-6">
                            <label class="form-label">Email</label>
                            <input class="form-control" v-model="form.email" />
                            <small v-if="errors.email" class="text-danger">{{
                                errors.email[0]
                            }}</small>
                        </div>

                        <!-- Phone -->
                        <div class="col-lg-3">
                            <label class="form-label">+44</label>
                            <input class="form-control" disabled value="+44" />
                        </div>
                        <div class="col-lg-9">
                            <label class="form-label">Phone*</label>
                            <input class="form-control" v-model="form.phone" />
                            <small v-if="errors.contact" class="text-danger">{{
                                errors.contact[0]
                            }}</small>
                        </div>

                        <!-- Address -->
                        <div class="col-lg-6">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" rows="4" v-model="form.address"></textarea>
                            <small v-if="errors.address" class="text-danger">{{
                                errors.address[0]
                            }}</small>
                        </div>

                        <!-- Preferred Items -->
                        <div class="col-lg-6">
                            <label class="form-label">Preferred Items</label>
                            <input class="form-control" v-model="form.preferred_items" />
                            <small v-if="errors.preferred_items" class="text-danger">
                                {{ errors.preferred_items[0] }}
                            </small>
                        </div>

                        <!-- Submit -->
                        <button v-if="processStatus === 'Edit'" class="btn btn-primary rounded-pill w-100 mt-4"
                            :disabled="loading" @click="updateSupplier()">
                            <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                            Update Supplier
                        </button>
                        <button v-else class="btn btn-primary rounded-pill w-100 mt-4" :disabled="loading"
                            @click="submit()">
                            <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                            Add Supplier
                        </button>
                    </div>
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

.table-responsive {
    overflow: visible !important;
}

.dropdown-menu {
    position: absolute !important;
    z-index: 1050 !important;
}

/* Ensure the table container doesn't clip the dropdown */
.table-container {
    overflow: visible !important;
}
</style>
