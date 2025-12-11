<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted, watch } from "vue";
import Select from "primevue/select";
import MultiSelect from "primevue/multiselect";
import { toast } from "vue3-toastify";
import { useFormatters } from '@/composables/useFormatters';
import FilterModal from "@/Components/FilterModal.vue";
import ImageZoomModal from "@/Components/ImageZoomModal.vue";
import ImageCropperModal from "@/Components/ImageCropperModal.vue";
import ConfirmModal from "@/Components/ConfirmModal.vue";
import { Head } from "@inertiajs/vue3";
import { Package, XCircle, Plus, Pencil, CheckCircle } from "lucide-vue-next";
import Pagination from "@/Components/Pagination.vue";

const { formatMoney, formatCurrencySymbol, formatNumber, dateFmt } = useFormatters();

const props = defineProps({
    menuItems: {
        type: Array,
        default: () => []
    }
});

// State Management
const deals = ref([]);
const currentPage = ref(1);
const perPage = ref(10);
const totalItems = ref(0);
const paginationLinks = ref([]);
const isLoading = ref(false);
const counts = ref({
    total: 0,
    active: 0,
    inactive: 0,
});

const form = ref({
    name: "",
    price: "",
    menu_item_ids: [],
    description: "",
    imageFile: null,
    imageUrl: null,
    status: 1,
});

const formErrors = ref({});
const submitting = ref(false);
const isEditMode = ref(false);
const showCropper = ref(false);
const showImageModal = ref(false);
const previewImage = ref(null);

// Search and Filter
const q = ref("");
const searchKey = ref(Date.now());
const inputId = `search-${Math.random().toString(36).substr(2, 9)}`;
const isReady = ref(false);

const defaultFilters = {
    sortBy: "",
    status: "",
    priceMin: null,
    priceMax: null,
};

const filters = ref({ ...defaultFilters });
const appliedFilters = ref({ ...defaultFilters });

// Fetch Deals
const fetchDeals = async (page = 1) => {
    isLoading.value = true;
    try {
        const res = await axios.get("/api/deals", {
            params: {
                page: page,
                per_page: perPage.value,
                search: q.value.trim() || null,
                status: filters.value.status !== "" ? filters.value.status : null,
                sort_by: filters.value.sortBy || null,
                price_min: filters.value.priceMin || null,
                price_max: filters.value.priceMax || null,
            }
        });

        deals.value = res.data.data || [];

        if (res.data.pagination) {
            currentPage.value = res.data.pagination.current_page;
            totalItems.value = res.data.pagination.total;
            paginationLinks.value = res.data.pagination.links;
        }

        if (res.data.counts) {
            counts.value = res.data.counts;
        }

    } catch (err) {
        console.error("❌ Error fetching deals:", err);
        toast.error("Failed to fetch deals");
    } finally {
        isLoading.value = false;
    }
};

const handlePageChange = (url) => {
    if (!url || isLoading.value) return;

    const urlParams = new URLSearchParams(url.split('?')[1]);
    const page = urlParams.get('page');

    if (page) {
        fetchDeals(parseInt(page));
    }
};

// KPIs
const kpis = computed(() => [
    {
        label: "Total Deals",
        value: counts.value.total ?? 0,
        icon: Package,
        iconBg: "bg-soft-success",
        iconColor: "text-success",
    },
    {
        label: "Active Deals",
        value: counts.value.active ?? 0,
        icon: CheckCircle,
        iconBg: "bg-soft-danger",
        iconColor: "text-success",
    },
    {
        label: "Inactive Deals",
        value: counts.value.inactive ?? 0,
        icon: XCircle,
        iconBg: "bg-soft-warning",
        iconColor: "text-danger",
    },
]);

const filterOptions = computed(() => ({
    sortOptions: [
        { value: "price_desc", label: "Price: High to Low" },
        { value: "price_asc", label: "Price: Low to High" },
        { value: "name_asc", label: "Name: A to Z" },
        { value: "name_desc", label: "Name: Z to A" },
    ],
    statusOptions: [
        { value: 1, label: "Active" },
        { value: 0, label: "Inactive" },
    ],
}));

const handleFilterClear = () => {
    filters.value = { ...defaultFilters };
    appliedFilters.value = { ...defaultFilters };
};

const handleFilterApply = () => {
    appliedFilters.value = { ...filters.value };
};

// Image Handling
function openImageModal(src) {
    previewImage.value = src || form.value.imageUrl;
    if (!previewImage.value) return;
    showImageModal.value = true;
}

