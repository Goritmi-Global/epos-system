<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted, onUpdated, watch } from "vue";
import { toast } from "vue3-toastify";
import { jsPDF } from "jspdf";
import autoTable from "jspdf-autotable";
import * as XLSX from "xlsx";
import { Pencil, Eye } from "lucide-vue-next";
const logs = ref([]);
const q = ref("");

const fetchLogs = async () => {
    try {
        const { data } = await axios.get("stock_entries/stock-logs");
        logs.value = data;
    } catch (e) {
        toast.error("Failed to fetch logs", e);
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
const formErrors = ref({});
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
const isUpdating = ref(false);
 const resetErrors = () => {
         formErrors.value = {};
     }
async function updateLog() {
    if (isUpdating.value) return; // prevent double click
    isUpdating.value = true;
     formErrors.value = {};

    
    try {
        await axios.put(
            `stock_entries/stock-logs/${editForm.value.id}`,
            editForm.value
        );
        await fetchLogs();
        toast.success("Stock log updated successfully");

        const modal = bootstrap.Modal.getInstance(
            document.getElementById("editLogModal")
        );
        if (modal) modal.hide();
    } catch (err) {
   
        if (err?.response?.status === 422 && err.response.data?.errors) {
                formErrors.value = err.response.data.errors;
            
                const list = [
                    ...new Set(Object.values(err.response.data.errors).flat()),
                ];
                const msg = list.join("<br>");

                // toast.dismiss();
                toast.error(`Validation failed:<br>${msg}`, {
                    autoClose: 3500,
                    dangerouslyHTMLString: true, // for vue-toastification
                });
            } else {
                // toast.dismiss();
                toast.error("Something went wrong. Please try again.");
                console.error(err);
            }
    } finally {
        isUpdating.value = false;
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

const onDownload = (type) => {
    if (!logs.value || logs.value.length === 0) {
        toast.error("No Allergies data to download");
        return;
    }

    // Use filtered data if there's a search query, otherwise use all suppliers
    const dataToExport = q.value.trim() ? filtered.value : logs.value;

    if (dataToExport.length === 0) {
        toast.error("No Logs Moment found to download");
        return;
    }

    try {
        if (type === "pdf") {
            downloadPDF(dataToExport);
        } else if (type === "excel") {
            downloadExcel(dataToExport);
        } else if (type === "csv") {
            downloadCSV(dataToExport);
        } else {
            toast.error("Invalid download type");
        }
    } catch (error) {
        console.error("Download failed:", error);
        toast.error(`Download failed: ${error.message}`);
    }
};

const downloadCSV = (data) => {
    try {
        // Define headers
        const headers = [
            "Category",
            "Supplier",
            "Product",
            "Quantity",
            "Price",
            "Value",
            "Operation Type",
            "Stock Type",
        ];

        // Build CSV rows
        const rows = data.map((s) => [
            `"${s.category || ""}"`,
            `"${s.supplier || ""}"`,
            `"${s.itemName || ""}"`,
            `"${s.quantity || ""}"`,
            `"${s.unitPrice || ""}"`,
            `"${s.totalPrice || ""}"`,
            `"${s.operationType || ""}"`,
            `"${s.type || ""}"`,
        ]);

        // Combine into CSV string
        const csvContent = [
            headers.join(","), // header row
            ...rows.map((r) => r.join(",")), // data rows
        ].join("\n");

        // Create blob
        const blob = new Blob([csvContent], {
            type: "text/csv;charset=utf-8;",
        });
        const url = URL.createObjectURL(blob);

        // Create download link
        const link = document.createElement("a");
        link.setAttribute("href", url);
        link.setAttribute(
            "download",
            `logs_moment_${new Date().toISOString().split("T")[0]}.csv`
        );
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        toast.success("CSV downloaded successfully");
    } catch (error) {
        console.error("CSV generation error:", error);
        toast.error(`CSV generation failed: ${error.message}`, {
            autoClose: 5000,
        });
    }
};

const downloadPDF = (data) => {
    try {
        const doc = new jsPDF("p", "mm", "a4"); // portrait, millimeters, A4

        // 🌟 Title
        doc.setFontSize(20);
        doc.setFont("helvetica", "bold");
        doc.text("Logs Moment Report", 70, 20);

        // 🗓️ Metadata
        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        const currentDate = new Date().toLocaleString();
        doc.text(`Generated on: ${currentDate}`, 70, 28);
        doc.text(`Total Logs: ${data.length}`, 70, 34);

        // 📋 Table Data
        const tableColumns = [
            "Category",
            "Supplier",
            "Product",
            "Quantity",
            "Value",
            "Price",
            "Operation Type",
            "Stock Type",
        ];
        const tableRows = data.map((s) => [
            s.category || "",
            s.supplier || "",
            s.itemName || "",
            s.quantity || "",
            s.unitPrice || "",
            s.totalPrice || "",
            s.operationType || "",
            s.type || "",
        ]);

        // 📑 Styled table
        autoTable(doc, {
            head: [tableColumns],
            body: tableRows,
            startY: 40,
            styles: {
                fontSize: 8,
                cellPadding: 2,
                halign: "left",
                lineColor: [0, 0, 0],
                lineWidth: 0.1,
            },
            headStyles: {
                fillColor: [41, 128, 185],
                textColor: 255,
                fontStyle: "bold",
            },
            alternateRowStyles: { fillColor: [240, 240, 240] },
            margin: { left: 14, right: 14 },
            didDrawPage: (tableData) => {
                // Footer with page numbers
                const pageCount = doc.internal.getNumberOfPages();
                const pageHeight = doc.internal.pageSize.height;
                doc.setFontSize(8);
                doc.text(
                    `Page ${tableData.pageNumber} of ${pageCount}`,
                    tableData.settings.margin.left,
                    pageHeight - 10
                );
            },
        });

        // 💾 Save file
        const fileName = `logs_moment_${
            new Date().toISOString().split("T")[0]
        }.pdf`;
        doc.save(fileName);

        toast.success("PDF downloaded successfully");
    } catch (error) {
        console.error("PDF generation error:", error);
        toast.error(`PDF generation failed: ${error.message}`, {
            autoClose: 5000,
        });
    }
};

const downloadExcel = (data) => {
    try {
        if (typeof XLSX === "undefined") {
            throw new Error("XLSX library is not loaded");
        }

        // Prepare worksheet data (same fields as PDF)
        const worksheetData = data.map((s) => ({
            Category: s.category || "",
            Supplier: s.supplier || "",
            Product: s.itemName || "",
            Quantity: s.quantity || "",
            Price: s.unitPrice || "",
            Value: s.totalPrice || "",
            "Operation Type": s.operationType || "",
            "Stock Type": s.type || "",
        }));

        // Create workbook and worksheet
        const workbook = XLSX.utils.book_new();
        const worksheet = XLSX.utils.json_to_sheet(worksheetData);

        // Set column widths
        worksheet["!cols"] = [
            { wch: 20 }, // Category
            { wch: 20 }, // Supplier
            { wch: 25 }, // Product
            { wch: 10 }, // Quantity
            { wch: 15 }, // Price
            { wch: 15 }, // Value
            { wch: 20 }, // Operation Type
            { wch: 20 }, // Stock Type
        ];

        // Add worksheet to workbook
        XLSX.utils.book_append_sheet(workbook, worksheet, "Logs Moments");

        // Metadata sheet
        const metaData = [
            { Info: "Generated On", Value: new Date().toLocaleString() },
            { Info: "Total Records", Value: data.length },
            { Info: "Exported By", Value: "Logs Management System" },
        ];
        const metaSheet = XLSX.utils.json_to_sheet(metaData);
        XLSX.utils.book_append_sheet(workbook, metaSheet, "Report Info");

        // Generate file name
        const fileName = `logs_moment_${
            new Date().toISOString().split("T")[0]
        }.xlsx`;

        // Save the file
        XLSX.writeFile(workbook, fileName);

        toast.success("Excel file downloaded successfully ✅", {
            autoClose: 2500,
        });
    } catch (error) {
        console.error("Excel generation error:", error);
        toast.error(`Excel generation failed: ${error.message}`, {
            autoClose: 5000,
        });
    }
};

// expiry date color coding
const MS_PER_DAY = 86400000;

const toDateOnly = (v) => {
    if (!v) return null;
    if (typeof v === "string") {
        const m = v.match(/^\d{4}-\d{2}-\d{2}/);
        if (m) return new Date(m[0] + "T00:00:00");
    }
    const d = new Date(v);
    if (isNaN(d)) return null;
    return new Date(d.getFullYear(), d.getMonth(), d.getDate());
};

const daysUntil = (v) => {
    const d = toDateOnly(v);
    if (!d) return null;
    const today = new Date();
    const t0 = new Date(today.getFullYear(), today.getMonth(), today.getDate());
    return Math.ceil((d - t0) / MS_PER_DAY);
};

const expiryBadgeClass = (v) => {
    const n = daysUntil(v);
    if (n === null) return "bg-secondary";
    if (n < 0) return "bg-danger"; // expired
    if (n < 15) return "bg-danger"; // < 15 days → red
    if (n < 30) return "bg-warning text-dark"; // < 30 days → yellow
    return "bg-success"; // else → green
};

// operation type text class
// "inventory_stockin" -> "Inventory Stockin", etc.
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

// Text color classes (Bootstrap)
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

// calculate total price when quantity or unit price changes
const toNum = (v) =>
    v === "" || v === null || v === undefined ? 0 : Number(v);
const round2 = (n) => Math.round((n + Number.EPSILON) * 100) / 100;

watch(
    () => [editForm.value.quantity, editForm.value.unitPrice],
    ([q, p]) => {
        const total = toNum(q) * toNum(p);
        editForm.value.totalPrice = round2(total);
    },
    { immediate: true }
);
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
                                        class="btn btn-outline-secondary rounded-pill px-4 dropdown-toggle"
                                        data-bs-toggle="dropdown"
                                    >
                                        Download all
                                    </button>
                                    <ul
                                        class="dropdown-menu dropdown-menu-end shadow rounded-4 py-2"
                                    >
                                        <li>
                                            <a
                                                class="dropdown-item py-2"
                                                href="javascript:;"
                                                @click="onDownload('pdf')"
                                                >Download as PDF</a
                                            >
                                        </li>
                                        <li>
                                            <a
                                                class="dropdown-item py-2"
                                                href="javascript:;"
                                                @click="onDownload('excel')"
                                                >Download as Excel</a
                                            >
                                        </li>
                                        <li>
                                            <a
                                                class="dropdown-item py-2"
                                                href="javascript:;"
                                                @click="onDownload('csv')"
                                            >
                                                Download as CSV
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
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
                                            <td>{{ row.category.name }}</td>
                                            <td>{{ money(row.unitPrice) }}</td>
                                            <td>
                                                {{ fmtDateTime(row.dateTime) }}
                                            </td>
                                            <td>
                                                <template v-if="row.expiryDate">
                                                    <span
                                                        :class="[
                                                            'badge',
                                                            'rounded-pill',
                                                            expiryBadgeClass(
                                                                row.expiryDate
                                                            ),
                                                        ]"
                                                        :title="
                                                            daysUntil(
                                                                row.expiryDate
                                                            ) !== null
                                                                ? daysUntil(
                                                                      row.expiryDate
                                                                  ) < 0
                                                                    ? Math.abs(
                                                                          daysUntil(
                                                                              row.expiryDate
                                                                          )
                                                                      ) +
                                                                      ' day(s) ago'
                                                                    : daysUntil(
                                                                          row.expiryDate
                                                                      ) +
                                                                      ' day(s) left'
                                                                : ''
                                                        "
                                                    >
                                                        {{
                                                            fmtDate(
                                                                row.expiryDate
                                                            )
                                                        }}
                                                    </span>
                                                    <!-- Optional helper text -->
                                                    <small
                                                        class="text-muted ms-2"
                                                    >
                                                        {{
                                                            daysUntil(
                                                                row.expiryDate
                                                            ) < 0
                                                                ? Math.abs(
                                                                      daysUntil(
                                                                          row.expiryDate
                                                                      )
                                                                  ) +
                                                                  " day(s) ago"
                                                                : daysUntil(
                                                                      row.expiryDate
                                                                  ) +
                                                                  " day(s) left"
                                                        }}
                                                    </small>
                                                </template>
                                                <span v-else class="text-muted"
                                                    >—</span
                                                >
                                            </td>

                                            <td>{{ row.quantity }}</td>
                                            <!-- Operation Type -->
                                            <td class="text-break">
                                                <template
                                                    v-if="row.operationType"
                                                >
                                                    <span
                                                        :class="[
                                                            'fw-semibold',
                                                            opTextClass(
                                                                row.operationType
                                                            ),
                                                        ]"
                                                    >
                                                        {{
                                                            formatOpType(
                                                                row.operationType
                                                            )
                                                        }}
                                                    </span>
                                                </template>
                                                <span v-else class="text-muted"
                                                    >—</span
                                                >
                                            </td>

                                            <!-- Type -->
                                            <td>
                                                <template v-if="row.type">
                                                    <span
                                                        :class="[
                                                            'fw-semibold',
                                                            typeTextClass(
                                                                row.type
                                                            ),
                                                        ]"
                                                    >
                                                        {{
                                                            formatType(row.type)
                                                        }}
                                                    </span>
                                                </template>
                                                <span v-else class="text-muted"
                                                    >—</span
                                                >
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
                                        <!-- separator line between rows to match screenshot -->
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
                            <div
                                class="modal-dialog modal-lg modal-dialog-centered"
                            >
                                <div
                                    class="modal-content rounded-4 shadow-lg border-0"
                                >
                                    <!-- Header -->
                                    <div
                                        class="modal-header align-items-center"
                                    >
                                        <div
                                            class="d-flex align-items-center gap-2"
                                        >
                                            <span
                                                class="badge bg-primary rounded-circle p-2"
                                            >
                                                <!-- box icon -->
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
                                                <h5 class="modal-title mb-0">
                                                    View Stock Log
                                                </h5>
                                                <small class="text-muted"
                                                    >Item:
                                                    {{
                                                        selectedLog?.itemName ??
                                                        "—"
                                                    }}</small
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

                                    <!-- Body -->
                                    <div class="modal-body p-4 bg-light">
                                        <div v-if="selectedLog" class="row g-4">
                                            <!-- LEFT: Details card -->
                                            <div class="col-lg-12">
                                                <div
                                                    class="card border-0 shadow-sm rounded-4 h-100"
                                                >
                                                    <div class="card-body">
                                                        <!-- Top: Key attributes (like screenshot style) -->
                                                        <h6
                                                            class="fw-semibold mb-3"
                                                        >
                                                            Details
                                                        </h6>

                                                        <div class="row g-3">
                                                            <div
                                                                class="col-md-6"
                                                            >
                                                                <small
                                                                    class="text-muted d-block"
                                                                    >Category</small
                                                                >
                                                                <div
                                                                    class="fw-semibold"
                                                                >
                                                                    {{
                                                                        selectedLog
                                                                            .category
                                                                            ?.name ??
                                                                        "—"
                                                                    }}
                                                                </div>
                                                            </div>

                                                            <div
                                                                class="col-md-6"
                                                            >
                                                                <small
                                                                    class="text-muted d-block"
                                                                    >Operation</small
                                                                >
                                                                <div
                                                                    class="fw-semibold"
                                                                >
                                                                    {{
                                                                        selectedLog.operationType ??
                                                                        "—"
                                                                    }}
                                                                </div>
                                                            </div>

                                                            <div
                                                                class="col-md-6"
                                                            >
                                                                <small
                                                                    class="text-muted d-block"
                                                                    >Quantity</small
                                                                >
                                                                <div
                                                                    class="fw-semibold"
                                                                >
                                                                    {{
                                                                        selectedLog.quantity ??
                                                                        "—"
                                                                    }}
                                                                </div>
                                                            </div>

                                                            <div
                                                                class="col-md-6"
                                                            >
                                                                <small
                                                                    class="text-muted d-block"
                                                                    >Type</small
                                                                >
                                                                <span
                                                                    class="badge rounded-pill"
                                                                    :class="
                                                                        selectedLog.type ===
                                                                        'stockin'
                                                                            ? 'bg-success'
                                                                            : 'bg-danger'
                                                                    "
                                                                >
                                                                    {{
                                                                        selectedLog.type ??
                                                                        "—"
                                                                    }}
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <hr class="my-4" />

                                                        <!-- Prices row (centered boxes like KPIs) -->
                                                        <div
                                                            class="row g-3 text-center"
                                                        >
                                                            <div
                                                                class="col-md-4"
                                                            >
                                                                <div
                                                                    class="p-3 bg-light rounded-3"
                                                                >
                                                                    <small
                                                                        class="text-muted d-block"
                                                                        >Unit
                                                                        Price</small
                                                                    >
                                                                    <div
                                                                        class="fs-6 fw-semibold"
                                                                    >
                                                                        {{
                                                                            money(
                                                                                selectedLog.unitPrice ??
                                                                                    0
                                                                            )
                                                                        }}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="col-md-4"
                                                            >
                                                                <div
                                                                    class="p-3 bg-light rounded-3"
                                                                >
                                                                    <small
                                                                        class="text-muted d-block"
                                                                        >Total
                                                                        Price</small
                                                                    >
                                                                    <div
                                                                        class="fs-6 fw-semibold"
                                                                    >
                                                                        {{
                                                                            money(
                                                                                selectedLog.totalPrice ??
                                                                                    0
                                                                            )
                                                                        }}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="col-md-4"
                                                            >
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

                                                        <hr class="my-4" />

                                                        <!-- Dates section -->
                                                        <div class="row g-3">
                                                            <div
                                                                class="col-md-6"
                                                            >
                                                                <small
                                                                    class="text-muted d-block"
                                                                    >Recorded
                                                                    On</small
                                                                >
                                                                <div
                                                                    class="fw-semibold"
                                                                >
                                                                    {{
                                                                        fmtDateTime(
                                                                            selectedLog.dateTime
                                                                        )
                                                                    }}
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="col-md-6"
                                                            >
                                                                <small
                                                                    class="text-muted d-block"
                                                                    >Expiry
                                                                    Date</small
                                                                >
                                                                <div
                                                                    class="fw-semibold"
                                                                >
                                                                    {{
                                                                        selectedLog.expiryDate
                                                                            ? fmtDate(
                                                                                  selectedLog.expiryDate
                                                                              )
                                                                            : "—"
                                                                    }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- (No footer needed, like screenshot) -->
                                </div>
                            </div>
                        </div>

                        <!-- Edit Log Modal -->
                        <div
                            class="modal fade"
                            id="editLogModal"
                            tabindex="-1"
                            aria-hidden="true"
                        >
                            <div
                                class="modal-dialog modal-lg modal-dialog-centered"
                            >
                                <div class="modal-content rounded-4">
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-semibold">
                                            Edit Stock Log
                                        </h5>
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
                                                <label class="form-label"
                                                    >Item Name</label
                                                >
                                                <input
                                                    type="text"
                                                    class="form-control"
                                                    v-model="editForm.itemName"
                                                    disabled
                                                />
                                            </div>

                                            <div class="col-md-6 col-md-12">
                                                <label class="form-label"
                                                    >Quantity</label
                                                >
                                                <input
                                                    type="number"
                                                    class="form-control"
                                                    v-model="editForm.quantity"
                                                    :class="{ 'is-invalid': formErrors.quantity }"
                                                    required
                                                />
                                               
                                                <small v-if="formErrors.quantity" class="text-danger">
                                                    {{ formErrors.quantity[0] }}
                                                </small>
                                            </div>

                                            <div class="col-md-6 col-md-12">
                                                <label class="form-label"
                                                    >Unit Price</label
                                                >
                                                <input
                                                    type="number"
                                                    class="form-control"
                                                    v-model="editForm.unitPrice"
                                                    
                                                    :class="{ 'is-invalid': formErrors.unitPrice }"
                                                    required
                                                />
                                                
                                                <small v-if="formErrors.unitPrice" class="text-danger">
                                                    {{ formErrors.unitPrice[0] }}
                                                </small>
                                            </div>
                                            <div class="col-md-6 col-md-12">
                                                <label class="form-label"
                                                    >Value</label
                                                >
                                                <input
                                                    type="number"
                                                    readonly
                                                    class="form-control"
                                                    v-model="
                                                        editForm.totalPrice
                                                    "
                                                />
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <label class="form-label"
                                                    >Expiry Date</label
                                                >
                                                <!-- remove the debug {{ editForm.expiryDate }} line -->
                                                <input
                                                    type="date"
                                                    class="form-control"
                                                    v-model="expiryDateModel"
                                                />
                                            </div>

                                            <!-- <div class="mb-3">
                                                <label class="form-label">Operation Type</label>
                                                <select class="form-select" v-model="editForm.operationType">
                                                    <option value="purchase">Purchase</option>
                                                    <option value="sale">Sale</option>
                                                    <option value="adjustment">Adjustment</option>
                                                </select>
                                            </div> -->

                                            <div class="mt-4">
                                                <button
                                                    class="btn btn-primary rounded-pill px-4 d-inline-flex align-items-center gap-2"
                                                    :disabled="isUpdating"
                                                    :aria-busy="
                                                        isUpdating
                                                            ? 'true'
                                                            : 'false'
                                                    "
                                                    @click="updateLog"
                                                >
                                                    <span
                                                        v-if="isUpdating"
                                                        class="spinner-border spinner-border-sm"
                                                        role="status"
                                                        aria-hidden="true"
                                                    ></span>
                                                    <span>{{
                                                        isUpdating
                                                            ? "Updating…"
                                                            : "Update"
                                                    }}</span>
                                                </button>
                                            </div>
                                        </div>
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
</style>
