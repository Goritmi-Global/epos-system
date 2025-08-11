<script setup>
import { reactive, toRefs } from 'vue'

const props = defineProps({ model: { type: Object, required: true } })
const emit = defineEmits(['save','next','back'])

const form = reactive({
  timezone: props.model.timezone ?? '',
  language: props.model.language ?? '',
  languages_supported: props.model.languages_supported ?? []
})

function submit() {
  emit('save', { ...form })
}
</script>

<template>
  <div class="card border-0 shadow-lg rounded-4 p-4">
    <h5 class="mb-3">Welcome & Language Selection</h5>

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Timezone</label>
        <input class="form-control" v-model="form.timezone" placeholder="Auto detected or select..." />
      </div>

      <div class="col-md-6">
        <label class="form-label">Primary Language</label>
        <select class="form-select" v-model="form.language">
          <option value="">Select...</option>
          <option value="en">English</option>
          <option value="ur">Urdu</option>
          <option value="ar">Arabic</option>
          <option value="fr">French</option>
          <!-- add others -->
        </select>
      </div>

      <div class="col-12">
        <label class="form-label">Supported Languages</label>
        <div class="d-flex flex-wrap gap-2">
          <div v-for="opt in ['en','ur','ar','fr','de','hi']" :key="opt" class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox"
                   :value="opt" v-model="form.languages_supported" :id="`lang_${opt}`">
            <label class="form-check-label" :for="`lang_${opt}`">{{ opt.toUpperCase() }}</label>
          </div>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
      <button type="button" class="btn btn-outline-secondary" @click="$emit('back')">Back</button>
      <button type="button" class="btn btn-primary" @click="submit">Save</button>
      <button type="button" class="btn btn-dark" @click="$emit('next')">Next</button>
    </div>
  </div>
</template>
