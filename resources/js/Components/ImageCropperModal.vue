<script setup>
import {
    ref,
    watch,
    onMounted,
    onBeforeUnmount,
    computed,
    nextTick,
} from "vue";
import VueCropper from "vue-cropperjs";
import "cropperjs/dist/cropper.css";

const props = defineProps({
    show: { type: Boolean, default: false },
    image: { type: String, default: "" }, // optional initial image
});
const emit = defineEmits(["close", "cropped"]);

const cropper = ref(null);
const fileInput = ref(null);
const uploadedImage = ref(props.image || null);
const scaleX = ref(-1);
const scaleY = ref(-1);
const hasImage = computed(() => !!uploadedImage.value);

function openPicker() {
    // allow re-choosing the same file
    if (fileInput.value) fileInput.value.value = "";
    fileInput.value?.click();
}

function clearState() {
    uploadedImage.value = null;
    scaleX.value = -1;
    scaleY.value = -1;
    // cropper instance will unmount due to v-if
}

watch(
    () => props.show,
    async (v) => {
        if (v) {
            // Always prompt for a new image right away
            await nextTick();
            openPicker();
        } else {
            // When closed, wipe previous session
            clearState();
        }
    }
);

watch(
    () => props.image,
    (val) => {
        if (val) uploadedImage.value = val;
    }
);

function handleFileUpload(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = () => {
        uploadedImage.value = String(reader.result || "");
    };
    reader.readAsDataURL(file);
}

function cropImage() {
    const c = cropper.value?.getCroppedCanvas?.({ willReadFrequently: true });
    if (!c) return;
    // Emit dataURL; swap to toBlob if you prefer File/Blob
    const dataUrl = c.toDataURL("image/webp", 0.92);
    emit("cropped", dataUrl);
    closeModal();
}

function closeModal() {
    clearState();
    emit("close");
}

const reset = () => hasImage.value && cropper.value?.reset?.();

const rotate = (deg) => hasImage.value && cropper.value?.rotate?.(deg);
const flipX = () => {
    if (!hasImage.value) return;
    cropper.value?.scaleX?.(scaleX.value);
    scaleX.value = -scaleX.value;
};
const flipY = () => {
    if (!hasImage.value) return;
    cropper.value?.scaleY?.(scaleY.value);
    scaleY.value = -scaleY.value;
};

// Close on ESC
function onKey(e) {
    if (e.key === "Escape" && props.show) closeModal();
}
onMounted(() => document.addEventListener("keydown", onKey));
onBeforeUnmount(() => document.removeEventListener("keydown", onKey));
</script>

<template>
    <transition name="fade">
        <div
            v-if="show"
            class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center"
            role="dialog"
            aria-modal="true"
            @click.self="closeModal"
        >
            <div
                class="w-full max-w-4xl max-h-[92vh] bg-white rounded-xl shadow-2xl p-5 overflow-auto relative"
            >
                <!-- Header -->
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h2 class="text-xl font-semibold">Upload and Crop Image</h2>
                    <div class="flex items-center gap-2">
                        <button
                            class="btn btn-outline-secondary"
                            @click="openPicker"
                        >
                            Change Image
                        </button>
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
                </div>

                <!-- Hidden file input -->
                <input
                    ref="fileInput"
                    type="file"
                    class="hidden"
                    accept="image/*"
                    @change="handleFileUpload"
                />

                <div class="grid grid-cols-12 gap-4">
                    <!-- Cropper -->
                    <div class="col-span-12 md:col-span-9">
                        <VueCropper
                            v-if="uploadedImage"
                            ref="cropper"
                            :src="uploadedImage"
                            :aspect-ratio="1"
                            :view-mode="1"
                            :auto-crop-area="1"
                            :background="false"
                            :responsive="true"
                            :check-cross-origin="false"
                            :preview="'.cropper-preview'"
                            class="w-full h-[60vh] min-h-[320px] border rounded"
                        />
                        <div
                            v-else
                            class="w-full h-[60vh] min-h-[320px] border rounded bg-gray-50 flex items-center justify-center text-gray-500"
                        >
                            Select an image to start…
                        </div>
                    </div>

                    <!-- Live preview -->
                    <div class="col-span-12 md:col-span-3">
                        <label class="block text-sm font-medium mb-2"
                            >Preview</label
                        >
                        <div
                            class="cropper-preview border rounded overflow-hidden bg-gray-100"
                            style="width: 100%; max-width: 220px; height: 220px"
                        ></div>
                    </div>
                </div>

                <!-- Tools -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-4">
                    <button
                        class="bg-blue-600 text-white text-sm py-1.5 px-4 rounded"
                        :disabled="!hasImage"
                        @click="rotate(90)"
                    >
                        Rotate +90°
                    </button>
                    <button
                        class="bg-blue-600 text-white text-sm py-1.5 px-4 rounded"
                        :disabled="!hasImage"
                        @click="rotate(-90)"
                    >
                        Rotate -90°
                    </button>
                    <button
                        class="bg-blue-600 text-white text-sm py-1.5 px-4 rounded"
                        :disabled="!hasImage"
                        @click="flipX"
                    >
                        Flip X
                    </button>
                    <button
                        class="bg-blue-600 text-white text-sm py-1.5 px-4 rounded"
                        :disabled="!hasImage"
                        @click="flipY"
                    >
                        Flip Y
                    </button>
                </div>

                <!-- Actions -->
                <div class="flex justify-end mt-6 gap-2">
                    <button
                        class="bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300"
                        :disabled="!hasImage"
                        @click="reset"
                    >
                        Reset
                    </button>
                    <button
                        class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700"
                        :disabled="!hasImage"
                        @click="cropImage"
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
