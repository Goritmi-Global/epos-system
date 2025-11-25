<template>
  <div v-if="show" class="modal fade show" tabindex="-1" aria-hidden="true" style="display: block;">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content rounded-4 border-0 shadow">
        <div class="modal-header border-0">
          <div class="d-flex align-items-center gap-3">
            <h5 class="modal-title fw-bold mb-0">Select Discounts</h5>
            <span v-if="selectedDiscounts.length > 0" class="badge bg-primary">
              {{ selectedDiscounts.length }} Selected
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
          <!-- Filters Section -->
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
                  placeholder="Search discounts by name or description..."
                  @input="applyFilters"
                >
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label small fw-semibold">Discount Type</label>
                <select v-model="filters.type" @change="applyFilters" class="form-select form-select-sm">
                  <option value="">All Types</option>
                  <option value="flat">Flat Discount</option>
                  <option value="percent">Percentage</option>
                </select>
              </div>

              <div class="col-md-4">
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

              <div class="col-md-4">
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
            </div>

            <div v-if="hasActiveFilters" class="mt-3 d-flex align-items-center justify-content-between">
              <div class="d-flex flex-wrap gap-2">
                <span class="badge bg-secondary">{{ filteredDiscounts.length }} of {{ discounts.length }} discounts</span>
                <span v-if="searchQuery" class="badge bg-info">Search: "{{ searchQuery }}"</span>
                <span v-if="filters.type" class="badge bg-primary">Type: {{ filters.type }}</span>
                <span v-if="filters.maxMinPurchase" class="badge bg-warning">Max Min: ${{ filters.maxMinPurchase }}</span>
                <span v-if="filters.minDiscount" class="badge bg-success">Min Discount: ${{ filters.minDiscount }}</span>
              </div>
              <button @click="clearFilters" class="btn btn-sm btn-outline-secondary">Clear Filters</button>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading discounts...</p>
          </div>

          <!-- Discount Grid -->
          <div v-else>
            <div v-if="filteredDiscounts.length === 0"
              class="alert alert-light text-center dark:bg-gray-800 dark:text-white border-0">
              <p class="mb-2">{{ searchQuery || hasActiveFilters ? 'No discounts match your filters' : 'No discounts available' }}</p>
              <small class="text-muted">{{ hasActiveFilters ? 'Try adjusting your filters' : 'Check back later for available discounts' }}</small>
            </div>

            <div class="discount-grid" v-else>
              <div v-for="discount in filteredDiscounts" :key="discount.id" 
                class="discount-card"
                :class="{ 
                  'selected': isDiscountSelected(discount),
                  'already-applied': isDiscountAlreadyApplied(discount),
                  'rejected': isDiscountRejected(discount)
                }" 
                @click="toggleDiscount(discount)">
                              
                              <!-- Selection Checkbox -->
                              <div class="discount-checkbox">
                <input 
                  type="checkbox" 
                  :checked="isDiscountSelected(discount)"
                  :disabled="isDiscountAlreadyApplied(discount) || isDiscountRejected(discount)"
                  @click.stop="toggleDiscount(discount)"
                  class="form-check-input"
                >
              </div>

                <div class="discount-header d-flex align-items-center justify-content-between">
                  <span class="fw-bold fs-6">{{ discount.name }}</span>
                  <div class="d-flex gap-2 align-items-center">
                  <span class="badge bg-primary text-white small">{{ discount.type.toUpperCase() }}</span>
                  <span v-if="isDiscountAlreadyApplied(discount)" class="badge bg-success text-white small">
                    ✓ APPLIED
                  </span>
                  <span v-if="isDiscountRejected(discount)" class="badge bg-danger text-white small">
                    ✗ REJECTED
                  </span>
                </div>
                </div>

                <p class="discount-desc">{{ discount.description || 'Special discount offer' }}</p>

                <div class="discount-applies-to small mb-2">
                  <span class="badge bg-info-subtle text-info">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                      <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                      <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                    </svg>
                    Applies to all cart items
                  </span>
                  <span class="badge bg-success-subtle text-success ms-1">
                    {{ orderItems.length }} item(s) in cart
                  </span>
                </div>

                <div class="discount-info small">
                  Valid: {{ formatDate(discount.start_date) }} → {{ formatDate(discount.end_date) }}
                </div>

                <div class="discount-info small">
                  Min: ${{ formatNumber(discount.min_purchase) }} | Max: ${{ discount.max_discount ?
                    formatNumber(discount.max_discount) : '—' }}
                </div>

                <div class="discount-amount d-flex justify-content-between align-items-center mt-2">
                  <span class="fw-semibold">You Save:</span>
                  <span class="fw-bold fs-5 text-danger">{{ formatCurrencySymbol(calculateDiscount(discount)) }}</span>
                </div>

                <div v-if="!isDiscountEligible(discount)" class="alert alert-warning p-2 mt-2 mb-0 small">
                  {{ getIneligibilityReason(discount) }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer border-0 justify-content-between">
          <button class="btn btn-secondary rounded-pill py-2 px-4" @click="$emit('close')">Close</button>

          <div v-if="selectedDiscounts.length > 0" class="d-flex gap-2 align-items-center">
            <div class="text-end me-3">
              <small class="d-block text-muted">
                {{ selectedDiscounts.length }} Discount(s) • {{ orderItems.length }} Item(s)
              </small>
              <strong class="text-success fs-5">{{ formatCurrencySymbol(totalDiscount) }}</strong>
            </div>
            <button 
              class="btn btn-success rounded-pill px-4" 
              @click="applyDiscounts"
              :disabled="isApplying"
            >
              <span v-if="isApplying" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
              {{ isApplying ? 'Applying...' : `Apply (${selectedDiscounts.length})` }}
            </button>
            <button class="btn btn-outline-danger rounded-pill" @click="clearDiscounts">Clear</button>
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
  discounts: {
    type: Array,
    default: () => []
  },
  orderItems: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  appliedDiscounts: { type: Array, default: () => [] },
  rejectedDiscounts: { type: Array, default: () => [] }
});

