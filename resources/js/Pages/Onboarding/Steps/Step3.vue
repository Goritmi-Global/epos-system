<script setup>
import { reactive, toRaw } from "vue"
const props = defineProps({ model: Object })
const emit = defineEmits(["save"])

const form = reactive({
  currency: props.model.currency ?? "PKR",
  symbol_position: props.model.symbol_position ?? "after",
  date_format: props.model.date_format ?? "dd/MM/yyyy",
  number_format: props.model.number_format ?? "1,000",
  time_format: props.model.time_format ?? "12 Hour"
})

const currencies = ["PKR","GBP","USD","EUR","AED","SAR"]
const symbolPositions = [
  {label:"Before Amount (Rs 1000)", value:"before"},
  {label:"After Amount (1000 Rs)", value:"after"}
]
const dateFormats = ["dd/MM/yyyy","MM/dd/yyyy","yyyy-MM-dd"]
const numberFormats = ["1,000","1.000","1 000"]
const timeFormats = ["12 Hour","24 Hour"]

function emitSave(){ emit("save",{ step:3, data: toRaw(form) }) }
</script>

<template>
  <div>
    <h5 class="fw-bold mb-4">Step 3 of 9 - Currency & Locale</h5>

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Select Currency</label>
        <select class="form-select" v-model="form.currency" @change="emitSave">
          <option v-for="c in currencies" :key="c" :value="c">{{ c }}</option>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Symbol Position</label>
        <select class="form-select" v-model="form.symbol_position" @change="emitSave">
          <option v-for="p in symbolPositions" :key="p.value" :value="p.value">{{ p.label }}</option>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label">Date Format</label>
        <select class="form-select" v-model="form.date_format" @change="emitSave">
          <option v-for="d in dateFormats" :key="d" :value="d">{{ d }}</option>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Number Format</label>
        <select class="form-select" v-model="form.number_format" @change="emitSave">
          <option v-for="n in numberFormats" :key="n" :value="n">{{ n }}</option>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label">Time Format</label>
        <select class="form-select" v-model="form.time_format" @change="emitSave">
          <option v-for="t in timeFormats" :key="t" :value="t">{{ t }}</option>
        </select>
      </div>
    </div>
  </div>
</template>
