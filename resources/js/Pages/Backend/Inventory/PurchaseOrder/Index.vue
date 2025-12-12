<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted, watch, onUpdated } from "vue";
import { jsPDF } from "jspdf";
import autoTable from "jspdf-autotable";
import * as XLSX from "xlsx";
import { toast } from "vue3-toastify";
import Select from "primevue/select";
import "vue3-toastify/dist/index.css";
import PurchaseComponent from "./PurchaseComponent.vue";
import OrderComponent from "./OrderComponent.vue";
import axios from "axios";
import { Eye, Plus, Trash2 } from "lucide-vue-next";
import { useDateFormat } from "@vueuse/core";
import { useFormatters } from '@/composables/useFormatters'
import BulkOrderComponent from "./BulkOrderComponent.vue";
import { nextTick } from "vue";
import { Head } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";

const { formatMoney, formatCurrencySymbol, formatNumber, dateFmt } = useFormatters()

/* =============== Helpers =============== */
const money = (n, currency = "GBP") =>
    new Intl.NumberFormat("en-GB", { style: "currency", currency }).format(
        Number(n || 0)
    );
const q = ref("");
const searchKey = ref(Date.now());
const inputId = `search-${Math.random().toString(36).substr(2, 9)}`;
const isReady = ref(false);

const fmtDateTime = (iso) =>
    new Date(iso).toLocaleString("en-GB", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        hour12: true,
    });

const round2 = (n) => Math.round((Number(n) || 0) * 100) / 100;
const showHelp = ref(false);

/* =============== Data you already fetch =============== */
const supplierOptions = ref([]);
const fetchSuppliers = async () => {
    const res = await axios.get("/api/suppliers/pluck");
    supplierOptions.value = res.data;
};

const inventoryItems = ref([]); // holds API data
const p_search = ref("");

const fetchInventory = async () => {
    try {
        const response = await axios.get("/inventory/api-inventories");
        inventoryItems.value = response.data.data;
    } catch (error) {
        console.error("Error fetching inventory:", error);
    }
};

const p_filteredInv = computed(() => {
    const t = p_search.value.trim().toLowerCase();
    if (!t) return inventoryItems.value;
    return inventoryItems.value.filter((i) =>
        [i.name, i.category ?? "", i.subcategory ?? "", String(i.stock ?? "")]
            .join(" ")
            .toLowerCase()
            .includes(t)
    );
});

console.log("p_filteredInv", p_filteredInv);
const filteredOrders = computed(() => {
    const term = p_search.value.trim().toLowerCase();
    if (!term) return orderData.value;

    return orderData.value.filter((row) =>
        [
            row.supplier ?? "",
            row.status ?? "",
            String(row.total ?? ""),
            fmtDateTime(row.purchasedAt) ?? "",
        ]
            .join(" ")
            .toLowerCase()
            .includes(term)
    );
});

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

    fetchInventory();
    fetchSuppliers();
});
/* =========================================================================
   === Pagination code starts here =========================================
   This uses  GLOBAL <Paginator> component. connect in the app.js file 
   ========================================================================= */

const loading = ref(false);

// unified paginator state for the table + global Paginator
const paginator = ref({
    data: [],
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
    from: 0,
    to: 0,
    links: []
});

const orderData = computed(() => paginator.value.data || []);
const meta = computed(() => ({
    current_page: paginator.value.current_page,
    last_page: paginator.value.last_page,
    per_page: paginator.value.per_page,
    total: paginator.value.total,
}));
const links = computed(() => paginator.value.links || []);



/** Safely unwrap different paginator shapes */
function normalizeFromFetchOrders(raw) {
    // common shapes we may get:
    // A) { data: { data: [...], current_page, last_page, per_page, total, links: [...] } }
    // B) { data: [...], current_page, last_page, per_page, total, links: [...] }
    // C) Laravel API Resource style: { data: [...], meta: {...}, links: {...} }

    // prefer inner "data"
    const lvl1 = raw?.data ?? raw;

    // shape A
    if (
        lvl1 &&
        lvl1.data &&
        Array.isArray(lvl1.data) &&
        typeof lvl1.current_page !== "undefined"
    ) {
        return {
            data: lvl1.data,
            current_page: Number(lvl1.current_page) || 1,
            last_page: Number(lvl1.last_page) || 1,
            per_page: Number(lvl1.per_page) || 20,
            total: Number(lvl1.total) || (lvl1.data?.length ?? 0),
            links: Array.isArray(lvl1.links) ? lvl1.links : [],
        };
    }

    // shape B (root is the paginator)
    if (
        raw &&
        raw.data &&
        Array.isArray(raw.data) &&
        typeof raw.current_page !== "undefined"
    ) {
        return {
            data: raw.data,
            current_page: Number(raw.current_page) || 1,
            last_page: Number(raw.last_page) || 1,
            per_page: Number(raw.per_page) || 20,
            total: Number(raw.total) || (raw.data?.length ?? 0),
            links: Array.isArray(raw.links) ? raw.links : [],
        };
    }

    // shape C (API Resource with meta/links objects)
    if (raw && Array.isArray(raw.data) && raw.meta) {
        const m = raw.meta;
        return {
            data: raw.data,
            current_page: Number(m.current_page) || 1,
            last_page: Number(m.last_page) || 1,
            per_page: Number(m.per_page) || 20,
            total: Number(m.total) || (raw.data?.length ?? 0),
            // when links are objects, we’ll synthesize simple numeric buttons
            links: Array.isArray(m.links) ? m.links : [],
        };
    }

    // fallback: just a list
    return {
        data: Array.isArray(lvl1) ? lvl1 : [],
        current_page: 1,
        last_page: 1,
        per_page: perPage.value,
        total: Array.isArray(lvl1) ? lvl1.length : 0,
        links: [],
    };
}

