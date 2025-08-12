<script setup>
import { reactive, toRaw, computed } from "vue"
const props = defineProps({ model: Object })
const emit = defineEmits(["save"])

const form = reactive({
  order_types: props.model.order_types ?? [], // ['dine_in','takeaway',...]
  table_mgmt: props.model.table_mgmt ?? "yes",
  tables: props.model.tables ?? 1,
  online_ordering: props.model.online_ordering ?? "yes"
})

function toggle(type){
  const i = form.order_types.indexOf(type)
  i===-1 ? form.order_types.push(type) : form.order_types.splice(i,1)
  emitSave()
}
function emitSave(){ emit("save",{ step:5, data: toRaw(form) }) }
const types = [
  {key:'dine_in', label:'Dine-in'},
  {key:'takeaway', label:'Takeaway'},
  {key:'delivery', label:'Delivery'},
  {key:'collection', label:'Collection'}
]
</script>

<template>
  <div>
    <h5 class="fw-bold mb-3">Step 5 of 9 - Order Type & Service Options</h5>

    <label class="mb-2">Enable Order types</label>
    <div class="d-flex flex-column gap-2 mb-3">
      <label v-for="t in types" :key="t.key" class="form-check">
        <input class="form-check-input me-2" type="checkbox"
               :checked="form.order_types.includes(t.key)"
               @change="toggle(t.key)">
        {{ t.label }}
      </label>
    </div>

    <div class="mb-3 d-flex align-items-center justify-content-between">
      <label class="me-3">Enable Table Management?</label>
      <div>
        <label class="me-3"><input class="form-check-input me-1" type="radio" value="yes" v-model="form.table_mgmt" @change="emitSave"> Yes</label>
        <label><input class="form-check-input me-1" type="radio" value="no" v-model="form.table_mgmt" @change="emitSave"> No</label>
      </div>
    </div>

    <div v-if="form.table_mgmt==='yes'" class="mb-3" style="max-width:420px">
      <label class="form-label">Number of Tables</label>
      <input type="number" min="1" class="form-control" v-model.number="form.tables" @input="emitSave">
    </div>

    <div class="mb-3 d-flex align-items-center justify-content-between">
      <label class="me-3">Enable Online Ordering Integration</label>
      <div>
        <label class="me-3"><input class="form-check-input me-1" type="radio" value="yes" v-model="form.online_ordering" @change="emitSave"> Yes</label>
        <label><input class="form-check-input me-1" type="radio" value="no" v-model="form.online_ordering" @change="emitSave"> No</label>
      </div>
    </div>
  </div>
</template>
