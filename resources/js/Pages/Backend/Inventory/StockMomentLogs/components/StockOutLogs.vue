<script setup>
import { ref, computed, onMounted, onUpdated } from "vue";
import { toast } from "vue3-toastify";
import axios from "axios";
import { Pencil, Eye } from "lucide-vue-next";

const props = defineProps({
    logs: { type: Array, default: () => [] },
    q: { type: String, default: "" },
});
const emit = defineEmits(["updated"]);

/* --------- Filters (stockout only) --------- */
const filtered = computed(() => {
    const t = (props.q || "").trim().toLowerCase();
    const base = props.logs.filter((r) => r.type === "stockout");
    if (!t) return base;
    return base.filter((r) =>
        [
            r.itemName,
            r.category?.name || r.category || "",
            r.operationType,
            r.type,
            String(r.quantity),
            String(r.totalPrice),
            r.dateTime ?? "",
        ]
            .join(" ")
            .toLowerCase()
            .includes(t)
    );
});

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
const selectedAllocations = ref([]); // allocation rows for stockout
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
        console.log(data);
        selectedAllocations.value = data || [];
    } catch (e) {
        selectedAllocations.value = [];
        console.error(e);
    }
};

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
        // For stockout, there is no unitPrice/expiryDate field to edit.
        await axios.put(
            `stock_entries/stock-logs/${editForm.value.id}`,
            editForm.value
        );
        toast.success("Stock log updated successfully");
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
        emit("updated");
    } catch (error) {
        console.error(error);
        toast.error("Failed to delete log");
    }
}

