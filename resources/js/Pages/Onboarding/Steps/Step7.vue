<script setup>
import { reactive, toRaw, watch } from "vue"

const STEP_NUMBER = 7;
const props = defineProps({ model: Object, formErrors: Object })
const emit = defineEmits(["save"])

const form = reactive({
  cash_enabled: props.model?.cash_enabled ? 1 : 0,
  card_enabled: props.model?.card_enabled ? 1 : 0,
  // card_provider: props.model?.card_provider ?? "Stripe Terminal",
  // provider_key:  props.model?.provider_key  ?? ""
})

const emitSave = () => {
  try {
    emit("save", { step: STEP_NUMBER, data: toRaw(form) })
  } catch (e) {
    console.error(`Step ${STEP_NUMBER} emitSave error:`, e)
  }
}
// sync parent profile whenever something in this step changes
watch(form, emitSave, { deep: true, immediate: true })


// const providers = ["Stripe Terminal","Square","SumUp","Ingenico"]
</script>

<template>
  <div>
    <h5 class="fw-bold mb-4">Step 7 of 9 - Payment Method</h5>

    <!-- Cash -->
    <div class="mb-3 d-flex align-items-center justify-content-between">
      <span><i class="bi bi-wallet2 me-2"></i>Enable Cash Payment</span>
      <div class="segmented">
        <input class="segmented__input" type="radio" :class="{ 'is-invalid': formErrors?.cash_enabled }" id="cash-yes"
          :value="1" v-model="form.cash_enabled">
        <label class="segmented__btn" :class="{ 'is-active': form.cash_enabled === 1 }" for="cash-yes">YES</label>


        <input class="segmented__input" type="radio" id="cash-no" :value="0" v-model="form.cash_enabled">
        <label class="segmented__btn" :class="{ 'is-active': form.cash_enabled === 0 }" for="cash-no">NO</label>
      </div>

    </div>
    <small v-if="formErrors?.cash_enabled" class="text-danger">
      {{ formErrors.cash_enabled[0] }}
    </small>

    <!-- Card -->
    <div class="mb-3 d-flex align-items-center justify-content-between">
      <span><i class="bi bi-credit-card me-2"></i>Enable Card Payment</span>
      <div class="segmented">
        <input class="segmented__input" type="radio" :class="{ 'is-invalid': formErrors?.card_enabled }" id="card-yes"
          :value="1" v-model="form.card_enabled">
        <label class="segmented__btn" :class="{ 'is-active': form.card_enabled === 1 }" for="card-yes">YES</label>

        <input class="segmented__input" type="radio" id="card-no" :value="0" v-model="form.card_enabled">
        <label class="segmented__btn" :class="{ 'is-active': form.card_enabled === 0 }" for="card-no">NO</label>
      </div>
    </div>

    <small v-if="formErrors?.card_enabled" class="text-danger">
      {{ formErrors.card_enabled[0] }}
    </small>

    <!-- Provider details -->
    <!-- <div v-if="form.card_enabled==='yes'" class="row g-3 mt-1" style="max-width:560px">
      <div class="col-12">
        <label class="form-label">Card Provider</label>
        <select class="form-select" v-model="form.card_provider">
          <option v-for="p in providers" :key="p" :value="p">{{ p }}</option>
        </select>
      </div>
      <div class="col-12">
        <label class="form-label">Provider Key</label>
        <input class="form-control" v-model="form.provider_key" placeholder="Enter public/terminal key" />
        <small class="text-muted">Keep your secret keys on the server; never in the browser.</small>
      </div>
    </div> -->
  </div>
</template>

<style scoped>
:root {
  --brand: #1C0D82;
}

/* Segmented YES/NO — same as Steps 5 & 6 (compact) */
.segmented {
  display: inline-flex;
  border-radius: 999px;
  background: #f4f6fb;
  border: 1px solid #e3e8f2;
  box-shadow: 0 2px 6px rgba(25, 28, 90, .05);
  overflow: hidden;
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
  transition: all .15s ease;
}

.dark .segmented{
  background-color: #121212 !important;
  color: #fff !important;
}

.dark .segmented__btn{
  color: #fff !important;
}

.segmented__btn:hover {
  background: rgba(28, 13, 130, .08);
}

.segmented__btn.is-active {
  background: #1C0D82;
  color: #fff;
  box-shadow: 0 2px 6px rgba(28, 13, 130, .25);
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

</style>
