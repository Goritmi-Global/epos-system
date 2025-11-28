<template>
  <div v-if="show" class="modal fade show" tabindex="-1" aria-hidden="true" style="display: block;">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content rounded-4 border-0 shadow">
        <div class="modal-header border-0">
          <div class="d-flex align-items-center gap-3">
            <h5 class="modal-title fw-bold mb-0">Select Promotions</h5>
            <span v-if="selectedPromos.length > 0" class="badge bg-primary">
              {{ selectedPromos.length }} Selected
            </span>
          </div>

          <button
            class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-100 transition transform hover:scale-110"
            @click="$emit('close')" aria-label="Close" title="Close">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24"
              stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="modal-body">
          <!-- Filters Section (same as before) -->
          <div class="filters-section mb-4">
            <div class="mb-3">
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                  </svg>
                </span>
                <input 
                  v-model="searchQuery" 
                  type="text" 
                  class="form-control border-start-0" 
                  placeholder="Search promos by name or description..."
                  @input="applyFilters"
                >
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label small fw-semibold">Promo Type</label>
                <select v-model="filters.type" @change="applyFilters" class="form-select form-select-sm">
                  <option value="">All Types</option>
                  <option value="flat">Flat Discount</option>
                  <option value="percent">Percentage</option>
                </select>
              </div>

              <div class="col-md-3">
                <label class="form-label small fw-semibold">Max Min Purchase</label>
                <input 
                  v-model.number="filters.maxMinPurchase" 
                  type="number" 
                  class="form-control form-control-sm" 
                  placeholder="e.g., 50"
                  step="0.01"
                  @input="applyFilters"
                >
              </div>

              <div class="col-md-3">
                <label class="form-label small fw-semibold">Min Discount</label>
                <input 
                  v-model.number="filters.minDiscount" 
                  type="number" 
                  class="form-control form-control-sm" 
                  placeholder="e.g., 5"
                  step="0.01"
                  @input="applyFilters"
                >
              </div>

              <div class="col-md-3">
                <label class="form-label small fw-semibold">Applies To</label>
                <select v-model="filters.applicability" @change="applyFilters" class="form-select form-select-sm">
                  <option value="">All Promos</option>
                  <option value="cart">Only My Cart Items</option>
                  <option value="all">All Items</option>
                </select>
              </div>
            </div>

            <div v-if="hasActiveFilters" class="mt-3 d-flex align-items-center justify-content-between">
              <div class="d-flex flex-wrap gap-2">
                <span class="badge bg-secondary">{{ filteredPromos.length }} of {{ promos.length }} promos</span>
                <span v-if="searchQuery" class="badge bg-info">Search: "{{ searchQuery }}"</span>
                <span v-if="filters.type" class="badge bg-primary">Type: {{ filters.type }}</span>
                <span v-if="filters.maxMinPurchase" class="badge bg-warning">Max Min: ${{ filters.maxMinPurchase }}</span>
                <span v-if="filters.minDiscount" class="badge bg-success">Min Discount: ${{ filters.minDiscount }}</span>
                <span v-if="filters.applicability" class="badge bg-dark">{{ filters.applicability === 'cart' ? 'Cart Items' : 'All Items' }}</span>
              </div>
              <button @click="clearFilters" class="btn btn-sm btn-outline-secondary">Clear Filters</button>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading promotions...</p>
          </div>

          <!-- Promo Grid -->
          <div v-else>
            <div v-if="filteredPromos.length === 0"
              class="alert alert-light text-center dark:bg-gray-800 dark:text-white border-0">
              <p class="mb-2">{{ searchQuery || hasActiveFilters ? 'No promotions match your filters' : 'No promotions available' }}</p>
              <small class="text-muted">{{ hasActiveFilters ? 'Try adjusting your filters' : 'Add eligible items to see available promos' }}</small>
            </div>

            <div class="promo-grid" v-else>
              <div v-for="promo in filteredPromos" :key="promo.id" 
                class="promo-card"
                :class="{ 'selected': isPromoSelected(promo) }" 
                @click="togglePromo(promo)">
                
                <!-- Selection Checkbox -->
                <div class="promo-checkbox">
                  <input 
                    type="checkbox" 
                    :checked="isPromoSelected(promo)"
                    @click.stop="togglePromo(promo)"
                    class="form-check-input"
                  >
                </div>

                <div class="promo-header d-flex align-items-center justify-content-between">
                  <span class="fw-bold fs-6">{{ promo.name }}</span>
                  <span class="badge bg-primary text-white small">{{ promo.type.toUpperCase() }}</span>
                </div>

                <p class="promo-desc">{{ promo.description || 'Special discount offer' }}</p>

                <div v-if="promo.menu_items && promo.menu_items.length > 0" class="promo-applies-to small mb-2">
                  <strong>Applies to:</strong>
                  <div class="d-flex flex-wrap gap-1 mt-1">
                    <span v-for="item in getMatchingCartItems(promo)" :key="item.id"
                      class="badge bg-success-subtle text-success" style="font-size: 11px;">
                      {{ item.title }} (×{{ item.qty }})
                    </span>
                    <span v-if="getMatchingCartItems(promo).length === 0" 
                      class="badge bg-warning-subtle text-warning" style="font-size: 11px;">
                      No cart items match
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

                <div v-if="!isPromoEligible(promo)" class="alert alert-warning p-2 mt-2 mb-0 small">
                  {{ getIneligibilityReason(promo) }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer border-0 justify-content-between">
          <button class="btn btn-secondary rounded-pill py-2 px-4" @click="$emit('close')">Close</button>

          <div v-if="selectedPromos.length > 0" class="d-flex gap-2 align-items-center">
            <div class="text-end me-3">
              <small class="d-block text-muted">
                {{ selectedPromos.length }} Promo(s) • {{ affectedItemsCount }} Item(s)
              </small>
              <strong class="text-success fs-5">{{ formatCurrencySymbol(totalDiscount) }}</strong>
            </div>
            <button 
              class="btn btn-success rounded-pill px-4" 
              @click="applyPromos"
            >
              Apply ({{ selectedPromos.length }})
            </button>
            <button class="btn btn-outline-danger rounded-pill" @click="clearPromos">Clear</button>
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
const selectedPromos = ref([]);
const searchQuery = ref('');
const filters = ref({
  type: '',
  maxMinPurchase: null,
  minDiscount: null,
  applicability: ''
});

const togglePromo = (promo) => {
  const index = selectedPromos.value.findIndex(p => p.id === promo.id);
  
  if (index >= 0) {
    selectedPromos.value.splice(index, 1);
  } else {
    selectedPromos.value.push(promo);
  }
};
const isPromoSelected = (promo) => {
  return selectedPromos.value.some(p => p.id === promo.id);
};
const applyPromos = () => {
  const eligiblePromos = selectedPromos.value.filter(promo => isPromoEligible(promo));
  
  if (eligiblePromos.length === 0) {
    return;
  }
  const promosWithDiscounts = eligiblePromos.map(promo => {
    const discountAmount = parseFloat(calculateDiscount(promo));
    const matchingItems = getMatchingCartItems(promo);
    
    return {
      ...promo,
      applied_discount: discountAmount,
      applied_to_items: matchingItems.map(item => item.id),
      applied_subtotal: getPromoSubtotal(promo)
    };
  });

  emit('apply-promo', promosWithDiscounts);
};
const clearPromos = () => {
  selectedPromos.value = [];
  emit('clear-promo');
};

const clearFilters = () => {
  searchQuery.value = '';
  filters.value = {
    type: '',
    maxMinPurchase: null,
    minDiscount: null,
    applicability: ''
  };
};

const hasActiveFilters = computed(() => {
  return searchQuery.value || 
         filters.value.type || 
         filters.value.maxMinPurchase !== null || 
         filters.value.minDiscount !== null || 
         filters.value.applicability;
});
const totalDiscount = computed(() => {
  return selectedPromos.value.reduce((sum, promo) => {
    return sum + parseFloat(calculateDiscount(promo));
  }, 0);
});
const affectedItemsCount = computed(() => {
  const uniqueItemIds = new Set();
  
  selectedPromos.value.forEach(promo => {
    const matchingItems = getMatchingCartItems(promo);
    matchingItems.forEach(item => uniqueItemIds.add(item.id));
  });
  
  return uniqueItemIds.size;
});

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

const cartItemIds = computed(() => {
  return props.orderItems.map(item => item.id);
});

const promoAppliesToCart = (promo) => {
  if (!promo.menu_items || promo.menu_items.length === 0) {
    return true;
  }

  const promoMenuIds = promo.menu_items.map(item => item.id);
  return promoMenuIds.some(id => cartItemIds.value.includes(id));
};

const getMatchingCartItems = (promo) => {
  if (!promo.menu_items || promo.menu_items.length === 0) {
    return props.orderItems;
  }

  const promoMenuIds = promo.menu_items.map(item => item.id);
  return props.orderItems.filter(item => promoMenuIds.includes(item.id));
};

const getPromoSubtotal = (promo) => {
  const matchingItems = getMatchingCartItems(promo);
  return matchingItems.reduce((total, item) => {
    const unit = parseFloat(item.unit_price ?? item.price ?? 0) || 0;
    const qty = parseFloat(item.qty ?? 0) || 0;
    return total + unit * qty;
  }, 0);
};

const calculateDiscount = (promo) => {
  const rawDiscount = parseFloat(promo.discount_amount ?? 0) || 0;
  const subtotal = getPromoSubtotal(promo);

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

const isPromoEligible = (promo) => {
  const matchingItems = getMatchingCartItems(promo);
  if (matchingItems.length === 0) return false;

  const subtotal = getPromoSubtotal(promo);
  const minPurchase = parseFloat(promo.min_purchase || 0);
  
  return subtotal >= minPurchase;
};

const getIneligibilityReason = (promo) => {
  const matchingItems = getMatchingCartItems(promo);
  if (matchingItems.length === 0) {
    return 'Add eligible items to cart';
  }

  const subtotal = getPromoSubtotal(promo);
  const minPurchase = parseFloat(promo.min_purchase || 0);
  
  if (subtotal < minPurchase) {
    const remaining = minPurchase - subtotal;
    return `Need $${formatNumber(remaining)} more for min purchase`;
  }

  return '';
};

// Filtering Logic
const applyFilters = () => {
  filters.value = { ...filters.value };
};

const filteredPromos = computed(() => {
  let result = [...props.promos];

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    result = result.filter(promo => 
      promo.name.toLowerCase().includes(query) ||
      (promo.description && promo.description.toLowerCase().includes(query))
    );
  }

  if (filters.value.type) {
    result = result.filter(promo => promo.type === filters.value.type);
  }

  if (filters.value.maxMinPurchase !== null && filters.value.maxMinPurchase > 0) {
    result = result.filter(promo => {
      const minPurchase = parseFloat(promo.min_purchase || 0);
      return minPurchase <= filters.value.maxMinPurchase;
    });
  }

  if (filters.value.minDiscount !== null && filters.value.minDiscount > 0) {
    result = result.filter(promo => {
      const discount = parseFloat(calculateDiscount(promo));
      return discount >= filters.value.minDiscount;
    });
  }

  if (filters.value.applicability === 'cart') {
    result = result.filter(promo => promoAppliesToCart(promo) && getMatchingCartItems(promo).length > 0);
  } else if (filters.value.applicability === 'all') {
    result = result.filter(promo => !promo.menu_items || promo.menu_items.length === 0);
  }

  return result;
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

.filters-section {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 0.5rem;
  margin-bottom: 1rem;
}

.dark .filters-section {
  background: #1a1a1a;
}

.dark .form-control,
.dark .form-select {
  background: #222;
  color: #fff;
  border-color: #444;
}

.dark .input-group-text {
  background: #222 !important;
  color: #fff;
  border-color: #444;
}

.dark .text-danger {
  color: red !important;
}

.promo-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 15px;
  max-height: 500px;
  overflow-y: auto;
  padding-right: 5px;
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
  padding: 0.5rem 1rem !important;
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

.badge.bg-warning-subtle {
  background-color: rgba(255, 193, 7, 0.1) !important;
  color: #ffc107 !important;
  font-weight: 500;
}

.promo-card.selected .badge.bg-success-subtle,
.promo-card.selected .badge.bg-info-subtle,
.promo-card.selected .badge.bg-warning-subtle {
  background-color: rgba(255, 255, 255, 0.25) !important;
  color: #fff !important;
}

.promo-grid::-webkit-scrollbar {
  width: 8px;
}

.promo-grid::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.promo-grid::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 10px;
}

.promo-grid::-webkit-scrollbar-thumb:hover {
  background: #555;
}

.dark .promo-grid::-webkit-scrollbar-track {
  background: #222;
}

.dark .promo-grid::-webkit-scrollbar-thumb {
  background: #444;
}

.promo-checkbox {
  position: absolute;
  top: 10px;
  left: 10px;
  z-index: 10;
}

.promo-checkbox .form-check-input {
  width: 20px;
  height: 20px;
  cursor: pointer;
  border: 2px solid #6c757d;
}

.promo-card.selected .promo-checkbox .form-check-input {
  background-color: #fff;
  border-color: #fff;
}

.promo-card {
  position: relative;
  padding: 15px 15px 15px 45px; /* Add left padding for checkbox */
}
</style>