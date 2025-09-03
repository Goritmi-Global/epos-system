<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted, onUpdated } from "vue";
import { toast } from "vue3-toastify";

const logs = ref([]);
const q = ref("");

const fetchLogs = async () => {
  try {
    const { data } = await axios.get("stock_entries/stock-logs");
    logs.value = data;
  } catch (e) {
    console.error("Failed to fetch logs", e);
  }
};


onMounted(() => {
  fetchLogs(); 
});

/* ---------------- Search ---------------- */
// const q = ref("");
const filtered = computed(() => {
    const t = q.value.trim().toLowerCase();
    if (!t) return logs.value;
    return logs.value.filter((r) =>
        [
            r.itemName,
            r.category,
            r.operationType,
            r.type,
            String(r.quantity),
            String(r.unitPrice),
            String(r.totalPrice),
            r.expiryDate ?? "",
            r.dateTime ?? "",
        ]
            .join(" ")
            .toLowerCase()
            .includes(t)
    );
});

/* ---------------- Helpers ---------------- */
const money = (n, currency = "GBP") =>
    new Intl.NumberFormat("en-GB", { style: "currency", currency }).format(n);

const fmtDateTime = (iso) =>
    new Date(iso).toLocaleString("en-GB", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        hour12: true,
    });

const fmtDate = (iso) => new Date(iso).toISOString().slice(0, 10); // YYYY-MM-DD

const typeClass = (t) =>
    t === "stock_in"
        ? "text-success"
        : t === "stock_out"
        ? "text-danger"
        : "text-muted";

/* ---------------- Actions (wire later) ---------------- */
const onDownloadAll = () => {
    console.log("Download all logs… (demo)");
};
const onView = (row) => console.log("View log:", row);
const onEdit = (row) => console.log("Edit log:", row);
const onDelete = (row) => console.log("Delete log:", row);

onMounted(() => window.feather?.replace());
onUpdated(() => window.feather?.replace());

/* ---------------- Logs view ---------------- */
const selectedLog = ref(null);

const View = (row) => {
    selectedLog.value = row;

    // Open modal manually
    const modal = new bootstrap.Modal(document.getElementById("viewLogModal"));
    modal.show();
};

/* ---------------- Logs Edit ---------------- */
const editForm = ref({
  id: null,
  itemName: "",
  quantity: "",
  unitPrice: "",
  expiryDate: "",
  operationType: "",
});

function Edit(row) {
  editForm.value = { ...row }; // copy log data
  const modal = new bootstrap.Modal(document.getElementById("editLogModal"));
  modal.show();
}

async function updateLog() {
  try {
    await axios.put(`stock_entries/stock-logs/${editForm.value.id}`, editForm.value);
    await fetchLogs(); 
    toast.success("Stock log updated successfully");
    const modal = bootstrap.Modal.getInstance(document.getElementById("editLogModal"));
    if (modal) modal.hide();
  } catch (error) {
    console.error(error);
    toast.error("Failed to update log");
  }
}

/* -------------------------- Delete Log ---------------------- */
async function Delete(row) {
  try {
    await axios.delete(`stock_entries/stock-logs/${row.id}`);
    await fetchLogs(); // refresh list
    toast.success("Stock log deleted successfully");
  } catch (error) {
    console.error(error);
    toast.error("Failed to delete log");
  }
}


</script>

