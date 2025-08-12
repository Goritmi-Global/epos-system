<script setup>
import { reactive, toRaw } from "vue"
const props = defineProps({ model: Object })
const emit = defineEmits(["save"])

const form = reactive({
  feat_loyalty: props.model.feat_loyalty ?? "yes",
  feat_inventory: props.model.feat_inventory ?? "no",
  feat_backup: props.model.feat_backup ?? "no",
  feat_multilocation: props.model.feat_multilocation ?? "yes",
  feat_theme: props.model.feat_theme ?? "yes"
})

function emitSave(){ emit("save",{ step:9, data: toRaw(form) }) }
</script>

<template>
  <div>
    <h5 class="fw-bold mb-4">Step 9 of 9 - Optional Features</h5>

    <div class="vstack gap-3" style="max-width:520px">
      <div class="d-flex align-items-center justify-content-between">
        <span><i class="bi bi-gift me-2"></i>Enable Loyalty System</span>
        <div>
          <label class="me-3"><input class="form-check-input me-1" type="radio" value="yes" v-model="form.feat_loyalty" @change="emitSave"> Yes</label>
          <label><input class="form-check-input me-1" type="radio" value="no" v-model="form.feat_loyalty" @change="emitSave"> No</label>
        </div>
      </div>

      <div class="d-flex align-items-center justify-content-between">
        <span><i class="bi bi-box-seam me-2"></i>Enable Inventory Tracking</span>
        <div>
          <label class="me-3"><input class="form-check-input me-1" type="radio" value="yes" v-model="form.feat_inventory" @change="emitSave"> Yes</label>
          <label><input class="form-check-input me-1" type="radio" value="no" v-model="form.feat_inventory" @change="emitSave"> No</label>
        </div>
      </div>

      <div class="d-flex align-items-center justify-content-between">
        <span><i class="bi bi-cloud me-2"></i>Enable Cloud Backup</span>
        <div>
          <label class="me-3"><input class="form-check-input me-1" type="radio" value="yes" v-model="form.feat_backup" @change="emitSave"> Yes</label>
          <label><input class="form-check-input me-1" type="radio" value="no" v-model="form.feat_backup" @change="emitSave"> No</label>
        </div>
      </div>

      <div class="d-flex align-items-center justify-content-between">
        <span><i class="bi bi-geo-alt me-2"></i>Enable Multi-Location Support</span>
        <div>
          <label class="me-3"><input class="form-check-input me-1" type="radio" value="yes" v-model="form.feat_multilocation" @change="emitSave"> Yes</label>
          <label><input class="form-check-input me-1" type="radio" value="no" v-model="form.feat_multilocation" @change="emitSave"> No</label>
        </div>
      </div>

      <div class="d-flex align-items-center justify-content-between">
        <span><i class="bi bi-palette me-2"></i>Theme Preference</span>
        <div>
          <label class="me-3"><input class="form-check-input me-1" type="radio" value="yes" v-model="form.feat_theme" @change="emitSave"> Yes</label>
          <label><input class="form-check-input me-1" type="radio" value="no" v-model="form.feat_theme" @change="emitSave"> No</label>
        </div>
      </div>
    </div>
  </div>
</template>
