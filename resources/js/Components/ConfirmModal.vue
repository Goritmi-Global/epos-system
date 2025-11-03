<template>
    <div class="side-link">
        <!-- Delete / Custom Trigger Button -->
        <button v-if="showDeleteButton" @click="show = true" 
            class="inline-flex items-center justify-center p-2.5 rounded-full text-red-600 hover:bg-red-100"
            title="Delete">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0H7m4-3h2a1 1 0 011 1v1H8V5a1 1 0 011-1z" />
            </svg>
        </button>

        <!-- Toggle Status Button -->
        <div v-if="showStatusButton" @click="show = true" class="cursor-pointer">
            <slot name="trigger">
                <button
                    class="relative inline-flex items-center status-btn rounded-full transition-colors duration-300 focus:outline-none"
                    :class="status === 'active' ? 'bg-green-500 hover:bg-green-600' : 'bg-red-400 hover:bg-red-500'"
                    :title="status === 'active' ? 'Set Inactive' : 'Set Active'">
                    <span
                        class="absolute left-0.5 top-0.5 w-1 h-1 bg-white rounded-full shadow transform transition-transform duration-300"
                        :class="status === 'active' ? 'translate-x-6' : 'translate-x-0'"></span>
                </button>
            </slot>
        </div>

        <!-- Modal -->
        <Transition name="fade-slide">
            <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 animate-drop-in relative">
                    <!-- Close Button -->
                    <button
                        class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                        @click="handleCancel" 
                        :disabled="loading"
                        title="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Icon - Customizable based on modal type -->
                    <div class="flex justify-center mb-4">
                        <div class="bg-gray-100 p-3 rounded-full">
                            <!-- Default warning icon -->
                            <svg v-if="iconType === 'warning'" 
                                class="w-8 h-8 text-danger" 
                                fill="none" 
                                stroke="currentColor" 
                                stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v2m0 4h.01M12 19a7 7 0 100-14 7 7 0 000 14z" />
                            </svg>

                            <!-- Database/Backup icon - for database backup modal -->
                            <svg v-else-if="iconType === 'database'" 
                                class="w-8 h-8 text-primary" 
                                fill="none" 
                                stroke="currentColor" 
                                stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                            </svg>

                            <!-- Download icon - alternative for backup -->
                            <svg v-else-if="iconType === 'download'" 
                                class="w-8 h-8 text-blue-600" 
                                fill="none" 
                                stroke="currentColor" 
                                stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>

                            <!-- Restore/Refresh icon - for system restore -->
                            <svg v-else-if="iconType === 'restore'" 
                                class="w-8 h-8 text-orange-600" 
                                fill="none" 
                                stroke="currentColor" 
                                stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>

                            <!-- Success/Check icon -->
                            <svg v-else-if="iconType === 'success'" 
                                class="w-8 h-8 text-green-600" 
                                fill="none" 
                                stroke="currentColor" 
                                stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>

                            <!-- Error/X icon -->
                            <svg v-else-if="iconType === 'error'" 
                                class="w-8 h-8 text-red-600" 
                                fill="none" 
                                stroke="currentColor" 
                                stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>

                            <!-- Info icon -->
                            <svg v-else-if="iconType === 'info'" 
                                class="w-8 h-8 text-blue-600" 
                                fill="none" 
                                stroke="currentColor" 
                                stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Text -->
                    <h3 class="text-center text-lg font-medium text-gray-800 mb-2">
                        {{ title }}
                    </h3>
                    <p class="text-center text-sm text-gray-500 mb-6">
                        {{ message }}
                    </p>

                    <!-- Buttons -->
                    <div class="flex justify-center gap-3">
                        <!-- Confirm Button - Shows loading state -->
                        <button 
                            @click="handleConfirm"
                            :disabled="loading"
                            class="btn btn-primary d-flex align-items-center gap-2 px-4 py-2 rounded-pill text-white"
                            :class="{ 'opacity-50 cursor-not-allowed': loading }">
                            <!-- Loading Spinner -->
                            <span v-if="loading" class="spinner-border spinner-border-sm" role="status"></span>
                            <!-- Button Text -->
                            <span>{{ loading ? loadingText : confirmText }}</span>
                        </button>

                        <!-- Cancel Button -->
                        <button 
                            @click="handleCancel"
                            :disabled="loading"
                            class="btn btn-secondary d-flex align-items-center gap-1 px-4 py-2 rounded-pill text-white"
                            :class="{ 'opacity-50 cursor-not-allowed': loading }">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, watch } from "vue";
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

