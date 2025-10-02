<script setup>
import { ref } from "vue";
import * as XLSX from "xlsx";
import { toast } from "vue3-toastify";

const props = defineProps({
  accept: {
    type: String,
    default:
      ".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel",
  },
  label: {
    type: String,
    default: "Import",
  },
});

const emit = defineEmits(["on-import"]);

const fileInput = ref(null);

const triggerFile = () => {
  fileInput.value.click();
};

const handleFile = async (e) => {
  const file = e.target.files[0];
  if (!file) return;

  try {
    const reader = new FileReader();
    reader.onload = (evt) => {
      const data = evt.target.result;
      const workbook = XLSX.read(data, { type: "binary" });

      // Take first sheet
      const firstSheet = workbook.SheetNames[0];
      const worksheet = workbook.Sheets[firstSheet];

      // Convert to JSON
      const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });

      // Emit data back to parent
      emit("on-import", jsonData);

      //  Reset input so selecting the same file again works
      fileInput.value.value = "";
    };

    reader.readAsBinaryString(file);
  } catch (err) {
    console.error("Import failed", err);
    toast.error("Import failed: " + err.message);
  }
};
</script>

<template>
  <div>
    <button
      class="btn btn-primary py-2 btn-sm rounded-pill px-4"
      @click="triggerFile"
    >
      {{ label }}
    </button>
    <input
      type="file"
      ref="fileInput"
      :accept="accept"
      class="d-none"
      @change="handleFile"
    />
  </div>
</template>