function onCropped({ file }) {
    form.value.imageFile = file;
    form.value.imageUrl = URL.createObjectURL(file);
}

// Form Submission
const submitDeal = async () => {
    submitting.value = true;
    formErrors.value = {};

    try {
        const formData = new FormData();
        formData.append("name", form.value.name.trim());
        formData.append("price", form.value.price);
        formData.append("description", form.value.description || "");
        formData.append("status", form.value.status);

        form.value.menu_item_ids.forEach((id, index) => {
            formData.append(`menu_item_ids[${index}]`, id);
        });

        if (form.value.imageFile) {
            formData.append("image", form.value.imageFile);
        }

        await axios.post("/deals", formData, {
            headers: { "Content-Type": "multipart/form-data" },
        });

        toast.success("Deal created successfully");
        resetForm();
        await fetchDeals();
        bootstrap.Modal.getInstance(document.getElementById("addDealModal"))?.hide();
    } catch (err) {
        console.error("❌ Error saving:", err.response?.data);

        if (err?.response?.status === 422 && err.response.data?.errors) {
            formErrors.value = err.response.data.errors;
            const errorMessages = Object.values(err.response.data.errors)
                .flat()
                .join("\n");
            toast.error(errorMessages || "Validation failed");
        } else {
            toast.error("Failed to save deal: " + (err.response?.data?.message || err.message));
        }
    } finally {
        submitting.value = false;
    }
};

const submitEdit = async () => {
    submitting.value = true;
    formErrors.value = {};

    try {
        const formData = new FormData();
        formData.append("name", form.value.name.trim());
        formData.append("price", form.value.price);
        formData.append("description", form.value.description || "");
        formData.append("status", form.value.status);

        form.value.menu_item_ids.forEach((id, index) => {
            formData.append(`menu_item_ids[${index}]`, id);
        });

        if (form.value.imageFile) {
            formData.append("image", form.value.imageFile);
        }

        formData.append("_method", "PUT");

        await axios.post(`/deals/${form.value.id}`, formData, {
            headers: { "Content-Type": "multipart/form-data" },
        });

        toast.success("Deal updated successfully");
        resetForm();
        await fetchDeals();

        const modal = bootstrap.Modal.getInstance(document.getElementById("addDealModal"));
        modal?.hide();
    } catch (err) {
        console.error("❌ Update failed:", err.response?.data);

        if (err?.response?.status === 422 && err.response.data?.errors) {
            formErrors.value = err.response.data.errors;
            const errorMessages = Object.values(err.response.data.errors)
                .flat()
                .join("\n");
            toast.error(errorMessages || "Validation failed");
        } else {
            toast.error("Failed to update deal: " + (err.response?.data?.message || err.message));
        }
    } finally {
        submitting.value = false;
    }
};

// Edit Deal
const editDeal = (deal) => {
    if (form.value.imageUrl && form.value.imageUrl.startsWith("blob:")) {
        URL.revokeObjectURL(form.value.imageUrl);
    }

    isEditMode.value = true;

    form.value = {
        id: deal.id,
        name: deal.name,
        price: deal.price,
        description: deal.description || "",
        menu_item_ids: deal.menu_items?.map(item => item.id) || [],
        imageFile: null,
        imageUrl: deal.image_url || null,
        status: deal.status ?? 1,
    };

    const modal = new bootstrap.Modal(document.getElementById("addDealModal"));
    modal.show();
};

// Add this to your state management section
const confirmModalShow = ref({});

// Update toggleStatus function
const toggleStatus = async (deal) => {
    try {
        const newStatus = deal.status === 1 ? 0 : 1;
        const response = await axios.patch(`/deals/${deal.id}/status`, { status: newStatus });

        const dealIndex = deals.value.findIndex(d => d.id === deal.id);
        if (dealIndex !== -1) {
            deals.value[dealIndex].status = newStatus;
        }

        // Update counts
        if (newStatus === 1) {
            counts.value.active++;
            counts.value.inactive--;
        } else {
            counts.value.active--;
            counts.value.inactive++;
        }

        toast.success(`Deal is now ${newStatus === 1 ? "Active" : "Inactive"}`);

        // Close the modal
        confirmModalShow.value[deal.id] = false;
    } catch (err) {
        console.error("Failed to toggle status", err);
        toast.error("Failed to update status");
        await fetchDeals(currentPage.value);

        // Close the modal even on error
        confirmModalShow.value[deal.id] = false;
    }
};

