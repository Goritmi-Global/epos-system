<script setup>
import Master from "@/Layouts/Master.vue";
import { Head } from "@inertiajs/vue3";
import { ref, computed, onMounted } from "vue";
import { toast } from "vue3-toastify";
/* ----------------------------
   Categories (same keys/icons)
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
   Real Products by Category
-----------------------------*/

const productsByCat = computed(() => {
    const grouped = {};

    menuItems.value.forEach((item) => {
        const catId = item.category?.id || "uncategorized";
        const catName = item.category?.name || "Uncategorized";

        if (!grouped[catId]) {
            grouped[catId] = [];
        }

        grouped[catId].push({
            id: item.id,
            title: item.name,
            img: item.image_url || "/assets/img/default.png",
            // ingredientCount: item.ingredients?.length ?? 0, // optional rename
            stock: calculateMenuStock(item),     // <-- computed menu stock
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



// calculate how many servings of a menu item can be made from its ingredients
const calculateMenuStock = (item) => {
    if (!item) return 0;

    // If API provided a direct stock for menu item, use it as fallback
    if (!item.ingredients || item.ingredients.length === 0) {
        return Number(item.stock ?? 0);
    }

    let menuStock = Infinity;
    item.ingredients.forEach((ing) => {
        // pick whatever fields your ingredient object uses:
        const required = Number(ing.quantity ?? ing.qty ?? 1);
        const inventoryStock = Number(ing.inventory_stock ?? ing.stock ?? 0);

        if (required <= 0) return; // ignore bad data

        const possible = Math.floor(inventoryStock / required);
        menuStock = Math.min(menuStock, possible);
    });

    if (menuStock === Infinity) menuStock = 0;
    return menuStock;
};


/* ----------------------------
   UI State
-----------------------------*/
// const activeCat = ref("fruits");
const searchQuery = ref("");
const orderType = ref("dine");
const customer = ref("Walk In");
const deliveryPercent = ref(10); // demo: 10% delivery charges

const activeCat = ref(null); // store ID
const setCat = (id) => {
    activeCat.value = id;
};
const isCat = (id) => activeCat.value === id;


const selectedCategory = ref(null);
// function openCategory(id) {
//     selectedCategory.value = id
// }

// function goBack() {
//     selectedCategory.value = null
// }



const profileTables = ref({});
const orderTypes = ref([]);
const selectedTable = ref(null);

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

const visibleProducts = computed(() => productsByCat.value[activeCat.value] ?? []);

const filteredProducts = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return visibleProducts.value;

    return visibleProducts.value.filter(
        (p) =>
            p.title.toLowerCase().includes(q) ||
            (p.family || "").toLowerCase().includes(q) ||
            (p.description || "").toLowerCase().includes(q) ||
            (p.tags?.map(t => t.name.toLowerCase()).join(", ") || "").includes(q)
    );
});


/* Horizontal scroll arrows for category tabs */
const catScroller = ref(null);
const showCatArrows = computed(() => menuCategories.value.length > 5);
const scrollTabs = (dir) => {
    const el = catScroller.value;
    if (!el) return;
    el.scrollBy({ left: dir === "left" ? -240 : 240, behavior: "smooth" });
};

/* ----------------------------
   Order cart
-----------------------------*/
const orderItems = ref([]);
/* item shape: { title, img, price, qty, note } */
const addToOrder = (baseItem, qty = 1, note = "") => {
    // ensure we have a menu-stock here (in case baseItem.stock missing)
    const menuStock = calculateMenuStock(baseItem);

    const idx = orderItems.value.findIndex((i) => i.title === baseItem.title);
    if (idx >= 0) {
        const newQty = orderItems.value[idx].qty + qty;
        if (newQty <= orderItems.value[idx].stock) {
            orderItems.value[idx].qty = newQty;
            if (note) orderItems.value[idx].note = note;
        } else {
            toast.error("Not enough Ingredients stock available for this Menu.");
        }
    } else {
        if (qty > menuStock) {
            toast.error("Not enough Ingredients stock available for this Menu.");
            return;
        }

        orderItems.value.push({
            id: baseItem.id,
            title: baseItem.title,
            img: baseItem.img,
            price: Number(baseItem.price) || 0,
            qty: qty,
            note: note || "",
            stock: menuStock,                 // <-- store menu-level stock
            ingredients: baseItem.ingredients ?? [], // optional for UI
        });
    }
};



const incCart = async (i) => {
    const it = orderItems.value[i];
    if (!it) return;
    console.log("it", it);

    if ((it.stock ?? 0) <= 0) {
        alert("Item out of stock.");
        return;
    }

    if (it.qty >= (it.stock ?? 0)) {
        alert("Not enough stock to add more of this item.");
        return;
    }

    try {
        // Call backend to reduce stock (stock-out)
        await updateStock(it, 1, "stockout");

        // Only increment if stock update succeeds
        it.qty++;
        console.log(`Stock-out successful. New qty for ${it.title}: ${it.qty}`);
    } catch (err) {
        console.error("Failed to update stock for increment:", err);
        alert("Failed to add item. Please try again.");
    }
};

const decCart = async (i) => {
    const it = orderItems.value[i];
    if (!it || it.qty <= 1) {
        alert("Cannot reduce below 1.");
        return;
    }

    try {
        // Call backend to increase stock (stock-in)
        await updateStock(it, 1, "stockin");

        // Only decrement if stock update succeeds
        it.qty--;
        console.log(`Stock-in successful. New qty for ${it.title}: ${it.qty}`);
    } catch (err) {
        console.error("Failed to update stock for decrement:", err);
        alert("Failed to remove item. Please try again.");
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

/* format £ to 2dp for display only */
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
// const incQty = () => modalQty.value++;
// const decQty = () => (modalQty.value = Math.max(1, modalQty.value - 1));

const menuStockForSelected = computed(() => {
    return calculateMenuStock(selectedItem.value);
});



const confirmAdd = async () => {
    if (!selectedItem.value) return;

    try {
        //  1) Add to cart (UI only)
        addToOrder(selectedItem.value, modalQty.value, modalNote.value);
        console.log(selectedItem.value.ingredients);
        //  2) Stockout each ingredient
        if (selectedItem.value.ingredients && selectedItem.value.ingredients.length) {
            for (const ingredient of selectedItem.value.ingredients) {
                const requiredQty =
                    ingredient.pivot?.qty
                        ? ingredient.pivot.qty * modalQty.value
                        : modalQty.value;

                //Payload matching your request rules
                await axios.post("/stock_entries", {
                    product_id: ingredient.inventory_item_id,
                    name: ingredient.product_name,
                    category_id: ingredient.category_id,
                    supplier_id: ingredient.supplier_id,
                    available_quantity: ingredient.inventory_stock,
                    quantity: ingredient.quantity * modalQty.value,
                    price: null,
                    value: 0,
                    operation_type: "pos_stockout",
                    stock_type: "stockout",
                    expiry_date: null,
                    description: null,
                    purchase_date: null,
                    user_id: ingredient.user_id,
                });

            }
        }

        //  3) Close modal
        if (chooseItemModal) chooseItemModal.hide();
    } catch (err) {
        alert("Stockout failed: " + (err.response?.data?.message || err.message));
    }
};



// ===============================================================
//               Update sotck on inc and dec
// ===============================================================

const updateStock = async (item, qty, type = "stockout") => {
    console.log("updateStock called with:", { item: item.title, qty, type });
    console.log("item.ingredients:", item.ingredients, "length:", item.ingredients?.length);

    if (!item.ingredients || !item.ingredients.length) {
        console.log("No ingredients found, skipping stock update");
        return;
    }

    try {
        for (const ingredient of item.ingredients) {
            console.log("Processing ingredient:", ingredient.product_name);

            // Use the correct quantity field and ensure it's a number
            const ingredientQty = Number(ingredient.quantity) || 1;
            const requiredQty = ingredientQty * qty;

            console.log(`Ingredient ${ingredient.product_name}: ${ingredientQty} x ${qty} = ${requiredQty}`);

            const payload = {
                product_id: ingredient.inventory_item_id,
                name: ingredient.product_name,
                category_id: ingredient.category_id,
                available_quantity: ingredient.inventory_stock,
                quantity: requiredQty, // Fixed calculation
                value: 0,
                operation_type: type === "stockout" ? "pos_stockout" : "pos_stockin",
                stock_type: type,
                expiry_date: null,
                description: null,
                purchase_date: null,
                user_id: ingredient.user_id,
            };

            if (type === "stockout") {
                // stockout → price & supplier_id can be null
                payload.price = null;
                payload.supplier_id = null;
            } else {
                // stockin → price & supplier_id required
                // Use 'cost' field instead of 'price' based on your data structure
                payload.price = ingredient.cost || ingredient.price || 1;
                payload.supplier_id = ingredient.supplier_id || 1;
            }

            console.log("Sending payload:", payload);

            const response = await axios.post("/stock_entries", payload);
            console.log("Stock update successful for:", ingredient.product_name, response.data);
        }

        console.log("All stock updates completed successfully");

    } catch (err) {
        console.error("Stock update error:", err);
        console.error("Error response:", err.response?.data);
        alert(`${type} failed: ` + (err.response?.data?.message || err.message));
        throw err; // Re-throw to handle it in calling function if needed
    }
};

// Also update your incQty and decQty functions to handle errors properly
const incQty = async () => {
    console.log('incQty called, current modalQty:', modalQty.value);
    console.log('menuStockForSelected:', menuStockForSelected.value); // Add .value here

    if (modalQty.value < menuStockForSelected.value) { // Add .value here
        try {
            await updateStock(selectedItem.value, 1, "stockout");
            modalQty.value++; // Only increment after successful stock update
            console.log('Stock updated successfully, new modalQty:', modalQty.value);
        } catch (error) {
            console.error('Failed to update stock:', error);
            // Don't increment modalQty if stock update failed
        }
    } else {
        console.log('Cannot increment: reached maximum stock limit');
    }
};

const decQty = async () => {
    console.log('decQty called, current modalQty:', modalQty.value);

    if (modalQty.value > 1) {
        try {
            await updateStock(selectedItem.value, 1, "stockin");
            modalQty.value--; // Only decrement after successful stock update
            console.log('Stock updated successfully, new modalQty:', modalQty.value);
        } catch (error) {
            console.error('Failed to update stock:', error);
            // Don't decrement modalQty if stock update failed
        }
    } else {
        console.log('Cannot decrement: minimum quantity is 1');
    }
};


// ===============================================================
//                      Submit Order
// ===============================================================
const note = ref("");
const placeOrder = async () => {
    const payload = {
        customer_name: customer.value,
        sub_total: subTotal.value,
        total_amount: grandTotal.value,
        tax: 0,                  // temporarily 0
        service_charges: 0,      // temporarily 0
        delivery_charges: 0,     // temporarily 0
        note: note.value,
        order_date: new Date().toISOString().split('T')[0],
        order_time: new Date().toLocaleTimeString(),
        order_type: orderType.value === "dine_in" ? "Dine In"
                    : orderType.value === "delivery" ? "Delivery"
                    : orderType.value === "takeaway" ? "Takeaway"
                    : "Collection",
        table_number: selectedTable.value?.name || null,
        items: orderItems.value.map(it => ({
            product_id: it.id,
            title: it.title,
            quantity: it.qty,
            price: it.price,
            note: it.note || null,
        })),
    };

    console.log("sending payload...", payload);

    try {
        const res = await axios.post('/pos/order', payload);
        alert(res.data.message);
        // reset cart/form here if needed
    } catch (err) {
        console.error(err);
        alert('Failed to place order');
    }
};







onMounted(() => {
    // Bootstrap tooltips (if any) & Modal instance
    const tipEls = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    if (window.bootstrap)
        tipEls.forEach((el) => new window.bootstrap.Tooltip(el));

    const modalEl = document.getElementById("chooseItem");
    if (window.bootstrap && modalEl) {
        chooseItemModal = new window.bootstrap.Modal(modalEl, {
            backdrop: "static",
        });
    }
    fetchMenuCategories();
    fetchMenuItems();
    fetchProfileTables();
});
</script>

<template>

    <Head title="POS Order" />

    <Master>
        <div class="page-wrapper">
            <div class="container-fluid py-3">
                <div class="row">
                    <!-- LEFT: Categories + Products -->
                    <div class="col-lg-8 col-sm-12"> <!-- Search -->
                        <div class="search-wrap mb-3"> <i class="bi bi-search"></i> <input v-model="searchQuery"
                                type="text" class="form-control search-input" placeholder="Search" /> </div>
                        <!-- Category tabs with arrows -->
                        <div class="tabs-wrap"> <button v-if="showCatArrows" class="tab-arrow left" type="button"
                                @click="scrollTabs('left')" aria-label="Previous categories"> <i
                                    class="fa fa-chevron-left"></i> </button>
                            <ul class="tabs border-0" id="catTabs" ref="catScroller">
                                <li v-for="c in menuCategories" :key="c.id" :class="{ active: isCat(c.id) }"
                                    @click="setCat(c.id)" role="button" tabindex="0">
                                    <div class="product-details flex-column text-center">
                                        <!-- if backend gives emoji in icon field -->
                                        <div class="text-2xl">{{ c.icon }}</div>
                                        <h6 class="mt-2 mb-0">{{ c.name }}</h6>
                                    </div>
                                </li>
                            </ul> <button v-if="showCatArrows" class="tab-arrow right" type="button"
                                @click="scrollTabs('right')" aria-label="Next categories"> <i
                                    class="fa fa-chevron-right"></i> </button>
                        </div> <!-- Products Grid -->
                        <div class="row g-3">
                            <div class="col-lg-3 col-sm-6 d-flex" v-for="p in filteredProducts" :key="p.title">
                                <div class="productset flex-fill hoverable" @click="openItem(p)">
                                    <div class="productsetimg"> <img :src="p.img" alt="img" />
                                        <h6> Qty: {{ Number(p.qty).toFixed(2) }} </h6>

                                        <div class="check-product"> <i class="fa fa-plus"></i> </div>
                                    </div>
                                    <div class="productsetcontent">
                                        <h5 class="text-muted small"> {{ p.family }} </h5>
                                        <h4 class="mb-1">{{ p.title }}</h4>
                                        <h6>{{ money(p.price) }}</h6>

                                    </div>
                                </div>
                            </div>
                            <div v-if="filteredProducts.length === 0" class="col-12">
                                <div class="alert alert-light border text-center"> No items found. </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: Order panel -->
                    <div class="col-lg-4 col-sm-12">
                        <div class="card card-order shadow-sm">
                            <div class="card-body pb-2">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="btn-group">
                                        <button v-for="(type, index) in orderTypes" :key="index" class="btn btn-sm"
                                            :class="orderType === type ? 'btn-success' : 'btn-light'"
                                            @click="orderType = type">
                                            {{type.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())}}
                                        </button>

                                    </div>
                                    <span class="badge bg-secondary">Order</span>
                                </div>

                                <!-- Type-specific inputs -->
                                <div v-if="orderType === 'dine_in'" class="mb-3">
                                    <label class="form-label small mb-1">Table No:</label>
                                    <select v-model="selectedTable" class="form-control">
                                        <option v-for="(table, index) in profileTables.table_details" :key="index"
                                            :value="table">
                                            {{ table.name }}
                                        </option>
                                    </select>

                                    <label class="form-label small mt-3 mb-1">Customer</label>
                                    <input v-model="customer" type="text" class="form-control"
                                        placeholder="Customer Name" />

                                </div>

                                <div v-else class="mb-3">
                                    <label class="form-label small mb-1">Customer</label>
                                    <input v-model="customer" type="text" class="form-control"
                                        placeholder="Customer Name" />

                                    <!-- <div
                                        class="d-flex align-items-center gap-2 mt-3"
                                    >
                                        <label class="form-label mb-0 small"
                                            >Delivery Charges (%)</label
                                        >
                                        <input
                                            type="number"
                                            min="0"
                                            v-model.number="deliveryPercent"
                                            class="form-control form-control-sm"
                                            style="width: 100px"
                                        />
                                    </div> -->
                                </div>

                                <hr />

                                <!-- Order items -->
                                <div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <strong>Items</strong>
                                        <small class="text-muted">Qty</small>
                                        <small class="text-muted">Price</small>
                                    </div>

                                    <div v-if="orderItems.length === 0" class="text-muted text-center py-3">
                                        No items added
                                    </div>

                                    <div v-for="(it, i) in orderItems" :key="it.title"
                                        class="d-flex align-items-center justify-content-between py-2 border-bottom">
                                        <div class="d-flex align-items-center gap-2">
                                            <img :src="it.img" alt="" class="rounded" style="
                                                    width: 36px;
                                                    height: 36px;
                                                    object-fit: cover;
                                                " />

                                            <div>

                                                <div class="fw-semibold">
                                                    {{ it.title }}
                                                </div>
                                                <small class="text-muted" v-if="it.note">{{ it.note }}</small>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center gap-2">
                                            <button class="btn btn-sm btn-primary" @click="decCart(i)">
                                                −
                                            </button>

                                            <div class="px-2 bg-danger">{{ it.qty }}</div>
                                            <button class="btn btn-sm"
                                                :class="it.qty >= (it.stock ?? 0) ? 'btn-secondary' : 'btn-primary'"
                                                @click="incCart(i)" :disabled="it.qty >= (it.stock ?? 0)">
                                                +
                                            </button>

                                        </div>

                                        <div class="d-flex align-items-center gap-3">
                                            <div class="text-nowrap">
                                                {{ money(it.price) }}
                                            </div>
                                            <button class="btn btn-sm btn-outline-danger" @click="removeCart(i)">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <hr />

                                <!-- Totals -->
                                <div class="d-flex flex-column gap-1">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Sub Total</span>
                                        <strong>{{ money(subTotal) }}</strong>
                                    </div>
                                    <div class="d-flex justify-content-between" v-if="orderType === 'delivery'">
                                        <span class="text-muted">Delivery Charges</span>
                                        <strong>{{ deliveryPercent }}%</strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Total</span>
                                        <strong>{{ money(grandTotal) }}</strong>
                                    </div>
                                </div>

                                <hr />

                                <textarea class="form-control" v-model="note" rows="4"
                                    placeholder="Add Note"></textarea>

                                <div class="d-flex gap-3 mt-3">
                                    <button class="btn btn-success flex-fill" @click="placeOrder()">
                                        Place Order
                                    </button>
                                    <button class="btn btn-danger flex-fill">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /row -->
            </div>
        </div>

        <!-- Choose Item Modal -->
        <div class="modal fade" id="chooseItem" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0"> <!-- Show Item Name -->
                        <h5 class="modal-title fw-bold">{{ selectedItem?.title || "Choose Item" }}</h5> <button
                            type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pb-0">
                        <div class="row align-items-start">
                            <div class="col-lg-5 mb-3"> <img
                                    :src="selectedItem?.image_url || selectedItem?.img || '/assets/img/product/product29.jpg'"
                                    class="img-fluid rounded shadow-sm w-100" alt="item" /> </div>
                            <div class="col-lg-7">
                                <h3 class="mb-1 text-primary-dark">{{ selectedItem?.title }}</h3> <!-- PRICE -->
                                <div class="h5 mb-3"> {{ money(selectedItem?.price || 0) }} </div>
                                <!-- INGREDIENTS (top) -->
                                <div class="mb-2"> <strong>Ingredients:</strong>
                                    <div v-if="!(selectedItem?.ingredients?.length)"> <em class="text-muted">No
                                            ingredients listed</em> </div>
                                    <div v-else class="mt-2"> <!-- inline chips/list --> <span
                                            v-for="ing in selectedItem.ingredients"
                                            :key="'ing-' + (ing.id ?? ing.inventory_item_id ?? JSON.stringify(ing))"
                                            class="chip" style="margin-right:6px;"> {{ ing.product_name || ing.name ||
                                                'Item' }} <span class="text-muted" v-if="ing.quantity"> ({{
                                                Number(ing.quantity).toFixed(2) }})</span> </span> </div>
                                </div> <!-- NUTRITION / ALLERGIES / TAGS -->
                                <div class="chips mb-3"> <!-- NUTRITION -->
                                    <div class="mb-2"> <strong>Nutrition:</strong>
                                        <div class="mt-1"> <span v-if="selectedItem?.nutrition?.calories"
                                                class="chip chip-orange mx-1"> Calories: {{
                                                    selectedItem.nutrition.calories }} </span> <span
                                                v-if="selectedItem?.nutrition?.carbs" class="chip chip-green mx-1">
                                                Carbs: {{ selectedItem.nutrition.carbs }} </span> <span
                                                v-if="selectedItem?.nutrition?.fat" class="chip chip-purple mx-1"> Fats:
                                                {{ selectedItem.nutrition.fat }} </span> <span
                                                v-if="selectedItem?.nutrition?.protein" class="chip chip-blue mx-1">
                                                Protein: {{ selectedItem.nutrition.protein }} </span> </div>
                                    </div> <!-- ALLERGIES -->
                                    <div class="mb-2"> <strong>Allergies:</strong>
                                        <div class="mt-1"> <span v-for="(a, i) in selectedItem?.allergies || []"
                                                :key="'allergy-' + (a.id ?? i)" class="chip chip-red mx-1"> {{ a.name }}
                                            </span> </div>
                                    </div> <!-- TAGS -->
                                    <div> <strong>Tags:</strong>
                                        <div class="mt-1"> <span v-for="(t, i) in selectedItem?.tags || []"
                                                :key="'tag-' + (t.id ?? i)" class="chip chip-teal mx-1"> {{ t.name }}
                                            </span> </div>
                                    </div>
                                </div> <!-- Qty control -->
                                <div class="qty-group d-inline-flex align-items-center mb-3"> <button class="qty-btn"
                                        @click="decQty">−</button>
                                    <div class="qty-box">{{ modalQty }}</div>
                                    <button class="qty-btn" @click="incQty" :disabled="modalQty >= menuStockForSelected"
                                        :class="modalQty >= menuStockForSelected ? 'bg-secondary text-white' : 'btn-primary text-white'">
                                        +
                                    </button>

                                </div>
                                <div class="mb-3"> <input v-model="modalNote" class="form-control"
                                        placeholder="Add note (optional)" /> </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0"> <button class="btn btn-outline-secondary rounded-pill"
                            data-bs-dismiss="modal">Cancel</button> <button class="btn btn-primary rounded-pill"
                            @click="confirmAdd">Add to Order</button> </div>
                </div>
            </div>
        </div>
    </Master>
</template>

<style scoped>
/* Search pill */
.search-wrap {
    position: relative;
    /* width: clamp(220px, 28vw, 360px); */
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

/* Tabs container + arrows */
.tabs-wrap {
    position: relative;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
}

.tab-arrow {
    border: 0;
    background: #fff;
    box-shadow: 0 2px 10px rgba(17, 23, 31, 0.08);
    width: 36px;
    height: 36px;
    border-radius: 50%;
}

.tab-arrow.left {
    order: -1;
}

/* Category list — single row, scrollable */
.tabs {
    display: flex;
    align-items: center;
    gap: 14px;
    overflow-x: auto;
    padding: 5px;
    margin: 0;
    list-style: none;
    scrollbar-width: none;
}

.tabs::-webkit-scrollbar {
    display: none;
}

.tabs>li {
    flex: 0 0 auto;
    width: 100px;
    height: 100px;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 2px 10px rgba(17, 23, 31, 0.06);
    cursor: pointer;
    transition: 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.tabs>li:hover {
    transform: translateY(-1px);
}

.tabs>li.active {
    outline: 2px solid #6f61ff;
    background: #f1eeff;
}

.tabs img {
    width: 32px;
    height: 32px;
    object-fit: contain;
}

/* Product cards */
.productset.hoverable {
    transition: 0.15s;
    cursor: pointer;
}

.productset.hoverable:hover {
    transform: translateY(-2px);
}

.productsetimg {
    position: relative;
    overflow: hidden;
    border-radius: 10px;
}

.productsetimg img {
    width: 100%;
    display: block;
}

.productsetimg h6 {
    position: absolute;
    left: 8px;
    bottom: 8px;
    margin: 0;
    background: rgba(0, 0, 0, 0.6);
    color: #fff;
    padding: 2px 6px;
    border-radius: 6px;
    font-size: 12px;
}

.check-product {
    position: absolute;
    right: 8px;
    bottom: 8px;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #6f61ff;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
}

.productsetcontent h4 {
    font-size: 16px;
}

/* Chips (modal) */
.chips {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.chip {
    padding: 6px 10px;
    border-radius: 20px;
    background: #f5f5f7;
    font-size: 12px;
    border: 1px solid #eee;
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

/* Qty control (modal) */
/* Wrap as a group */
.qty-group {
    border-radius: 10px;
    overflow: hidden;
    /* makes them look like one control */
    border: 1px solid #d0cfd7;
}

/* Btns still follow your style */
.qty-btn {
    width: 34px;
    height: 44px;
    border: 0;
    background: #1b1670;
    font-size: 20px;
    line-height: 1;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Middle box (value) */
.qty-box {
    height: 44px;
    min-width: 60px;
    text-align: center;
    padding: 8px 12px;
    background: #1b1670;
    color: white;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #d0cfd7;
}
</style>
