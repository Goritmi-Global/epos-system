<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    terminalId: {
        type: String,
        required: true
    }
});

// State
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

const menuCategories = ref([]);
const menuItems = ref([]);
const selectedCardVariant = ref({});
const selectedCardAddons = ref({});

const currentTime = ref(new Date().toLocaleTimeString());
const connectionStatus = ref('Connecting...');
const lastUpdate = ref(null);

// Polling state
const currentVersion = ref(0);
const isFetching = ref(false);
const consecutiveErrors = ref(0);
const isPageVisible = ref(true);

let versionCheckInterval = null;
let timeUpdateInterval = null;

// ✅ FASTER: Reduced timeouts for quicker responses
const VERSION_CHECK_TIMEOUT = 3000;  // 3 seconds instead of 10
const STATE_FETCH_TIMEOUT = 5000;    // 5 seconds instead of 15

// ✅ FASTER: More aggressive polling for cart updates
const getPollingInterval = () => {
    if (consecutiveErrors.value === 0) return 200;  // Check every 200ms when no errors
    if (consecutiveErrors.value === 1) return 500;
    if (consecutiveErrors.value === 2) return 1000;
    return 2000;  // Max 2 seconds instead of 5
};

const checkForUpdates = async () => {
    if (isFetching.value || !isPageVisible.value) return;

    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), VERSION_CHECK_TIMEOUT);

        const response = await fetch(
            `/api/pos/terminal/${props.terminalId}/version`,
            { 
                signal: controller.signal,
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                cache: 'no-store'  // ✅ Prevent caching
            }
        );
        
        clearTimeout(timeoutId);

        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const data = await response.json();
        
        // ✅ CRITICAL: Always fetch if version changed OR on first load
        if (data.version !== currentVersion.value) {
            console.log(`🔄 Version: ${currentVersion.value} → ${data.version}`);
            await fetchTerminalState();
        }
        
        connectionStatus.value = 'Connected';
        consecutiveErrors.value = 0;
        
    } catch (error) {
        consecutiveErrors.value++;
        
        if (error.name === 'AbortError') {
            console.warn('⚠️ Version check timeout');
        } else {
            console.error('❌ Version check failed:', error.message);
        }
        
        connectionStatus.value = 'Connection Error';
        
        // ✅ Try fallback faster - after 3 errors instead of 5
        if (consecutiveErrors.value >= 3 && !isFetching.value) {
            console.log('🔄 Fallback: Fetching state');
            await fetchTerminalState();
        }
        
        restartPolling();
    }
};

const fetchTerminalState = async () => {
    if (isFetching.value) {
        console.log('⏳ Already fetching...');
        return;
    }
    
    isFetching.value = true;
    console.log('📡 Fetching terminal state...');
    
    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), STATE_FETCH_TIMEOUT);

        const response = await fetch(
            `/api/pos/terminal/${props.terminalId}/state`,
            { 
                signal: controller.signal,
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                cache: 'no-store'  // ✅ Prevent caching
            }
        );
        
        clearTimeout(timeoutId);

        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const data = await response.json();
        
        console.log('📦 Received:', {
            version: data.version,
            cartItems: data.cart?.items?.length || 0,
            categories: data.ui?.menuCategories?.length || 0,
            items: data.ui?.menuItems?.length || 0
        });
        
        if (data.success) {
            // ✅ CRITICAL: Force complete reactivity update
            if (data.cart) {
                cartData.value = JSON.parse(JSON.stringify(data.cart));
                console.log('✅ Cart updated:', cartData.value.items.length, 'items');
            }

            if (data.ui) {
                const ui = data.ui;
                menuCategories.value = JSON.parse(JSON.stringify(ui.menuCategories || []));
                menuItems.value = JSON.parse(JSON.stringify(ui.menuItems || []));
                selectedCardVariant.value = JSON.parse(JSON.stringify(ui.selectedCardVariant || {}));
                selectedCardAddons.value = JSON.parse(JSON.stringify(ui.selectedCardAddons || {}));
            }

            lastUpdate.value = data.timestamp;
            currentVersion.value = data.version;
            connectionStatus.value = 'Connected';
            consecutiveErrors.value = 0;
            
            console.log('✅ State updated - v' + data.version);
        }
    } catch (error) {
        consecutiveErrors.value++;
        
        if (error.name === 'AbortError') {
            console.warn('⚠️ State fetch timeout');
        } else {
            console.error('❌ Failed to fetch state:', error.message);
        }
        
        connectionStatus.value = 'Connection Error';
        restartPolling();
    } finally {
        isFetching.value = false;
    }
};

