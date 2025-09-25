<script setup>
import Master from "@/Layouts/Master.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { ref, computed, onMounted, watch } from "vue";
import { toast } from "vue3-toastify";
import ConfirmOrderModal from "./ConfirmOrderModal.vue";
import ReceiptModal from "./ReceiptModal.vue";
import KotModal from "./KotModal.vue";

const props = defineProps(["client_secret", "order_code"]);

/* ----------------------------
   Categories
-----------------------------*/
const menuCategories = ref([]);
const fetchMenuCategories = async () => {
    try {
        const response = await axios.get("/pos/fetch-menu-categories");
        menuCategories.value = response.data;
        if (menuCategories.value.length > 0) {
            activeCat.value = menuCategories.value[0].id;
        }
    } catch (error) {
        console.error("Error fetching inventory:", error);
    }
};

const menuItems = ref([]);
const fetchMenuItems = async () => {
    try {
        const response = await axios.get("/pos/fetch-menu-items");
        menuItems.value = response.data;
    } catch (error) {
        console.error("Error fetching inventory:", error);
    }
};

/* ----------------------------
   Products by Category
-----------------------------*/
const productsByCat = computed(() => {
    const grouped = {};
    menuItems.value.forEach((item) => {
        const catId = item.category?.id || "uncategorized";
        const catName = item.category?.name || "Uncategorized";
        if (!grouped[catId]) grouped[catId] = [];
        grouped[catId].push({
            id: item.id,
            title: item.name,
            img: item.image_url || "/assets/img/default.png",
            stock: calculateMenuStock(item),
            price: Number(item.price),
            family: catName,
            description: item.description,
            nutrition: item.nutrition,
            tags: item.tags,
            allergies: item.allergies,
            ingredients: item.ingredients ?? [],
        });
    });
    return grouped;
});

// calculate menu stock
const calculateMenuStock = (item) => {
    if (!item) return 0;
    if (!item.ingredients || item.ingredients.length === 0) {
        return Number(item.stock ?? 0);
    }
    let menuStock = Infinity;
    item.ingredients.forEach((ing) => {
        const required = Number(ing.quantity ?? ing.qty ?? 1);
        const inventoryStock = Number(ing.inventory_stock ?? ing.stock ?? 0);
        if (required <= 0) return;
        const possible = Math.floor(inventoryStock / required);
        menuStock = Math.min(menuStock, possible);
    });
    if (menuStock === Infinity) menuStock = 0;
    return menuStock;
};

/* ----------------------------
   UI State
-----------------------------*/
const searchQuery = ref("");
const orderType = ref("dine");
const customer = ref("Walk In");
const deliveryPercent = ref(10);

const activeCat = ref(null);
const setCat = (id) => (activeCat.value = id);

const showCategories = ref(true);
const openCategory = (c) => {
    setCat(c.id);
    showCategories.value = false;
};
const backToCategories = () => (showCategories.value = true);

const profileTables = ref({});
const orderTypes = ref([]);
const selectedTable = ref(null);
/* ----------------------------
   Fetch Profile Tables
-----------------------------*/
const fetchProfileTables = async () => {
    try {
        const response = await axios.get("/pos/fetch-profile-tables");
        profileTables.value = response.data;
        if (profileTables.value.order_types) {
            orderTypes.value = profileTables.value.order_types;
            orderType.value = orderTypes.value[0];
        }
    } catch (error) {
        console.error("Error fetching profile tables:", error);
    }
};

const visibleProducts = computed(
    () => productsByCat.value[activeCat.value] ?? []
);

const filteredProducts = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return visibleProducts.value;
    return visibleProducts.value.filter(
        (p) =>
            p.title.toLowerCase().includes(q) ||
            (p.family || "").toLowerCase().includes(q) ||
            (p.description || "").toLowerCase().includes(q) ||
            (
                p.tags?.map((t) => t.name.toLowerCase()).join(", ") || ""
            ).includes(q)
    );
});

/* ----------------------------
   Order cart
-----------------------------*/
const orderItems = ref([]);
const addToOrder = (baseItem, qty = 1, note = "") => {
    const menuStock = calculateMenuStock(baseItem);
    const idx = orderItems.value.findIndex((i) => i.title === baseItem.title);

    if (idx >= 0) {
        const newQty = orderItems.value[idx].qty + qty;
        if (newQty <= orderItems.value[idx].stock) {
            orderItems.value[idx].qty = newQty;
            if (note) orderItems.value[idx].note = note;
        } else {
            toast.error(
                "Not enough Ingredients stock available for this Menu."
            );
        }
    } else {
        if (qty > menuStock) {
            toast.error(
                "Not enough Ingredients stock available for this Menu."
            );
            return;
        }
        orderItems.value.push({
            id: baseItem.id,
            title: baseItem.title,
            img: baseItem.img,
            price: Number(baseItem.price) || 0,
            qty: qty,
            note: note || "",
            stock: menuStock,
            ingredients: baseItem.ingredients ?? [],
        });
    }
};

