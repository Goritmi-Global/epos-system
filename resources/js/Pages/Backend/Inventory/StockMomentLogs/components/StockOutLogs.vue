<script setup>
import { ref, computed, onMounted, watch, onUpdated } from "vue";
import { toast } from "vue3-toastify";
import axios from "axios";
import { Pencil, Eye } from "lucide-vue-next";
import { useFormatters } from '@/composables/useFormatters'
import Pagination from "@/Components/Pagination.vue";

const { formatMoney, formatNumber, formatCurrencySymbol, dateFmt } = useFormatters()

const props = defineProps({
    logs: { type: Array, default: () => [] },
    q: { type: String, default: "" },
});
const emit = defineEmits(["updated"]);


// ===================== Pagination & Data =====================
const loading = ref(false);
const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
    from: 0,
    to: 0,
    links: []
});
const stockOutLogs = ref([]);

const fetchStockOutLogs = async (page = null) => {
    loading.value = true;
    try {
        const res = await axios.get("stock_entries/api-stock-out-logs", {
            params: {
                q: props.q,
                page: page || pagination.value.current_page,
                per_page: pagination.value.per_page
            }
        });

        stockOutLogs.value = res.data.data || [];

        pagination.value = {
            current_page: res.data.current_page,
            last_page: res.data.last_page,
            per_page: res.data.per_page,
            total: res.data.total,
            from: res.data.from,
            to: res.data.to,
            links: res.data.links
        };

        loading.value = false;
    } catch (err) {
        console.error('❌ Fetch stock out logs error:', err);
        toast.error("Failed to load stock out logs");
        loading.value = false;
    }
};

const handlePageChange = (url) => {
    if (!url) return;
    const urlParams = new URLSearchParams(url.split('?')[1]);
    const page = urlParams.get('page');
    if (page) {
        fetchStockOutLogs(parseInt(page));
    }
};

// Use stockOutLogs instead of filtered
const filtered = computed(() => stockOutLogs.value);
/* --------- Helpers (unchanged) --------- */
const money = (n, currency = "GBP") =>
    new Intl.NumberFormat("en-GB", { style: "currency", currency }).format(
        n ?? 0
    );
const fmtDateTime = (iso) =>
    new Date(iso).toLocaleString("en-GB", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        hour12: true,
    });
const startCase = (str) =>
    (str ?? "")
        .toString()
        .replace(/[_-]+/g, " ")
        .trim()
        .replace(
            /\w\S*/g,
            (t) => t[0].toUpperCase() + t.slice(1).toLowerCase()
        );
const formatOpType = (v) => {
    const map = {
        inventory_stockin: "Inventory Stockin",
        inventory_stockout: "Inventory Stockout",
        purchase: "Purchase",
        adjustment: "Adjustment",
        return: "Return",
    };
    return map[v] ?? startCase(v);
};
const formatType = (v) => {
    const map = { stockin: "Stockin", stockout: "Stockout" };
    return map[v] ?? startCase(v);
};
const opTextClass = (v) => {
    switch (v) {
        case "inventory_stockin":
        case "purchase":
            return "text-success";
        case "inventory_stockout":
            return "text-danger";
        case "adjustment":
            return "text-warning";
        default:
            return "text-secondary";
    }
};
const typeTextClass = (v) =>
    v === "stockin"
        ? "text-success"
        : v === "stockout"
            ? "text-danger"
            : "text-secondary";

/* --------- View / Edit --------- */
const selectedLog = ref(null);
const selectedAllocations = ref([]);
const formErrors = ref({});
const editForm = ref({
    id: null,
    itemName: "",
    quantity: "",
    totalPrice: "",
    operationType: "",
    type: "stockout",
});

const fetchAllocations = async (logId) => {
    try {
        const { data } = await axios.get(
            `stock_entries/stock-logs/${logId}/allocations`
        );
        selectedAllocations.value = data || [];
    } catch (e) {
        selectedAllocations.value = [];
        console.error(e);
    }
};
let searchTimeout = null;
watch(() => props.q, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        pagination.value.current_page = 1;
        fetchStockOutLogs(1);
    }, 500);
});

