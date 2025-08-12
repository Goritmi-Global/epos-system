<script setup>
import { reactive, toRaw, watch } from "vue"
const props = defineProps({ model: Object })
const emit = defineEmits(["save"])

const form = reactive({
  tax_registered: props.model.tax_registered ?? "yes",
  tax_type: props.model.tax_type ?? "VAT",
  tax_rate: props.model.tax_rate ?? 0,
  price_includes_tax: props.model.price_includes_tax ?? "yes",
  tax_id: props.model.tax_id ?? ""
})

watch(form, () => emit("save",{ step:4, data: toRaw(form) }), { deep:true })

const taxTypes = ["VAT","GST","Sales Tax"]
</script>

<template>
  <div>
    <h5 class="fw-bold mb-4">Step 4 of 9 - Tax & VAT Setup</h5>

    <div class="mb-3 d-flex align-items-center justify-content-between">
      <label class="me-3">Is your business tax registered?</label>
      <div>
        <label class="me-3"><input class="form-check-input me-1" type="radio" value="yes" v-model="form.tax_registered"> Yes</label>
        <label><input class="form-check-input me-1" type="radio" value="no" v-model="form.tax_registered"> No</label>
      </div>
    </div>

    <div v-if="form.tax_registered==='yes'" class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Tax Type</label>
        <select class="form-select" v-model="form.tax_type">
          <option v-for="t in taxTypes" :key="t" :value="t">{{ t }}</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Tax Rate (%)</label>
        <input type="number" class="form-control" min="0" max="100" v-model.number="form.tax_rate"/>
      </div>
      <div class="col-md-3">
        <label class="form-label">Tax ID</label>
        <input class="form-control" v-model="form.tax_id"/>
      </div>
    </div>

    <div class="mt-3 d-flex align-items-center justify-content-between">
      <label class="me-3">Price includes tax?</label>
      <div>
        <label class="me-3"><input class="form-check-input me-1" type="radio" value="yes" v-model="form.price_includes_tax"> Yes</label>
        <label><input class="form-check-input me-1" type="radio" value="no" v-model="form.price_includes_tax"> No</label>
      </div>
    </div>
  </div>
</template>
