<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted, onUpdated, watch } from "vue";
import { Percent, Calendar, AlertTriangle, XCircle, Pencil, Plus, CheckCircle } from "lucide-vue-next";
import { toast } from "vue3-toastify";
import axios from "axios";
import Select from "primevue/select";
import ConfirmModal from "@/Components/ConfirmModal.vue";
import { useFormatters } from '@/composables/useFormatters'
import { nextTick } from "vue";
import { Head } from "@inertiajs/vue3";
import MultiSelect from 'primevue/multiselect';

import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';

const { formatMoney, formatCurrencySymbol, formatNumber, dateFmt } = useFormatters()

const props = defineProps({
    meals: {
        type: Array,
        required: true,
    },
});

// Don't override props with ref - use computed or direct reference
const mealsData = computed(() => props.meals || []);

// Flatten all menu items for the menu items MultiSelect
const menuItemsData = computed(() => {
    if (!props.meals || !Array.isArray(props.meals)) return [];

    const items = props.meals.flatMap(meal =>
        (meal.menu_items || []).map(item => ({
            ...item,
            meal_name: meal.name
        }))
    );

    // Remove duplicates based on item.id
    const uniqueItems = [];
    const seenIds = new Set();

    for (const item of items) {
        if (!seenIds.has(item.id)) {
            seenIds.add(item.id);
            uniqueItems.push(item);
        }
    }

    return uniqueItems;
});


/* ---------------- Data ---------------- */
const promos = ref([]);
const editingPromo = ref(null);
const submitting = ref(false);
const promoFormErrors = ref({});

// Promo Scope
const promoScopes = ref([]);
const editingPromoScope = ref(null);
const promoScopeFormErrors = ref({});

const discountOptions = [
    { label: "Flat Amount", value: "flat" },
    { label: "Percentage", value: "percent" },
];

const statusOptions = [
    { label: "Active", value: "active" },
    { label: "Deactive", value: "inactive" },
];

// reactive for form
const promoScopeForm = ref({
    promo_id: null,
    meals: [],
    menu_items: [],
});

// Debug: Watch the form values
watch(() => promoScopeForm.value.meals, (newVal) => {
    console.log('Selected meals changed:', newVal);
}, { deep: true });

watch(() => promoScopeForm.value.menu_items, (newVal) => {
    console.log('Selected menu items changed:', newVal);
}, { deep: true });

/* ---------------- Fetch Promos ---------------- */
const fetchPromos = async () => {
    try {
        const res = await axios.get("/api/promos/all");
        promos.value = res.data.data;
        console.log('Fetched promos:', promos.value);
    } catch (err) {
        console.error("Failed to fetch promos:", err);
        toast.error("Failed to load promos");
    }
};

/* ---------------- Fetch Promo Scopes ---------------- */
const fetchPromoScopes = async () => {
    try {
        const res = await axios.get("/promo-scopes");
        promoScopes.value = res.data.data || [];
        console.log('Fetched promo scopes:', promoScopes.value);
    } catch (err) {
        console.error("Failed to fetch promo scopes:", err);
        toast.error("Failed to load promo scopes");
    }
};

onMounted(async () => {
    q.value = "";
    searchKey.value = Date.now();
    await nextTick();

    // Delay to prevent autofill
    setTimeout(() => {
        isReady.value = true;

        // Force clear any autofill that happened
        const input = document.getElementById(inputId);
        if (input) {
            input.value = '';
            q.value = '';
        }
    }, 100);

    await fetchPromos();
    await fetchPromoScopes();

    // Debug log
    console.log('Props meals on mount:', props.meals);
    console.log('Computed mealsData:', mealsData.value);
    console.log('Computed menuItemsData:', menuItemsData.value);
});

/* ---------------- KPI Cards ---------------- */
const promoStats = computed(() => [
    {
        label: "Total Promos",
        value: promos.value.length,
        icon: Percent,
        iconBg: "bg-light-primary",
        iconColor: "text-primary",
    },
    {
        label: "Active Promos",
        value: promos.value.filter((p) => p.status === "active").length,
        icon: Calendar,
        iconBg: "bg-light-success",
        iconColor: "text-success",
    },
    {
        label: "Flat Discount",
        value: promos.value.filter((p) => p.type === "flat").length,
        icon: AlertTriangle,
        iconBg: "bg-light-warning",
        iconColor: "text-warning",
    },
    {
        label: "Percentage",
        value: promos.value.filter((p) => p.type === "percent").length,
        icon: XCircle,
        iconBg: "bg-light-danger",
        iconColor: "text-danger",
    },
]);

