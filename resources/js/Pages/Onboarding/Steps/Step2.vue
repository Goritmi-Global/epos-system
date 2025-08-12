<script setup>
import { reactive, toRaw } from "vue"
const props = defineProps({ model: Object })
const emit = defineEmits(["save"])

const form = reactive({
  business_name: props.model.business_name ?? "",
  business_type: props.model.business_type ?? "",
  address: props.model.address ?? "",
  phone: props.model.phone ?? "",
  email: props.model.email ?? "",
  website: props.model.website ?? "",
  logo: props.model.logo ?? null,          // URL or file
  logo_file: null
})
const businessTypes = ["Cafe","Restaurant","Bakery","Takeaway","Food Truck"]

function onLogoChange(e){
  const f = e.target.files?.[0]
  form.logo_file = f || null
  if (f) form.logo = URL.createObjectURL(f)
  emitSave()
}
function emitSave(){ emit("save",{ step:2, data: toRaw(form) }) }
</script>

<template>
  <div>
    <h5 class="fw-bold mb-4">Step 2 of 9 - Business Information</h5>

    <div class="row g-3 align-items-start">
      <div class="col-12">
        <label class="form-label">Business Name*</label>
        <input class="form-control" v-model="form.business_name" @input="emitSave" />
      </div>

      <div class="col-md-3">
        <div class="border rounded-4 p-2 text-center" style="height:160px;">
          <img v-if="form.logo" :src="form.logo" alt="Logo" style="max-height:100%; max-width:100%;"/>
          <div v-else class="h-100 d-flex align-items-center justify-content-center text-muted">
            <i class="bi bi-upload fs-2"></i>
          </div>
        </div>
        <input type="file" class="form-control mt-2" accept="image/*" @change="onLogoChange">
      </div>

      <div class="col-md-9">
        <label class="form-label">Business Type*</label>
        <select class="form-select" v-model="form.business_type" @change="emitSave">
          <option value="" disabled>Select</option>
          <option v-for="t in businessTypes" :key="t" :value="t">{{ t }}</option>
        </select>

        <label class="form-label mt-3">Address*</label>
        <input class="form-control" v-model="form.address" @input="emitSave"/>

        <div class="row g-3 mt-1">
          <div class="col-md-6">
            <label class="form-label">Phone*</label>
            <input class="form-control" v-model="form.phone" @input="emitSave"/>
          </div>
          <div class="col-md-6">
            <label class="form-label">Email*</label>
            <input type="email" class="form-control" v-model="form.email" @input="emitSave"/>
          </div>
        </div>

        <label class="form-label mt-3">Website</label>
        <input class="form-control" v-model="form.website" @input="emitSave"/>
      </div>
    </div>
  </div>
</template>
