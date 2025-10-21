<template>
    <Transition name="fade-slide">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 animate-drop-in relative">
                <!-- Close Button -->
                <button
                    class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                    @click="handleCancel" title="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Icon -->
                <div class="flex justify-center mb-4">
                    <div class="bg-red-100 p-3 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-red-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
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
                    <button @click="handleConfirm"
                        class="btn btn-danger d-flex align-items-center gap-1 px-3 py-2 rounded-pill text-white">
                        Yes, Logout
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
import { ref } from "vue";

const props = defineProps({
    title: { type: String, default: "Confirm Logout" },
    message: { type: String, default: "Are you sure you want to log out?" },
    show: { type: Boolean, default: false },
});

const emit = defineEmits(["confirm", "cancel"]);

const handleConfirm = () => emit("confirm");
const handleCancel = () => emit("cancel");
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

.dark .bg-white {
    border: 1px solid #fff !important;
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
</style>
