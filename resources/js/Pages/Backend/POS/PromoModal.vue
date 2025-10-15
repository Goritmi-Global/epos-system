<template>
  <div v-if="show" class="modal fade show" tabindex="-1" aria-hidden="true" style="display: block;">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content rounded-4 border-0 shadow">
        <div class="modal-header border-0">
          <h5 class="modal-title fw-bold">Select a Promo</h5>

          <button
            class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition transform hover:scale-110"
            @click="$emit('close')"
            data-bs-dismiss="modal"
            aria-label="Close"
            title="Close"
          >
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
            <div v-if="promos.length === 0" class="alert alert-light text-center dark:bg-gray-800 dark:text-white border-0">
              No promotions available today
            </div>

            <div class="promo-grid" v-else>
              <div
                v-for="promo in promos"
                :key="promo.id"
                class="promo-card"
                :class="{ 'selected': selectedPromo?.id === promo.id }"
                @click="selectPromo(promo)"
              >
                <div class="promo-header d-flex align-items-center justify-content-between">
                  <span class="fw-bold fs-6">{{ promo.name }}</span>
                  <span class="badge bg-primary text-white small">{{ promo.type.toUpperCase() }}</span>
                </div>

                <p class="promo-desc">{{ promo.description }}</p>

                <div class="promo-info small">
                  Valid: {{ formatDate(promo.start_date) }} → {{ formatDate(promo.end_date) }}
                </div>

                <div class="promo-info small">
                  Min: ${{ formatNumber(promo.min_purchase) }} | Max: ${{ promo.max_discount ? formatNumber(promo.max_discount) : '—' }}
                </div>

                <div class="promo-discount d-flex justify-content-between align-items-center mt-2">
                  <span class="fw-semibold">Discount Amount:</span>
                  <span class="fw-bold fs-5 text-success">-${{ calculateDiscount(promo) }}</span>
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
import { ref } from 'vue';

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

// Get cart subtotal
const getCartSubtotal = () => {
  return props.orderItems.reduce((total, item) => {
    const unit = parseFloat(item.unit_price ?? item.price ?? 0) || 0;
    const qty = parseFloat(item.qty ?? 0) || 0;
    return total + unit * qty;
  }, 0);
};

// Calculate discount
const calculateDiscount = (promo) => {
  const rawDiscount = parseFloat(promo.discount_amount ?? 0) || 0;
  const subtotal = getCartSubtotal();

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
.promo-card.selected .fw-semibold {
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

.modal .modal-footer .btn{
  padding: 0px !important;
}
</style>
