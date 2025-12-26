<template>
    <div class="modal fade" :class="{ show: show, 'd-block': show }" :style="{ display: show ? 'block' : 'none' }"
        tabindex="-1" @click.self="$emit('close')">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content rounded-4 border-0 shadow-lg">

                <!-- HEADER -->
                <div class="modal-header border-0 bg-warning bg-opacity-10">
                    <div>
                        <h5 class="fw-bold mb-0">
                            <i class="bi bi-clock-history text-warning me-2"></i>
                            Pending Orders
                        </h5>
                        <small class="text-muted">Hold and resume orders</small>
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
                    <!-- Loading State -->
                    <div v-if="loading" class="text-center py-5">
                        <div class="spinner-border text-warning" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 text-muted">Loading pending orders...</p>
                    </div>

                    <!-- Empty State -->
                    <div v-else-if="!loading && (!pendingOrders || pendingOrders.length === 0)"
                        class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 4rem; opacity: 0.2;"></i>
                        <p class="mt-3 text-muted fw-semibold">No pending orders</p>
                        <p class="text-muted small">Use "Pending" button to hold orders</p>
                    </div>

                    <!-- Pending Orders List -->
                    <div v-else class="row g-3">
                        <div v-for="order in filteredOrders" :key="order.id" class="col-4">
                            <div class="card shadow-sm border-warning border-2 hover-shadow">
                                <div class="card-body p-3">
                                    <div class="align-items-center">
                                        <div class="col-lg-12">
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="flex-grow-1">
                                                    <h6 class="fw-bold mb-1">
                                                        {{ order.customer_name || 'Walk In' }}
                                                        <span class="badge ms-2"
                                                            style="background-color: #1C0D82; padding: 8px; border-radius: 6px;">
                                                            {{ order.order_type.replace('_', ' ') }}
                                                        </span>
                                                        <span v-if="order.table_number" class="badge bg-info ms-1">
                                                            {{ order.table_number }}
                                                        </span>
                                                    </h6>

                                                    <p class="text-muted small mb-2">
                                                        <i class="bi bi-clock me-1"></i>
                                                        Held {{ formatRelativeTime(order.held_at) }}
                                                    </p>

                                                    <!-- Items Summary -->
                                                    <div class="mb-2">
                                                        <small class="fw-semibold text-secondary">Items ({{
                                                            order.order_items.length }}):</small>
                                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                                            <span v-for="(item, idx) in order.order_items.slice(0, 3)"
                                                                :key="idx" class="badge bg-light text-dark border">
                                                                {{ item.title }} ({{ item.quantity }}x)
                                                            </span>
                                                            <span v-if="order.order_items.length > 3"
                                                                class="badge bg-secondary">
                                                                +{{ order.order_items.length - 3 }} more
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <!-- Total Amount -->
                                                    <div class="fw-bold text-success fs-5">
                                                        Total: {{ formatCurrencySymbol(order.total_amount) }}
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Action Buttons -->
                                            <div class="d-flex gap-2 mt-2">
                                                <button
                                                    class="btn btn-success btn-sm flex-fill d-flex align-items-center justify-content-center gap-2"
                                                    @click="$emit('resume', order)">
                                                    <i class="bi bi-play-fill"></i>
                                                    Resume
                                                </button>
                                                <button
                                                    class="btn btn-danger btn-sm flex-fill d-flex align-items-center justify-content-center gap-2"
                                                    @click="confirmReject(order)">
                                                    <i class="bi bi-trash"></i>
                                                    Reject
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
                    <button type="button" class="btn btn-secondary px-2 py-2" @click="$emit('close')">
                        Close
                    </button>
                </div>

            </div>
        </div>
    </div>
    <div v-if="show" class="modal-backdrop fade show"></div>
</template>

<script setup>
import { computed } from 'vue';
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

const emit = defineEmits(['close', 'resume', 'reject']);

const filteredOrders = computed(() => {
    return props.pendingOrders.filter(order => order.type === 'held');
});

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

const confirmReject = (order) => {
    if (confirm(`Are you sure you want to reject order for "${order.customer_name || 'Walk In'}"? This action cannot be undone.`)) {
        emit('reject', order);
    }
};
</script>

<style scoped>
.hover-shadow {
    transition: all 0.3s ease;
}

.dark .bg-light {
    background: #141414 !important;
}

.hover-shadow:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1) !important;
}

.modal.show {
    background-color: rgba(0, 0, 0, 0.5);
}
</style>