onMounted(() => window.feather?.replace());
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
                        <!-- <th>Unit Price</th>
                        <th>Total Price</th> -->
                        <th>Category</th>
                        <th>Date &amp; Time</th>
                        <!-- <th>Expiry Date</th> -->
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
                            <!-- <td><span class="text-muted">—</span></td>
                            <td>{{ money(row.totalPrice) }}</td> -->
                            <td>{{ row.category?.name || row.category }}</td>
                            <!-- Unit Price hidden for stockout -->
                            <td>{{ fmtDateTime(row.dateTime) }}</td>
                            <!-- Expiry hidden for stockout -->
                            <!-- <td><span class="text-muted">—</span></td> -->

                            
                            <td class="text-break">
                                <template v-if="row.operationType">
                                    <span
                                        :class="[
                                            'fw-semibold',
                                            opTextClass(row.operationType),
                                        ]"
                                    >
                                        {{ formatOpType(row.operationType) }}
                                    </span>
                                </template>
                                <span v-else class="text-muted">—</span>
                            </td>
                            
                            <td class="text-end">
                                <button
                                    class="p-2 rounded-full text-blue-600 hover:bg-blue-100"
                                    @click="Edit(row)"
                                    title="Adjustment"
                                >
                                    <Pencil class="w-4 h-4" />
                                </button>
                                <button
                                    class="p-2 rounded-full text-gray-600 hover:bg-blue-100"
                                    @click="View(row)"
                                    title="Show"
                                >
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

        <!-- View Modal (Stock Out) -->
        <div
            class="modal fade"
            id="viewLogModal_out"
            tabindex="-1"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content rounded-4 shadow-lg border-0">
                    <div class="modal-header align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary rounded-circle p-2">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="18"
                                    height="18"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M3 7.5L12 12l9-4.5M3 7.5V17a2 2 0 002 2h14a2 2 0 002-2V7.5M21 7.5L12 3 3 7.5"
                                    />
                                </svg>
                            </span>
                            <div class="d-flex flex-column">
                                <h5 class="modal-title mb-0">View Stock Log</h5>
                                <small class="text-muted"
                                    >Item:
                                    {{ selectedLog?.itemName ?? "—" }}</small
                                >
                            </div>
                        </div>
                        <button
                            class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                            title="Close"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-6 w-6 text-danger"
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

                    <div class="modal-body p-4 bg-light">
                        <div v-if="selectedLog" class="row g-4">
                            <div class="col-lg-12">
                                <div
                                    class="card border-0 shadow-sm rounded-4 h-100"
                                >
                                    <div class="card-body">
                                        <h6 class="fw-semibold mb-3">
                                            Details
                                        </h6>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <small
                                                    class="text-muted d-block"
                                                    >Category</small
                                                >
                                                <div class="fw-semibold">
                                                    {{
                                                        selectedLog.category
                                                            ?.name ??
                                                        selectedLog.category ??
                                                        "—"
                                                    }}
                                                </div>
                                            </div>
                                            <!-- <div class="col-md-6">
                                                <small class="text-muted d-block">Operation</small>
                                                <div class="fw-semibold">{{ selectedLog.operationType ?? "—" }}</div>
                                            </div> -->
                                            <!-- <div class="col-md-6">
                                                <small class="text-muted d-block">Stock Out Quantity</small>
                                                <div class="fw-semibold">{{ selectedLog.quantity ?? "—" }}</div>
                                            </div> -->
                                            <div class="col-md-6">
                                                <small
                                                    class="text-muted d-block"
                                                    >Operation</small
                                                >
                                                <span
                                                    class="badge rounded-pill bg-danger"
                                                    >{{
                                                        selectedLog.operationType ??
                                                        "—"
                                                    }}</span
                                                >
                                            </div>
                                        </div>

                                        <hr class="my-4" />

                                        <div class="row g-3 text-center">
                                            <!-- <div class="col-md-6">
                                                <div class="p-3 bg-light rounded-3">
                                                    <small class="text-muted d-block">Total Price</small>
                                                    <div class="fs-6 fw-semibold">{{ money(selectedLog.totalPrice ?? 0) }}</div>
                                                </div>
                                            </div> -->
                                            <div class="col-md-12">
                                                <div
                                                    class="p-3 bg-light rounded-3"
                                                >
                                                    <small
                                                        class="text-muted d-block"
                                                        >Date</small
                                                    >
                                                    <div
                                                        class="fs-6 fw-semibold"
                                                    >
                                                        {{
                                                            fmtDateTime(
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
                                            <table
                                                class="table table-hover align-middle"
                                            >
                                                <thead class="small text-muted">
                                                    <tr>
                                                        <th>#</th>
                                                        <!-- <th>Batch (IN) ID</th> -->
                                                        <!-- <th>Product</th> -->
                                                        <th>Expiry Date</th>
                                                        <th>Unit Price</th>
                                                        <th>Allocated Qty</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr
                                                        v-for="(a, idx) in selectedAllocations" :key="a.id"
                                                    >
                                                        <td>{{ idx + 1 }}</td>
                                                        <!-- <td>{{ a.stock_in_entry_id }}</td> -->
                                                        <!-- <td>{{ a.product_name }}</td> -->
                                                        <td>
                                                            {{
                                                                a.expiry_date
                                                                    ? new Date(
                                                                          a.expiry_date
                                                                      )
                                                                          .toISOString()
                                                                          .slice(
                                                                              0,
                                                                              10
                                                                          )
                                                                    : "—"
                                                            }}
                                                        </td>
                                                        <td>
                                                            {{
                                                                money(
                                                                    a.unit_price ??
                                                                        0
                                                                )
                                                            }}
                                                        </td>
                                                        <td>
                                                            {{ a.quantity }}
                                                        </td>
                                                    </tr>
                                                    <tr
                                                        v-if="
                                                            selectedAllocations.length ===
                                                            0
                                                        "
                                                    >
                                                        <td
                                                            colspan="5"
                                                            class="text-center text-muted py-3"
                                                        >
                                                            No allocation
                                                            history.
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <!-- /Allocation table -->
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
        <div
            class="modal fade"
            id="editLogModal_out"
            tabindex="-1"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content rounded-4">
                    <div class="modal-header">
                        <h5 class="modal-title fw-semibold">Edit Stock Log</h5>
                        <button
                            class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                            title="Close"
                            @click="resetErrors"
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
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6 col-md-12">
                                <label class="form-label">Item Name</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="editForm.itemName"
                                    disabled
                                />
                            </div>
                            <div class="col-md-6 col-md-12">
                                <label class="form-label">Quantity</label>
                                <input
                                    type="number"
                                    class="form-control"
                                    v-model="editForm.quantity"
                                    :class="{
                                        'is-invalid': formErrors.quantity,
                                    }"
                                    required
                                />
                                <small
                                    v-if="formErrors.quantity"
                                    class="text-danger"
                                    >{{ formErrors.quantity[0] }}</small
                                >
                            </div>

                            <!-- No unit price, no expiry date for stockout -->
                            <div class="col-md-6 col-md-12">
                                <label class="form-label">Value</label>
                                <input
                                    type="number"
                                    readonly
                                    class="form-control"
                                    v-model="editForm.totalPrice"
                                />
                            </div>

                            <div class="mt-4">
                                <button
                                    class="btn btn-primary rounded-pill px-4 d-inline-flex align-items-center gap-2"
                                    :disabled="isUpdating"
                                    :aria-busy="isUpdating ? 'true' : 'false'"
                                    @click="updateLog"
                                >
                                    <span
                                        v-if="isUpdating"
                                        class="spinner-border spinner-border-sm"
                                        role="status"
                                        aria-hidden="true"
                                    ></span>
                                    <span>{{
                                        isUpdating ? "Updating…" : "Update"
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
.dark .table thead th{
  background-color:#111827 !important; ;
  color: #ffffff;
}
</style>
