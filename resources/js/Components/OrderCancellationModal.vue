<template>
    <Transition name="fade-slide">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 animate-drop-in relative">
                <!-- Close Button -->
                  <button
                        class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                         @click="handleCancel" 
                        data-bs-dismiss="modal"
                        aria-label="Close"
                        title="Close"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6 text-red-500"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>

                <!-- Icon -->
                <div class="flex justify-center mb-4">
                    <div class="bg-red-100 p-3 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-red-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                </div>

                <!-- Text -->
                <h3 class="text-center text-lg font-medium text-gray-800 mb-2">
                    Cancel Order #{{ order?.id }}?
                </h3>
                <p class="text-center text-sm text-gray-500 mb-4">
                    This action will cancel the order and cannot be undone.
                </p>

                <!-- Order Details -->
                <div class="bg-gray-50 rounded-lg p-4 mb-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Customer:</span>
                        <span class="font-medium text-gray-800">{{ order?.customer_name || 'Walk In' }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Total Amount:</span>
                        <span class="font-medium text-gray-800">{{ formatCurrency(order?.total_amount) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Payment Type:</span>
                        <span class="font-medium text-gray-800">{{ order?.payment?.payment_type || 'N/A' }}</span>
                    </div>
                </div>

                <!-- Cancellation Reason (Optional) -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Reason for Cancellation (Optional)
                    </label>
                    <textarea 
                        v-model="cancellationReason"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-white focus:border-transparent resize-none"
                        rows="3"
                        placeholder="Enter reason for cancelling this order..."
                    ></textarea>
                </div>

                <!-- Buttons -->
                <div class="flex justify-center gap-3">
                    <button 
                        @click="handleConfirm"
                        :disabled="loading"
                        class="btn btn-danger d-flex align-items-center gap-2 px-4 py-2 rounded-pill text-white">
                        <span v-if="loading" class="spinner-border spinner-border-sm" role="status"></span>
                      
                        {{ loading ? 'Cancelling...' : 'Cancel Order' }}
                    </button>
                    <button 
                        @click="handleCancel"
                        :disabled="loading"
                        class="btn btn-secondary d-flex align-items-center gap-2 px-4 py-2 rounded-pill text-white">
                        
                        Keep Order
                        
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { ref, watch } from "vue";

const props = defineProps({
    show: { type: Boolean, default: false },
    order: { type: Object, default: null },
    loading: { type: Boolean, default: false }
});

const emit = defineEmits(["confirm", "cancel"]);

const cancellationReason = ref("");

// Reset reason when modal closes
watch(() => props.show, (newVal) => {
    if (!newVal) {
        cancellationReason.value = "";
    }
});

const handleConfirm = () => {
    emit("confirm", cancellationReason.value || "Cancelled by admin from orders page");
};

const handleCancel = () => {
    emit("cancel");
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-GB', {
        style: 'currency',
        currency: 'GBP'
    }).format(amount || 0);
};
</script>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.3s ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-20px);
}

.fade-slide-enter-to,
.fade-slide-leave-from {
    opacity: 1;
    transform: translateY(0);
}

.animate-drop-in {
    animation: dropIn 0.25s ease-out;
}

@keyframes dropIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Dark mode support */
.dark .bg-white {
    background-color: #1f2937;
    border: 1px solid #374151;
}

.dark .text-gray-800 {
    color: #f9fafb;
}

.dark .text-gray-700{
    color: #fff !important;
}

.dark .text-gray-600 {
    color: #d1d5db;
}

.dark .text-gray-500{
    color: #fff !important;
}

.dark .bg-gray-50 {
    background-color: #374151;
}

.dark .border-gray-300 {
    border-color: #4b5563;
}

.dark textarea {
    background-color: #374151;
    color: #f9fafb;
}
</style>