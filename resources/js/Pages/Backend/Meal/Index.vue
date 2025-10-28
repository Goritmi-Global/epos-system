<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted, nextTick } from "vue";
import { Utensils, Clock, AlertTriangle, Plus, Pencil, Trash2 } from "lucide-vue-next";
import { toast } from "vue3-toastify";
import axios from "axios";
import ConfirmModal from "@/Components/ConfirmModal.vue";
import { Head } from "@inertiajs/vue3";


import { usePage } from "@inertiajs/vue3";
const page = usePage()

/* ---------------- Data ---------------- */
const meals = ref([]);
const editingMeal = ref(null);
const submitting = ref(false);
const mealFormErrors = ref({});

/* ---------------- Fetch Meals ---------------- */
const fetchMeals = async () => {
    try {
        const res = await axios.get("api/meals/all");
        meals.value = res.data.data;
    } catch (err) {
        console.error("Failed to fetch meals:", err);
        toast.error("Failed to load meals");
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
    fetchMeals();
});

/* ---------------- KPI Cards ---------------- */
const mealStats = computed(() => [
    {
        label: "Total Meals",
        value: meals.value.length,
        icon: Utensils,
        iconBg: "bg-light-primary",
        iconColor: "text-primary",
    },
]);

/* ---------------- Search ---------------- */
const q = ref("");
const searchKey = ref(Date.now());
const inputId = `search-${Math.random().toString(36).substr(2, 9)}`;
const isReady = ref(false);

const handleFocus = (e) => {
    e.target.value = q.value;
};

const filtered = computed(() => {
    const t = q.value.trim().toLowerCase();
    if (!t) return meals.value;
    return meals.value.filter((m) => m.name.toLowerCase().includes(t));
});

/* ---------------- Form State ---------------- */
const mealForm = ref({
    name: "",
    start_time: "",
    end_time: "",
});

const resetModal = () => {
    mealForm.value = {
        name: "",
        start_time: "",
        end_time: "",
    };
    editingMeal.value = null;
    mealFormErrors.value = {};
};
const formatTime = (date) => {
    if (!date) return null;

    // Handle if it's already a string in HH:mm format
    if (typeof date === 'string' && /^\d{2}:\d{2}$/.test(date)) {
        return date;
    }

    // Handle Date object
    const d = new Date(date);
    const hours = d.getHours().toString().padStart(2, '0');
    const minutes = d.getMinutes().toString().padStart(2, '0');
    return `${hours}:${minutes}`; // Returns "09:30" format
};

/* ---------------- Submit (Create/Update) ---------------- */
const submitMeal = async () => {
    submitting.value = true;
    mealFormErrors.value = {};

    const payload = {
        ...mealForm.value,
        start_time: formatTime(mealForm.value.start_time),
        end_time: formatTime(mealForm.value.end_time),
    };

    try {
        if (editingMeal.value) {
            await axios.post(`/meals/${editingMeal.value.id}`, payload);
            toast.success("Meal updated successfully");
        } else {
            await axios.post("/meals", payload);
            toast.success("Meal created successfully");
        }

        const modal = bootstrap.Modal.getInstance(document.getElementById("mealModal"));
        modal?.hide();

        resetModal();
        await fetchMeals();
    } catch (err) {
        console.error("❌ Error:", err.response?.data || err.message);
        if (err.response?.status === 422 && err.response?.data?.errors) {
            mealFormErrors.value = err.response.data.errors;
            const errorMessages = Object.values(err.response.data.errors).flat();
            toast.error(errorMessages.join("\n"));
        } else {
            const errorMessage = err.response?.data?.message || "Failed to save meal";
            toast.error(errorMessage);
        }
    } finally {
        submitting.value = false;
    }
};

/* ---------------- Edit ---------------- */
const parseTime = (timeStr) => {
    if (!timeStr) return null;
    const [hours, minutes] = timeStr.split(":");
    const date = new Date();
    date.setHours(hours);
    date.setMinutes(minutes);
    date.setSeconds(0);
    return date;
};

const editRow = (row) => {
    editingMeal.value = row;
    mealForm.value = {
        name: row.name,
        start_time: row.start_time, // ✅ Direct string assignment (already in HH:mm format)
        end_time: row.end_time,     // ✅ Direct string assignment (already in HH:mm format)
    };

    const modalEl = document.getElementById("mealModal");
    const bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();
};


/* ---------------- Delete ---------------- */
const deleteMeal = async (row) => {
    if (!row?.id) return;

    try {
        await axios.delete(`/meals/${row.id}`);
        toast.success("Meal deleted successfully");
        await fetchMeals();
    } catch (err) {
        console.error("❌ Delete error:", err.response?.data || err.message);
        toast.error("Failed to delete meal");
    }
};
</script>

