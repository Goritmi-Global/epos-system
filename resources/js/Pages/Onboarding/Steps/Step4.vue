<script setup>
import { reactive, toRaw, watch, ref } from "vue";
import Select from "primevue/select"; // PrimeVue Select

const props = defineProps({ model: Object, formErrors: Object });
const emit = defineEmits(["save"]);

const form = reactive({
  tax_registered: props.model.tax_registered ?? 1,
  tax_type: props.model.tax_type ?? "VAT",
  tax_rate: props.model.tax_rate ?? 0,
  price_includes_tax: props.model.price_includes_tax ? 1 : 0,
  tax_id: props.model.tax_id ?? null,
  extra_tax_rates: props.model.extra_tax_rates ?? "", 
});

console.log("props.model.tax_registered",props.model.tax_registered);
console.log("tax_registered", form.tax_registered);
watch(form, () => {
  const payload = {
    step: 4,
    data: {
      tax_registered: Number(form.tax_registered),
      price_includes_tax: Number(form.price_includes_tax),
    },
  }

  if (Number(form.tax_registered) === 1) {
    payload.data.tax_type = form.tax_type;
    payload.data.tax_rate = Number(form.tax_rate);
    payload.data.tax_id = form.tax_id;
    payload.data.extra_tax_rates = form.extra_tax_rates || "";
  } else {
    payload.data.tax_type = "";
    payload.data.tax_rate = 0;
    payload.data.tax_id = "";
    payload.data.extra_tax_rates = 0;
  }

  emit("save", payload);
}, { deep: true, immediate: true });




// Tax types for Select
const taxTypeOptions = ref([
  { name: "VAT", code: "VAT" },
  { name: "GST", code: "GST" },
  { name: "Sales Tax", code: "Sales Tax" },
]);

// // PrimeVue Select expects object as value
// const selectedTaxType = ref(
//   taxTypeOptions.value.find((t) => t.code === form.tax_type) || null
// );

// // Keep form in sync with Select
// watch(selectedTaxType, (opt) => {
//   form.tax_type = opt?.code || "";
// });
</script>

<template>
  <div>
    <h5 class="fw-bold mb-4">Step 4 of 9 - Tax & VAT Setup</h5>

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
        <Select v-model="form.tax_type" :options="taxTypeOptions" optionLabel="name" optionValue="code"
          placeholder="Select Tax Type" class="w-100" :class="{ 'is-invalid': formErrors?.tax_type }" />

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
        <input  :class="{ 'is-invalid': formErrors?.tax_id }" class="form-control" v-model="form.tax_id" />
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
        <label for="price-no" class="segmented__btn"
          :class="{ 'is-active': form.price_includes_tax === 0 }">NO</label>
      </div>
    </div>
  </div>

</template>

<style scoped>
.dark input{
    background-color: #000000 !important;
    color: #ffffff;
}

.dark textarea{
    background-color: #000000 !important;
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