const incCart = async (i) => {
    const it = orderItems.value[i];
    if (!it) return;
    if ((it.stock ?? 0) <= 0) {
        toast.error("Item out of stock.");
        return;
    }
    if (it.qty >= (it.stock ?? 0)) {
        toast.error("Not enough stock to add more of this item.");
        return;
    }
    try {
        // await updateStock(it, 1, "stockout");
        it.qty++;
    } catch (err) {
        toast.error("Failed to add item. Please try again.");
    }
};

const decCart = async (i) => {
    const it = orderItems.value[i];
    if (!it || it.qty <= 1) {
        toast.error("Cannot reduce below 1.");
        return;
    }
    try {
        // await updateStock(it, 1, "stockin");
        it.qty--;
    } catch (err) {
        toast.error("Failed to remove item. Please try again.");
    }
};

const removeCart = (i) => orderItems.value.splice(i, 1);

const subTotal = computed(() =>
    orderItems.value.reduce((s, i) => s + i.price * i.qty, 0)
);
const deliveryCharges = computed(() =>
    orderType.value === "delivery"
        ? (subTotal.value * deliveryPercent.value) / 100
        : 0
);
const grandTotal = computed(() => subTotal.value + deliveryCharges.value);

const money = (n) => `£${(Math.round(n * 100) / 100).toFixed(2)}`;

/* ----------------------------
   Item modal
-----------------------------*/
const selectedItem = ref(null);
const modalQty = ref(1);
const modalNote = ref("");
let chooseItemModal = null;

const openItem = (p) => {
    selectedItem.value = p;
    modalQty.value = 1;
    modalNote.value = "";
    if (chooseItemModal) chooseItemModal.show();
};

const menuStockForSelected = computed(() =>
    calculateMenuStock(selectedItem.value)
);

const confirmAdd = async () => {
    if (!selectedItem.value) return;
    try {
        addToOrder(selectedItem.value, modalQty.value, modalNote.value);
        console.log(selectedItem.value.ingredients);

        //  2) Stockout each ingredient
        // if (
        //     selectedItem.value.ingredients &&
        //     selectedItem.value.ingredients.length
        // ) {
        //     for (const ingredient of selectedItem.value.ingredients) {
        //         const requiredQty = ingredient.pivot?.qty
        //             ? ingredient.pivot.qty * modalQty.value
        //             : modalQty.value;

        //         //Payload matching your request rules
        //         await axios.post("/stock_entries", {
        //             product_id: ingredient.inventory_item_id,
        //             name: ingredient.product_name,
        //             category_id: ingredient.category_id,
        //             supplier_id: ingredient.supplier_id,
        //             available_quantity: ingredient.inventory_stock,
        //             quantity: ingredient.quantity * modalQty.value,
        //             price: null,
        //             value: 0,
        //             operation_type: "pos_stockout",
        //             stock_type: "stockout",
        //             expiry_date: null,
        //             description: null,
        //             purchase_date: null,
        //             user_id: ingredient.user_id,
        //         });
        //     }
        // }

        //  3) Close modal
        if (chooseItemModal) chooseItemModal.hide();
    } catch (err) {
        alert(
            "Stockout failed: " + (err.response?.data?.message || err.message)
        );
    }
};
/* ----------------------------
   Update Stock
-----------------------------*/
const updateStock = async (item, qty, type = "stockout") => {
    if (!item.ingredients || !item.ingredients.length) return;
    try {
        for (const ingredient of item.ingredients) {
            const ingredientQty = Number(ingredient.quantity) || 1;
            const requiredQty = ingredientQty * qty;
            const payload = {
                product_id: ingredient.inventory_item_id,
                name: ingredient.product_name,
                category_id: ingredient.category_id,
                available_quantity: ingredient.inventory_stock,
                quantity: requiredQty,
                value: 0,
                operation_type:
                    type === "stockout" ? "pos_stockout" : "pos_stockin",
                stock_type: type,
                expiry_date: null,
                description: null,
                purchase_date: null,
                user_id: ingredient.user_id,
            };
            if (type === "stockout") {
                payload.price = null;
                payload.supplier_id = null;
            } else {
                payload.price = ingredient.cost || ingredient.price || 1;
                payload.supplier_id = ingredient.supplier_id || 1;
            }
            await axios.post("/stock_entries", payload);
        }
    } catch (err) {
        alert(
            `${type} failed: ` + (err.response?.data?.message || err.message)
        );
        throw err;
    }
};

