<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted, reactive,  onUpdated } from "vue";
import { jsPDF } from "jspdf";
import autoTable from "jspdf-autotable";
import * as XLSX from "xlsx";
import { toast } from "vue3-toastify";
import Select from "primevue/select";
import "vue3-toastify/dist/index.css";
import PurchaseComponent from "./PurchaseComponent.vue";

import {
    Shapes,
    Package,
    AlertTriangle,
    XCircle,
    Pencil,
    Eye,
    Plus,
} from "lucide-vue-next";

/* =============== Helpers =============== */
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

// const orderData = computed(() => props.orders.data);
const orderData = ref([]);
const fetchPurchaseOrders = async () => {
    const res = await axios.get("/purchase-orders/fetchOrders");
   
    orderData.value = res.data.data.data; // [{id, name}]
};

const supplierOptions = ref([]);
const p_supplier = ref(null);

const fetchSuppliers = async () => {
    const res = await axios.get("/suppliers/pluck");
    supplierOptions.value = res.data; // [{id, name}]
};

// =========================================================================
// Inventory retrieval for Add Purchase modal
// ========================================================================

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

// ========================================================================
// Purchase Items Retrieval
// ========================================================================
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

// Computed filteredOrders for search
// const filteredOrders = computed(() => {
//   const t = q.value.trim().toLowerCase();
//   return props.orders.value.filter((o) => {
//     const str = [o.supplier, o.status, String(o.total), o.purchasedAt].join(" ").toLowerCase();
//     return str.includes(t);
//   });
// });

// ==========================================================================

 
// cart rows: {id,name,category,qty,unitPrice,expiry,cost}

// ===========================Submitting Quick Purchase===========================

/* =========================================================================
   ADD ORDER (later delivery) — MERGE on same product+unitPrice+expiry
   =======================================================================*/

const o_search = ref("");

// cart rows: {id,name,category,qty,unitPrice,expiry,cost}
const o_cart = ref([]);
const o_total = computed(() =>
    round2(o_cart.value.reduce((s, r) => s + Number(r.cost || 0), 0))
);

function addOrderItem(item) {
    const qty = Number(item.qty || 0);
    const price =
        item.unitPrice !== "" && item.unitPrice != null
            ? Number(item.unitPrice)
            : Number(item.defaultPrice || 0);
    const expiry = item.expiry || null;

    if (!qty || qty <= 0) return toast.error("Enter a valid quantity.");
    if (!price || price <= 0) return toast.error("Enter a valid unit price.");
    if (!expiry || expiry <= 0) return toast.error("Enter an expiry date.");

    // MERGE: same product + same unitPrice + same expiry
    const found = o_cart.value.find(
        (r) => r.id === item.id && r.unitPrice === price && r.expiry === expiry
    );

    if (found) {
        found.qty = round2(found.qty + qty);
        found.cost = round2(found.qty * found.unitPrice);
    } else {
        o_cart.value.push({
            id: item.id,
            name: item.name,
            category: item.category,
            qty,
            unitPrice: price,
            expiry,
            cost: round2(qty * price),
        });
    }

    // reset only this item's fields
    item.qty = null;
    item.unitPrice = null;
    item.expiry = null;
}

function delOrderRow(idx) {
    o_cart.value.splice(idx, 1);
}

// ============================================================================
// Submitting Order
// ============================================================================
// Add these to your Vue component's setup() function

// Add these to your Vue component's setup() function

const selectedOrder = ref(null);
const editingOrder = ref(null);
const editItems = ref([]);
const isEditing = ref(false);

