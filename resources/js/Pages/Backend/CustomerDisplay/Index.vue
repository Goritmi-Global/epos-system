<!-- resources/js/Pages/Backend/CustomerDisplay/Index.vue -->
<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    terminalId: {
        type: String,
        required: true
    }
});

// Cart data from POS
const cartData = ref({
    items: [],
    customer: 'Walk In',
    orderType: 'Dine In',
    table: null,
    subtotal: 0,
    tax: 0,
    serviceCharges: 0,
    deliveryCharges: 0,
    promoDiscount: 0,
    total: 0,
    note: '',
    appliedPromos: []
});

// UI State (mirrors POS)
const showCategories = ref(true);
const activeCat = ref(null);
const menuCategories = ref([]);
const menuItems = ref([]);
const searchQuery = ref("");

const currentTime = ref(new Date().toLocaleTimeString());
const connectionStatus = ref('Connecting...');
let echoChannel = null;

const formatCurrencySymbol = (amount) => `£${(amount || 0).toFixed(2)}`;

// Products by Category (same as POS)
const productsByCat = computed(() => {
    const grouped = {};
    menuItems.value.forEach((item) => {
        const catId = item.category?.id || "uncategorized";
        const catName = item.category?.name || "Uncategorized";
        if (!grouped[catId]) grouped[catId] = [];

        const product = {
            id: item.id,
            title: item.name,
            img: item.image_url || "/assets/img/default.png",
            price: Number(item.price),
            label_color: item.label_color || "#1B1670",
            family: catName,
            description: item.description,
            variants: item.variants ?? [],
            addon_groups: item.addon_groups ?? [],
        };

        grouped[catId].push(product);
    });
    return grouped;
});

const visibleProducts = computed(
    () => productsByCat.value[activeCat.value] ?? []
);

const filteredProducts = computed(() => {
   const q = (searchQuery.value || '').trim().toLowerCase();
    if (!q) return visibleProducts.value;
    return visibleProducts.value.filter(
        (p) =>
            p.title.toLowerCase().includes(q) ||
            (p.family || "").toLowerCase().includes(q) ||
            (p.description || "").toLowerCase().includes(q)
    );
});

// Get selected category name
const selectedCategoryName = computed(() => {
    return menuCategories.value.find((c) => c.id === activeCat.value)?.name || "Items";
});

// Check if item is eligible for promo
const isItemEligibleForPromo = (cartItem) => {
    if (!cartData.value.appliedPromos || cartData.value.appliedPromos.length === 0) return false;

    return cartData.value.appliedPromos.some(promo => {
        if (!promo.applied_to_items || promo.applied_to_items.length === 0) {
            return true;
        }
        return promo.applied_to_items.includes(cartItem.id);
    });
};

onMounted(() => {
    // Clock update
    setInterval(() => {
        currentTime.value = new Date().toLocaleTimeString();
    }, 1000);

    if (!window.Echo) {
        console.error('Echo not found');
        connectionStatus.value = 'Error: Echo not found';
        return;
    }

    try {
        echoChannel = window.Echo.channel(`pos-terminal.${props.terminalId}`);

        // Listen for cart updates
        echoChannel.listen('.cart.updated', (e) => {
            console.log('📦 Cart update received:', e);
            cartData.value = e.cart;
            connectionStatus.value = 'Connected';
        });

        // Listen for UI state updates (categories, menu items, active category, etc.)
        echoChannel.listen('.ui.updated', (e) => {
            console.log('🖥️ UI update received:', e);

            const ui = e.ui || e; // support both formats

            if (ui.showCategories !== undefined) showCategories.value = ui.showCategories;
            if (ui.activeCat !== undefined) activeCat.value = ui.activeCat;
            if (ui.menuCategories) menuCategories.value = ui.menuCategories;
            if (ui.menuItems) menuItems.value = ui.menuItems;
            if (ui.searchQuery !== undefined) searchQuery.value = ui.searchQuery;

            console.log("✅ menuCategories updated:", menuCategories.value.length);
        });


        if (window.Echo.connector && window.Echo.connector.pusher) {
            const pusher = window.Echo.connector.pusher;

            pusher.connection.bind('connected', () => {
                connectionStatus.value = 'Connected';
            });

            pusher.connection.bind('disconnected', () => {
                connectionStatus.value = 'Disconnected';
            });

            pusher.connection.bind('failed', () => {
                connectionStatus.value = 'Connection Failed';
            });
        }

    } catch (error) {
        console.error('Echo setup error:', error);
        connectionStatus.value = 'Setup Error';
    }
});

