<script setup>
import {
  ref,
  watch,
  onMounted,
  onBeforeUnmount,
  computed,
  nextTick,
} from "vue";
// import * as bootstrap from "bootstrap/dist/js/bootstrap.bundle.min.js";
import VueCropper from "vue-cropperjs";
import "cropperjs/dist/cropper.css";

const props = defineProps({
  show: { type: Boolean, default: false },
  image: { type: String, default: "" }, // optional initial image
  // optional: choose side -> "start" | "end" | "top" | "bottom"
  placement: { type: String, default: "start" },
  // optional: id for the offcanvas
  offcanvasId: { type: String, default: "cropperOffcanvas" },
});
const emit = defineEmits(["close", "cropped"]);

const cropper = ref(null);
const fileInput = ref(null);
const uploadedImage = ref(props.image || null);
const scaleX = ref(-1);
const scaleY = ref(-1);
const hasImage = computed(() => !!uploadedImage.value);

function openPicker() {
  if (fileInput.value) fileInput.value.value = "";
  fileInput.value?.click();
}

function clearState() {
  uploadedImage.value = null;
  scaleX.value = -1;
  scaleY.value = -1;
}

watch(
  () => props.show,
  async (v) => {
    await nextTick();
    if (!bsOffcanvas.value) return;
    if (v) {
      // prompt for a new image when opened
      bsOffcanvas.value.show();
      openPicker();
    } else {
      bsOffcanvas.value.hide();
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
  const dataUrl = c.toDataURL("image/webp", 0.92);
  emit("cropped", dataUrl);
  closeOffcanvas(); // also clears state & emits close
}

function closeOffcanvas() {
  clearState();
  // hide via bootstrap API to keep transitions consistent
  bsOffcanvas.value?.hide();
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

// Bootstrap offcanvas instance
const offcanvasEl = ref(null);
const bsOffcanvas = ref(null);

onMounted(() => {
  if (offcanvasEl.value) {
    bsOffcanvas.value = new bootstrap.Offcanvas(offcanvasEl.value, {
      backdrop: true,  // dim background (Bootstrap manages this)
      scroll: true,    // allow background scroll inside body
      keyboard: true,  // ESC to close
    });

    // Keep Vue state in sync if user closes with ESC or the "X" button
    offcanvasEl.value.addEventListener("hidden.bs.offcanvas", () => {
      clearState();
      emit("close");
    });

    // Open initially if show===true
    if (props.show) {
      bsOffcanvas.value.show();
      openPicker();
    }
  }

  // Optional: manual ESC handler not needed because Offcanvas handles it.
});

onBeforeUnmount(() => {
  // Clean up listeners
  offcanvasEl.value?.removeEventListener?.("hidden.bs.offcanvas", () => {});
  bsOffcanvas.value = null;
});
</script>

<template>
  <!-- Hidden file input -->
  <input
    ref="fileInput"
    type="file"
    class="hidden"
    accept="image/*"
    @change="handleFileUpload"
  />

  <!-- Offcanvas container -->
  <div
    :id="offcanvasId"
    ref="offcanvasEl"
    class="offcanvas"
    :class="`offcanvas-${placement}`"
    tabindex="-1"
    aria-labelledby="offcanvasExampleLabel"
    style="--bs-offcanvas-width: min(100vw, 920px);"   
  >
    <!-- Header -->
    <div class="offcanvas-header">
      <h5 class="offcanvas-title" id="offcanvasExampleLabel">
        Upload and Crop Image
      </h5>
      <button
        type="button"
        class="btn-close text-reset"
        data-bs-dismiss="offcanvas"
        aria-label="Close"
        title="Close"
      ></button>
    </div>

    <!-- Body -->
    <div class="offcanvas-body">
      <!-- Actions row (top-right) -->
      <div class="flex items-center justify-end gap-2 mb-4">
        <button class="btn btn-outline-secondary" @click="openPicker">
          Change Image
        </button>
      </div>

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
          <label class="block text-sm font-medium mb-2">Preview</label>
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

      <!-- Footer actions -->
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
</template>

<style scoped>
/* no modal fade needed; Bootstrap handles offcanvas transitions */
</style>