const View = async (row) => {
    selectedLog.value = row;
    selectedAllocations.value = [];
    await fetchAllocations(row.id);
    const modal = new bootstrap.Modal(
        document.getElementById("viewLogModal_out")
    );
    modal.show();
};

function Edit(row) {
    editForm.value = { ...row };
    const modal = new bootstrap.Modal(
        document.getElementById("editLogModal_out")
    );
    modal.show();
}

const isUpdating = ref(false);
const resetErrors = () => (formErrors.value = {});

async function updateLog() {
    if (isUpdating.value) return;
    isUpdating.value = true;
    formErrors.value = {};
    try {
        await axios.put(
            `stock_entries/stock-logs/${editForm.value.id}`,
            editForm.value
        );
        toast.success("Stock log updated successfully");
        await fetchStockOutLogs();
        emit("updated");
        const modal = bootstrap.Modal.getInstance(
            document.getElementById("editLogModal_out")
        );
        if (modal) modal.hide();
    } catch (err) {
        if (err?.response?.status === 422 && err.response.data?.errors) {
            formErrors.value = err.response.data.errors;
            toast.error("Please fill in all required fields correctly.");
        } else {
            toast.error("Something went wrong. Please try again.");
            console.error(err);
        }
    } finally {
        isUpdating.value = false;
    }
}

async function Delete(row) {
    try {
        await axios.delete(`stock_entries/stock-logs/${row.id}`);
        toast.success("Stock log deleted successfully");
        await fetchStockOutLogs();
        emit("updated");
    } catch (error) {
        console.error(error);
        toast.error("Failed to delete log");
    }
}

onMounted(() => {
    window.feather?.replace();
    fetchStockOutLogs();
});
onUpdated(() => window.feather?.replace());
</script>

