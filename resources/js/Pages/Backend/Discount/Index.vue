<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted, onUpdated, watch } from "vue";
import { Percent, Calendar, AlertTriangle, XCircle, Pencil, Plus, Trash2 } from "lucide-vue-next";
import { toast } from "vue3-toastify";
import axios from "axios";
import Select from "primevue/select";
import ConfirmModal from "@/Components/ConfirmModal.vue";
import { useFormatters } from '@/composables/useFormatters'
import { nextTick } from "vue";
import { Head } from "@inertiajs/vue3";

const { formatMoney, formatCurrencySymbol, formatNumber, dateFmt } = useFormatters()

/* ============================================================
   DATA STATE
   ============================================================ */

// Discounts list and editing state
const discounts = ref([]);
const editingDiscount = ref(null);
const submitting = ref(false);
const discountFormErrors = ref({});

// Discount type and status options for dropdowns
const discountOptions = [
    { label: "Flat Amount", value: "flat" },
    { label: "Percentage", value: "percent" },
];

const statusOptions = [
    { label: "Active", value: "active" },
    { label: "Deactive", value: "inactive" },
];

// Form state for creating/editing discounts
const discountForm = ref({
    name: "",
    type: "percent",
    status: "active",
    start_date: "",
    end_date: "",
    min_purchase: 0,
    max_discount: null,
    description: "",
    discount_amount: null,
});

// Search and filter state
const q = ref("");
const searchKey = ref(Date.now());
const inputId = `search-${Math.random().toString(36).substr(2, 9)}`;
const isReady = ref(false);

/* ============================================================
   COMPUTED PROPERTIES
   ============================================================ */

/**
 * Filter discounts based on search query
 * Searches by discount name (case-insensitive)
 */
const filtered = computed(() => {
    const t = q.value.trim().toLowerCase();
    if (!t) return discounts.value;
    return discounts.value.filter((d) => d.name.toLowerCase().includes(t));
});

/**
 * KPI Statistics Cards
 * Shows total, active, flat, and percentage discounts
 */
const discountStats = computed(() => [
    {
        label: "Total Discounts",
        value: discounts.value.length,
        icon: Percent,
        iconBg: "bg-light-primary",
        iconColor: "text-primary",
    },
    {
        label: "Active Discounts",
        value: discounts.value.filter((d) => d.status === "active").length,
        icon: Calendar,
        iconBg: "bg-light-success",
        iconColor: "text-success",
    },
    {
        label: "Flat Discount",
        value: discounts.value.filter((d) => d.type === "flat").length,
        icon: AlertTriangle,
        iconBg: "bg-light-warning",
        iconColor: "text-warning",
    },
    {
        label: "Percentage",
        value: discounts.value.filter((d) => d.type === "percent").length,
        icon: XCircle,
        iconBg: "bg-light-danger",
        iconColor: "text-danger",
    },
]);

/* ============================================================
   API CALLS - FETCH DATA
   ============================================================ */

/**
 * Fetch all discounts from the backend
 * Called on component mount and after create/update/delete
 */
const fetchDiscounts = async () => {
    try {
        const res = await axios.get("/api/discounts/all");
        discounts.value = res.data.data;
        console.log('Fetched discounts:', discounts.value);
    } catch (err) {
        console.error("Failed to fetch discounts:", err);
        toast.error("Failed to load discounts");
    }
};

/* ============================================================
   MODAL FORM MANAGEMENT
   ============================================================ */

/**
 * Reset the discount form to default values
 * Used when opening add modal or closing edit modal
 */
const resetModal = () => {
    discountForm.value = {
        name: "",
        type: "percent",
        status: "active",
        start_date: "",
        end_date: "",
        min_purchase: 0,
        max_discount: null,
        description: "",
        discount_amount: null,
    };
    editingDiscount.value = null;
    discountFormErrors.value = {};
};

/**
 * Load existing discount data into form for editing
 * @param {Object} row - The discount record to edit
 */