const incQty = async () => {
    if (modalQty.value < menuStockForSelected.value) {
        modalQty.value++;
        // Add .value here
        // try {
        //     await updateStock(selectedItem.value, 1, "stockout");
        //     modalQty.value++; // Only increment after successful stock update
        //     console.log(
        //         "Stock updated successfully, new modalQty:",
        //         modalQty.value
        //     );
        // } catch (error) {
        //     console.error("Failed to update stock:", error);
        //     // Don't increment modalQty if stock update failed
        // }
    } else {
        console.log("Cannot increment: reached maximum stock limit");
    }
};
const decQty = async () => {
    if (modalQty.value > 1) {
        modalQty.value--;


        // try {
        //     await updateStock(selectedItem.value, 1, "stockin");
        //     modalQty.value--; // Only decrement after successful stock update
        //     console.log(
        //         "Stock updated successfully, new modalQty:",
        //         modalQty.value
        //     );
        // } catch (error) {
        //     console.error("Failed to update stock:", error);
        //     // Don't decrement modalQty if stock update failed
        // }


    } else {
        console.log("Cannot decrement: minimum quantity is 1");
    }
};

/* ----------------------------
   Order + Receipt
-----------------------------*/
const formErrors = ref({});
const resetCart = () => {
    orderItems.value = [];
    customer.value = "Walk In";
    selectedTable.value = null;
    orderType.value = orderTypes.value[0] || "dine_in";
    note.value = "";
    deliveryPercent.value = 0;
};
watch(orderType, () => (formErrors.value = {}));