const page = usePage();

/**
 * Props for customizing the modal behavior and appearance
 * 
 * @param {String} title - Modal title
 * @param {String} message - Modal description/message
 * @param {Boolean} show - Controls modal visibility (v-model compatible)
 * @param {Boolean} showDeleteButton - Shows delete button trigger
 * @param {Boolean} showStatusButton - Shows status toggle trigger
 * @param {String} iconType - Icon type: 'warning', 'database', 'download', 'restore', 'success', 'error', 'info'
 * @param {String} confirmText - Text for confirm button (default: "Yes, I'm sure")
 * @param {String} loadingText - Text shown during loading (default: "Processing...")
 * @param {Boolean} isCollapsed - For sidebar compatibility
 */
const props = defineProps({
    title: {
        type: String,
        required: true
    },
    message: {
        type: String,
        required: true
    },
    show: {
        type: Boolean,
        default: false
    },
    showDeleteButton: {
        type: Boolean,
        default: false
    },
    showStatusButton: {
        type: Boolean,
        default: false
    },
    iconType: {
        type: String,
        default: 'warning', // Options: 'warning', 'database', 'download', 'restore', 'success', 'error', 'info'
        validator: (value) => {
            return ['warning', 'database', 'download', 'restore', 'success', 'error', 'info'].includes(value);
        }
    },
    confirmText: {
        type: String,
        default: "Yes, I'm sure"
    },
    loadingText: {
        type: String,
        default: "Processing..."
    },
    isCollapsed: {
        type: Boolean,
        default: false
    },
    status: {
        type: String,
        default: 'inactive'
    }
});

const emit = defineEmits(["confirm", "cancel", "update:show"]);

// Local state for modal visibility
const show = ref(props.show);

// Loading state for async operations
const loading = ref(false);

// Watch for external show prop changes
watch(() => props.show, (newValue) => {
    show.value = newValue;
});

// Watch for internal show changes and emit to parent
watch(show, (newValue) => {
    emit('update:show', newValue);
});

/**
 * Handle confirm action
 * Sets loading state, emits confirm event
 * Note: Parent component should handle the actual operation and loading state
 */
const handleConfirm = async () => {
    loading.value = true;
    
    // Emit confirm event - parent will handle the actual operation
    emit("confirm");
    
    // Reset loading after a brief delay (parent should control actual loading)
    // This is just for visual feedback
    setTimeout(() => {
        loading.value = false;
    }, 300);
};

/**
 * Handle cancel action
 * Closes modal and emits cancel event
 */
const handleCancel = () => {
    if (!loading.value) {
        show.value = false;
        emit("cancel");
    }
};

// Permission helpers (from original code)
const userPermissions = computed(() => page.props.current_user?.permissions ?? []);
const userRoles = computed(() => page.props.current_user?.roles ?? []);

const hasPermission = (perm) => {
    if (!perm) return true;
    if (userRoles.value.includes("Super Admin")) return true;

    const permissions = Array.isArray(userPermissions.value)
        ? userPermissions.value
        : [];
    return permissions.includes(perm);
};
</script>

<style scoped>
/* Keep icon centered when collapsed */
.sidebar-btn {
    border-radius: 10px !important;
    width: 160px !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.sidebar-collapsed .sidebar-btn {
    width: 48px !important;
}

/* Fade slide animation */
.fade-slide-enter-active {
    transition: all 0.3s ease;
}

/* Dark mode support */
.dark .bg-white {
    background-color: #212121 !important;
    color: #fff !important;
    border: 1px solid #fff !important;
}

.dark h3 {
    color: #fff !important;
}

.dark p {
    color: #fff !important;
}

.dark .bg-gray-100 {
    background-color: #121212 !important;
}

/* Animation states */
.fade-slide-enter-from {
    opacity: 0;
    transform: translateY(-20px);
}

.fade-slide-enter-to {
    opacity: 1;
    transform: translateY(0);
}

/* Drop-in animation */
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

/* Button hover effects */
.sidebar-btn {
    border-radius: 10px !important;
    width: 160px !important;
}

.sidebar-btn:hover {
    color: #fff !important;
    background-color: #1B2850 !important;
}

/* Status button styling */
.status-btn {
    height: 12px !important;
    width: 8px !important;
}

/* Disabled state styling */
.opacity-50 {
    opacity: 0.5;
}

.cursor-not-allowed {
    cursor: not-allowed !important;
}

/* Loading spinner */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
    border-width: 0.15em;
}
</style>