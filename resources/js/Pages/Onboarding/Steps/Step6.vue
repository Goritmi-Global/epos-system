<script setup>
import ImageCropperModal from "@/Components/ImageCropperModal.vue";
import { onMounted, onUnmounted, reactive, ref, toRaw, watch } from "vue";
import Select from 'primevue/select';
import { useFormatters } from '@/composables/useFormatters'

const props = defineProps({ model: Object, formErrors: Object, isOnboarding: { type: Boolean, default: false } });
const emit = defineEmits(["save"]);

const { formatMoney, formatNumber, formatCurrencySymbol, dateFmt } = useFormatters()

const form = reactive({
  receipt_header: props.model?.receipt_header ?? props.model?.business_name ?? "Enter store name",
  receipt_footer: props.model?.receipt_footer ?? "Thank You for Taste Us",
  receipt_logo: props.model?.receipt_logo_url ?? props.model?.logo_url ?? null,
  receipt_logo_file: null,
  show_qr: props.model?.show_qr ?? 1,
  tax_breakdown: props.model?.tax_breakdown ?? true,
  store_phone: props.model?.phone ?? "",
  store_name: props.model?.store_name ?? props.model?.business_name ?? "Enter store name",
  address: props.model?.address ?? "xyz",
  customer_printer: props.model?.customer_printer ?? "browser",
  kot_printer: props.model?.kot_printer ?? "browser",
});

watch(form, () => emit("save", { step: 6, data: toRaw(form) }), { deep: true, immediate: true });

/* --- Image crop/zoom like Step 2 --- */
const showCropper = ref(false);

function openCropper() {
  showCropper.value = true;
}

function onCropped({ file }) {
  form.receipt_logo_file = file;
  form.receipt_logo = URL.createObjectURL(file);
  showCropper.value = false;
}

// ============================================
// CLIENT-SIDE PRINTER DETECTION (WebUSB)
// ============================================

const printers = ref([]);
const loadingPrinters = ref(false);
const webUSBSupported = ref(false);
const connectedUSBPrinter = ref(null);
const printerError = ref(null);

// Common thermal printer vendor IDs
const vendorIds = [
  0x0483, // STMicroelectronics
  0x0416, // Winbond
  0x0419, // Samsung
  0x04B8, // Epson
  0x0525, // PLX Technology
  0x067B, // Prolific
  0x1504, // Black Copper
  0x1A86, // QinHeng Electronics (CH340)
  0x0DD4, // Custom Engineering
  0x0FE6, // ICS Electronics
  0x20D1, // Chinese printers
  0x154F, // SNBC
];

// Check WebUSB support
const checkWebUSBSupport = () => {
  webUSBSupported.value = 'usb' in navigator;
  return webUSBSupported.value;
};

// Get already paired USB devices
const getPairedDevices = async () => {
  if (!webUSBSupported.value) return [];
  
  try {
    const devices = await navigator.usb.getDevices();
    return devices.filter(device =>
      vendorIds.includes(device.vendorId) ||
      device.productName?.toLowerCase().includes('printer') ||
      device.productName?.toLowerCase().includes('pos') ||
      device.productName?.toLowerCase().includes('thermal')
    );
  } catch (error) {
    console.error('Error getting USB devices:', error);
    return [];
  }
};

// Request new USB printer connection
const requestUSBPrinter = async () => {
  if (!webUSBSupported.value) {
    printerError.value = 'WebUSB is not supported in this browser. Please use Chrome or Edge.';
    return null;
  }

  try {
    printerError.value = null;
    const filters = vendorIds.map(vendorId => ({ vendorId }));
    const device = await navigator.usb.requestDevice({ filters });
    
    if (device) {
      connectedUSBPrinter.value = {
        name: device.productName || 'USB Thermal Printer',
        manufacturer: device.manufacturerName || 'Unknown',
        vendorId: device.vendorId,
        productId: device.productId,
        device: device
      };
      
      // Update printers list
      await fetchPrinters();
      
      return device;
    }
  } catch (error) {
    if (error.name !== 'NotFoundError') {
      printerError.value = 'Failed to connect: ' + error.message;
      console.error('USB request error:', error);
    }
  }
  return null;
};

