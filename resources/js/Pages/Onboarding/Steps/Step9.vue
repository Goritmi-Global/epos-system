<script setup>
import { onMounted, reactive, toRaw, watch } from "vue"

const props = defineProps({ model: Object, formErrors: Object, isOnboarding: { type: Boolean, default: false } })
const emit = defineEmits(["save"])
const form = reactive({
  feat_inventory: props.model?.feat_inventory ?? "no",

  // Cashier logout options
  logout_after_order: props.model?.logout_after_order ? 1 : 0,
  logout_after_time: props.model?.logout_after_time ? 1 : 0,
  logout_manual_only: props.model?.logout_manual_only ? 1 : 0,
  logout_time_minutes: props.model?.logout_time_minutes ?? null,
})

onMounted(() => {
  console.log("Step 9 - Props received:", props.model);
  console.log("Step 9 - Form initialized:", toRaw(form));
})

watch(
  () => form,
  (newVal) => {
    const rawForm = toRaw(form);
    console.log("Step 9 Form Updated (watch):", rawForm);
    emit("save", { step: 9, data: rawForm });
  },
  { deep: true, immediate: true }
);

function emitSave() {
  const rawForm = toRaw(form);
  console.log("Step 9 - Emitting save with data:", rawForm);
  emit("save", { step: 9, data: rawForm })
}

/* Rows + icons */
const rows = [
  { key: "feat_inventory", label: "Enable Inventory Tracking", icon: "box" },
]

/* Minimal inline SVG set */
const icons = {
  box: `
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
      <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
      <path d="M3.3 7.5 12 12l8.7-4.5"></path>
      <path d="M12 22V12"></path>
    </svg>
  `,
}
</script>

<template>
  <div>
    <h5 class="fw-bold mb-4" v-if="props.isOnboarding">Step 9 of 9 - Advanced Features</h5>

    <!-- Inventory Tracking -->
    <div class="mb-5 pb-4 border-bottom">
      <div class="vstack gap-2" style="max-width:620px">
        <div v-for="row in rows" :key="row.key" class="feat-row d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <span class="fi" v-html="icons[row.icon]"></span>
            <span class="feat-text">{{ row.label }}</span>
          </div>

          <div class="segmented">
            <input class="segmented__input" type="radio" :id="row.key + '-yes'" value="yes" v-model="form[row.key]"
              @change="emitSave">
            <label class="segmented__btn" :class="{ 'is-active': form[row.key] === 'yes' }"
              :for="row.key + '-yes'">YES</label>

            <input class="segmented__input" type="radio" :id="row.key + '-no'" value="no" v-model="form[row.key]"
              @change="emitSave">
            <label class="segmented__btn" :class="{ 'is-active': form[row.key] === 'no' }"
              :for="row.key + '-no'">NO</label>
          </div>
        </div>
      </div>
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
          <label class="segmented__btn" :class="{ 'is-active': form.logout_after_order === 1 }"
            for="logout-order-yes">YES</label>

          <input class="segmented__input" type="radio" id="logout-order-no" :value="0"
            v-model="form.logout_after_order">
          <label class="segmented__btn" :class="{ 'is-active': form.logout_after_order === 0 }"
            for="logout-order-no">NO</label>
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
          <label class="segmented__btn" :class="{ 'is-active': form.logout_after_time === 1 }"
            for="logout-time-yes">YES</label>

          <input class="segmented__input" type="radio" id="logout-time-no" :value="0" v-model="form.logout_after_time">
          <label class="segmented__btn" :class="{ 'is-active': form.logout_after_time === 0 }"
            for="logout-time-no">NO</label>
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
          <label class="segmented__btn" :class="{ 'is-active': form.logout_manual_only === 1 }"
            for="logout-manual-yes">YES</label>

          <input class="segmented__input" type="radio" id="logout-manual-no" :value="0"
            v-model="form.logout_manual_only">
          <label class="segmented__btn" :class="{ 'is-active': form.logout_manual_only === 0 }"
            for="logout-manual-no">NO</label>
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

.feat-row {
  padding: 10px 12px;
  border: 1px solid #edf0f6;
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 3px 10px rgba(17, 38, 146, .05);
}

.feat-text {
  color: #2b2f3b;
  font-weight: 500;
}

.dark .feat-text {
  color: #fff;
  font-weight: 500;
}

.dark .feat-row {
  background-color: #121212 !important;
  color: #ffffff !important;
}

/* Icon chip */
.fi {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 8px;
  background: rgba(28, 13, 130, .08);
  color: #1C0D82;
}

.dark .fi {
  background: rgba(28, 13, 130, .08);
  color: #ffffff;
}

.fi svg {
  width: 18px;
  height: 18px;
  display: block;
}

/* Segmented YES/NO */
.segmented {
  display: inline-flex;
  border-radius: 999px;
  background: #f4f6fb;
  border: 1px solid #e3e8f2;
  box-shadow: 0 2px 6px rgba(25, 28, 90, .05);
  overflow: hidden;
}

.dark .segmented {
  background-color: #121212 !important;
  color: #fff !important;
}

.dark .segmented__btn {
  color: #fff !important;
}

.dark input {
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

.dark .text-muted {
  color: #fff !important;
}

.form-label {
  margin-bottom: 0.35rem;
  font-weight: 500;
}

.dark .form-control {
  background-color: #121212 !important;
  color: #fff !important;
}
</style>