const editRow = (row) => {
    editingDiscount.value = row;
    discountForm.value = {
        name: row.name,
        type: row.type,
        status: row.status,
        start_date: row.start_date,
        end_date: row.end_date,
        min_purchase: row.min_purchase,
        max_discount: row.max_discount,
        description: row.description || "",
        discount_amount: row.discount_amount,
    };

    const modalEl = document.getElementById("discountModal");
    const bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();
};

/* ============================================================
   FORM SUBMISSION - CREATE & UPDATE
   ============================================================ */

/**
 * Submit discount form (Create or Update)
 * Validates and sends data to backend
 */
const submitDiscount = async () => {
    submitting.value = true;
    discountFormErrors.value = {};

    try {
        if (editingDiscount.value) {
            // UPDATE existing discount
            await axios.post(`/discounts/${editingDiscount.value.id}`, discountForm.value);
            toast.success("Discount updated successfully");
        } else {
            // CREATE new discount
            await axios.post("/discounts", discountForm.value);
            toast.success("Discount created successfully");
        }

        // Close modal and refresh data
        const modal = bootstrap.Modal.getInstance(document.getElementById("discountModal"));
        modal?.hide();

        resetModal();
        await fetchDiscounts();
    } catch (err) {
        console.error("❌ Error:", err.response?.data || err.message);

        // Handle validation errors (422)
        if (err.response?.status === 422 && err.response?.data?.errors) {
            discountFormErrors.value = err.response.data.errors;
            const errorMessages = Object.values(err.response.data.errors).flat();
            toast.error(errorMessages.join("\n"));
        } else {
            // Handle other errors
            const errorMessage = err.response?.data?.message || "Failed to save discount";
            toast.error(errorMessage);
        }
    } finally {
        submitting.value = false;
    }
};

/* ============================================================
   ACTION HANDLERS - STATUS & DELETE
   ============================================================ */

/**
 * Toggle discount status between active and inactive
 * Called when user clicks the status toggle button
 * @param {Object} row - The discount record to toggle
 */
const toggleStatus = async (row) => {
    const newStatus = row.status === "active" ? "inactive" : "active";

    try {
        await axios.patch(`/api/discounts/${row.id}/toggle-status`, {
            status: newStatus,
        });
        row.status = newStatus;
        toast.success(`Discount status updated to ${newStatus}`);
    } catch (error) {
        console.error("Failed to update status:", error);
        toast.error("Failed to update status");
    }
};

/**
 * Delete a discount
 * Called from delete button in table actions
 * @param {Object} row - The discount record to delete
 */
const deleteDiscount = async (row) => {
    try {
        await axios.delete(`/discounts/${row.id}`);
        toast.success("Discount deleted successfully");
        await fetchDiscounts();
    } catch (error) {
        console.error("Failed to delete discount:", error);
        toast.error("Failed to delete discount");
    }
};

/* ============================================================
   LIFECYCLE HOOKS
   ============================================================ */

/**
 * Initialize component on mount
 * - Clear search input
 * - Mark component as ready (prevent autofill issues)
 * - Fetch all discounts from backend
 */
onMounted(async () => {
    q.value = "";
    searchKey.value = Date.now();
    await nextTick();

    // Delay to prevent browser autofill
    setTimeout(() => {
        isReady.value = true;

        // Force clear any autofill that happened
        const input = document.getElementById(inputId);
        if (input) {
            input.value = '';
            q.value = '';
        }
    }, 100);

    await fetchDiscounts();
});

/**
 * Re-initialize feather icons after DOM update
 */
onUpdated(() => window.feather?.replace());
</script>

