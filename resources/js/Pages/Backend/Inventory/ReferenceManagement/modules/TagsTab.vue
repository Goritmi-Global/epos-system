<script setup>
import { ref, computed, onMounted, nextTick } from "vue";
import { toast } from "vue3-toastify";
import MultiSelect from "primevue/multiselect";

const rows = ref([
    { id: 1, name: "Vegan" },
    { id: 2, name: "Halal" },
]);

const options = ref([
    { label: "Vegan", value: "Vegan" },
    { label: "Vegetarian", value: "Vegetarian" },
    { label: "Halal", value: "Halal" },
    { label: "Kosher", value: "Kosher" },
    { label: "Organic", value: "Organic" },
    { label: "Locally Sourced", value: "Locally Sourced" },
    { label: "Fairtrade", value: "Fairtrade" },
    { label: "Spicy", value: "Spicy" },
    { label: "Free-From Nuts", value: "Free-From Nuts" },
    { label: "Contains Soy", value: "Contains Soy" },
    { label: "Free-From Egg", value: "Free-From Egg" },
    { label: "Sugar-Free", value: "Sugar-Free" },
    { label: "Ethically Sourced", value: "Ethically Sourced" },
    { label: "Red Tractor Certified", value: "Red Tractor Certified" },
    { label: "Scottish Produce", value: "Scottish Produce" },
    { label: "Welsh Lamb", value: "Welsh Lamb" },
]);

const selected = ref([]); // array of values
const filterText = ref(""); // Fixed: Added missing filterText ref

const isEditing = ref(false);
const editingRow = ref(null);
const editName = ref("");

const q = ref("");

// Fixed: Create filtered computed property that works with tags array
const filteredTags = computed(() => {
    const searchTerm = q.value.trim().toLowerCase();
    return searchTerm
        ? tags.value.filter((tag) => tag.name.toLowerCase().includes(searchTerm))
        : tags.value;
});

const selectAll = () => (selected.value = options.value.map((o) => o.value));

const addCustom = () => {
    const name = (filterText.value || "").trim();
    if (!name) return;
    if (
        !options.value.some((o) => o.label.toLowerCase() === name.toLowerCase())
    ) {
        options.value.push({ label: name, value: name });
    }
    if (!selected.value.includes(name))
        selected.value = [...selected.value, name];
    filterText.value = "";
};

const openAdd = () => {
    isEditing.value = false;
    selected.value = [];
    filterText.value = "";
    const modal = new bootstrap.Modal(document.getElementById("modalTagForm"));
    modal.show();
};
const availableOptions = computed(() => {
    return options.value.filter(option => 
        !tags.value.some(tag => tag.name.toLowerCase() === option.value.toLowerCase())
    );
});
const openEdit = (row) => {
    isEditing.value = true;
    editingRow.value = row;
    editName.value = row.name;
    const modal = new bootstrap.Modal(document.getElementById("modalTagForm"));
    modal.show();
};

const viewRow = ref(null);

const openView = (row) => {
    viewRow.value = row;
    const modal = new bootstrap.Modal(document.getElementById("modalTagView"));
    modal.show();
};

// const removeRow = (row) => (rows.value = rows.value.filter((r) => r !== row));

const openRemove = async (row) => {
    try {
        await axios.delete(`/tags/${row.id}`);
        tags.value = tags.value.filter((t) => t.id !== row.id);
        toast.success("Tag deleted ✅");
    } catch (e) {
        toast.error("Delete failed ❌");
    }
};

import axios from "axios";

const runQuery = async (payload) => {
    if (payload.action === "create") {
        return axios.post("/tags", { tags: payload.added });
    }
    if (payload.action === "update") {
        return axios.put(`/tags/${payload.row.id}`, { name: payload.row.name });
    }
    if (payload.action === "delete") {
        return axios.delete(`/tags/${payload.row.id}`);
    }
};