const emit = defineEmits(['close', 'apply-discount', 'clear-discount']);

const selectedDiscounts = ref([]);
const searchQuery = ref('');
const filters = ref({
  type: '',
  maxMinPurchase: null,
  minDiscount: null
});

// ✅ NEW: Check if discount is already applied to current order
const isDiscountAlreadyApplied = (discount) => {
  return props.appliedDiscounts.some(d => d.id === discount.id);
};

// ✅ NEW: Check if discount was rejected
const isDiscountRejected = (discount) => {
  return props.rejectedDiscounts.some(d => d.id === discount.id);
};

// ✅ UPDATED: Toggle discount selection (prevent re-selection of applied discounts)
const toggleDiscount = (discount) => {
  // Prevent selecting already applied or rejected discounts
  if (isDiscountAlreadyApplied(discount) || isDiscountRejected(discount)) {
    return;
  }
  
  const index = selectedDiscounts.value.findIndex(d => d.id === discount.id);
  
  if (index >= 0) {
    selectedDiscounts.value.splice(index, 1);
  } else {
    selectedDiscounts.value.push(discount);
  }
};

// Check if discount is selected
const isDiscountSelected = (discount) => {
  return selectedDiscounts.value.some(d => d.id === discount.id);
};

const isApplying = ref(false);

// Apply discounts
const applyDiscounts = async () => {
  if (isApplying.value) {
    return;
  }

  const eligibleDiscounts = selectedDiscounts.value.filter(discount => isDiscountEligible(discount));
  
  if (eligibleDiscounts.length === 0) {
    return;
  }

  try {
    isApplying.value = true;

    const discountsWithAmounts = eligibleDiscounts.map(discount => {
      const discountAmount = parseFloat(calculateDiscount(discount));
      
      return {
        ...discount,
        applied_discount: discountAmount,
        applied_to_items: props.orderItems.map(item => item.id),
        applied_subtotal: getCartSubtotal(),
        discount_id: discount.id
      };
    });

    emit('apply-discount', discountsWithAmounts);
    
  } finally {
    selectedDiscounts.value = [];
    setTimeout(() => {
      isApplying.value = false;
    }, 500);
  }
};

// Clear all selections
const clearDiscounts = () => {
  selectedDiscounts.value = [];
  emit('clear-discount');
};

const clearFilters = () => {
  searchQuery.value = '';
  filters.value = {
    type: '',
    maxMinPurchase: null,
    minDiscount: null
  };
};

const hasActiveFilters = computed(() => {
  return searchQuery.value || 
         filters.value.type || 
         filters.value.maxMinPurchase !== null || 
         filters.value.minDiscount !== null;
});

