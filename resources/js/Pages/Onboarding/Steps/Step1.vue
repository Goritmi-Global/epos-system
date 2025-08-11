<script setup>
import { reactive, ref, onMounted, computed } from 'vue'
import axios from 'axios'

const props = defineProps({ model: { type: Object, required: true } })
const emit = defineEmits(['save','next','back'])

const countries = ref([])     // fill with your master list
const languages = ref([])     // fill with your language list
const timezones = ref([])     // optional: a list, if you want to show all

const form = reactive({
  country_code: props.model.country_code ?? '',
  country_name: props.model.country_name ?? '',
  timezone:     props.model.timezone ?? '',
  language:     props.model.language ?? '',
  languages_supported: props.model.languages_supported ?? []
})

onMounted(async () => {
  // 1) Country by IP (backend)
  try {
    const { data } = await axios.get('/api/geo')
    form.country_code = data.country_code || form.country_code
    form.country_name = data.country_name || form.country_name
    // 2) Timezone from browser (fallback to server one if provided)
    const tzBrowser = Intl.DateTimeFormat().resolvedOptions().timeZone
    form.timezone = tzBrowser || data.timezone || form.timezone
    // 3) Language from browser; then normalize by country if you want
    const navLang = (navigator.language || 'en').toLowerCase() // e.g. en-GB
    form.language = pickLanguageForCountry(form.country_code, navLang)
  } catch (e) {
    // Fallbacks
    form.timezone = Intl.DateTimeFormat().resolvedOptions().timeZone
    form.language = (navigator.language || 'en').toLowerCase()
  }
})

// Map country to preferred language code (extend as needed)
function pickLanguageForCountry(cc, browserLang) {
  const map = {
    GB: 'en-GB', US: 'en-US', PK: 'ur', SA: 'ar', AE: 'ar',
    FR: 'fr', DE: 'de', ES: 'es', IT: 'it', CN: 'zh-CN', JP: 'ja'
  }
  // if browser language already matches country (en-GB for GB), keep it
  if (browserLang?.toUpperCase().endsWith(cc)) return browserLang
  return map[cc] || browserLang || 'en'
}

// Create a display like "GMT (UTC+00:00)" from an IANA name
const tzLabel = computed(() => formatTz(form.timezone))

function formatTz(iana) {
  try {
    const dtf = new Intl.DateTimeFormat('en', {
      timeZone: iana, timeZoneName: 'shortOffset'
    })
    const parts = dtf.formatToParts(new Date())
    const off = parts.find(p => p.type === 'timeZoneName')?.value // "GMT+1" / "UTC+05:00"
    // Normalize to "GMT (UTC+05:00)"
    if (!off) return iana
    const pretty = off.includes('UTC') ? off : off.replace('GMT', 'UTC')
    return `GMT (${pretty})`
  } catch { return iana || '' }
}

function submit() {
  emit('save', {
    country_code: form.country_code,
    country_name: form.country_name,
    timezone: form.timezone,
    language: form.language,
    languages_supported: form.languages_supported
  })
}
</script>

<template>
  <div class="card border-0 shadow-lg rounded-4 p-4">
    <h5 class="mb-3">Welcome & Language Selection</h5>

    <div class="row g-3">
      <!-- Country -->
      <div class="col-12">
        <label class="form-label">Country</label>
        <select class="form-select" v-model="form.country_code">
          <option value="" disabled>Select country</option>
          <!-- Expect countries like [{code:'GB', name:'United Kingdom'}, ...] -->
          <option v-for="c in countries" :key="c.code" :value="c.code">
            {{ c.name }}
          </option>
        </select>
        <small v-if="form.country_name" class="text-muted">
          Auto‑detected: {{ form.country_name }}
        </small>
      </div>

      <!-- Time Zone -->
      <div class="col-md-6">
        <label class="form-label">Time Zone</label>
        <select class="form-select" v-model="form.timezone">
          <option :value="form.timezone">{{ tzLabel }}</option>
          <!-- optionally list more timezones here -->
        </select>
      </div>

      <!-- Language -->
      <div class="col-md-6">
        <label class="form-label">Language</label>
        <select class="form-select" v-model="form.language">
          <option value="" disabled>Select language</option>
          <!-- languages like [{code:'en-GB', name:'GB English'}, {code:'ur', name:'Urdu'}] -->
          <option v-for="l in languages" :key="l.code" :value="l.code">
            {{ l.name }}
          </option>
        </select>
        <small class="text-muted">Auto‑detected: {{ form.language }}</small>
      </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
      <button type="button" class="btn btn-outline-secondary" @click="$emit('back')">Back</button>
      <button type="button" class="btn btn-primary" @click="submit">Save</button>
      <button type="button" class="btn btn-dark" @click="$emit('next')">Next</button>
    </div>
  </div>
</template>
