<script setup>
import ImageCropperModal from "@/Components/ImageCropperModal.vue";
import { onMounted, reactive, ref, toRaw, watch } from "vue";
import Select from 'primevue/select';
import { useFormatters } from '@/composables/useFormatters'

const props = defineProps({ model: Object, formErrors: Object, isOnboarding: { type: Boolean, default: false } });
const emit = defineEmits(["save"]);

const { formatMoney, formatNumber, formatCurrencySymbol, dateFmt } = useFormatters()


const form = reactive({
  receipt_header: props.model?.receipt_header ?? "",
  receipt_footer: props.model?.receipt_footer ?? "",
  receipt_logo: props.model?.receipt_logo ?? null, // preview (URL or base64)
  receipt_logo_file: null, // file for backend
  show_qr: props.model?.show_qr ?? 1,
  tax_breakdown: props.model?.tax_breakdown ?? true,
  store_phone: props.model?.phone ?? "",
  store_name: props.model?.receipt_header ?? "Enter store name",
  address: props.model?.address ?? "xyz",
  customer_printer: props.model?.customer_printer ?? "",
  kot_printer: props.model?.kot_printer ?? "",
});

watch(form, () => emit("save", { step: 6, data: toRaw(form) }), { deep: true });

/* --- Image crop/zoom like Step 2 --- */
const showCropper = ref(false);

function onCropped({ file }) {
  form.receipt_logo_file = file;
  form.receipt_logo = URL.createObjectURL(file);
}


// Get All Connected Printers
const printers = ref([]);
const loadingPrinters = ref(false);

const fetchPrinters = async () => {
  loadingPrinters.value = true;
  try {
    const res = await axios.get("/api/printers");
    console.log("Printers:", res.data.data);

    // ✅ Only show connected printers (status OK)
    printers.value = res.data.data
      .filter(p => p.is_connected === true || p.status === "OK")
      .map(p => ({
        label: `${p.name}`,
        value: p.name,
        driver: p.driver,
        port: p.port,
      }));
  } catch (err) {
    console.error("Failed to fetch printers:", err);
  } finally {
    loadingPrinters.value = false;
  }
};

// 🔹 Fetch once on mount
onMounted(fetchPrinters);


</script>

