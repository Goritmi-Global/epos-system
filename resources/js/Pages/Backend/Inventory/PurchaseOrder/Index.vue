<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted, reactive, onUpdated } from "vue";
import { jsPDF } from "jspdf";
import autoTable from "jspdf-autotable";
import * as XLSX from "xlsx";
import { toast } from "vue3-toastify";
import Select from "primevue/select";
import "vue3-toastify/dist/index.css";
import PurchaseComponent from "./PurchaseComponent.vue";
import OrderComponent from "./OrderComponent.vue";

import { Eye, Plus } from "lucide-vue-next";

const money = (n, currency = "GBP") =>
    new Intl.NumberFormat("en-GB", { style: "currency", currency }).format(
        Number(n || 0)
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

const round2 = (n) => Math.round((Number(n) || 0) * 100) / 100;

const showHelp = ref(false);

const props = defineProps({
    orders: Array,
});

const orderData = ref([]);
const fetchPurchaseOrders = async () => {
    const res = await axios.get("/purchase-orders/fetchOrders");

    orderData.value = res.data.data.data;
};

const supplierOptions = ref([]);

const fetchSuppliers = async () => {
    const res = await axios.get("/suppliers/pluck");
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

onMounted(() => {
    fetchInventory();
    fetchSuppliers();
    Purchase();
    fetchPurchaseOrders();
});
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

const orders = ref([]);
const loading = ref(false);

// Search & filter
const q = ref(""); // search input
const statusFilter = ref(""); // optional status filter

// Fetch orders from API
async function Purchase() {
    loading.value = true;
    try {
        const res = await axios.get("/purchase-orders", {
            params: {
                q: q.value,
                status: statusFilter.value || undefined,
            },
        });
        orders.value = res.data;
    } catch (err) {
        console.error("Failed to fetch orders:", err);
        orders.value = [];
    } finally {
        loading.value = false;
    }
}

const selectedOrder = ref(null);
const editItems = ref([]);
const isEditing = ref(false);

// Open modal function
async function openModal(order) {
    try {
        // Fetch full order details with items
        const response = await axios.get(`/purchase-orders/${order.id}`);
        selectedOrder.value = response.data;

        if (order.status === "pending") {
            isEditing.value = true;
            editItems.value = selectedOrder.value.items.map((item) => ({
                id: item.id,
                product_id: item.product_id,
                name: item.product?.name || "Unknown Product",
                quantity: item.quantity,
                unit_price: item.unit_price,
                expiry: item.expiry || "",
                sub_total: item.sub_total,
            }));

            // Show edit modal
            const modal = new bootstrap.Modal(
                document.getElementById("editOrderModal")
            );
            modal.show();
        } else {
            // For completed orders, show view-only modal
            isEditing.value = false;
            const modal = new bootstrap.Modal(
                document.getElementById("viewOrderModal")
            );
            modal.show();
        }
    } catch (error) {
        console.error("Error fetching order details:", error);
        toast.error("Failed to load order details");
    }
}

// Update order function
const updating = ref(false);

async function updateOrder() {
    if (!selectedOrder.value) return;

    updating.value = true;
    try {
        const payload = {
            status: "completed",
            items: editItems.value.map((item) => ({
                purchase_id: selectedOrder.value.id,
                product_id: item.product_id,
                qty: item.quantity, // Changed from 'quantity' to 'qty'
                unit_price: item.unit_price,
                expiry: item.expiry || null,
            })),
        };
        console.log("Update payload:", payload);
        await axios.put(`/purchase-orders/${selectedOrder.value.id}`, payload);

        // Close modal and refresh orders
        const modal = bootstrap.Modal.getInstance(
            document.getElementById("editOrderModal")
        );
        modal?.hide();

        // Refresh the orders list
        await fetchOrders();

        toast.error("Order updated successfully and stock entries created!");
    } catch (error) {
        console.error("Error updating order:", error);
        toast.error("Failed to update order");
    } finally {
        updating.value = false;
    }
}

// Calculate subtotal for editing
function calculateSubtotal(item) {
    const quantity = parseFloat(item.quantity) || 0;
    const unitPrice = parseFloat(item.unit_price) || 0;
    item.sub_total = (quantity * unitPrice).toFixed(2);
}

/* -------------------- Helpers for normalization -------------------- */
const extractArray = (maybe) => {
    if (!maybe) return [];
    if (Array.isArray(maybe)) return maybe;
    if (Array.isArray(maybe.data)) return maybe.data;
    return [];
};

const extractSupplierName = (o) => {
    if (!o) return "";
    if (o.supplier && typeof o.supplier === "object")
        return o.supplier.name || o.supplier.title || "";
    if (o.supplier_name) return o.supplier_name;
    if (o.supplierId) return String(o.supplierId);
    if (o.supplier_id) {
        // try lookup from supplierOptions if available
        const s = supplierOptions.value?.find(
            (sp) => String(sp.id) === String(o.supplier_id)
        );
        return s?.name || String(o.supplier_id);
    }
    // fallback plain string
    return typeof o.supplier === "string" ? o.supplier : "";
};

const extractTotalAmount = (o) => {
    if (!o) return 0;
    if (o.total_amount != null) return Number(o.total_amount);
    if (o.total != null) return Number(o.total);
    if (o.amount != null) return Number(o.amount);
    // try to compute from items if present
    if (Array.isArray(o.items)) {
        return o.items.reduce((sum, it) => {
            const sub =
                it.sub_total ??
                it.subTotal ??
                it.total ??
                (it.quantity || 0) * (it.unit_price ?? it.unitPrice ?? 0);
            return sum + Number(sub || 0);
        }, 0);
    }
    return 0;
};

const extractPurchaseDate = (o) => {
    if (!o) return "";
    return o.purchase_date || o.purchased_at || o.date || o.created_at || "";
};

const normalizeOrders = (arr) =>
    arr.map((o) => ({
        supplier_name: extractSupplierName(o),
        purchase_date: extractPurchaseDate(o),
        status: o.status || "",
        total_amount: extractTotalAmount(o),
        _raw: o, // keep original if needed
    }));

/* -------------------- Updated onDownload -------------------- */
const onDownload = (type) => {
    // pick the best source available (client-fetched orders OR props)
    let source = extractArray(orders.value);
    if (!source.length) source = extractArray(orderData.value); // props.orders.data
    if (!source.length) source = extractArray(props.orders); // fallback

    if (!source.length) {
        toast.error("No Orders data to download");
        return;
    }

    // apply client-side search filter (q) if present
    const t = q.value?.trim()?.toLowerCase();
    const filteredSource = t
        ? source.filter((o) => {
              const hay = [
                  extractSupplierName(o),
                  o.status || "",
                  String(extractTotalAmount(o)),
                  extractPurchaseDate(o),
              ]
                  .join(" ")
                  .toLowerCase();
              return hay.includes(t);
          })
        : source;

    if (!filteredSource.length) {
        toast.error("No Orders found to download");
        return;
    }

    const normalized = normalizeOrders(filteredSource);

    try {
        if (type === "pdf") {
            downloadPDF(normalized);
        } else if (type === "excel") {
            downloadExcel(normalized);
        } else if (type === "csv") {
            downloadCSV(normalized);
        } else {
            toast.error("Invalid download type");
        }
    } catch (error) {
        console.error("Download failed:", error);
        toast.error(`Download failed: ${error.message}`);
    }
};

/* -------------------- Updated export helpers (work with normalized data) -------------------- */

const downloadCSV = (data) => {
    try {
        const headers = ["Supplier", "Purchase Date", "Status", "Total Amount"];
        const rows = data.map((o) => [
            `"${String(o.supplier_name || "").replace(/"/g, '""')}"`,
            `"${String(o.purchase_date || "")}"`,
            `"${String(o.status || "")}"`,
            `"${o.total_amount ?? 0}"`,
        ]);
        const csvContent = [
            headers.join(","),
            ...rows.map((r) => r.join(",")),
        ].join("\n");
        const blob = new Blob([csvContent], {
            type: "text/csv;charset=utf-8;",
        });
        const link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.setAttribute(
            "download",
            `purchase_orders_${new Date().toISOString().split("T")[0]}.csv`
        );
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        toast.success("CSV downloaded successfully", { autoClose: 2500 });
    } catch (error) {
        console.error("CSV generation error:", error);
        toast.error(`CSV generation failed: ${error.message}`);
    }
};

const downloadPDF = (data) => {
    try {
        const doc = new jsPDF("p", "mm", "a4");
        doc.setFontSize(18);
        doc.text("Purchase Orders Report", 14, 20);
        doc.setFontSize(10);
        doc.text(`Generated on: ${new Date().toLocaleString()}`, 14, 28);
        doc.text(`Total Orders: ${data.length}`, 14, 34);

        const tableColumns = [
            "Supplier",
            "Purchase Date",
            "Status",
            "Total Amount",
        ];
        const tableRows = data.map((o) => [
            o.supplier_name || "",
            o.purchase_date || "",
            o.status || "",
            money(o.total_amount || 0, "GBP"),
        ]);

        autoTable(doc, {
            head: [tableColumns],
            body: tableRows,
            startY: 40,
            styles: { fontSize: 8, cellPadding: 2 },
            headStyles: { fillColor: [41, 128, 185], textColor: 255 },
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

        doc.save(
            `purchase_orders_${new Date().toISOString().split("T")[0]}.pdf`
        );
        toast.success("PDF downloaded successfully", { autoClose: 2500 });
    } catch (error) {
        console.error("PDF generation error:", error);
        toast.error(`PDF generation failed: ${error.message}`);
    }
};

const downloadExcel = (data) => {
    try {
        if (typeof XLSX === "undefined") throw new Error("XLSX not loaded");

        const worksheetData = data.map((o) => ({
            Supplier: o.supplier_name || "",
            "Purchase Date": o.purchase_date || "",
            Status: o.status || "",
            "Total Amount": o.total_amount ?? 0,
        }));

        const workbook = XLSX.utils.book_new();
        const worksheet = XLSX.utils.json_to_sheet(worksheetData);
        worksheet["!cols"] = [
            { wch: 25 },
            { wch: 18 },
            { wch: 15 },
            { wch: 15 },
        ];
        XLSX.utils.book_append_sheet(workbook, worksheet, "Orders");

        const metaData = [
            { Info: "Generated On", Value: new Date().toLocaleString() },
            { Info: "Total Orders", Value: data.length },
            { Info: "Exported By", Value: "Purchase Orders System" },
        ];
        const metaSheet = XLSX.utils.json_to_sheet(metaData);
        XLSX.utils.book_append_sheet(workbook, metaSheet, "Report Info");

        XLSX.writeFile(
            workbook,
            `purchase_orders_${new Date().toISOString().split("T")[0]}.xlsx`
        );
        toast.success("Excel file downloaded successfully", {
            autoClose: 2500,
        });
    } catch (error) {
        console.error("Excel generation error:", error);
        toast.error(`Excel generation failed: ${error.message}`);
    }
};

// ===========================================================

onMounted(() => window.feather?.replace());
onUpdated(() => window.feather?.replace());
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
                            <div class="d-flex align-items-center gap-2">
                                <h3 class="fw-semibold mb-0">Purchase Order</h3>

                                <div class="position-relative">
                                    <button
                                        class="btn btn-link p-0 ms-2"
                                        @click="showHelp = !showHelp"
                                        title="Help"
                                    >
                                        <i
                                            class="bi bi-question-circle fs-5"
                                        ></i>
                                    </button>
                                    <div
                                        v-if="showHelp"
                                        class="help-popover shadow rounded-4 p-3"
                                    >
                                        <p class="mb-2">
                                            This screen allows you to view,
                                            manage, and update all purchase
                                            orders.
                                        </p>
                                        <p class="mb-2">
                                            <strong>Add Purchase</strong>:
                                            create a purchase and stock-in
                                            immediately.
                                        </p>
                                        <p class="mb-0">
                                            <strong>Add Order</strong>: create a
                                            purchase order for later delivery.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 align-items-center">
                                <div class="search-wrap me-1">
                                    <i class="bi bi-search"></i>
                                    <input
                                        v-model="q"
                                        type="text"
                                        class="form-control search-input"
                                        placeholder="Search"
                                    />
                                </div>

                                <button
                                    class="btn btn-primary rounded-pill px-4"
                                    data-bs-toggle="modal"
                                    data-bs-target="#addPurchaseModal"
                                >
                                    Purchase
                                </button>
                                <PurchaseComponent
                                    :suppliers="supplierOptions"
                                    :items="p_filteredInv"
                                    @refresh-data="fetchPurchaseOrders"
                                />

                                <button
                                    class="btn btn-primary rounded-pill px-4"
                                    data-bs-toggle="modal"
                                    data-bs-target="#addOrderModal"
                                >
                                    Order
                                </button>
                                <OrderComponent
                                    :suppliers="supplierOptions"
                                    :items="p_filteredInv"
                                    @refresh-data="fetchPurchaseOrders"
                                />
                                <div class="dropdown">
                                    <button
                                        class="btn btn-outline-secondary rounded-pill px-4 dropdown-toggle"
                                        data-bs-toggle="dropdown"
                                    >
                                        Download
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
                                    <!-- {{ orderData }} -->
                                    <template
                                        v-for="(row, i) in orderData"
                                        :key="row.id"
                                    >
                                        <tr>
                                            <td>{{ i + 1 }}</td>
                                            <td>{{ row.supplier }}</td>
                                            <td class="text-nowrap">
                                                {{
                                                    fmtDateTime(
                                                        row.purchasedAt
                                                    ).split(",")[0]
                                                }},
                                                <div class="small text-muted">
                                                    {{
                                                        fmtDateTime(
                                                            row.purchasedAt
                                                        )
                                                            .split(",")[1]
                                                            ?.trim()
                                                    }}
                                                </div>
                                            </td>
                                            <td class="text-start">
                                                <span
                                                    :class="[
                                                        'badge rounded-pill w-20',
                                                        row.status === 'pending'
                                                            ? 'badge-pending'
                                                            : '',
                                                        row.status ===
                                                        'completed'
                                                            ? 'badge-completed'
                                                            : '',
                                                    ]"
                                                >
                                                    {{ row.status }}
                                                </span>
                                            </td>

                                            <td>{{ money(row.total) }}</td>
                                            <td class="text-end">
                                                <button
                                                    @click="openModal(row)"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#viewItemModal"
                                                    title="View Item"
                                                    class="p-2 rounded-full text-gray-600 hover:bg-gray-100"
                                                >
                                                    <Eye class="w-4 h-4" />
                                                </button>
                                            </td>
                                            <!-- <td class="text-end">
                                                <div class="dropdown">
                                                    
                                                    <button class="btn btn-link text-secondary p-0 fs-5"
                                                        data-bs-toggle="dropdown" aria-expanded="false">⋮</button>
                                                    <ul
                                                        class="dropdown-menu dropdown-menu-end shadow rounded-4 overflow-hidden"
                                                    >
                                                        <li>
                                                            <a
                                                                class="dropdown-item py-2"
                                                                href="javascript:void(0)"
                                                                @click="
                                                                    openModal(
                                                                        row
                                                                    )
                                                                "
                                                                >View</a
                                                            >
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td> -->
                                        </tr>
                                    </template>

                                    <!-- Fix this line -->
                                    <tr v-if="orderData?.length === 0">
                                        <td
                                            colspan="6"
                                            class="text-center text-muted py-4"
                                        >
                                            No purchase orders found.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ====================View Modal either Purchase or Order ==================== -->
                <!-- View Order Modal (Read-only) -->
                <div
                    class="modal fade"
                    id="viewOrderModal"
                    tabindex="-1"
                    aria-hidden="true"
                >
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content rounded-4 shadow-lg border-0">
                            <!-- Header -->
                            <div class="modal-header align-items-center">
                                <div class="d-flex align-items-center gap-2">
                                    <span
                                        class="badge bg-success rounded-circle p-2"
                                    >
                                        <!-- shopping cart icon -->
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
                                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9m-6-9v9"
                                            />
                                        </svg>
                                    </span>
                                    <div class="d-flex flex-column">
                                        <h5 class="modal-title mb-0">
                                            Purchase Details
                                        </h5>
                                        <small class="text-muted">
                                            Supplier:
                                            {{
                                                selectedOrder?.supplier?.name ??
                                                "—"
                                            }}
                                        </small>
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
                            <div
                                class="modal-body p-4 bg-light"
                                v-if="selectedOrder"
                            >
                                <div class="row g-4">
                                    <div class="col-lg-12">
                                        <div
                                            class="card border-0 shadow-sm rounded-4 h-100"
                                        >
                                            <div class="card-body">
                                                <!-- Top section -->
                                                <h6 class="fw-semibold mb-3">
                                                    Order Summary
                                                </h6>

                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <small
                                                            class="text-muted d-block"
                                                            >Purchase
                                                            Date</small
                                                        >
                                                        <div
                                                            class="fw-semibold"
                                                        >
                                                            {{
                                                                fmtDateTime(
                                                                    selectedOrder.purchase_date
                                                                ).split(",")[0]
                                                            }}
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <small
                                                            class="text-muted d-block"
                                                            >Status</small
                                                        >
                                                        <span
                                                            class="badge rounded-pill"
                                                            :class="
                                                                selectedOrder.status ===
                                                                'completed'
                                                                    ? 'bg-success'
                                                                    : 'bg-warning'
                                                            "
                                                        >
                                                            {{
                                                                selectedOrder.status
                                                            }}
                                                        </span>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <small
                                                            class="text-muted d-block"
                                                            >Total Amount</small
                                                        >
                                                        <div
                                                            class="fw-semibold"
                                                        >
                                                            {{
                                                                money(
                                                                    selectedOrder.total_amount
                                                                )
                                                            }}
                                                        </div>
                                                    </div>
                                                </div>

                                                <hr class="my-4" />

                                                <!-- Items Table -->
                                                <h6 class="fw-semibold mb-3">
                                                    Purchased Items
                                                </h6>
                                                <div class="table-responsive">
                                                    <table
                                                        class="table table-bordered align-middle"
                                                    >
                                                        <thead
                                                            class="table-light"
                                                        >
                                                            <tr>
                                                                <th>
                                                                    Product Name
                                                                </th>
                                                                <th>
                                                                    Quantity
                                                                </th>
                                                                <th>
                                                                    Unit Price
                                                                </th>
                                                                <th>
                                                                    Subtotal
                                                                </th>
                                                                <th>
                                                                    Expiry Date
                                                                </th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr
                                                                v-for="item in selectedOrder.items"
                                                                :key="item.id"
                                                            >
                                                                <td>
                                                                    {{
                                                                        item
                                                                            .product
                                                                            ?.name ||
                                                                        "Unknown Product"
                                                                    }}
                                                                </td>
                                                                <td>
                                                                    {{
                                                                        item.quantity
                                                                    }}
                                                                </td>
                                                                <td>
                                                                    {{
                                                                        money(
                                                                            item.unit_price
                                                                        )
                                                                    }}
                                                                </td>
                                                                <td>
                                                                    {{
                                                                        money(
                                                                            item.sub_total
                                                                        )
                                                                    }}
                                                                </td>
                                                                <td>
                                                                    {{
                                                                        item.expiry ||
                                                                        "—"
                                                                    }}
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- No footer, matches other modal -->
                        </div>
                    </div>
                </div>

                <!-- Edit Order Modal (Editable) -->
                <div
                    class="modal fade"
                    id="editOrderModal"
                    tabindex="-1"
                    aria-hidden="true"
                >
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content rounded-4">
                            <div class="modal-header">
                                <h5 class="modal-title fw-semibold">
                                    Purchase Details
                                </h5>
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

                            <div class="modal-body" v-if="selectedOrder">
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <p>
                                            <strong>Supplier:</strong>
                                            {{
                                                selectedOrder.supplier?.name ||
                                                "N/A"
                                            }}
                                        </p>
                                        <p>
                                            <strong>Purchase Date:</strong>
                                            {{
                                                fmtDateTime(
                                                    selectedOrder.purchase_date
                                                ).split(",")[0]
                                            }}
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p>
                                            <strong>Current Status: </strong
                                            >{{ selectedOrder.status }}
                                        </p>
                                        <p>
                                            <strong>Total Price:</strong>
                                            {{
                                                money(
                                                    selectedOrder.total_amount
                                                )
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Editable Items Table -->
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th style="width: 25%">
                                                    Product Name
                                                </th>
                                                <th style="width: 15%">
                                                    Quantity
                                                </th>
                                                <th style="width: 15%">
                                                    Unit Price
                                                </th>
                                                <th style="width: 15%">
                                                    Subtotal
                                                </th>
                                                <th style="width: 20%">
                                                    Expiry Date
                                                </th>
                                                <th style="width: 10%">
                                                    Action
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="(
                                                    item, index
                                                ) in editItems"
                                                :key="item.id || index"
                                            >
                                                <td>
                                                    <input
                                                        v-model="item.name"
                                                        type="text"
                                                        class="form-control"
                                                        readonly
                                                       
                                                    />
                                                </td>
                                                <td>
                                                    <input
                                                        v-model.number="
                                                            item.quantity
                                                        "
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        class="form-control"
                                                        @input="
                                                            calculateSubtotal(
                                                                item
                                                            )
                                                        "
                                                    />
                                                </td>
                                                <td>
                                                    <input
                                                        v-model.number="
                                                            item.unit_price
                                                        "
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        class="form-control"
                                                        @input="
                                                            calculateSubtotal(
                                                                item
                                                            )
                                                        "
                                                    />
                                                </td>
                                                <td>
                                                    <input
                                                        v-model="item.sub_total"
                                                        type="text"
                                                        class="form-control"
                                                        readonly
                                                        style="
                                                            background-color: #f8f9fa;
                                                        "
                                                    />
                                                </td>
                                                <td>
                                                    <!-- {{ item }} -->
                                                    <input
                                                        v-model="item.expiry"
                                                        type="date"
                                                        class="form-control"
                                                    />
                                                </td>
                                                <td>
                                                    <button
                                                        class="btn btn-danger btn-sm"
                                                        @click="
                                                            editItems.splice(
                                                                index,
                                                                1
                                                            )
                                                        "
                                                        type="button"
                                                    >
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr v-if="editItems.length === 0">
                                                <td
                                                    colspan="6"
                                                    class="text-center text-muted py-3"
                                                >
                                                    No items found
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td
                                                    colspan="3"
                                                    class="text-end fw-bold"
                                                >
                                                    Total:
                                                </td>
                                                <td class="fw-bold">
                                                    {{
                                                        money(
                                                            editItems.reduce(
                                                                (sum, item) =>
                                                                    sum +
                                                                    parseFloat(
                                                                        item.sub_total ||
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

                                <div class="alert alert-info mt-3">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Note:</strong> Updating this order
                                    will change its status to "completed" and
                                    create stock entries for all items.
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button
                                    type="button"
                                    class="btn btn-primary rounded-pill px-4 py-2"
                                    @click="updateOrder"
                                    :disabled="
                                        updating || editItems.length === 0
                                    "
                                >
                                    <span v-if="updating">
                                        <span
                                            class="spinner-border spinner-border-sm me-2"
                                        ></span>
                                        Updating...
                                    </span>
                                    <span v-else
                                        >Complete Order & Update Stock</span
                                    >
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-secondary rounded-pill py-2 ms-2"
                                    data-bs-dismiss="modal"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Master>
</template>

<style scoped>
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
</style>
