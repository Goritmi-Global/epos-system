<script setup>
import Master from "@/Layouts/Master.vue";
import { Head } from "@inertiajs/vue3";
import { ref, computed, onMounted } from "vue";

/* ----------------------------
   Categories (same keys/icons)
-----------------------------*/
const categories = [
    {
        key: "fruits",
        label: "Fruits",
        icon: "/assets/img/product/product62.png",
    },
    {
        key: "headphone",
        label: "Headphones",
        icon: "/assets/img/product/product63.png",
    },
    {
        key: "Accessories",
        label: "Accessories",
        icon: "/assets/img/product/product64.png",
    },
    { key: "Shoes", label: "Shoes", icon: "/assets/img/product/product65.png" },
    {
        key: "computer",
        label: "Computer",
        icon: "/assets/img/product/product66.png",
    },
    {
        key: "Snacks",
        label: "Snacks",
        icon: "/assets/img/product/product67.png",
    },
    {
        key: "watch",
        label: "Watches",
        icon: "/assets/img/product/product68.png",
    },
    {
        key: "cycle",
        label: "Cycles",
        icon: "/assets/img/product/product61.png",
    },
];

/* ----------------------------
   Demo Products by Category
-----------------------------*/
const productsByCat = {
    fruits: [
        {
            title: "Orange",
            img: "/assets/img/product/product29.jpg",
            qty: 5,
            price: 150.0,
            family: "Fruits",
        },
        {
            title: "Strawberry",
            img: "/assets/img/product/product31.jpg",
            qty: 1,
            price: 15.0,
            family: "Fruits",
        },
        {
            title: "Banana",
            img: "/assets/img/product/product35.jpg",
            qty: 5,
            price: 150.0,
            family: "Fruits",
        },
        {
            title: "Limon",
            img: "/assets/img/product/product37.jpg",
            qty: 5,
            price: 1500.0,
            family: "Fruits",
        },
        {
            title: "Apple",
            img: "/assets/img/product/product54.jpg",
            qty: 5,
            price: 1500.0,
            family: "Fruits",
        },
    ],
    headphone: [
        {
            title: "Earphones A",
            img: "/assets/img/product/product44.jpg",
            qty: 5,
            price: 150.0,
            family: "Headphones",
        },
        {
            title: "Earphones B",
            img: "/assets/img/product/product45.jpg",
            qty: 5,
            price: 150.0,
            family: "Headphones",
        },
        {
            title: "Earphones C",
            img: "/assets/img/product/product36.jpg",
            qty: 5,
            price: 150.0,
            family: "Headphones",
        },
    ],
    Accessories: [
        {
            title: "Sunglasses",
            img: "/assets/img/product/product32.jpg",
            qty: 1,
            price: 15.0,
            family: "Accessories",
        },
        {
            title: "Pendrive",
            img: "/assets/img/product/product46.jpg",
            qty: 1,
            price: 150.0,
            family: "Accessories",
        },
        {
            title: "Mouse",
            img: "/assets/img/product/product55.jpg",
            qty: 1,
            price: 150.0,
            family: "Accessories",
        },
    ],
    Shoes: [
        {
            title: "Red Nike",
            img: "/assets/img/product/product60.jpg",
            qty: 1,
            price: 1500.0,
            family: "Shoes",
        },
    ],
    computer: [
        {
            title: "Desktop",
            img: "/assets/img/product/product56.jpg",
            qty: 1,
            price: 1500.0,
            family: "Computers",
        },
    ],
    Snacks: [
        {
            title: "Duck Salad",
            img: "/assets/img/product/product47.jpg",
            qty: 1,
            price: 1500.0,
            family: "Snacks",
        },
        {
            title: "Breakfast Board",
            img: "/assets/img/product/product48.png",
            qty: 1,
            price: 1500.0,
            family: "Snacks",
        },
        {
            title: "California roll",
            img: "/assets/img/product/product57.jpg",
            qty: 1,
            price: 1500.0,
            family: "Snacks",
        },
        {
            title: "Sashimi",
            img: "/assets/img/product/product58.jpg",
            qty: 1,
            price: 1500.0,
            family: "Snacks",
        },
    ],
    watch: [
        {
            title: "Watch A",
            img: "/assets/img/product/product49.jpg",
            qty: 1,
            price: 1500.0,
            family: "Watch",
        },
        {
            title: "Watch B",
            img: "/assets/img/product/product51.jpg",
            qty: 1,
            price: 1500.0,
            family: "Watch",
        },
    ],
    cycle: [
        {
            title: "Cycle A",
            img: "/assets/img/product/product52.jpg",
            qty: 1,
            price: 1500.0,
            family: "Cycle",
        },
        {
            title: "Cycle B",
            img: "/assets/img/product/product53.jpg",
            qty: 1,
            price: 1500.0,
            family: "Cycle",
        },
    ],
};

/* ----------------------------
   UI State
-----------------------------*/
const activeCat = ref("fruits");
const searchQuery = ref("");
const orderType = ref("dine"); // 'dine' | 'delivery'

const tableNo = ref(""); // dine-in
const customer = ref("Walk In"); // delivery/customer

