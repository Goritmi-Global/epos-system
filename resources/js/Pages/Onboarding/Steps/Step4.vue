<script setup>
import { reactive, toRaw, watch, ref } from "vue";
import Select from "primevue/select"; // PrimeVue Select

const props = defineProps({ model: Object, formErrors: Object, isOnboarding: { type: Boolean, default: false } });
const emit = defineEmits(["save"]);

const form = reactive({
  tax_registered: props.model.tax_registered ?? 1,
  tax_type: props.model.tax_type ?? "VAT",
  tax_rate: props.model.tax_rate ?? 0,
  price_includes_tax: props.model.price_includes_tax ? 1 : 0,
  tax_id: props.model.tax_id ?? null,
  extra_tax_rates: props.model.extra_tax_rates ?? "",

  has_service_charges: props.model.has_service_charges ? 1 : 0,
  service_charge_flat: props.model.service_charge_flat ?? null,
  service_charge_percentage: props.model.service_charge_percentage ?? null,

  has_delivery_charges: props.model.has_delivery_charges ? 1 : 0,
  delivery_charge_flat: props.model.delivery_charge_flat ?? null,
  delivery_charge_percentage: props.model.delivery_charge_percentage ?? null,
});



watch(form, () => {
  const payload = {
    step: 4,
    data: {
      tax_registered: Number(form.tax_registered),
      price_includes_tax: Number(form.price_includes_tax),


      has_service_charges: Number(form.has_service_charges),
      has_delivery_charges: Number(form.has_delivery_charges),
    },
  }

  if (Number(form.tax_registered) === 1) {
    payload.data.tax_type = form.tax_type;
    payload.data.tax_rate = form.tax_rate;
    payload.data.tax_id = form.tax_id;
    payload.data.extra_tax_rates = form.extra_tax_rates || "";
  } else {
    payload.data.tax_type = "";
    payload.data.tax_rate = 0;
    payload.data.tax_id = "";
    payload.data.extra_tax_rates = 0;
  }

  if (Number(form.has_service_charges) === 1) {
    payload.data.service_charge_flat = form.service_charge_flat || null;
    payload.data.service_charge_percentage = form.service_charge_percentage || null;
  } else {
    payload.data.service_charge_flat = null;
    payload.data.service_charge_percentage = null;
  }

  if (Number(form.has_delivery_charges) === 1) {
    payload.data.delivery_charge_flat = form.delivery_charge_flat || null;
    payload.data.delivery_charge_percentage = form.delivery_charge_percentage || null;
  } else {
    payload.data.delivery_charge_flat = null;
    payload.data.delivery_charge_percentage = null;
  }

  emit("save", payload);
}, { deep: true, immediate: true });

// Tax types for Select
const taxTypeOptions = ref([
  { name: "VAT", code: "VAT" },
  { name: "GST", code: "GST" },
  { name: "Sales Tax", code: "Sales Tax" },
]);
</script>

