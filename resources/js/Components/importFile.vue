<!-- ImportFile.vue -->
<script setup>
import { ref } from "vue";
import * as XLSX from "xlsx";
import { toast } from "vue3-toastify";

const props = defineProps({
  label: { type: String, default: "Import" },
  sampleHeaders: { type: Array, default: () => [] }, // custom columns
  sampleData: { type: Array, default: () => [] }, // sample rows
});

const emit = defineEmits(["on-import"]);

const fileInput = ref(null);
const modalId = `modalImport-${props.label.replace(/\s+/g, "-")}`;

// Trigger file selection
const triggerFile = () => {
  fileInput.value.click();
};

// Handle file import
const handleFile = async (e) => {
  const file = e.target.files[0];
  if (!file) return;

  try {
    const reader = new FileReader();
    reader.onload = (evt) => {
      const data = evt.target.result;
      const workbook = XLSX.read(data, { type: "binary" });
      const firstSheet = workbook.SheetNames[0];
      const worksheet = workbook.Sheets[firstSheet];
      const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });

      emit("on-import", jsonData);
      fileInput.value.value = "";
      closeModal();
    };
    reader.readAsBinaryString(file);
  } catch (err) {
    toast.error("Import failed: " + err.message);
  }
};

// Close modal
const closeModal = () => {
  const modalEl = document.getElementById(modalId);
  const modal =
    window.bootstrap?.Modal.getInstance(modalEl) ||
    new window.bootstrap.Modal(modalEl);
  modal.hide();
};

// Download sample
const downloadSample = () => {
  if (!props.sampleHeaders.length) {
    toast.warn("No sample format defined for this module.");
    return;
  }

  const rows = [props.sampleHeaders, ...props.sampleData];
  const csv = rows.map((r) => r.join(",")).join("\n");

  const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
  const link = document.createElement("a");
  link.href = URL.createObjectURL(blob);
  link.download = `${props.label}_Sample.csv`;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};
</script>

<template>
  <!-- Button to open modal -->
  <button class="btn btn-primary py-2 btn-sm rounded-pill px-4" data-bs-toggle="modal" :data-bs-target="`#${modalId}`">
    {{ label }}
  </button>

  <!-- Modal -->
  <div class="modal fade" :id="modalId" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
        <!-- Header with gradient background -->


        <div class="modal-header">
          <h5 class="modal-title fw-semibold">
            Import
          </h5>
          <button class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
            data-bs-dismiss="modal" aria-label="Close" title="Close">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24"
              stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Body with enhanced styling -->
        <div class="modal-body" style="padding: 2rem 1.5rem;">
          <!-- Guidelines section with icon -->
          <div class="d-flex align-items-start mb-4">
            <div class="flex-shrink-0 me-3">
              <div class="rounded-circle d-flex align-items-center justify-content-center"
                style="width: 48px; height: 48px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <span style="font-size: 1.5rem;">📋</span>
              </div>
            </div>
            <div class="flex-grow-1">
              <h6 class="fw-bold mb-3" style="color: #2d3748; font-size: 1.1rem;">Import Guidelines</h6>
              <ul class="list-unstyled mb-0">
                <li class="mb-2 d-flex align-items-start">
                  <span class="text-success me-2">✓</span>
                  <span style="color: #4a5568; font-size: 0.925rem;">File must be in <strong>CSV</strong> or
                    <strong>Excel</strong> format</span>
                </li>
                <li class="mb-2 d-flex align-items-start">
                  <span class="text-success me-2">✓</span>
                  <span style="color: #4a5568; font-size: 0.925rem;">Ensure column order matches the required
                    structure</span>
                </li>
                <li class="mb-0 d-flex align-items-start">
                  <span class="text-success me-2">✓</span>
                  <span style="color: #4a5568; font-size: 0.925rem;">Empty rows will be ignored automatically</span>
                </li>
              </ul>
            </div>
          </div>

          <!-- Action buttons -->
          <div class="row g-3">
            <div class="col-6">
              <button
                class="btn btn-primary w-100 rounded-pill d-flex align-items-center justify-content-center gap-2 py-2"
               
                @click="downloadSample" @mouseover="$event.target.style.transform = 'translateY(-2px)'"
                @mouseout="$event.target.style.transform = 'translateY(0)'">
                <i class="bi bi-download" style="font-size: 1rem;"></i>
                <span>Download Sample</span>
              </button>

            </div>
            <div class="col-6">
              <button class="btn btn-primary rounded-pill w-100 d-flex align-items-center justify-content-center gap-2 py-2 text-white"
               
                @click="triggerFile"
                @mouseover="$event.target.style.transform = 'translateY(-2px)'; $event.target.style.boxShadow = '0 8px 20px rgba(102, 126, 234, 0.4)'"
                @mouseout="$event.target.style.transform = 'translateY(0)'; $event.target.style.boxShadow = 'none'">
                <span style="font-size: 1rem;">📁</span>
                <span>Import File</span>
              </button>
            </div>
          </div>

          <!-- Info footer -->
          <div class="mt-4 pt-3 border-top">
            <p class="text-center mb-0 small" style="color: #a0aec0;">
              <span style="font-size: 1rem; vertical-align: middle;">💡</span>
              <span class="ms-1">Need help? Check our documentation for detailed instructions</span>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Hidden File Input -->
  <input type="file" ref="fileInput" accept=".csv, .xlsx, .xls" class="d-none" @change="handleFile" />
</template>

<style scoped>
/* Smooth modal animation */
.modal.fade .modal-dialog {
  transition: transform 0.3s ease-out;
}



/* Backdrop blur effect (optional - requires Bootstrap 5.2+) */
.modal-backdrop {
  backdrop-filter: blur(4px);
}
</style>