const note = ref("");
const showReceiptModal = ref(false);
const lastOrder = ref(null);
const showConfirmModal = ref(false);
const cashReceived = ref(0);
const openConfirmModal = () => {
    if (orderItems.value.length === 0) {
        toast.error("Please add at least one item to the cart.");
        return;
    }
    if (orderType.value === "dine_in" && !selectedTable.value) {
        formErrors.value.table_number = [
            "Table number is required for dine-in orders.",
        ];
        toast.error("Please select a table number for Dine In orders.");
        return;
    }
    cashReceived.value = grandTotal.value;
    showConfirmModal.value = true;
};
/* ----------------------------
   Print Receipt 
-----------------------------*/
function printReceipt(order) {
    const type = (order?.payment_type || "").toLowerCase();
    let payLine = "";
    if (type === "split") {
        payLine = `Payment Type: Split 
      (Cash: £${Number(order?.cash_amount ?? 0).toFixed(2)}, 
       Card: £${Number(order?.card_amount ?? 0).toFixed(2)})`;
    } else if (type === "card" || type === "stripe") {
        payLine = `Payment Type: Card${order?.card_brand ? ` (${order.card_brand}` : ""
            }${order?.last4 ? ` •••• ${order.last4}` : ""}${order?.card_brand ? ")" : ""
            }`;
    } else {
        payLine = `Payment Type: ${order?.payment_method || "Cash"}`;
    }
    const html = `...`; // unchanged
    const w = window.open("", "", "width=400,height=600");
    if (!w) {
        alert("Please allow popups for this site to print receipts");
        return;
    }
    w.document.open();
    w.document.write(html);
    w.document.close();
}

const paymentMethod = ref("cash");
const changeAmount = ref(0);

/* ----------------------------
   Print KOT - FIXED
-----------------------------*/

function printKot(order) {
    // Convert reactive object to plain object
    const plainOrder = JSON.parse(JSON.stringify(order));

    const type = (plainOrder?.payment_method || "").toLowerCase();
    let payLine = "";
    if (type === "split") {
        payLine = `Payment Type: Split 
      (Cash: £${Number(plainOrder?.cash_amount ?? 0).toFixed(2)}, 
       Card: £${Number(plainOrder?.card_amount ?? 0).toFixed(2)})`;
    } else if (type === "card" || type === "stripe") {
        payLine = `Payment Type: Card${plainOrder?.card_brand ? ` (${plainOrder.card_brand}` : ""
            }${plainOrder?.last4 ? ` •••• ${plainOrder.last4}` : ""}${plainOrder?.card_brand ? ")" : ""
            }`;
    } else {
        payLine = `Payment Type: ${plainOrder?.payment_method || "Cash"}`;
    }

    const html = `
    <html>
    <head>
      <title>Kitchen Order Ticket</title>
      <style>
        @page { size: 80mm auto; margin: 0; }
        html, body {
          width: 80mm;
          margin: 0;
          padding: 8px;
          font-family: monospace, Arial, sans-serif;
          font-size: 12px;
          line-height: 1.4;
        }
        .header { text-align: center; margin-bottom: 10px; }
        .order-info { margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 4px 0; text-align: left; }
        th { border-bottom: 1px solid #000; }
        td:last-child, th:last-child { text-align: right; }
        .totals { margin-top: 10px; border-top: 1px dashed #000; padding-top: 8px; }
        .footer { text-align: center; margin-top: 10px; font-size: 11px; }
      </style>
    </head>
    <body>
      <div class="header">
        <h2>KITCHEN ORDER TICKET</h2>
      </div>
      
      <div class="order-info">
        <div><strong>Date:</strong> ${plainOrder.order_date}</div>
        <div><strong>Time:</strong> ${plainOrder.order_time}</div>
        <div><strong>Customer:</strong> ${plainOrder.customer_name}</div>
        <div><strong>Order Type:</strong> ${plainOrder.order_type}</div>
        ${plainOrder.note ? `<div><strong>Note:</strong> ${plainOrder.note}</div>` : ''}
      </div>

      <table>
        <thead>
          <tr>
            <th>Item</th>
            <th>Qty</th>
            <th>Price</th>
          </tr>
        </thead>
        <tbody>
          ${(plainOrder.items || []).map(item => {
        const qty = Number(item.quantity) || 0;
        const price = Number(item.price) || 0;
        const total = qty * price;
        return `
            <tr>
              <td>${item.title || 'Unknown Item'}</td>
              <td>${qty}</td>
              <td>£${total.toFixed(2)}</td>
            </tr>
          `;
    }).join('')}
        </tbody>
      </table>

      <div class="totals">
        <div>Subtotal: £${Number(plainOrder.sub_total).toFixed(2)}</div>
        <div><strong>Total: £${Number(plainOrder.total_amount).toFixed(2)}</strong></div>
        <div>${payLine}</div>
        ${plainOrder.cash_received ? `<div>Cash Received: £${Number(plainOrder.cash_received).toFixed(2)}</div>` : ''}
        ${plainOrder.change ? `<div>Change: £${Number(plainOrder.change).toFixed(2)}</div>` : ''}
      </div>

      <div class="footer">
        Kitchen Copy - Thank you!
      </div>
    </body>
    </html>
  `;

    const w = window.open("", "_blank", "width=400,height=600");
    if (!w) {
        alert("Please allow popups for this site to print KOT");
        return;
    }
    w.document.open();
    w.document.write(html);
    w.document.close();
    w.onload = () => {
        w.print();
        w.close();
    };
}


const showKotModal = ref(false);
const kotData = ref(null);
const ShowKotDataInModal  = ref(null);

/* ----------------------------
   Confirm Order
-----------------------------*/
const confirmOrder = async ({ paymentMethod, cashReceived, changeAmount, items, autoPrintKot }) => {
    try {
        const payload = {
            customer_name: customer.value,
            sub_total: subTotal.value,
            total_amount: grandTotal.value,
            tax: 0,
            service_charges: 0,
            delivery_charges: 0,
            note: note.value,
            order_date: new Date().toISOString().split("T")[0],
            order_time: new Date().toTimeString().split(" ")[0],
            order_type: orderType.value === "dine_in"
                ? "Dine In"
                : orderType.value === "delivery"
                    ? "Delivery"
                    : orderType.value === "takeaway"
                        ? "Takeaway"
                        : "Collection",
            table_number: selectedTable.value?.name || null,
            payment_method: paymentMethod,
            auto_print_kot: autoPrintKot,
            cash_received: cashReceived,
            change: changeAmount,
            items: (orderItems.value ?? []).map(it => ({
                product_id: it.id,
                title: it.title,
                quantity: it.qty,
                price: it.price,
                note: it.note ?? "",
            })),
        };

        const res = await axios.post("/pos/order", payload);
        resetCart();
        showConfirmModal.value = false;
        toast.success(res.data.message);

        lastOrder.value = { ...res.data.order, ...payload, items: payload.items };

        // Open KOT modal after confirmation
        kotData.value = JSON.parse(JSON.stringify(lastOrder.value));
        ShowKotDataInModal.value = res.data.kot; 
        showKotModal.value = true;
        console.log("ShowKotDataInModal", ShowKotDataInModal.value);
        // Print receipt immediately
        printReceipt(JSON.parse(JSON.stringify(lastOrder.value)));
        printKot(JSON.parse(JSON.stringify(lastOrder.value)));

    } catch (err) {
        console.error("Order submission error:", err);
        toast.error(err.response?.data?.message || "Failed to place order");
    }
};

/* ----------------------------
   Lifecycle
-----------------------------*/
onMounted(() => {
    if (window.bootstrap) {
        document
            .querySelectorAll('[data-bs-toggle="tooltip"]')
            .forEach((el) => new window.bootstrap.Tooltip(el));
        const modalEl = document.getElementById("chooseItem");
        if (modalEl) {
            chooseItemModal = new window.bootstrap.Modal(modalEl, {
                backdrop: "static",
            });
        }
    }
    fetchMenuCategories();
    fetchMenuItems();
    fetchProfileTables();
});

const page = usePage();
function bumpToasts() {
    const s = page.props.flash?.success;
    const e = page.props.flash?.error;
    if (s) toast.success(s, { autoClose: 4000 });
    if (e) toast.error(e, { autoClose: 6000 });
}
onMounted(() => bumpToasts());
onMounted(() => {
    const s = page.props.flash?.success;
    const e = page.props.flash?.error;
    if (s) toast.success(s);
    if (e) toast.error(e);

    const payload = page.props.flash?.print_payload; // from controller
    if (payload) setTimeout(() => printReceipt(payload), 250);
    if (payload) setTimeout(() => printKot(payload), 250);
});
// Also react if flash changes due to subsequent navigations
watch(
    () => page.props.flash,
    () => bumpToasts(),
    { deep: true }
);


// In parent
const handleKotStatusUpdated = ({ id, status, message }) => {
    const kot = kotList.find(k => k.id === id);
    if (kot) kot.status = status;
    toast.success(message); // only one toast here
};


</script>

<template>

    <Head title="POS Order" />

    <Master>
        <div class="page-wrapper">
            <div class="container-fluid px-3 py-3">
                <div class="row gx-3 gy-3">
                    <!-- LEFT: Menu -->
                    <div class="col-lg-8">
                        <!-- Categories Grid -->
                        <div v-if="showCategories" class="row g-3">
                            <div v-for="c in menuCategories" :key="c.id" class="col-6 col-md-4">
                                <div class="cat-tile" :style="{ background: c.box_bg_color || '#1b1670' }"
                                    @click="openCategory(c)">
                                    <div class="cat-icon">{{ c.icon }}</div>
                                    <div class="cat-name">{{ c.name }}</div>
                                    <div class="cat-sub">{{ c.menu_items_count }} items</div>
                                </div>
                            </div>

                            <div v-if="menuCategories.length === 0" class="col-12">
                                <div class="alert alert-light border text-center rounded-4">
                                    No categories found
                                </div>
                            </div>
                        </div>


                        <!-- Items in selected category -->
                        <div v-else>
                            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
                                <button class="btn btn-light rounded-pill shadow-sm px-3" @click="backToCategories">
                                    <i class="bi bi-arrow-left me-1"></i> Back
                                </button>
                                <h5 class="fw-bold mb-0">
                                    {{
                                        menuCategories.find(
                                            (c) => c.id === activeCat
                                        )?.name || "Items"
                                    }}
                                </h5>

                                <!-- Search -->
                                <div class="search-wrap ms-auto">
                                    <i class="bi bi-search"></i>
                                    <input v-model="searchQuery" class="form-control search-input" type="text"
                                        placeholder="Search items..." />
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-6 col-md-4 col-xl-3 d-flex" v-for="p in filteredProducts"
                                    :key="p.title">
                                    <div class="item-card" @click="openItem(p)">
                                        <div class="item-img">
                                            <img :src="p.img" alt="" />
                                            <span class="item-price">{{
                                                money(p.price)
                                                }}</span>
                                            <span v-if="(p.stock ?? 0) <= 0" class="item-badge">
                                                Out
                                            </span>
                                        </div>
                                        <div class="item-body">
                                            <div class="item-title">
                                                {{ p.title }}
                                            </div>
                                            <div class="item-sub">
                                                {{ p.family }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="filteredProducts.length === 0" class="col-12">
                                    <div class="alert alert-light border text-center rounded-4">
                                        No items found
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: Cart -->
                    <div class="col-lg-4">
                        <div class="cart card border-0 shadow-lg rounded-4">
                            <div class="cart-header">
                                <div class="cart-title">Shopping Cart</div>
                                <div class="order-type">
                                    <button v-for="(type, i) in orderTypes" :key="i" class="ot-pill"
                                        :class="{ active: orderType === type }" @click="orderType = type">
                                        {{ type.replace(/_/g, " ") }}
                                    </button>
                                </div>
                            </div>

                            <div class="cart-body">
                                <!-- Dine-in table / customer -->
                                <div class="mb-3">
                                    <div v-if="orderType === 'dine_in'" class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label small">Table</label>
                                            <select v-model="selectedTable" class="form-select form-select-sm" :class="{
                                                'is-invalid':
                                                    formErrors.table_number,
                                            }">
                                                <option v-for="(
table, idx
                                                    ) in profileTables.table_details" :key="idx" :value="table">
                                                    {{ table.name }}
                                                </option>
                                            </select>
                                            <div v-if="formErrors.table_number" class="invalid-feedback d-block">
                                                {{ formErrors.table_number[0] }}
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">Customer</label>
                                            <input v-model="customer" class="form-control form-control-sm"
                                                placeholder="Walk In" />
                                        </div>
                                    </div>

                                    <div v-else>
                                        <label class="form-label small">Customer</label>
                                        <input v-model="customer" class="form-control form-control-sm"
                                            placeholder="Walk In" />
                                    </div>
                                </div>

                                <!-- Line items -->
                                <div class="cart-lines">
                                    <div v-if="orderItems.length === 0" class="empty">
                                        Add items from the left
                                    </div>

                                    <div v-for="(it, i) in orderItems" :key="it.title" class="line">
                                        <div class="line-left">
                                            <img :src="it.img" alt="" />
                                            <div class="meta">
                                                <div class="name" :title="it.title">
                                                    {{ it.title }}
                                                </div>
                                                <div class="note" v-if="it.note">
                                                    {{ it.note }}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="line-mid">
                                            <button class="qty-btn" @click="decCart(i)">
                                                −
                                            </button>
                                            <div class="qty">{{ it.qty }}</div>
                                            <button class="qty-btn" :class="{
                                                disabled:
                                                    it.qty >=
                                                    (it.stock ?? 0),
                                            }" @click="incCart(i)" :disabled="it.qty >= (it.stock ?? 0)
                                                ">
                                                +
                                            </button>
                                        </div>

                                        <div class="line-right">
                                            <div class="price">
                                                {{ money(it.price) }}
                                            </div>
                                            <button class="del" @click="removeCart(i)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Totals -->
                                <div class="totals">
                                    <div class="trow">
                                        <span>Sub Total</span>
                                        <b>{{ money(subTotal) }}</b>
                                    </div>
                                    <div class="trow" v-if="orderType === 'delivery'">
                                        <span>Delivery</span>
                                        <b>{{ deliveryPercent }}%</b>
                                    </div>
                                    <div class="trow total">
                                        <span>Total</span>
                                        <b>{{ money(grandTotal) }}</b>
                                    </div>
                                </div>

                                <textarea v-model="note" rows="3" class="form-control form-control-sm rounded-3"
                                    placeholder="Note"></textarea>
                            </div>

                            <div class="cart-footer">
                                <button class="btn-clear" @click="resetCart()">
                                    Clear
                                </button>
                                <button class="btn-place" @click="openConfirmModal">
                                    Place Order
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Choose Item Modal (unchanged content/ids) -->
            <div class="modal fade" id="chooseItem" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content rounded-4 border-0 shadow">
                        <div class="modal-header border-0">
                            <h5 class="modal-title fw-bold">
                                {{ selectedItem?.title || "Choose Item" }}
                            </h5>
                            <button class="btn btn-light btn-sm rounded-pill" data-bs-dismiss="modal"
                                aria-label="Close">
                                ✕
                            </button>
                        </div>

                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <img :src="selectedItem?.image_url ||
                                        selectedItem?.img ||
                                        '/assets/img/product/product29.jpg'
                                        " class="img-fluid rounded-3 w-100" alt="" />
                                </div>
                                <div class="col-md-7">
                                    <div class="h4 mb-1">
                                        {{ money(selectedItem?.price || 0) }}
                                    </div>

                                    <!-- Chips -->
                                    <div class="chips mb-3">
                                        <div class="mb-1">
                                            <strong>Nutrition:</strong>
                                        </div>
                                        <span v-if="
                                            selectedItem?.nutrition
                                                ?.calories
                                        " class="chip chip-orange">
                                            Cal:
                                            {{
                                                selectedItem.nutrition.calories
                                            }}
                                        </span>
                                        <span v-if="
                                            selectedItem?.nutrition?.carbs
                                        " class="chip chip-green">
                                            Carbs:
                                            {{ selectedItem.nutrition.carbs }}
                                        </span>
                                        <span v-if="selectedItem?.nutrition?.fat" class="chip chip-purple">
                                            Fat:
                                            {{ selectedItem.nutrition.fat }}
                                        </span>
                                        <span v-if="
                                            selectedItem?.nutrition?.protein
                                        " class="chip chip-blue">
                                            Protein:
                                            {{ selectedItem.nutrition.protein }}
                                        </span>

                                        <div class="w-100 mt-2">
                                            <strong>Allergies:</strong>
                                        </div>
                                        <span v-for="(
