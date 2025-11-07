<script setup>
import { reactive, toRaw, watch } from "vue"

const STEP_NUMBER = 7;
const props = defineProps({ model: Object, formErrors: Object, isOnboarding: { type: Boolean, default: false } })
const emit = defineEmits(["save"])

const form = reactive({
  cash_enabled: props.model?.cash_enabled ? 1 : 0,
  card_enabled: props.model?.card_enabled ? 1 : 0,
  // Cashier logout options
  logout_after_order: props.model?.logout_after_order ? 1 : 0,
  logout_after_time: props.model?.logout_after_time ? 1 : 0,
  logout_manual_only: props.model?.logout_manual_only ? 1 : 0,
  logout_time_minutes: props.model?.logout_time_minutes ?? 30,
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
</script>

<template>
  <div>
    <h5 class="fw-bold mb-4" v-if="props.isOnboarding">Step 7 of 9 - Payment Method & Cashier Logout</h5>

    <!-- Payment Methods Section -->
    <div class="mb-5 pb-4 border-bottom">
      <h6 class="fw-semibold mb-3">Payment Methods</h6>

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
    </div>

    <!-- Cashier Logout Section -->
    <div class="mb-5">
      <h6 class="fw-semibold mb-3"><i class="bi bi-door-open me-2"></i>Cashier Logout Options</h6>
      <small class="text-muted d-block mb-3">Select when cashiers should be logged out automatically</small>

      <!-- Option A: After Each Order -->
      <div class="mb-3 d-flex align-items-center justify-content-between">
        <span><i class="bi bi-bag-check me-2"></i>After Each Order</span>
        <div class="segmented">
          <input class="segmented__input" type="radio" :class="{ 'is-invalid': formErrors?.logout_after_order }" 
            id="logout-order-yes" :value="1" v-model="form.logout_after_order">
          <label class="segmented__btn" :class="{ 'is-active': form.logout_after_order === 1 }" for="logout-order-yes">YES</label>

          <input class="segmented__input" type="radio" id="logout-order-no" :value="0" v-model="form.logout_after_order">
          <label class="segmented__btn" :class="{ 'is-active': form.logout_after_order === 0 }" for="logout-order-no">NO</label>
        </div>
      </div>
      <small v-if="formErrors?.logout_after_order" class="text-danger">
        {{ formErrors.logout_after_order[0] }}
      </small>

      <!-- Option B: After Selected Time -->
      <div class="mb-3 d-flex align-items-center justify-content-between">
        <span><i class="bi bi-hourglass-end me-2"></i>After Selected Time</span>
        <div class="segmented">
          <input class="segmented__input" type="radio" :class="{ 'is-invalid': formErrors?.logout_after_time }" 
            id="logout-time-yes" :value="1" v-model="form.logout_after_time">
          <label class="segmented__btn" :class="{ 'is-active': form.logout_after_time === 1 }" for="logout-time-yes">YES</label>

          <input class="segmented__input" type="radio" id="logout-time-no" :value="0" v-model="form.logout_after_time">
          <label class="segmented__btn" :class="{ 'is-active': form.logout_after_time === 0 }" for="logout-time-no">NO</label>
        </div>
      </div>
      <small v-if="formErrors?.logout_after_time" class="text-danger">
        {{ formErrors.logout_after_time[0] }}
      </small>

      <!-- Logout Time Input (show only if logout_after_time is enabled) -->
      <div v-if="form.logout_after_time === 1" class="mb-3 ms-3">
        <label class="form-label">Logout Time (Minutes)</label>
        <input type="number" class="form-control" :class="{ 'is-invalid': formErrors?.logout_time_minutes }" 
          v-model.number="form.logout_time_minutes" placeholder="Enter minutes (e.g., 30)" min="1" max="1440">
        <small v-if="formErrors?.logout_time_minutes" class="text-danger">
          {{ formErrors.logout_time_minutes[0] }}
        </small>
        <small class="text-muted d-block mt-1">Range: 1 to 1440 minutes (24 hours)</small>
      </div>

      <!-- Option C: Manual Logout Only -->
      <div class="mb-3 d-flex align-items-center justify-content-between">
        <span><i class="bi bi-hand-index me-2"></i>Manual Logout Only</span>
        <div class="segmented">
          <input class="segmented__input" type="radio" :class="{ 'is-invalid': formErrors?.logout_manual_only }" 
            id="logout-manual-yes" :value="1" v-model="form.logout_manual_only">
          <label class="segmented__btn" :class="{ 'is-active': form.logout_manual_only === 1 }" for="logout-manual-yes">YES</label>

          <input class="segmented__input" type="radio" id="logout-manual-no" :value="0" v-model="form.logout_manual_only">
          <label class="segmented__btn" :class="{ 'is-active': form.logout_manual_only === 0 }" for="logout-manual-no">NO</label>
        </div>
      </div>
      <small v-if="formErrors?.logout_manual_only" class="text-danger">
        {{ formErrors.logout_manual_only[0] }}
      </small>
    </div>
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

.dark input{
  background-color: #212121 !important;
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
</style>