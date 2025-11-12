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
    saleDiscount: 0,
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

// ✅ NEW: Track selected variants and addons from POS
const selectedCardVariant = ref({});
const selectedCardAddons = ref({});

const currentTime = ref(new Date().toLocaleTimeString());
const connectionStatus = ref('Connecting...');
let echoChannel = null;

const formatCurrencySymbol = (amount) => {
    const num = parseFloat(amount) || 0;
    return `£${num.toFixed(2)}`;
};

// ✅ Filter categories to show only those with menu items
const filteredCategories = computed(() => {
    return menuCategories.value.filter(cat => cat.menu_items_count > 0);
});

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

// ✅ NEW: Get variant price range
const getVariantPriceRange = (product) => {
    if (!product.variants || product.variants.length === 0) {
        return { min: product.price, max: product.price };
    }
    const prices = product.variants.map(v => Number(v.price));
    return {
        min: Math.min(...prices),
        max: Math.max(...prices)
    };
};

// ✅ NEW: Get selected variant or default
const getSelectedVariant = (product) => {
    if (!product.variants || product.variants.length === 0) return null;
    const selectedId = selectedCardVariant.value[product.id];
    return product.variants.find(v => v.id === selectedId) || product.variants[0];
};

// ✅ NEW: Get total price including addons
const getTotalPrice = (product) => {
    let basePrice = product.price;

    // Use variant price if selected
    const selectedVariant = getSelectedVariant(product);
    if (selectedVariant) {
        basePrice = Number(selectedVariant.price);
    }

    // Add addon prices
    const productAddons = selectedCardAddons.value[product.id] || {};
    let addonTotal = 0;

    Object.values(productAddons).forEach(groupAddons => {
        if (Array.isArray(groupAddons)) {
            groupAddons.forEach(addon => {
                addonTotal += Number(addon.price || 0);
            });
        }
    });

    return basePrice + addonTotal;
};

// ========================================
// NEW: Sales Discount Eligibility Check
// =======================================
const calculateResalePrice = (item, isVariant = false) => {
    if (!item) return 0;

    const isSaleable = item.is_saleable ?? item?.menu_item?.is_saleable;
    const resaleType = item.resale_type ?? item?.menu_item?.resale_type;
    const resaleValue = item.resale_value ?? item?.menu_item?.resale_value;
    const basePrice = parseFloat(item.price || 0);

    if (!isSaleable || !resaleType || !resaleValue) {
        return 0;
    }

    if (resaleType === 'flat') {
        return parseFloat(resaleValue);
    }

    if (resaleType === 'percentage') {
        return (basePrice * parseFloat(resaleValue)) / 100;
    }

    return 0;
};

const getFinalPrice = (item, isVariant = false) => {
    const basePrice = parseFloat(item.price || 0);
    const resalePrice = calculateResalePrice(item, isVariant);
    return Math.max(0, basePrice - resalePrice);
};

const getResaleBadgeInfo = (item, isVariant = false) => {
    if (!item) return null;

    const isSaleable = item.is_saleable ?? item?.menu_item?.is_saleable;
    const resaleType = item.resale_type ?? item?.menu_item?.resale_type;
    const resaleValue = item.resale_value ?? item?.menu_item?.resale_value;
    const resalePrice = calculateResalePrice(item, isVariant);

    if (!isSaleable || !resaleType || !resaleValue || resalePrice <= 0) {
        return null;
    }

    return {
        type: resaleType,
        value: resaleValue,
        amount: resalePrice,
        display:
            resaleType === 'flat'
                ? `Sale ${formatCurrencySymbol(resalePrice)}`
                : `${resaleValue}% OFF`
    };
};

// ✅ UPDATE: Get variant price range with resale
const getVariantPriceRangeWithResale = (product) => {
    if (!product.variants || product.variants.length === 0) {
        const hasSale = calculateResalePrice(product, false) > 0;
        return {
            min: getFinalPrice(product, false),
            max: getFinalPrice(product, false),
            minOriginal: hasSale ? parseFloat(product.price || 0) : null,
            maxOriginal: hasSale ? parseFloat(product.price || 0) : null
        };
    }

    const prices = product.variants.map(v => getFinalPrice(v, true));
    const originalPrices = product.variants.map(v => parseFloat(v.price || 0));
    const hasSale = product.variants.some(v => calculateResalePrice(v, true) > 0);

    return {
        min: Math.min(...prices),
        max: Math.max(...prices),
        minOriginal: hasSale ? Math.min(...originalPrices) : null,
        maxOriginal: hasSale ? Math.max(...originalPrices) : null
    };
};

