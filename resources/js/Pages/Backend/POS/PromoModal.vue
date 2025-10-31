<template>
  <div v-if="show" class="modal fade show" tabindex="-1" aria-hidden="true" style="display: block;">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content rounded-4 border-0 shadow">
        <div class="modal-header border-0">
          <h5 class="modal-title fw-bold">Select a Promo</h5>

          <button
            class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-100 transition transform hover:scale-110"
            @click="$emit('close')" data-bs-dismiss="modal" aria-label="Close" title="Close">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24"
              stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="modal-body">
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading promotions...</p>
          </div>

          <div v-else>
            <div v-if="validPromos.length === 0"
              class="alert alert-light text-center dark:bg-gray-800 dark:text-white border-0">
              <p class="mb-2">No promotions available for items in your cart</p>
              <small class="text-muted">Add eligible items to see available promos</small>
            </div>

            <div class="promo-grid" v-else>
              <div v-for="promo in validPromos" :key="promo.id" class="promo-card"
                :class="{ 'selected': selectedPromo?.id === promo.id }" @click="selectPromo(promo)">
                <div class="promo-header d-flex align-items-center justify-content-between">
                  <span class="fw-bold fs-6">{{ promo.name }}</span>
                  <span class="badge bg-primary text-white small">{{ promo.type.toUpperCase() }}</span>
                </div>

                <p class="promo-desc">{{ promo.description }}</p>

                <!-- ✅ Show which cart items this promo applies to -->
                <div v-if="promo.menu_items && promo.menu_items.length > 0" class="promo-applies-to small mb-2">
                  <strong>Applies to:</strong>
                  <div class="d-flex flex-wrap gap-1 mt-1">
                    <span v-for="item in getMatchingCartItems(promo)" :key="item.id"
                      class="badge bg-success-subtle text-success" style="font-size: 12px;">
                      {{ item.title }} (×{{ item.qty }})
                    </span>
                  </div>
                </div>
                <div v-else class="promo-applies-to small mb-2">
                  <span class="badge bg-info-subtle text-info">Applies to all items</span>
                </div>

                <div class="promo-info small">
                  Valid: {{ formatDate(promo.start_date) }} → {{ formatDate(promo.end_date) }}
                </div>

                <div class="promo-info small">
                  Min: ${{ formatNumber(promo.min_purchase) }} | Max: ${{ promo.max_discount ?
                    formatNumber(promo.max_discount) : '—' }}
                </div>

                <div class="promo-discount d-flex justify-content-between align-items-center mt-2">
                  <span class="fw-semibold">You Save:</span>
                  <span class="fw-bold fs-5 text-danger">{{ formatCurrencySymbol(calculateDiscount(promo)) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer with Add / Clear buttons -->
        <div class="modal-footer border-0 justify-content-between">
          <button class="btn btn-secondary rounded-pill py-2 px-4" @click="$emit('close')">Close</button>

          <div v-if="selectedPromo" class="d-flex gap-2">
            <button class="btn btn-success rounded-pill px-4" @click="applyPromo">Add</button>
            <button class="btn btn-outline-danger rounded-pill" @click="clearPromo">Clear</button>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show"></div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useFormatters } from '@/composables/useFormatters'

const { formatMoney, formatCurrencySymbol, dateFmt } = useFormatters()

const props = defineProps({
  show: Boolean,
  promos: {
    type: Array,
    default: () => []
  },
  orderItems: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false }
});

const emit = defineEmits(['close', 'apply-promo', 'clear-promo']);

const selectedPromo = ref(null);

const selectPromo = (promo) => {
  selectedPromo.value = selectedPromo.value?.id === promo.id ? null : promo;
};

const applyPromo = () => {
  if (selectedPromo.value) {
    emit('apply-promo', selectedPromo.value);
  }
};

const clearPromo = () => {
  selectedPromo.value = null;
  emit('clear-promo');
};

// Helpers
const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString();
};

const formatNumber = (n) => {
  const num = parseFloat(n);
  if (isNaN(num)) return '0.00';
  return num.toFixed(2);
};

// ✅ Get cart item IDs
const cartItemIds = computed(() => {
  return props.orderItems.map(item => item.id);
});

