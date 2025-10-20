<script setup>
import Master from "@/Layouts/Master.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { ref, computed, onMounted, watch } from "vue";
import { toast } from "vue3-toastify";
import ConfirmOrderModal from "./ConfirmOrderModal.vue";
import ReceiptModal from "./ReceiptModal.vue";
import PromoModal from "./PromoModal.vue";
import KotModal from "./KotModal.vue";
import { useFormatters } from "@/composables/useFormatters";
import PosOrdersModal from "./PosOrdersModal.vue";
import { Package, ShoppingCart } from "lucide-vue-next";


const { formatMoney, formatCurrencySymbol, formatNumber, dateFmt } = useFormatters();

const props = defineProps(["client_secret", "order_code"]);

/* ----------------------------
   Categories
-----------------------------*/
const menuCategories = ref([]);
const menuCategoriesLoading = ref(true);

const fetchMenuCategories = async () => {
    menuCategoriesLoading.value = true;
    try {
        const response = await axios.get("/api/pos/fetch-menu-categories");
        menuCategories.value = response.data;
        if (menuCategories.value.length > 0) {
            activeCat.value = menuCategories.value[0].id;
        }
    } catch (error) {
        console.error("Error fetching categories:", error);
    } finally {
        menuCategoriesLoading.value = false;
    }
};

const menuItems = ref([]);
const fetchMenuItems = async () => {
    try {
        const response = await axios.get("/api/pos/fetch-menu-items");
        menuItems.value = response.data;
        console.log("Fetched menu items:", menuItems.value);
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
            label_color: item.label_color || "#1B1670",
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
        const response = await axios.get("/api/pos/fetch-profile-tables");
        profileTables.value = response.data;

        if (profileTables.value.order_types) {
            // Capitalize first character of each type
            orderTypes.value = profileTables.value.order_types.map(type =>
                type.charAt(0).toUpperCase() + type.slice(1)
            );

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
            price: baseItem.price * qty || 0,
            unit_price: Number(baseItem.price) || 0,
            qty: qty,
            note: note || "",
            stock: menuStock,
            ingredients: baseItem.ingredients ?? [],
        });
    }
};

// const incCart = async (i) => {
//     const it = orderItems.value[i];
//     if (!it) return;
//     if ((it.stock ?? 0) <= 0) {
//         toast.error("Item out of stock.");
//         return;
//     }
//     if (it.qty >= (it.stock ?? 0)) {
//         toast.error("Not enough stock to add more of this item.");
//         return;
//     }
//     try {
//         // await updateStock(it, 1, "stockout");
//         it.qty++;
//     } catch (err) {
//         toast.error("Failed to add item. Please try again.");
//     }
// };

const incCart = async (i) => {
    const it = orderItems.value[i];
    if (!it) return;

    // Build stock map
    const ingredientStock = {};
    for (const item of orderItems.value) {
        if (!item.ingredients?.length) continue;
        for (const ing of item.ingredients) {
            const id = ing.inventory_item_id;
            if (!ingredientStock[id]) ingredientStock[id] = parseFloat(ing.inventory_stock);
            ingredientStock[id] -= parseFloat(ing.quantity) * item.qty;
        }
    }

    // Check ingredient stock
    if (it.ingredients?.length) {
        for (const ing of it.ingredients) {
            const id = ing.inventory_item_id;
            // restore stock by adding back current item's usage
            const currentStock = (ingredientStock[id] ?? parseFloat(ing.inventory_stock))
                + parseFloat(ing.quantity) * it.qty;

            const required = parseFloat(ing.quantity) * (it.qty + 1);

            if (currentStock < required) {
                it.outOfStock = true;
                toast.error(`Not enough stock for "${it.title}".`);
                return;
            }
        }
    }


    // If stock is okay
    it.outOfStock = false;
    it.qty++;
    it.price = it.unit_price * it.qty;
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
        it.price = it.unit_price * it.qty;
    } catch (err) {
        toast.error("Failed to remove item. Please try again.");
    }
};

const removeCart = (i) => orderItems.value.splice(i, 1);

const subTotal = computed(() =>
    orderItems.value.reduce((s, i) => s + i.price, 0)
);

const deliveryCharges = computed(() =>
    orderType.value === "Delivery"
        ? (subTotal.value * deliveryPercent.value) / 100
        : 0
);
const grandTotal = computed(() => {
    const total = subTotal.value + deliveryCharges.value - promoDiscount.value;
    return Math.max(0, total);
});

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

// const confirmAdd = async () => {
//     if (!selectedItem.value) return;
//     try {
//         addToOrder(selectedItem.value, modalQty.value, modalNote.value);
//         console.log(selectedItem.value.ingredients);

//         //  2) Stockout each ingredient
//         // if (
//         //     selectedItem.value.ingredients &&
//         //     selectedItem.value.ingredients.length
//         // ) {
//         //     for (const ingredient of selectedItem.value.ingredients) {
//         //         const requiredQty = ingredient.pivot?.qty
//         //             ? ingredient.pivot.qty * modalQty.value
//         //             : modalQty.value;

//         //         //Payload matching your request rules
//         //         await axios.post("/stock_entries", {
//         //             product_id: ingredient.inventory_item_id,
//         //             name: ingredient.product_name,
//         //             category_id: ingredient.category_id,
//         //             supplier_id: ingredient.supplier_id,
//         //             available_quantity: ingredient.inventory_stock,
//         //             quantity: ingredient.quantity * modalQty.value,
//         //             price: null,
//         //             value: 0,
//         //             operation_type: "pos_stockout",
//         //             stock_type: "stockout",
//         //             expiry_date: null,
//         //             description: null,
//         //             purchase_date: null,
//         //             user_id: ingredient.user_id,
//         //         });
//         //     }
//         // }

//         //  3) Close modal
//         if (chooseItemModal) chooseItemModal.hide();
//     } catch (err) {
//         alert(
//             "Stockout failed: " + (err.response?.data?.message || err.message)
//         );
//     }
// };

