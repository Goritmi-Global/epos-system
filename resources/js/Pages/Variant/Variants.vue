<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted, nextTick } from "vue";
import { Package, CheckCircle, XCircle, DollarSign, Pencil, Plus, Trash2 } from "lucide-vue-next";
import { toast } from "vue3-toastify";
import axios from "axios";
import Select from "primevue/select";
import ConfirmModal from "@/Components/ConfirmModal.vue";
import { Head } from "@inertiajs/vue3";

/* ============================================
   DATA & STATE MANAGEMENT
============================================ */

// Main data stores
const variants = ref([]);
const variantGroups = ref([]);

// Form state for create/edit modal
const variantForm = ref({
    name: "",
    variant_group_id: null,
    price_modifier: 0,
    description: "",
    status: "active",
    sort_order: 0,
});

// Track if we're editing (null = create mode, object = edit mode)
const editingVariant = ref(null);

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
   FETCH DATA FROM API
============================================ */

/**
 * Fetch all variants from the backend
 */
const fetchVariants = async () => {
    loading.value = true;
    try {
        const res = await axios.get("/api/variants/all");
        variants.value = res.data.data;
    } catch (err) {
        console.error("Failed to fetch variants:", err);
        toast.error("Failed to load variants");
    } finally {
        loading.value = false;
    }
};

/**
 * Fetch all variant groups for dropdown
 */
const fetchVariantGroups = async () => {
    try {
        const res = await axios.get("/api/variant-groups/all");
        variantGroups.value = res.data.data;
    } catch (err) {
        console.error("Failed to fetch variant groups:", err);
        toast.error("Failed to load variant groups");
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
    fetchVariants();
    fetchVariantGroups();
});

/* ============================================
   KPI STATISTICS CARDS
============================================ */

/**
 * Computed statistics for dashboard cards
 */