a, i
                                            ) in selectedItem?.allergies || []" :key="'a-' + i"
                                            class="chip chip-red">{{ a.name }}</span>

                                        <div class="w-100 mt-2">
                                            <strong>Tags:</strong>
                                        </div>
                                        <span v-for="(
t, i
                                            ) in selectedItem?.tags || []" :key="'t-' + i" class="chip chip-teal">{{
                                                t.name }}</span>
                                    </div>

                                    <div class="qty-group">
                                        <button class="qty-btn" @click="decQty">
                                            −
                                        </button>
                                        <div class="qty-box">
                                            {{ modalQty }}
                                        </div>
                                        <button class="qty-btn" @click="incQty" :disabled="modalQty >= menuStockForSelected
                                            ">
                                            +
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-0">
                            <button class="btn btn-primary rounded-pill px-4" @click="confirmAdd">
                                Add to Order
                            </button>
                        </div>
                    </div>
                </div>
            </div>


            <KotModal
    :show="showKotModal"
    :kot="ShowKotDataInModal"
    @close="showKotModal = false"
    @status-updated="handleKotStatusUpdated"
/>



            <!-- Confirm / Receipt (unchanged props) -->
            <ConfirmOrderModal :show="showConfirmModal" :customer="customer" :order-type="orderType"
                :selected-table="selectedTable" :order-items="orderItems" :grand-total="grandTotal" :money="money"
                v-model:cashReceived="cashReceived" :client_secret="client_secret" :order_code="order_code"
                :sub-total="subTotal" :tax="0" :service-charges="0" :delivery-charges="0" :note="note"
                :order-date="new Date().toISOString().split('T')[0]"
                :order-time="new Date().toTimeString().split(' ')[0]" :payment-method="paymentMethod"
                :change="changeAmount" @close="showConfirmModal = false" @confirm="confirmOrder" />
            <ReceiptModal :show="showReceiptModal" :order="lastOrder" :money="money"
                @close="showReceiptModal = false" />
        </div>
    </Master>
