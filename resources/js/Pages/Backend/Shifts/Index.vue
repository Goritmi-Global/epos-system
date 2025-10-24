<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted, onUpdated } from "vue";
import { Eye, Calendar, AlertTriangle, XCircle, Lock, Plus, Unlock, Percent } from "lucide-vue-next";
import { toast } from "vue3-toastify";
import axios from "axios";
import { useFormatters } from '@/composables/useFormatters'
import { nextTick } from "vue";
import { Head } from "@inertiajs/vue3";
import ConfirmModal from "@/Components/ConfirmModal.vue";

const { formatMoney, formatCurrencySymbol, formatNumber, dateFmt } = useFormatters()

/* ---------------- Data ---------------- */
const shifts = ref([]);
/* ---------------- Fetch shifts ---------------- */
const fetchShifts = async () => {
    try {
        const res = await axios.get("api/shift/all");
        shifts.value = res.data.data;
    } catch (err) {
        console.error("Failed to fetch shifts:", err);
        toast.error("Failed to load shifts");
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
    fetchShifts();
});

/* ---------------- KPI Cards ---------------- */
const shiftstats = computed(() => {
    const today = new Date().toISOString().split("T")[0]; // 'YYYY-MM-DD'

    return [
        {
            label: "Total Shifts",
            value: shifts.value.length,
            icon: Percent,
            iconBg: "bg-light-primary",
            iconColor: "text-primary",
        },
        {
            label: "Open Shifts",
            value: shifts.value.filter((s) => s.status === "open").length,
            icon: Unlock,
            iconBg: "bg-light-success",
            iconColor: "text-success",
        },
        {
            label: "Closed Shifts",
            value: shifts.value.filter((s) => s.status === "closed").length,
            icon: Lock,
            iconBg: "bg-light-warning",
            iconColor: "text-warning",
        },
        {
            label: "Today’s Shifts",
            value: shifts.value.filter((s) => {
                if (!s.start_time) return false;
                return new Date(s.start_time).toISOString().split("T")[0] === today;
            }).length,
            icon: Calendar,
            iconBg: "bg-light-danger",
            iconColor: "text-danger",
        },
    ];
});


/* ---------------- Search ---------------- */
const q = ref("");
const searchKey = ref(Date.now());
const inputId = `search-${Math.random().toString(36).substr(2, 9)}`;
const isReady = ref(false);

const filtered = computed(() => {
    const t = q.value.trim().toLowerCase();
    if (!t) return shifts.value;
    return shifts.value.filter((s) =>
        String(s.id).includes(t) ||
        (s.started_by && s.started_by.toLowerCase().includes(t)) ||
        (s.ended_by && s.ended_by.toLowerCase().includes(t)) ||
        (s.status && s.status.toLowerCase().includes(t)) ||
        (s.start_time && s.start_time.toLowerCase().includes(t)) ||
        String(s.sales_total).includes(t)
    );

});


/* ---------------- Helpers ---------------- */
const money = (n, currency = "GBP") =>
    new Intl.NumberFormat("en-GB", { style: "currency", currency }).format(n);

const formatTime = (date) => {
    if (!date) return "N/A";
    return new Date(date).toLocaleTimeString("en-GB", {
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
    });
};


onUpdated(() => window.feather?.replace());


// Open or closed shift 

const toggleShiftStatus = async (shift) => {
    if (shift.status !== "open") {
        toast.error("Only open shifts can be closed.")
        return;
    }

    try {
        const response = await axios.patch(`/api/shift/${shift.id}/close`, {
            status: "closed",
        });

        if (response.data.success) {
            toast.success(response.data.message);
            shift.status = "closed";
            window.location.href = response.data.redirect;
        } else {
            console.error("Unexpected response:", response.data);
        }
    } catch (error) {
        console.error("Failed to close shift:", error);
    }
};


</script>

<template>
    <Master>

        <Head title="Promo" />
        <div class="page-wrapper">

            <h4 class="fw-semibold mb-3">Shift Management</h4>

            <!-- KPI Cards -->
            <div class="row g-3">
                <div v-for="stat in shiftstats" :key="stat.label" class="col-md-6 col-xl-3">
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
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 fw-semibold">shifts</h5>

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
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="border-top small text-muted">
                                <tr>
                                    <th>#</th>
                                    <th>Shift ID</th>
                                    <th>Started By</th>
                                    <th>Start Time</th>
                                    <th>Opening Cash</th>
                                    <th>Closing Cash</th>
                                    <th>Sales Total</th>
                                    <th>Closed By</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr v-for="(shift, i) in filtered" :key="shift.id">
                                    <td>{{ i + 1 }}</td>
                                    <td>#{{ shift.id }}</td>
                                    <td>{{ shift.started_by || 'N/A' }}</td>
                                    <td>{{ formatTime(shift.start_time) }}</td>
                                    <td>{{ formatCurrencySymbol(shift.opening_cash) }}</td>
                                    <td>{{ formatCurrencySymbol(shift.closing_cash) }}</td>
                                    <td>{{ formatCurrencySymbol(shift.sales_total) }}</td>
                                    <td>{{ shift.ended_by || 'N/A' }}</td>

                                    <!-- Status -->
                                    <td class="text-center">
                                        <span :class="shift.status === 'open'
                                            ? 'badge bg-success px-4 py-2 rounded-pill'
                                            : 'badge bg-danger px-4 py-2 rounded-pill'">
                                            {{ shift.status === 'open' ? 'Open' : 'Closed' }}
                                        </span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center gap-3">
                                            <!-- View Shift -->
                                            <button @click="viewShift(shift)" title="View Shift"
                                                class="p-2 rounded-full text-blue-600 hover:bg-blue-100">
                                                <Eye class="w-4 h-4" />
                                            </button>

                                            <!-- Toggle Shift Status -->
                                            <button @click="toggleShiftStatus(shift)"
                                                class="relative inline-flex items-center w-10 h-5 rounded-full transition-colors duration-300 focus:outline-none"
                                                :class="shift.status === 'open'
                                                    ? 'bg-green-500 hover:bg-green-600'
                                                    : 'bg-red-400 hover:bg-red-500'"
                                                :title="shift.status === 'open' ? 'Close Shift' : 'Reopen Shift'">
                                                <!-- Circle inside switch -->
                                                <span
                                                    class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow transform transition-transform duration-300"
                                                    :class="shift.status === 'open' ? 'translate-x-5' : 'translate-x-0'"></span>
                                            </button>
                                        </div>
                                    </td>

                                </tr>

                                <tr v-if="!shifts.length">
                                    <td colspan="10" class="text-center text-muted py-4">
                                        No shifts found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

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