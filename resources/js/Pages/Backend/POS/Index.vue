<script setup>
import Master from "@/Layouts/Master.vue";
import { Head } from "@inertiajs/vue3";
import { ref, computed, onMounted } from "vue";

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
        console.log(menuCategories.value);
    } catch (error) {
        console.error("Error fetching inventory:", error);
    }
};

const menuItems = ref([]);
const fetchMenuItems = async () => {
    try {
        const response = await axios.get("/pos/fetch-menu-items");
        menuItems.value = response.data;
        console.log(menuItems.value);
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
            qty: item.ingredients?.length ?? 0,
            price: Number(item.price),
            family: catName, // still displayable name
            description: item.description,
            nutrition: item.nutrition,
            tags: item.tags,
            allergies: item.allergies,
            ingredients: item.ingredients ?? [],
        });
    });

    return grouped;
});



/* ----------------------------
   UI State
-----------------------------*/
// const activeCat = ref("fruits");
const searchQuery = ref("");
const orderType = ref("dine"); // 'dine' | 'delivery'

const tableNo = ref(""); // dine-in
const customer = ref("Walk In"); // delivery/customer

const deliveryPercent = ref(10); // demo: 10% delivery charges

// const setCat = (k) => (activeCat.value = k);
// const isCat = (k) => activeCat.value === k;

const activeCat = ref(null); // store ID
const setCat = (id) => {
    activeCat.value = id;
};
const isCat = (id) => activeCat.value === id;


const selectedCategory = ref(null);
function openCategory(id) {
    selectedCategory.value = id
}

function goBack() {
    selectedCategory.value = null
}



const profileTables = ref({});
const orderTypes = ref([]);
const selectedTable = ref(null);

const fetchProfileTables = async () => {
    try {
        const response = await axios.get("/pos/fetch-profile-tables");
        profileTables.value = response.data;
        console.log("profileTables", profileTables.value);

        if (profileTables.value.order_types) {
            orderTypes.value = profileTables.value.order_types;
            // pick the first as default
            orderType.value = orderTypes.value[0];
        }

        console.log("Order Types:", orderTypes.value);
    } catch (error) {
        console.error("Error fetching profile tables:", error);
    }
};

/* Search + category combined */
// const visibleProducts = computed(() => productsByCat[activeCat.value] ?? []);
// const filteredProducts = computed(() => {
//     const q = searchQuery.value.trim().toLowerCase();
//     if (!q) return visibleProducts.value;
//     return visibleProducts.value.filter(
//         (p) =>
//             p.title.toLowerCase().includes(q) ||
//             (p.family || "").toLowerCase().includes(q)
//     );
// });

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
    const idx = orderItems.value.findIndex((i) => i.title === baseItem.title);
    if (idx >= 0) {
        orderItems.value[idx].qty += qty;
        if (note) orderItems.value[idx].note = note;
    } else {
        orderItems.value.push({
            title: baseItem.title,
            img: baseItem.img,
            price: Number(baseItem.price) || 0,
            qty: qty,
            note: note || "",
        });
    }
};
const incCart = (i) => orderItems.value[i].qty++;
const decCart = (i) => {
    const it = orderItems.value[i];
    it.qty = Math.max(1, it.qty - 1);
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
const incQty = () => modalQty.value++;
const decQty = () => (modalQty.value = Math.max(1, modalQty.value - 1));
const confirmAdd = () => {
    if (selectedItem.value) {
        addToOrder(selectedItem.value, modalQty.value, modalNote.value);
    }
    if (chooseItemModal) chooseItemModal.hide();
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
                                    @click="setCat(c.id)" role="button" tabindex="0" class="product-details flex-column text-center">
                                        <!-- if backend gives emoji in icon field -->
                                        <div class="text-2xl">{{ c.icon }}</div>
                                        <h6 class="mt-2 mb-0">{{ c.name }}</h6>
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
                                        <button v-for="(type, index) in orderTypes" :key="index" class="btn btn-sm px-3 rounded-pill"
                                            :class="orderType === type ? 'btn-primary ' : 'btn-light'"
                                            @click="orderType = type">
                                            {{type.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())}}
                                        </button>

                                    </div>
                                    <span class="badge btn btn-primary rounded-pill px-3 py-2">Order</span>
                                </div>

                                <!-- Type-specific inputs -->
                                <div v-if="orderType === 'dine_in'" class="mb-3">
                                    <label class="form-label small mb-1">Table No:</label>
                                    <select :P v-model="selectedTable" class="form-control">
                                        <option v-for="(table, index) in profileTables.table_details" :key="index"
                                            :value="table">
                                            {{ table.name }} ({{ table.chairs }} chairs)
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
                                        <div class="d-flex align-items-center gap-3">
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

                                        <div class="d-flex align-items-center gap-3">
                                            <button class="px-3 py-1 rounded-pill btn btn-primary text-white text-center" @click="decCart(i)">
                                                −
                                            </button>
                                            <div class="px-3 py-1 rounded-pill btn btn-danger text-white">{{ it.qty }}</div>
                                            <button class="px-3 py-1 rounded-pill btn btn-primary text-white text-center" @click="incCart(i)">
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

                                <textarea class="form-control" rows="4" placeholder="Add Note"></textarea>

                                <div class="d-flex gap-3 mt-3">
                                    <button class="px-4 py-2 rounded-pill btn btn-primary text-white text-center">
                                        Place Order
                                    </button>
                                    <button class="btn btn-secondary rounded-pill px-4 ms-2">
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
                        <h5 class="modal-title fw-bold">{{ selectedItem?.title || "Choose Item" }}</h5> 
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
                                    <div class="qty-box">{{ modalQty }}</div> <button class="qty-btn"
                                        @click="incQty">+</button>
                                </div>
                                <div class="mb-3">  </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0"> <button class="btn btn-secondary rounded-pill px-4 ms-2"
                            data-bs-dismiss="modal">Cancel</button> 
                            <button class="btn btn-primary rounded-pill"
                            @click="confirmAdd">Add to Order
                        </button>
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
    padding: 8px;
}

.tabs>li:hover {
    transform: translateY(-1px);
}

.tabs>li.active {
    outline: 2px solid #6f61ff;
    color: white;

}

/* Product details container - this was missing! */
.product-details {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 4px;
}

.tabs img {
    width: 32px;
    height: 32px;
    object-fit: contain;
    flex-shrink: 0;
}

.product-details h6 {
    font-size: 11px;
    line-height: 1.2;
    margin: 0;
    font-weight: 500;
    color: #333;
    word-wrap: break-word;
    overflow: hidden;
    text-overflow: ellipsis;
}


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

/* .productsetimg h6 {
    position: absolute;
    left: 8px;
    bottom: 8px;
    margin: 0;
    background: rgba(0, 0, 0, 0.6);
    color: #fff;
    padding: 2px 6px;
    border-radius: 6px;
    font-size: 12px;
} */

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
.btn-group {
  gap: 8px; 
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


