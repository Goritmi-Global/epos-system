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

/**
 * Props
 * - controlled: use parent `show` or render a built-in trigger
 * - show: only in controlled mode
 * - image: optional initial image (URL / DataURL)
 * - aspectRatio/mime/quality: crop output controls
 * - autoOpenPicker: auto-open file dialog on modal open if no image
 * - btnLabel/btnClass: trigger button (self-contained mode)
 */
const props = defineProps({
    controlled: { type: Boolean, default: false },
    show: { type: Boolean, default: false },
    image: { type: String, default: "" },
    aspectRatio: { type: Number, default: 1 },
    mime: { type: String, default: "image/webp" },
    quality: { type: Number, default: 0.92 },
    autoOpenPicker: { type: Boolean, default: true },
    btnLabel: { type: String, default: "Upload & Crop Image" },
    btnClass: {
        type: String,
        default: "btn btn-primary rounded-pill mt-3 px-4",
    },
});

const emit = defineEmits(["close", "cropped", "open"]);

// state
const cropper = ref(null);
const fileInput = ref(null);
const uploadedImage = ref(props.image || null);
const cropperKey = ref(0);
const scaleX = ref(-1);
const scaleY = ref(-1);
const hasImage = computed(() => !!uploadedImage.value);

// local show for self-contained usage
const localShow = ref(false);
const isOpen = computed(() =>
    props.controlled ? props.show : localShow.value
);

// helpers
function openPicker() {
    if (fileInput.value) fileInput.value.value = "";
    fileInput.value?.click();
}
function clearState() {
    uploadedImage.value = props.image || null;
    scaleX.value = -1;
    scaleY.value = -1;
}
function openModal() {
    if (props.controlled) emit("open");
    else localShow.value = true;
}
function closeModal() {
    clearState();
    if (props.controlled) emit("close");
    else localShow.value = false;
}

// react to modal open/close
watch(
    () => isOpen.value,
    async (v) => {
        if (v) {
            await nextTick();
            // if parent passed an image, keep it; else prompt
            if (props.autoOpenPicker && !uploadedImage.value) openPicker();
        } else {
            clearState();
        }
    }
);

// sync when parent-provided image changes
watch(
    () => props.image,
    (val) => {
        uploadedImage.value = val || null;
    }
);

// choose file
function handleFileUpload(e) {
    const file = e.target.files?.[0];
    if (!file) {
        // Reset for next time even if cancelled
        if (fileInput.value) fileInput.value.value = "";
        return;
    }
    const reader = new FileReader();
    reader.onload = () => {
        uploadedImage.value = String(reader.result || "");
        cropperKey.value++; // Force cropper to re-render with new image
        // Reset input after successful load for next selection
        if (fileInput.value) fileInput.value.value = "";
    };
    reader.readAsDataURL(file);
}
// crop → emit File
function cropImage() {
    const canvas = cropper.value?.getCroppedCanvas?.({
        willReadFrequently: true,
    });
    if (!canvas) return;

    const mime = props.mime || "image/webp";
    const quality = typeof props.quality === "number" ? props.quality : 0.92;

    canvas.toBlob(
        (blob) => {
            if (!blob) return;
            // keep extension aligned with mime
            const ext = (mime.split("/")[1] || "webp").replace("jpeg", "jpg");
            const file = new File([blob], `image.${ext}`, { type: mime });
            emit("cropped", { file });
            closeModal();
        },
        mime,
        quality
    );
}

// tools
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

// ESC closes
function onKey(e) {
    if (e.key === "Escape" && isOpen.value) closeModal();
}
onMounted(() => document.addEventListener("keydown", onKey));
onBeforeUnmount(() => document.removeEventListener("keydown", onKey));
</script>

<template>
    <!-- Self-contained trigger (hidden in controlled mode) -->
    <button
        v-if="!controlled"
        type="button"
        :class="btnClass"
        @click="openModal"
    >
        {{ btnLabel }}
    </button>

    <!-- Hidden file input -->
    <input
        ref="fileInput"
        type="file"
        class="d-none"
        accept="image/*"
        @change="handleFileUpload"
    />

    <!-- Modal -->
    <teleport to="body">
        <transition name="fade">
            <div
                v-if="isOpen"
                class="fixed inset-0 z-[2001] bg-black/40 flex items-center justify-center"
                role="dialog"
                aria-modal="true"
                @click.self="closeModal"
            >
                <div
                    class="w-full max-w-4xl max-h-[92vh] bg-white rounded-xl shadow-2xl p-5 overflow-auto relative"
                >
                    <!-- Header -->
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <h2 class="text-xl font-semibold">
                            Upload and Crop Image
                        </h2>
                        <div class="flex items-center gap-2">
                            <button
                                class="btn btn-sm btn-outline-secondary"
                                @click="openPicker"
                            >
                                Change Image
                            </button>
                            <button
                                class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                                @click="closeModal"
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

                    <div class="grid grid-cols-12 gap-4">
                        <!-- Cropper -->
                        <div class="col-span-12 md:col-span-9">
                            <VueCropper
                                v-if="uploadedImage"
                                ref="cropper"
                                 :key="cropperKey"
                                :src="uploadedImage"
                                :aspect-ratio="aspectRatio"
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
                                style="
                                    width: 100%;
                                    max-width: 220px;
                                    height: 220px;
                                "
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
    </teleport>
</template>

<style scoped>

.daek .bg-white {
    background-color: #181818 !important;
}
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
