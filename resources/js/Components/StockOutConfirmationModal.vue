<template>
  <Transition>
    <div 
      v-if="show" 
      class="fixed inset-0 z-50 flex items-center justify-center custom-overlay"
    >
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 animate-drop-in relative">

        <!-- Close -->
        <button
          class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
          @click="closeModal"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-danger" fill="none"
               viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>

        <!-- Icon -->
        <div class="flex justify-center mb-4">
          <div class="bg-gray-100 p-3 rounded-full">
            <svg class="w-8 h-8 text-danger" fill="none" stroke="currentColor" stroke-width="2"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v2m0 4h.01M12 19a7 7 0 100-14 7 7 0 000 14z"/>
            </svg>
          </div>
        </div>

        <!-- Title -->
        <h3 class="text-center text-lg font-medium text-gray-800 mb-2">
          {{ title }}
        </h3>

        <!-- Message -->
        <p class="text-center text-sm text-gray-500 mb-6">
          {{ message }}
        </p>

        <!-- Buttons -->
        <div class="flex justify-center gap-3">
          <button
            @click="confirmAction"
            class="btn btn-primary d-flex align-items-center gap-1 px-3 py-2 rounded-pill text-white"
          >
            Yes, I'm sure
          </button>

          <button
            @click="closeModal"
            class="btn btn-secondary d-flex align-items-center gap-1 px-3 py-2 rounded-pill text-white"
          >
            Cancel
          </button>
        </div>

      </div>
    </div>
  </Transition>
</template>

<script setup>
const props = defineProps({
  show: Boolean,
  title: String,
  message: String,
})
const emits = defineEmits(['confirm', 'close'])

const confirmAction = () => emits('confirm')
const closeModal = () => emits('close')
</script>

<style scoped>
.custom-overlay {
  background-color: rgba(0, 0, 0, 0.1) !important; /* SAME AS RESTORE */
}

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
</style>