/* ---------------- Search ---------------- */
const q = ref("");
const searchKey = ref(Date.now());
const inputId = `search-${Math.random().toString(36).substr(2, 9)}`;
const isReady = ref(false);

const filtered = computed(() => {
    const t = q.value.trim().toLowerCase();
    if (!t) return promos.value;
    return promos.value.filter((p) => p.name.toLowerCase().includes(t));
});

/* ---------------- Form State ---------------- */
const promoForm = ref({
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

const resetModal = () => {
    promoForm.value = {
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
    editingPromo.value = null;
    promoFormErrors.value = {};
};

const resetPromoScopeModal = () => {
    promoScopeForm.value = {
        promo_id: null,
        meals: [],
        menu_items: [],
    };
    editingPromoScope.value = null;
    promoScopeFormErrors.value = {};
};

/* ---------------- Submit Promo (Create/Update) ---------------- */
const submitPromo = async () => {
    submitting.value = true;
    promoFormErrors.value = {};

    try {
        if (editingPromo.value) {
            await axios.post(`/promos/${editingPromo.value.id}`, promoForm.value);
            toast.success("Promo updated successfully");
        } else {
            await axios.post("/promos", promoForm.value);
            toast.success("Promo created successfully");
        }

        const modal = bootstrap.Modal.getInstance(document.getElementById("promoModal"));
        modal?.hide();

        resetModal();
        await fetchPromos();
    } catch (err) {
        console.error("❌ Error:", err.response?.data || err.message);

        if (err.response?.status === 422 && err.response?.data?.errors) {
            promoFormErrors.value = err.response.data.errors;
            const errorMessages = Object.values(err.response.data.errors).flat();
            toast.error(errorMessages.join("\n"));
        } else {
            const errorMessage = err.response?.data?.message || "Failed to save promo";
            toast.error(errorMessage);
        }
    } finally {
        submitting.value = false;
    }
};

/* ---------------- Submit Promo Scope (Create/Update) ---------------- */
const submitPromoScope = async () => {
    submitting.value = true;
    promoScopeFormErrors.value = {};

    console.log('Submitting promo scope:', promoScopeForm.value);

    try {
        const payload = {
            promos: promoScopeForm.value.promos || [],
            meals: promoScopeForm.value.meals || [],
            menu_items: promoScopeForm.value.menu_items || [],
        };

        console.log('Payload:', payload);

        if (editingPromoScope.value) {
            await axios.put(`/promo-scopes/${editingPromoScope.value.id}`, payload);
            toast.success("Promo scope updated successfully");
        } else {
            await axios.post("/promo-scopes", payload);
            toast.success("Promo scope created successfully");
        }

        const modal = bootstrap.Modal.getInstance(document.getElementById("promoScopeModal"));
        modal?.hide();

        resetPromoScopeModal();
        await fetchPromoScopes();
    } catch (err) {
        console.error("❌ Error:", err.response?.data || err.message);

        if (err.response?.status === 422 && err.response?.data?.errors) {
            promoScopeFormErrors.value = err.response.data.errors;
            const errorMessages = Object.values(err.response.data.errors).flat();
            toast.error("Please filled all the required fields.");
        } else {
            const errorMessage = err.response?.data?.message || "Failed to save promo scope";
            toast.error(errorMessage);
        }
    } finally {
        submitting.value = false;
    }
};

/* ---------------- Edit Promo ---------------- */
const editRow = (row) => {
    editingPromo.value = row;
    promoForm.value = {
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

    const modalEl = document.getElementById("promoModal");
    const bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();
};

/* ---------------- Edit Promo Scope ---------------- */
const editPromoScope = (scope) => {
    editingPromoScope.value = scope;
    promoScopeForm.value = {
        promos: scope.promos ? scope.promos.map(p => p.id) : [],
        meals: scope.meals ? scope.meals.map(m => m.id) : [],
        menu_items: scope.menu_items ? scope.menu_items.map(m => m.id) : [],
    };

    const modalEl = document.getElementById("promoScopeModal");
    const bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();
};

/* ---------------- Toggle Status ---------------- */
const toggleStatus = async (row) => {
    const newStatus = row.status === "active" ? "inactive" : "active";

    try {
        await axios.patch(`/api/promos/${row.id}/toggle-status`, {
            status: newStatus,
        });
        row.status = newStatus;
        toast.success(`Promo status updated to ${newStatus}`);
    } catch (error) {
        console.error("Failed to update status:", error);
        toast.error("Failed to update status");
    }
};

/* ---------------- Helpers ---------------- */
const money = (n, currency = "GBP") =>
    new Intl.NumberFormat("en-GB", { style: "currency", currency }).format(n);

const formatDate = (date) => {
    if (!date) return "N/A";
    return new Date(date).toLocaleDateString("en-GB");
};

onUpdated(() => window.feather?.replace());
</script>

<template>
    <Master>

        <Head title="Promo" />
        <div class="page-wrapper">
            <h4 class="fw-semibold mb-3">Promo Management</h4>

            <Tabs value="0" class="w-100">
                <!-- ====== TAB HEADERS ====== -->
                <TabList>
                    <Tab value="0">Promos</Tab>
                    <Tab value="1">Promo Scope</Tab>
                </TabList>

                <!-- ====== TAB PANELS ====== -->
                <TabPanels>
                    <!-- === PROMOS TAB === -->
                    <TabPanel value="0">
                        <!-- === PROMOS TAB === -->
                        <TabPanel value="0">
                            <div class="mt-3">
                                <!-- KPI Cards -->
                                <div class="row g-3">
                                    <div v-for="stat in promoStats" :key="stat.label" class="col-md-6 col-xl-3">
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

                                <!-- Table card -->
                                <div class="card border-0 shadow-lg rounded-4 mt-0">
                                    <div class="card-body">
                                        <!-- Toolbar -->
                                        <div
                                            class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                                            <h5 class="mb-0 fw-semibold">Promos</h5>

                                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                                <div class="search-wrap">
                                                    <i class="bi bi-search"></i>
                                                    <input type="email" name="email" autocomplete="email"
                                                        style="position: absolute; left: -9999px; width: 1px; height: 1px;"
                                                        tabindex="-1" aria-hidden="true" />

                                                    <input v-if="isReady" :id="inputId" v-model="q" :key="searchKey"
                                                        class="form-control search-input" placeholder="Search"
                                                        type="search" autocomplete="new-password" :name="inputId"
                                                        role="presentation" @focus="handleFocus" />
                                                    <input v-else class="form-control search-input" placeholder="Search"
                                                        disabled type="text" />
                                                </div>

                                                <button data-bs-toggle="modal" data-bs-target="#promoModal"
                                                    @click="resetModal"
                                                    class="d-flex align-items-center gap-1 px-4 py-2 rounded-pill btn btn-primary text-white">
                                                    <Plus class="w-4 h-4" /> Add Promo
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
                                                        <th>Type</th>
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
                                                        <td>{{ dateFmt(row.start_date) }}</td>
                                                        <td>{{ dateFmt(row.end_date) }}</td>
                                                        <td>{{ formatCurrencySymbol(row.min_purchase) }}</td>
                                                        <td>
                                                            {{ row.max_discount ? formatCurrencySymbol(row.max_discount)
                                                                :
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
                                                            </div>
                                                        </td>
                                                    </tr>

                                                    <tr v-if="filtered.length === 0">
                                                        <td colspan="9" class="text-center text-muted py-4">
                                                            No promos found.
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- ================== Add/Edit Promo Modal ================== -->
                                <div class="modal fade" id="promoModal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg modal-dialog-centered">
                                        <div class="modal-content rounded-4">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-semibold">
                                                    {{ editingPromo ? "Edit Promo" : "Add New Promo" }}
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

                                            <div class="modal-body">
                                                <div class="row g-3">
                                                    <!-- Name -->
                                                    <div class="col-12">
                                                        <label class="form-label">Promo Name</label>
                                                        <input v-model="promoForm.name" type="text" class="form-control"
                                                            :class="{
                                                                'is-invalid': promoFormErrors.name,
                                                            }" placeholder="Enter promo name" />
                                                        <small v-if="promoFormErrors.name" class="text-danger">
                                                            {{ promoFormErrors.name[0] }}
                                                        </small>
                                                    </div>

                                                    <!-- Type -->
                                                    <div class="col-md-6">
                                                        <label class="form-label">Discount Type</label>
                                                        <Select v-model="promoForm.type" :options="discountOptions"
                                                            optionLabel="label" appendTo="self" :autoZIndex="true"
                                                            :baseZIndex="2000" optionValue="value" class="form-select"
                                                            :class="{ 'is-invalid': promoFormErrors.type }" />
                                                        <small v-if="promoFormErrors.type" class="text-danger">
                                                            {{ promoFormErrors.type[0] }}
                                                        </small>
                                                    </div>


                                                    <!-- Status -->
                                                    <div class="col-md-6">
                                                        <label class="form-label">Status</label>
                                                        <Select v-model="promoForm.status" :options="statusOptions"
                                                            optionLabel="label" optionValue="value" class="form-select"
                                                            appendTo="self" :autoZIndex="true" :baseZIndex="2000"
                                                            :class="{ 'is-invalid': promoFormErrors.status }">
                                                        </Select>
                                                        <small v-if="promoFormErrors.status" class="text-danger">
                                                            {{ promoFormErrors.status[0] }}
                                                        </small>
                                                    </div>
                                                    <!-- Discount Amount -->
                                                    <div class="col-12">
                                                        <label class="form-label">Discount Amount</label>
                                                        <input v-model="promoForm.discount_amount" type="number"
                                                            class="form-control" :class="{
                                                                'is-invalid': promoFormErrors.discount_amount,
                                                            }" placeholder="Enter discount amount" />
                                                        <small v-if="promoFormErrors.discount_amount"
                                                            class="text-danger">
                                                            {{ promoFormErrors.discount_amount[0] }}
                                                        </small>
                                                    </div>
                                                    <small v-if="promoFormErrors.name" class="text-danger">
                                                        {{ promoFormErrors.name[0] }}
                                                    </small>
                                                    <!-- Start Date -->
                                                    <div class="col-md-6">
                                                        <label class="form-label">Start Date</label>

                                                        <VueDatePicker v-model="promoForm.start_date" :format="dateFmt"
                                                            :min-date="new Date()" :enableTimePicker="false"
                                                            placeholder="Select Start date" :class="{
                                                                'is-invalid': promoFormErrors.start_date,
                                                            }" />
                                                        <small v-if="promoFormErrors.start_date" class="text-danger">
                                                            {{ promoFormErrors.start_date[0] }}
                                                        </small>
                                                    </div>

                                                    <!-- End Date -->
                                                    <div class="col-md-6">
                                                        <label class="form-label">End Date</label>
                                                        <VueDatePicker v-model="promoForm.end_date" :format="dateFmt"
                                                            :min-date="new Date()" :enableTimePicker="false"
                                                            placeholder="Select End date" :class="{
                                                                'is-invalid': promoFormErrors.end_date,
                                                            }" />
                                                        <small v-if="promoFormErrors.end_date" class="text-danger">
                                                            {{ promoFormErrors.end_date[0] }}
                                                        </small>
                                                    </div>

                                                    <!-- Min Purchase -->
                                                    <div class="col-md-6">
                                                        <label class="form-label">Minimum Purchase Amount</label>
                                                        <input v-model="promoForm.min_purchase" type="number"
                                                            step="0.01" class="form-control" :class="{
                                                                'is-invalid': promoFormErrors.min_purchase,
                                                            }" placeholder="0.00" />
                                                        <small v-if="promoFormErrors.min_purchase" class="text-danger">
                                                            {{ promoFormErrors.min_purchase[0] }}
                                                        </small>
                                                    </div>

                                                    <!-- Max Discount -->
                                                    <div class="col-md-6">
                                                        <label class="form-label">Maximum Discount (Optional)</label>
                                                        <input v-model="promoForm.max_discount" type="number"
                                                            step="0.01" class="form-control" :class="{
                                                                'is-invalid': promoFormErrors.max_discount,
                                                            }" placeholder="0.00" />
                                                        <small v-if="promoFormErrors.max_discount" class="text-danger">
                                                            {{ promoFormErrors.max_discount[0] }}
                                                        </small>
                                                    </div>

                                                    <!-- Description -->
                                                    <div class="col-12">
                                                        <label class="form-label">Description (Optional)</label>
                                                        <textarea v-model="promoForm.description" class="form-control"
                                                            rows="3" :class="{
                                                                'is-invalid': promoFormErrors.description,
                                                            }" placeholder="Enter promo description"></textarea>
                                                        <small v-if="promoFormErrors.description" class="text-danger">
                                                            {{ promoFormErrors.description[0] }}
                                                        </small>
                                                    </div>
                                                </div>

                                                <hr class="my-4" />

                                                <div class="mt-4">
                                                    <button class="btn btn-primary rounded-pill px-4"
                                                        :disabled="submitting" @click="submitPromo()">
                                                        <template v-if="submitting">
                                                            <span class="spinner-border spinner-border-sm me-2"></span>
                                                            Saving...
                                                        </template>
                                                        <template v-else>
                                                            {{ editingPromo ? "Save" : "Save" }}
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
                        </TabPanel>
                    </TabPanel>

                    <!-- === PROMO SCOPE TAB === -->
                    <TabPanel value="1">
                        <div class="mt-3">

                            <!-- Table card -->
                            <div class="card border-0 shadow-lg rounded-4 mt-0">
                                <div class="card-body">
                                    <!-- Toolbar -->
                                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0 fw-semibold">Promo Scope</h5>

                                        <div class="d-flex flex-wrap gap-2 align-items-center">
                                            <button data-bs-toggle="modal" data-bs-target="#promoScopeModal"
                                                @click="resetPromoScopeModal"
                                                class="d-flex align-items-center gap-1 px-4 py-2 rounded-pill btn btn-primary text-white">
                                                <Plus class="w-4 h-4" /> Add Promo Scope
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Table -->
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead class="border-top small text-muted">
                                                <tr>
                                                    <th>S.#</th>
                                                    <th>Promo Name</th>
                                                    <th>Meals</th>
                                                    <th>Menu Items</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(scope, i) in promoScopes" :key="scope.id">
                                                    <td>{{ i + 1 }}</td>
                                                    <td>
                                                        <span v-if="scope.promos && scope.promos.length > 0">
                                                            {{scope.promos.map(p => p.name).join(', ')}}
                                                        </span>
                                                        <span v-else class="text-muted">N/A</span>
                                                    </td>

                                                    <td>
                                                        <span v-if="scope.meals && scope.meals.length > 0">
                                                            {{scope.meals.map(m => m.name).join(', ')}}
                                                        </span>
                                                        <span v-else class="text-muted">None</span>
                                                    </td>
                                                    <td>
                                                        <span v-if="scope.menu_items && scope.menu_items.length > 0">
                                                            {{scope.menu_items.map(m => m.name).join(', ')}}
                                                        </span>
                                                        <span v-else class="text-muted">None</span>
                                                    </td>
                                                    <td class="text-center">
                                                        <button @click="editPromoScope(scope)" title="Edit"
                                                            class="p-2 rounded-full text-blue-600 hover:bg-blue-100">
                                                            <Pencil class="w-4 h-4" />
                                                        </button>
                                                    </td>
                                                </tr>

                                                <tr v-if="promoScopes.length === 0">
                                                    <td colspan="5" class="text-center text-muted py-4">
                                                        No promo scopes found.
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- ================== Add/Edit Promo Scope Modal ================== -->
                            <div class="modal fade" id="promoScopeModal" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <div class="modal-content rounded-4">
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-semibold">
                                                {{ editingPromoScope ? "Edit Promo Scope" : "Add New Promo Scope" }}
                                            </h5>

                                            <button
                                                class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                                                data-bs-dismiss="modal" aria-label="Close" title="Close"
                                                @click="resetPromoScopeModal">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>

                                        <div class="modal-body">


                                            <div class="row g-3">
                                                <!-- Select Promos (multiple) -->
                                                <div class="col-12">
                                                    <label class="form-label">Select Promos *</label>
                                                    <MultiSelect v-model="promoScopeForm.promos" :options="promos"
                                                        optionLabel="name" optionValue="id" placeholder="Select promos"
                                                        class="w-100" display="chip" appendTo="self" :autoZIndex="true"
                                                        :baseZIndex="2000" :filter="true"
                                                        :class="{ 'is-invalid': promoScopeFormErrors.promos }" />
                                                    <small v-if="promoScopeFormErrors.promos" class="text-danger">
                                                        {{ promoScopeFormErrors.promos[0] }}
                                                    </small>
                                                </div>


                                                <!-- Select Meals -->
                                                <div class="col-md-6">
                                                    <label class="form-label">Select Meals</label>
                                                    <MultiSelect v-model="promoScopeForm.meals" :options="mealsData"
                                                        optionLabel="name" optionValue="id" placeholder="Select meals"
                                                        class="w-100" display="chip" appendTo="self" :autoZIndex="true"
                                                        :baseZIndex="2000" :filter="true"
                                                        :class="{ 'is-invalid': promoScopeFormErrors.meals }" />
                                                    <small v-if="promoScopeFormErrors.meals" class="text-danger">
                                                        {{ promoScopeFormErrors.meals[0] }}
                                                    </small>

                                                </div>

                                                <!-- Select Menu Items -->
                                                <div class="col-md-6">
                                                    <label class="form-label">Select Menu Items</label>
                                                    <MultiSelect v-model="promoScopeForm.menu_items"
                                                        :options="menuItemsData" optionLabel="name" optionValue="id"
                                                        placeholder="Select menu items" class="w-100" display="chip"
                                                        appendTo="self" :autoZIndex="true" :baseZIndex="2000"
                                                        :filter="true"
                                                        :class="{ 'is-invalid': promoScopeFormErrors.menu_items }" />
                                                    <small v-if="promoScopeFormErrors.menu_items" class="text-danger">
                                                        {{ promoScopeFormErrors.menu_items[0] }}
                                                    </small>

                                                </div>
                                            </div>

                                            <hr class="my-4" />

                                            <div class="mt-4 text-end">
                                                <button class="btn btn-secondary rounded-pill px-4 me-2"
                                                    data-bs-dismiss="modal" @click="resetPromoScopeModal">
                                                    Cancel
                                                </button>
                                                <button class="btn btn-primary rounded-pill px-4" :disabled="submitting"
                                                    @click="submitPromoScope">
                                                    <template v-if="submitting">
                                                        <span class="spinner-border spinner-border-sm me-2"></span>
                                                        Saving...
                                                    </template>
                                                    <template v-else>
                                                        Save
                                                    </template>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- /modal -->
                        </div>
                    </TabPanel>
                </TabPanels>
            </Tabs>
        </div>
    </Master>