<template>
  <div>
    <h5 class="fw-bold mb-3" v-if="props.isOnboarding">Step 6 of 9 - Receipt & Printer Setup</h5>

    <div class="row g-4">
      <!-- Left side -->
      <div class="col-lg-7">
        <!-- Receipt header -->
        <label class="form-label">Receipt header</label>
        <input class="form-control" v-model="form.receipt_header"
          :class="{ 'is-invalid': formErrors?.receipt_header }" />
        <small v-if="formErrors?.receipt_header" class="text-danger">
          {{ formErrors.receipt_header[0] }}
        </small>

        <div class="row g-3 align-items-start mt-2">
          <!-- Upload / crop card -->
          <div class="col-md-4">
            <small class="text-muted mt-2">Upload Receipt Logo</small>
            <div class="logo-card">
              <div class="logo-frame" @click="
                form.receipt_logo && openImageModal(form.receipt_logo)
                ">
                <img v-if="form.receipt_logo" :src="form.receipt_logo" alt="Logo" />
                <div v-else class="placeholder">
                  <i class="bi bi-image"></i>
                </div>
              </div>

              <ImageCropperModal :show="showCropper" @close="showCropper = false" @cropped="onCropped" />


            </div>
            <small v-if="formErrors?.receipt_logo_file" class="text-danger">
              {{ formErrors.receipt_logo_file[0] }}
            </small>
          </div>

          <!-- Text fields + radios -->
          <div class="col-md-8">
            <!-- Receipt footer -->
            <label class="form-label">Receipt Footer</label>
            <input class="form-control" v-model="form.receipt_footer"
              :class="{ 'is-invalid': formErrors?.receipt_footer }" />
            <small v-if="formErrors?.receipt_footer" class="text-danger">
              {{ formErrors.receipt_footer[0] }}
            </small>

            <!-- Show QR Code -->
            <div class="mt-3">
              <label class="form-label d-block mb-2">
                Show QR Code on Receipt
              </label>
              <div class="segmented">
                <input class="segmented__input" type="radio" id="qr-yes" :value="1"
                  :class="{ 'is-invalid': formErrors?.show_qr }" v-model="form.show_qr" />
                <label class="segmented__btn" :class="{ 'is-active': form.show_qr === 1 }" for="qr-yes">YES</label>

                <input class="segmented__input" type="radio" id="qr-no" :value="0" v-model="form.show_qr" />
                <label class="segmented__btn" :class="{ 'is-active': form.show_qr === 0 }" for="qr-no">NO</label>
                <small v-if="formErrors?.show_qr" class="text-danger">
                  {{ formErrors.show_qr[0] }}
                </small>
              </div>
            </div>

            <!-- Tax Breakdown -->
            <div class="mt-3">
              <label class="form-label d-block mb-2">Tax Breakdown</label>
              <div class="segmented">
                <input class="segmented__input" type="radio" id="tb-yes" :value="true" v-model="form.tax_breakdown" />
                <label class="segmented__btn" :class="{ 'is-active': form.tax_breakdown === true }"
                  for="tb-yes">YES</label>

                <input class="segmented__input" type="radio" id="tb-no" :value="false" v-model="form.tax_breakdown" />
                <label class="segmented__btn" :class="{ 'is-active': form.tax_breakdown === false }"
                  for="tb-no">NO</label>
              </div>
            </div>

            
            <div class="mt-3">
              <!-- Header with refresh button -->
              <div class="d-flex align-items-center justify-content-end mb-1">
                <button type="button"
                  class="btn btn-sm btn-outline-primary custom-refresh-btn d-flex align-items-center rounded-pill"
                  @click="fetchPrinters" :disabled="loadingPrinters">
                  <i class="pi pi-refresh me-1 px-1" :class="{ 'pi-spin': loadingPrinters }"></i>
                  {{ loadingPrinters ? "Refreshing..." : "Refresh Printers" }}
                </button>
              </div>

              <!-- Show loading message -->
              <div v-if="loadingPrinters" class="text-center py-3">
                <div class="fw-semibold text-secondary mb-2">
                  Scanning for printers, please wait...
                </div>
                <div class="spinner-border" role="status" style="color: #1c0d82; width: 2rem; height: 2rem;">
                  <span class="visually-hidden">Loading...</span>
                </div>
              </div>

              <!-- If no printers detected -->
              <div v-else-if="printers.length === 0" class="alert alert-warning py-2 px-3 rounded-3 mt-3">
                <i class="pi pi-exclamation-triangle me-2"></i>
                No printers detected. Please connect a printer and click "Refresh Printers".
              </div>

              <!-- If printers are available -->
              <div v-else>
                <!-- POS Printer -->
                <label class="form-label d-block mb-2">Customer Printer</label>
                <Select v-model="form.customer_printer" :options="printers" optionLabel="label" optionValue="value"
                  placeholder="Select POS Printer" :loading="loadingPrinters" class="w-100" />

                <!-- KOT Printer -->
                <label class="form-label d-block mt-3 mb-2">KOT Printer</label>
                <Select v-model="form.kot_printer" :options="printers" optionLabel="label" optionValue="value"
                  placeholder="Select KOT Printer" :loading="loadingPrinters" class="w-100" />
              </div>
            </div>



            <!-- Printers error -->
            <small v-if="formErrors?.printers" class="text-danger">
              {{ formErrors.printers[0] }}
            </small>
          </div>
        </div>
      </div>


      <div class="col-lg-5">
        <div class="receipt-card">
          <div class="text-center">
            <!-- <div class="text-center small"> {{ form.receipt_header }} </div> -->
            <div v-if="form.receipt_logo" class="mb-2 logo-container">
              <img :src="form.receipt_logo" alt class="logo-preview rounded-circle" />
            </div>

            <h6 class="mb-1">{{ form.receipt_header }}</h6>
             <div class="text-center my-2">
              <!-- class="badge-pill" -->
              <div >{{ form.store_phone }}</div>
            </div>
             <div class="text-center my-2">
              <div >{{ form.address }}</div>
            </div>
            <div class="text-muted small"> {{ new Date().toLocaleString() }} </div>
           
          </div>
          <hr class="my-2" />
          <div class="small">
            <div class="d-flex justify-content-between"> <span>Payment Type:</span><span>Credit</span> </div>
            <div class="d-flex justify-content-between"> <span>Customer Name:</span><span>Lorem Ipsum</span> </div>
            <div class="d-flex justify-content-between"> <span>Address:</span><span>abc</span> </div>
          </div>
          <hr class="my-2" />
          <div class="small">
            <div class="d-flex justify-content-between fw-semibold"> <span>Name</span><span>Qty</span><span>Price</span>
            </div>
            <div class="d-flex justify-content-between"> <span>Item A</span><span>1</span><span>{{ formatCurrencySymbol(950, 'PKR') }}</span> </div>
            <div class="d-flex justify-content-between"> <span>Item B</span><span>2</span><span>{{ formatCurrencySymbol(50, 'Rs') }}</span> </div>
          </div>
          <hr class="my-2" />
          <div class="small">
            <div class="d-flex justify-content-between"> <span>Total Price:</span><span>{{ formatCurrencySymbol(2000, 'Rs') }}</span> </div>
            <div class="d-flex justify-content-between"> <span>Tax:</span><span>23%</span> </div>
          </div>
          <div v-if="form.show_qr === 1" class="d-flex justify-content-center my-2">
            <div class="qr-box" aria-hidden="true"></div>
          </div>
          <hr class="my-2" />
          <div class="text-center small"> {{ form.receipt_footer || "Thank you!" }} </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