// ✅ Check if promo applies to any cart item
const promoAppliesToCart = (promo) => {
  if (!promo.menu_items || promo.menu_items.length === 0) {
    // If no specific menu items, promo applies to all
    return true;
  }

  // Check if any menu item in promo matches cart items
  const promoMenuIds = promo.menu_items.map(item => item.id);
  return promoMenuIds.some(id => cartItemIds.value.includes(id));
};

// ✅ Get matching cart items for a promo
const getMatchingCartItems = (promo) => {
  if (!promo.menu_items || promo.menu_items.length === 0) {
    return props.orderItems;
  }

  const promoMenuIds = promo.menu_items.map(item => item.id);
  return props.orderItems.filter(item => promoMenuIds.includes(item.id));
};

// ✅ Calculate subtotal for specific promo (only matching items)
const getPromoSubtotal = (promo) => {
  const matchingItems = getMatchingCartItems(promo);
  return matchingItems.reduce((total, item) => {
    const unit = parseFloat(item.unit_price ?? item.price ?? 0) || 0;
    const qty = parseFloat(item.qty ?? 0) || 0;
    return total + unit * qty;
  }, 0);
};

// ✅ Calculate discount for each promo (only for matching items)
const calculateDiscount = (promo) => {
  const rawDiscount = parseFloat(promo.discount_amount ?? 0) || 0;
  const subtotal = getPromoSubtotal(promo); // Use promo-specific subtotal

  if (promo.min_purchase && subtotal < parseFloat(promo.min_purchase)) {
    return formatNumber(0);
  }

  if (promo.type === 'flat') return formatNumber(rawDiscount);

  if (promo.type === 'percent') {
    const discount = (subtotal * rawDiscount) / 100;
    const maxCap = parseFloat(promo.max_discount ?? 0) || 0;
    if (maxCap > 0 && discount > maxCap) return formatNumber(maxCap);
    return formatNumber(discount);
  }

  return formatNumber(0);
};

// ✅ Computed: Only show promos with discount > 0 AND applicable to cart
const validPromos = computed(() => {
  return props.promos.filter(promo => {
    // Must apply to at least one cart item
    if (!promoAppliesToCart(promo)) return false;

    // Must have discount > 0
    return parseFloat(calculateDiscount(promo)) > 0;
  });
});

</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1040;
}

.modal-dialog {
  z-index: 1050;
}

.dark .text-danger {
  color: red !important;
}

.promo-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 15px;
}

.promo-card {
  border: 1px solid #e0e0e0;
  border-radius: 1rem;
  padding: 15px;
  background: #fff;
  color: #333;
  transition: transform 0.2s, box-shadow 0.2s, background 0.2s, color 0.2s;
  cursor: pointer;
}

.promo-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.promo-card.selected {
  background: #28a745 !important;
  color: #fff !important;
  border: 2px solid #1e7e34 !important;
}

.promo-card.selected .promo-info,
.promo-card.selected .promo-desc,
.promo-card.selected .fw-semibold,
.promo-card.selected .promo-applies-to strong {
  color: #fff !important;
}

.promo-card.selected .badge {
  background: rgba(255, 255, 255, 0.2) !important;
  color: #fff !important;
}

.promo-card.selected .text-success {
  color: #fff !important;
}

.dark .promo-card {
  background: #181818;
  color: #f1f1f1;
  border-color: #333;
}

.dark .promo-card.selected {
  background: #1e7e34 !important;
  color: #fff !important;
}

.dark .alert-light {
  background-color: #181818 !important;
  color: #fff !important;
  border: none;
}

.modal .modal-footer .btn {
  padding: 0px !important;
}

.promo-applies-to {
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px solid #e0e0e0;
}

.dark .promo-applies-to {
  border-top-color: #333;
}

.badge.bg-success-subtle {
  background-color: rgba(25, 135, 84, 0.1) !important;
  color: #198754 !important;
  font-weight: 500;
}

.badge.bg-info-subtle {
  background-color: rgba(13, 202, 240, 0.1) !important;
  color: #0dcaf0 !important;
  font-weight: 500;
}

.promo-card.selected .badge.bg-success-subtle,
.promo-card.selected .badge.bg-info-subtle {
  background-color: rgba(255, 255, 255, 0.25) !important;
  color: #fff !important;
}
</style>