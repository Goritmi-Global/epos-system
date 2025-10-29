<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted, nextTick } from "vue";
import { Layers, CheckCircle, XCircle, AlertCircle, Pencil, Plus } from "lucide-vue-next";
import { toast } from "vue3-toastify";
import axios from "axios";
import Select from "primevue/select";
import ConfirmModal from "@/Components/ConfirmModal.vue";
import { Head } from "@inertiajs/vue3";

/* ============================================
   DATA & STATE MANAGEMENT
============================================ */

// Main data store for variant groups
const variantGroups = ref([]);

// Form state for create/edit modal
const variantGroupForm = ref({
    name: "",
    min_select: 1,
    max_select: 1,
    description: "",
    status: "active",
    sort_order: 0,
});

// Track if we're editing (null = create mode, object = edit mode)
const editingGroup = ref(null);

// Loading states
const submitting = ref(false);
const loading = ref(false);

// Validation errors from backend
const formErrors = ref({});

// Status options for dropdown
const statusOptions = [
    { label: "Active", value: "active" },
    { label: "Inactive", value: "inactive" },
];

/* ============================================
   FETCH VARIANT GROUPS FROM API
============================================ */

/**
 * Fetch all variant groups from the backend
 * Called on component mount and after create/update/delete operations
 */
const fetchVariantGroups = async () => {
    loading.value = true;
    try {
        const res = await axios.get("/api/variant-groups/all");
        variantGroups.value = res.data.data;
    } catch (err) {
        console.error("Failed to fetch variant groups:", err);
        toast.error("Failed to load variant groups");
    } finally {
        loading.value = false;
    }
};

/* ============================================
   LIFECYCLE HOOKS
============================================ */

onMounted(async () => {
    // Reset search on mount
    q.value = "";
    searchKey.value = Date.now();
    await nextTick();

    // Delay to prevent browser autofill
    setTimeout(() => {
        isReady.value = true;

        // Force clear any autofill
        const input = document.getElementById(inputId);
        if (input) {
            input.value = "";
            q.value = "";
        }
    }, 100);

    // Fetch initial data
    fetchVariantGroups();
});

/* ============================================
   KPI STATISTICS CARDS
============================================ */

/**
 * Computed statistics for dashboard cards
 * Updates automatically when variantGroups changes
 */
const groupStats = computed(() => [
    {
        label: "Total Groups",
        value: variantGroups.value.length,
        icon: Layers,
        iconBg: "bg-light-primary",
        iconColor: "text-primary",
    },
    {
        label: "Active Groups",
        value: variantGroups.value.filter((g) => g.status === "active").length,
        icon: CheckCircle,
        iconBg: "bg-light-success",
        iconColor: "text-success",
    },
    {
        label: "Inactive Groups",
        value: variantGroups.value.filter((g) => g.status === "inactive").length,
        icon: XCircle,
        iconBg: "bg-light-danger",
        iconColor: "text-danger",
    },
    {
        label: "Total Variants",
        value: variantGroups.value.reduce((sum, g) => sum + (g.variants_count || 0), 0),
        icon: AlertCircle,
        iconBg: "bg-light-warning",
        iconColor: "text-warning",
    },
]);

/* ============================================
   SEARCH FUNCTIONALITY
============================================ */

// Search query
const q = ref("");

// Unique key for search input (prevents autofill issues)
const searchKey = ref(Date.now());
const inputId = `search-${Math.random().toString(36).substr(2, 9)}`;
const isReady = ref(false);

/**
 * Filter groups based on search query
 * Searches in: name
 */
const filteredGroups = computed(() => {
    const searchTerm = q.value.trim().toLowerCase();
    if (!searchTerm) return variantGroups.value;

    return variantGroups.value.filter((group) =>
        group.name.toLowerCase().includes(searchTerm)
    );
});

/**
 * Handle focus on search input (prevents autofill)
 */
const handleFocus = (event) => {
    event.target.setAttribute("autocomplete", "off");
};

/* ============================================
   MODAL MANAGEMENT
============================================ */

/**
 * Reset modal form to initial state
 * Called when opening modal for create or after closing
 */
const resetModal = () => {
    variantGroupForm.value = {
        name: "",
        min_select: 1,
        max_select: 1,
        description: "",
        status: "active",
        sort_order: 0,
    };
    editingGroup.value = null;
    formErrors.value = {};
};

/**
 * Open modal in edit mode with existing group data
 */
