<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted } from "vue";
import { toast } from "vue3-toastify";
import { jsPDF } from "jspdf";
import autoTable from "jspdf-autotable";
import * as XLSX from "xlsx";
import axios from "axios";

import StockInLogs from "./components/StockInLogs.vue";
import StockOutLogs from "./components/StockOutLogs.vue";

const logs = ref([]);
const q = ref("");
const activeTab = ref("stockin"); // 'stockin' | 'stockout'

const fetchLogs = async () => {
  try {
    const { data } = await axios.get("stock_entries/stock-logs");
    logs.value = data;
  } catch (e) {
    toast.error("Failed to fetch logs", e);
  }
};
onMounted(fetchLogs);

/* ------- Search (same logic) ------- */
const filtered = computed(() => {
  const t = q.value.trim().toLowerCase();
  if (!t) return logs.value;
  return logs.value.filter((r) =>
    [
      r.itemName,
      r.category?.name || r.category || "",
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

/* ------- Downloads (same behavior) ------- */
const onDownload = (type) => {
  if (!logs.value || logs.value.length === 0) {
    toast.error("No Allergies data to download");
    return;
  }
  const dataToExport = q.value.trim() ? filtered.value : logs.value;
  if (dataToExport.length === 0) {
    toast.error("No Logs Moment found to download");
    return;
  }
  try {
    if (type === "pdf") downloadPDF(dataToExport);
    else if (type === "excel") downloadExcel(dataToExport);
    else if (type === "csv") downloadCSV(dataToExport);
    else toast.error("Invalid download type");
  } catch (error) {
    console.error("Download failed:", error);
    toast.error(`Download failed: ${error.message}`);
  }
};

const downloadCSV = (data) => {
  try {
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
    const rows = data.map((s) => [
      `"${(s.category?.name || s.category || "").toString().replace(/"/g, '""')}"`,
      `"${(s.supplier || "").toString().replace(/"/g, '""')}"`,
      `"${(s.itemName || "").toString().replace(/"/g, '""')}"`,
      `"${s.quantity ?? ""}"`,
      `"${s.unitPrice ?? ""}"`,
      `"${s.totalPrice ?? ""}"`,
      `"${(s.operationType || "").toString().replace(/"/g, '""')}"`,
      `"${(s.type || "").toString().replace(/"/g, '""')}"`,
    ]);
    const csvContent = [headers.join(","), ...rows.map((r) => r.join(","))].join("\n");
    const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.setAttribute("href", url);
    link.setAttribute("download", `logs_moment_${new Date().toISOString().split("T")[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    toast.success("CSV downloaded successfully");
  } catch (error) {
    console.error("CSV generation error:", error);
    toast.error(`CSV generation failed: ${error.message}`, { autoClose: 5000 });
  }
};

const downloadPDF = (data) => {
  try {
    const doc = new jsPDF("p", "mm", "a4");
    doc.setFontSize(20);
    doc.setFont("helvetica", "bold");
    doc.text("Logs Moment Report", 70, 20);
    doc.setFontSize(10);
    doc.setFont("helvetica", "normal");
    const currentDate = new Date().toLocaleString();
    doc.text(`Generated on: ${currentDate}`, 70, 28);
    doc.text(`Total Logs: ${data.length}`, 70, 34);

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
      s.category?.name || s.category || "",
      s.supplier || "",
      s.itemName || "",
      s.quantity ?? "",
      s.unitPrice ?? "",
      s.totalPrice ?? "",
      s.operationType || "",
      s.type || "",
    ]);

    autoTable(doc, {
      head: [tableColumns],
      body: tableRows,
      startY: 40,
      styles: { fontSize: 8, cellPadding: 2, halign: "left", lineColor: [0, 0, 0], lineWidth: 0.1 },
      headStyles: { fillColor: [41, 128, 185], textColor: 255, fontStyle: "bold" },
      alternateRowStyles: { fillColor: [240, 240, 240] },
      margin: { left: 14, right: 14 },
      didDrawPage: (tableData) => {
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

    const fileName = `logs_moment_${new Date().toISOString().split("T")[0]}.pdf`;
    doc.save(fileName);
    toast.success("PDF downloaded successfully");
  } catch (error) {
    console.error("PDF generation error:", error);
    toast.error(`PDF generation failed: ${error.message}`, { autoClose: 5000 });
  }
};

const downloadExcel = (data) => {
  try {
    if (typeof XLSX === "undefined") throw new Error("XLSX library is not loaded");
    const worksheetData = data.map((s) => ({
      Category: s.category?.name || s.category || "",
      Supplier: s.supplier || "",
      Product: s.itemName || "",
      Quantity: s.quantity ?? "",
      Price: s.unitPrice ?? "",
      Value: s.totalPrice ?? "",
      "Operation Type": s.operationType || "",
      "Stock Type": s.type || "",
    }));
    const workbook = XLSX.utils.book_new();
    const worksheet = XLSX.utils.json_to_sheet(worksheetData);
    worksheet["!cols"] = [
      { wch: 20 }, { wch: 20 }, { wch: 25 }, { wch: 10 },
      { wch: 15 }, { wch: 15 }, { wch: 20 }, { wch: 20 },
    ];
    XLSX.utils.book_append_sheet(workbook, worksheet, "Logs Moments");

    const metaData = [
      { Info: "Generated On", Value: new Date().toLocaleString() },
      { Info: "Total Records", Value: data.length },
      { Info: "Exported By", Value: "Logs Management System" },
    ];
    const metaSheet = XLSX.utils.json_to_sheet(metaData);
    XLSX.utils.book_append_sheet(workbook, metaSheet, "Report Info");

    const fileName = `logs_moment_${new Date().toISOString().split("T")[0]}.xlsx`;
    XLSX.writeFile(workbook, fileName);
    toast.success("Excel file downloaded successfully ✅", { autoClose: 2500 });
  } catch (error) {
    console.error("Excel generation error:", error);
    toast.error(`Excel generation failed: ${error.message}`, { autoClose: 5000 });
  }
};
</script>

<template>
  <Master>
    <div class="page-wrapper">
      <div class="container-fluid py-3">
        <div class="card border-0 shadow-lg rounded-4">
          <div class="card-body p-4">
            <!-- Header with search + downloads -->
            <div class="d-flex align-items-center justify-content-between mb-3">
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
                  <ul class="dropdown-menu dropdown-menu-end shadow rounded-4 py-2">
                    <li>
                      <a class="dropdown-item py-2" href="javascript:;" @click="onDownload('pdf')">
                        Download as PDF
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item py-2" href="javascript:;" @click="onDownload('excel')">
                        Download as Excel
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item py-2" href="javascript:;" @click="onDownload('csv')">
                        Download as CSV
                      </a>
                    </li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- Underline Tabs like screenshot -->
            <div class="tabs-underline mb-2">
              <button
                class="tab-link"
                :class="{ active: activeTab === 'stockin' }"
                @click="activeTab = 'stockin'"
              >
                Stock In
              </button>
              <button
                class="tab-link"
                :class="{ active: activeTab === 'stockout' }"
                @click="activeTab = 'stockout'"
              >
                Stock Out
              </button>
            </div>
            <div class="border-bottom mb-3"></div>

            <!-- Content -->
            <StockInLogs
              v-if="activeTab === 'stockin'"
              :logs="logs"
              :q="q"
              @updated="fetchLogs"
            />
            <StockOutLogs
              v-else
              :logs="logs"
              :q="q"
              @updated="fetchLogs"
            />
          </div>
        </div>
      </div>
    </div>
  </Master>
</template>

<style scoped>

/* Search pill like other pages */
.search-wrap { position: relative; width: clamp(220px, 28vw, 360px); }
.search-wrap .bi-search {
  position: absolute; left: 12px; top: 50%;
  transform: translateY(-50%); color: #6b7280; font-size: 1rem;
}
.search-input { padding-left: 38px; border-radius: 9999px; background: #fff; }

/* Screenshot-like underline tabs */
.tabs-underline {
  display: flex;
  align-items: center;
  gap: 28px;
}
.tab-link {
  background: none;
  border: 0;
  padding: 0;
  font-size: 1.05rem;
  color: #6c757d;           /* muted like screenshot */
  font-weight: 600;
  position: relative;
}
.tab-link.active {
  color: #111827;           /* darker text when active */
}
.tab-link.active::after {
  content: "";
  display: block;
  height: 4px;
  width: 44px;              /* short underline */
  background: var(--bs-primary); /* uses your theme's primary (purple in screenshot) */
  border-radius: 9999px;
  margin-top: 6px;
}
</style>