</template>

<style scoped>
/* ========== Page Base ========== */
.page-wrapper {
    background: #f5f7fb;
}

/* ========== Categories Grid ========== */
.cat-tile {
    border-radius: 16px;
    padding: 2rem 1rem;
    text-align: center;
    cursor: pointer;
    box-shadow: 0 6px 16px rgba(17, 23, 31, 0.06);
    transition: 0.2s;
    color: #fff;
}

.cat-tile:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 24px rgba(17, 23, 31, 0.1);
}

.cat-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.cat-name {
    font-weight: 700;
    font-size: 1rem;
}

.cat-sub {
    font-size: 0.8rem;
    opacity: 0.9;
}

/* ========== Search Pill ========== */
.search-wrap {
    position: relative;
    min-width: 220px;
}

.search-wrap .bi-search {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
}

.search-input {
    padding-left: 34px;
    border-radius: 999px;
    background: #fff;
}

/* ========== Items Grid ========== */
.item-card {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    width: 100%;
    box-shadow: 0 8px 18px rgba(17, 23, 31, 0.06);
    cursor: pointer;
    transition: 0.2s;
    display: flex;
    flex-direction: column;
}

.item-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 22px rgba(17, 23, 31, 0.1);
}

.item-img {
    position: relative;
    aspect-ratio: 1/1;
    overflow: hidden;
}

