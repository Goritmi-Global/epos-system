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
                    <div class="bg-blue-100 p-3 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-blue-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" 
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                </div>

                <!-- Text -->
                <h3 class="text-center text-lg font-medium text-gray-800 mb-2">
                    Super Admin Verification Required
                </h3>
                <p class="text-center text-sm text-gray-500 mb-6">
                    Please enter your password to confirm this critical action
                </p>

                <!-- Password Input -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Password
                    </label>
                    <div class="relative">
                        <input 
                            :type="showPassword ? 'text' : 'password'"
                            v-model="password"
                            @keyup.enter="handleConfirm"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Enter your password"
                            :disabled="isVerifying"
                            autofocus
                        />
                        <button 
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Error Message -->
                    <p v-if="errorMessage" class="mt-2 text-sm text-red-600">
                        {{ errorMessage }}
                    </p>
                </div>

                <!-- Loading State -->
                <div v-if="isVerifying" class="flex justify-center mb-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Verifying...</span>
                    </div>
                </div>

                <!-- Buttons -->
                <div v-else class="flex justify-center gap-3">
                    <button 
                        @click="handleConfirm"
                        :disabled="!password"
                        class="btn btn-primary d-flex align-items-center gap-1 px-4 py-2 rounded-pill text-white"
                        :class="{ 'opacity-50 cursor-not-allowed': !password }">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Verify & Continue
                    </button>
                    <button 
                        @click="handleCancel"
                        class="btn btn-secondary d-flex align-items-center gap-1 px-4 py-2 rounded-pill text-white">
                        Cancel
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
    isVerifying: { type: Boolean, default: false },
    errorMessage: { type: String, default: '' },
});

const emit = defineEmits(["confirm", "cancel"]);

const password = ref('');
const showPassword = ref(false);

// Reset password when modal is closed
watch(() => props.show, (newVal) => {
    if (!newVal) {
        password.value = '';
        showPassword.value = false;
    }
});

const handleConfirm = () => {
    if (password.value) {
        const passwordValue = password.value;
        password.value = '';
        showPassword.value = false;
        emit("confirm", passwordValue);
    }
};

const handleCancel = () => {
    password.value = '';
    showPassword.value = false;
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

.dark .text-gray-700{
    color: #fff !important;
}

.dark .text-gray-500{
    color: #fff !important;
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