<template>
    <div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="small text-muted">
                    <tr>
                        <th>Item name</th>
                        <th>Quantity</th>
                        <th>Category</th>
                        <th>Date &amp; Time</th>
                        <th>Operation Type</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="row in filtered" :key="row.id">
                        <tr>
                            <td class="fw-semibold text-capitalize">
                                {{ row.itemName }}
                            </td>
                            <td>{{ row.quantity }}</td>
                            <td>{{ row.category?.name || row.category }}</td>
                            <td>{{ dateFmt(row.dateTime) }}</td>


                            <td class="text-break">
                                <template v-if="row.operationType">
                                    <span :class="[
                                        'fw-semibold',
                                        opTextClass(row.operationType),
                                    ]">
                                        {{ formatOpType(row.operationType) }}
                                    </span>
                                </template>
                                <span v-else class="text-muted">—</span>
                            </td>

                            <td class="text-end">
                                <button class="p-2 rounded-full text-blue-600 hover:bg-blue-100" @click="Edit(row)"
                                    title="Adjustment">
                                    <Pencil class="w-4 h-4" />
                                </button>
                                <button class="p-2 rounded-full text-primary hover:bg-blue-100" @click="View(row)"
                                    title="Show">
                                    <Eye class="w-4 h-4" />
                                </button>
                            </td>
                        </tr>
                    </template>

                    <tr v-if="filtered.length === 0">
                        <td colspan="10" class="text-center text-muted py-4">
                            No logs found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- Add this pagination section -->
        <div v-if="!loading && pagination.last_page > 1" class="mt-4 d-flex justify-content-between align-items-center">
            <div class="text-muted small">
                Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
            </div>

            <Pagination :pagination="pagination.links" :isApiDriven="true" @page-changed="handlePageChange" />
        </div>


        <!-- View Modal (Stock Out) -->
        <div class="modal fade" id="viewLogModal_out" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content rounded-4 shadow-lg border-0">
                    <div class="modal-header align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary rounded-circle p-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 7.5L12 12l9-4.5M3 7.5V17a2 2 0 002 2h14a2 2 0 002-2V7.5M21 7.5L12 3 3 7.5" />
                                </svg>
                            </span>
                            <div class="d-flex flex-column">
                                <h5 class="modal-title mb-0">View Stock Log</h5>
                                <small class="text-muted">Item:
                                    {{ selectedLog?.itemName ?? "—" }}</small>
                            </div>
                        </div>
                        <button
                            class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                            data-bs-dismiss="modal" aria-label="Close" title="Close">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-danger" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="modal-body p-4 bg-light">
                        <div v-if="selectedLog" class="row g-4">
                            <div class="col-lg-12">
                                <div class="card border-0 shadow-sm rounded-4 h-100">
                                    <div class="card-body">
                                        <h6 class="fw-semibold mb-3">
                                            Details
                                        </h6>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <small class="text-muted d-block">Category</small>
                                                <div class="fw-semibold">
                                                    {{
                                                        selectedLog.category
                                                            ?.name ??
                                                        selectedLog.category ??
                                                        "—"
                                                    }}
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted d-block">Operation</small>
                                                <span class="badge rounded-pill bg-danger">{{
                                                    selectedLog.operationType ??
                                                    "—"
                                                    }}</span>
                                            </div>
                                        </div>

                                        <hr class="my-4" />

                                        <div class="row g-3 text-center">
                                            <div class="col-md-12">
                                                <div class="p-3 bg-light rounded-3">
                                                    <small class="text-muted d-block">Date</small>
                                                    <div class="fs-6 fw-semibold">
                                                        {{
                                                            dateFmt(
                                                                selectedLog.dateTime
                                                            )
                                                        }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Allocation table -->
                                        <hr class="my-4" />
                                        <h6 class="fw-semibold mb-3">
                                            Stock-out Allocation
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle">
                                                <thead class="small text-muted">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Expiry Date</th>
                                                        <th>Unit Price</th>
                                                        <th>Allocated Qty</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="(a, idx) in selectedAllocations" :key="a.id">
                                                        <td>{{ idx + 1 }}</td>
                                                        <td>
                                                            {{
                                                                dateFmt(a.expiry_date
                                                                    ? new Date(
                                                                        a.expiry_date
                                                                    )
                                                                        .toISOString()
                                                                        .slice(
                                                                            0,
                                                                            10
                                                                        )
                                                                    : "—")
                                                            }}
                                                        </td>
                                                        <td>
                                                            {{
                                                                formatCurrencySymbol(
                                                                    a.unit_price ??
                                                                    0
                                                                )
                                                            }}
                                                        </td>
                                                        <td>
                                                            {{ a.quantity }}
                                                        </td>
                                                    </tr>
                                                    <tr v-if="
                                                        selectedAllocations.length ===
                                                        0
                                                    ">
                                                        <td colspan="5" class="text-center text-muted py-3">
                                                            No allocation
                                                            history.
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                    </div>
                                </div>
                            </div>
                            <!-- /col -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal (Stock Out) -->
        <div class="modal fade" id="editLogModal_out" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content rounded-4">
                    <div class="modal-header">
                        <h5 class="modal-title fw-semibold">Edit Stock Log</h5>
                        <button
                            class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                            data-bs-dismiss="modal" aria-label="Close" title="Close" @click="resetErrors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6 col-md-12">
                                <label class="form-label">Item Name</label>
                                <input type="text" class="form-control" v-model="editForm.itemName" disabled />
                            </div>
                            <div class="col-md-6 col-md-12">
                                <label class="form-label">Quantity</label>
                                <input type="number" class="form-control" v-model="editForm.quantity" :class="{
                                    'is-invalid': formErrors.quantity,
                                }" required />
                                <small v-if="formErrors.quantity" class="text-danger">{{ formErrors.quantity[0]
                                }}</small>
                            </div>
                            <div class="col-md-6 col-md-12">
                                <label class="form-label">Value</label>
                                <input type="number" readonly class="form-control" v-model="editForm.totalPrice" />
                            </div>

                            <div class="mt-4">
                                <button class="btn btn-primary rounded-pill px-4 d-inline-flex align-items-center gap-2"
                                    :disabled="isUpdating" :aria-busy="isUpdating ? 'true' : 'false'"
                                    @click="updateLog">
                                    <span v-if="isUpdating" class="spinner-border spinner-border-sm" role="status"
                                        aria-hidden="true"></span>
                                    <span>{{
                                        isUpdating ? "Saving…" : "Save"
                                    }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /wrapper -->
</template>

<style>
.dark .table thead th {
    background-color: #181818 !important;
    ;
    color: #ffffff;
}

.dark .bg-light {
    background-color: #212121 !important;
    color: #fff !important;
}
</style>