</template>

<style scoped>
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

.img-chip {
    width: 40px;
    height: 40px;
    background: #f8f9fa;
}


.dark .p-select {
    background-color: #121212 !important;
    color: #fff !important;
}

.dark .p-select-label {
    color: #fff !important;
}

.dark .p-select-list {
    background-color: #121212 !important;
    color: #fff !important;
}

/* keep PrimeVue overlays above Bootstrap modal/backdrop */
:deep(.p-multiselect-panel),
:deep(.p-select-panel),
:deep(.p-dropdown-panel) {
    z-index: 2000 !important;
}



/* ========================  MultiSelect Styling   ============================= */
:deep(.p-multiselect-header) {
    background-color: white !important;
    color: black !important;

}

:deep(.p-multiselect-label) {
    color: #000 !important;
}

:deep(.p-select .p-component .p-inputwrapper) {
    background: #fff !important;
    color: #000 !important;
    border-bottom: 1px solid #ddd;
}

/* Options list container */
:deep(.p-multiselect-list) {
    background: #fff !important;
}

/* Each option */
:deep(.p-multiselect-option) {
    background: #fff !important;
    color: #000 !important;
}

/* Hover/selected option */
:deep(.p-multiselect-option.p-highlight) {
    background: #f0f0f0 !important;
    color: #000 !important;
}