<template>
    <Master>
        <div class="page-wrapper">
            <div class="container-fluid py-3">
                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-body p-4">
                        <div
                            class="d-flex align-items-center justify-content-between mb-3"
                        >
                            <h4 class="fw-semibold mb-0">Stock Moment Logs</h4>

                            <div class="d-flex gap-2 align-items-center">
                                <div class="search-wrap">
                                    <i class="bi bi-search"></i>
                                    <input
                                        v-model="q"
                                        type="text"
                                        class="form-control search-input"
                                        placeholder="Search"
                                    />
                                </div>

                                <div class="dropdown">
                                    <button
                                        class="btn btn-outline-secondary rounded-pill px-4"
                                        @click="onDownloadAll"
                                    >
                                        Download all
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table align-middle moment-table">
                                <thead class="small text-muted">
                                    <tr>
                                        <th>Item name</th>
                                        <th>Total Price</th>
                                        <th>Category</th>
                                        <th>Unit Price</th>
                                        <th>Date &amp; Time</th>
                                        <th>Expiry Date</th>
                                        <th>Quantity</th>
                                        <th>Operation Type</th>
                                        <th>Type</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template
                                        v-for="(row, i) in filtered"
                                        :key="row.id"
                                    >
                                        <tr>
                                            <td
                                                class="fw-semibold text-capitalize"
                                            >
                                                {{ row.itemName }}
                                            </td>
                                            <td>{{ money(row.totalPrice) }}</td>
                                            <td>{{ row.category }}</td>
                                            <td>{{ money(row.unitPrice) }}</td>
                                            <td>
                                                {{ fmtDateTime(row.dateTime) }}
                                            </td>
                                            <td>
                                                <span
                                                    v-if="row.expiryDate"
                                                    class="text-danger fw-semibold"
                                                    >{{
                                                        fmtDate(row.expiryDate)
                                                    }}</span
                                                >
                                                <span v-else class="text-muted"
                                                    >—</span
                                                >
                                            </td>
                                            <td>{{ row.quantity }}</td>
                                            <td class="text-break">
                                                {{ row.operationType }}
                                            </td>
                                            <td
                                                :class="typeClass(row.type)"
                                                class="fw-semibold"
                                            >
                                                {{ row.type }}
                                            </td>
                                            <td class="text-end">
                                                <div class="dropdown">
                                                    <button
                                                        class="btn btn-link text-secondary p-0 fs-5"
                                                        data-bs-toggle="dropdown"
                                                        aria-expanded="false"
                                                        title="Actions"
                                                    >
                                                        ⋮
                                                    </button>
                                                    <ul
                                                        class="dropdown-menu dropdown-menu-end shadow rounded-4 overflow-hidden"
                                                    >
                                                        <li>
                                                            <a
                                                                class="dropdown-item py-2"
                                                                href="javascript:void(0)"
                                                                @click="
                                                                    View(row)
                                                                "
                                                                ><i
                                                                    class="bi bi-eye me-2"
                                                                ></i
                                                                >View</a
                                                            >
                                                        </li>
                                                        <li>
                                                            <a
                                                                class="dropdown-item py-2"
                                                                href="javascript:void(0)"
                                                                @click="
                                                                    Edit(row)
                                                                "
                                                                ><i
                                                                    class="bi bi-pencil-square me-2"
                                                                ></i
                                                                >Edit</a
                                                            >
                                                        </li>
                                                        <li>
                                                            <hr
                                                                class="dropdown-divider"
                                                            />
                                                        </li>
                                                        <li>
                                                            <a
                                                                class="dropdown-item py-2 text-danger"
                                                                href="javascript:void(0)"
                                                                @click="
                                                                    Delete(
                                                                        row
                                                                    )
                                                                "
                                                                ><i
                                                                    class="bi bi-trash me-2"
                                                                ></i
                                                                >Delete</a
                                                            >
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- separator line between rows to match screenshot -->
                                        <tr class="sep-row">
                                            <td colspan="10"></td>
                                        </tr>
                                    </template>

                                    <tr v-if="filtered.length === 0">
                                        <td
                                            colspan="10"
                                            class="text-center text-muted py-4"
                                        >
                                            No logs found.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>


                         <!-- View Log Modal -->
                        <div
                            class="modal fade"
                            id="viewLogModal"
                            tabindex="-1"
                            aria-hidden="true"
                        >
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content rounded-4 shadow-lg">
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-semibold">Log Details</h5>
                                        <button
                                            type="button"
                                            class="btn-close"
                                            data-bs-dismiss="modal"
                                            aria-label="Close"
                                        ></button>
                                    </div>

                                    <div class="modal-body">
                                        <div v-if="selectedLog">
                                            <p><strong>Item:</strong> {{ selectedLog.itemName }}</p>
                                            <p><strong>Category:</strong> {{ selectedLog.category }}</p>
                                            <p><strong>Quantity:</strong> {{ selectedLog.quantity }}</p>
                                            <p><strong>Unit Price:</strong> {{ money(selectedLog.unitPrice) }}</p>
                                            <p><strong>Total Price:</strong> {{ money(selectedLog.totalPrice) }}</p>
                                            <p><strong>Operation:</strong> {{ selectedLog.operationType }}</p>
                                            <p><strong>Type:</strong> {{ selectedLog.type }}</p>
                                            <p><strong>Date:</strong> {{ fmtDateTime(selectedLog.dateTime) }}</p>
                                            <p>
                                                <strong>Expiry Date:</strong>
                                                {{ selectedLog.expiryDate ? fmtDate(selectedLog.expiryDate) : "—" }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- ✅ Footer with Close button -->
                                    <div class="modal-footer">
                                        <button
                                            type="button"
                                            class="btn btn-secondary rounded-pill px-4"
                                            data-bs-dismiss="modal"
                                        >
                                            Close
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Log Modal -->
                         <div class="modal fade" id="editLogModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content rounded-4">
                                <div class="modal-header">
                                    <h5 class="modal-title fw-semibold">Edit Stock Log</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <form @submit.prevent="updateLog">
                                    <div class="mb-3">
                                        <label class="form-label">Item Name</label>
                                        <input type="text" class="form-control" v-model="editForm.itemName" disabled>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Quantity</label>
                                        <input type="number" class="form-control" v-model="editForm.quantity" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Unit Price</label>
                                        <input type="number" class="form-control" v-model="editForm.unitPrice" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Expiry Date</label>
                                        <input type="date" class="form-control" v-model="editForm.expiryDate">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Operation Type</label>
                                        <select class="form-select" v-model="editForm.operationType">
                                        <option value="purchase">Purchase</option>
                                        <option value="sale">Sale</option>
                                        <option value="adjustment">Adjustment</option>
                                        </select>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100">Update</button>
                                    </form>
                                </div>
                                </div>
                            </div>
                            </div>


                    </div>
                </div>
            </div>
        </div>
    </Master>
</template>

<style scoped>
/* Search pill like other pages */
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

/* Table look & separators */
.moment-table thead th {
    font-weight: 600;
    border-bottom: 2px solid #111;
}
.moment-table tbody tr.sep-row td {
    border-bottom: 2px solid #111;
    height: 12px;
    padding: 0;
}
.moment-table tbody tr td {
    border-top: none; /* we control separators ourselves */
}

/* Make long operation types wrap neatly */
.text-break {
    word-break: break-word;
}

/* Keep dropdown width comfy */
.dropdown-menu {
    min-width: 200px;
}

/* Mobile tweaks */
@media (max-width: 575.98px) {
    .search-wrap {
        width: 100%;
    }
    .moment-table thead th:nth-child(4),
    .moment-table tbody td:nth-child(4) {
        display: none;
    } /* hide Unit Price on xs for space */
}
</style>
