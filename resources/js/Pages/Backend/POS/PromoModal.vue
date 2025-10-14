<template>
  <div v-if="show" class="modal fade show" tabindex="-1" aria-hidden="true" style="display: block;">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content rounded-4 border-0 shadow">
        <div class="modal-header border-0">
          <h5 class="modal-title fw-bold">Select a Promo</h5>

          <button
            class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
            @click="$emit('close')"
            data-bs-dismiss="modal"
            aria-label="Close"
            title="Close"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
              viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="modal-body">
          <!-- ✅ Loader while fetching -->
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading promotions...</p>
          </div>

          <!-- ✅ Show promos after loading -->
          <div v-else>
            <div v-if="filteredPromos.length === 0" class="alert alert-light text-center">
              No promotions available today
            </div>

            <div class="promo-grid">
              <div v-for="promo in filteredPromos" :key="promo.id" class="promo-card">
                <div class="promo-header d-flex align-items-center justify-content-between">
                  <span class="fw-bold fs-6">{{ promo.name }}</span>
                  <span class="badge bg-primary text-white small">{{ promo.type.toUpperCase() }}</span>
                </div>
                <p class="promo-desc text-muted">{{ promo.description }}</p>
                <div class="promo-info small text-muted">
                  Min: ${{ promo.min_purchase }} | Max: ${{ promo.max_discount }}
                </div>
                <div class="promo-discount text-success fw-bold fs-5 mt-2">
                  -${{ calculateDiscount(promo) }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer border-0">
          <button class="btn btn-secondary rounded-pill py-2" @click="$emit('close')">Close</button>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show"></div>
  </div>
</template>


<script setup>
import { computed } from 'vue';

const props = defineProps({
  show: Boolean,
  promos: {
    type: Array,
    default: () => []
  },
  subtotal: {
    type: Number,
    default: 0
  },
  loading: {
    type: Boolean,
    default: false
  }
});

defineEmits(['close']);

// Calculate discount
const calculateDiscount = (promo) => {
  if (promo.type === 'flat') return parseFloat(promo.max_discount).toFixed(2);
  if (promo.type === 'percentage') return ((props.subtotal * promo.max_discount) / 100).toFixed(2);
  return 0;
};

// Filter promos starting today
const filteredPromos = computed(() => {
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  return props.promos.filter(promo => {
    const promoDate = new Date(promo.start_date);
    promoDate.setHours(0, 0, 0, 0);
    return promoDate.getTime() === today.getTime();
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

.promo-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(45%, 1fr));
  gap: 15px;
}

.promo-card {
  border: 1px solid #e0e0e0;
  border-radius: 1rem;
  padding: 15px;
  background: #ffffff;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  transition: transform 0.2s, box-shadow 0.2s;
  cursor: pointer;
}

.dark .promo-card {
  background: #181818 !important;
  color: #fff !important;

}

.promo-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.dark .alert-light{
  background-color: #181818 !important;
  color: #fff !important;
  border: none;
}

.promo-header {
  margin-bottom: 5px;
}

.promo-desc {
  font-size: 0.9rem;
  margin: 5px 0;
}

.promo-info {
  font-size: 0.8rem;
  color: #6c757d;
}

.promo-discount {
  text-align: right;
}
</style>
