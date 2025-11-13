<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted, onUpdated, onUnmounted, reactive } from "vue";
import { Percent, Calendar, AlertTriangle, XCircle, Pencil, Plus, Trash2 } from "lucide-vue-next";
import { toast } from "vue3-toastify";
import axios from "axios";
import Select from "primevue/select";
import ConfirmModal from "@/Components/ConfirmModal.vue";
import { useFormatters } from '@/composables/useFormatters'
import { nextTick } from "vue";
import { Head } from "@inertiajs/vue3";

// Import PrimeVue Tabs
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';

const { formatMoney, formatCurrencySymbol, formatNumber, dateFmt } = useFormatters()

/* ============================================================
   DATA STATE
   ============================================================ */

// Discounts list and editing state
const discounts = ref([]);
const editingDiscount = ref(null);
const submitting = ref(false);
const discountFormErrors = ref({});

// Discount Approvals
const pendingRequests = ref([]);
const loadingApprovals = ref(false);
const processing = reactive({});
const approvalNotes = reactive({});
const showOrderItems = reactive({});
const refreshInterval = ref(null);

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
 */
const filtered = computed(() => {
    const t = q.value.trim().toLowerCase();
    if (!t) return discounts.value;
    return discounts.value.filter((d) => d.name.toLowerCase().includes(t));
});

/**
 * KPI Statistics Cards
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

/**
 * Fetch pending approval requests
 */
const fetchPendingRequests = async (silent = false) => {
    // Prevent multiple simultaneous requests
    if (loadingApprovals.value && !silent) return;
    
    if (!silent) {
        loadingApprovals.value = true;
    }
    
    try {
        const response = await axios.get('/api/discount-approvals/pending');
        if (response.data.success) {
            pendingRequests.value = response.data.data;
        }
    } catch (error) {
        console.error('Error fetching pending requests:', error);
        if (!silent) {
            toast.error('Failed to load approval requests');
        }
    } finally {
        if (!silent) {
            loadingApprovals.value = false;
        }
    }
};

/* ============================================================
   MODAL FORM MANAGEMENT
   ============================================================ */

/**
 * Reset the discount form to default values
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
 */