const deliveryPercent = ref(10); // demo: 10% delivery charges

const setCat = (k) => (activeCat.value = k);
const isCat = (k) => activeCat.value === k;

/* Search + category combined */
const visibleProducts = computed(() => productsByCat[activeCat.value] ?? []);
const filteredProducts = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return visibleProducts.value;
    return visibleProducts.value.filter(
        (p) =>
            p.title.toLowerCase().includes(q) ||
            (p.family || "").toLowerCase().includes(q)
    );
});

/* Horizontal scroll arrows for category tabs */
const catScroller = ref(null);
const showCatArrows = computed(() => categories.length > 5);
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
});
</script>

<template>
    <Head title="POS Order" />

    <Master>
        <div class="page-wrapper">
            <div class="container-fluid py-3">
                <div class="row">
                    <!-- LEFT: Categories + Products -->
                    <div class="col-lg-8 col-sm-12">
                        <!-- Search -->

                        <div class="search-wrap mb-3">
                            <i class="bi bi-search"></i>
                            <input
                                v-model="searchQuery"
                                type="text"
                                class="form-control search-input"
                                placeholder="Search"
                            />
                        </div>

                        <!-- Category tabs with arrows -->
                        <div class="tabs-wrap">
                            <button
                                v-if="showCatArrows"
                                class="tab-arrow left"
                                type="button"
                                @click="scrollTabs('left')"
                                aria-label="Previous categories"
                            >
                                <i class="fa fa-chevron-left"></i>
                            </button>

                            <ul
                                class="tabs border-0"
                                id="catTabs"
                                ref="catScroller"
                            >
                                <li
                                    v-for="c in categories"
                                    :key="c.key"
                                    :class="{ active: isCat(c.key) }"
                                    @click="setCat(c.key)"
                                    role="button"
                                    tabindex="0"
                                >
                                    <div
                                        class="product-details flex-column text-center"
                                    >
                                        <img :src="c.icon" alt="" />
                                        <h6 class="mt-2 mb-0">{{ c.label }}</h6>
                                    </div>
                                </li>
                            </ul>

                            <button
                                v-if="showCatArrows"
                                class="tab-arrow right"
                                type="button"
                                @click="scrollTabs('right')"
                                aria-label="Next categories"
                            >
                                <i class="fa fa-chevron-right"></i>
                            </button>
                        </div>

                        <!-- Products Grid -->
                        <div class="row g-3">
                            <div
                                class="col-lg-3 col-sm-6 d-flex"
                                v-for="p in filteredProducts"
                                :key="p.title"
                            >
                                <div
                                    class="productset flex-fill hoverable"
                                    @click="openItem(p)"
                                >
                                    <div class="productsetimg">
                                        <img :src="p.img" alt="img" />
                                        <h6>
                                            Qty: {{ Number(p.qty).toFixed(2) }}
                                        </h6>
                                        <div class="check-product">
                                            <i class="fa fa-plus"></i>
                                        </div>
                                    </div>
                                    <div class="productsetcontent">
                                        <h5 class="text-muted small">
                                            {{ p.family }}
                                        </h5>
                                        <h4 class="mb-1">{{ p.title }}</h4>
                                        <h6>{{ money(p.price) }}</h6>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="filteredProducts.length === 0"
                                class="col-12"
                            >
                                <div
                                    class="alert alert-light border text-center"
                                >
                                    No items found.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: Order panel -->
                    <div class="col-lg-4 col-sm-12">
                        <div class="card card-order shadow-sm">
                            <div class="card-body pb-2">
                                <div
                                    class="d-flex align-items-center justify-content-between mb-3"
                                >
                                    <div class="btn-group">
                                        <button
                                            class="btn btn-sm"
                                            :class="
                                                orderType === 'dine'
                                                    ? 'btn-success'
                                                    : 'btn-light'
                                            "
                                            @click="orderType = 'dine'"
                                        >
                                            Dine In
                                        </button>
                                        <button
                                            class="btn btn-sm"
                                            :class="
                                                orderType === 'delivery'
                                                    ? 'btn-success'
                                                    : 'btn-light'
                                            "
                                            @click="orderType = 'delivery'"
                                        >
                                            Delivery
                                        </button>
                                    </div>

                                    <span class="badge bg-secondary"
                                        >Order</span
                                    >
                                </div>

                                <!-- Type-specific inputs -->
                                <div v-if="orderType === 'dine'" class="mb-3">
                                    <label class="form-label small mb-1"
                                        >Table No:</label
                                    >
                                    <input
                                     v-model="tableNo"
                                            type="text"
                                            class="form-control"
                                             
                                        placeholder="Table No:"
                                    />
                                    <label class="form-label small mt-3 mb-1"
                                        >Customer</label
                                    >
                                    <input
                                     v-model="customer"
                                            type="text"
                                            class="form-control"
                                             
                                        placeholder="Customer Name"
                                    />
                                     
                                </div>

                                <div v-else class="mb-3">
                                    <label class="form-label small mb-1"
                                        >Customer</label
                                    >
                                    <input
                                     v-model="customer"
                                            type="text"
                                            class="form-control"
                                             
                                        placeholder="Customer Name"
                                    />
                                     
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
                                    <div
                                        class="d-flex justify-content-between mb-2"
                                    >
                                        <strong>Items</strong>
                                        <small class="text-muted">Qty</small>
                                        <small class="text-muted">Price</small>
                                    </div>

                                    <div
                                        v-if="orderItems.length === 0"
                                        class="text-muted text-center py-3"
                                    >
                                        No items added
                                    </div>

                                    <div
                                        v-for="(it, i) in orderItems"
                                        :key="it.title"
                                        class="d-flex align-items-center justify-content-between py-2 border-bottom"
                                    >
                                        <div
                                            class="d-flex align-items-center gap-2"
                                        >
                                            <img
                                                :src="it.img"
                                                alt=""
                                                class="rounded"
                                                style="
                                                    width: 36px;
                                                    height: 36px;
                                                    object-fit: cover;
                                                "
                                            />
                                            
                                            <div>
                                              
                                                <div class="fw-semibold">
                                                    {{ it.title }}
                                                </div>
                                                <small
                                                    class="text-muted"
                                                    v-if="it.note"
                                                    >{{ it.note }}</small
                                                >
                                            </div>
                                        </div>

                                        <div
                                            class="d-flex align-items-center gap-2"
                                        >
                                            <button
                                                class="btn btn-sm btn-primary"
                                                @click="decCart(i)"
                                            >
                                                −
                                            </button>
                                            <div class="px-2 bg-danger">{{ it.qty }}</div>
                                            <button
                                                class="btn btn-sm btn-primary"
                                                @click="incCart(i)"
                                            >
                                                +
                                            </button>
                                        </div>

                                        <div
                                            class="d-flex align-items-center gap-3"
                                        >
                                            <div class="text-nowrap">
                                                {{ money(it.price) }}
                                            </div>
                                            <button
                                                class="btn btn-sm btn-outline-danger"
                                                @click="removeCart(i)"
                                            >
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <hr />

                                <!-- Totals -->
                                <div class="d-flex flex-column gap-1">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted"
                                            >Sub Total</span
                                        >
                                        <strong>{{ money(subTotal) }}</strong>
                                    </div>
                                    <div
                                        class="d-flex justify-content-between"
                                        v-if="orderType === 'delivery'"
                                    >
                                        <span class="text-muted"
                                            >Delivery Charges</span
                                        >
                                        <strong>{{ deliveryPercent }}%</strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Total</span>
                                        <strong>{{ money(grandTotal) }}</strong>
                                    </div>
                                </div>

                                <hr />

                                <textarea
                                    class="form-control"
                                    rows="4"
                                    placeholder="Add Note"
                                ></textarea>

                                <div class="d-flex gap-3 mt-3">
                                    <button class="btn btn-success flex-fill">
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
        <div
            class="modal fade"
            id="chooseItem"
            tabindex="-1"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title fw-bold">Choose Item</h5>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                        ></button>
                    </div>

                    <div class="modal-body pb-0">
                        <div class="row align-items-start">
                            <div class="col-lg-5 mb-3">
                                <img
                                    :src="
                                        selectedItem?.img ||
                                        '/assets/img/product/product29.jpg'
                                    "
                                    class="img-fluid rounded shadow-sm w-100"
                                    alt="item"
                                />
                            </div>

                            <div class="col-lg-7">
                                <h3 class="mb-2 text-primary-dark">
                                    {{ selectedItem?.title }}
                                </h3>
                                <div class="h5 mb-3">
                                    {{ money(selectedItem?.price || 0) }}
                                </div>

                                <!-- Example chips (demo only) -->
                                <div class="chips mb-3">
                                    <span class="chip"
                                        >Basmati rice (20 gram (g))</span
                                    >
                                    <span class="chip chip-orange"
                                        >Calories: 120.0</span
                                    >
                                    <span class="chip chip-green"
                                        >Carbs: 4.0</span
                                    >
                                    <span class="chip chip-purple"
                                        >Fats: 3.0</span
                                    >
                                    <span class="chip chip-blue"
                                        >Protein: 18.0</span
                                    >
                                    <span class="chip chip-red">Celery</span>
                                    <span class="chip">Gluten-Free</span>
                                </div>

                                <!-- Qty control -->
                               <div class="qty-group d-inline-flex align-items-center mb-3">
  <button class="qty-btn" @click="decQty">−</button>
  <div class="qty-box">{{ modalQty }}</div>
  <button class="qty-btn" @click="incQty">+</button>
</div>

                                <div class="mb-3">
                                    <input
                                        v-model="modalNote"
                                        class="form-control "
                                        placeholder="Add note (optional)"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 pt-0">
                        <button
                        class="btn btn-outline-secondary rounded-pill"
                            
                            data-bs-dismiss="modal"
                        >
                            Cancel
                        </button>
                        <button class="btn btn-primary rounded-pill" @click="confirmAdd">
                            Add to Order
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

.tabs > li {
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
.tabs > li:hover {
    transform: translateY(-1px);
}
.tabs > li.active {
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
  overflow: hidden; /* makes them look like one control */
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