const onSubmit = async () => {
    if (isEditing.value) {
        if (!editName.value.trim()) {
            toast.warning("Name cannot be empty ⚠️");
            return;
        }
        try {
            const { data } = await axios.put(`/tags/${editingRow.value.id}`, {
                name: editName.value.trim(),
            });

            const idx = tags.value.findIndex(
                (t) => t.id === editingRow.value.id
            );
            if (idx !== -1) tags.value[idx] = data;

            toast.success("Tag updated Successfully ✅");

            // Hide the modal after successful update
            hideModal();
        } catch (e) {
            if (e.response?.data?.errors) {
                Object.values(e.response.data.errors).forEach((msgs) =>
                    msgs.forEach((m) => toast.error(m))
                );
            } else toast.error("Update failed ❌");
        }
    } else {
        // create
        const newTags = selected.value
            .filter((v) => !tags.value.some((t) => t.name === v))
            .map((v) => ({ name: v }));

        // Filter new tags and detect duplicates
        const existingTags = selected.value.filter((v) =>
            tags.value.some((t) => t.name === v)
        );

        if (newTags.length === 0) {
            // Show which tags already exist
            toast.info(
                `Tag${
                    existingTags.length > 1 ? "s" : ""
                } already exist: ${existingTags.join(", ")}`
            );
            hideModal();
            return;
        }

        try {
            const response = await axios.post("/tags", { tags: newTags });

            // If backend returns array directly
            const createdTags = response.data?.tags ?? response.data;

            if (Array.isArray(createdTags) && createdTags.length) {
                tags.value = [...tags.value, ...createdTags];
            }

            toast.success("Tags added ✅");

            // Hide the modal after successful creation
            hideModal();
            await fetchTags();
        } catch (e) {
            // Only show create failed if there is a real error
            if (e.response?.data?.errors) {
                Object.values(e.response.data.errors).forEach((msgs) =>
                    msgs.forEach((m) => toast.error(m))
                );
            } else {
                console.error(e); // log actual error for debugging
                toast.error("Create failed ❌");
            }
        }
    }
};

// Function to properly hide modal and clean up backdrop
const hideModal = () => {
    // Get the modal element
    const modalElement = document.getElementById("modalTagForm");

    if (modalElement) {
        // Get existing modal instance or create new one
        let modal = bootstrap.Modal.getInstance(modalElement);
        if (!modal) {
            modal = new bootstrap.Modal(modalElement);
        }

        // Hide the modal
        modal.hide();
    }

    // Force cleanup after a short delay to ensure Bootstrap animations complete
    setTimeout(() => {
        // Remove all modal backdrops
        const backdrops = document.querySelectorAll(".modal-backdrop");
        backdrops.forEach((backdrop) => {
            backdrop.remove();
        });

        // Clean up body classes and styles that Bootstrap adds
        document.body.classList.remove("modal-open");
        document.body.style.overflow = "";
        document.body.style.paddingRight = "";
        document.body.style.marginRight = "";

        // Reset modal-related attributes
        const body = document.body;
        body.removeAttribute("data-bs-overflow");
        body.removeAttribute("data-bs-padding-right");
    }, 150); // Reduced timeout for quicker cleanup
};

// show Index page
const tags = ref([]);
const page = ref(1);
const perPage = ref(15);
const loading = ref(false);

const fetchTags = () => {
    loading.value = true;

    return axios
        .get("/tags", {
            params: { q: q.value, page: page.value, per_page: perPage.value },
        })
        .then(({ data }) => {
            tags.value = data?.data ?? data?.tags?.data ?? data ?? [];
            return nextTick();
        })
        .then(() => {
            window.feather?.replace();
        })
        .catch((err) => {
            console.error("Failed to fetch tags", err);
        })
        .finally(() => {
            loading.value = false;
        });
};

// Fixed: Add missing onDownload function
const onDownload = (format) => {
    console.log(`Downloading tags as ${format}`);
    // Add your download logic here
};

onMounted(async () => {
    await fetchTags();
    window.feather?.replace();
});
</script>

