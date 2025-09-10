<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted, onUpdated } from "vue";
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
                                    <div class="modal-header">
                                        <h5 class="modal-title">Log Details</h5>
                                        <button
                                            class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                                            data-bs-dismiss="modal"
                                            aria-label="Close"
                                            title="Close"
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

                                    <!-- Body -->
                                    <div class="modal-body p-4 bg-light">
                                        <div v-if="selectedLog" class="row g-3">
                                            <!-- Item & Category -->
                                            <div class="col-md-6">
                                                <div
                                                    class="p-3 bg-white rounded-3 shadow-sm h-100"
                                                >
                                                    <h6 class="text-muted mb-1">
                                                        Item
                                                    </h6>
                                                    <p class="fw-semibold mb-0">
                                                        {{
                                                            selectedLog.itemName
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div
                                                    class="p-3 bg-white rounded-3 shadow-sm h-100"
                                                >
                                                    <h6 class="text-muted mb-1">
                                                        Category
                                                    </h6>
                                                    <p class="fw-semibold mb-0">
                                                        {{
                                                            selectedLog.category
                                                                ?.name ?? "—"
                                                        }}
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Quantity & Prices -->
                                            <div class="col-md-4">
                                                <div
                                                    class="p-3 bg-white rounded-3 shadow-sm h-100 text-center"
                                                >
                                                    <h6 class="text-muted mb-1">
                                                        Quantity
                                                    </h6>
                                                    <p class="fw-semibold mb-0">
                                                        {{
                                                            selectedLog.quantity
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div
                                                    class="p-3 bg-white rounded-3 shadow-sm h-100 text-center"
                                                >
                                                    <h6 class="text-muted mb-1">
                                                        Unit Price
                                                    </h6>
                                                    <p class="fw-semibold mb-0">
                                                        {{
                                                            money(
                                                                selectedLog.unitPrice
                                                            )
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div
                                                    class="p-3 bg-white rounded-3 shadow-sm h-100 text-center"
                                                >
                                                    <h6 class="text-muted mb-1">
                                                        Total Price
                                                    </h6>
                                                    <p class="fw-semibold mb-0">
                                                        {{
                                                            money(
                                                                selectedLog.totalPrice
                                                            )
                                                        }}
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Operation & Type -->
                                            <div class="col-md-6">
                                                <div
                                                    class="p-3 bg-white rounded-3 shadow-sm h-100"
                                                >
                                                    <h6 class="text-muted mb-1">
                                                        Operation
                                                    </h6>
                                                    <p class="fw-semibold mb-0">
                                                        {{
                                                            selectedLog.operationType
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div
                                                    class="p-3 bg-white rounded-3 shadow-sm h-100"
                                                >
                                                    <h6 class="text-muted mb-1">
                                                        Type
                                                    </h6>
                                                    <span
                                                        class="badge rounded-pill"
                                                        :class="
                                                            selectedLog.type ===
                                                            'stockin'
                                                                ? 'bg-success'
                                                                : 'bg-danger'
                                                        "
                                                    >
                                                        {{ selectedLog.type }}
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Dates -->
                                            <div class="col-md-6">
                                                <div
                                                    class="p-3 bg-white rounded-3 shadow-sm h-100"
                                                >
                                                    <h6 class="text-muted mb-1">
                                                        Date
                                                    </h6>
                                                    <p class="fw-semibold mb-0">
                                                        {{
                                                            fmtDateTime(
                                                                selectedLog.dateTime
                                                            )
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div
                                                    class="p-3 bg-white rounded-3 shadow-sm h-100"
                                                >
                                                    <h6 class="text-muted mb-1">
                                                        Expiry Date
                                                    </h6>
                                                    <p class="fw-semibold mb-0">
                                                        {{
                                                            selectedLog.expiryDate
                                                                ? fmtDate(
                                                                      selectedLog.expiryDate
                                                                  )
                                                                : "—"
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Footer -->
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
                                            type="button"
                                            class="btn-close"
                                            data-bs-dismiss="modal"
                                        ></button>
                                    </div>
                                    <div class="modal-body">
                                        <form @submit.prevent="updateLog">
                                            <div class="mb-3">
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

                                            <div class="mb-3">
                                                <label class="form-label"
                                                    >Quantity</label
                                                >
                                                <input
                                                    type="number"
                                                    class="form-control"
                                                    v-model="editForm.quantity"
                                                    required
                                                />
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label"
                                                    >Unit Price</label
                                                >
                                                <input
                                                    type="number"
                                                    class="form-control"
                                                    v-model="editForm.unitPrice"
                                                    required
                                                />
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label"
                                                    >Expiry Date</label
                                                >
                                                <input
                                                    type="date"
                                                    class="form-control"
                                                    v-model="
                                                        editForm.expiryDate
                                                    "
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

                                            <button
                                                type="submit"
                                                class="btn btn-primary w-100"
                                            >
                                                Update
                                            </button>
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
</style>