// Calculate total discount from all selected discounts
const totalDiscount = computed(() => {
  return selectedDiscounts.value.reduce((sum, discount) => {
    return sum + parseFloat(calculateDiscount(discount));
  }, 0);
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

// Get cart subtotal (applies to all items)
const getCartSubtotal = () => {
  return props.orderItems.reduce((total, item) => {
    const unit = parseFloat(item.unit_price ?? item.price ?? 0) || 0;
    const qty = parseFloat(item.qty ?? 0) || 0;
    return total + unit * qty;
  }, 0);
};

const calculateDiscount = (discount) => {
  const rawDiscount = parseFloat(discount.discount_amount ?? 0) || 0;
  const subtotal = getCartSubtotal();

  if (discount.min_purchase && subtotal < parseFloat(discount.min_purchase)) {
    return formatNumber(0);
  }

  if (discount.type === 'flat') return formatNumber(rawDiscount);

  if (discount.type === 'percent') {
    const discountAmt = (subtotal * rawDiscount) / 100;
    const maxCap = parseFloat(discount.max_discount ?? 0) || 0;
    if (maxCap > 0 && discountAmt > maxCap) return formatNumber(maxCap);
    return formatNumber(discountAmt);
  }

  return formatNumber(0);
};

const isDiscountEligible = (discount) => {
  if (props.orderItems.length === 0) return false;

  const subtotal = getCartSubtotal();
  const minPurchase = parseFloat(discount.min_purchase || 0);
  
  return subtotal >= minPurchase;
};

const getIneligibilityReason = (discount) => {
  if (props.orderItems.length === 0) {
    return 'Add items to cart to use this discount';
  }

  const subtotal = getCartSubtotal();
  const minPurchase = parseFloat(discount.min_purchase || 0);
  
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

const filteredDiscounts = computed(() => {
  let result = [...props.discounts];

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    result = result.filter(discount => 
      discount.name.toLowerCase().includes(query) ||
      (discount.description && discount.description.toLowerCase().includes(query))
    );
  }

  if (filters.value.type) {
    result = result.filter(discount => discount.type === filters.value.type);
  }

  if (filters.value.maxMinPurchase !== null && filters.value.maxMinPurchase > 0) {
    result = result.filter(discount => {
      const minPurchase = parseFloat(discount.min_purchase || 0);
      return minPurchase <= filters.value.maxMinPurchase;
    });
  }

  if (filters.value.minDiscount !== null && filters.value.minDiscount > 0) {
    result = result.filter(discount => {
      const discountAmt = parseFloat(calculateDiscount(discount));
      return discountAmt >= filters.value.minDiscount;
    });
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

.discount-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 15px;
  max-height: 500px;
  overflow-y: auto;
  padding-right: 5px;
}

.discount-card {
  border: 1px solid #e0e0e0;
  border-radius: 1rem;
  padding: 15px;
  background: #fff;
  color: #333;
  transition: transform 0.2s, box-shadow 0.2s, background 0.2s, color 0.2s;
  cursor: pointer;
  position: relative;
  padding-left: 45px;
}

.discount-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.discount-card.selected {
  background: #007bff !important;
  color: #fff !important;
  border: 2px solid #0056b3 !important;
}

.discount-card.selected .discount-info,
.discount-card.selected .discount-desc,
.discount-card.selected .fw-semibold,
.discount-card.selected .discount-applies-to strong {
  color: #fff !important;
}

.discount-card.selected .badge {
  background: rgba(255, 255, 255, 0.2) !important;
  color: #fff !important;
}

.discount-card.selected .text-success {
  color: #fff !important;
}

/* ✅ NEW: Already Applied Styles */
.discount-card.already-applied {
  opacity: 0.6;
  cursor: not-allowed;
  background-color: #e8f5e9 !important;
  border-color: #4caf50 !important;
}

.dark .discount-card.already-applied {
  background-color: #1b5e20 !important;
  border-color: #4caf50 !important;
}

.discount-card.already-applied:hover {
  transform: none;
  box-shadow: none;
}

.discount-card.already-applied .discount-checkbox .form-check-input {
  cursor: not-allowed;
}

.discount-card.already-applied .badge.bg-success {
  background-color: #4caf50 !important;
}

.dark .discount-card {
  background: #181818;
  color: #f1f1f1;
  border-color: #333;
}

.dark .discount-card.selected {
  background: #0056b3 !important;
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

.discount-applies-to {
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px solid #e0e0e0;
}

.dark .discount-applies-to {
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

.discount-card.selected .badge.bg-success-subtle,
.discount-card.selected .badge.bg-info-subtle,
.discount-card.selected .badge.bg-warning-subtle {
  background-color: rgba(255, 255, 255, 0.25) !important;
  color: #fff !important;
}

.discount-grid::-webkit-scrollbar {
  width: 8px;
}

.discount-grid::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.discount-grid::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 10px;
}

.discount-grid::-webkit-scrollbar-thumb:hover {
  background: #555;
}

.dark .discount-grid::-webkit-scrollbar-track {
  background: #222;
}

.dark .discount-grid::-webkit-scrollbar-thumb {
  background: #444;
}

.discount-checkbox {
  position: absolute;
  top: 10px;
  left: 10px;
  z-index: 10;
}

.discount-checkbox .form-check-input {
  width: 20px;
  height: 20px;
  cursor: pointer;
  border: 2px solid #6c757d;
}

.discount-card.selected .discount-checkbox .form-check-input {
  background-color: #fff;
  border-color: #fff;
}


/* Rejected discount styles */
.discount-card.rejected {
  opacity: 0.5;
  cursor: not-allowed;
  background-color: #ffebee !important;
  border-color: #f44336 !important;
}

.dark .discount-card.rejected {
  background-color: #5d1f1f !important;
  border-color: #f44336 !important;
}

.discount-card.rejected:hover {
  transform: none;
  box-shadow: none;
}

.discount-card.rejected .discount-checkbox .form-check-input {
  cursor: not-allowed;
}

.discount-card.rejected .badge.bg-danger {
  background-color: #f44336 !important;
}
</style>