const submitDiscount = async () => {
    submitting.value = true;
    discountFormErrors.value = {};

    try {
        if (editingDiscount.value) {
            await axios.patch(`/discounts/${editingDiscount.value.id}`, discountForm.value);
            toast.success("Discount updated successfully");
        } else {
            await axios.post("/discounts", discountForm.value);
            toast.success("Discount created successfully");
        }

        const modal = bootstrap.Modal.getInstance(document.getElementById("discountModal"));
        modal?.hide();

        resetModal();
        await fetchDiscounts();
    } catch (err) {
        console.error("❌ Error:", err.response?.data || err.message);

        if (err.response?.status === 422 && err.response?.data?.errors) {
            discountFormErrors.value = err.response.data.errors;
            const errorMessages = Object.values(err.response.data.errors).flat();
            toast.error(errorMessages.join("\n"));
        } else {
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
   DISCOUNT APPROVAL HANDLERS
   ============================================================ */

/**
 * Respond to approval request (Approve/Reject)
 */
const respondToRequest = async (requestId, status) => {
    processing[requestId] = true;

    try {
        const response = await axios.post(`/api/discount-approvals/${requestId}/respond`, {
            status: status,
            approval_note: approvalNotes[requestId] || null
        });

        if (response.data.success) {
            toast.success(`Discount ${status} successfully!`);

            // Remove from pending list
            pendingRequests.value = pendingRequests.value.filter(r => r.id !== requestId);

            // Clear note
            delete approvalNotes[requestId];
        }
    } catch (error) {
        console.error('Error responding to request:', error);
        toast.error(error.response?.data?.message || `Failed to ${status} discount`);
    } finally {
        processing[requestId] = false;
    }
};

/**
 * Toggle order items visibility
 */
const toggleOrderItems = (requestId) => {
    showOrderItems[requestId] = !showOrderItems[requestId];
};

/**
 * Format date time
 */
const formatDateTime = (timestamp) => {
    if (!timestamp) return '-';
    const date = new Date(timestamp);
    return date.toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

/**
 * Auto-refresh approval requests
 */
const startAutoRefresh = () => {
    // Clear any existing interval
    if (refreshInterval.value) {
        clearInterval(refreshInterval.value);
    }
    
    refreshInterval.value = setInterval(() => {
        // Use silent mode for background refresh (no loading spinner)
        fetchPendingRequests(true);
    }, 30000); // Every 30 seconds instead of 10
};

const stopAutoRefresh = () => {
    if (refreshInterval.value) {
        clearInterval(refreshInterval.value);
        refreshInterval.value = null;
    }
};

/* ============================================================
   LIFECYCLE HOOKS
   ============================================================ */

onMounted(async () => {
    q.value = "";
    searchKey.value = Date.now();
    await nextTick();

    setTimeout(() => {
        isReady.value = true;
        const input = document.getElementById(inputId);
        if (input) {
            input.value = '';
            q.value = '';
        }
    }, 100);

    await fetchDiscounts();
    await fetchPendingRequests(); // Initial load with spinner
    startAutoRefresh(); // Start background refresh

    // Listen for real-time updates
    if (window.Echo) {
        window.Echo.channel('discount-approvals')
            .listen('.approval.requested', (event) => {
                console.log('New approval request received:', event.approvals);
                fetchPendingRequests(true); // Silent refresh
                toast.info('New discount approval request received!');
            });
    }
});

onUnmounted(() => {
    stopAutoRefresh();
});

onUpdated(() => window.feather?.replace());
</script>

<template>
    <Master>
        <Head title="Discount Management" />

        <div class="page-wrapper">
            <h4 class="fw-semibold mb-3">Discount Management</h4>

            <!-- Tabs Component -->
            <Tabs value="0" class="w-100">
                <!-- Tab Headers -->
                <TabList>
                    <Tab value="0">Discounts</Tab>
                    <Tab value="1">Discount Approvals</Tab>
                </TabList>

                <!-- Tab Panels -->
                <TabPanels>
                    <!-- ========== DISCOUNTS TAB ========== -->
                    <TabPanel value="0">
                        <div class="mt-3">
                            <!-- KPI Statistics Cards -->
                            <div class="row g-3 mb-4">
                                <div v-for="stat in discountStats" :key="stat.label" class="col-md-6 col-xl-3">
                                    <div class="card border-0 shadow-sm rounded-4">
                                        <div class="card-body d-flex align-items-center">
                                            <div :class="[stat.iconBg, stat.iconColor]"
                                                class="rounded-circle p-3 d-flex align-items-center justify-content-center me-3"
                                                style="width: 56px; height: 56px">
                                                <component :is="stat.icon" class="w-6 h-6" />
                                            </div>
                                            <div>
                                                <h3 class="mb-0 fw-bold">{{ stat.value }}</h3>
                                                <p class="text-muted mb-0 small">{{ stat.label }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Discounts Table Card -->
                            <div class="card border-0 shadow-lg rounded-4">
                                <div class="card-body">
                                    <!-- Table Toolbar -->
                                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0 fw-semibold">Discounts</h5>

                                        <div class="d-flex flex-wrap gap-2 align-items-center">
                                            <!-- Search Input -->
                                            <div class="search-wrap">
                                                <i class="bi bi-search"></i>

                                                <input type="email" name="email" autocomplete="email"
                                                    style="position: absolute; left: -9999px; width: 1px; height: 1px;"
                                                    tabindex="-1" aria-hidden="true" />

                                                <input v-if="isReady" :id="inputId" v-model="q" :key="searchKey"
                                                    class="form-control search-input" placeholder="Search discounts"
                                                    type="search" autocomplete="new-password" :name="inputId"
                                                    role="presentation" />
                                                <input v-else class="form-control search-input"
                                                    placeholder="Search discounts" disabled type="text" />
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
                                                <tr v-for="(row, i) in filtered" :key="row.id">
                                                    <td>{{ i + 1 }}</td>
                                                    <td class="fw-semibold">{{ row.name }}</td>

                                                    <td>
                                                        <span :class="row.type === 'flat'
                                                            ? 'badge bg-primary px-4 py-2 rounded-pill'
                                                            : 'badge bg-warning px-4 py-2 rounded-pill'
                                                            ">
                                                            {{ row.type === "flat" ? "Flat" : "Percent" }}
                                                        </span>
                                                    </td>

                                                    <td>
                                                        {{ row.type === "flat"
                                                            ? formatCurrencySymbol(row.discount_amount)
                                                            : row.discount_amount + "%"
                                                        }}
                                                    </td>

                                                    <td>{{ dateFmt(row.start_date) }}</td>
                                                    <td>{{ dateFmt(row.end_date) }}</td>
                                                    <td>{{ formatCurrencySymbol(row.min_purchase) }}</td>
                                                    <td>
                                                        {{ row.max_discount ? formatCurrencySymbol(row.max_discount) :
                                                            "N/A" }}
                                                    </td>

                                                    <td class="text-center">
                                                        <span :class="row.status === 'active'
                                                            ? 'badge bg-success px-4 py-2 rounded-pill'
                                                            : 'badge bg-danger px-4 py-2 rounded-pill'
                                                            ">
                                                            {{ row.status === "active" ? "Active" : "Inactive" }}
                                                        </span>
                                                    </td>

                                                    <td class="text-center">
                                                        <div class="d-inline-flex align-items-center gap-3">
                                                            <button @click="editRow(row)" title="Edit"
                                                                class="p-2 rounded-full text-blue-600 hover:bg-blue-100">
                                                                <Pencil class="w-4 h-4" />
                                                            </button>

                                                            <ConfirmModal :title="'Confirm Status Change'"
                                                                :message="`Are you sure you want to set ${row.name} to ${row.status === 'active' ? 'Inactive' : 'Active'}?`"
                                                                :showStatusButton="true" confirmText="Yes, Change"
                                                                cancelText="Cancel" :status="row.status"
                                                                @confirm="toggleStatus(row)">
                                                                <template #trigger>
                                                                    <button
                                                                        class="relative inline-flex items-center w-8 h-4 rounded-full transition-colors duration-300 focus:outline-none"
                                                                        :class="row.status === 'active' ? 'bg-green-500 hover:bg-green-600' : 'bg-red-400 hover:bg-red-500'"
                                                                        :title="row.status === 'active' ? 'Set Inactive' : 'Set Active'">
                                                                        <span
                                                                            class="absolute left-0.5 top-0.5 w-3 h-3 bg-white rounded-full shadow transform transition-transform duration-300"
                                                                            :class="row.status === 'active' ? 'translate-x-4' : 'translate-x-0'"></span>
                                                                    </button>
                                                                </template>
                                                            </ConfirmModal>

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
                        </div>
                    </TabPanel>

                    <!-- ========== DISCOUNT APPROVALS TAB ========== -->
                    <TabPanel value="1">
                        <div class="mt-3">
                            <div class="card border-0 shadow-sm rounded-4">
                                <div
                                    class="card-header bg-white border-0 d-flex justify-content-between align-items-center py-3">
                                    <h4 class="mb-0 fw-bold">
                                        <i class="bi bi-shield-check text-primary me-2"></i>
                                        Discount Approval Requests
                                    </h4>
                                    <button class="btn btn-outline-primary btn-sm" @click="fetchPendingRequests">
                                        <i class="bi bi-arrow-clockwise me-1"></i>
                                        Refresh
                                    </button>
                                </div>

                                <div class="card-body">
                                    <!-- Loading State -->
                                    <div v-if="loadingApprovals" class="text-center py-5">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-3 text-muted">Loading approval requests...</p>
                                    </div>

                                    <!-- No Pending Requests -->
                                    <div v-else-if="pendingRequests.length === 0" class="text-center py-5">
                                        <i class="bi bi-inbox" style="font-size: 4rem; opacity: 0.3;"></i>
                                        <p class="mt-3 text-muted fw-semibold">No pending discount approval requests</p>
                                    </div>

                                    <!-- Pending Requests List -->
                                    <div v-else class="row g-3">
                                        <div v-for="request in pendingRequests" :key="request.id" class="col-md-6">
                                            <div class="card border border-warning rounded-4 h-100">
                                                <div class="card-body">
                                                    <!-- Header -->
                                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                                        <div>
                                                            <h5 class="fw-bold mb-1">{{ request.discount.name }}</h5>
                                                            <span class="badge bg-warning">
                                                                <i class="bi bi-clock-history me-1"></i>
                                                                Pending
                                                            </span>
                                                        </div>
                                                        <div class="text-end">
                                                            <div class="h4 text-success mb-0">
                                                                -£{{ parseFloat(request.discount_amount).toFixed(2) }}
                                                            </div>
                                                            <small class="text-muted">Discount Amount</small>
                                                        </div>
                                                    </div>

                                                    <!-- Request Details -->
                                                    <div class="mb-3">
                                                        <div class="d-flex align-items-center gap-2 mb-2">
                                                            <i class="bi bi-person-circle text-muted"></i>
                                                            <small class="text-muted">
                                                                Requested by: <span class="fw-semibold">{{
                                                                    request.requested_by.name }}</span>
                                                            </small>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-2 mb-2">
                                                            <i class="bi bi-clock text-muted"></i>
                                                            <small class="text-muted">
                                                                {{ formatDateTime(request.requested_at) }}
                                                            </small>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <i class="bi bi-cart text-muted"></i>
                                                            <small class="text-muted">
                                                                Order Subtotal: <span class="fw-semibold">£{{
                                                                    parseFloat(request.order_subtotal).toFixed(2)
                                                                }}</span>
                                                            </small>
                                                        </div>
                                                    </div>

                                                    <!-- Request Note -->
                                                    <div v-if="request.request_note"
                                                        class="alert alert-light border mb-3 py-2">
                                                        <small class="text-muted">
                                                            <i class="bi bi-chat-left-text me-1"></i>
                                                            {{ request.request_note }}
                                                        </small>
                                                    </div>

                                                    <!-- Order Items Preview -->
                                                    <div class="mb-3">
                                                        <button class="btn btn-link btn-sm p-0 text-decoration-none"
                                                            @click="toggleOrderItems(request.id)">
                                                            <i class="bi"
                                                                :class="showOrderItems[request.id] ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                                                            View Order Items ({{ request.order_items?.length || 0 }})
                                                        </button>

                                                        <div v-if="showOrderItems[request.id]" class="mt-2">
                                                            <div class="list-group list-group-flush small">
                                                                <div v-for="item in request.order_items" :key="item.id"
                                                                    class="list-group-item px-0 py-2 d-flex justify-content-between">
                                                                    <span>{{ item.title }} × {{ item.qty }}</span>
                                                                    <span class="fw-semibold">£{{
                                                                        parseFloat(item.price).toFixed(2) }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Approval Note Input -->
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Response Note
                                                            (Optional)</label>
                                                        <textarea v-model="approvalNotes[request.id]"
                                                            class="form-control form-control-sm" rows="2"
                                                            placeholder="Add a note for the cashier..."></textarea>
                                                    </div>

                                                    <!-- Action Buttons -->
                                                    <div class="d-flex gap-2">
                                                        <button class="btn btn-success flex-fill"
                                                            @click="respondToRequest(request.id, 'approved')"
                                                            :disabled="processing[request.id]">
                                                            <i class="bi bi-check-circle me-1"></i>
                                                            Approve
                                                        </button>
                                                        <button class="btn btn-danger flex-fill"
                                                            @click="respondToRequest(request.id, 'rejected')"
                                                            :disabled="processing[request.id]">
                                                            <i class="bi bi-x-circle me-1"></i>
                                                            Reject
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </TabPanel>
                </TabPanels>
            </Tabs>

            <!-- ================== ADD/EDIT DISCOUNT MODAL ================== -->
            <div class="modal fade" id="discountModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content rounded-4">
                        <div class="modal-header">
                            <h5 class="modal-title fw-semibold">
                                {{ editingDiscount ? "Edit Discount" : "Add New Discount" }}
                            </h5>
                            <button
                                class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                                @click="resetModal" data-bs-dismiss="modal" aria-label="Close" title="Close">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

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
                                    <Select v-model="discountForm.type" :options="discountOptions" optionLabel="label"
                                        appendTo="self" :autoZIndex="true" :baseZIndex="2000" optionValue="value"
                                        class="form-select" :class="{ 'is-invalid': discountFormErrors.type }" />
                                    <small v-if="discountFormErrors.type" class="text-danger">
                                        {{ discountFormErrors.type[0] }}
                                    </small>
                                </div>

                                <!-- Status -->
                                <div class="col-md-6">
                                    <label class="form-label">Status *</label>
                                    <Select v-model="discountForm.status" :options="statusOptions" optionLabel="label"
                                        optionValue="value" class="form-select" appendTo="self" :autoZIndex="true"
                                        :baseZIndex="2000" :class="{ 'is-invalid': discountFormErrors.status }" />
                                    <small v-if="discountFormErrors.status" class="text-danger">
                                        {{ discountFormErrors.status[0] }}
                                    </small>
                                </div>

                                <!-- Discount Amount -->
                                <div class="col-12">
                                    <label class="form-label">Discount Amount *</label>
                                    <input v-model="discountForm.discount_amount" type="number" class="form-control"
                                        :class="{ 'is-invalid': discountFormErrors.discount_amount }"
                                        placeholder="Enter discount amount" />
                                    <small v-if="discountFormErrors.discount_amount" class="text-danger">
                                        {{ discountFormErrors.discount_amount[0] }}
                                    </small>
                                </div>

                                <!-- Start Date -->
                                <div class="col-md-6">
                                    <label class="form-label">Start Date *</label>
                                    <VueDatePicker v-model="discountForm.start_date" :format="dateFmt"
                                        :min-date="new Date()" :enableTimePicker="false" placeholder="Select start date"
                                        :class="{ 'is-invalid': discountFormErrors.start_date }" />
                                    <small v-if="discountFormErrors.start_date" class="text-danger">
                                        {{ discountFormErrors.start_date[0] }}
                                    </small>
                                </div>

                                <!-- End Date -->
                                <div class="col-md-6">
                                    <label class="form-label">End Date *</label>
                                    <VueDatePicker v-model="discountForm.end_date" :format="dateFmt"
                                        :min-date="new Date()" :enableTimePicker="false" placeholder="Select end date"
                                        :class="{ 'is-invalid': discountFormErrors.end_date }" />
                                    <small v-if="discountFormErrors.end_date" class="text-danger">
                                        {{ discountFormErrors.end_date[0] }}
                                    </small>
                                </div>

                                <!-- Minimum Purchase Amount -->
                                <div class="col-md-6">
                                    <label class="form-label">Minimum Purchase Amount *</label>
                                    <input v-model="discountForm.min_purchase" type="number" step="0.01"
                                        class="form-control" :class="{ 'is-invalid': discountFormErrors.min_purchase }"
                                        placeholder="0.00" />
                                    <small v-if="discountFormErrors.min_purchase" class="text-danger">
                                        {{ discountFormErrors.min_purchase[0] }}
                                    </small>
                                </div>

                                <!-- Maximum Discount (Optional) -->
                                <div class="col-md-6">
                                    <label class="form-label">Maximum Discount (Optional)</label>
                                    <input v-model="discountForm.max_discount" type="number" step="0.01"
                                        class="form-control" :class="{ 'is-invalid': discountFormErrors.max_discount }"
                                        placeholder="0.00" />
                                    <small v-if="discountFormErrors.max_discount" class="text-danger">
                                        {{ discountFormErrors.max_discount[0] }}
                                    </small>
                                </div>

                                <!-- Description (Optional) -->
                                <div class="col-12">
                                    <label class="form-label">Description (Optional)</label>
                                    <textarea v-model="discountForm.description" class="form-control" rows="3"
                                        :class="{ 'is-invalid': discountFormErrors.description }"
                                        placeholder="Enter discount description"></textarea>
                                    <small v-if="discountFormErrors.description" class="text-danger">
                                        {{ discountFormErrors.description[0] }}
                                    </small>
                                </div>
                            </div>

                            <hr class="my-4" />

                            <!-- Modal Footer -->
                            <div class="mt-4">
                                <button class="btn btn-primary rounded-pill px-4" :disabled="submitting"
                                    @click="submitDiscount()">
                                    <template v-if="submitting">
                                        <span class="spinner-border spinner-border-sm me-2"></span>
                                        Saving...
                                    </template>
                                    <template v-else>
                                        {{ editingDiscount ? "Save" : "Save" }}
                                    </template>
                                </button>

                                <button class="btn btn-secondary rounded-pill px-4 ms-2" data-bs-dismiss="modal"
                                    @click="resetModal">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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

/* ======================== Tabs Styling ======================== */
.p-tabpanels {
    background-color: #fff !important;
}

:global(.dark .p-tabpanels) {
    background-color: #222 !important;
}

:global(.dark .p-tablist-tab-list) {
    background: #212121 !important;
}

:global(.dark .p-tablist-content) {
    background: #212121 !important;
    color: #fff !important;
}

.p-tab {
    color: #000 !important;
}

:global(.dark .p-tab) {
    color: #fff !important;
}

.p-tablist-active-bar {
    background-color: #1c0d82;
}

/* ======================== Approval Cards Styling ======================== */
.card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1) !important;
}

.list-group-item {
    background: transparent;
    border: none;
    border-bottom: 1px solid #f0f0f0;
}

.list-group-item:last-child {
    border-bottom: none;
}

:global(.dark .list-group-item) {
    border-bottom-color: #333;
}
</style>