const editRow = (row) => {
    editingGroup.value = row;

    // Populate form with existing data
    variantGroupForm.value = {
        name: row.name,
        min_select: row.min_select,
        max_select: row.max_select,
        description: row.description || "",
        status: row.status,
        sort_order: row.sort_order || 0,
    };

    // Open Bootstrap modal
    const modalEl = document.getElementById("variantGroupModal");
    const bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();
};

/* ============================================
   CREATE / UPDATE OPERATIONS
============================================ */

/**
 * Submit form (handles both create and update)
 * Validates min_select <= max_select on frontend before sending
 */
const submitVariantGroup = async () => {
    // Frontend validation
    if (variantGroupForm.value.min_select > variantGroupForm.value.max_select) {
        toast.error("Minimum select cannot be greater than maximum select");
        return;
    }

    if (variantGroupForm.value.min_select < 0) {
        toast.error("Minimum select cannot be negative");
        return;
    }

    if (variantGroupForm.value.max_select < 1) {
        toast.error("Maximum select must be at least 1");
        return;
    }

    submitting.value = true;
    formErrors.value = {};

    try {
        if (editingGroup.value) {
            // UPDATE existing group
            await axios.post(
                `/api/variant-groups/${editingGroup.value.id}`,
                variantGroupForm.value
            );
            toast.success("Variant group updated successfully");
        } else {
            // CREATE new group
            await axios.post("/api/variant-groups", variantGroupForm.value);
            toast.success("Variant group created successfully");
        }

        // Close modal
        const modal = bootstrap.Modal.getInstance(
            document.getElementById("variantGroupModal")
        );
        modal?.hide();

        // Reset form and refresh data
        resetModal();
        await fetchVariantGroups();
    } catch (err) {
        console.error("❌ Error:", err.response?.data || err.message);

        // Handle validation errors (422)
        if (err.response?.status === 422 && err.response?.data?.errors) {
            formErrors.value = err.response.data.errors;

            // Show all error messages
            const errorMessages = Object.values(err.response.data.errors).flat();
            toast.error(errorMessages.join("\n"));
        } else {
            // Handle other errors
            const errorMessage = err.response?.data?.message || "Failed to save variant group";
            toast.error(errorMessage);
        }
    } finally {
        submitting.value = false;
    }
};

/* ============================================
   TOGGLE STATUS (ACTIVE/INACTIVE)
============================================ */

/**
 * Toggle group status between active and inactive
 * Updates immediately on success (optimistic UI update)
 */
const toggleStatus = async (row) => {
    const newStatus = row.status === "active" ? "inactive" : "active";

    try {
        await axios.patch(`/api/variant-groups/${row.id}/toggle-status`, {
            status: newStatus,
        });

        // Update local state immediately
        row.status = newStatus;
        toast.success(`Status changed to ${newStatus}`);
    } catch (error) {
        console.error("Failed to update status:", error);
        toast.error("Failed to update status");
    }
};

/* ============================================
   DELETE OPERATION
============================================ */

/**
 * Delete variant group
 * Shows confirmation modal before deletion
 */
const deleteGroup = async (row) => {
    if (!row?.id) return;

    try {
        await axios.delete(`/api/variant-groups/${row.id}`);
        toast.success("Variant group deleted successfully");
        await fetchVariantGroups();
    } catch (err) {
        console.error("❌ Delete error:", err.response?.data || err.message);

        // Show specific error message from backend
        const errorMessage = err.response?.data?.message || "Failed to delete variant group";
        toast.error(errorMessage);
    }
};
</script>