const restartPolling = () => {
    stopPolling();
    startPolling();
};

const startPolling = () => {
    fetchTerminalState();
    const interval = getPollingInterval();
    
    versionCheckInterval = setInterval(() => {
        checkForUpdates();
    }, interval);
};

const stopPolling = () => {
    if (versionCheckInterval) {
        clearInterval(versionCheckInterval);
        versionCheckInterval = null;
    }
};

const handleVisibilityChange = () => {
    isPageVisible.value = !document.hidden;
    
    if (isPageVisible.value) {
        consecutiveErrors.value = 0;
        fetchTerminalState();
        restartPolling();
    } else {
        stopPolling();
    }
};

const formatCurrencySymbol = (amount) => {
    const num = parseFloat(amount) || 0;
    return `£${num.toFixed(2)}`;
};

// Group products by category
const productsByCategory = computed(() => {
    const grouped = {};
    
    menuItems.value.forEach((item) => {
        const catId = item.category?.id || "uncategorized";
        const catName = item.category?.name || "Uncategorized";
        
        if (!grouped[catId]) {
            grouped[catId] = {
                id: catId,
                name: catName,
                products: []
            };
        }

        grouped[catId].products.push({
            id: item.id,
            title: item.name,
            img: item.image_url || "/assets/img/default.png",
            price: Number(item.price),
            label_color: item.label_color || "#1B1670",
            description: item.description,
            variants: item.variants ?? [],
            addon_groups: item.addon_groups ?? [],
        });
    });
    
    return Object.values(grouped);
});

const getSelectedVariant = (product) => {
    if (!product.variants || product.variants.length === 0) return null;
    const selectedId = selectedCardVariant.value[product.id];
    return product.variants.find(v => v.id === selectedId) || product.variants[0];
};

const calculateResalePrice = (item, isVariant = false) => {
    if (!item) return 0;
    const isSaleable = item.is_saleable ?? item?.menu_item?.is_saleable;
    const resaleType = item.resale_type ?? item?.menu_item?.resale_type;
    const resaleValue = item.resale_value ?? item?.menu_item?.resale_value;
    const basePrice = parseFloat(item.price || 0);

    if (!isSaleable || !resaleType || !resaleValue) return 0;
    if (resaleType === 'flat') return parseFloat(resaleValue);
    if (resaleType === 'percentage') return (basePrice * parseFloat(resaleValue)) / 100;
    return 0;
};

const getFinalPrice = (item, isVariant = false) => {
    const basePrice = parseFloat(item.price || 0);
    const resalePrice = calculateResalePrice(item, isVariant);
    return Math.max(0, basePrice - resalePrice);
};

