<template>
  <div v-if="show" class="modal fade show" tabindex="-1" aria-hidden="true" style="display: block;">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-4 border-0 shadow-lg">
        <div class="modal-header border-0 bg-warning bg-opacity-10">
          <div class="d-flex align-items-center gap-3">
            <div class="spinner-border text-warning" role="status" style="width: 24px; height: 24px;">
              <span class="visually-hidden">Loading...</span>
            </div>
            <h5 class="modal-title fw-bold mb-0">Waiting for Approval</h5>
          </div>
          <button
            class="btn-close"
            @click="$emit('cancel')"
            aria-label="Close">
          </button>
        </div>

        <div class="modal-body">
          <div class="alert alert-info border-0 rounded-3 mb-3">
            <i class="bi bi-info-circle-fill me-2"></i>
            Your discount request has been sent to the Super Admin. Please wait for approval.
          </div>

          <div v-if="pendingApprovals && pendingApprovals.length > 0" class="list-group list-group-flush">
            <div
              v-for="approval in pendingApprovals"
              :key="approval.id"
              class="list-group-item border-0 px-0"
            >
              <div class="d-flex align-items-center justify-content-between">
                <div class="flex-grow-1">
                  <div class="fw-semibold">{{ approval.discount?.name || 'Discount' }}</div>
                  <small class="text-muted">
                    Amount: <span class="fw-semibold text-success">{{ formatCurrency(approval.discount_amount) }}</span>
                  </small>
                </div>
                <div class="spinner-border spinner-border-sm text-warning" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-3 p-3 bg-light rounded-3">
            <div class="d-flex align-items-center gap-2 text-muted small">
              <i class="bi bi-clock"></i>
              <span>Requested: {{ formatTime(pendingApprovals[0]?.requested_at) }}</span>
            </div>
            <div class="d-flex align-items-center gap-2 text-muted small mt-1">
              <i class="bi bi-person"></i>
              <span>Requested by: {{ pendingApprovals[0]?.requested_by?.name || 'You' }}</span>
            </div>
          </div>
        </div>

        <div class="modal-footer border-0">
          <button class="btn btn-secondary" @click="$emit('cancel')">
            Cancel Request
          </button>
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
  pendingApprovals: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['cancel']);

const formatCurrency = (amount) => {
  return `£${parseFloat(amount || 0).toFixed(2)}`;
};

const formatTime = (timestamp) => {
  if (!timestamp) return '-';
  const date = new Date(timestamp);
  return date.toLocaleTimeString('en-GB', {
    hour: '2-digit',
    minute: '2-digit'
  });
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

.spinner-border {
  border-width: 3px;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.list-group-item {
  animation: pulse 2s ease-in-out infinite;
}
</style>