const fetchPurchaseOrders = async (page = null) => {
    loading.value = true;
    try {
        const { data } = await axios.get("/api/purchase-orders/fetch-orders", {
            params: {
                q: q.value,
                page: page || paginator.value.current_page,
                per_page: paginator.value.per_page
            },
        });

        // Update paginator with the response data
        paginator.value = {
            data: data.data || [],
            current_page: data.current_page,
            last_page: data.last_page,
            per_page: data.per_page,
            total: data.total,
            from: data.from,
            to: data.to,
            links: data.links
        };
    } catch (err) {
        console.error("Failed to fetch purchase orders:", err);
        toast.error("Failed to load purchase orders");
    } finally {
        loading.value = false;
    }
};

const handlePageChange = (url) => {
    if (!url) return;
    const urlParams = new URLSearchParams(url.split('?')[1]);
    const page = urlParams.get('page');

    if (page) {
        fetchPurchaseOrders(parseInt(page));
    }
};
// hooked up to the GLOBAL Paginator
function onGo(label) {
    const p = Number(label);
    if (!Number.isFinite(p)) return;
    if (p < 1 || p > meta.value.last_page) return;
    fetchPurchaseOrders(p);
}
function onSize(size) {
    perPage.value = Number(size) || 20;
    fetchPurchaseOrders(1);
}
/* =========================================================================
   === Pagination code ends here ===========================================
   ========================================================================= */

/* =============== Order modal/update (your logic kept) =============== */
const selectedOrder = ref(null);
const editItems = ref([]);
const isEditing = ref(false);
const formError = ref({});
const resetModal = () => {
    formError.value = {};
};
const updating = ref(false);
const isLoading = ref(false);

let searchTimeout = null;
watch(q, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        paginator.value.current_page = 1;
        fetchPurchaseOrders(1);
    }, 500);
});


async function openModal(order) {
    try {
        const res = await axios.get(`/purchase-orders/${order.id}`);
        selectedOrder.value = res.data;
        console.log("selectedOrder.value", selectedOrder.value);

        if (order.status === "pending") {
            isEditing.value = true;
            editItems.value = selectedOrder.value.items.map((item) => ({
                id: item.id,
                product_id: item.product_id,
                name: item.product?.name || "Unknown",
                quantity: item.quantity,
                unit_price: item.unit_price,
                expiry: item.expiry || "",
                sub_total: item.sub_total,
            }));
        } else {
            isEditing.value = false;
        }

        const modal = new bootstrap.Modal(
            document.getElementById("orderDetailsModal")
        );
        modal.show();
    } catch (err) {
        console.error(err);
        toast.error("Failed to load order details");
    }
}

async function updateOrder() {
    if (!selectedOrder.value) return;
    updating.value = true;
    try {
        const payload = {
            status: "completed",
            items: editItems.value.map((item) => ({
                purchase_id: selectedOrder.value.id,
                product_id: item.product_id,
                qty: item.quantity,
                unit_price: item.unit_price,
                expiry: item.expiry || null,
            })),
        };
        await axios.put(`/purchase-orders/${selectedOrder.value.id}`, payload);

        const modal = bootstrap.Modal.getInstance(
            document.getElementById("orderDetailsModal")
        );
        modal?.hide();
        formError.value = {};
        // Refresh the orders list
        await fetchPurchaseOrders();

        // refresh current page after update
        await fetchPurchaseOrders(meta.value.current_page);

        toast.success("Order updated successfully and stock entries created!");
    } catch (error) {
        if (error.response?.status === 422 && error.response?.data?.errors) {
            formError.value = error.response.data.errors;

            // Convert errors object to a single string
            const messages = Object.values(formError.value).flat().join("\n");

            toast.error(messages);
        } else {
            console.error("Error updating order:", error);
            toast.error("Failed to update order");
        }
    } finally {
        updating.value = false;
    }
}