<template>
    <Master>

        <Head title="Meals" />
        <div class="page-wrapper">
            <h4 class="fw-semibold mb-3">Meal Management</h4>

            <!-- KPI Cards -->
            <div class="row g-3">
                <div v-for="stat in mealStats" :key="stat.label" class="col-md-6 col-xl-3">
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
            <div class="card border-0 shadow-lg rounded-4 mt-4">
                <div class="card-body">
                    <!-- Toolbar -->
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 fw-semibold">Meals</h5>

                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <div class="search-wrap">
                                <i class="bi bi-search"></i>
                                <input type="email" name="email" autocomplete="email"
                                    style="position: absolute; left: -9999px; width: 1px; height: 1px;" tabindex="-1"
                                    aria-hidden="true" />

                                <input v-if="isReady" :id="inputId" v-model="q" :key="searchKey"
                                    class="form-control search-input" placeholder="Search" type="search"
                                    autocomplete="new-password" :name="inputId" role="presentation"
                                    @focus="handleFocus" />
                                <input v-else class="form-control search-input" placeholder="Search" disabled
                                    type="text" />
                            </div>

                            <button data-bs-toggle="modal" data-bs-target="#mealModal" @click="resetModal"
                                class="d-flex align-items-center gap-1 px-4 py-2 rounded-pill btn btn-primary text-white">
                                <Plus class="w-4 h-4" /> Add Meal
                            </button>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="border-top small text-muted">
                                <tr>
                                    <th>Id</th>
                                    <th>Name</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in filtered" :key="row.id">
                                    <td>{{ row.id }}</td>
                                    <td class="fw-semibold">{{ row.name }}</td>
                                    <td>{{ row.start_time }}</td>
                                    <td>{{ row.end_time }}</td>
                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center gap-3">
                                            <button @click="editRow(row)" title="Edit"
                                                class="p-2 rounded-full text-blue-600 hover:bg-blue-100">
                                                <Pencil class="w-4 h-4" />
                                            </button>

                                            <ConfirmModal :title="'Confirm Delete'"
                                                :message="`Are you sure you want to delete ${row.name}?`"
                                                confirmText="Yes, Delete" cancelText="Cancel"
                                                @confirm="deleteMeal(row)">
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
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No meals found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ================== Add/Edit Meal Modal ================== -->
            <div class="modal fade" id="mealModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content rounded-4">
                        <div class="modal-header">
                            <h5 class="modal-title fw-semibold">
                                {{ editingMeal ? "Edit Meal" : "Add New Meal" }}
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
                                <!-- Name -->
                                <div class="col-12">
                                    <label class="form-label">Meal Name</label>
                                    <input v-model="mealForm.name" type="text" class="form-control" :class="{
                                        'is-invalid': mealFormErrors.name,
                                    }" placeholder="Enter meal name" />
                                    <small v-if="mealFormErrors.name" class="text-danger">
                                        {{ mealFormErrors.name[0] }}
                                    </small>
                                </div>

                                <!-- Start Time -->
                                <div class="col-md-6">
                                    <label class="form-label">Start Time</label>
                                    <VueDatePicker v-model="mealForm.start_time" time-picker format="HH:mm"
                                        model-type="HH:mm" placeholder="Select start time"
                                        :class="{ 'is-invalid': mealFormErrors.start_time }" />
                                    <small v-if="mealFormErrors.start_time" class="text-danger">
                                        {{ mealFormErrors.start_time[0] }}
                                    </small>
                                </div>

                                <!-- End Time -->
                                <div class="col-md-6">
                                    <label class="form-label">End Time</label>
                                    <VueDatePicker v-model="mealForm.end_time" time-picker format="HH:mm"
                                        model-type="HH:mm" placeholder="Select end time"
                                        :class="{ 'is-invalid': mealFormErrors.end_time }" />
                                    <small v-if="mealFormErrors.end_time" class="text-danger">
                                        {{ mealFormErrors.end_time[0] }}
                                    </small>
                                </div>
                            </div>

                            <hr class="my-4" />

                            <div class="mt-4">
                                <button class="btn btn-primary rounded-pill px-4" :disabled="submitting"
                                    @click="submitMeal()">
                                    <template v-if="submitting">
                                        <span class="spinner-border spinner-border-sm me-2"></span>
                                        Saving...
                                    </template>
                                    <template v-else>
                                        {{ editingMeal ? "Update" : "Save" }}
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
            <!-- /modal -->
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
</style>