:deep(.p-multiselect),
:deep(.p-multiselect-panel),
:deep(.p-multiselect-token) {
    background: #fff !important;
    color: #000 !important;
    border-color: #a4a7aa;
}

/* Checkbox box in dropdown */
:deep(.p-multiselect-overlay .p-checkbox-box) {
    background: #fff !important;
    border: 1px solid #ccc !important;
}

/* Search filter input */
:deep(.p-multiselect-filter) {
    background: #fff !important;
    color: #000 !important;
    border: 1px solid #ccc !important;
}

/* Optional: adjust filter container */
:deep(.p-multiselect-filter-container) {
    background: #fff !important;
}

/* Selected chip inside the multiselect */
:deep(.p-multiselect-chip) {
    background: #e9ecef !important;
    color: #000 !important;
    border-radius: 12px !important;
    border: 1px solid #ccc !important;
    padding: 0.25rem 0.5rem !important;
}

/* Chip remove (x) icon */
:deep(.p-multiselect-chip .p-chip-remove-icon) {
    color: #555 !important;
}

:deep(.p-multiselect-chip .p-chip-remove-icon:hover) {
    color: #dc3545 !important;
    /* red on hover */
}

/* keep PrimeVue overlays above Bootstrap modal/backdrop */
:deep(.p-multiselect-panel),
:deep(.p-select-panel),
:deep(.p-dropdown-panel) {
    z-index: 2000 !important;
}

