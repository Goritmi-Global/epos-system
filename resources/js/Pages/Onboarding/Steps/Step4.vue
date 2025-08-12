<script setup>
import { reactive, toRaw, watch, ref } from "vue";
import Select from "primevue/select"; // PrimeVue Select

const props = defineProps({ model: Object });
const emit = defineEmits(["save"]);

const form = reactive({
  tax_registered: props.model.tax_registered ?? "yes",
  tax_type: props.model.tax_type ?? "VAT",
  tax_rate: props.model.tax_rate ?? 0,
  price_includes_tax: props.model.price_includes_tax ?? "yes",
  tax_id: props.model.tax_id ?? "",
});

watch(form, () => emit("save", { step: 4, data: toRaw(form) }), { deep: true });

// Tax types for Select
const taxTypeOptions = ref([
  { name: "VAT", code: "VAT" },
  { name: "GST", code: "GST" },
  { name: "Sales Tax", code: "Sales Tax" },
]);

// PrimeVue Select expects object as value
const selectedTaxType = ref(
  taxTypeOptions.value.find((t) => t.code === form.tax_type) || null
);

// Keep form in sync with Select
watch(selectedTaxType, (opt) => {
  form.tax_type = opt?.code || "";
});
</script>

<template>
  <div>
    <h5 class="fw-bold mb-4">Step 4 of 9 - Tax & VAT Setup</h5>

    <!-- Tax Registered -->
    <div class="mb-3">
      <label class="form-label d-block mb-2">Is your business tax registered?</label>
      <div class="segmented">
        <input type="radio" id="tax-yes" value="yes" v-model="form.tax_registered" class="segmented__input" />
        <label for="tax-yes" class="segmented__btn" :class="{ 'is-active': form.tax_registered === 'yes' }">YES</label>

        <input type="radio" id="tax-no" value="no" v-model="form.tax_registered" class="segmented__input" />
        <label for="tax-no" class="segmented__btn" :class="{ 'is-active': form.tax_registered === 'no' }">NO</label>
      </div>
    </div>

    <!-- If registered -->
    <div v-if="form.tax_registered === 'yes'" class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Tax Type</label>
        <Select
          v-model="selectedTaxType"
          :options="taxTypeOptions"
          optionLabel="name"
          :filter="false"
          placeholder="Select Tax Type"
          class="w-100"
        >
          <template #value="{ value, placeholder }">
            <span v-if="value">{{ value.name }}</span>
            <span v-else>{{ placeholder }}</span>
          </template>
          <template #option="{ option }">
            <span>{{ option.name }}</span>
          </template>
        </Select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Tax Rate (%)</label>
        <input type="number" class="form-control" min="0" max="100" v-model.number="form.tax_rate" />
      </div>
      <div class="col-md-3">
        <label class="form-label">Tax ID</label>
        <input class="form-control" v-model="form.tax_id" />
      </div>
    </div>

    <!-- Price includes tax -->
    <div class="mt-3">
      <label class="form-label d-block mb-2">Price includes tax?</label>
      <div class="segmented">
        <input type="radio" id="price-yes" value="yes" v-model="form.price_includes_tax" class="segmented__input" />
        <label for="price-yes" class="segmented__btn" :class="{ 'is-active': form.price_includes_tax === 'yes' }">YES</label>

        <input type="radio" id="price-no" value="no" v-model="form.price_includes_tax" class="segmented__input" />
        <label for="price-no" class="segmented__btn" :class="{ 'is-active': form.price_includes_tax === 'no' }">NO</label>
      </div>
    </div>
  </div>
</template>

<style scoped>
.segmented {
  display: inline-flex;
  border-radius: 999px;
  background: #f4f6fb;
  border: 1px solid #e3e8f2;
  box-shadow: 0 4px 10px rgba(25, 28, 90, 0.05);
  overflow: hidden;
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
</style>
