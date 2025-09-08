<script setup>
import { ref } from "vue";

const props = defineProps({
    file: { type: String, required: true },
    width: { type: [String, Number], default: undefined },
    height: { type: [String, Number], default: undefined },
    custom_class: { type: [String, Array, Object], default: "" },
});

const DEFAULT_IMG = "/assets/img/default.png";
const openModal = ref(null);
const modelId = Math.floor(Math.random() * 1000);

function zoomImage() {
    openModal.value?.click();
}

function setAltImg(event) {
    if (event?.target?.src?.includes(DEFAULT_IMG)) return; // avoid infinite loop
    event.target.onerror = null;
    event.target.src = DEFAULT_IMG;
}
</script>

<template>
    <!-- Thumbnail -->
    <img
        :src="file || DEFAULT_IMG"
        alt="File"
        :class="custom_class"
        class="img-fluid img-thumbnail rounded"
        :width="width"
        :height="height"
        @error="setAltImg"
        @click="zoomImage"
    />

    <a
        type="hidden"
        ref="openModal"
        data-bs-toggle="modal"
        :data-bs-target="'#ZoomImage' + modelId"
    ></a>

    <!-- Modal -->
    <div class="modal fade" :id="'ZoomImage' + modelId" tabindex="-1">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content relative">
                <!-- Modal Header -->
                <div class="modal-header justify-center relative">
                    <!-- Title centered -->
                    <h5 class="modal-title mx-auto text-center">
                        Preview Image
                    </h5>

                    <!-- Download (top-left) -->
                    <a
                        :href="file || DEFAULT_IMG"
                        target="_blank"
                        :target="'_blank'"
                        :download="`downloaded_image_${Date.now()}.webp`"
                        class="absolute top-2 left-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
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

                    <!-- Close (top-right) -->
                    <button
                        class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
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
                </div>

                <!-- Modal Body -->
                <div class="modal-body relative p-3">
                    <!-- Full Image -->
                    <img
                        :src="file || DEFAULT_IMG"
                        class="img-fluid d-block mx-auto w-100"
                        style="max-width: 100%; height: auto"
                        @error="setAltImg"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