<template>
    <Master>
        <Head title="Variant Groups" />

        <div class="page-wrapper">
            <!-- Page Header -->
            <h4 class="fw-semibold mb-3">Variant Groups Management</h4>

            <!-- KPI Statistics Cards -->
            <div class="row g-3 mb-4">
                <div v-for="stat in groupStats" :key="stat.label" class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body d-flex align-items-center">
                            <!-- Icon -->
                            <div
                                :class="[stat.iconBg, stat.iconColor]"
                                class="rounded-circle p-3 d-flex align-items-center justify-content-center me-3"
                                style="width: 56px; height: 56px"
                            >
                                <component :is="stat.icon" class="w-6 h-6" />
                            </div>

                            <!-- Value & Label -->
                            <div>
                                <h3 class="mb-0 fw-bold">{{ stat.value }}</h3>
                                <p class="text-muted mb-0 small">{{ stat.label }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Table Card -->
            <div class="card border-0 shadow-lg rounded-4">
                <div class="card-body">
                    <!-- Toolbar: Search & Add Button -->
                    <div
                        class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3"
                    >
                        <h5 class="mb-0 fw-semibold">Variant Groups</h5>

                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <!-- Search Input -->
                            <div class="search-wrap">
                                <i class="bi bi-search"></i>

                                <!-- Hidden email input to prevent autofill -->
                                <input
                                    type="email"
                                    name="email"
                                    autocomplete="email"
                                    style="
                                        position: absolute;
                                        left: -9999px;
                                        width: 1px;
                                        height: 1px;
                                    "
                                    tabindex="-1"
                                    aria-hidden="true"
                                />

                                <!-- Actual search input -->
                                <input
                                    v-if="isReady"
                                    :id="inputId"
                                    v-model="q"
                                    :key="searchKey"
                                    class="form-control search-input  rounded-pill"
                                    placeholder="Search groups..."
                                    type="search"
                                    autocomplete="new-password"
                                    :name="inputId"
                                    role="presentation"
                                    @focus="handleFocus"
                                />
                                <input
                                    v-else
                                    class="form-control search-input rounded-pill"
                                    placeholder="Search groups..."
                                    disabled
                                    type="text"
                                />
                            </div>

                            <!-- Add Group Button -->
                            <button
                                data-bs-toggle="modal"
                                data-bs-target="#variantGroupModal"
                                @click="resetModal"
                                class="d-flex align-items-center gap-1 px-4 py-2 rounded-pill btn btn-primary text-white"
                            >
                                <Plus class="w-4 h-4" /> Add Group
                            </button>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="border-top small text-muted">
                                <tr>
                                    <th>S.#</th>
                                    <th>Name</th>
                                    <th>Variants Count</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Loading State -->
                                <tr v-if="loading">
                                    <td colspan="7" class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Data Rows -->
                                <tr v-else v-for="(row, i) in filteredGroups" :key="row.id">
                                    <!-- Serial Number -->
                                    <td>{{ i + 1 }}</td>

                                    <!-- Group Name -->
                                    <td class="fw-semibold">{{ row.name }}</td>

                                    <!-- Variants Count -->
                                    <td>
                                        <span class="badge bg-secondary px-3 py-2 rounded-pill">
                                            {{ row.variants_count || 0 }} variants
                                        </span>
                                    </td>

                                    <!-- Status Badge -->
                                    <td class="text-center">
                                        <span
                                            :class="
                                                row.status === 'active'
                                                    ? 'badge bg-success px-4 py-2 rounded-pill'
                                                    : 'badge bg-danger px-4 py-2 rounded-pill'
                                            "
                                        >
                                            {{ row.status === "active" ? "Active" : "Inactive" }}
                                        </span>
                                    </td>

                                    <!-- Action Buttons -->
                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center gap-3">
                                            <!-- Edit Button -->
                                            <button
                                                @click="editRow(row)"
                                                title="Edit"
                                                class="p-2 rounded-full text-blue-600 hover:bg-blue-100"
                                            >
                                                <Pencil class="w-4 h-4" />
                                            </button>

                                            <!-- Toggle Status Switch -->
                                            <ConfirmModal
                                                :title="'Confirm Status Change'"
                                                :message="`Are you sure you want to set ${row.name} to ${row.status === 'active' ? 'Inactive' : 'Active'}?`"
                                                :showStatusButton="true"
                                                confirmText="Yes, Change"
                                                cancelText="Cancel"
                                                :status="row.status"
                                                @confirm="toggleStatus(row)"
                                            >
                                                <template #trigger>
                                                    <button
                                                        class="relative inline-flex items-center w-8 h-4 rounded-full transition-colors duration-300 focus:outline-none"
                                                        :class="
                                                            row.status === 'active'
                                                                ? 'bg-green-500 hover:bg-green-600'
                                                                : 'bg-red-400 hover:bg-red-500'
                                                        "
                                                        :title="
                                                            row.status === 'active'
                                                                ? 'Set Inactive'
                                                                : 'Set Active'
                                                        "
                                                    >
                                                        <span
                                                            class="absolute left-0.5 top-0.5 w-3 h-3 bg-white rounded-full shadow transform transition-transform duration-300"
                                                            :class="
                                                                row.status === 'active'
                                                                    ? 'translate-x-4'
                                                                    : 'translate-x-0'
                                                            "
                                                        ></span>
                                                    </button>
                                                </template>
                                            </ConfirmModal>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="!loading && filteredGroups.length === 0">
                                    <td colspan="7" class="text-center text-muted py-4">
                                        No variant groups found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ================== Add/Edit Modal ================== -->
            <div class="modal fade" id="variantGroupModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content rounded-4">
                        <!-- Modal Header -->
                        <div class="modal-header">
                            <h5 class="modal-title fw-semibold">
                                {{ editingGroup ? "Edit Variant Group" : "Add New Variant Group" }}
                            </h5>
                            <button
                                class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                                @click="resetModal"
                                data-bs-dismiss="modal"
                                aria-label="Close"
                                title="Close"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-6 w-6 text-red-500"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="modal-body">
                            <div class="row g-3">
                                <!-- Group Name -->
                                <div class="col-12">
                                    <label class="form-label">Group Name</label>
                                    <input
                                        v-model="variantGroupForm.name"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': formErrors.name }"
                                        placeholder="e.g., Size, Temperature, Crust Type"
                                    />
                                    <small v-if="formErrors.name" class="text-danger">
                                        {{ formErrors.name[0] }}
                                    </small>
                                </div>

                                

                               

                                <!-- Status -->
                                <div class="col-12">
                                    <label class="form-label">Status</label>
                                    <Select
                                        v-model="variantGroupForm.status"
                                        :options="statusOptions"
                                        optionLabel="label"
                                        optionValue="value"
                                        class="form-select"
                                        appendTo="self"
                                        :autoZIndex="true"
                                        :baseZIndex="2000"
                                        :class="{ 'is-invalid': formErrors.status }"
                                    />
                                    <small v-if="formErrors.status" class="text-danger">
                                        {{ formErrors.status[0] }}
                                    </small>
                                </div>

                                <!-- Description -->
                                <div class="col-12">
                                    <label class="form-label">Description (Optional)</label>
                                    <textarea
                                        v-model="variantGroupForm.description"
                                        class="form-control"
                                        rows="3"
                                        :class="{ 'is-invalid': formErrors.description }"
                                        placeholder="Enter group description..."
                                    ></textarea>
                                    <small v-if="formErrors.description" class="text-danger">
                                        {{ formErrors.description[0] }}
                                    </small>
                                </div>
                            </div>


                            <!-- Modal Actions -->
                            <div class="mt-4">
                                <button
                                    class="btn btn-primary rounded-pill px-4"
                                    :disabled="submitting"
                                    @click="submitVariantGroup"
                                >
                                    <template v-if="submitting">
                                        <span class="spinner-border spinner-border-sm me-2"></span>
                                        Saving...
                                    </template>
                                    <template v-else>
                                        {{ editingGroup ? "Save" : "Save" }}
                                    </template>
                                </button>

                                <button
                                    class="btn btn-secondary rounded-pill px-4 ms-2"
                                    data-bs-dismiss="modal"
                                    @click="resetModal"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /modal -->
        </div>
    </Master>
</template>

<style scoped>
/* Custom styles for search input */
.search-wrap {
    position: relative;
}

.search-wrap i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
}