.item-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.item-price {
    position: absolute;
    left: 10px;
    bottom: 10px;
    background: #1b1670;
    color: #fff;
    padding: 0.25rem 0.55rem;
    font-weight: 700;
    border-radius: 8px;
    font-size: 0.9rem;
}

.item-badge {
    position: absolute;
    right: 10px;
    top: 10px;
    background: #ffeded;
    color: #c0392b;
    padding: 0.1rem 0.5rem;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.75rem;
}

.item-body {
    padding: 0.6rem 0.75rem 0.8rem;
}

.item-title {
    font-weight: 700;
    font-size: 0.98rem;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.item-sub {
    color: #8a8fa7;
    font-size: 0.8rem;
}

/* ========== Cart Panel ========== */
.cart {
    display: flex;
    flex-direction: column;
}

.cart-header {
    background: #1b1670;
    color: #fff;
    padding: 0.9rem 1rem;
    border-top-left-radius: 1rem;
    border-top-right-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.cart-title {
    font-weight: 800;
    letter-spacing: 0.3px;
}

.order-type {
    display: flex;
    gap: 0.4rem;
}

.ot-pill {
    background: rgba(255, 255, 255, 0.18);
    color: #fff;
    border: 0;
    border-radius: 999px;
    padding: 0.25rem 0.65rem;
    font-size: 0.8rem;
}

.ot-pill.active {
    background: #fff;
    color: #1b1670;
    font-weight: 700;
}

.cart-body {
    padding: 1rem;
    background: #fff;
}

.cart-lines {
    background: #fff;
    border: 1px dashed #e8e9ef;
    border-radius: 12px;
    padding: 0.5rem;
    max-height: 360px;
    overflow: auto;
}

.empty {
    color: #9aa0b6;
    text-align: center;
    padding: 1.25rem 0;
}

.line {
    display: grid;
    grid-template-columns: 1fr auto auto;
    align-items: center;
    gap: 0.6rem;
    padding: 0.45rem 0.35rem;
    border-bottom: 1px solid #f1f2f6;
}

.line:last-child {
    border-bottom: 0;
}

.line-left {
    display: flex;
    gap: 0.6rem;
    align-items: center;
    min-width: 0;
}

.line-left img {
    width: 38px;
    height: 38px;
    object-fit: cover;
    border-radius: 8px;
}

.meta .name {
    font-weight: 700;
    font-size: 0.92rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.meta .note {
    font-size: 0.75rem;
    color: #8a8fa7;
}

.line-mid {
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.qty-btn {
    width: 28px;
    height: 28px;
    border-radius: 999px;
    border: 0;
    background: #1b1670;
    color: #fff;
    font-weight: 800;
    line-height: 1;
}

.qty-btn.disabled {
    background: #b9bdd4;
}

.qty {
    min-width: 30px;
    text-align: center;
    font-weight: 700;
    background: #f1f2f6;
    border-radius: 999px;
    padding: 0.15rem 0.4rem;
}

.line-right {
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.price {
    font-weight: 700;
    min-width: 64px;
    text-align: right;
}

.del {
    border: 0;
    background: #ffeded;
    color: #c0392b;
    width: 30px;
    height: 30px;
    border-radius: 8px;
}

.totals {
    padding: 0.75rem 0 0.25rem;
}

.trow {
    display: flex;
    justify-content: space-between;
    padding: 0.25rem 0;
    color: #4b5563;
}

.trow.total {
    border-top: 1px solid #eef0f6;
    margin-top: 0.25rem;
    padding-top: 0.6rem;
    color: #111827;
    font-size: 1.05rem;
    font-weight: 800;
}

.cart-footer {
    background: #f7f8ff;
    padding: 0.75rem;
    display: flex;
    gap: 0.6rem;
    border-bottom-left-radius: 1rem;
    border-bottom-right-radius: 1rem;
}

.btn-clear {
    flex: 1;
    border: 0;
    background: #eef1ff;
    color: #1b1670;
    font-weight: 700;
    padding: 0.6rem;
    border-radius: 999px;
}

.btn-place {
    flex: 1;
    border: 0;
    background: #1b1670;
    color: #fff;
    font-weight: 800;
    padding: 0.6rem;
    border-radius: 999px;
}

/* ========== Chips & Qty in Modal ========== */
.chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
}

.chip {
    font-size: 0.75rem;
    padding: 0.25rem 0.55rem;
    border-radius: 999px;
    background: #f5f6fb;
    border: 1px solid #eceef7;
}

.chip-green {
    background: #e9f8ef;
    border-color: #d2f1de;
}

.chip-blue {
    background: #e8f3ff;
    border-color: #d2e6ff;
}

.chip-purple {
    background: #f1e9ff;
    border-color: #e1d2ff;
}

.chip-orange {
    background: #fff3e6;
    border-color: #ffe1bf;
}

.chip-red {
    background: #ffe9ea;
    border-color: #ffd3d6;
}

.chip-teal {
    background: #e8fffb;
    border-color: #c9f4ee;
}

.qty-group {
    display: inline-flex;
    border: 1px solid #d0cfd7;
    border-radius: 12px;
    overflow: hidden;
}

.qty-box {
    min-width: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #1b1670;
    color: #fff;
    font-weight: 800;
}

.qty-group .qty-btn {
    width: 38px;
    height: 38px;
}

/* ========== Responsive ========== */
@media (max-width: 1199.98px) {
    .item-title {
        font-size: 0.92rem;
    }
}

@media (max-width: 991.98px) {
    .cart-lines {
        max-height: 260px;
    }

    .search-wrap {
        width: 100%;
        margin-top: 0.4rem;
    }
}

@media (max-width: 575.98px) {
    .cat-tile {
        padding: 1.5rem 1rem;
    }

    .cat-name {
        font-size: 0.9rem;
    }

    .search-input {
        width: 100%;
    }
}
</style>