// Open modal function
async function openModal(order) {
    try {
        // Fetch full order details with items
        const response = await axios.get(`/purchase-orders/${order.id}`);
        selectedOrder.value = response.data;

        if (order.status === "pending") {
            // For pending orders, make items editable
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
        alert("Failed to load order details");
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

        alert("Order updated successfully and stock entries created!");
    } catch (error) {
        console.error("Error updating order:", error);
        alert("Failed to update order");
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

// ===========================================================

// ===========================================================
// central place to keep form errors
const p_errors = reactive({
    supplier: "",
});
const o_submitting = ref(false);

function orderSubmit() {
    // validate supplier
    if (!p_supplier.value) {
        p_errors.supplier = "Please select a supplier.";
        toast.error("Please select a supplier.");
        // optional: focus the field
        nextTick(() => document.getElementById("supplierSelect")?.focus());
        return;
    }
    // if (!p_supplier.value) return toast.error("Please select a supplier.");
    if (!p_cart.value.length) return toast.error("No items added.");

    const payload = {
        supplier_id: p_supplier.value.id,
        purchase_date: new Date().toISOString().split("T")[0],
        status: "pending",
        items: o_cart.value.map(({ id, qty, unitPrice, expiry }) => ({
            product_id: id,
            quantity: qty,
            unit_price: unitPrice,
            expiry: expiry || null,
        })),
    };

    o_submitting.value = true;

    axios
        .post("/purchase-orders", payload)
        .then((res) => {
            // reset
            o_cart.value = [];
            p_supplier.value = null;
            const m = bootstrap.Modal.getInstance(
                document.getElementById("addOrderModal")
            );
            m?.hide();
        })
        .catch((err) => {
            console.error(
                "❌ Failed to create order:",
                err.response?.data || err.message
            );
            alert("Failed to create order.");
        })
        .finally(() => {
            o_submitting.value = false;
        });
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
  if (o.supplier && typeof o.supplier === "object") return o.supplier.name || o.supplier.title || "";
  if (o.supplier_name) return o.supplier_name;
  if (o.supplierId) return String(o.supplierId);
  if (o.supplier_id) {
    // try lookup from supplierOptions if available
    const s = supplierOptions.value?.find((sp) => String(sp.id) === String(o.supplier_id));
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
      const sub = it.sub_total ?? it.subTotal ?? it.total ?? ( (it.quantity || 0) * (it.unit_price ?? it.unitPrice ?? 0) );
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
    const csvContent = [headers.join(","), ...rows.map((r) => r.join(","))].join("\n");
    const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.setAttribute("download", `purchase_orders_${new Date().toISOString().split("T")[0]}.csv`);
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

    const tableColumns = ["Supplier", "Purchase Date", "Status", "Total Amount"];
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

    doc.save(`purchase_orders_${new Date().toISOString().split("T")[0]}.pdf`);
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
    worksheet["!cols"] = [{ wch: 25 }, { wch: 18 }, { wch: 15 }, { wch: 15 }];
    XLSX.utils.book_append_sheet(workbook, worksheet, "Orders");

    const metaData = [
      { Info: "Generated On", Value: new Date().toLocaleString() },
      { Info: "Total Orders", Value: data.length },
      { Info: "Exported By", Value: "Purchase Orders System" },
    ];
    const metaSheet = XLSX.utils.json_to_sheet(metaData);
    XLSX.utils.book_append_sheet(workbook, metaSheet, "Report Info");

    XLSX.writeFile(workbook, `purchase_orders_${new Date().toISOString().split("T")[0]}.xlsx`);
    toast.success("Excel file downloaded successfully", { autoClose: 2500 });
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
                                  <PurchaseComponent :suppliers="supplierOptions" :items="p_filteredInv" />

                                <button
                                    class="btn btn-primary rounded-pill px-4"
                                    data-bs-toggle="modal"
                                    data-bs-target="#addOrderModal"
                                >
                                    Order
                                </button>
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
                            <table class="table align-middle purchase-table">
                                <thead class="small text-muted">
                                    <tr>
                                        <th style="width: 80px">S. #</th>
                                        <th>Supplier Name</th>
                                        <th>Purchase date</th>
                                        <th>Status</th>
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
                                            <td
                                                class="fw-semibold text-success"
                                            >
                                                {{ row.status }}
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
                                        <tr class="sep-row">
                                            <td colspan="6"></td>
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

                <!-- ================= Add Purchase (Modal) ================= -->

                <!-- ================= Add Order (Modal) ================= -->
                <div
                    class="modal fade"
                    id="addOrderModal"
                    tabindex="-1"
                    aria-hidden="true"
                >
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content rounded-4">
                            <div class="modal-header">
                                <h5 class="modal-title fw-semibold">Order</h5>
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

                            <div class="modal-body">
                                <div class="row g-3 align-items-center">
                                    <div class="col-md-6">
                                        <label
                                            class="form-label small text-muted d-block"
                                            >preferred supplier</label
                                        >
                                        <div class="dropdown w-100">
                                            <button
                                                class="btn btn-light border rounded-3 w-100 d-flex justify-content-between align-items-center"
                                                data-bs-toggle="dropdown"
                                            >
                                                {{
                                                    p_supplier
                                                        ? p_supplier.name
                                                        : "Select Supplier"
                                                }}
                                                <i
                                                    class="bi bi-caret-down-fill"
                                                ></i>
                                            </button>
                                            <ul
                                                class="dropdown-menu w-100 shadow rounded-3"
                                            >
                                                <li
                                                    v-for="s in supplierOptions"
                                                    :key="s.id"
                                                >
                                                    <a
                                                        class="dropdown-item"
                                                        href="javascript:void(0)"
                                                        @click="p_supplier = s"
                                                    >
                                                        {{ s.name }}
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-4 mt-1">
                                    <!-- Left: inventory picker -->
                                    <div class="col-lg-5">
                                        <div class="search-wrap mb-2">
                                            <i class="bi bi-search"></i>
                                            <input
                                                v-model="o_search"
                                                type="text"
                                                class="form-control search-input"
                                                placeholder="Search..."
                                            />
                                        </div>

                                        <div
                                            v-for="it in p_filteredInv"
                                            :key="it.id"
                                            class="card shadow-sm border-0 rounded-4 mb-3"
                                        >
                                            <div class="card-body">
                                                <div
                                                    class="d-flex align-items-start gap-3"
                                                >
                                                    <img
                                                        :src="it.image_url"
                                                        alt=""
                                                        style="
                                                            width: 76px;
                                                            height: 76px;
                                                            object-fit: cover;
                                                            border-radius: 6px;
                                                        "
                                                    />

                                                    <div class="flex-grow-1">
                                                        <div
                                                            class="fw-semibold"
                                                        >
                                                            {{ it.name }}
                                                        </div>
                                                        <div
                                                            class="text-muted small"
                                                        >
                                                            Category:
                                                            {{
                                                                it.category.name
                                                            }}
                                                        </div>
                                                        <div
                                                            class="text-muted small"
                                                        >
                                                            Unit: {{ it.unit }}
                                                        </div>
                                                        <div
                                                            class="text-muted small"
                                                        >
                                                            Stock:
                                                            {{ it.stock }}
                                                        </div>
                                                    </div>
                                                    <button  class="btn btn-primary rounded-pill px-3 py-1 btn-sm" @click="
                                                        addOrderItem(it)
                                                        ">
                                                        Add
                                                    </button>
                                                </div>

                                                <div class="row g-2 mt-3">
                                                    <div class="col-4">
                                                        <label
                                                            class="small text-muted"
                                                            >Quantity</label
                                                        >
                                                        <input
                                                            v-model.number="
                                                                it.qty
                                                            "
                                                            type="number"
                                                            min="0"
                                                            class="form-control form-control"
                                                        />
                                                    </div>
                                                    <div class="col-4">
                                                        <label
                                                            class="small text-muted"
                                                            >Unit Price</label
                                                        >
                                                        <input
                                                            v-model.number="
                                                                it.unitPrice
                                                            "
                                                            type="number"
                                                            min="0"
                                                            class="form-control form-control"
                                                        />
                                                    </div>
                                                    <div class="col-4">
                                                        <label
                                                            class="small text-muted"
                                                            >Expiry Date</label
                                                        >
                                                        <input
                                                            v-model="it.expiry"
                                                            type="date"
                                                            class="form-control form-control"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right: cart -->
                                    <div class="col-lg-7">
                                        <div class="cart card border rounded-4">
                                            <div class="table-responsive">
                                                <table
                                                    class="table align-middle mb-0"
                                                >
                                                    <thead>
                                                        <tr>
                                                            <th>Name</th>

                                                            <th>Quantity</th>
                                                            <th>unit price</th>
                                                            <th>expiry</th>
                                                            <th>cost</th>
                                                            <th
                                                                class="text-end"
                                                            >
                                                                Action
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr
                                                            v-for="(
                                                                r, idx
                                                            ) in o_cart"
                                                            :key="idx"
                                                        >
                                                            <td>
                                                                {{ r.name }}
                                                            </td>

                                                            <td>{{ r.qty }}</td>
                                                            <td>
                                                                {{
                                                                    r.unitPrice
                                                                }}
                                                            </td>
                                                            <td>
                                                                {{
                                                                    r.expiry ||
                                                                    "—"
                                                                }}
                                                            </td>
                                                            <td>
                                                                {{ r.cost }}
                                                            </td>
                                                            <td
                                                                class="text-end"
                                                            >
                                                                <button
                                                                    @click="
                                                                        delOrderRow(
                                                                            idx
                                                                        )
                                                                    "
                                                                    class="inline-flex items-center justify-center p-2.5 rounded-full text-red-600 hover:bg-red-100"
                                                                    title="Delete"
                                                                >
                                                                    <svg
                                                                        class="w-5 h-5"
                                                                        fill="none"
                                                                        stroke="currentColor"
                                                                        stroke-width="2"
                                                                        viewBox="0 0 24 24"
                                                                    >
                                                                        <path
                                                                            stroke-linecap="round"
                                                                            stroke-linejoin="round"
                                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0H7m4-3h2a1 1 0 011 1v1H8V5a1 1 0 011-1z"
                                                                        />
                                                                    </svg>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                        <tr
                                                            v-if="
                                                                o_cart.length ===
                                                                0
                                                            "
                                                        >
                                                            <td
                                                                colspan="7"
                                                                class="text-center text-muted py-4"
                                                            >
                                                                No items added.
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div
                                                class="text-end p-3 fw-semibold"
                                            >
                                                Total Bill: {{ money(o_total) }}
                                            </div>
                                        </div>

                                        <div class="mt-4 text-center">
                                            <button type="button"  class="btn btn-primary rounded-pill px-5 py-2" :disabled="o_submitting ||
                                                o_cart.length === 0
                                                " @click="orderSubmit">
                                                <span v-if="!o_submitting">Order</span>
                                                <span v-else>Saving...</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- /modal-body -->
                        </div>
                    </div>
                </div>

                <!-- ====================View Modal either Purchase or Order ==================== -->
                <!-- View Order Modal (Read-only) -->
<div class="modal fade" id="viewOrderModal" tabindex="-1" aria-hidden="true">
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
                        <p><strong>Supplier:</strong> {{ selectedOrder.supplier?.name || 'N/A' }}</p>
                        <p><strong>Purchase Date:</strong> {{ fmtDateTime(selectedOrder.purchase_date).split(',')[0] }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Status:</strong> 
                            <span class="badge bg-success">{{ selectedOrder.status }}</span>
                        </p>
                        <p><strong>Total:</strong> {{ money(selectedOrder.total_amount) }}</p>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Subtotal</th>
                                <th>Expiry Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in selectedOrder.items" :key="item.id">
                                <td>{{ item.product?.name || 'Unknown Product' }}</td>
                                <td>{{ item.quantity }}</td>
                                <td>{{ money(item.unit_price) }}</td>
                                <td>{{ money(item.sub_total) }}</td>
                                <td>{{ item.expiry || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<!-- Edit Order Modal (Editable) -->
<div class="modal fade" id="editOrderModal" tabindex="-1" aria-hidden="true">
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
                        <p><strong>Supplier:</strong> {{ selectedOrder.supplier?.name || 'N/A' }}</p>
                        <p><strong>Purchase Date:</strong> {{ fmtDateTime(selectedOrder.purchase_date).split(',')[0] }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Current Status:</strong> 
                            <span class="badge bg-warning text-dark">{{ selectedOrder.status }}</span>
                        </p>
                        <p><strong>Total:</strong> {{ money(selectedOrder.total_amount) }}</p>
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
                                                        style="
                                                            background-color: #f8f9fa;
                                                        "
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
                                    class="btn btn-secondary rounded-pill px-4 ms-2"
                                    data-bs-dismiss="modal"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-primary rounded-pill px-4"
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

.sep-row td {
    border-bottom: 2px solid #111;
    height: 12px;
    padding: 0;
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
    max-height: 65vh; /* adjust as you like */
    overflow-y: auto;
    padding-right: 0.25rem; /* prevent content jump near scrollbar */
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
