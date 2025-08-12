<script setup>
import { ref, watch } from "vue";
import VueCropper from "vue-cropperjs";
import "cropperjs/dist/cropper.css";

const props = defineProps({
    show: Boolean,
    image: String,
});

const emit = defineEmits(["close", "cropped"]);

const cropper = ref(null);
const scaleX = ref(-1);
const scaleY = ref(-1);
const uploadedImage = ref(props.image);

watch(
    () => props.show,
    (visible) => {
        if (visible) {
            // wait for DOM to render, then auto-click input
            setTimeout(() => {
                fileInput.value?.click();
            }, 100);
        }
    }
);

const handleFileUpload = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = () => {
        uploadedImage.value = reader.result;
    };
    reader.readAsDataURL(file);
};

const cropImage = () => {
    const canvas = cropper.value?.getCroppedCanvas();
    if (canvas) {
        const cropped = canvas.toDataURL("image/webp");
        emit("cropped", cropped);
        emit("close");
    }
};
const fileInput = ref(null);

const reset = () => cropper.value?.reset();
const zoom = (step) => cropper.value?.relativeZoom(step);
const move = (x, y) => cropper.value?.move(x, y);
const rotate = (deg) => cropper.value?.rotate(deg);
const flipX = () => {
    cropper.value?.scaleX(scaleX.value);
    scaleX.value = -scaleX.value;
};
const flipY = () => {
    cropper.value?.scaleY(scaleY.value);
    scaleY.value = -scaleY.value;
};
</script>

<template>
    <transition name="fade">
        <div
            v-if="show"
            class="fixed inset-0 z-50 bg-black bg-opacity-40 flex justify-end"
        >
            <div
                class="w-full max-w-xl h-full bg-white shadow-lg p-5 overflow-y-auto relative"
            >
                <!-- Header -->
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">Upload and Crop Image</h2>
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
                </div>

                <!-- Cropper Preview -->
                <input
                    type="file"
                    class="hidden"
                    @change="handleFileUpload"
                    accept="image/*"
                    ref="fileInput"
                />

                <vue-cropper
                    v-if="uploadedImage"
                    ref="cropper"
                    :src="uploadedImage"
                    :aspect-ratio="1"
                    :view-mode="1"
                    class="w-full h-80 border rounded"
                />

                <!-- Tools -->
                <div class="grid grid-cols-2 gap-2 mt-4">
                    <button
                        class="bg-blue-600 text-white text-sm py-1.5 px-4 rounded"
                        @click="zoom(0.2)"
                    >
                        Zoom In
                    </button>
                    <button
                        class="bg-blue-600 text-white text-sm py-1.5 px-4 rounded"
                        @click="zoom(-0.2)"
                    >
                        Zoom Out
                    </button>
                    <button
                        class="bg-blue-600 text-white text-sm py-1.5 px-4 rounded"
                        @click="move(-10, 0)"
                    >
                        Left
                    </button>
                    <button
                        class="bg-blue-600 text-white text-sm py-1.5 px-4 rounded"
                        @click="move(10, 0)"
                    >
                        Right
                    </button>
                    <button
                        class="bg-blue-600 text-white text-sm py-1.5 px-4 rounded"
                        @click="move(0, -10)"
                    >
                        Up
                    </button>
                    <button
                        class="bg-blue-600 text-white text-sm py-1.5 px-4 rounded"
                        @click="move(0, 10)"
                    >
                        Down
                    </button>
                    <button
                        class="bg-blue-600 text-white text-sm py-1.5 px-4 rounded"
                        @click="rotate(90)"
                    >
                        Rotate +90°
                    </button>
                    <button
                        class="bg-blue-600 text-white text-sm py-1.5 px-4 rounded"
                        @click="rotate(-90)"
                    >
                        Rotate -90°
                    </button>
                    <button
                        class="bg-blue-600 text-white text-sm py-1.5 px-4 rounded"
                        @click="flipX"
                    >
                        Flip X
                    </button>
                    <button
                        class="bg-blue-600 text-white text-sm py-1.5 px-4 rounded"
                        @click="flipY"
                    >
                        Flip Y
                    </button>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end mt-6 gap-2">
                    <button
                        @click="reset"
                        class="bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300"
                    >
                        Reset
                    </button>
                    <button
                        @click="cropImage"
                        class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700"
                    >
                        Crop & Save
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