// Reset Form
function resetForm() {
    if (form.value.imageUrl && form.value.imageUrl.startsWith("blob:")) {
        URL.revokeObjectURL(form.value.imageUrl);
    }

    form.value = {
        name: "",
        price: "",
        menu_item_ids: [],
        description: "",
        imageFile: null,
        imageUrl: null,
        status: 1,
    };

    showCropper.value = false;
    showImageModal.value = false;
    previewImage.value = null;
    formErrors.value = {};
    isEditMode.value = false;
}

const openAddDealModal = () => {
    resetForm();
};

// Watchers
watch([filters, q], () => {
    fetchDeals(1);
}, { deep: true });

watch(
    form,
    (newVal) => {
        Object.keys(formErrors.value).forEach((key) => {
            const value = newVal[key];
            if (
                value !== null &&
                value !== undefined &&
                value !== "" &&
                (!Array.isArray(value) || value.length > 0)
            ) {
                delete formErrors.value[key];
            }
        });
    },
    { deep: true }
);

// Lifecycle
onMounted(async () => {
    q.value = "";
    searchKey.value = Date.now();

    setTimeout(() => {
        isReady.value = true;
        const input = document.getElementById(inputId);
        if (input) {
            input.value = '';
            q.value = '';
        }
    }, 100);

    await fetchDeals();

    const filterModal = document.getElementById('dealFilterModal');
    if (filterModal) {
        filterModal.addEventListener('hidden.bs.modal', () => {
            filters.value = { ...appliedFilters.value };
        });
    }
});
</script>

