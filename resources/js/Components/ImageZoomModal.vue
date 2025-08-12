<script setup>
import { ref } from 'vue';

const props = defineProps({
  show: Boolean,
  image: String,
});

const emit = defineEmits(['close']);
</script>

<template>
  <transition name="fade">
    <div
      v-if="show"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70"
    >
      <div class="bg-white rounded-lg shadow-lg relative max-w-3xl w-full">
        <!-- Top Buttons -->
        <div class="absolute top-2 left-2 flex gap-3 items-center z-10">
          <!-- Download Button -->
          <a
            :href="image"
            :download="`downloaded_image_${Date.now()}.webp`"
            class="p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
            title="Download"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-6 w-6 text-blue-600"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"
              />
            </svg>
          </a>
        </div>

        <!-- Close Button (Top Right) -->
        <button
          class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
          @click="$emit('close')"
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

        <!-- Image Preview -->
        <div class="text-center p-0">
          <img
            :src="image"
            alt="Zoomed Image"
            class="max-h-[90vh] w-auto mx-auto rounded"
          />
        </div>
      </div>
    </div>
  </transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