onUnmounted(() => {
    if (echoChannel) {
        window.Echo.leaveChannel(`pos-terminal.${props.terminalId}`);
    }
});
</script>

<template>

    <Head title="Customer Display" />

    <div class="page-wrapper">
        <!-- Connection Status Bar -->
        <div class="connection-bar" :class="connectionStatus === 'Connected' ? 'connected' : 'disconnected'">
            <div class="container-fluid px-4">
                <div class="d-flex justify-content-between align-items-center py-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="status-dot" :class="connectionStatus === 'Connected' ? 'active' : ''"></span>
                        <span class="small fw-semibold">{{ connectionStatus }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="small fw-bold">{{ currentTime }}</span>
                        <span class="small">Terminal: {{ terminalId }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid px-3 py-3">
            <div class="row gx-3 gy-3">
                <!-- LEFT: Menu (Categories or Products) -->
                <div :class="showCategories ? 'col-md-12' : 'col-lg-8'">
                    <!-- Categories Grid (Same as POS) -->
                    <div v-if="showCategories" class="row g-3">
                        <div class="col-12 mb-3">
                            <h4 class="mb-3">Menu Categories</h4>
                            <hr class="mt-2 mb-3">
                        </div>

                        <!-- Categories List -->
                        <div v-for="c in menuCategories" :key="c.id" class="col-6 col-md-4 col-lg-3">
                            <div class="cat-card">
                                <div class="cat-icon-wrap">
                                    <span class="cat-icon">
                                        <img v-if="c.image_url" :src="c.image_url" alt="Category Image"
                                            class="cat-image" />
                                        <span v-else>🍵</span>
                                    </span>
                                </div>
                                <div class="cat-name">{{ c.name }}</div>
                                <div class="cat-pill">
                                    {{ c.menu_items_count }} Menu
                                </div>
                            </div>
                        </div>

                        <!-- No Categories Found -->
                        <div v-if="menuCategories.length === 0" class="col-12">
                            <div class="alert alert-light border text-center rounded-4">
                                No categories found
                            </div>
                        </div>
                    </div>

                    <!-- Items in selected category (Same as POS) -->
                    <div v-else>
                        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-arrow-left text-primary fs-5"></i>
                                <h5 class="fw-bold mb-0">{{ selectedCategoryName }}</h5>
                            </div>

                            <!-- Search Display -->
                            <div v-if="searchQuery" class="search-display">
                                <i class="bi bi-search me-2"></i>
                                <span>{{ searchQuery }}</span>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6 col-xl-6 d-flex" v-for="p in filteredProducts" :key="p.id">
                                <div class="card rounded-4 shadow-sm overflow-hidden border-3 w-100 d-flex flex-row align-items-stretch"
                                    :style="{ borderColor: p.label_color || '#1B1670' }">

                                    <!-- Left Side (Image + Price Badge) - 40% -->
                                    <div class="position-relative" style="flex: 0 0 40%; max-width: 40%;">
                                        <img :src="p.img" alt="" class="w-100 h-100" style="object-fit: cover;" />

                                        <!-- Price Badge -->
                                        <span
                                            class="position-absolute top-0 start-0 m-2 px-3 py-1 rounded-pill text-white small fw-semibold"
                                            :style="{ background: p.label_color || '#1B1670' }">
                                            {{ formatCurrencySymbol(p.price) }}
                                        </span>
                                    </div>

                                    <!-- Right Side (Details) -->
                                    <div class="p-3 d-flex flex-column justify-content-between"
                                        style="flex: 1 1 60%; min-width: 0;">
                                        <div>
                                            <div class="h5 fw-bold mb-2" :style="{ color: p.label_color || '#1B1670' }">
                                                {{ p.title }}
                                            </div>

                                            <!-- Variants Display -->
                                            <div v-if="p.variants && p.variants.length > 0" class="mb-2">
                                                <small class="text-muted d-block mb-1 fw-semibold">Variants:</small>
                                                <div class="d-flex flex-wrap gap-1">
                                                    <span v-for="variant in p.variants" :key="variant.id"
                                                        class="badge bg-secondary-subtle text-secondary">
                                                        {{ variant.name }}
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Addon Groups Display -->
                                            <div v-if="p.addon_groups && p.addon_groups.length > 0" class="mb-2">
                                                <small class="text-muted d-block mb-1 fw-semibold">Available
                                                    Add-ons:</small>
                                                <div class="d-flex flex-wrap gap-1">
                                                    <span v-for="group in p.addon_groups" :key="group.group_id"
                                                        class="badge bg-primary-subtle text-primary">
                                                        {{ group.group_name }}
                                                    </span>
                                                </div>
                                            </div>

                                            <p v-if="p.description" class="text-muted small mb-0">
                                                {{ p.description }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Cart Summary (Only visible when viewing products) -->
                <div class="col-lg-4" v-if="!showCategories">
                    <div class="cart card border-0 shadow-lg rounded-4">
                        <div class="cart-header">
                            <div class="order-type">
                                <div class="ot-pill active">
                                    {{ cartData.orderType }}
                                </div>
                            </div>
                        </div>

                        <div class="cart-body">
                            <!-- Customer & Table Info -->
                            <div class="mb-3">
                                <div v-if="cartData.orderType === 'Dine In'" class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small">Table</label>
                                        <div class="form-control form-control-sm bg-light">
                                            {{ cartData.table?.name || 'Select Table' }}
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small">Customer</label>
                                        <div class="form-control form-control-sm bg-light">
                                            {{ cartData.customer }}
                                        </div>
                                    </div>
                                </div>
                                <div v-else>
                                    <label class="form-label small">Customer</label>
                                    <div class="form-control form-control-sm bg-light">
                                        {{ cartData.customer }}
                                    </div>
                                </div>
                            </div>

                            <!-- Line items -->
                            <div class="cart-lines">
                                <div v-if="cartData.items.length === 0" class="empty">
                                    Add items from the left
                                </div>

                                <div v-for="(item, i) in cartData.items" :key="i" class="line">
                                    <div class="line-left">
                                        <img :src="item.img || '/assets/img/default.png'" alt="" />
                                        <div class="meta">
                                            <div class="name" :title="item.title">
                                                {{ item.title }}
                                            </div>
                                            <div v-if="item.addons && item.addons.length > 0 || item.variant_name"
                                                class="addon-icons mt-1">
                                                <span v-if="item.addons && item.addons.length > 0"
                                                    class="text-muted small">
                                                    <i class="bi bi-plus-circle-fill text-primary me-1"
                                                        style="font-size: 0.9rem;"></i>
                                                    {{ item.addons.length }} addon(s)
                                                </span>
                                                <span v-if="item.variant_name" class="badge ms-1"
                                                    style="font-size: 0.65rem; background: #1B1670; color: white;">
                                                    {{ item.variant_name }}
                                                </span>
                                                <span v-if="isItemEligibleForPromo(item)" class="badge bg-success ms-1"
                                                    style="font-size: 0.65rem;">
                                                    <i class="bi bi-tag-fill"></i>
                                                </span>
                                            </div>
                                            <div class="note" v-if="item.note">
                                                {{ item.note }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="line-mid">
                                        <div class="qty">{{ item.qty }}</div>
                                    </div>

                                    <div class="line-right">
                                        <div class="price">
                                            {{ formatCurrencySymbol(item.price) }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Totals -->
                            <div class="totals">
                                <div class="trow">
                                    <span>Sub Total</span>
                                    <b class="sub-total">{{ formatCurrencySymbol(cartData.subtotal) }}</b>
                                </div>

                                <!-- Tax Row -->
                                <div class="trow" v-if="cartData.tax > 0">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="bi bi-receipt text-info"></i>
                                        Tax
                                    </span>
                                    <b class="text-info">{{ formatCurrencySymbol(cartData.tax) }}</b>
                                </div>

                                <!-- Service Charges -->
                                <div v-if="cartData.serviceCharges > 0" class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Service Charges:</span>
                                    <span class="fw-semibold">{{ formatCurrencySymbol(cartData.serviceCharges) }}</span>
                                </div>

                                <!-- Delivery Charges -->
                                <div v-if="cartData.deliveryCharges > 0" class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Delivery Charges:</span>
                                    <span class="fw-semibold">{{ formatCurrencySymbol(cartData.deliveryCharges)
                                        }}</span>
                                </div>

                                <!-- Promo Discount -->
                                <div v-if="cartData.appliedPromos && cartData.appliedPromos.length > 0"
                                    class="promos-section mb-3">
                                    <div class="promo-total mt-2 rounded-3 bg-success-subtle">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-success">Total Promo Savings:</span>
                                            <b class="text-success fs-6">-{{
                                                formatCurrencySymbol(cartData.promoDiscount) }}</b>
                                        </div>
                                    </div>
                                </div>

                                <!-- Total -->
                                <div class="trow total">
                                    <span>Total</span>
                                    <b>{{ formatCurrencySymbol(cartData.total) }}</b>
                                </div>
                            </div>

                            <!-- Note Display -->
                            <div v-if="cartData.note" class="mt-3">
                                <label class="form-label small fw-semibold">Note</label>
                                <div class="form-control form-control-sm rounded-3 bg-light" style="min-height: 60px;">
                                    {{ cartData.note }}
                                </div>
                            </div>
                        </div>

                        <div class="cart-footer">
                            <div class="text-center py-2">
                                <span class="text-muted small fw-semibold">
                                    <i class="bi bi-clock-fill me-1"></i>
                                    {{ currentTime }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* ========== Connection Bar ========== */
.connection-bar {
    background: #fef3c7;
    border-bottom: 2px solid #f59e0b;
    transition: all 0.3s ease;
}

.connection-bar.connected {
    background: #d1fae5;
    border-bottom: 2px solid #10b981;
}

.status-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #f59e0b;
    display: inline-block;
    animation: pulse 2s infinite;
}

.status-dot.active {
    background: #10b981;
}

@keyframes pulse {

    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: 0.5;
    }
}

/* ========== Page Base ========== */
.page-wrapper {
    background: #f5f7fb;
    min-height: 100vh;
}

/* ========== Categories Grid (Same as POS) ========== */
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
    box-shadow: 0 6px 16px rgba(17, 23, 31, 0.06);
    transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.cat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 24px rgba(17, 23, 31, 0.1);
}

.cat-icon-wrap {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #eee;
    display: grid;
    place-items: center;
    margin-bottom: 0.25rem;
}

.cat-icon {
    font-size: 1.35rem;
    line-height: 1;
}

.cat-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.cat-name {
    font-weight: 700;
    font-size: 1rem;
    color: #141414;
}

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

/* ========== Search Display ========== */
.search-display {
    background: #fff;
    padding: 0.5rem 1rem;
    border-radius: 999px;
    border: 2px solid #1b1670;
    font-weight: 600;
    color: #1b1670;
}

/* ========== Cart Panel (Same as POS) ========== */
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

.order-type {
    display: flex;
    gap: 0.4rem;
    width: 100%;
    justify-content: center;
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
    border-bottom-left-radius: 1rem;
    border-bottom-right-radius: 1rem;
}

/* ========== Scrollbar ========== */
.cart-lines::-webkit-scrollbar {
    width: 6px;
}

.cart-lines::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.cart-lines::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

.cart-lines::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* ========== Responsive ========== */
@media (max-width: 1199.98px) {
    .cat-card {
        padding: 1.5rem 0.85rem;
    }
}

@media (max-width: 991.98px) {
    .cart-lines {
        max-height: 300px;
    }

    .cat-icon-wrap {
        width: 60px;
        height: 60px;
    }
}

@media (max-width: 575.98px) {
    .cat-card {
        padding: 1.25rem 0.75rem;
    }

    .cat-icon-wrap {
        width: 52px;
        height: 52px;
    }

    .cat-name {
        font-size: 0.9rem;
    }
}
</style>