function calculateSubtotal(item) {
    const quantity = parseFloat(item.quantity) || 0;
    const unitPrice = parseFloat(item.unit_price) || 0;
    item.sub_total = (quantity * unitPrice).toFixed(2);
}

// In parent component
const updateSearch = (value) => {
    console.log("Parent: received search update:", value);
    p_search.value = value;
};



const fetchAllDataForExport = async () => {
    try {
        loading.value = true;
        const { data } = await axios.get("/api/purchase-orders/fetch-orders", {
            params: {
                q: q.value,
                page: 1,
                per_page: 10000, // Fetch up to 10,000 records at once
            },
        });

        return data.data || [];
    } catch (err) {
        console.error("Failed to fetch data for export:", err);
        toast.error("Failed to load data for export");
        return [];
    } finally {
        loading.value = false;
    }
};

// ✅ UPDATED: Main download function
const onDownload = async (type) => {
    try {
        loading.value = true;

        // ✅ Fetch ALL data instead of using current page
        const allData = await fetchAllDataForExport();

        if (!allData || allData.length === 0) {
            toast.error("No purchase orders found to download");
            loading.value = false;
            return;
        }

        console.log(`📥 Exporting ${allData.length} purchase orders as ${type.toUpperCase()}`);

        // Export based on type
        if (type === "pdf") {
            downloadPDF(allData);
        } else if (type === "excel") {
            downloadExcel(allData);
        } else if (type === "csv") {
            downloadCSV(allData);
        } else {
            toast.error("Invalid download type");
        }
    } catch (error) {
        console.error("Download failed:", error);
        toast.error(`Download failed: ${error.message}`);
    } finally {
        loading.value = false;
    }
};

// ✅ UPDATED: CSV Download - use data parameter instead of orderData.value
const downloadCSV = (data) => {
    try {
        const headers = ["S.No", "Supplier Name", "Purchase Date", "Status", "Total Value"];

        const rows = data.map((s, index) => [
            index + 1,
            `"${s.supplier || ""}"`,
            `"${fmtDateTime(s.purchasedAt) || ""}"`,
            `"${s.status || ""}"`,
            `"${round2(s.total) || 0}"`,
        ]);

        const csvContent = [
            headers.join(","),
            ...rows.map((r) => r.join(",")),
        ].join("\n");

        const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.download = `purchase_orders_${new Date().toISOString().split("T")[0]}.csv`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);

        toast.success(`CSV downloaded successfully (${data.length} records)`, { autoClose: 2500 });
    } catch (error) {
        console.error("CSV generation error:", error);
        toast.error(`CSV generation failed: ${error.message}`);
    }
};

// ✅ UPDATED: PDF Download - use data parameter instead of orderData.value
const downloadPDF = (data) => {
    try {
        const doc = new jsPDF("p", "mm", "a4");
        doc.setFontSize(18);
        doc.setFont("helvetica", "bold");
        doc.text("Purchase Orders Report", 60, 20);

        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        const currentDate = new Date().toLocaleString();
        doc.text(`Generated on: ${currentDate}`, 14, 28);
        doc.text(`Total Records: ${data.length}`, 14, 33);

        const tableColumns = [
            "S.No",
            "Supplier Name",
            "Purchase Date",
            "Status",
            "Total Value",
        ];

        const tableRows = data.map((s, index) => [
            index + 1,
            s.supplier || "",
            fmtDateTime(s.purchasedAt) || "",
            s.status || "",
            round2(s.total) || 0,
        ]);

        autoTable(doc, {
            head: [tableColumns],
            body: tableRows,
            startY: 40,
            styles: {
                fontSize: 9,
                cellPadding: 2,
                lineWidth: 0.1,
                halign: "left",
            },
            headStyles: {
                fillColor: [41, 128, 185],
                textColor: 255,
                fontStyle: "bold",
            },
            alternateRowStyles: { fillColor: [240, 240, 240] },
            margin: { left: 14, right: 14 },
            didDrawPage: (td) => {
                const pageCount = doc.internal.getNumberOfPages();
                const pageHeight = doc.internal.pageSize.height;
                doc.setFontSize(8);
                doc.text(
                    `Page ${td.pageNumber} of ${pageCount}`,
                    td.settings.margin.left,
                    pageHeight - 10
                );
            },
        });

        const fileName = `purchase_orders_${new Date().toISOString().split("T")[0]}.pdf`;
        doc.save(fileName);

        toast.success(`PDF downloaded successfully (${data.length} records)`, { autoClose: 2500 });
    } catch (error) {
        console.error("PDF generation error:", error);
        toast.error(`PDF generation failed: ${error.message}`);
    }
};