// Fetch/refresh printers list
const fetchPrinters = async () => {
  loadingPrinters.value = true;
  printerError.value = null;
  
  try {
    // Always include browser print option
    const printerList = [
      {
        label: '🖨 Browser Print Dialog',
        value: 'browser',
        driver: 'Browser',
        port: 'System',
        type: 'browser'
      }
    ];

    // Check for WebUSB support and get paired devices
    if (checkWebUSBSupport()) {
      const usbDevices = await getPairedDevices();
      
      for (const device of usbDevices) {
        printerList.push({
          label: '🔌 ' + (device.productName || 'USB Printer'),
          value: 'usb_' + device.vendorId + '_' + device.productId,
          driver: device.manufacturerName || 'USB',
          port: 'USB',
          type: 'usb',
          device: device
        });
      }
    }

    printers.value = printerList;

    // Auto-select USB printer if available and nothing selected
    if (printers.value.length > 1 && form.customer_printer === 'browser') {
      const usbPrinter = printers.value.find(p => p.type === 'usb');
      if (usbPrinter) {
        form.customer_printer = usbPrinter.value;
        form.kot_printer = usbPrinter.value;
      }
    }

  } catch (err) {
    console.error("Failed to fetch printers:", err);
    printerError.value = 'Failed to detect printers: ' + err.message;
  } finally {
    loadingPrinters.value = false;
  }
};

// Connect new USB printer
const connectNewPrinter = async () => {
  loadingPrinters.value = true;
  try {
    await requestUSBPrinter();
  } finally {
    loadingPrinters.value = false;
  }
};

// Store printer config in localStorage for use during printing
const savePrinterConfig = () => {
  const config = {
    customer_printer: form.customer_printer,
    kot_printer: form.kot_printer,
    printers: printers.value.map(p => ({
      label: p.label,
      value: p.value,
      type: p.type,
      vendorId: p.device?.vendorId,
      productId: p.device?.productId
    }))
  };
  localStorage.setItem('pos_printer_config', JSON.stringify(config));
};

// Watch for printer selection changes
watch(() => [form.customer_printer, form.kot_printer], () => {
  savePrinterConfig();
}, { deep: true });