const confirmAdd = async () => {
    if (!selectedItem.value) return;

    const ingredientStock = {}; // Track remaining stock per ingredient

    // 1️⃣ Initialize stock from current cart
    for (const item of orderItems.value) {
        if (!item.ingredients?.length) continue;
        for (const ing of item.ingredients) {
            const ingredientId = ing.inventory_item_id;
            if (!ingredientStock[ingredientId]) {
                ingredientStock[ingredientId] = parseFloat(ing.inventory_stock);
            }
            ingredientStock[ingredientId] -= parseFloat(ing.quantity) * item.qty;
        }
    }

    // 2️⃣ Check stock for selected item against remaining stock
    if (selectedItem.value.ingredients?.length) {
        for (const ing of selectedItem.value.ingredients) {
            const ingredientId = ing.inventory_item_id;
            const availableStock = ingredientStock[ingredientId] ?? parseFloat(ing.inventory_stock);
            const requiredQty = parseFloat(ing.quantity) * modalQty.value;

            if (availableStock < requiredQty) {
                toast.error(
                    `Not enough stock for "${selectedItem.value.title}".`
                );
                return; // Stop adding to cart
            }
        }
    }

    try {
        // 3️⃣ If enough stock, add to cart
        addToOrder(selectedItem.value, modalQty.value, modalNote.value);

        // 4️⃣ Close modal
        if (chooseItemModal) chooseItemModal.hide();
    } catch (err) {
        alert(
            "Failed to add item: " + (err.response?.data?.message || err.message)
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

// const incQty = async () => {
//     if (modalQty.value < menuStockForSelected.value) {
//         modalQty.value++;
//         // Add .value here
//         // try {
//         //     await updateStock(selectedItem.value, 1, "stockout");
//         //     modalQty.value++; // Only increment after successful stock update
//         //     console.log(
//         //         "Stock updated successfully, new modalQty:",
//         //         modalQty.value
//         //     );
//         // } catch (error) {
//         //     console.error("Failed to update stock:", error);
//         //     // Don't increment modalQty if stock update failed
//         // }
//     } else {
//         console.log("Cannot increment: reached maximum stock limit");
//     }
// };

const incQty = async () => {
    if (!selectedItem.value || !selectedItem.value.ingredients?.length) return;

    // 1️⃣ Calculate remaining stock for all ingredients
    const ingredientStock = {};

    // Include items already in the cart
    for (const item of orderItems.value) {
        if (!item.ingredients?.length) continue;
        for (const ing of item.ingredients) {
            const ingredientId = ing.inventory_item_id;
            if (!ingredientStock[ingredientId]) {
                ingredientStock[ingredientId] = parseFloat(ing.inventory_stock);
            }
            ingredientStock[ingredientId] -= parseFloat(ing.quantity) * item.qty;
        }
    }

    // 2️⃣ Check if increasing qty will exceed available stock
    for (const ing of selectedItem.value.ingredients) {
        const ingredientId = ing.inventory_item_id;
        const availableStock = ingredientStock[ingredientId] ?? parseFloat(ing.inventory_stock);
        const requiredQty = parseFloat(ing.quantity) * (modalQty.value + 1); // next quantity

        if (availableStock < requiredQty) {
            toast.error(
                `Not enough stock for "${selectedItem.value.title}".`
            );
            return; // prevent increasing qty
        }
    }

    // 3️⃣ If stock is enough, increment quantity
    modalQty.value++;
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
// Update resetCart to clear promo
const resetCart = () => {
    orderItems.value = [];
    customer.value = "Walk In";
    selectedTable.value = null;
    orderType.value = orderTypes.value[0] || "dine_in";
    note.value = "";
    deliveryPercent.value = 0;
    selectedPromo.value = null; // Clear promo
};
watch(orderType, () => (formErrors.value = {}));

const note = ref("");
const showReceiptModal = ref(false);
const lastOrder = ref(null);
const showConfirmModal = ref(false);
const cashReceived = ref(0);

/* ------------------------------------
   Helper Function to calculate Stock
---------------------------------------*/
const hasEnoughStockForOrder = () => {
    const ingredientStock = {}; // tracks remaining stock for each ingredient

    // Go through items in the same order as they are in the cart (first added = first served)
    for (const item of orderItems.value) {
        if (!item.ingredients || item.ingredients.length === 0) continue;

        for (const ing of item.ingredients) {
            const ingredientId = ing.inventory_item_id;
            const availableStock = parseFloat(ing.inventory_stock);
            const requiredQty = parseFloat(ing.quantity) * item.qty;

            // Initialize available stock for this ingredient (if not already)
            if (!ingredientStock[ingredientId]) {
                ingredientStock[ingredientId] = {
                    name: ing.product_name,
                    remaining: availableStock,
                };
            }

            // Deduct required quantity from remaining stock
            if (ingredientStock[ingredientId].remaining >= requiredQty) {
                ingredientStock[ingredientId].remaining -= requiredQty;
            } else {
                // Not enough stock → toast and stop checking further
                toast.error(
                    `Not enough stock for "${item.title}". Please remove it from the cart to proceed.`
                );
                return false;
            }
        }
    }

    // All items successfully allocated
    return true;
};





const openConfirmModal = () => {
    if (orderItems.value.length === 0) {
        toast.error("Please add at least one item to the cart.");
        return;
    }
    if (orderType.value === "Dine_in" && !selectedTable.value) {
        formErrors.value.table_number = [
            "Table number is required for dine-in orders.",
        ];
        toast.error("Please select a table number for Dine In orders.");
        return;
    }
    if ((orderType.value === "Delivery" || orderType.value === "Takeaway") && !customer.value) {
        formErrors.value.customer = [
            "Customer name is required for delivery.",
        ];
        toast.error("Customer name is required for delivery.");
        return;
    }
    if (!hasEnoughStockForOrder()) {
        // Stop the process if not enough stock
        return;
    }

    cashReceived.value = grandTotal.value;
    showConfirmModal.value = true;
};




/* ----------------------------
   Print Receipt 
-----------------------------*/
function printReceipt(order) {
    const plainOrder = JSON.parse(JSON.stringify(order));

    // Normalize the payment fields so it works for all cases
    const cash = Number(plainOrder.cashReceived ?? plainOrder.cash_received ?? 0);
    const card = Number(plainOrder.cardAmount ?? plainOrder.cardPayment ?? 0);
    const change = Number(plainOrder.changeAmount ?? plainOrder.change ?? 0);

    console.log("Printing receipt for order:", plainOrder);
    const type = (plainOrder?.payment_type || "").toLowerCase();
    let payLine = "";

    if (type === "split") {
        const cardAmount =
            Number(plainOrder.total_amount || plainOrder.sub_total || 0) - cash || 0;
        payLine = `Split (Cash: £${cash.toFixed(2)}, Card: £${cardAmount.toFixed(
            2
        )})`;
    } else if (type === "card" || type === "stripe") {
        payLine = `Card${plainOrder?.card_brand ? ` (${plainOrder.card_brand}` : ""}${plainOrder?.last4 ? ` •••• ${plainOrder.last4}` : ""
            }${plainOrder?.card_brand ? ")" : ""}`;
    } else {
        payLine = plainOrder?.payment_method || "Cash";
    }


    const businessName =
        page.props.business_info.business_name || "Business Name";
    const businessPhone = page.props.business_info.phone || "+4477221122";
    const businessAddress = page.props.business_info.address || "XYZ";

    const businessLogo = page.props.business_info.image_url || "";
    const html = `
    <html>
    <head>
      <title>Customer Receipt</title>
      <style>
        @page { size: 80mm auto; margin: 0; }
        html, body {
          width: 78mm;
          margin: 0;
          padding: 8px 10px 8px 8px;
          font-family: monospace, Arial, sans-serif;
          font-size: 12px;
          line-height: 1.4;
          box-sizing: border-box;
        }
        .header { text-align: center; margin-bottom: 10px; }
        .order-info { margin: 10px 0; word-break: break-word; }
        .row {
          display: flex;
          justify-content: space-between;
          margin: 2px 0;
        }
        .label { text-align: left; }
        .value { text-align: right; }
        table {
          width: 100%;
          border-collapse: collapse;
          margin: 10px 0;
          table-layout: fixed;
        }
        th, td {
          padding: 4px 2px;
          text-align: left;
          word-wrap: break-word;
        }
        th {
          border-bottom: 1px solid #000;
        }
        td:last-child, th:last-child {
          text-align: right;
          padding-right: 4px;
        }
        .totals {
          margin-top: 10px;
          border-top: 1px dashed #000;
          padding-top: 8px;
        }
        .footer {
          text-align: center;
          margin-top: 10px;
          font-size: 11px;
        }
        .totals > div {
          display: flex;
          justify-content: space-between;
          margin: 3px 0;
        }
        .header img {
          max-width: 60px;
          max-height: 60px;
          object-fit: contain;
          margin-bottom: 5px;
          border-radius: 50%;
        }
        .business-name {
          font-size: 14px;
          font-weight: bold;
          text-transform: uppercase;
        }
      </style>
    </head>
    <body>
      <div class="header">
        ${businessLogo ? `<img src="${businessLogo}" alt="Logo">` : ""}
        <div class="business-name">${businessName}</div>
        <div class="business-phone">${businessPhone}</div>
        <h2>Customer Receipt</h2>
      </div>

      <!-- 🔧 Updated section -->
      <div class="order-info">
        <div class="row"><span class="label">Date:</span><span class="value">${plainOrder.order_date || new Date().toLocaleDateString()
        }</span></div>
        <div class="row"><span class="label">Time:</span><span class="value">${plainOrder.order_time || new Date().toLocaleTimeString()
        }</span></div>
        <div class="row"><span class="label">Customer:</span><span class="value">${plainOrder.customer_name || "Walk In"
        }</span></div>
        <div class="row"><span class="label">Order Type:</span><span class="value">${plainOrder.order_type || "In-Store"
        }</span></div>
        ${plainOrder.note
            ? `<div class="row"><span class="label">Note:</span><span class="value">${plainOrder.note}</span></div>`
            : ""
        }
        <div class="row"><span class="label">Payment Type:</span><span class="value">${payLine}</span></div>
      </div>

      <table>
        <thead>
          <tr>
            <th style="width: 30%;">Item</th>
            <th style="width: 25%;">Qty</th>
            <th style="width: 25%;">Price</th>
            <th style="width: 30%;">Total</th>
          </tr>
        </thead>
        <tbody>
          ${(plainOrder.items || [])
            .map((item) => {
                const qty = Number(item.quantity) || Number(item.qty) || 0;
                const price = qty > 0 ? (Number(item.price) || 0) / qty : 0;
                const total = price * qty;
                return `
                <tr>
                  <td style="font-size: 12px;">${item.title || "Unknown Item"}</td>
                  <td style="font-size: 12px;">${qty}</td>
                  <td style="font-size: 12px;">£${price.toFixed(2)}</td>
                  <td style="font-size: 12px;">£${total.toFixed(2)}</td>
                </tr>
              `;
            })
            .join("")}
        </tbody>
      </table>

      <div class="totals">
        <div><span>Subtotal:</span><span>£${Number(
                plainOrder.sub_total || 0
            ).toFixed(2)}</span></div>
        ${plainOrder.promo_discount
            ? `<div><span>Promo Discount:</span><span>-£${Number(
                plainOrder.promo_discount
            ).toFixed(2)}</span></div>`
            : ""
        }
        <div><span><strong>Total:</strong></span><span><strong>£${Number(
            plainOrder.total_amount || plainOrder.sub_total || 0
        ).toFixed(2)}</strong></span></div>
        ${plainOrder.cash_received
            ? `<div><span>Cash Received:</span><span>£${Number(
                plainOrder.cash_received
            ).toFixed(2)}</span></div>`
            : ""
        }
        ${plainOrder.change
            ? `<div><span>Change:</span><span>£${Number(
                plainOrder.change
            ).toFixed(2)}</span></div>`
            : ""
        }
      </div>
      <div class="footer">
          <div>${businessAddress}</div>
       <div>Customer Copy - Thank you for your purchase!</div>
      </div>
    </body>
    </html>
  `;

    const w = window.open("", "_blank", "width=400,height=600");
    if (!w) {
        alert("Please allow popups for this site to print receipts");
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


const paymentMethod = ref("cash");
const changeAmount = ref(0);

/* ----------------------------
   Print KOT - FIXED
-----------------------------*/

function printKot(order) {
    const plainOrder = JSON.parse(JSON.stringify(order));

    const type = (plainOrder?.payment_method || "").toLowerCase();
    let payLine = "";
    if (type === "split") {
        payLine = `Split (Cash: £${Number(plainOrder?.cash_amount ?? 0).toFixed(2)}, Card: £${Number(plainOrder?.card_amount ?? 0).toFixed(2)})`;
    } else if (type === "card" || type === "stripe") {
        payLine = `Card${plainOrder?.card_brand ? ` (${plainOrder.card_brand}` : ""}${plainOrder?.last4 ? ` •••• ${plainOrder.last4}` : ""}${plainOrder?.card_brand ? ")" : ""}`;
    } else {
        payLine = plainOrder?.payment_method || "Cash";
    }

    const businessName = page.props.business_info.business_name || "Business Name";
    const businessPhone = page.props.business_info.phone || "+4477221122";
    const businessAddress = page.props.business_info.address || "XYZ";
    const businessLogo = page.props.business_info.image_url || "";

    const html = `
    <html>
    <head>
      <title>Kitchen Order Ticket</title>
      <style>
        @page { size: 80mm auto; margin: 0; }
        html, body {
          width: 78mm;
          margin: 0;
          padding: 8px 10px 8px 8px;
          font-family: monospace, Arial, sans-serif;
          font-size: 12px;
          line-height: 1.4;
          box-sizing: border-box;
        }
        .header { text-align: center; margin-bottom: 10px; }
        .order-info { margin: 10px 0; word-break: break-word; }
        .row {
          display: flex;
          justify-content: space-between;
          margin: 2px 0;
        }
        .label { text-align: left; }
        .value { text-align: right; }
        table {
          width: 100%;
          border-collapse: collapse;
          margin: 10px 0;
          table-layout: fixed;
        }
        th, td {
          padding: 4px 2px;
          text-align: left;
          word-wrap: break-word;
        }
        th {
          border-bottom: 1px solid #000;
        }
        td:last-child, th:last-child {
          text-align: right;
          padding-right: 4px;
        }
        .totals {
          margin-top: 10px;
          border-top: 1px dashed #000;
          padding-top: 8px;
        }
        .footer {
          text-align: center;
          margin-top: 10px;
          font-size: 11px;
        }
        .totals > div {
          display: flex;
          justify-content: space-between;
          margin: 3px 0;
        }
        .header img {
          max-width: 60px;
          max-height: 60px;
          object-fit: contain;
          margin-bottom: 5px;
          border-radius: 50%;
        }
        .business-name {
          font-size: 14px;
          font-weight: bold;
          text-transform: uppercase;
        }
      </style>
    </head>
    <body>
      <div class="header">
        ${businessLogo ? `<img src="${businessLogo}" alt="Logo">` : ""}
        <div class="business-name">${businessName}</div>
        <div class="business-phone">${businessPhone}</div>
        <h2>KITCHEN ORDER TICKET</h2>
      </div>

      <!-- Same design as Customer Receipt -->
      <div class="order-info">
        <div class="row"><span class="label">Date:</span><span class="value">${plainOrder.order_date || new Date().toLocaleDateString()}</span></div>
        <div class="row"><span class="label">Time:</span><span class="value">${plainOrder.order_time || new Date().toLocaleTimeString()}</span></div>
        <div class="row"><span class="label">Customer:</span><span class="value">${plainOrder.customer_name || "Walk In"}</span></div>
        <div class="row"><span class="label">Order Type:</span><span class="value">${plainOrder.order_type || "In-Store"}</span></div>
        ${plainOrder.note ? `<div class="row"><span class="label">Note:</span><span class="value">${plainOrder.note}</span></div>` : ""}
        <div class="row"><span class="label">Payment Type:</span><span class="value">${payLine}</span></div>
      </div>

      <table>
        <thead>
          <tr>
            <th style="width: 30%;">Item</th>
            <th style="width: 25%;">Qty</th>
            <th style="width: 25%;">Price</th>
            <th style="width: 30%;">Total</th>
          </tr>
        </thead>
        <tbody>
          ${(plainOrder.items || [])
            .map((item) => {
                const qty = Number(item.quantity) || Number(item.qty) || 0;
                const price = qty > 0 ? (Number(item.price) || 0) / qty : 0;
                const total = price * qty;
                return `
                <tr>
                  <td style="font-size: 12px;">${item.title || "Unknown Item"}</td>
                  <td style="font-size: 12px;">${qty}</td>
                  <td style="font-size: 12px;">£${price.toFixed(2)}</td>
                  <td style="font-size: 12px;">£${total.toFixed(2)}</td>
                </tr>
              `;
            })
            .join("")}
        </tbody>
      </table>

      <div class="totals">
        <div><span>Subtotal:</span><span>£${Number(plainOrder.sub_total || 0).toFixed(2)}</span></div>
        <div><span><strong>Total:</strong></span><span><strong>£${Number(plainOrder.total_amount || 0).toFixed(2)}</strong></span></div>
      </div>

      <div class="footer">
        <div>${businessAddress}</div>
        <div>Kitchen Copy - Thank you!</div>
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


/* ----------------------------
   Confirm Order
-----------------------------*/
const confirmOrder = async ({
    paymentMethod,
    cashReceived,
    cardAmount,  // ✅ Add this parameter
    changeAmount,
    items,
    autoPrintKot,
    done
}) => {
    formErrors.value = {};
    try {
        const payload = {
            customer_name: customer.value,
            sub_total: subTotal.value,
            // Promo Details
            promo_discount: promoDiscount.value,
            promo_id: selectedPromo.value?.id || null,
            promo_name: selectedPromo.value?.name || null,
            promo_type: selectedPromo.value?.type || null,
            total_amount: grandTotal.value,
            tax: 0,
            service_charges: 0,
            delivery_charges: deliveryCharges.value,
            note: note.value,
            order_date: new Date().toISOString().split("T")[0],
            order_time: new Date().toTimeString().split(" ")[0],
            order_type:
                orderType.value === "Dine_in"
                    ? "Dine In"
                    : orderType.value === "Delivery"
                        ? "Delivery"
                        : orderType.value === "Takeaway"
                            ? "Takeaway"
                            : "Collection",
            table_number: selectedTable.value?.name || null,
            payment_method: paymentMethod,
            auto_print_kot: autoPrintKot,

            // ✅ Add split payment handling
            cash_received: cashReceived,
            change: changeAmount,

            // ✅ Add these for split payments
            ...(paymentMethod === 'Split' && {
                payment_type: 'split',
                cash_amount: cashReceived,
                card_amount: cardAmount
            }),

            items: (orderItems.value ?? []).map((it) => ({
                product_id: it.id,
                title: it.title,
                quantity: it.qty,
                price: it.price,
                note: it.note ?? "",
                unit_price: it.unit_price,
            })),
        };

        const res = await axios.post("/pos/order", payload);
        resetCart();
        showConfirmModal.value = false;
        toast.success(res.data.message);

        // ✅ Merge the response with payload to include split payment data
        lastOrder.value = {
            ...res.data.order,
            ...payload,
            items: payload.items,
            // Ensure split payment data is included
            payment_type: paymentMethod === 'Split' ? 'split' : paymentMethod.toLowerCase(),
            cash_amount: paymentMethod === 'Split' ? cashReceived : null,
            card_amount: paymentMethod === 'Split' ? cardAmount : null,
        };

        // Open KOT modal after confirmation
        if (autoPrintKot) {
            kotData.value = await openPosOrdersModal();
            printKot(JSON.parse(JSON.stringify(lastOrder.value)));
            showKotModal.value = true;
        }

        // Print receipt immediately
        printReceipt(JSON.parse(JSON.stringify(lastOrder.value)));

        // Clear promo after successful order
        selectedPromo.value = null;
    } catch (err) {
        console.error("Order submission error:", err);
        toast.error(err.response?.data?.message || "Failed to place order");
    }
    finally {
        if (done) done();
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
    // if (s) toast.success(s);
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



const handleKotStatusUpdated = ({ id, status, message }) => {
    console.log("KOT updated:", id, status, message);

    kotData.value = kotData.value.map((kot) =>
        kot.id === id ? { ...kot, status } : kot
    );

    // toast.success(message); // optional
};


const showKotModal = ref(false);
const kotData = ref([]);
const kotLoading = ref(false);

const openOrderModal = async () => {
    showKotModal.value = true;   // show modal immediately
    kotData.value = [];           // clear old data
    kotLoading.value = true;      // start loading

    try {
        const res = await axios.get(`/api/pos/orders/today`);
        kotData.value = res.data.orders;
    } catch (err) {
        console.error("Failed to fetch today's orders:", err);
        toast.error(
            err.response?.data?.message || "Failed to fetch today's orders"
        );
        kotData.value = [];
    } finally {
        kotLoading.value = false;  // stop loading
    }
};


const showPosOrdersModal = ref(false);
const posOrdersData = ref([]);


const loading = ref(false);

const openPosOrdersModal = async () => {
    showPosOrdersModal.value = true;
    posOrdersData.value = [];
    loading.value = true;

    try {
        const res = await axios.get(`/api/pos/orders/today`);
        posOrdersData.value = res.data.orders;
    } catch (err) {
        console.error("Failed to fetch POS orders:", err);
        toast.error(
            err.response?.data?.message || "Failed to fetch POS orders"
        );
    } finally {
        loading.value = false;
    }
};

const showPromoModal = ref(false);
const loadingPromos = ref(true);
const promosData = ref([]);
const selectedPromo = ref(null);

// Update handleApplyPromo function
const handleApplyPromo = (promo) => {
    console.log("Applying promo:", promo);
    selectedPromo.value = promo;

    // Check if cart meets minimum purchase requirement
    if (promo.min_purchase && subTotal.value < parseFloat(promo.min_purchase)) {
        toast.warning(`Minimum purchase of ${formatCurrencySymbol(promo.min_purchase)} required for this promo.`);
    } else {
        toast.success(`Promo "${promo.name}" applied! You saved ${formatCurrencySymbol(promoDiscount.value)}`);
    }

    showPromoModal.value = false;
};



const handleClearPromo = () => {
    selectedPromo.value = null;
    toast.info("Promo cleared.");
};


const openPromoModal = async () => {
    loadingPromos.value = true;
    try {
        // Show the modal immediately (optional)
        showPromoModal.value = true;

        // Fetch promos from API
        const response = await axios.get('/api/promos/today');
        if (response.data.success) {
            promosData.value = response.data.data;
        } else {
            console.error('Failed to fetch promos', response.data);
            promosData.value = [];
        }
    } catch (error) {
        console.error('Error fetching promos', error);
        promosData.value = [];
    } finally {
        loadingPromos.value = false;
    }
};

const handleViewOrderDetails = (order) => {
    lastOrder.value = order;
    showReceiptModal.value = true;
    showPosOrdersModal.value = false;
};

// =============Promos Discount ======================
// Add these computed properties after your existing computed properties

const promoDiscount = computed(() => {
    if (!selectedPromo.value) return 0;

    const promo = selectedPromo.value;
    const rawDiscount = parseFloat(promo.discount_amount ?? 0) || 0;
    const subtotal = subTotal.value;

    // Check minimum purchase requirement
    if (promo.min_purchase && subtotal < parseFloat(promo.min_purchase)) {
        return 0;
    }

    // Calculate based on promo type
    if (promo.type === 'flat') {
        return rawDiscount;
    }

    if (promo.type === 'percent') {
        const discount = (subtotal * rawDiscount) / 100;
        const maxCap = parseFloat(promo.max_discount ?? 0) || 0;
        if (maxCap > 0 && discount > maxCap) {
            return maxCap;
        }
        return discount;
    }

    return 0;
});


// ========================================
// // Get quantity for a product in the cart
// ========================================
const getCardQty = (product) => {
    const cartItem = orderItems.value.find(item => item.id === product.id);
    return cartItem ? cartItem.qty : 0;
};

// Check if we can add more of this product
const canAddMore = (product) => {
    const currentQty = getCardQty(product);
    const menuStock = calculateMenuStock(product);

    if (menuStock <= 0) return false;
    if (currentQty >= menuStock) return false;

    return checkIngredientAvailability(product, currentQty + 1);
};

// Check if ingredients are available for a specific quantity
const checkIngredientAvailability = (product, targetQty) => {
    if (!product.ingredients?.length) return true;

    const ingredientStock = {};

    // Build current stock map based on items in cart
    for (const item of orderItems.value) {
        if (!item.ingredients?.length) continue;
        for (const ing of item.ingredients) {
            const id = ing.inventory_item_id;
            if (!ingredientStock[id]) {
                ingredientStock[id] = parseFloat(ing.inventory_stock);
            }
            ingredientStock[id] -= parseFloat(ing.quantity) * item.qty;
        }
    }

    // Check if enough stock for one more unit
    for (const ing of product.ingredients) {
        const id = ing.inventory_item_id;
        const availableStock = ingredientStock[id] ?? parseFloat(ing.inventory_stock);
        const requiredQty = parseFloat(ing.quantity);

        if (availableStock < requiredQty) {
            return false;
        }
    }

    return true;
};

// Increment quantity directly from card
const incrementCardQty = (product) => {
    const currentQty = getCardQty(product);
    const menuStock = calculateMenuStock(product);

    if ((product.stock ?? 0) <= 0) {
        toast.error(`${product.title} is out of stock.`);
        return;
    }

    if (currentQty >= menuStock) {
        toast.error(`Not enough stock for "${product.title}".`);
        return;
    }

    if (!checkIngredientAvailability(product, currentQty + 1)) {
        toast.error(`Not enough ingredients for "${product.title}".`);
        return;
    }

    const existingIndex = orderItems.value.findIndex(item => item.id === product.id);

    if (existingIndex >= 0) {
        orderItems.value[existingIndex].qty++;
        orderItems.value[existingIndex].price =
            orderItems.value[existingIndex].unit_price * orderItems.value[existingIndex].qty;
        orderItems.value[existingIndex].outOfStock = false;
    } else {
        addToOrder(product, 1, "");
    }
};

// Decrement quantity directly from card
const decrementCardQty = (product) => {
    const existingIndex = orderItems.value.findIndex(item => item.id === product.id);
    if (existingIndex < 0) return;

    const item = orderItems.value[existingIndex];
    if (item.qty <= 1) {
        orderItems.value.splice(existingIndex, 1);

    } else {
        item.qty--;
        item.price = item.unit_price * item.qty;
        item.outOfStock = false;
    }
};

console.log("props data", page.props);
</script>

<template>

    <Head title="POS Order" />

    <Master>
        <div class="page-wrapper">
            <div class="container-fluid px-3 py-3">
                <div class="row gx-3 gy-3">
                    <!-- LEFT: Menu -->
                    <div :class="showCategories ? 'col-md-12' : 'col-lg-8'">
                        <!-- Categories Grid -->
                        <div v-if="showCategories" class="row g-3">
                            <div class="col-12 mb-3">
                                <h5 class="fw-bold text-primary mb-0">Menu Categories</h5>
                                <hr class="mt-2 mb-3">
                            </div>
                            <div v-if="menuCategoriesLoading" class="col-12 text-center py-5">
                                <div class="spinner-border" role="status"
                                    style="color: #1B1670; width: 3rem; height: 3rem; border-width: 0.3em;"></div>
                                <div class="mt-2 fw-semibold text-muted">Loading...</div>
                            </div>


                            <!-- Categories List -->
                            <template v-else>
                                <div v-for="c in menuCategories" :key="c.id" class="col-6 col-md-4 col-lg-3">
                                    <div class="cat-card" @click="openCategory(c)">
                                        <div class="cat-icon-wrap">
                                            <span class="cat-icon">{{ c.icon || "🍵" }}</span>
                                        </div>
                                        <div class="cat-name">{{ c.name }}</div>
                                        <div class="cat-pill">
                                            {{ c.menu_items_count }} Menu
                                        </div>
                                    </div>
                                </div>

                                <!-- No Categories Found -->
                                <div v-if="!menuCategoriesLoading && menuCategories.length === 0" class="col-12">
                                    <div class="alert alert-light border text-center rounded-4">
                                        No categories found
                                    </div>
                                </div>
                            </template>
                        </div>


                        <!-- Items in selected category -->
                        <div v-else>
                            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
                                <button class="btn btn-primary rounded-pill shadow-sm px-3" @click="backToCategories">
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

                            <!-- <div class="row g-3">
                                <div class="col-6 col-md-4 col-xl-3 d-flex" v-for="p in filteredProducts"
                                    :key="p.title">
                                    <div class="item-card" :class="{
                                        'out-of-stock': (p.stock ?? 0) <= 0,
                                    }" :style="{
                                        border:
                                            '2px solid ' +
                                            (p.label_color || '#1B1670'),
                                    }" @click="
                                        (p.stock ?? 0) > 0 && openItem(p)
                                        ">
                                        <div class="item-img">
                                            <img :src="p.img" alt="" />
                                            <span class="item-price rounded-pill" :style="{
                                                background:
                                                    p.label_color ||
                                                    '#1B1670',
                                            }">
                                                {{ formatCurrencySymbol(p.price) }}
                                            </span>

                                            <span v-if="(p.stock ?? 0) <= 0" class="item-badge">OUT OF STOCK</span>
                                        </div>

                                        <div class="item-body">
                                            <div class="item-title" :style="{
                                                color: (p.label_color || '#1B1670'),
                                            }">
                                                {{ p.title }}
                                            </div>
                                            <div class="item-sub" :style="{
                                                color: (p.label_color || '#1B1670'),
                                            }">
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
                            </div> -->


                            <!-- <div class="row g-3">
                                <div class="col-12 col-md-8 col-xl-8 d-flex" v-for="p in filteredProducts"
                                    :key="p.title">
                                    <div class="card rounded-4 shadow-sm overflow-hidden border-3 w-100 d-flex flex-row align-items-stretch"
                                        :class="{ 'out-of-stock': (p.stock ?? 0) <= 0 }"
                                        :style="{ borderColor: p.label_color || '#1B1670' }">

                                       
                                        <div class="p-2 d-flex flex-column align-items-center justify-content-between position-relative bg-light"
                                            style="flex: 0 0 40%; max-width: 40%; position: relative;">

                                            <div
                                                class="d-flex align-items-center justify-content-center w-100 flex-grow-1">
                                                <img :src="p.img" alt="" class="img-fluid rounded-3"
                                                    style="max-height: 150px; object-fit: contain;" />
                                            </div>

                                          
                                            <span
                                                class="position-absolute top-0 start-0 m-2 px-3 py-1 rounded-pill text-white small"
                                                :style="{ background: p.label_color || '#1B1670' }">
                                                {{ formatCurrencySymbol(p.price) }}
                                            </span>

                                           
                                            <span v-if="(p.stock ?? 0) <= 0"
                                                class="position-absolute bottom-0 start-0 m-2 badge bg-danger">
                                                OUT OF STOCK
                                            </span>

                                          
                                            <div v-if="(p.stock ?? 0) > 0"
                                                class="qty-group d-flex align-items-center justify-content-center gap-2 mt-2 w-100"
                                                @click.stop style="padding: 0.5rem;">
                                                <button class="qty-btn btn px-2 py-2 btn-outline-secondary btn-sm"
                                                    @click.stop="decrementCardQty(p)"
                                                    :disabled="getCardQty(p) <= 0">−</button>
                                                <div class="qty-box border rounded-pill px-2 py-2 text-center small">
                                                    {{ getCardQty(p) }}
                                                </div>
                                                <button class="qty-btn btn btn-outline-secondary btn-sm rounded-circle"
                                                    @click.stop="incrementCardQty(p)"
                                                    :disabled="!canAddMore(p)">+</button>
                                            </div>
                                        </div>

                                     
                                        <div class="p-3 d-flex flex-column justify-content-between"
                                            style="flex: 1 1 60%; min-width: 0;">

                                            <div>
                                                <div class="h5 fw-bold mb-1">
                                                    {{ p.title }}
                                                </div>
                                                <div class="text-muted mb-2 small"
                                                    :style="{ color: p.label_color || '#1B1670' }">
                                                    {{ p.family }}
                                                </div>

                                              
                                                <div class="chips small">
                                                  
                                                    <div v-if="p.nutrition" class="mb-3">
                                                        <strong class="d-block mb-1">Nutrition:</strong>
                                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                                            <span v-if="p.nutrition.calories" class="chip chip-orange">
                                                                Cal: {{ p.nutrition.calories }}
                                                            </span>
                                                            <span v-if="p.nutrition.carbs" class="chip chip-green">
                                                                Carbs: {{ p.nutrition.carbs }}
                                                            </span>
                                                            <span v-if="p.nutrition.fat" class="chip chip-purple">
                                                                Fat: {{ p.nutrition.fat }}
                                                            </span>
                                                            <span v-if="p.nutrition.protein" class="chip chip-blue">
                                                                Protein: {{ p.nutrition.protein }}
                                                            </span>
                                                        </div>
                                                    </div>

                                                   
                                                    <div v-if="p.allergies?.length" class="mb-3">
                                                        <strong class="d-block mb-1">Allergies:</strong>
                                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                                            <span v-for="(a, i) in p.allergies" :key="'a-' + i"
                                                                class="chip chip-red">
                                                                {{ a.name }}
                                                            </span>
                                                        </div>
                                                    </div>

                                                   
                                                    <div v-if="p.tags?.length">
                                                        <strong class="d-block mb-1">Tags:</strong>
                                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                                            <span v-for="(t, i) in p.tags" :key="'t-' + i"
                                                                class="chip chip-teal">
                                                                {{ t.name }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div> -->


                            <div class="row g-3">
                                <div class="col-12 col-md-8 col-xl-8 d-flex" v-for="p in filteredProducts"
                                    :key="p.title">
                                    <div class="card rounded-4 shadow-sm overflow-hidden border-3 w-100 d-flex flex-row align-items-stretch"
                                        :class="{ 'out-of-stock': (p.stock ?? 0) <= 0 }"
                                        :style="{ borderColor: p.label_color || '#1B1670' }">

                                        <!-- Left Side (Image + Price Badge) - 40% -->
                                        <div class="position-relative" style="flex: 0 0 40%; max-width: 40%;">

                                            <!-- Image fills entire area -->
                                            <img :src="p.img" alt="" class="w-100 h-100" style="object-fit: cover;" />

                                            <!-- Price Badge -->
                                            <span
                                                class="position-absolute top-0 start-0 m-2 px-3 py-1 rounded-pill text-white small fw-semibold"
                                                :style="{ background: p.label_color || '#1B1670' }">
                                                {{ formatCurrencySymbol(p.price) }}
                                            </span>

                                            <!-- OUT OF STOCK Badge -->
                                            <span v-if="(p.stock ?? 0) <= 0"
                                                class="position-absolute bottom-0 start-0 end-0 m-2 badge bg-danger py-2">
                                                OUT OF STOCK
                                            </span>
                                        </div>

                                        <!-- Right Side (Details + Quantity Controls) - 60% -->
                                        <div class="p-3 d-flex flex-column justify-content-between"
                                            style="flex: 1 1 60%; min-width: 0;">

                                            <!-- Title and Family -->
                                            <div>
                                                <div class="h5 fw-bold mb-1"
                                                    :style="{ color: p.label_color || '#1B1670' }">
                                                    {{ p.title }}
                                                </div>
                                                <div class="text-muted mb-3 small">
                                                    {{ p.family }}
                                                </div>

                                                <!-- Chips Section -->
                                                <div class="chips small">
                                                    <!-- Nutrition -->
                                                    <div v-if="p.nutrition" class="mb-2">
                                                        <strong class="d-block mb-1">Nutrition:</strong>
                                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                                            <span v-if="p.nutrition.calories" class="chip chip-orange">
                                                                Cal: {{ p.nutrition.calories }}
                                                            </span>
                                                            <span v-if="p.nutrition.carbs" class="chip chip-green">
                                                                Carbs: {{ p.nutrition.carbs }}
                                                            </span>
                                                            <span v-if="p.nutrition.fat" class="chip chip-purple">
                                                                Fat: {{ p.nutrition.fat }}
                                                            </span>
                                                            <span v-if="p.nutrition.protein" class="chip chip-blue">
                                                                Protein: {{ p.nutrition.protein }}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <!-- Allergies -->
                                                    <div v-if="p.allergies?.length" class="mb-2">
                                                        <strong class="d-block mb-1">Allergies:</strong>
                                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                                            <span v-for="(a, i) in p.allergies" :key="'a-' + i"
                                                                class="chip chip-red">
                                                                {{ a.name }}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <!-- Tags -->
                                                    <div v-if="p.tags?.length" class="mb-2">
                                                        <strong class="d-block mb-1">Tags:</strong>
                                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                                            <span v-for="(t, i) in p.tags" :key="'t-' + i"
                                                                class="chip chip-teal">
                                                                {{ t.name }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Quantity Controls at Bottom Right -->
                                            <div v-if="(p.stock ?? 0) > 0"
                                                class="mt-3 d-flex align-items-center justify-content-start gap-2"
                                                @click.stop>
                                                <button class="qty-btn btn btn-outline-secondary rounded-circle px-4"
                                                    style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;"
                                                    @click.stop="decrementCardQty(p)" :disabled="getCardQty(p) <= 0">
                                                    <strong>−</strong>
                                                </button>
                                                <div class="qty-box border rounded-pill px-4 py-2 text-center fw-semibold"
                                                    style="min-width: 50px;">
                                                    {{ getCardQty(p) }}
                                                </div>
                                                <button class="qty-btn btn btn-outline-secondary rounded-circle px-4"
                                                    style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;"
                                                    @click.stop="incrementCardQty(p)" :disabled="!canAddMore(p)">
                                                    <strong>+</strong>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- No Items Found -->
                                <div v-if="filteredProducts.length === 0" class="col-12">
                                    <div class="alert alert-light border text-center rounded-4">
                                        No items found
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- RIGHT: Cart -->

                    <div class="col-lg-4" v-if="!showCategories">
                        <div class="col-lg-4 d-flex align-items-center gap-2 mb-2">
                            <!-- KOT Orders button -->
                            <button class="btn btn-primary rounded-pill d-flex align-items-center gap-2 px-4"
                                @click="openOrderModal">
                                <Package class="lucide-icon" width="16" height="16" />
                                KOT
                            </button>

                            <!-- POS Orders button -->
                            <button class="btn btn-success rounded-pill d-flex align-items-center gap-2 px-3"
                                @click="openPosOrdersModal">
                                <ShoppingCart class="lucide-icon" width="16" height="16" />
                                Orders
                            </button>
                            <button class="btn btn-warning rounded-pill" @click="openPromoModal">
                                Promos
                            </button>

                        </div>

                        <div class="cart card border-0 shadow-lg rounded-4">

                            <div class="cart-header">

                                <div class="order-type">
                                    <button v-for="(type, i) in orderTypes" :key="i" class="ot-pill btn"
                                        :class="{ active: orderType === type }" @click="orderType = type">
                                        {{ type.replace(/_/g, " ") }}
                                    </button>
                                    <div class="d-flex justify-content-between mb-3">

                                    </div>
                                </div>
                            </div>

                            <div class="cart-body">
                                <!-- Dine-in table / customer -->
                                <div class="mb-3">

                                    <div v-if="orderType === 'Dine_in'" class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label small">Table</label>
                                            <select v-model="selectedTable" class="form-select form-select-sm">
                                                <option :value="null">Select Table</option>
                                                <option v-for="(table, idx) in profileTables.table_details" :key="idx"
                                                    :value="table">
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
                                            placeholder="Walk In" :class="{ 'is-invalid': formErrors.customer }" />

                                        <div v-if="formErrors.customer" class="invalid-feedback d-block">
                                            {{ formErrors.customer[0] }}
                                        </div>
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
                                            <button class="qty-btn" :class="[
                                                (it.outOfStock || it.qty >= (it.stock ?? 0))
                                                    ? 'bg-secondary text-white cursor-not-allowed opacity-70'
                                                    : ''
                                            ]" @click="incCart(i)"
                                                :disabled="it.outOfStock || it.qty >= (it.stock ?? 0)">
                                                +
                                            </button>


                                        </div>

                                        <div class="line-right">
                                            <div class="price">
                                                {{ formatCurrencySymbol(it.price) }}
                                            </div>
                                            <button class="del" @click="removeCart(i)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Totals -->
                                <!-- Totals -->
                                <div class="totals">
                                    <div class="trow">
                                        <span>Sub Total</span>
                                        <b class="sub-total">{{ formatCurrencySymbol(subTotal) }}</b>
                                    </div>

                                    <!-- Delivery Charges -->
                                    <div class="trow" v-if="orderType === 'Delivery'">
                                        <span>Delivery</span>
                                        <b>{{ deliveryPercent }}%</b>
                                    </div>

                                    <!-- Promo Discount -->
                                    <div class="trow promo-discount" v-if="selectedPromo && promoDiscount > 0">
                                        <span class="d-flex align-items-center gap-2">
                                            <i class="bi bi-tag-fill text-success"></i>
                                            Promo: {{ selectedPromo.name }}
                                        </span>
                                        <b class="text-success">-{{ formatCurrencySymbol(promoDiscount) }}</b>
                                    </div>

                                    <!-- Total -->
                                    <div class="trow total">
                                        <span>Total</span>
                                        <b>{{ formatCurrencySymbol(grandTotal) }}</b>
                                    </div>
                                </div>

                                <!-- <textarea v-model="note" rows="3" class="form-control form-control-sm rounded-3"
                                    placeholder="Note"></textarea> -->

                                <textarea v-model="note" rows="3" class="form-control form-control-sm rounded-3"
                                    placeholder="Note"></textarea>
                            </div>

                            <div class="cart-footer">
                                <button class="btn btn-secondary btn-clear" @click="resetCart()">
                                    Clear
                                </button>
                                <button class="btn btn-primary btn-place" @click="openConfirmModal">
                                    Place Order
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Choose Item Modal (unchanged content/ids) -->
            <!-- <div class="modal fade" id="chooseItem" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content rounded-4 border-0 shadow">
                        <div class="modal-header border-0">
                            <h5 class="modal-title fw-bold">
                                {{ selectedItem?.title || "Choose Item" }}
                            </h5>
                            <button
                                class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                                data-bs-dismiss="modal" aria-label="Close" title="Close">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
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
                                        {{
                                            formatCurrencySymbol(
                                                selectedItem?.price || 0
                                            )
                                        }}
                                    </div>

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
                                        <span v-for="(a, i) in selectedItem?.allergies || []" :key="'a-' + i"
                                            class="chip chip-red">{{ a.name }}</span>

                                        <div class="w-100 mt-2">
                                            <strong>Tags:</strong>
                                        </div>
                                        <span v-for="(t, i) in selectedItem?.tags || []" :key="'t-' + i"
                                            class="chip chip-teal">{{
                                                t.name }}</span>
                                    </div>

                                    <div class="qty-group gap-1">
                                        <button class="qty-btn" @click="decQty">
                                            −
                                        </button>
                                        <div class="qty-box rounded-pill">
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
                            <button class="btn btn-primary btn-sm py-2 rounded-pill px-4" @click="confirmAdd">
                                Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div> -->

            <KotModal :show="showKotModal" :kot="kotData" :loading="kotLoading" @close="showKotModal = false"
                @status-updated="handleKotStatusUpdated" />


            <!-- Confirm / Receipt (unchanged props) -->
            <!-- <ConfirmOrderModal :show="showConfirmModal" :customer="customer" :order-type="orderType"
                :selected-table="selectedTable" :order-items="orderItems" :grand-total="grandTotal" :money="money"
                v-model:cashReceived="cashReceived" :client_secret="client_secret" :order_code="order_code"
                :sub-total="subTotal" :tax="0" :service-charges="0" :delivery-charges="0" :note="note"
                :order-date="new Date().toISOString().split('T')[0]"
                :order-time="new Date().toTimeString().split(' ')[0]" :payment-method="paymentMethod"
                :change="changeAmount" @close="showConfirmModal = false" @confirm="confirmOrder" /> -->
            <ReceiptModal :show="showReceiptModal" :order="lastOrder" :money="money"
                @close="showReceiptModal = false" />


            <ConfirmOrderModal :show="showConfirmModal" :customer="customer" :order-type="orderType"
                :selected-table="selectedTable" :order-items="orderItems" :grand-total="grandTotal" :money="money"
                v-model:cashReceived="cashReceived" :client_secret="client_secret" :order_code="order_code"
                :sub-total="subTotal" :tax="0" :service-charges="0" :delivery-charges="deliveryCharges"
                :promo-discount="promoDiscount" :promo-id="selectedPromo?.id" :promo-name="selectedPromo?.name"
                :promo-type="selectedPromo?.type" :promo-discount-amount="promoDiscount" :note="note"
                :order-date="new Date().toISOString().split('T')[0]"
                :order-time="new Date().toTimeString().split(' ')[0]" :payment-method="paymentMethod"
                :change="changeAmount" @close="showConfirmModal = false" @confirm="confirmOrder" />

            <PosOrdersModal :show="showPosOrdersModal" :orders="posOrdersData" @close="showPosOrdersModal = false"
                @view-details="handleViewOrderDetails" :loading="loading" />

            <PromoModal :show="showPromoModal" :loading="loadingPromos" :promos="promosData" :order-items="orderItems"
                @apply-promo="handleApplyPromo" @close="showPromoModal = false" />


        </div>
    </Master>
</template>

<style scoped>
.dark .ot-pill {
    background-color: #181818;
    color: #fff;
}

.dark .cart-body {
    background-color: #181818;
}

.dark .item-title {
    color: #fff !important;
}

.dark .bg-light {
    background-color: #181818 !important;
}

.dark b {
    color: #fff !important;
}

.dark .item-sub {
    color: #fff !important;
}

.dark .cart-footer {
    background-color: #181818;
}

.dark .sub-total {
    color: #fff !important;
}

.dark .form-control {
    background-color: #181818;
    color: white;
}

.dark .item-card {
    background-color: #181818 !important;
    color: white !important;
}

.dark .cart-lines {
    background-color: #181818;
}

.dark .chip-orange {
    color: #0000;
}

.dark .modal-footer {
    background-color: #181818;
}

.dark .alert {
    background-color: #181818;
}

.dark .table {
    background-color: #181818 !important;
    /* gray-900 */
    color: #f9fafb !important;
}

.dark .table thead {
    background-color: #4d4d4d !important;
    color: #ffffff;
}

.dark .table thead th {
    background-color: #4d4d4d !important;
    color: #ffffff;
}

/* ========== Page Base ========== */
.page-wrapper {
    background: #f5f7fb;
}

/* ========== Categories Grid ========== */
.cat-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    background: #fff;
    border-radius: 14px;
    padding: 1.75rem 1rem;
    text-align: center;
    cursor: pointer;
    box-shadow: 0 6px 16px rgba(17, 23, 31, 0.06);
    transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.cat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 24px rgba(17, 23, 31, 0.1);
}

/* circular gray icon holder */
.cat-icon-wrap {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: #eee;
    /* light gray like the screen */
    display: grid;
    place-items: center;
    margin-bottom: 0.25rem;
}

/* the actual icon (emoji/text/svg) */
.cat-icon {
    font-size: 1.35rem;
    /* tweak to match your cup size */
    line-height: 1;
}

/* title */
.cat-name {
    font-weight: 700;
    font-size: 1rem;
    color: #141414;
    /* per your dark accent preference */
}

/* little purple pill with count */
.cat-pill {
    display: inline-block;
    font-size: 0.72rem;
    line-height: 1;
    padding: 0.35rem 0.6rem;
    border-radius: 999px;
    color: #fff;
    background: #1b1670;
    box-shadow: 0 2px 6px rgba(75, 43, 183, 0.25);
}

/* spacing on small screens */
@media (max-width: 575.98px) {
    .cat-card {
        padding: 1.25rem 0.75rem;
    }

    .cat-icon-wrap {
        width: 52px;
        height: 52px;
    }

    .cat-name {
        font-size: 0.95rem;
    }
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
    border: 2px solid #1b1670;
    /* fallback if no inline style */
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

/* out-of-stock look */
.item-card.out-of-stock {
    cursor: not-allowed;
    opacity: 0.9;
    position: relative;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
}

/* overlay background */
.item-card.out-of-stock::after {
    content: "";
    position: absolute;
    inset: 0;
    /* cover whole card */
    background: rgba(90, 85, 85, 0.192);
    /* semi-transparent dark overlay */
    border-radius: 16px;
    /* match card radius */
    z-index: 2;
    /* sit above content */
}

/* make text & badge still visible above overlay */
/* .item-card.out-of-stock .item-body,
.item-card.out-of-stock .item-badge {
    position: relative;
    z-index: 3;
} */
.dark .form-select {
    background-color: #212121;
    color: #fff;
}

.item-price {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #1b1670;
    /* theme purple */
    color: #fff;
    padding: 0.25rem 0.75rem;
    font-weight: 600;
    font-size: 0.8rem;
    border-radius: 999px;
    /* pill shape */
    line-height: 1;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    z-index: 2;
}

.item-badge {
    position: absolute;
    left: 10px;
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
    border-top-left-radius: 1rem;
    border-top-right-radius: 1rem;
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

.dark .ot-pill {
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

.dark .ot-pill.active {
    background-color: #181818;
    color: #fff;
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
    line-height: 1.5;
}

.dark .qty {
    width: 28px;
    height: 28px;
    border-radius: 999px;
    border: 0;
    background: #1b1670;
    color: #fff;
    font-weight: 800;
    line-height: 1.5;
}

.dark .del {
    background-color: #212121 !important;
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
    color: #181818;
    font-size: 16px;
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

.dark .btn-clear {
    flex: 1;
    border: 0;
    background-color: #4b5563;
    color: #fff;
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

.dark .chip {
    font-size: 0.75rem;
    padding: 0.25rem 0.55rem;
    border-radius: 999px;
    background: #181818;
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
    /* border: 1px solid #d0cfd7; */
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