// ✅ UPDATED: EXCEL Download - use data parameter instead of orderData.value
const downloadExcel = (data) => {
    try {
        const worksheetData = data.map((s, index) => ({
            "S.No": index + 1,
            "Supplier Name": s.supplier || "",
            "Purchase Date": fmtDateTime(s.purchasedAt) || "",
            Status: s.status || "",
            "Total Value": round2(s.total) || 0,
        }));

        const workbook = XLSX.utils.book_new();
        const worksheet = XLSX.utils.json_to_sheet(worksheetData);

        worksheet["!cols"] = [
            { wch: 8 },
            { wch: 25 },
            { wch: 25 },
            { wch: 15 },
            { wch: 15 },
        ];

        XLSX.utils.book_append_sheet(workbook, worksheet, "Purchase Orders");

        const metaData = [
            { Info: "Generated On", Value: new Date().toLocaleString() },
            { Info: "Total Records", Value: data.length },
            { Info: "Exported By", Value: "Purchase Order System" },
        ];
        const metaSheet = XLSX.utils.json_to_sheet(metaData);
        XLSX.utils.book_append_sheet(workbook, metaSheet, "Report Info");

        const fileName = `purchase_orders_${new Date().toISOString().split("T")[0]}.xlsx`;
        XLSX.writeFile(workbook, fileName);

        toast.success(`Excel file downloaded successfully (${data.length} records)`, { autoClose: 2500 });
    } catch (error) {
        console.error("Excel generation error:", error);
        toast.error(`Excel generation failed: ${error.message}`);
    }
};

/* =============== Lifecycle =============== */
onMounted(() => {
    fetchPurchaseOrders(1); // initial load
});
onUpdated(() => window.feather?.replace?.());

const downloadInvoice = async (order) => {
    try {
        // Fetch the full order details with items
        const res = await axios.get(`/purchase-orders/${order.id}`);
        const fullOrder = res.data;

        const doc = new jsPDF("p", "mm", "a4");

        // ===== HEADER SECTION =====
        doc.setFontSize(24);
        doc.setFont("helvetica", "bold");
        doc.setTextColor(41, 128, 185); // Blue color
        doc.text("INVOICE", 14, 20);

        // Invoice Details Box
        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        doc.setTextColor(0, 0, 0);

        // Right side - Invoice metadata
        const invoiceX = 140;
        doc.text(`Invoice #: ${fullOrder.id}`, invoiceX, 20);
        doc.text(`Date: ${fmtDateTime(fullOrder.purchasedAt || fullOrder.purchase_date)}`, invoiceX, 26);
        doc.text(`Status: ${fullOrder.status?.toUpperCase() || 'PENDING'}`, invoiceX, 32);

        // ===== COMPANY & SUPPLIER INFO =====
        doc.setFontSize(12);
        doc.setFont("helvetica", "bold");
        doc.text("FROM (Your Company):", 14, 45);

        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        doc.text("Your Company Name", 14, 52);
        doc.text("123 Business Street", 14, 58);
        doc.text("City, Country", 14, 64);
        doc.text("contact@company.com", 14, 70);

        // Supplier Information
        doc.setFontSize(12);
        doc.setFont("helvetica", "bold");
        doc.text("TO (Supplier):", 100, 45);

        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        const supplierName = fullOrder.supplier?.name || fullOrder.supplier || "N/A";
        doc.text(supplierName, 100, 52);
        doc.text("Supplier Details", 100, 58);
        doc.text("Contact: " + (fullOrder.supplier?.contact || "N/A"), 100, 64);
        doc.text("Email: " + (fullOrder.supplier?.email || "N/A"), 100, 70);

        // ===== LINE SEPARATOR =====
        doc.setDrawColor(41, 128, 185);
        doc.setLineWidth(0.5);
        doc.line(14, 78, 196, 78);

        // ===== ITEMS TABLE =====
        const tableColumns = ["Item", "Quantity", "Unit Price", "Subtotal"];
        const tableRows = fullOrder.items.map((item) => [
            item.product?.name || item.name || "Unknown Product",
            item.quantity,
            money(item.unit_price),
            money(item.sub_total || item.quantity * item.unit_price),
        ]);

        autoTable(doc, {
            head: [tableColumns],
            body: tableRows,
            startY: 85,
            styles: {
                fontSize: 10,
                cellPadding: 3,
                lineWidth: 0.1,
                halign: "left",
            },
            headStyles: {
                fillColor: [41, 128, 185],
                textColor: 255,
                fontStyle: "bold",
                halign: "center",
            },
            alternateRowStyles: { fillColor: [245, 245, 245] },
            margin: { left: 14, right: 14 },
            columnStyles: {
                1: { halign: "center" },
                2: { halign: "right" },
                3: { halign: "right" },
            },
        });

        // Get the Y position after the table
        const finalY = doc.lastAutoTable.finalY || 150;

        // ===== TOTALS SECTION =====
        doc.setFontSize(11);
        doc.setFont("helvetica", "bold");

        const totalAmount = fullOrder.items.reduce(
            (sum, item) => sum + (parseFloat(item.sub_total) || 0),
            0
        );

        doc.text(`Total Amount: ${money(totalAmount)}`, 140, finalY + 15);

        // ===== FOOTER =====
        doc.setFontSize(9);
        doc.setFont("helvetica", "normal");
        doc.setTextColor(128, 128, 128);

        const pageHeight = doc.internal.pageSize.height;
        const pageCount = doc.internal.getNumberOfPages();

        // Page number
        doc.text(
            `Page 1 of ${pageCount}`,
            14,
            pageHeight - 10
        );

        // Footer text
        doc.text(
            "Thank you for your business!",
            100,
            pageHeight - 10
        );

        // ===== SAVE PDF =====
        const fileName = `invoice_${fullOrder.id}_${new Date().toISOString().split("T")[0]}.pdf`;
        doc.save(fileName);

        toast.success("Invoice downloaded successfully", { autoClose: 2500 });
    } catch (error) {
        console.error("Invoice generation error:", error);
        toast.error(`Invoice generation failed: ${error.message}`);
    }
};
</script>