<template>
    <Master>
        <Head title="Discount" />
        
        <div class="page-wrapper">
            <h4 class="fw-semibold mb-3">Discount Management</h4>

            <!-- ====== KPI STATISTICS CARDS ====== -->
            <div class="row g-3 mb-4">
                <div v-for="stat in discountStats" :key="stat.label" class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body d-flex align-items-center">
                            <!-- Icon Circle -->
                            <div :class="[stat.iconBg, stat.iconColor]"
                                class="rounded-circle p-3 d-flex align-items-center justify-content-center me-3"
                                style="width: 56px; height: 56px">
                                <component :is="stat.icon" class="w-6 h-6" />
                            </div>
                            <!-- Stat Info -->
                            <div>
                                <h3 class="mb-0 fw-bold">{{ stat.value }}</h3>
                                <p class="text-muted mb-0 small">{{ stat.label }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ====== DISCOUNTS TABLE CARD ====== -->
            <div class="card border-0 shadow-lg rounded-4">
                <div class="card-body">
                    <!-- Table Toolbar -->
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 fw-semibold">Discounts</h5>

                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <!-- Search Input -->
                            <div class="search-wrap">
                                <i class="bi bi-search"></i>
                                
                                <!-- Hidden autofill prevention input -->
                                <input type="email" name="email" autocomplete="email"
                                    style="position: absolute; left: -9999px; width: 1px; height: 1px;"
                                    tabindex="-1" aria-hidden="true" />

                                <!-- Actual search input (conditionally rendered) -->
                                <input v-if="isReady" :id="inputId" v-model="q" :key="searchKey"
                                    class="form-control search-input" placeholder="Search discounts"
                                    type="search" autocomplete="new-password" :name="inputId"
                                    role="presentation" />
                                <input v-else class="form-control search-input" placeholder="Search discounts"
                                    disabled type="text" />
                            </div>

                            <!-- Add New Button -->
                            <button data-bs-toggle="modal" data-bs-target="#discountModal"
                                @click="resetModal"
                                class="d-flex align-items-center gap-1 px-4 py-2 rounded-pill btn btn-primary text-white">
                                <Plus class="w-4 h-4" /> Add Discount
                            </button>
                        </div>
                    </div>

                    <!-- Discounts Table -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="border-top small text-muted">
                                <tr>
                                    <th>S.#</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Min Purchase</th>
                                    <th>Max Discount</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Table Rows -->
                                <tr v-for="(row, i) in filtered" :key="row.id">
                                    <td>{{ i + 1 }}</td>
                                    <td class="fw-semibold">{{ row.name }}</td>
                                    
                                    <!-- Type Badge -->
                                    <td>
                                        <span :class="row.type === 'flat'
                                            ? 'badge bg-primary px-4 py-2 rounded-pill'
                                            : 'badge bg-warning px-4 py-2 rounded-pill'
                                            ">
                                            {{ row.type === "flat" ? "Flat" : "Percent" }}
                                        </span>
                                    </td>
                                    
                                    <!-- Discount Amount -->
                                    <td>
                                        {{ row.type === "flat" 
                                            ? formatCurrencySymbol(row.discount_amount)
                                            : row.discount_amount + "%" 
                                        }}
                                    </td>
                                    
                                    <!-- Dates -->
                                    <td>{{ dateFmt(row.start_date) }}</td>
                                    <td>{{ dateFmt(row.end_date) }}</td>
                                    
                                    <!-- Purchase Requirements -->
                                    <td>{{ formatCurrencySymbol(row.min_purchase) }}</td>
                                    <td>
                                        {{ row.max_discount ? formatCurrencySymbol(row.max_discount) : "N/A" }}
                                    </td>
                                    
                                    <!-- Status Badge -->
                                    <td class="text-center">
                                        <span :class="row.status === 'active'
                                            ? 'badge bg-success px-4 py-2 rounded-pill'
                                            : 'badge bg-danger px-4 py-2 rounded-pill'
                                            ">
                                            {{ row.status === "active" ? "Active" : "Inactive" }}
                                        </span>
                                    </td>
                                    
                                    <!-- Action Buttons -->
                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center gap-3">
                                            <!-- Edit Button -->
                                            <button @click="editRow(row)" title="Edit"
                                                class="p-2 rounded-full text-blue-600 hover:bg-blue-100">
                                                <Pencil class="w-4 h-4" />
                                            </button>

                                            <!-- Toggle Status Button with Confirmation -->
                                            <ConfirmModal :title="'Confirm Status Change'"
                                                :message="`Are you sure you want to set ${row.name} to ${row.status === 'active' ? 'Inactive' : 'Active'}?`"
                                                :showStatusButton="true" confirmText="Yes, Change"
                                                cancelText="Cancel" :status="row.status"
                                                @confirm="toggleStatus(row)">
                                                <template #trigger>
                                                    <!-- Toggle Switch -->
                                                    <button
                                                        class="relative inline-flex items-center w-8 h-4 rounded-full transition-colors duration-300 focus:outline-none"
                                                        :class="row.status === 'active' ? 'bg-green-500 hover:bg-green-600' : 'bg-red-400 hover:bg-red-500'"
                                                        :title="row.status === 'active' ? 'Set Inactive' : 'Set Active'">
                                                        <!-- Circle -->
                                                        <span
                                                            class="absolute left-0.5 top-0.5 w-3 h-3 bg-white rounded-full shadow transform transition-transform duration-300"
                                                            :class="row.status === 'active' ? 'translate-x-4' : 'translate-x-0'"></span>
                                                    </button>
                                                </template>
                                            </ConfirmModal>

                                            <!-- Delete Button with Confirmation -->
                                            <ConfirmModal :title="'Confirm Delete'"
                                                :message="`Are you sure you want to delete ${row.name}? This action cannot be undone.`"
                                                confirmText="Yes, Delete" cancelText="Cancel"
                                                @confirm="deleteDiscount(row)">
                                                <template #trigger>
                                                    <button title="Delete"
                                                        class="p-2 rounded-full text-red-600 hover:bg-red-100">
                                                        <Trash2 class="w-4 h-4" />
                                                    </button>
                                                </template>
                                            </ConfirmModal>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="filtered.length === 0">
                                    <td colspan="10" class="text-center text-muted py-4">
                                        No discounts found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ================== ADD/EDIT DISCOUNT MODAL ================== -->
            <div class="modal fade" id="discountModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content rounded-4">
                        <!-- Modal Header -->
                        <div class="modal-header">
                            <h5 class="modal-title fw-semibold">
                                {{ editingDiscount ? "Edit Discount" : "Add New Discount" }}
                            </h5>
                            <button
                                class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                                @click="resetModal" data-bs-dismiss="modal" aria-label="Close"
                                title="Close">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="modal-body">
                            <div class="row g-3">
                                <!-- Discount Name -->
                                <div class="col-12">
                                    <label class="form-label">Discount Name *</label>
                                    <input v-model="discountForm.name" type="text" class="form-control"
                                        :class="{ 'is-invalid': discountFormErrors.name }"
                                        placeholder="Enter discount name" />
                                    <small v-if="discountFormErrors.name" class="text-danger">
                                        {{ discountFormErrors.name[0] }}
                                    </small>
                                </div>

                                <!-- Discount Type -->
                                <div class="col-md-6">
                                    <label class="form-label">Discount Type *</label>
                                    <Select v-model="discountForm.type" :options="discountOptions"
                                        optionLabel="label" appendTo="self" :autoZIndex="true"
                                        :baseZIndex="2000" optionValue="value" class="form-select"
                                        :class="{ 'is-invalid': discountFormErrors.type }" />
                                    <small v-if="discountFormErrors.type" class="text-danger">
                                        {{ discountFormErrors.type[0] }}
                                    </small>
                                </div>

                                <!-- Status -->
                                <div class="col-md-6">
                                    <label class="form-label">Status *</label>
                                    <Select v-model="discountForm.status" :options="statusOptions"
                                        optionLabel="label" optionValue="value" class="form-select"
                                        appendTo="self" :autoZIndex="true" :baseZIndex="2000"
                                        :class="{ 'is-invalid': discountFormErrors.status }" />
                                    <small v-if="discountFormErrors.status" class="text-danger">
                                        {{ discountFormErrors.status[0] }}
                                    </small>
                                </div>

                                <!-- Discount Amount -->
                                <div class="col-12">
                                    <label class="form-label">Discount Amount *</label>
                                    <input v-model="discountForm.discount_amount" type="number"
                                        class="form-control" :class="{ 'is-invalid': discountFormErrors.discount_amount }"
                                        placeholder="Enter discount amount" />
                                    <small v-if="discountFormErrors.discount_amount" class="text-danger">
                                        {{ discountFormErrors.discount_amount[0] }}
                                    </small>
                                </div>

                                <!-- Start Date -->
                                <div class="col-md-6">
                                    <label class="form-label">Start Date *</label>
                                    <VueDatePicker v-model="discountForm.start_date" :format="dateFmt"
                                        :min-date="new Date()" :enableTimePicker="false"
                                        placeholder="Select start date" :class="{ 'is-invalid': discountFormErrors.start_date }" />
                                    <small v-if="discountFormErrors.start_date" class="text-danger">
                                        {{ discountFormErrors.start_date[0] }}
                                    </small>
                                </div>

                                <!-- End Date -->
                                <div class="col-md-6">
                                    <label class="form-label">End Date *</label>
                                    <VueDatePicker v-model="discountForm.end_date" :format="dateFmt"
                                        :min-date="new Date()" :enableTimePicker="false"
                                        placeholder="Select end date" :class="{ 'is-invalid': discountFormErrors.end_date }" />
                                    <small v-if="discountFormErrors.end_date" class="text-danger">
                                        {{ discountFormErrors.end_date[0] }}
                                    </small>
                                </div>

                                <!-- Minimum Purchase Amount -->
                                <div class="col-md-6">
                                    <label class="form-label">Minimum Purchase Amount *</label>
                                    <input v-model="discountForm.min_purchase" type="number"
                                        step="0.01" class="form-control" :class="{ 'is-invalid': discountFormErrors.min_purchase }"
                                        placeholder="0.00" />
                                    <small v-if="discountFormErrors.min_purchase" class="text-danger">
                                        {{ discountFormErrors.min_purchase[0] }}
                                    </small>
                                </div>

                                <!-- Maximum Discount (Optional) -->
                                <div class="col-md-6">
                                    <label class="form-label">Maximum Discount (Optional)</label>
                                    <input v-model="discountForm.max_discount" type="number"
                                        step="0.01" class="form-control" :class="{ 'is-invalid': discountFormErrors.max_discount }"
                                        placeholder="0.00" />
                                    <small v-if="discountFormErrors.max_discount" class="text-danger">
                                        {{ discountFormErrors.max_discount[0] }}
                                    </small>
                                </div>

                                <!-- Description (Optional) -->
                                <div class="col-12">
                                    <label class="form-label">Description (Optional)</label>
                                    <textarea v-model="discountForm.description" class="form-control"
                                        rows="3" :class="{ 'is-invalid': discountFormErrors.description }"
                                        placeholder="Enter discount description"></textarea>
                                    <small v-if="discountFormErrors.description" class="text-danger">
                                        {{ discountFormErrors.description[0] }}
                                    </small>
                                </div>
                            </div>

                            <hr class="my-4" />

                            <!-- Modal Footer -->
                            <div class="mt-4">
                                <button class="btn btn-primary rounded-pill px-4"
                                    :disabled="submitting" @click="submitDiscount()">
                                    <template v-if="submitting">
                                        <span class="spinner-border spinner-border-sm me-2"></span>
                                        Saving...
                                    </template>
                                    <template v-else>
                                        {{ editingDiscount ? "Update" : "Save" }}
                                    </template>
                                </button>

                                <button class="btn btn-secondary rounded-pill px-4 ms-2"
                                    data-bs-dismiss="modal" @click="resetModal">
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
/* Search Input Styling */
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
    border-radius: 50px;
}

/* ======================== PrimeVue Select Styling ======================== */

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
    color: black !important;
}

/* Hovered/focused option */
:deep(.p-select-option:hover),
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

/* Keep PrimeVue overlays above Bootstrap modal */
:deep(.p-select-panel) {
    z-index: 2000 !important;
}

/* ======================== Dark Mode Select Styling ======================== */

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

:global(.dark .p-select-panel) {
    z-index: 2000 !important;
}
</style>