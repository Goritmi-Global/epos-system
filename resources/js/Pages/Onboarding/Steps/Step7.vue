<script setup>
import { reactive, toRaw, watch } from "vue"
const props = defineProps({ model: Object })
const emit = defineEmits(["save"])

const form = reactive({
  cash_enabled: props.model.cash_enabled ?? "yes",
  card_enabled: props.model.card_enabled ?? "yes",
  card_provider: props.model.card_provider ?? "Stripe Terminal",
  provider_key: props.model.provider_key ?? ""
})
watch(form, () => emit("save",{ step:7, data: toRaw(form) }), { deep:true })

const providers = ["Stripe Terminal","Square","SumUp","Ingenico"]
</script>

<template>
  <div>
    <h5 class="fw-bold mb-4">Step 7 of 9 - Payment Method</h5>

    <div class="mb-3 d-flex align-items-center justify-content-between">
      <span><i class="bi bi-wallet2 me-2"></i>Enable Cash Payment</span>
      <div>
        <label class="me-3"><input class="form-check-input me-1" type="radio" value="yes" v-model="form.cash_enabled"> Yes</label>
        <label><input class="form-check-input me-1" type="radio" value="no" v-model="form.cash_enabled"> No</label>
      </div>
    </div>

    <div class="mb-3 d-flex align-items-center justify-content-between">
      <span><i class="bi bi-credit-card me-2"></i>Enable Card Payment</span>
      <div>
        <label class="me-3"><input class="form-check-input me-1" type="radio" value="yes" v-model="form.card_enabled"> Yes</label>
        <label><input class="form-check-input me-1" type="radio" value="no" v-model="form.card_enabled"> No</label>
      </div>
    </div>

    <div v-if="form.card_enabled==='yes'" class="row g-3 mt-1" style="max-width:560px">
      <div class="col-md-6">
        <label class="form-label">Provider</label>
        <select class="form-select" v-model="form.card_provider">
          <option v-for="p in providers" :key="p" :value="p">{{ p }}</option>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">API / Terminal Key</label>
        <input class="form-control" v-model="form.provider_key">
      </div>
    </div>
  </div>
</template>