const getTotalPriceWithResale = (product) => {
    const selectedVariant = getSelectedVariant(product);

    let basePrice;
    if (selectedVariant) {
        basePrice = getFinalPrice(selectedVariant, true);
    } else {
        basePrice = getFinalPrice(product, false);
    }

    // Add addon prices (addons don't have resale)
    const productAddons = selectedCardAddons.value[product.id] || {};
    let addonTotal = 0;

    Object.values(productAddons).forEach(groupAddons => {
        if (Array.isArray(groupAddons)) {
            groupAddons.forEach(addon => {
                addonTotal += Number(addon.price || 0);
            });
        }
    });

    return basePrice + addonTotal;
};


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

    console.log('🔌 Terminal ID:', props.terminalId);

    if (!window.Echo) {
        console.error('❌ Echo not found - WebSocket connection failed');
        connectionStatus.value = 'Error: Echo not found';
        return;
    }

    try {
        const channelName = `pos-terminal.${props.terminalId}`;
        console.log('🔌 Subscribing to channel:', channelName);

        echoChannel = window.Echo.channel(channelName);

        // Subscription success callback
        echoChannel.subscribed(() => {
            console.log('✅ Successfully subscribed to:', channelName);
            connectionStatus.value = 'Connected';
        });

        // Listen for cart updates
        echoChannel.listen('.cart.updated', (e) => {
            console.log('📦 Cart update received:', e);
            cartData.value = e.cart;
            connectionStatus.value = 'Connected';
        });

        // Listen for UI state updates
        echoChannel.listen('.ui.updated', (e) => {
            console.log('🖥️ UI update received:', e);

            const ui = e.ui || e;

            if (ui.showCategories !== undefined) showCategories.value = ui.showCategories;
            if (ui.activeCat !== undefined) activeCat.value = ui.activeCat;
            if (ui.menuCategories) menuCategories.value = ui.menuCategories;
            if (ui.menuItems) menuItems.value = ui.menuItems;
            if (ui.searchQuery !== undefined) searchQuery.value = ui.searchQuery;

            // ✅ NEW: Sync selected variants and addons
            if (ui.selectedCardVariant) selectedCardVariant.value = ui.selectedCardVariant;
            if (ui.selectedCardAddons) selectedCardAddons.value = ui.selectedCardAddons;

            console.log("✅ UI state updated:", {
                showCategories: showCategories.value,
                activeCat: activeCat.value,
                categoriesCount: menuCategories.value.length,
                itemsCount: menuItems.value.length,
                selectedVariants: Object.keys(selectedCardVariant.value).length,
                selectedAddons: Object.keys(selectedCardAddons.value).length
            });
        });

        // Add error handler
        echoChannel.error((error) => {
            console.error('❌ Channel error:', error);
            connectionStatus.value = 'Error';
        });

        // Monitor Pusher connection status
        if (window.Echo.connector && window.Echo.connector.pusher) {
            const pusher = window.Echo.connector.pusher;

            pusher.connection.bind('connected', () => {
                console.log('✅ Pusher connected');
                connectionStatus.value = 'Connected';
            });

            pusher.connection.bind('connecting', () => {
                console.log('🔄 Pusher connecting...');
                connectionStatus.value = 'Connecting...';
            });

            pusher.connection.bind('disconnected', () => {
                console.log('❌ Pusher disconnected');
                connectionStatus.value = 'Disconnected';
            });

            pusher.connection.bind('unavailable', () => {
                console.log('❌ Pusher unavailable');
                connectionStatus.value = 'Unavailable';
            });

            pusher.connection.bind('failed', () => {
                console.log('❌ Pusher connection failed');
                connectionStatus.value = 'Connection Failed';
            });

            pusher.connection.bind('error', (err) => {
                console.error('❌ Pusher error:', err);
                connectionStatus.value = 'Connection Error';
            });

            console.log('🔍 Current Pusher state:', pusher.connection.state);
        }

    } catch (error) {
        console.error('❌ Echo setup error:', error);
        connectionStatus.value = 'Setup Error';
    }

    // Debug: Log Echo configuration after 2 seconds
    setTimeout(() => {
        if (window.Echo && window.Echo.connector && window.Echo.connector.pusher) {
            const pusher = window.Echo.connector.pusher;
            console.log('📊 Echo Debug Info:', {
                state: pusher.connection.state,
                key: pusher.key,
                config: pusher.config,
                channels: Object.keys(pusher.channels.channels)
            });
        }
    }, 2000);
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
                    <!-- Categories Grid -->
                    <div v-if="showCategories" class="row g-3">
                        <div class="col-12 mb-3">
                            <h4 class="mb-3">Menu Categories</h4>
                            <hr class="mt-2 mb-3">
                        </div>

                        <!-- ✅ Categories List (only showing categories with menu items) -->
                        <div v-for="c in filteredCategories" :key="c.id" class="col-6 col-md-4 col-lg-3">
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
                        <div v-if="filteredCategories.length === 0" class="col-12">
                            <div class="alert alert-light border text-center rounded-4">
                                No categories found
                            </div>
                        </div>
                    </div>

                    <!-- Items in selected category -->
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

                                        <!-- ✅ UPDATED: Show Variant Price Range with Resale -->
                                        <div v-if="p.variants && p.variants.length > 0"
                                            class="position-absolute bottom-0 start-0 end-0 text-center bg-light bg-opacity-75 fw-semibold"
                                            style="font-size: 0.6rem !important; padding: 2px 4px;">

                                            <!-- Minimum price -->
                                            <span v-if="getVariantPriceRangeWithResale(p).minOriginal !== null"
                                                class="text-muted text-decoration-line-through me-1">
                                                {{ formatCurrencySymbol(getVariantPriceRangeWithResale(p).minOriginal)
                                                }}
                                            </span>
                                            <span class="fw-bold text-success me-2">
                                                {{ formatCurrencySymbol(getVariantPriceRangeWithResale(p).min) }} -
                                            </span>

                                            <!-- Maximum price -->
                                            <span v-if="getVariantPriceRangeWithResale(p).maxOriginal !== null"
                                                class="text-muted text-decoration-line-through me-1">
                                                {{ formatCurrencySymbol(getVariantPriceRangeWithResale(p).maxOriginal)
                                                }}
                                            </span>
                                            <span class="fw-bold text-success">
                                                {{ formatCurrencySymbol(getVariantPriceRangeWithResale(p).max) }}
                                            </span>
                                        </div>

                                        <!-- ✅ UPDATED: Dynamic Price Badge with Resale -->
                                        <span
                                            class="position-absolute top-0 start-0 m-2 px-2 py-1 rounded-pill text-white fw-semibold"
                                            :style="{ background: p.label_color || '#1B1670', fontSize: '0.78rem' }">
                                            <template
                                                v-if="getResaleBadgeInfo(getSelectedVariant(p) || p, !!p.variants?.length)">
                                                <span class="text-decoration-line-through opacity-75 me-1">
                                                    {{ formatCurrencySymbol(getSelectedVariant(p)?.price || p.price) }}
                                                </span>
                                                <span class="fw-bold text-white">
                                                    {{ formatCurrencySymbol(getTotalPriceWithResale(p)) }}
                                                </span>
                                            </template>
                                            <template v-else>
                                                {{ formatCurrencySymbol(getTotalPriceWithResale(p)) }}
                                            </template>
                                        </span>
                                    </div>
                                    <!-- Right Side (Details + Variant + Addons) -->
                                    <div class="p-3 d-flex flex-column justify-content-between"
                                        style="flex: 1 1 60%; min-width: 0;">
                                        <div>
                                            <span
                                                v-if="(p.variants && p.variants.length > 0 && getResaleBadgeInfo(getSelectedVariant(p), true)) ||
                                                    (!p.variants || p.variants.length === 0) && getResaleBadgeInfo(p, false)"
                                                class="position-absolute px-2 py-1 rounded-pill bg-success text-white small fw-bold"
                                                :style="{ fontSize: '0.7rem', top: '7px', right: '7px' }">
                                                {{ p.variants && p.variants.length > 0
                                                    ? getResaleBadgeInfo(getSelectedVariant(p), true).display
                                                    : getResaleBadgeInfo(p, false).display }}
                                            </span>
                                            <div class="h5 fw-bold mb-2" :style="{ color: p.label_color || '#1B1670' }">
                                                {{ p.title }}
                                            </div>

                                            <!-- ✅ Variant Dropdown (Read-only, showing selected from POS) -->
                                            <div v-if="p.variants && p.variants.length > 0" class="mb-3">
                                                <label class="form-label small fw-semibold mb-1">
                                                    Selected Variant:
                                                </label>
                                                <select :value="selectedCardVariant[p.id] || p.variants[0].id"
                                                    class="form-select form-select-sm variant-select" disabled
                                                    style="background-color: #f8f9fa; cursor: not-allowed;">
                                                    <option v-for="variant in p.variants" :key="variant.id"
                                                        :value="variant.id">
                                                        {{ variant.name }} - {{ formatCurrencySymbol(variant.price) }}

                                                    </option>
                                                </select>
                                            </div>

                                            <!-- ✅ Addons Display (Read-only, showing selected from POS) -->
                                            <div v-if="p.addon_groups && p.addon_groups.length > 0">
                                                <div v-for="group in p.addon_groups" :key="group.group_id" class="mb-3">
                                                    <label class="form-label small fw-semibold mb-2">
                                                        {{ group.group_name }}
                                                        <span v-if="group.max_select > 0" class="text-muted"
                                                            style="font-size: 0.75rem;">
                                                            (Max {{ group.max_select }})
                                                        </span>
                                                    </label>

                                                    <!-- Show selected addons -->
                                                    <div v-if="selectedCardAddons[p.id]?.[group.group_id]?.length > 0"
                                                        class="d-flex flex-wrap gap-1">
                                                        <span v-for="addon in selectedCardAddons[p.id][group.group_id]"
                                                            :key="addon.id" class="badge bg-primary px-2 py-1">
                                                            {{ addon.name }} +{{ formatCurrencySymbol(addon.price) }}
                                                        </span>
                                                    </div>
                                                    <div v-else class="text-muted small fst-italic">
                                                        No addons selected
                                                    </div>
                                                </div>
                                            </div>


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
                                        <div class="meta d-flex align-items-center flex-wrap gap-1">
                                            <!-- Item Title -->
                                            <div class="name fw-semibold text-truncate" :title="item.title"
                                                style="max-width: 150px;">
                                                {{ item.title }}
                                            </div>

                                            <!-- Sale Discount Badge -->
                                            <span v-if="item.resale_discount_per_item > 0"
                                                class="badge d-inline-flex align-items-center gap-1 sale-badge">
                                                <i class="bi bi-star-fill blinking-star"></i>
                                                -{{ formatCurrencySymbol(item.resale_discount_per_item) }}
                                            </span>



                                            <!-- Addons -->
                                            <span v-if="item.addons && item.addons.length > 0"
                                                class="text-muted small d-inline-flex align-items-center">
                                                <i class="bi bi-plus-circle-fill text-primary me-1"
                                                    style="font-size: 0.9rem;"></i>
                                                {{ item.addons.length }} addon(s)
                                            </span>

                                            <!-- Variant -->
                                            <span v-if="item.variant_name" class="badge ms-1"
                                                style="font-size: 0.65rem; background: #1B1670; color: white;">
                                                {{ item.variant_name }}
                                            </span>

                                            <!-- Promo Tag -->
                                            <span v-if="isItemEligibleForPromo(item)" class="badge bg-success ms-1"
                                                style="font-size: 0.65rem;">
                                                <i class="bi bi-tag-fill"></i>
                                            </span>
                                        </div>

                                        <!-- Optional: Note below (if you still want notes separate) -->
                                        <div class="note text-muted small mt-1" v-if="item.note">
                                            {{ item.note }}
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
                                <div v-if="cartData.saleDiscount > 0" class="trow">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="bi bi-tag text-danger"></i>
                                        <span class="text-danger">Sale Discount:</span>
                                    </span>
                                    <b class="text-danger">-{{ formatCurrencySymbol(cartData.saleDiscount) }}</b>
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

