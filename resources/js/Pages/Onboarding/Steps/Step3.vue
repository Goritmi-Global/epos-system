<script setup>
import { reactive, toRaw,watch } from "vue"
import Select from "primevue/select"

const STEP_NUMBER = 3;
const props = defineProps({ model: Object })
const emit = defineEmits(["save"])

const form = reactive({
  currency:        props.model?.currency ?? "PKR",
  symbol_position: props.model?.symbol_position ?? "after",
  date_format:     props.model?.date_format ?? "dd/MM/yyyy",
  number_format:   props.model?.number_format ?? "1,000",
  time_format:     props.model?.time_format ?? "12 Hour",
})

const currencies     = ["PKR","GBP","USD","EUR","AED","SAR"]
const symbolPositions= [
  { label: "Before Amount (Rs 1000)", value: "before" },
  { label: "After Amount (1000 Rs)",  value: "after" },
]
const dateFormats    = ["dd/MM/yyyy","MM/dd/yyyy","yyyy-MM-dd"]
const numberFormats  = ["1,000","1.000","1 000"]
const timeFormats    = ["12 Hour","24 Hour"]

 
// sync parent profile whenever something in this step changes
const emitSave = () => {
  try {
    emit("save", { step: STEP_NUMBER, data: toRaw(form) })
  } catch (e) {
    console.error(`Step ${STEP_NUMBER} emitSave error:`, e)
  }
}

watch(form, emitSave, { deep: true })

</script>

<template>
  <div>
    <h5 class="fw-bold mb-4">Step 3 of 9 - Currency & Locale</h5>

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Select Currency</label>
        <Select
          v-model="form.currency"
          :options="currencies"
          placeholder="Select currency"
          class="w-100" 
          @change="emitSave"
        />
      </div>
      

      <div class="col-md-6">
        <label class="form-label">Symbol Position</label>
        <Select
          v-model="form.symbol_position"
          :options="symbolPositions"
          optionLabel="label"
          optionValue="value"
          placeholder="Choose position"
          class="w-100"
          @change="emitSave"
        />
      </div>

      <div class="col-md-6">
        <label class="form-label">Date Format</label>
        <Select
          v-model="form.date_format"
          :options="dateFormats"
          placeholder="Select format"
          class="w-100"
          @change="emitSave"
        />
      </div>

      <div class="col-md-6">
        <label class="form-label">Number Format</label>
        <Select
          v-model="form.number_format"
          :options="numberFormats"
          placeholder="Select format"
          class="w-100"
          @change="emitSave"
        />
      </div>

      <div class="col-md-6">
        <label class="form-label">Time Format</label>
        <Select
          v-model="form.time_format"
          :options="timeFormats"
          placeholder="Select format"
          class="w-100"
          @change="emitSave"
        />
      </div>
    </div>
  </div>
</template>