.search-input {
    padding-left: 40px;
    min-width: 250px;
}

/* Hover effects for action buttons */
.p-2:hover {
    background-color: rgba(59, 130, 246, 0.1);
    border-radius: 50%;
}

:deep(.p-select) {
    background-color: white !important;
    color: black !important;
    border-color: #9b9c9c;
}

:deep(.p-select-list-container) {
    background-color: white !important;
    color: black !important;
}

:deep(.p-select-option) {
    background-color: transparent !important;
    color: black !important;
}

:deep(.p-select-option:hover) {
    background-color: #f0f0f0 !important;
    color: black !important;
}

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

/* Dark Mode Support */
:global(.dark .p-select) {
    background-color: #181818 !important;
    color: #fff !important;
    border-color: #555 !important;
}

:global(.dark .p-select-list-container) {
    background-color: #181818 !important;
    color: #fff !important;
}

:global(.dark .p-select-option) {
    background-color: transparent !important;
    color: #fff !important;
}

:global(.dark .p-select-option:hover),
:global(.dark .p-select-option.p-focus) {
    background-color: #222 !important;
    color: #fff !important;
}

:global(.dark .p-select-label) {
    color: #fff !important;
}

:global(.dark .p-placeholder) {
    color: #aaa !important;
}

/* Keep PrimeVue overlays above Bootstrap modal/backdrop */
:deep(.p-select-panel) {
    z-index: 2000 !important;
}
</style>