<template>
    <Master>

        <Head title="Deals" />
        <div class="page-wrapper">
            <h4 class="mb-3">Deals Management</h4>

            <!-- KPI Cards -->
            <div class="row g-3">
                <div v-for="c in kpis" :key="c.label" class="col-md-6 col-xl-4">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <div class="fw-bold fs-4">{{ c.value }}</div>
                                <div class="text-muted fs-6">{{ c.label }}</div>
                            </div>
                            <div :class="['icon-chip', c.iconBg]">
                                <component :is="c.icon" :class="c.iconColor" size="26" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Table -->
            <div class="card border-0 shadow-lg rounded-4 mt-4">
                <div class="card-body">
                    <!-- Toolbar -->
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">Deals</h4>

                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <div class="search-wrap">
                                <i class="bi bi-search"></i>
                                <input v-if="isReady" :id="inputId" v-model="q" :key="searchKey"
                                    class="form-control search-input" placeholder="Search" type="search"
                                    autocomplete="new-password" :name="inputId" />
                                <input v-else class="form-control search-input" placeholder="Search" disabled
                                    type="text" />
                            </div>

                            <!-- Filter -->
                            <FilterModal v-model="filters" title="Deals" modal-id="dealFilterModal"
                                modal-size="modal-lg" :sort-options="filterOptions.sortOptions"
                                :status-options="filterOptions.statusOptions" :show-price-range="true"
                                :show-date-range="false" :show-category="false" :show-stock-status="false"
                                @apply="handleFilterApply" @clear="handleFilterClear" />

                            <!-- Add Deal -->
                            <button data-bs-toggle="modal" @click="openAddDealModal" data-bs-target="#addDealModal"
                                class="d-flex align-items-center gap-1 px-4 btn-sm py-2 rounded-pill btn btn-primary text-white">
                                <Plus class="w-4 h-4" /> Add Deal
                            </button>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="border-top small text-muted">
                                <tr>
                                    <th>S.#</th>
                                    <th>Image</th>
                                    <th>Deal Name</th>
                                    <th>Price</th>
                                    <th>Menus</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="isLoading">
                                    <td colspan="7" class="text-center py-5">
                                        <div class="d-flex flex-column align-items-center justify-content-center">
                                            <div class="spinner-border text-primary mb-3" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <p class="text-muted mb-0">Loading deals...</p>
                                        </div>
                                    </td>
                                </tr>

                                <template v-else>
                                    <tr v-for="(deal, idx) in deals" :key="deal.id">
                                        <td>{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                                        <td>
                                            <ImageZoomModal v-if="deal.image_url" :file="deal.image_url"
                                                :alt="deal.name" :width="50" :height="50"
                                                :custom_class="'cursor-pointer'" />
                                        </td>
                                        <td class="fw-semibold">{{ deal.name }}</td>
                                        <td>{{ formatCurrencySymbol(deal.price || 0, "GBP") }}</td>
                                        <td>
                                            {{(deal.menu_items || [])
                                            .map(item => item.name)
                                            .join(", ") }}
                                        </td>

                                        <td>
                                            <span v-if="deal.status === 0"
                                                class="badge bg-red-600 rounded-pill d-inline-block text-center px-3 py-1">Inactive</span>
                                            <span v-else
                                                class="badge bg-success rounded-pill d-inline-block text-center px-3 py-1">Active</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-inline-flex align-items-center gap-3">
                                                <button @click="editDeal(deal)" data-bs-toggle="modal" title="Edit"
                                                    class="p-2 rounded-full text-blue-600 hover:bg-blue-100">
                                                    <Pencil class="w-4 h-4" />
                                                </button>

                                                <!-- Status Toggle with Confirmation -->
                                                <!-- In your Deals component template -->
                                                <ConfirmModal :show="confirmModalShow[deal.id]"
                                                    @update:show="confirmModalShow[deal.id] = $event"
                                                    :title="'Confirm Status Change'"
                                                    :message="`Are you sure you want to ${deal.status === 1 ? 'deactivate' : 'activate'} this deal?`"
                                                    :showStatusButton="true"
                                                    :status="deal.status === 1 ? 'active' : 'inactive'"
                                                    @confirm="() => toggleStatus(deal)">
                                                    <template #trigger>
                                                        <button @click="confirmModalShow[deal.id] = true"
                                                            class="relative inline-flex items-center w-8 h-4 rounded-full transition-colors duration-300 focus:outline-none"
                                                            :class="deal.status === 1 ? 'bg-green-500 hover:bg-green-600' : 'bg-red-400 hover:bg-red-500'"
                                                            :title="deal.status === 1 ? 'Set Inactive' : 'Set Active'">
                                                            <span
                                                                class="absolute left-0.5 top-0.5 w-3 h-3 bg-white rounded-full shadow transform transition-transform duration-300"
                                                                :class="deal.status === 1 ? 'translate-x-4' : 'translate-x-0'"></span>
                                                        </button>
                                                    </template>
                                                </ConfirmModal>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr v-if="deals.length === 0">
                                        <td colspan="7" class="text-center text-muted py-4">
                                            No deals found.
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="paginationLinks.length > 0 && !isLoading" class="mt-4">
                        <Pagination :pagination="paginationLinks" :isApiDriven="true"
                            @page-changed="handlePageChange" />
                        <div class="text-center mt-3 text-sm text-gray-600">
                            Showing {{ (currentPage - 1) * perPage + 1 }} to
                            {{ Math.min(currentPage * perPage, totalItems) }} of {{ totalItems }} results
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add/Edit Deal Modal -->
            <div class="modal fade" id="addDealModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content rounded-4">
                        <div class="modal-header">
                            <h5 class="modal-title fw-semibold">
                                {{ isEditMode ? "Edit Deal" : "Add Deal" }}
                            </h5>
                            <button @click="resetForm"
                                class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                                data-bs-dismiss="modal" aria-label="Close" title="Close">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Deal Name</label>
                                    <input v-model="form.name" type="text" class="form-control"
                                        :class="{ 'is-invalid': formErrors.name }" placeholder="e.g., Family Deal" />
                                    <small v-if="formErrors.name" class="text-danger">
                                        {{ formErrors.name[0] }}
                                    </small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Price</label>
                                    <input v-model="form.price" type="number" min="0" step="0.01" class="form-control"
                                        :class="{ 'is-invalid': formErrors.price }" placeholder="e.g., 29.99" />
                                    <small v-if="formErrors.price" class="text-danger">
                                        {{ formErrors.price[0] }}
                                    </small>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label">Select Menu Items</label>
                                    <MultiSelect v-model="form.menu_item_ids" :options="menuItems" optionLabel="name"
                                        optionValue="id" filter placeholder="Select Menu Items" class="w-full"
                                        appendTo="self" :autoZIndex="true" :baseZIndex="2000"
                                        :class="{ 'is-invalid': formErrors.menu_item_ids }">
                                        <template #option="slotProps">
                                            <div class="d-flex justify-content-between align-items-center w-100">
                                                <span>{{ slotProps.option.name }}</span>
                                                <span class="badge bg-primary">{{
                                                    formatCurrencySymbol(slotProps.option.price) }}</span>
                                            </div>
                                        </template>
                                    </MultiSelect>
                                    <small v-if="formErrors.menu_item_ids" class="text-danger">
                                        {{ formErrors.menu_item_ids[0] }}
                                    </small>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Description</label>
                                    <textarea v-model="form.description" rows="4" class="form-control"
                                        :class="{ 'is-invalid': formErrors.description }"
                                        placeholder="Deal description"></textarea>
                                    <small v-if="formErrors.description" class="text-danger">
                                        {{ formErrors.description[0] }}
                                    </small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Deal Image</label>
                                    <div class="logo-card" :class="{ 'is-invalid': formErrors.image }">
                                        <div class="logo-frame"
                                            @click="form.imageUrl ? openImageModal() : (showCropper = true)">
                                            <img v-if="form.imageUrl" :src="form.imageUrl" alt="Deal Image" />
                                            <div v-else class="placeholder">
                                                <i class="bi bi-image"></i>
                                            </div>
                                        </div>
                                        <small class="text-muted mt-2 d-block">Upload Image</small>
                                        <ImageCropperModal :show="showCropper" @close="showCropper = false"
                                            @cropped="onCropped" />
                                        <ImageZoomModal v-if="showImageModal" :show="showImageModal"
                                            :image="previewImage" @close="showImageModal = false" />
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button class="btn btn-primary rounded-pill btn-sm px-5 py-2" :disabled="submitting"
                                    @click="form.id ? submitEdit() : submitDeal()">
                                    <template v-if="submitting">
                                        <span class="spinner-border spinner-border-sm me-2"></span>
                                        Saving...
                                    </template>
                                    <template v-else>
                                        Save
                                    </template>
                                </button>

                                <button class="btn btn-secondary btn-sm rounded-pill py-2 px-4 ms-2"
                                    data-bs-dismiss="modal" @click="resetForm">
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
.dark h4 {
    color: white;
}

.dark .bg-light {
    background-color: #212121 !important;
    color: #fff !important;
}

.dark .card {
    background-color: #181818 !important;
    color: #ffffff !important;
}

.dark .table {
    background-color: #181818 !important;
    color: #f9fafb !important;
}

.dark .table thead {
    background-color: #181818 !important;
    color: #ffffff;
}

.dark .table thead th {
    background-color: #181818 !important;
    color: #ffffff;
}

:root {
    --brand: #1c0d82;
}

.icon-wrap {
    font-size: 2rem;
    color: var(--brand);
}

.kpi-label {
    font-size: 0.95rem;
}

.kpi-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: #181818;
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
    font-size: 1rem;
}