onMounted(() => {
  checkWebUSBSupport();
  fetchPrinters();
  
  // Load saved config
  const savedConfig = localStorage.getItem('pos_printer_config');
  if (savedConfig) {
    try {
      const config = JSON.parse(savedConfig);
      if (config.customer_printer) form.customer_printer = config.customer_printer;
      if (config.kot_printer) form.kot_printer = config.kot_printer;
    } catch (e) {
      console.error('Error loading printer config:', e);
    }
  }
});
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
          <div class="col-md-4 upload-image-area">
            <small class="text-muted mt-2">Upload Receipt Logo</small>
            <div class="logo-card">
              <div class="logo-frame" @click="openCropper()">
                <img v-if="form.receipt_logo" :src="form.receipt_logo" alt="Receipt Logo" class="receipt-logo-preview" />
                <div v-else class="placeholder">
                  <i class="bi bi-image"></i>
                </div>
              </div>

              <ImageCropperModal :show="showCropper" @close="showCropper = false" @cropped="onCropped" />

              <small class="d-block text-center mt-2 text-muted">Click to upload/change logo</small>
            </div>
            <small v-if="formErrors?.receipt_logo_file" class="text-danger">
              {{ formErrors.receipt_logo_file[0] }}
            </small>
          </div>

          <!-- Text fields + radios -->
          <div class="col-md-8 right-side-receipt-area">
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
                  :class="{ 'is-invalid': formErrors?.show_qr }" v-model.number="form.show_qr" />
                <label class="segmented__btn" :class="{ 'is-active': form.show_qr === 1 }" for="qr-yes">YES</label>

                <input class="segmented__input" type="radio" id="qr-no" :value="0" v-model.number="form.show_qr" />
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

            <!-- Printer Section -->
            <div class="mt-3">
              <!-- WebUSB Support Status -->
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div>
                  <span v-if="webUSBSupported" class="badge bg-success me-2">
                    <i class="bi bi-check-circle me-1"></i> Direct USB Supported
                  </span>
                  <span v-else class="badge bg-warning text-dark me-2">
                    <i class="bi bi-exclamation-triangle me-1"></i> Browser Print Only
                  </span>
                </div>
                <div class="d-flex gap-2">
                  <button 
                    v-if="webUSBSupported"
                    type="button"
                    class="btn btn-sm btn-success custom-connect-btn d-flex align-items-center rounded-pill"
                    @click="connectNewPrinter" 
                    :disabled="loadingPrinters">
                    <i class="bi bi-usb-plug me-1"></i>
                    Connect USB Printer
                  </button>
                  <button 
                    type="button"
                    class="btn btn-sm btn-outline-primary custom-refresh-btn d-flex align-items-center rounded-pill"
                    @click="fetchPrinters" 
                    :disabled="loadingPrinters">
                    <i class="pi pi-refresh me-1 px-1" :class="{ 'pi-spin': loadingPrinters }"></i>
                    {{ loadingPrinters ? "Refreshing..." : "Refresh" }}
                  </button>
                </div>
              </div>

              <!-- Error Message -->
              <div v-if="printerError" class="alert alert-danger py-2 px-3 rounded-3 mb-2">
                <i class="bi bi-exclamation-circle me-2"></i>
                {{ printerError }}
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

              <!-- Printer Selection -->
              <div v-else>
                <!-- Info about WebUSB -->
                <div v-if="!webUSBSupported" class="alert alert-info py-2 px-3 rounded-3 mb-3">
                  <i class="bi bi-info-circle me-2"></i>
                  <strong>Tip:</strong> For direct USB thermal printing, use Chrome or Edge browser.
                  Currently using browser print dialog.
                </div>

                <!-- Connected USB Printer Info -->
                <div v-if="printers.some(p => p.type === 'usb')" class="alert alert-success py-2 px-3 rounded-3 mb-3">
                  <i class="bi bi-check-circle me-2"></i>
                  <strong>USB Printer Connected!</strong> Direct thermal printing available.
                </div>

                <!-- Customer Printer -->
                <label class="form-label d-block mb-2">Customer Receipt Printer</label>
                <Select 
                  v-model="form.customer_printer" 
                  :options="printers" 
                  optionLabel="label" 
                  optionValue="value"
                  placeholder="Select Customer Printer" 
                  :loading="loadingPrinters" 
                  class="w-100" 
                />
                <small class="text-muted">Used for printing customer receipts</small>

                <!-- KOT Printer -->
                <label class="form-label d-block mt-3 mb-2">Kitchen (KOT) Printer</label>
                <Select 
                  v-model="form.kot_printer" 
                  :options="printers" 
                  optionLabel="label" 
                  optionValue="value"
                  placeholder="Select KOT Printer" 
                  :loading="loadingPrinters" 
                  class="w-100" 
                />
                <small class="text-muted">Used for printing kitchen order tickets</small>
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
            <!-- Receipt Logo Preview -->
            <div v-if="form.receipt_logo" class="mb-3 receipt-logo-container">
              <img :src="form.receipt_logo" alt="Receipt Logo" class="receipt-logo-display" />
            </div>
         
            <h6 class="mb-1">{{ form.receipt_header }}</h6>
            <div class="text-center my-2">
              <div class="text-muted small">{{ form.store_phone }}</div>
            </div>
            <div class="text-center my-2">
              <div class="text-muted small">{{ form.address }}</div>
            </div>

          </div>
          <hr class="my-2" />
          <div class="small">
            <div class="d-flex justify-content-between"> <span>Date:</span><span> {{ new
              Date().toLocaleDateString().replace(/\//g, '-') }}</span> </div>
            <div class="d-flex justify-content-between"> <span>Time:</span><span> {{ new Date().toLocaleTimeString()
                }}</span> </div>
            <div class="d-flex justify-content-between"> <span>Customer Name:</span><span>John Snow</span> </div>
            <div class="d-flex justify-content-between"> <span>Order Type:</span><span>Dine In</span> </div>
            <div class="d-flex justify-content-between"> <span>Note:</span><span>One Cheese Burger Please</span> </div>
            <div class="d-flex justify-content-between"> <span>Payment Type:</span><span>Cash/Card</span> </div>
          </div>
          <hr class="my-2" />
          <div class="small">
            <div class="d-flex justify-content-between fw-semibold"> <span>Name</span><span>Qty</span><span>Price</span>
            </div>
            <div class="d-flex justify-content-between"> <span>Item A</span><span>1</span><span>{{ props.model?.currency
              ?? "£" }} 950 </span> </div>
            <div class="d-flex justify-content-between"> <span>Item B</span><span>2</span><span>{{ props.model?.currency
              ?? "£" }} 50</span> </div>
          </div>
          <hr class="my-2" />
          <div class="small">
            <div class="d-flex justify-content-between"> <span>Total Price:</span><span>{{ props.model?.currency ??
              "£" }} 2000</span> </div>
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

.custom-refresh-btn:hover {
  background: #150a66;
  color: #fff;
}

.custom-connect-btn {
  padding: 6px 12px;
}

.dark .logo-frame {
  background-color: #121212;
  color: #fff !important;
}

/* -------- Segmented style -------- */
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

/* -------- Logo Upload Card -------- */
.logo-card {
  text-align: center;
}

.dark .logo-card {
  background-color: #121212 !important;
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
  transition: all 0.2s ease;
  margin: 0 auto;
}

.logo-frame:hover {
  border-color: #1c0d82;
  background: #f0f3ff;
}

.logo-frame .receipt-logo-preview {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  padding: 4px;
  border-radius: 20px;
}

.logo-frame .placeholder {
  color: #8b97a7;
  font-size: 36px;
  line-height: 0;
}

/* -------- Receipt Preview Card -------- */
.receipt-card {
  background: #fff;
  border: 1px solid #edf0f6;
  border-radius: 16px;
  padding: 16px;
  box-shadow: 0 6px 20px rgba(17, 38, 146, 0.06);
  font-family: monospace;
  font-size: 0.85rem;
}

.dark .receipt-card {
  background-color: #121212 !important;
  color: #ffffff !important;
}

.dark input {
  background-color: #121212 !important;
  color: #ffffff !important;
}

.receipt-logo-container {
  text-align: center;
}

.receipt-logo-display {
  max-width: 100px;
  max-height: 80px;
  object-fit: contain;
  border-radius: 50%;
  display: inline-block;
}

/* -------- QR Box -------- */
.qr-box {
  width: 84px;
  height: 84px;
  border: 4px solid #222;
  background: repeating-linear-gradient(90deg,
      #222 0 6px,
      transparent 6px 12px),
    repeating-linear-gradient(0deg, #222 0 6px, transparent 6px 12px);
  background-blend-mode: multiply;
  margin: 0 auto;
}

/* -------- Form Elements -------- */
.form-label {
  margin-bottom: 0.35rem;
  font-weight: 500;
}

.dial-select {
  min-width: 130px;
  border: 0;
}

:deep(.p-select) {
  background-color: white !important;
  color: black !important;
  border-color: #9b9c9c;
}

:deep(.p-select-list-container) {
  background-color: white !important;
  color: black !important;
}

:deep(.p-select-option) {
  background-color: transparent !important;
  color: black !important;
}

:deep(.p-select-option:hover) {
  background-color: #f0f0f0 !important;
  color: black !important;
}

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

.text-muted {
  color: #6c757d;
}

.dark .text-muted {
  color: #999 !important;
}

/* Badge styles */
.badge {
  font-size: 0.75rem;
  padding: 0.35em 0.65em;
}

/* Alert styles */
.alert {
  font-size: 0.85rem;
}

/* For screens between 1024px and 1366px */
@media only screen and (min-width: 1024px) and (max-width: 1366px) {
    .upload-image-area,
    .right-side-receipt-area {
        flex: 0 0 50% !important;
        max-width: 50% !important;
    }
}
</style>