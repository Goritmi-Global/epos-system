<template>
    <Transition name="fade-slide">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 animate-drop-in relative">
                <!-- Close Button -->
                <button
                    class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                    @click="handleCancel" title="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Icon -->
                <div class="flex justify-center mb-4">
                    <div class="bg-orange-100 p-3 rounded-full">
                        <RefreshCcw class="w-8 h-8 text-orange-600" />
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
                    <button @click="handleConfirm"
                        class="btn btn-primary d-flex align-items-center gap-1 px-3 py-2 rounded-pill text-white">
                        {{ confirmLabel }}
                    </button>

                    <button @click="handleCancel"
                        class="btn btn-secondary d-flex align-items-center gap-1 px-3 py-2 rounded-pill text-white">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { RefreshCcw } from "lucide-vue-next";

const props = defineProps({
    show: {
        type: Boolean,
        required: true
    },
    title: {
        type: String,
        default: 'Confirm System Restore'
    },
    message: {
        type: String,
        default: 'Are you sure you want to restore the system? This action cannot be undone.'
    },
    confirmLabel: {
        type: String,
        default: 'Yes, Restore'
    }
});


const emit = defineEmits(["confirm", "cancel"]);

const handleConfirm = () => {
    emit("confirm");
};

const handleCancel = () => {
    emit("cancel");
};
</script>

<style scoped>
.fade-slide-enter-active {
    transition: all 0.3s ease;
}

.fade-slide-enter-from {
    opacity: 0;
    transform: translateY(-20px);
}

.fade-slide-enter-to {
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

.dark .bg-orange-100 {
    background-color: #121212 !important;
}
</style>