const variantStats = computed(() => [
    {
        label: "Total Variants",
        value: variants.value.length,
        icon: Package,
        iconBg: "bg-light-primary",
        iconColor: "text-primary",
    },
    {
        label: "Active Variants",
        value: variants.value.filter((v) => v.status === "active").length,
        icon: CheckCircle,
        iconBg: "bg-light-success",
        iconColor: "text-success",
    },
    {
        label: "Inactive Variants",
        value: variants.value.filter((v) => v.status === "inactive").length,
        icon: XCircle,
        iconBg: "bg-light-danger",
        iconColor: "text-danger",
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
 * Filter variants based on search query
 */
const filteredVariants = computed(() => {
    const searchTerm = q.value.trim().toLowerCase();
    if (!searchTerm) return variants.value;

    return variants.value.filter((variant) =>
        variant.name.toLowerCase().includes(searchTerm) ||
        variant.variant_group?.name.toLowerCase().includes(searchTerm)
    );
});

/**
 * Handle focus on search input (prevents autofill)
 */
const handleFocus = (event) => {
    event.target.setAttribute("autocomplete", "off");
};

/* ============================================
   COMPUTED PROPERTIES
============================================ */

/**
 * Format variant groups for dropdown
 */
const groupOptions = computed(() => {
    return variantGroups.value
        .filter(g => g.status === 'active')
        .map(g => ({
            label: g.name,
            value: g.id
        }));
});

/* ============================================
   MODAL MANAGEMENT
============================================ */

/**
 * Reset modal form to initial state
 */
const resetModal = () => {
    variantForm.value = {
        name: "",
        variant_group_id: null,
        price_modifier: 0,
        description: "",
        status: "active",
        sort_order: 0,
    };
    editingVariant.value = null;
    formErrors.value = {};
};

/**
 * Open modal in edit mode with existing variant data
 */
const editRow = (row) => {
    editingVariant.value = row;

    // Populate form with existing data
    variantForm.value = {
        name: row.name,
        variant_group_id: row.variant_group_id,
        price_modifier: parseFloat(row.price_modifier) || 0,
        description: row.description || "",
        status: row.status,
        sort_order: row.sort_order || 0,
    };

    // Open Bootstrap modal
    const modalEl = document.getElementById("variantModal");
    const bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();
};

/* ============================================
   CREATE / UPDATE OPERATIONS
============================================ */

/**
 * Submit form (handles both create and update)
 */
const submitVariant = async () => {
    // Frontend validation
    if (!variantForm.value.variant_group_id) {
        toast.error("Please select a variant group");
        return;
    }

    submitting.value = true;
    formErrors.value = {};

    try {
        if (editingVariant.value) {
            // UPDATE existing variant
            await axios.post(
                `/api/variants/${editingVariant.value.id}`,
                variantForm.value
            );
            toast.success("Variant updated successfully");
        } else {
            // CREATE new variant
            await axios.post("/api/variants", variantForm.value);
            toast.success("Variant created successfully");
        }

        // Close modal
        const modal = bootstrap.Modal.getInstance(
            document.getElementById("variantModal")
        );
        modal?.hide();

        // Reset form and refresh data
        resetModal();
        await fetchVariants();
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
            const errorMessage = err.response?.data?.message || "Failed to save variant";
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
 * Toggle variant status between active and inactive
 */
const toggleStatus = async (row) => {
    const newStatus = row.status === "active" ? "inactive" : "active";

    try {
        await axios.patch(`/api/variants/${row.id}/toggle-status`, {
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
 * Delete variant
 */
const deleteVariant = async (row) => {
    if (!row?.id) return;

    try {
        await axios.delete(`/api/variants/${row.id}`);
        toast.success("Variant deleted successfully");
        await fetchVariants();
    } catch (err) {
        console.error("❌ Delete error:", err.response?.data || err.message);

        // Show specific error message from backend
        const errorMessage = err.response?.data?.message || "Failed to delete variant";
        toast.error(errorMessage);
    }
};

/* ============================================
   HELPER FUNCTIONS
============================================ */

/**
 * Format price modifier with proper sign
 */
const formatPriceModifier = (value) => {
    const num = parseFloat(value) || 0;
    const formatted = Math.abs(num).toFixed(2);
    
    if (num > 0) return `+£${formatted}`;
    if (num < 0) return `-£${formatted}`;
    return `£${formatted}`;
};

/**
 * Get group name by ID
 */
const getGroupName = (groupId) => {
    const group = variantGroups.value.find(g => g.id === groupId);
    return group ? group.name : 'N/A';
};
</script>

<template>
    <Master>
        <Head title="Variants" />

        <div class="page-wrapper">
            <!-- Page Header -->
            <h4 class="fw-semibold mb-3">Variants Management</h4>

            <!-- KPI Statistics Cards -->
            <div class="row g-3 mb-4">
                <div v-for="stat in variantStats" :key="stat.label" class="col-md-6 col-xl-3">
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
                        <h5 class="mb-0 fw-semibold">Variants</h5>

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
                                    placeholder="Search variants..."
                                    type="search"
                                    autocomplete="new-password"
                                    :name="inputId"
                                    role="presentation"
                                    @focus="handleFocus"
                                />
                                <input
                                    v-else
                                    class="form-control search-input  rounded-pill"
                                    placeholder="Search variants..."
                                    disabled
                                    type="text"
                                />
                            </div>

                            <!-- Add Variant Button -->
                            <button
                                data-bs-toggle="modal"
                                data-bs-target="#variantModal"
                                @click="resetModal"
                                class="d-flex align-items-center gap-1 px-4 py-2 rounded-pill btn btn-primary text-white"
                            >
                                <Plus class="w-4 h-4" /> Add Variant
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
                                    <th>Group</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Loading State -->
                                <tr v-if="loading">
                                    <td colspan="6" class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Data Rows -->
                                <tr v-else v-for="(row, i) in filteredVariants" :key="row.id">
                                    <!-- Serial Number -->
                                    <td>{{ i + 1 }}</td>

                                    <!-- Variant Name -->
                                    <td class="fw-semibold">{{ row.name }}</td>

                                    <!-- Group Name -->
                                    <td>
                                        <span class="badge bg-secondary px-3 py-2 rounded-pill">
                                            {{ row.variant_group?.name || 'N/A' }}
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

                                            <!-- Delete Button -->
                                            <ConfirmModal
                                                :title="'Confirm Delete'"
                                                :message="`Are you sure you want to delete ${row.name}?`"
                                                confirmText="Yes, Delete"
                                                cancelText="Cancel"
                                                @confirm="deleteVariant(row)"
                                            >
                                                <template #trigger>
                                                    <button
                                                        title="Delete"
                                                        class="p-2 rounded-full text-red-600 hover:bg-red-100"
                                                    >
                                                        <Trash2 class="w-4 h-4" />
                                                    </button>
                                                </template>
                                            </ConfirmModal>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="!loading && filteredVariants.length === 0">
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No variants found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ================== Add/Edit Modal ================== -->
            <div class="modal fade" id="variantModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content rounded-4">
                        <!-- Modal Header -->
                        <div class="modal-header">
                            <h5 class="modal-title fw-semibold">
                                {{ editingVariant ? "Edit Variant" : "Add New Variant" }}
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
                                <!-- Variant Group Selection -->
                                <div class="col-12">
                                    <label class="form-label">Variant Group *</label>
                                    <Select
                                        v-model="variantForm.variant_group_id"
                                        :options="groupOptions"
                                        optionLabel="label"
                                        optionValue="value"
                                        placeholder="Select a group"
                                        class="form-select"
                                        appendTo="self"
                                        :autoZIndex="true"
                                        :baseZIndex="2000"
                                        :class="{ 'is-invalid': formErrors.variant_group_id }"
                                    />
                                    
                                    <small
                                        v-if="formErrors.variant_group_id"
                                        class="text-danger d-block"
                                    >
                                        {{ formErrors.variant_group_id[0] }}
                                    </small>
                                </div>

                                <!-- Variant Name -->
                                <div class="col-12">
                                    <label class="form-label">Variant Name *</label>
                                    <input
                                        v-model="variantForm.name"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': formErrors.name }"
                                        placeholder="e.g., Small, Medium, Large, Hot, Cold"
                                    />
                                    <small v-if="formErrors.name" class="text-danger">
                                        {{ formErrors.name[0] }}
                                    </small>
                                </div>


                                <!-- Status -->
                                <div class="col-md-6">
                                    <label class="form-label">Status *</label>
                                    <Select
                                        v-model="variantForm.status"
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

                                <!-- Sort Order -->
                                <div class="col-md-6">
                                    <label class="form-label">Sort Order</label>
                                    <input
                                        v-model.number="variantForm.sort_order"
                                        type="number"
                                        min="0"
                                        class="form-control"
                                        :class="{ 'is-invalid': formErrors.sort_order }"
                                        placeholder="0"
                                    />
                                    <small class="text-muted">
                                        Display order (lower numbers first)
                                    </small>
                                    <small
                                        v-if="formErrors.sort_order"
                                        class="text-danger d-block"
                                    >
                                        {{ formErrors.sort_order[0] }}
                                    </small>
                                </div>

                                <!-- Description -->
                                <div class="col-12">
                                    <label class="form-label">Description (Optional)</label>
                                    <textarea
                                        v-model="variantForm.description"
                                        class="form-control"
                                        rows="3"
                                        :class="{ 'is-invalid': formErrors.description }"
                                        placeholder="Enter variant description..."
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
                                    @click="submitVariant"
                                >
                                    <template v-if="submitting">
                                        <span class="spinner-border spinner-border-sm me-2"></span>
                                        Saving...
                                    </template>
                                    <template v-else>
                                        {{ editingVariant ? "Save" : "Save" }}
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

/* PrimeVue Select Styling */
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