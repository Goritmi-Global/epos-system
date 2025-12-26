<script setup>
import { computed } from "vue";
import { useFormatters } from '@/composables/useFormatters';

const { formatCurrencySymbol } = useFormatters();

const props = defineProps({
    show: Boolean,
    pendingOrders: {
        type: Array,
        default: () => []
    },
    loading: Boolean
});

const emit = defineEmits(["close", "pay-now", "reject"]);

const formatRelativeTime = (date) => {
    const now = new Date();
    const held = new Date(date);
    const diffMs = now - held;
    const diffMins = Math.floor(diffMs / 60000);

    if (diffMins < 1) return 'just now';
    if (diffMins < 60) return `${diffMins} min${diffMins > 1 ? 's' : ''} ago`;

    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;

    const diffDays = Math.floor(diffHours / 24);
    return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
};

const filteredOrders = computed(() => {
    return props.pendingOrders.filter(order => order.type === 'unpaid');
});

const getStatusBadgeClass = (order) => {
    if (order.payment_status === 'partial') return 'bg-warning';
    return 'bg-danger';
};

const getStatusText = (order) => {
    if (order.payment_status === 'partial') return 'Partially Paid';
    return 'Payment Pending';
};

const confirmReject = (order) => {
    if (confirm(`Are you sure you want to reject order for "${order.customer_name || 'Walk In'}"? This action cannot be undone.`)) {
        emit('reject', order);
    }
};
</script>

<template>
    <div class="modal fade" :class="{ show: show, 'd-block': show }" :style="{ display: show ? 'block' : 'none' }"
        tabindex="-1" @click.self="$emit('close')">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <!-- HEADER -->
                <div class="modal-header border-0 bg-info bg-opacity-10">
                    <div>
                        <h5 class="fw-bold mb-0">
                            <i class="bi bi-credit-card-fill text-info me-2"></i>
                            Unpaid Orders
                        </h5>
                        <small class="text-muted">Pay Later and Partially Paid Orders</small>
                    </div>

                    <button
                        class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                        data-bs-dismiss="modal" aria-label="Close" title="Close" @click="$emit('close')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- BODY -->
                <div class="modal-body p-4">
                    <div v-if="loading" class="text-center py-5">
                        <div class="spinner-border text-info" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 text-muted">Fetching unpaid orders...</p>
                    </div>

                    <div v-else-if="filteredOrders.length === 0" class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 4rem; opacity: 0.2;"></i>
                        <p class="mt-3 text-muted fw-semibold">No unpaid orders found</p>
                    </div>

                    <div v-else class="row g-3">
                        <div v-for="order in filteredOrders" :key="order.id" class="col-md-4 col-sm-6">
                            <div class="card shadow-sm border-warning border-2 hover-shadow h-100">
                                <span class="badge position-absolute top-0 end-0 m-2"
                                    :class="getStatusBadgeClass(order)">
                                    <i class="bi bi-credit-card me-1"></i>
                                    {{ getStatusText(order) }}
                                </span>

                                <div class="card-body p-3">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="flex-grow-1">
                                            <h6 class="fw-bold mb-1 pe-5">
                                                {{ order.customer_name || 'Walk In' }}
                                                <span class="badge ms-1"
                                                    style="background-color: #1C0D82; padding: 4px 8px; border-radius: 6px; font-size: 0.75rem;">
                                                    {{ order.order_type.replace('_', ' ') }}
                                                </span>
                                                <span v-if="order.table_number" class="badge bg-info ms-1"
                                                    style="font-size: 0.75rem;">
                                                    {{ order.table_number }}
                                                </span>
                                            </h6>

                                            <p class="text-muted small mb-3">
                                                <i class="bi bi-clock me-1"></i>
                                                Created {{ formatRelativeTime(order.created_at) }}
                                            </p>

                                            <!-- Payment Details -->
                                            <div class="bg-light p-2 rounded-3 mb-3">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="text-muted extra-small">Total:</span>
                                                    <span class="fw-semibold small text-dark">{{
                                                        formatCurrencySymbol(order.total_amount) }}</span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="text-muted extra-small">Paid:</span>
                                                    <span class="fw-semibold small text-success">{{
                                                        formatCurrencySymbol(order.total_paid || 0) }}</span>
                                                </div>
                                                <div
                                                    class="border-top pt-1 mt-1 d-flex justify-content-between align-items-center">
                                                    <span class="fw-bold text-danger extra-small">Due:</span>
                                                    <span class="fw-bold text-danger">{{
                                                        formatCurrencySymbol(order.remaining_balance ||
                                                            order.total_amount) }}</span>
                                                </div>
                                            </div>

                                            <!-- Action Buttons -->
                                            <div class="d-flex gap-2">
                                                <button
                                                    class="btn btn-success btn-sm flex-fill d-flex align-items-center justify-content-center gap-2 py-2"
                                                    @click="$emit('pay-now', order)">
                                                    <i class="bi bi-credit-card-fill"></i>
                                                    Pay Now
                                                </button>
                                                <button
                                                    class="btn btn-danger btn-sm flex-fill d-flex align-items-center justify-content-center gap-2 py-2"
                                                    @click="confirmReject(order)">
                                                    <i class="bi bi-trash-fill"></i>
                                                    Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FOOTER -->
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary px-4 py-2" @click="$emit('close')">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div v-if="show" class="modal-backdrop fade show"></div>
</template>

<style scoped>
.hover-shadow {
    transition: all 0.3s ease;
}

.hover-shadow:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1) !important;
}

.hover-danger:hover {
    background-color: #dc3545;
    color: white;
}

.extra-small {
    font-size: 0.7rem;
}

.bg-light {
    background-color: #f8f9fa !important;
}

.dark .bg-light {
    background: #1a1a1a !important;
}

.modal.show {
    background-color: rgba(0, 0, 0, 0.5);
}
</style>
