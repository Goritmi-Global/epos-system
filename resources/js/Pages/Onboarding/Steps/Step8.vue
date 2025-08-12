<script setup>
import { reactive, toRaw } from "vue"
const props = defineProps({ model: Object })
const emit = defineEmits(["save"])

const defaultDay = (name) => ({ name, open:true, start:"09:00", end:"17:00", breaks:[] })
const form = reactive({
  auto_disable: props.model.auto_disable ?? "yes",
  hours: props.model.hours ?? [
    defaultDay("Monday"), defaultDay("Tuesday"), defaultDay("Wednesday"),
    defaultDay("Thursday"), defaultDay("Friday"), defaultDay("Saturday"), defaultDay("Sunday")
  ]
})

function addBreak(d){ d.breaks.push({ start:"13:00", end:"14:00" }); emitSave() }
function emitSave(){ emit("save",{ step:8, data: toRaw(form) }) }
const timeOptions = Array.from({length:24*2},(_,i)=>{
  const h = String(Math.floor(i/2)).padStart(2,"0");
  const m = i%2 ? "30" : "00";
  return `${h}:${m}`;
})
</script>

<template>
  <div>
    <h5 class="fw-bold mb-3">Step 8 of 9 - Business Hours</h5>

    <div class="mb-3 d-flex align-items-center justify-content-between">
      <span>Auto-disable ordering after hours</span>
      <div>
        <label class="me-3"><input class="form-check-input me-1" type="radio" value="yes" v-model="form.auto_disable" @change="emitSave"> Yes</label>
        <label><input class="form-check-input me-1" type="radio" value="no" v-model="form.auto_disable" @change="emitSave"> No</label>
      </div>
    </div>

    <div class="vstack gap-2">
      <div v-for="d in form.hours" :key="d.name" class="row g-2 align-items-center">
        <div class="col-md-2 fw-semibold">{{ d.name }}</div>
        <div class="col-md-3">
          <select class="form-select" v-model="d.start" @change="emitSave">
            <option v-for="t in timeOptions" :key="t" :value="t">{{ t }}</option>
          </select>
        </div>
        <div class="col-md-3">
          <select class="form-select" v-model="d.end" @change="emitSave">
            <option v-for="t in timeOptions" :key="t" :value="t">{{ t }}</option>
          </select>
        </div>
        <div class="col-md-2">
          <span class="badge rounded-pill" :class="d.open ? 'bg-success' : 'bg-secondary'">{{ d.open ? 'Open' : 'Closed' }}</span>
        </div>
        <div class="col-md-2 d-flex gap-2 justify-content-end">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" v-model="d.open" @change="emitSave">
          </div>
          <button class="btn btn-sm btn-success rounded-circle" title="Add break" @click="addBreak(d)">
            <i class="bi bi-plus-lg"></i>
          </button>
        </div>

        <!-- Break rows -->
        <div v-for="(b,bi) in d.breaks" :key="bi" class="offset-md-2 col-md-8 d-flex gap-2">
          <small class="text-muted">Break</small>
          <select class="form-select form-select-sm w-auto" v-model="b.start" @change="emitSave">
            <option v-for="t in timeOptions" :key="'bs'+t" :value="t">{{ t }}</option>
          </select>
          <select class="form-select form-select-sm w-auto" v-model="b.end" @change="emitSave">
            <option v-for="t in timeOptions" :key="'be'+t" :value="t">{{ t }}</option>
          </select>
        </div>
      </div>
    </div>
  </div>
</template>