/* ====================================================== */


/* ====================Select Styling===================== */
/* Entire select container */
:deep(.p-select) {
    background-color: white !important;
    color: black !important;
    border-color: #9b9c9c
}

/* Options container */
:deep(.p-select-list-container) {
    background-color: white !important;
    color: black !important;
}

/* Each option */
:deep(.p-select-option) {
    background-color: transparent !important;
    /* instead of 'none' */
    color: black !important;
}

/* Hovered option */
:deep(.p-select-option:hover) {
    background-color: #f0f0f0 !important;
    color: black !important;
}

/* Focused option (when using arrow keys) */
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

:global(.dark .p-multiselect-header) {
    background-color: #181818 !important;
    color: #fff !important;
}

:global(.dark .p-multiselect-label) {
    color: #fff !important;
}

:global(.dark .p-select .p-component .p-inputwrapper) {
    background: #181818 !important;
    color: #fff !important;
    border-bottom: 1px solid #555 !important;
}

/* Options list container */
:global(.dark .p-multiselect-list) {
    background: #181818 !important;
}

/* Each option */
:global(.dark .p-multiselect-option) {
    background: #181818 !important;
    color: #fff !important;
}

/* Hover/selected option */
:global(.dark .p-multiselect-option.p-highlight),
:global(.dark .p-multiselect-option:hover) {
    background: #222 !important;
    color: #fff !important;
}