.search-input {
    padding-left: 38px;
    border-radius: 9999px;
    background: #fff;
}

.btn-primary {
    background-color: var(--brand);
    border-color: var(--brand);
}

.btn-primary:hover {
    filter: brightness(1.05);
}

.table thead th {
    font-weight: 600;
}

.table tbody td {
    vertical-align: middle;
}

.logo-card {
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 0.5rem;
    border: 2px dashed #cbd5e1;
}

.logo-frame {
    width: 100%;
    height: 160px;
    background: #ffffff;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    overflow: hidden;
}

.logo-frame img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.logo-frame .placeholder {
    font-size: 3rem;
    color: #cbd5e1;
}

.dark .logo-card {
    background-color: #181818 !important;
}

.dark .logo-frame {
    background-color: #212121 !important;
}

.dark .form-control {
    background-color: #212121 !important;
    color: #fff !important;
}

/* Status Toggle Styles */
.bg-green-500 {
    background-color: #22c55e !important;
}

.bg-green-600 {
    background-color: #16a34a !important;
}

.bg-red-400 {
    background-color: #f87171 !important;
}

.bg-red-500 {
    background-color: #ef4444 !important;
}

.translate-x-0 {
    transform: translateX(0) !important;
}

.translate-x-4 {
    transform: translateX(1rem) !important;
}

.rounded-full {
    border-radius: 9999px !important;
}

.w-8 {
    width: 2rem !important;
}

.h-4 {
    height: 1rem !important;
}

.w-3 {
    width: 0.75rem !important;
}

.h-3 {
    height: 0.75rem !important;
}

.left-0\.5 {
    left: 0.125rem !important;
}

.top-0\.5 {
    top: 0.125rem !important;
}

:deep(.p-multiselect-panel),
:deep(.p-select-panel) {
    z-index: 2000 !important;
}

@media (max-width: 575.98px) {
    .kpi-value {
        font-size: 1.45rem;
    }

    .search-wrap {
        width: 100%;
    }
}
</style>