:root {
  --brand: #1c0d82;
}

.custom-refresh-btn {
  background: #1c0d82;
  color: #fff;
  padding: 6px 10px;
}

.dark .logo-frame {
  background-color: #121212;
  color: #fff !important;
}

/* -------- Match Step 5 segmented style -------- */
.segmented {
  display: inline-flex;
  border-radius: 999px;
  background: #f4f6fb;
  border: 1px solid #e3e8f2;
  box-shadow: 0 2px 6px rgba(25, 28, 90, 0.05);
  overflow: hidden;
}

.dark .segmented {
  background-color: #121212 !important;
  color: #fff !important;
}

.segmented__input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.segmented__btn {
  padding: 0.3rem 0.8rem;
  font-size: 0.8rem;
  color: #2b2f3b;
  background: transparent;
  cursor: pointer;
  user-select: none;
  transition: all 0.15s ease;
}

.dark .segmented__btn {
  color: #fff !important;
}

.segmented__btn:hover {
  background: rgba(28, 13, 130, 0.08);
}

.segmented__btn.is-active {
  background: #1c0d82;
  color: #fff;
  box-shadow: 0 2px 6px rgba(28, 13, 130, 0.25);
}

.segmented__btn:active {
  transform: translateY(1px);
}

/* -------- Upload card -------- */
.logo-card {
  text-align: center;
}

.dark .logo-card {
  background-color: #121212 !important;
}

.logo-box {
  width: 140px;
  height: 140px;
  border-radius: 16px;
  border: 2px dashed #d9deea;
  background: #fafbff;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
  transition: border-color 0.15s ease, background 0.15s ease;
}

.logo-box:hover {
  border-color: #c6cbe1;
  background: #f7f8ff;
}

.logo-box img {
  max-width: 100%;
  max-height: 100%;
  border-radius: 12px;
}

.logo-empty {
  color: #9aa3b2;
  font-size: 28px;
}

.logo-box--has {
  border-style: solid;
  border-color: #e7ebf5;
  background: #fff;
}

/* -------- Receipt preview -------- */
.receipt-card {
  background: #fff;
  border: 1px solid #edf0f6;
  border-radius: 16px;
  padding: 16px;
  box-shadow: 0 6px 20px rgba(17, 38, 146, 0.06);
}

.dark .receipt-card {
  background-color: #121212 !important;
  color: #ffffff !important;
}

.dark input {
  background-color: #121212 !important;
  color: #ffffff !important;
}

.badge-pill {
  border: 1px solid #e3e7f2;
  border-radius: 999px;
  padding: 0.35rem 0.9rem;
  display: inline-block;
}

.logo-preview {
  max-height: 48px;
  width: auto;
}

/* Simple QR placeholder */
.qr-box {
  width: 84px;
  height: 84px;
  border: 4px solid #222;
  background: repeating-linear-gradient(90deg,
      #222 0 6px,
      transparent 6px 12px),
    repeating-linear-gradient(0deg, #222 0 6px, transparent 6px 12px);
  background-blend-mode: multiply;
}

/* Compact labels */
.form-label {
  margin-bottom: 0.35rem;
}

.logo-frame {
  width: 140px;
  height: 140px;
  border-radius: 1rem;
  border: 1px dashed #cfd6e4;
  background: #f7f9fc;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  cursor: pointer;
}

.logo-frame img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}

.logo-frame .placeholder {
  color: #8b97a7;
  font-size: 28px;
  line-height: 0;
}

.dial-select {
  min-width: 130px;
  border: 0;
}

.logo-container {
  text-align: center;
}

.logo-preview {
  max-width: 120px;
  /* optional: keeps it small */
  display: inline-block;
  border-radius: 0.5rem;
}

:deep(.p-select) {
  background-color: white !important;
  color: black !important;
  border-color: #9b9c9c;
}

/* Options container */
:deep(.p-select-list-container) {
  background-color: white !important;
  color: black !important;
}

/* Each option */
:deep(.p-select-option) {
  background-color: transparent !important;
  /* instead of 'none' */
  color: black !important;
}

/* Hovered option */
:deep(.p-select-option:hover) {
  background-color: #f0f0f0 !important;
  color: black !important;
}

/* Focused option (when using arrow keys) */
:deep(.p-select-option.p-focus) {
  background-color: #f0f0f0 !important;
  color: black !important;
}

:deep(.p-select-label) {
  color: #181818 !important;
}

:deep(.p-placeholder) {
  color: #80878e !important;
}

.dark .form-control {
  background-color: #121212 !important;
  color: #fff !important;
}
</style>