<template>
    <Master>

        <Head title="Purchase Order" />
        <div class="page-wrapper">
            <div class="card border-0 shadow-lg rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <h3 class="fw-semibold mb-0">Purchase Order</h3>

                            <div class="position-relative">
                                <button class="btn btn-link p-0 ms-2" @click="showHelp = !showHelp" title="Help">
                                    <i class="bi bi-question-circle fs-5"></i>
                                </button>

                                <div v-if="showHelp"
                                    class="help-popover shadow-lg rounded-3 p-3 bg-white border position-absolute"
                                    style="width: 300px; right: 0; z-index: 999;">
                                    <!-- Close Button -->
                                    <button @click="showHelp = false"
                                        class="btn btn-sm btn-light border-0 position-absolute top-0 end-0 mt-1 me-1 text-secondary"
                                        title="Close">
                                        <i class="bi bi-x-lg"></i>
                                    </button>

                                    <h6 class="fw-semibold mb-3">Purchase Order Help</h6>

                                    <p class="mb-2 small text-muted">
                                        This screen allows you to view, manage, and update all purchase orders.
                                    </p>

                                    <p class="mb-1 small">
                                        <strong>Add Purchase:</strong> Create a purchase and stock-in immediately.
                                    </p>

                                    <p class="small mb-0">
                                        <strong>Add Order:</strong> Create a purchase order for later delivery.
                                    </p>
                                </div>
                            </div>

                        </div>

                        <div class="d-flex gap-2 align-items-center">
                            <div class="search-wrap me-1">
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

                            <button class="btn btn-primary btn-sm py-2 rounded-pill px-4" data-bs-toggle="modal"
                                data-bs-target="#bulkOrderModal">
                                Bulk Purchase
                            </button>

                            <BulkOrderComponent :suppliers="supplierOptions" :items="p_filteredInv"
                                @refresh-data="fetchPurchaseOrders" />


                            <!-- <button class="btn btn-primary btn-sm py-2 rounded-pill px-4" data-bs-toggle="modal"
                                data-bs-target="#addPurchaseModal">
                                Purchase
                            </button> -->
                            <PurchaseComponent :suppliers="supplierOptions" :items="p_filteredInv"
                                @refresh-data="fetchPurchaseOrders" @update:search="p_search = $event" />

                            <button class="btn btn-primary btn-sm py-2 rounded-pill px-4 py-2" data-bs-toggle="modal"
                                data-bs-target="#addOrderModal">
                                Order
                            </button>
                            <OrderComponent :suppliers="supplierOptions" :items="p_filteredInv"
                                @refresh-data="fetchPurchaseOrders" />
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm rounded-pill py-2 px-4 dropdown-toggle"
                                    data-bs-toggle="dropdown">
                                    Export
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow rounded-4 py-2">
                                    <li>
                                        <a class="dropdown-item py-2" href="javascript:;"
                                            @click="onDownload('pdf')">Export as PDF</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2" href="javascript:;"
                                            @click="onDownload('excel')">Export as Excel</a>
                                    </li>

                                    <li>
                                        <a class="dropdown-item py-2" href="javascript:;" @click="onDownload('csv')">
                                            Export as CSV
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="small text-muted">
                                <tr>
                                    <th style="width: 80px">S. #</th>
                                    <th>Supplier Name</th>
                                    <th>Purchase date</th>
                                    <th class="text-start">Status</th>
                                    <th>Total value</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                <!-- Loading State -->
                                <tr v-if="loading">
                                    <td colspan="6" class="text-center py-5">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="text-muted mt-2 mb-0">Loading purchase orders...</p>
                                    </td>
                                </tr>

                                <!-- Data Rows -->
                                <template v-else>
                                    <tr v-for="(row, i) in filteredOrders" :key="row.id">
                                        <td>{{ paginator.from + i }}</td>
                                        <td>{{ row.supplier }}</td>
                                        <td class="text-nowrap">
                                            {{ row.purchasedAt }}
                                            <div class="small text-muted">
                                                {{ dateFmt(row.purchasedAt).split(",")[1]?.trim() }}
                                            </div>
                                        </td>
                                        <td class="text-start">
                                            <span :class="[
                                                'badge rounded-pill',
                                                row.status === 'pending'
                                                    ? 'bg-warning text-dark'
                                                    : row.status === 'completed'
                                                        ? 'bg-success'
                                                        : 'bg-secondary',
                                            ]">
                                                {{ row.status }}
                                            </span>
                                        </td>
                                        <td>{{ formatCurrencySymbol(row.total) }}</td>
                                        <td class="text-end">
                                            <div class="d-flex gap-2 justify-content-end">
                                                <button class="p-2 rounded-pill text-primary hover:bg-gray-100 btn"
                                                    @click="openModal(row)" title="View Item">
                                                    <Eye class="w-4 h-4" />
                                                </button>
                                                <button class="p-2 rounded-pill text-success hover:bg-gray-100 btn"
                                                    @click="downloadInvoice(row)" :disabled="loading"
                                                    title="Print Invoice">
                                                    <i class="bi bi-printer fs-5"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr v-if="filteredOrders.length === 0">
                                        <td colspan="6" class="text-center text-muted py-4">
                                            {{ q.trim() ? "No purchase orders found matching your search." : "No purchase orders found." }}
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="paginator.last_page > 1" class="mt-4 d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Showing {{ paginator.from }} to {{ paginator.to }} of {{ paginator.total }} entries
                        </div>

                        <Pagination :pagination="paginator.links" :isApiDriven="true"
                            @page-changed="handlePageChange" />
                    </div>

                    <!-- your GLOBAL Paginator component -->
                    <!-- <Paginator class="mt-2" :meta="meta" :links="links" :disabled="loading" :show-sizes="true"
                        :sizes="[10, 20, 30, 50, 100]" @go="onGo" @size="onSize" /> -->
                </div>
            </div>

            <!-- ====================View Modal either Purchase or Order ==================== -->
            <!-- Unified Order Modal -->
            <!-- Order / Purchase Details Modal -->
            <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content rounded-4 shadow-lg border-0">
                        <!-- Header -->
                        <div class="modal-header align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-success rounded-circle p-2">
                                    <i class="bi bi-basket"></i>
                                </span>
                                <div class="d-flex flex-column">
                                    <h5 class="modal-title mb-0">
                                        {{
                                            isEditing
                                                ? "Edit Purchase Order"
                                                : "Purchase Details"
                                        }}
                                    </h5>
                                    <small class="text-muted">
                                        Supplier:
                                        {{
                                            selectedOrder?.supplier?.name ?? "—"
                                        }}
                                    </small>
                                </div>
                            </div>
                            <button @click="resetModal"
                                class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                                data-bs-dismiss="modal" aria-label="Close" title="Close">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="modal-body p-4 bg-light" v-if="selectedOrder">
                            <!-- Summary -->
                            <h6 class="fw-semibold mb-3">Order Summary</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Purchase Date</small>
                                    <div class="fw-semibold">
                                        {{
                                            dateFmt(
                                                selectedOrder.purchase_date
                                            ).split(",")[0]
                                        }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Status</small>
                                    <span class="badge rounded-pill" :class="selectedOrder.status === 'completed'
                                        ? 'bg-success'
                                        : 'bg-warning'
                                        ">
                                        {{ selectedOrder.status }}
                                    </span>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Total Amount</small>
                                    <div class="fw-semibold">
                                        {{ formatCurrencySymbol(selectedOrder.total_amount) }}
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4" />

                            <!-- Items Table -->
                            <h6 class="fw-semibold mb-3">
                                {{
                                    isEditing ? "Edit Items" : "Purchased Items"
                                }}
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Product</th>
                                            <th>Qty</th>
                                            <th>Unit Price</th>
                                            <th>Subtotal</th>
                                            <th>Expiry</th>
                                            <th v-if="isEditing">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="black-row" v-for="(item, index) in isEditing
                                            ? editItems
                                            : selectedOrder.items" :key="item.id || index">
                                            <!-- Product -->
                                            <td>
                                                <span v-if="!isEditing">{{
                                                    item.product?.name ||
                                                    item.name ||
                                                    "Unknown Product"
                                                }}</span>
                                                <input v-else v-model="item.name" class="form-control" readonly />
                                            </td>

                                            <!-- Quantity -->
                                            <td>
                                                <span v-if="!isEditing">{{
                                                    formatCurrencySymbol(item.quantity)
                                                }}</span>
                                                <input v-else v-model.number="item.quantity
                                                    " type="number" class="form-control" @input="
                                                        calculateSubtotal(item)
                                                        " />
                                            </td>

                                            <!-- Unit Price -->
                                            <td>
                                                <span v-if="!isEditing">{{
                                                    (item.unit_price)
                                                }}</span>
                                                <input v-else v-model.number="item.unit_price
                                                    " type="number" class="form-control" @input="
                                                        calculateSubtotal(item)
                                                        " />
                                            </td>

                                            <!-- Subtotal -->
                                            <td>
                                                <span v-if="!isEditing">{{
                                                    formatCurrencySymbol(item.sub_total)
                                                }}</span>
                                                <input v-else v-model="item.sub_total" class="form-control" readonly />
                                            </td>

                                            <!-- Expiry -->

                                            <td>
                                                <span v-if="!isEditing">
                                                    {{ dateFmt(item.expiry_date) }}
                                                </span>
                                                <VueDatePicker v-else v-model="item.expiry" :enableTimePicker="false"
                                                    :min-date="new Date()" :teleport="true" :format="'yyyy-MM-dd'"
                                                    placeholder="Select expiry date"
                                                    :class="{ 'is-invalid': formError[`items.${index}.expiry`] }" />

                                                <small v-if="formError[`items.${index}.expiry`]" class="text-danger">
                                                    {{ formError[`items.${index}.expiry`][0] }}
                                                </small>

                                            </td>
                                            <br />

                                            <!-- Action (only in edit mode) -->
                                            <td v-if="isEditing">
                                                <button
                                                    class="p-2 rounded-full transition transform hover:bg-gray-100 hover:scale-110"
                                                    @click="
                                                        editItems.splice(
                                                            index,
                                                            1
                                                        )
                                                        " title="Delete">
                                                    <Trash2 class="w-4 h-4 text-red-500" />
                                                </button>
                                            </td>
                                        </tr>

                                        <tr v-if="
                                            (isEditing
                                                ? editItems.length
                                                : selectedOrder.items
                                                    .length) === 0
                                        ">
                                            <td :colspan="isEditing ? 6 : 5" class="text-center text-muted py-3">
                                                No items found
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="footer">
                                        <tr>
                                            <td colspan="3" class="text-end fw-bold">
                                                Total:
                                            </td>
                                            <td class="fw-bold">
                                                {{
                                                    formatCurrencySymbol(
                                                        (isEditing
                                                            ? editItems
                                                            : selectedOrder.items
                                                        ).reduce(
                                                            (sum, i) =>
                                                                sum +
                                                                parseFloat(
                                                                    i.sub_total ||
                                                                    0
                                                                ),
                                                            0
                                                        )
                                                    )
                                                }}
                                            </td>
                                            <td colspan="2"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <!-- Note (only in edit mode) -->
                            <div v-if="isEditing" class="alert alert-info mt-3">
                                <i class="bi bi-info-circle me-2"></i>
                                Updating will mark this order as completed and
                                create stock entries.
                            </div>
                        </div>

                        <!-- Footer (only for edit mode) -->
                        <div class="modal-footer" v-if="isEditing">
                            <button type="button" class="btn btn-primary rounded-pill px-4 py-2" @click="updateOrder"
                                :disabled="updating || editItems.length === 0">
                                <span v-if="updating">
                                    <span class="spinner-border spinner-border-sm me-2"></span>
                                    Updating...
                                </span>
                                <span v-else>Complete Order & Update Stock</span>
                            </button>
                            <button type="button" class="btn btn-secondary rounded-pill px-4 py-2"
                                data-bs-dismiss="modal">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Master>
</template>

<style scoped>
.dark .modal-footer {
    background-color: #181818 !important;
    /* gray-800 */
    color: #f9fafb !important;
}

.dark .bg-white {
    border-bottom: 1px solid #fff !important;
}

.help-popover {
    animation: fadeIn 0.15s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(5px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.dark .btn-light {
    background: #212121 !important;
    color: #fff !important;
}

/* Search pill */
.search-wrap {
    position: relative;
    width: clamp(240px, 30vw, 420px);
}

.search-wrap .bi-search {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
}

.dark .black-row {
    background-color: #181818 !important;
}

.search-input {
    padding-left: 38px;
    border-radius: 9999px;
    background: #fff;
}

/* Brand buttons */
.btn-primary {
    background: #1c0d82;
    border-color: #1c0d82;
}

.btn-primary:hover {
    filter: brightness(1.05);
}

/* Table look */
.purchase-table thead th {
    font-weight: 600;
    border-bottom: 2px solid #111;
}

/* Help popover */
.help-popover {
    position: absolute;
    left: 12px;
    top: 34px;
    width: min(360px, 70vw);
    background: #fff;
    z-index: 2000;
}

.badge {
    padding: 6px 10px;
    border-radius: 8px;
    color: #fff;
    font-weight: 600;
}

.badge-pending {
    background-color: orange;
}

.badge-completed {
    background-color: green;
}

/* Cart */
.cart thead th {
    font-weight: 600;
}

/* Responsive */
@media (max-width: 575.98px) {
    .search-wrap {
        width: 100%;
    }
}


/* card scrollig */
.purchase-scroll {
    max-height: 65vh;
    /* adjust as you like */
    overflow-y: auto;
    padding-right: 0.25rem;
    /* prevent content jump near scrollbar */
    overscroll-behavior: contain;
}

/* Optional pretty scrollbar */
.purchase-scroll::-webkit-scrollbar {
    width: 8px;
}

.purchase-scroll::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 999px;
}

.purchase-scroll::-webkit-scrollbar-track {
    background: transparent;
}

:global(.dark .form-control:focus) {
    border-color: #fff !important;
}


/* 🎯 iPad Pro 12.9" Portrait (1024 x 1366) */
@media only screen and (min-device-width: 1024px) and (max-device-width: 1366px) and (orientation: portrait) {

    .page-wrapper {
        padding: 12px !important;
    }

    .card {
        border-radius: 16px !important;
    }

    .d-flex.align-items-center.justify-content-between {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 10px;
    }

    .d-flex.gap-2.align-items-center {
        flex-wrap: wrap;
        gap: 10px;
        justify-content: flex-start;
    }

    .search-wrap {
        width: 30% !important;
        margin-bottom: 10px;
    }


    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    table.table th,
    table.table td {
        font-size: 14px;
        white-space: nowrap;
    }
}

/* 🎯 iPad Pro 12.9" Landscape (1366 x 1024) */
@media only screen and (min-device-width: 1024px) and (max-device-width: 1366px) and (orientation: landscape) {

    .page-wrapper {
        padding: 16px !important;
    }

    .card-body {
        padding: 20px !important;
    }

    .d-flex.align-items-center.justify-content-between {
        flex-direction: row;
        flex-wrap: wrap;
        gap: 15px;
    }

    .d-flex.gap-2.align-items-center {
        flex-wrap: wrap;
        gap: 10px;
    }

    .search-wrap {
        min-width: 250px;
    }

    .table-responsive {
        overflow-x: auto;
    }

    table.table th,
    table.table td {
        font-size: 15px;
    }
}


/* Dark mode Multi Select  */
/* ======================== Dark Mode MultiSelect ============================= */
:global(.dark .p-multiselect-header) {
    background-color: #181818 !important;
    color: #fff !important;
}

:global(.dark .p-multiselect-label) {
    color: #fff !important;
}

:global(.dark .p-select .p-component .p-inputwrapper) {
    background: #212121 !important;
    color: #fff !important;
    border-bottom: 1px solid #555 !important;
}

/* Options list container */
:global(.dark .p-multiselect-list) {
    background: #181818 !important;
}

/* Each option */
:global(.dark .p-multiselect-option) {
    background: #212121 !important;
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
    background: #212121 !important;
    color: #fff !important;
    border-color: #555 !important;
}

/* Checkbox box in dropdown */
:global(.dark .p-multiselect-overlay .p-checkbox-box) {
    background: #212121 !important;
    border: 1px solid #555 !important;
}

/* Search filter input */
:global(.dark .p-multiselect-filter) {
    background: #212121 !important;
    color: #fff !important;
    border: 1px solid #555 !important;
}

/* Optional: adjust filter container */
:global(.dark .p-multiselect-filter-container) {
    background: #212121 !important;
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
    background-color: #212121 !important;
    color: #fff !important;
    border-color: #555 !important;
}

/* Options container */
:global(.dark .p-select-list-container) {
    background-color: #212121 !important;
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