:global(.dark .p-multiselect),
:global(.dark .p-multiselect-panel),
:global(.dark .p-multiselect-token) {
    background: #181818 !important;
    color: #fff !important;
    border-color: #555 !important;
}

/* Checkbox box in dropdown */
:global(.dark .p-multiselect-overlay .p-checkbox-box) {
    background: #181818 !important;
    border: 1px solid #555 !important;
}

/* Search filter input */
:global(.dark .p-multiselect-filter) {
    background: #181818 !important;
    color: #fff !important;
    border: 1px solid #555 !important;
}

/* Optional: adjust filter container */
:global(.dark .p-multiselect-filter-container) {
    background: #181818 !important;
}

/* Selected chip inside the multiselect */
:global(.dark .p-multiselect-chip) {
    background: #111 !important;
    color: #fff !important;
    border: 1px solid #555 !important;
    border-radius: 12px !important;
    padding: 0.25rem 0.5rem !important;
}

/* Chip remove (x) icon */
:global(.dark .p-multiselect-chip .p-chip-remove-icon) {
    color: #ccc !important;
}

:global(.dark .p-multiselect-chip .p-chip-remove-icon:hover) {
    color: #f87171 !important;
    /* lighter red */
}

/* ==================== Dark Mode Select Styling ====================== */
:global(.dark .p-select) {
    background-color: #181818 !important;
    color: #fff !important;
    border-color: #555 !important;
}

/* Options container */
:global(.dark .p-select-list-container) {
    background-color: #181818 !important;
    color: #fff !important;
}

/* Each option */
:global(.dark .p-select-option) {
    background-color: transparent !important;
    color: #fff !important;
}

/* Hovered option */
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

.dark .p-tabpanels {
    background-color: #222 !important;
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
</style>