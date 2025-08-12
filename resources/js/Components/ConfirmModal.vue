<template>
  <div>
    <!-- Delete Trigger Button -->
    <button
      v-if="showDeleteButton"
      @click="show = true"
      class="inline-flex items-center justify-center p-2.5 rounded-full text-red-600 hover:bg-red-100"
      title="Delete"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0H7m4-3h2a1 1 0 011 1v1H8V5a1 1 0 011-1z"
        />
      </svg>
    </button>

    <!-- Modal -->
    <Transition name="fade-slide">
      <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 animate-drop-in relative">
          <!-- Close Button -->

           <button
                    class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                    @click="handleCancel"
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
            <div class="bg-gray-100 p-3 rounded-full">
              <svg
                class="w-8 h-8 text-gray-400"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M12 19a7 7 0 100-14 7 7 0 000 14z" />
              </svg>
            </div>
          </div>

          <!-- Text -->
          <h3 class="text-center text-lg font-medium text-gray-800 mb-2">{{ title }}</h3>
          <p class="text-center text-sm text-gray-500 mb-6">{{ message }}</p>

          <!-- Buttons -->
          <div class="flex justify-center gap-3">
            <button
              @click="handleConfirm"
              class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-5 py-2.5 rounded-lg"
            >
              Yes, I'm sure
            </button>
            <button
              @click="handleCancel"
              class="bg-white text-sm font-medium text-gray-700 border border-gray-300 hover:bg-gray-100 px-5 py-2.5 rounded-lg"
            >
              No, cancel
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  title: String,
  message: String,
  showDeleteButton: Boolean,
});

const emit = defineEmits(['confirm', 'cancel']);

const show = ref(false);

const handleConfirm = () => {
  emit('confirm');
  show.value = false;
};

const handleCancel = () => {
  emit('cancel');
  show.value = false;
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
</style>