<template>
    <div class="card border-0 shadow-lg rounded-4">
        <div class="card-body">
            <div
                class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3"
            >
                <h4 class="mb-0">Tags</h4>
                <div class="d-flex gap-2">
                    <div class="search-wrap">
                        <i class="bi bi-search"></i>
                        <input
                            v-model="q"
                            class="form-control search-input"
                            placeholder="Search"
                        />
                    </div>
                    <button
                        class="btn btn-primary rounded-pill px-4"
                        data-bs-toggle="modal"
                        data-bs-target="#modalTagForm"
                        @click="openAdd"
                    >
                        Add Tag
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
                <table class="table table-hover">
                    <thead class="border-top small text-muted">
                        <tr>
                            <th>S. #</th>
                            <th>Tag Name</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Fixed: Use filteredTags instead of tags for proper filtering -->
                        <tr v-for="(r, i) in filteredTags" :key="r.id">
                            <td>{{ i + 1 }}</td>
                            <td class="fw-semibold">{{ r.name }}</td>
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
                                                @click="openView(r)"
                                            >
                                                <i
                                                    data-feather="eye"
                                                    class="me-2"
                                                ></i
                                                >View
                                            </a>
                                        </li>
                                        <li>
                                            <a
                                                class="dropdown-item py-2"
                                                href="javascript:;"
                                                @click="openEdit(r)"
                                            >
                                                <i
                                                    data-feather="edit-2"
                                                    class="me-2"
                                                ></i
                                                >Edit
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider" /></li>
                                        <li>
                                            <a
                                                class="dropdown-item py-2 text-danger"
                                                href="javascript:;"
                                                @click="openRemove(r)"
                                            >
                                                <i
                                                    data-feather="trash-2"
                                                    class="me-2"
                                                ></i
                                                >Delete
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>

                        <!-- Fixed: Check filteredTags length instead of tags -->
                        <tr v-if="filteredTags.length === 0">
                            <td colspan="3" class="text-center text-muted py-4">
                                {{ q.trim() ? 'No tags found matching your search.' : 'No tags found.' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="modalTagForm" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ isEditing ? "Edit Tag" : "Add Tag(s)" }}
                    </h5>
                    <button type="button" class="btn btn-close" data-bs-dismiss="modal" aria-label="Close">
                        ×
                    </button>
                </div>
                <div class="modal-body">
                    <div v-if="isEditing">
                        <label class="form-label">Tag Name</label>
                        <input
                            v-model="editName"
                            class="form-control"
                            placeholder="e.g., Vegan"
                        />
                    </div>
                    <div v-else>
                        <MultiSelect
                            v-model="selected"
                            :options="availableOptions"
                            optionLabel="label"
                            optionValue="value"
                            :multiple="true"
                            :filter="true"
                            display="chip"
                            placeholder="Choose tags or type to add"
                            class="w-100"
                            appendTo="self"
                            @filter="(e) => (filterText = e.value || '')"
                        >
                            <template #header>
                                <div class="w-100 d-flex justify-content-end">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-link text-primary"
                                        @click.stop="selectAll"
                                    >
                                        Select All
                                    </button>
                                </div>
                            </template>
                            <template #footer>
                                <div
                                    v-if="filterText?.trim()"
                                    class="p-2 border-top d-flex justify-content-between align-items-center"
                                >
                                    <small class="text-muted">Not found?</small>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary rounded-pill"
                                        @click="addCustom"
                                    >
                                        Add "{{ filterText.trim() }}"
                                    </button>
                                </div>
                            </template>
                        </MultiSelect>
                    </div>

                    <button
                        class="btn btn-primary rounded-pill w-100 mt-4"
                        @click="onSubmit"
                        data-bs-dismiss="modal"
                    >
                        {{ isEditing ? "Save Changes" : "Add Tag(s)" }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- View Modal -->
    <div class="modal fade" id="modalTagView" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title">View Tag</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>ID:</strong> {{ viewRow?.id }}</p>
                    <p><strong>Name:</strong> {{ viewRow?.name }}</p>
                    <!-- <p>
                        <strong>Description:</strong>
                        {{ viewRow?.description || "—" }}
                    </p> -->
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">
                        Close
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
.p-multiselect {
    background-color: white !important;
    color: black !important;
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

/* keep PrimeVue overlays above Bootstrap modal/backdrop */
:deep(.p-multiselect-panel),
:deep(.p-select-panel),
:deep(.p-dropdown-panel) {
    z-index: 2000 !important;
}

</style>