<template>
  <div>
    <h5 class="fw-bold mb-4" v-if="props.isOnboarding">Step 4 of 9 - Tax & VAT Setup</h5>

    <!-- Tax Registered -->
    <div class="mb-3">
      <label class="form-label d-block mb-2">Is your business tax registered?</label>
      <div class="segmented">
        <input type="radio" id="tax-yes" :value="1" v-model.number="form.tax_registered" class="segmented__input" />
        <label for="tax-yes" class="segmented__btn" :class="{ 'is-active': form.tax_registered === 1 }">YES</label>

        <input type="radio" id="tax-no" :value="0" v-model.number="form.tax_registered" class="segmented__input" />
        <label for="tax-no" class="segmented__btn" :class="{ 'is-active': form.tax_registered === 0 }">NO</label>
      </div>
    </div>

    <!-- If registered -->
    <div v-if="form.tax_registered === 1" class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Tax Type</label>
        <Select v-model="form.tax_type" :options="taxTypeOptions" optionLabel="name" optionValue="code" :pt="{
          root: { class: 'bg-white text-black' },
          label: { class: 'text-black' },
          listContainer: { class: 'bg-white text-black' },
          option: { class: 'text-black hover:bg-gray-100' },
          header: { class: 'bg-white text-black' },
          IconField: { class: 'bg-white' },
          InputText: { class: 'bg-white' },
          pcFilter: { class: 'bg-white' },
          pcFilterContainer: { class: 'bg-white' }
        }" placeholder="Select Tax Type" class="w-100" :class="{ 'is-invalid': formErrors?.tax_type }" />

        <small v-if="formErrors?.tax_type" class="text-danger">
          {{ formErrors.tax_type[0] }}
        </small>
      </div>

      <div class="col-md-3">
        <label class="form-label">Tax Rate (%)</label>
        <input type="number" class="form-control" min="0" max="100" v-model.number="form.tax_rate"
          :class="{ 'is-invalid': formErrors?.tax_rate }" />
        <small v-if="formErrors?.tax_rate" class="text-danger">
          {{ formErrors.tax_rate[0] }}
        </small>
      </div>

      <div class="col-md-3">
        <label class="form-label">Tax ID</label>
        <input :class="{ 'is-invalid': formErrors?.tax_id }" class="form-control" v-model="form.tax_id" />
        <small v-if="formErrors?.tax_id" class="text-danger">
          {{ formErrors.tax_id[0] }}
        </small>
      </div>
    </div>

    <!-- Extra Tax Rates -->
    <div v-if="form.tax_registered === 1" class="mt-3">
      <label class="form-label">Extra Tax Rates</label>
      <input class="form-control" v-model="form.extra_tax_rates" placeholder="e.g. 5%"
        :class="{ 'is-invalid': formErrors?.extra_tax_rates }" />
      <small v-if="formErrors?.extra_tax_rates" class="text-danger">
        {{ formErrors.extra_tax_rates[0] }}
      </small>
    </div>

    <!-- Price includes tax -->
    <div class="mt-3">
      <label class="form-label d-block mb-2">Price includes tax?</label>
      <div class="segmented">
        <input type="radio" id="price-yes" :value="1" v-model="form.price_includes_tax" class="segmented__input" />
        <label for="price-yes" class="segmented__btn"
          :class="{ 'is-active': form.price_includes_tax === 1 }">YES</label>

        <input type="radio" id="price-no" :value="0" v-model="form.price_includes_tax" class="segmented__input" />
        <label for="price-no" class="segmented__btn" :class="{ 'is-active': form.price_includes_tax === 0 }">NO</label>
      </div>
    </div>

    <!-- ============================================ -->
    <!-- ADD THIS ENTIRE SECTION BELOW (Service Charges) -->
    <!-- ============================================ -->
    <div class="mt-4">
      <label class="form-label d-block mb-2">Is there any Service Charges?</label>
      <div class="segmented">
        <input type="radio" id="service-yes" :value="1" v-model.number="form.has_service_charges" class="segmented__input" />
        <label for="service-yes" class="segmented__btn" :class="{ 'is-active': form.has_service_charges === 1 }">YES</label>

        <input type="radio" id="service-no" :value="0" v-model.number="form.has_service_charges" class="segmented__input" />
        <label for="service-no" class="segmented__btn" :class="{ 'is-active': form.has_service_charges === 0 }">NO</label>
      </div>
    </div>

    <!-- Service Charges Fields (shown only if Yes) -->
    <div v-if="form.has_service_charges === 1" class="row g-3 mt-2">
      <div class="col-md-6">
        <label class="form-label">Flat Amount (Optional)</label>
        <input 
          type="number" 
          class="form-control" 
          min="0" 
          step="0.01"
          v-model.number="form.service_charge_flat"
          placeholder="e.g. 50"
          :class="{ 'is-invalid': formErrors?.service_charge_flat }" 
        />
        <small v-if="formErrors?.service_charge_flat" class="text-danger">
          {{ formErrors.service_charge_flat[0] }}
        </small>
      </div>

      <div class="col-md-6">
        <label class="form-label">Percentage (Optional)</label>
        <input 
          type="number" 
          class="form-control" 
          min="0" 
          max="100"
          step="0.01"
          v-model.number="form.service_charge_percentage"
          placeholder="e.g. 10%"
          :class="{ 'is-invalid': formErrors?.service_charge_percentage }" 
        />
        <small v-if="formErrors?.service_charge_percentage" class="text-danger">
          {{ formErrors.service_charge_percentage[0] }}
        </small>
      </div>
      <div class="col-12">
        <small class="text-muted">* You can enter either flat amount or percentage, not both required.</small>
      </div>
    </div>

    <!-- ============================================ -->
    <!-- ADD THIS ENTIRE SECTION BELOW (Delivery Charges) -->
    <!-- ============================================ -->
    <div class="mt-4">
      <label class="form-label d-block mb-2">Is there any Delivery Charges?</label>
      <div class="segmented">
        <input type="radio" id="delivery-yes" :value="1" v-model.number="form.has_delivery_charges" class="segmented__input" />
        <label for="delivery-yes" class="segmented__btn" :class="{ 'is-active': form.has_delivery_charges === 1 }">YES</label>

        <input type="radio" id="delivery-no" :value="0" v-model.number="form.has_delivery_charges" class="segmented__input" />
        <label for="delivery-no" class="segmented__btn" :class="{ 'is-active': form.has_delivery_charges === 0 }">NO</label>
      </div>
    </div>

    <!-- Delivery Charges Fields (shown only if Yes) -->
    <div v-if="form.has_delivery_charges === 1" class="row g-3 mt-2">
      <div class="col-md-6">
        <label class="form-label">Flat Amount (Optional)</label>
        <input 
          type="number" 
          class="form-control" 
          min="0" 
          step="0.01"
          v-model.number="form.delivery_charge_flat"
          placeholder="e.g. 100"
          :class="{ 'is-invalid': formErrors?.delivery_charge_flat }" 
        />
        <small v-if="formErrors?.delivery_charge_flat" class="text-danger">
          {{ formErrors.delivery_charge_flat[0] }}
        </small>
      </div>

      <div class="col-md-6">
        <label class="form-label">Percentage (Optional)</label>
        <input 
          type="number" 
          class="form-control" 
          min="0" 
          max="100"
          step="0.01"
          v-model.number="form.delivery_charge_percentage"
          placeholder="e.g. 5%"
          :class="{ 'is-invalid': formErrors?.delivery_charge_percentage }" 
        />
        <small v-if="formErrors?.delivery_charge_percentage" class="text-danger">
          {{ formErrors.delivery_charge_percentage[0] }}
        </small>
      </div>
      <div class="col-12">
        <small class="text-muted">* You can enter either flat amount or percentage, not both required.</small>
      </div>
    </div>

  </div>