const getTotalPriceWithResale = (product) => {
    const selectedVariant = getSelectedVariant(product);
    let basePrice = selectedVariant ? getFinalPrice(selectedVariant, true) : getFinalPrice(product, false);

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

const isItemEligibleForPromo = (cartItem) => {
    if (!cartData.value.appliedPromos || cartData.value.appliedPromos.length === 0) return false;
    return cartData.value.appliedPromos.some(promo => {
        if (!promo.applied_to_items || promo.applied_to_items.length === 0) return true;
        return promo.applied_to_items.includes(cartItem.id);
    });
};

onMounted(() => {
    timeUpdateInterval = setInterval(() => {
        currentTime.value = new Date().toLocaleTimeString();
    }, 1000);

    document.addEventListener('visibilitychange', handleVisibilityChange);
    startPolling();
});

onUnmounted(() => {
    stopPolling();
    if (timeUpdateInterval) clearInterval(timeUpdateInterval);
    document.removeEventListener('visibilitychange', handleVisibilityChange);
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
            <div class="row gx-3">
                <!-- LEFT: All Products by Category -->
                <div class="col-lg-8">
                    <div class="products-scroll">
                        <!-- Empty State -->
                        <div v-if="productsByCategory.length === 0" class="alert alert-light text-center" style="align-items: center; display: flex; flex-direction: column; justify-content: center; height: 520px;">
                            <i class="bi bi-basket fs-1 text-muted d-block mb-2"></i>
                            <p class="mb-0 text-muted">No menu items available</p>
                        </div>

                        <!-- Category Sections -->
                        <div v-for="category in productsByCategory" :key="category.id" class="category-section mb-4">
                            <div class="category-header">
                                <h4 class="category-title">{{ category.name }}</h4>
                                <div class="category-count">{{ category.products.length }} items</div>
                            </div>

                            <!-- Products Grid -->
                            <div class="row g-3">
                                <div class="col-12 col-md-6 col-xl-2" v-for="p in category.products" :key="p.id">
                                    <div class="product-card" :style="{ borderColor: p.label_color }">
                                        <div class="product-image">
                                            <img :src="p.img" :alt="p.title" />
                                            <span class="product-price" :style="{ background: p.label_color }">
                                                {{ formatCurrencySymbol(getTotalPriceWithResale(p)) }}
                                            </span>
                                        </div>
                                        <div class="product-info">
                                            <h6 class="product-title" :style="{ color: p.label_color }">{{ p.title }}</h6>
                                            
                                            <!-- Variant Display -->
                                            <div v-if="p.variants && p.variants.length > 0" class="product-meta">
                                                <small class="text-muted">
                                                    <i class="bi bi-layers"></i>
                                                    {{ getSelectedVariant(p)?.name || p.variants[0].name }}
                                                </small>
                                            </div>
                                            
                                            <!-- Addons Display -->
                                            <div v-if="selectedCardAddons[p.id]" class="product-addons">
                                                <div v-for="(addons, groupId) in selectedCardAddons[p.id]" :key="groupId">
                                                    <span v-for="addon in addons" :key="addon.id" class="addon-badge">
                                                        +{{ addon.name }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Cart (Always Visible) -->
                <div class="col-lg-4">
                    <div class="cart-sticky">
                        <div class="cart">
                            <div class="cart-header">
                                <div class="order-type">
                                    <div class="ot-pill active">{{ cartData.orderType }}</div>
                                </div>
                            </div>

                            <div class="cart-body">
                                <!-- Customer Info -->
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

                                <!-- Cart Items -->
                                <div class="cart-lines">
                                    <div v-if="cartData.items.length === 0" class="empty">
                                        <i class="bi bi-cart-x fs-2 text-muted"></i>
                                        <p class="mb-0 mt-2 small text-muted">Cart is empty</p>
                                    </div>

                                    <div v-for="(item, i) in cartData.items" :key="i" class="cart-item">
                                        <img :src="item.img || '/assets/img/default.png'" class="cart-item-img" />
                                        <div class="cart-item-details">
                                            <div class="cart-item-name">{{ item.title }}</div>
                                            
                                            <div class="cart-item-meta">
                                                <!-- Sale Badge -->
                                                <span v-if="item.resale_discount_per_item > 0" class="sale-badge">
                                                    <i class="bi bi-star-fill"></i>
                                                    -{{ formatCurrencySymbol(item.resale_discount_per_item) }}
                                                </span>
                                                
                                                <!-- Variant -->
                                                <span v-if="item.variant_name" class="variant-badge">
                                                    {{ item.variant_name }}
                                                </span>
                                                
                                                <!-- Addons -->
                                                <span v-if="item.addons && item.addons.length > 0" class="addon-count">
                                                    <i class="bi bi-plus-circle"></i>
                                                    {{ item.addons.length }}
                                                </span>
                                                
                                                <!-- Promo -->
                                                <span v-if="isItemEligibleForPromo(item)" class="promo-badge">
                                                    <i class="bi bi-tag-fill"></i>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="cart-item-qty">×{{ item.qty }}</div>
                                        <div class="cart-item-price">{{ formatCurrencySymbol(item.price) }}</div>
                                    </div>
                                </div>

                                <!-- Totals -->
                                <div class="cart-totals">
                                    <div class="total-row">
                                        <span>Subtotal</span>
                                        <b>{{ formatCurrencySymbol(cartData.subtotal) }}</b>
                                    </div>
                                    
                                    <div v-if="cartData.tax > 0" class="total-row">
                                        <span><i class="bi bi-receipt text-info"></i> Tax</span>
                                        <b class="text-info">{{ formatCurrencySymbol(cartData.tax) }}</b>
                                    </div>
                                    
                                    <div v-if="cartData.serviceCharges > 0" class="total-row">
                                        <span>Service</span>
                                        <b>{{ formatCurrencySymbol(cartData.serviceCharges) }}</b>
                                    </div>
                                    
                                    <div v-if="cartData.deliveryCharges > 0" class="total-row">
                                        <span>Delivery</span>
                                        <b>{{ formatCurrencySymbol(cartData.deliveryCharges) }}</b>
                                    </div>
                                    
                                    <div v-if="cartData.saleDiscount > 0" class="total-row text-danger">
                                        <span><i class="bi bi-tag"></i> Sale Discount</span>
                                        <b>-{{ formatCurrencySymbol(cartData.saleDiscount) }}</b>
                                    </div>
                                    
                                    <div v-if="cartData.promoDiscount > 0" class="total-row text-success">
                                        <span><i class="bi bi-gift"></i> Promo Savings</span>
                                        <b>-{{ formatCurrencySymbol(cartData.promoDiscount) }}</b>
                                    </div>
                                    
                                    <div class="total-row total-final">
                                        <span>Total</span>
                                        <b>{{ formatCurrencySymbol(cartData.total) }}</b>
                                    </div>
                                </div>

                                <!-- Note -->
                                <div v-if="cartData.note" class="cart-note">
                                    <label class="small fw-semibold mb-1">Note</label>
                                    <div class="note-content">{{ cartData.note }}</div>
                                </div>
                            </div>

                            <div class="cart-footer">
                                <i class="bi bi-clock"></i>
                                {{ currentTime }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.page-wrapper {
    background: #f5f7fb;
    min-height: 100vh;
    margin: 0px;
    padding: 0px 0px;
}

/* Connection Bar */
.connection-bar {
    background: #fef3c7;
    border-bottom: 2px solid #f59e0b;
    transition: all 0.3s;
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
    animation: pulse 2s infinite;
}

.status-dot.active {
    background: #10b981;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Products Section */
.products-scroll {
    max-height: calc(100vh - 120px);
    overflow-y: auto;
    padding-right: 10px;
}

.products-scroll::-webkit-scrollbar {
    width: 8px;
}

.products-scroll::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.products-scroll::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.category-section {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.25rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #e5e7eb;
}

.category-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.category-count {
    background: #f3f4f6;
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #6b7280;
}

/* Product Card */
.product-card {
    background: white;
    border: 3px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s;
    height: 230px;
}

@media only screen and (min-device-width: 1024px) and (max-device-width: 1366px) {
  .product-card{
    height: 160px;
  }
  .product-card h6{
    font-size: 12px;
  }
  .product-price {
    position: absolute !important;
    top: 7px !important;
    left: 7px !important;
    color: white !important;
    padding: 0.2rem 0.5rem !important;
    border-radius: 999px !important;
    font-weight: 700 !important;
    font-size: 0.7rem !important;
}
.product-meta{
    font-size: 12px;
}
}

.product-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
}

.product-image {
    position: relative;
    padding-top: 75%;
    overflow: hidden;
}

.product-image img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-price {
    position: absolute;
    top: 10px;
    left: 10px;
    background: #1b1670;
    color: white;
    padding: 0.4rem 0.8rem;
    border-radius: 999px;
    font-weight: 700;
    font-size: 0.9rem;
}

.product-info {
    padding: 1rem;
}

.product-title {
    font-weight: 700;
    margin-bottom: 0.25rem;
    font-size: 1rem;
}

.product-desc {
    font-size: 0.85rem;
    color: #6b7280;
    margin-bottom: 0.5rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.product-meta {
    margin-top: 0.5rem;
}

.product-addons {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    margin-top: 0.5rem;
}

.addon-badge {
    background: #f3f4f6;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    color: #6b7280;
}

/* Cart */
.cart-sticky {
    position: sticky;
    top: 20px;
}

.cart {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    overflow: hidden;
    min-height: 90vh;
    display: flex;
    flex-direction: column;
}


.cart-header {
    background: #1b1670;
    color: white;
    padding: 1rem;
    text-align: center;
}

.ot-pill {
    background: rgba(255,255,255,0.2);
    padding: 0.35rem 0.8rem;
    border-radius: 999px;
    font-size: 0.85rem;
    display: inline-block;
}

.ot-pill.active {
    background: white;
    color: #1b1670;
    font-weight: 700;
}

.cart-body {
    padding: 1rem;
    flex: 1;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.cart-lines {
    border: 1px dashed #e5e7eb;
    border-radius: 12px;
    padding: 0.5rem;
    margin-bottom: 1rem;
    flex: 1;
    overflow-y: auto;
    max-height: 400px;
}

.cart-lines::-webkit-scrollbar {
    width: 6px;
}

.cart-lines::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
.cart-lines {
    height: 100%;          
    min-height: 350px;    
    display: flex;
    flex-direction: column;
}

.empty {
    flex: 1;                         
    display: flex;
    flex-direction: column;
    justify-content: center;         
    align-items: center;             
    text-align: center;
}


.empty {
    text-align: center;
    padding: 2rem 1rem;
    color: #9ca3af;
}

.cart-item {
    display: grid;
    grid-template-columns: 40px 1fr auto auto;
    gap: 0.5rem;
    align-items: center;
    padding: 0.5rem;
    border-bottom: 1px solid #f3f4f6;
}

.cart-item:last-child {
    border-bottom: none;
}

.cart-item-img {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    object-fit: cover;
}

.cart-item-details {
    min-width: 0;
}

.cart-item-name {
    font-weight: 700;
    font-size: 0.9rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cart-item-meta {
    display: flex;
    gap: 0.25rem;
    flex-wrap: wrap;
    margin-top: 0.25rem;
}

.sale-badge, .variant-badge, .addon-count, .promo-badge {
    font-size: 0.7rem;
    padding: 0.15rem 0.4rem;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    gap: 0.15rem;
}

.sale-badge {
    background: #fee2e2;
    color: #dc2626;
}

.variant-badge {
    background: #e0e7ff;
    color: #4f46e5;
}

.addon-count {
    background: #dbeafe;
    color: #2563eb;
}

.promo-badge {
    background: #d1fae5;
    color: #059669;
}

.cart-item-qty {
    font-weight: 700;
    background: #f3f4f6;
    padding: 0.2rem 0.5rem;
    border-radius: 999px;
    font-size: 0.85rem;
}

.cart-item-price {
    font-weight: 700;
    font-size: 0.95rem;
}

.cart-totals {
    border-top: 1px solid #e5e7eb;
    padding-top: 0.75rem;
}

.total-row {
    display: flex;
    justify-content: space-between;
    padding: 0.35rem 0;
    font-size: 0.9rem;
}

.total-final {
    border-top: 2px solid #e5e7eb;
    margin-top: 0.5rem;
    padding-top: 0.75rem;
    font-size: 1.1rem;
    font-weight: 800;
}

.cart-note {
    margin-top: 1rem;
    padding: 0.75rem;
    background: #f9fafb;
    border-radius: 8px;
}

.note-content {
    font-size: 0.85rem;
    color: #6b7280;
}

.cart-footer {
    background: #f3f4f6;
    padding: 0.75rem;
    text-align: center;
    font-size: 0.85rem;
    color: #6b7280;
    border-top: 1px solid #e5e7eb;
}

@media (max-width: 991px) {
    .cart-sticky {
        position: relative;
        top: 0;
        margin-top: 2rem;
    }
    
    .cart {
        max-height: none;
    }
}
</style>