.sale-badge {
    font-size: 0.65rem !important;
    padding: 3px 6px !important;
    background: #fff5f1;
    color: #d9480f;
    border: 1px dashed #f97316;
    border-radius: 0.5rem;
    font-weight: 500;
}

.blinking-star {
    font-size: 0.7rem;
    color: #f97316;
    animation: blink 1s infinite;
}

@keyframes blink {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.3; transform: scale(1.2); }
}

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
    margin: 0px 10px !important;
    padding: 0px !important;
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

/* ========== Product Cards ========== */
.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12) !important;
}

/* ========== Variant Select Styling ========== */
.variant-select {
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.variant-select:disabled {
    opacity: 0.8;
    cursor: not-allowed;
    background-color: #f8f9fa !important;
}

/* ========== Addon Badges ========== */
.badge {
    font-weight: 600;
    font-size: 0.8rem;
    padding: 0.4rem 0.7rem;
    border-radius: 6px;
}

.badge.bg-primary {
    background-color: #1b1670 !important;
}

/* ========== Cart Panel ========== */
.cart {
    display: flex;
    flex-direction: column;
    height: fit-content;
    max-height: calc(100vh - 120px);
    position: sticky;
    top: 20px;
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
    transition: all 0.2s ease;
}

.ot-pill.active {
    background: #fff;
    color: #1b1670;
    font-weight: 700;
}

.cart-body {
    padding: 1rem;
    background: #fff;
    flex: 1;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

/* ========== Cart Lines ========== */
.cart-lines {
    background: #fff;
    border: 1px dashed #e8e9ef;
    border-radius: 12px;
    padding: 0.5rem;
    max-height: 360px;
    overflow-y: auto;
    overflow-x: hidden;
    flex: 1;
}

.empty {
    color: #9aa0b6;
    text-align: center;
    padding: 1.25rem 0;
    font-size: 0.95rem;
}

.line {
    display: grid;
    grid-template-columns: 1fr auto auto;
    align-items: center;
    gap: 0.6rem;
    padding: 0.45rem 0.35rem;
    border-bottom: 1px solid #f1f2f6;
    transition: background-color 0.2s ease;
}

.line:last-child {
    border-bottom: 0;
}

.line:hover {
    background-color: #f9fafb;
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
    flex-shrink: 0;
}

.meta {
    min-width: 0;
    flex: 1;
}

.meta .name {
    font-weight: 700;
    font-size: 0.92rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #1f2937;
}

.meta .addon-icons {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    align-items: center;
}

.meta .note {
    font-size: 0.75rem;
    color: #8a8fa7;
    margin-top: 0.25rem;
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
    font-size: 0.85rem;
    color: #374151;
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
    color: #1f2937;
    font-size: 0.95rem;
}

/* ========== Totals Section ========== */
.totals {
    padding: 0.75rem 0 0.25rem;
    border-top: 1px solid #f1f2f6;
    margin-top: 0.5rem;
}

.trow {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.35rem 0;
    color: #4b5563;
    font-size: 0.9rem;
}

.trow span {
    display: flex;
    align-items: center;
}

.trow b {
    font-weight: 700;
}

.trow.total {
    border-top: 2px solid #e5e7eb;
    margin-top: 0.5rem;
    padding-top: 0.75rem;
    color: #181818;
    font-size: 1.1rem;
    font-weight: 800;
}

.sub-total {
    color: #1f2937;
}

/* ========== Promo Section ========== */
.promos-section {
    margin-top: 0.5rem;
}

.promo-total {
    padding: 0.75rem;
    background: #dcfce7;
    border: 1px solid #86efac;
}

.bg-success-subtle {
    background-color: #dcfce7 !important;
}

.text-success {
    color: #16a34a !important;
}

/* ========== Cart Footer ========== */
.cart-footer {
    background: #f7f8ff;
    padding: 0.75rem;
    border-bottom-left-radius: 1rem;
    border-bottom-right-radius: 1rem;
    border-top: 1px solid #e5e7eb;
}

/* ========== Scrollbar Styling ========== */
.cart-lines::-webkit-scrollbar {
    width: 6px;
}

.cart-lines::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.cart-lines::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
    transition: background 0.2s ease;
}

.cart-lines::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* ========== Responsive Design ========== */
@media (max-width: 1199.98px) {
    .cat-card {
        padding: 1.5rem 0.85rem;
    }

    .cat-icon-wrap {
        width: 70px;
        height: 70px;
    }
}

@media (max-width: 991.98px) {
    .cart {
        max-height: none;
        position: relative;
        top: 0;
    }

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

    .connection-bar .container-fluid {
        padding: 0 1rem;
    }

    .search-display {
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
    }
}
</style>