</template>

<style scoped>
.dark input {
  background-color: #181818 !important;
  color: #ffffff;
}

.dark textarea {
  background-color: #181818 !important;
  color: #ffffff;
}

.segmented {
  display: inline-flex;
  border-radius: 999px;
  background: #f4f6fb;
  border: 1px solid #e3e8f2;
  box-shadow: 0 4px 10px rgba(25, 28, 90, 0.05);
  overflow: hidden;
}

.dark .segmented {
  background-color: #181818 !important;
  color: #fff !important;
}

.segmented__input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.segmented__btn {
  padding: 0.5rem 1.2rem;
  font-size: 0.9rem;
  color: #2b2f3b;
  background: transparent;
  cursor: pointer;
  transition: all 0.15s ease;
  user-select: none;
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
  box-shadow: 0 4px 10px rgba(28, 13, 130, 0.25);
}

.segmented__btn:active {
  transform: translateY(1px);
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

.dark input {
  background-color: #181818 !important;
  color: #ffffff;
}

.dark .p-select {
  background-color: #121212 !important;
  color: #fff !important;
}

.dark .p-select-label {
  color: #fff !important;
}

.dark textarea {
  background-color: #181818 !important;
  color: #ffffff;
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

:global(.dark .p-multiselect-header) {
  background-color: #181818 !important;
  color: #fff !important;
}

:global(.dark .p-multiselect-label) {
  color: #fff !important;
}

:global(.dark .p-select .p-component .p-inputwrapper) {
  background: #181818 !important;
  color: #fff !important;
  border-bottom: 1px solid #555 !important;
}

/* Options list container */
:global(.dark .p-multiselect-list) {
  background: #181818 !important;
}

/* Each option */
:global(.dark .p-multiselect-option) {
  background: #181818 !important;
  color: #fff !important;
}

/* Hover/selected option */
:global(.dark .p-multiselect-option.p-highlight),
:global(.dark .p-multiselect-option:hover) {
  background: #222 !important;
  color: #fff !important;
}

:global(.dark .p-multiselect),
:global(.dark .p-multiselect-panel),
:global(.dark .p-multiselect-token) {
  background: #181818 !important;
  color: #fff !important;
  border-color: #555 !important;
}

/* Checkbox box in dropdown */
:global(.dark .p-multiselect-overlay .p-checkbox-box) {
  background: #181818 !important;
  border: 1px solid #555 !important;
}

/* Search filter input */
:global(.dark .p-multiselect-filter) {
  background: #181818 !important;
  color: #fff !important;
  border: 1px solid #555 !important;
}

/* Optional: adjust filter container */
:global(.dark .p-multiselect-filter-container) {
  background: #181818 !important;
}

/* Selected chip inside the multiselect */
:global(.dark .p-multiselect-chip) {
  background: #111 !important;
  color: #fff !important;
  border: 1px solid #555 !important;
  border-radius: 12px !important;
  padding: 0.25rem 0.5rem !important;
}

/* Chip remove (x) icon */
:global(.dark .p-multiselect-chip .p-chip-remove-icon) {
  color: #ccc !important;
}

:global(.dark .p-multiselect-chip .p-chip-remove-icon:hover) {
  color: #f87171 !important;
  /* lighter red */
}

/* ==================== Dark Mode Select Styling ====================== */
:global(.dark .p-select) {
  background-color: #181818 !important;
  color: #fff !important;
  border-color: #555 !important;
}

/* Options container */
:global(.dark .p-select-list-container) {
  background-color: #181818 !important;
  color: #fff !important;
}

/* Each option */
:global(.dark .p-select-option) {
  background-color: transparent !important;
  color: #fff !important;
}

/* Hovered option */
:global(.dark .p-select-option:hover),
:global(.dark .p-select-option.p-focus) {
  background-color: #222 !important;
  color: #fff !important;
}

:global(.dark .p-select-label) {
  color: #fff !important;
}

:global(.dark .p-placeholder) {
  color: #aaa !important;
}
</style>
