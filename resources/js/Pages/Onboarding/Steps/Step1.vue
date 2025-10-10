<script setup>
import { reactive, ref, onMounted, watch, computed, nextTick } from "vue"
import axios from "axios"
import Select from "primevue/select"
import { toast } from "vue3-toastify"


const emit = defineEmits(["save"])
const props = defineProps({ model: Object, formErrors: Object,  isOnboarding: { type: Boolean, default: false }  })

const selectedCountry = ref(null)
const selectedLanguage = ref(null)
const countries = ref([])
const timezonesOptions = ref([])

const languages = ref([
  { label: "English", value: "en", flag: "https://flagcdn.com/w20/gb.png" },
])

const languagesOptions = computed(() =>
  languages.value.map(l => ({ name: l.label, code: l.value, flag: l.flag }))
)

const form = reactive({
  country_code: "",
  country_name: "",
  timezone_id: "",
  language: "en",
  languages_supported: [],
})

const flagUrl = (code, size = "24x18") =>
  `https://flagcdn.com/${size}/${String(code || "").toLowerCase()}.png`

const emitSave = () => {
  emit("save", { step: 1, data: { ...form } })
}

// Fetch countries
const fetchCountries = async () => {
  try {
    const { data } = await axios.get("/api/countries")
    countries.value = data.map(c => ({
      name: c.name ?? c.label,
      code: String(c.code ?? c.iso2 ?? c.value ?? "").toUpperCase(),
    }))
  } catch (error) {
    console.error("fetchCountries error:", error)
    toast.warning(error.response?.data?.message || "Failed to load countries")
  }
}

// Fetch timezone_id for selected country
// const fetchCountryDetails = async (code) => {
//   if (!code) return
//   try {
//     const { data } = await axios.get(`/api/country/${code}`)
//     form.timezone_id = data.timezone_id
//     emitSave()
//   } catch (error) {
//     console.error("fetchCountryDetails error:", error)
//     toast.warning(error.response?.data?.message || "Failed to fetch country details")
//   }
// }

const fetchCountryDetails = async (code) => {
  if (!code) return
  try {
    const { data } = await axios.get(`/api/country/${code}`)

    // populate timezones dropdown
    timezonesOptions.value = data.timezones.map(tz => ({
      label: `${tz.gmt}`,  // show name + GMT offset
      value: tz.id,                     // ✅ send ID to backend
      is_default: tz.is_default
    }))

    // set selected timezone_id to default if exists
    const defaultTz = timezonesOptions.value.find(t => t.is_default)
    form.timezone_id = defaultTz ? defaultTz.value : ''

    emitSave()
  } catch (error) {
    console.error("fetchCountryDetails error:", error)
    toast.warning(error.response?.data?.message || "Failed to fetch country details")
  }
}



// Detect user country from geo API
const detectCountry = async () => {
  try {
    const { data } = await axios.get("/api/geo")
    form.country_code = data.country_code || ""
    form.country_name = data.country_name || ""
    if (data.timezone_id) form.timezone_id = data.timezone_id
    emitSave()
  } catch (error) {
    console.error("detectCountry error:", error)
    toast.warning(error.response?.data?.message || "Failed to detect country")
  }
}

// Sync selected country with profile/model
const syncSelectedCountry = () => {
  if (!countries.value.length) return
  const code = props.model?.country_code || form.country_code
  selectedCountry.value =
    countries.value.find(c => String(c.code).toUpperCase() === String(code).toUpperCase()) || null

  if (selectedCountry.value) {
    form.country_code = selectedCountry.value.code
    form.country_name = selectedCountry.value.name
  }
}

// Sync selected language with profile/model
const syncSelectedLanguage = () => {
  const code = props.model?.language || form.language
  selectedLanguage.value =
    languagesOptions.value.find(l => l.code === code) || languagesOptions.value[0]
}

// Initialize on mount
onMounted(async () => {
  await fetchCountries()
  // await detectCountry()
  syncSelectedCountry()
  syncSelectedLanguage()
})

// Watch for changes in countries or profile to resync selected country
watch([countries, () => props.model?.country_code], () => {
  syncSelectedCountry()
})

// Watch selected country changes
watch(selectedCountry, (opt) => {
  if (!opt || !opt.code) return
  form.country_code = opt.code
  form.country_name = opt.name
  fetchCountryDetails(opt.code)
  emitSave()
})

// Watch selected language changes
watch(selectedLanguage, (opt) => {
  if (!opt) return
  form.language = opt.code
  emitSave()
})
</script>

<template>
  <div>
    <h5 class="fw-bold mb-3" v-if="props.isOnboarding">Step 1 of 9 - Welcome & Language Selection</h5>

    <div class="section p-3 p-md-4">
      <div class="row g-3">
        <!-- Country -->
        <div class="col-12">
          <label class="form-label d-flex align-items-center gap-2">Country</label>



          <Select v-model="selectedCountry" :options="countries" optionLabel="name" :filter="true" :pt="{
            root: { class: 'bg-white' },
            listContainer: { class: 'bg-white text-black' },
            option: { class: 'text-black' },
            header: { class: 'bg-white text-black' },
            pcFilterContainer: { class: 'bg-white p-2 border-b border-gray-200' },
            pcFilter: { class: '!bg-white w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-300 !text-black' },
            pcFilterIconContainer: { class: 'text-gray-500' },
          }" placeholder="Select a Country" class="w-100 bg-white"
            :class="{ 'is-invalid': formErrors.country_name || formErrors.country_code }">
            <!-- Selected -->
            <template #value="{ value, placeholder }">
              <div v-if="value" class="d-flex align-items-center gap-2">
                <img :alt="value.name" :src="flagUrl(value.code)" width="18" height="14" style="object-fit:cover" />
                <span>{{ value.name }}</span>
              </div>
              <span v-else>{{ placeholder }}</span>
            </template>

            <!-- Options -->
            <template #option="{ option }">
              <div class="d-flex align-items-center gap-2">
                <img :alt="option.name" :src="flagUrl(option.code)" width="18" height="14" style="object-fit:cover" />
                <span>{{ option.name }}</span>
              </div>
            </template>
          </Select>

          <!-- Validation Error -->
          <small v-if="formErrors?.country_name || formErrors?.country_code" class="text-danger">
            {{ formErrors.country_name?.[0] || formErrors.country_code?.[0] }}
          </small>
        </div>


        <!-- Timezone -->
        <!-- <div class="col-md-6">
          <label class="form-label d-flex align-items-center gap-2">Time Zone</label>
          <input type="text" class="form-control" v-model="form.timezone"
            :class="{ 'is-invalid': formErrors.timezone }" />
          <small v-if="formErrors?.timezone" class="text-danger">
            {{ formErrors.timezone[0] }}
          </small>
        </div> -->

        <div class="col-md-6">
          <label class="form-label d-flex align-items-center gap-2">Time Zone</label>

          <Select v-model="form.timezone_id" :options="timezonesOptions" optionLabel="label" optionValue="value" :pt="{
            listContainer: { class: 'bg-white text-black' },
            option: { class: 'text-black hover:bg-gray-100' }
          }" placeholder="Select a Timezone" class="w-100" :class="{ 'is-invalid': formErrors.timezone }" />

          <small v-if="formErrors?.timezone_id" class="text-danger">
            {{ formErrors.timezone_id[0] }}
          </small>
        </div>


        <!-- Language -->
        <div class="col-md-6">
          <label class="form-label d-flex align-items-center gap-2">Language</label>
          <Select v-model="selectedLanguage" :options="languagesOptions" optionLabel="name" :filter="false" :pt="{
            listContainer: { class: 'bg-white text-black' },
            option: { class: 'text-black hover:bg-gray-100' },
            header: { class: 'bg-white text-black' },

          }" placeholder="Select a Language" class="w-100" :class="{ 'is-invalid': formErrors.language }">
            <template #value="{ value, placeholder }">
              <div v-if="value" class="d-flex align-items-center gap-2">
                <img :alt="value.name" :src="value.flag" width="18" height="14" style="object-fit:cover" />
                <span>{{ value.name }}</span>
              </div>
              <span v-else>{{ placeholder }}</span>
            </template>
            <template #option="{ option }">
              <div class="d-flex align-items-center gap-2">
                <img :alt="option.name" :src="option.flag" width="18" height="14" style="object-fit:cover" />
                <span>{{ option.name }}</span>
              </div>
            </template>
          </Select>
          <small v-if="formErrors?.language" class="text-danger">
            {{ formErrors.language[0] }}
          </small>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
:root {
  --brand: #1C0D82;
}
:global(.p-select-filter) {
  background-color: white !important;
}
:global(.p-select-option:hover){
  background: #fff !important;
}

:global(.p-select-option:not(.p-select-option-selected):not(.p-disabled).p-focus) {
  background: #f5f5f5 !important;
  color: #000 !important;
}
:global(.dark .p-select-option:not(.p-select-option-selected):not(.p-disabled).p-focus) {
  background: #212121 !important;
  color: #fff !important;
}
:global(.p-select-dropdown){
  background: white !important;
}
:global(.dark .p-select-dropdown){
  background: #212121 !important;
}
:global(.dark .p-select){
  background-color: #212121 !important;
}

.p-select-option:hover,
.p-select-option.p-highlight,
.p-select-option.p-focus {
  background-color: transparent !important; /* no background */
  color: inherit !important; /* keep text color same */
}

.dark .section {
  background-color: #000000 !important;
  /* gray-800 */
  color: #f9fafb !important;
}

.p-iconfield .p-inputtext:not(:last-child) {
  background-color: white;
}

/* Section wrapper to match other steps */
.section {
  border: 1px solid #edf0f6;
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 6px 18px rgba(17, 38, 146, .06);
}

/* Label icon chip (same style you’ve used elsewhere) */
.fi {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: rgba(28, 13, 130, .08);
  color: var(--brand);
}

/* keep PrimeVue overlays above Bootstrap modal/backdrop */
:deep(.p-multiselect-panel),
:deep(.p-select-panel),
:deep(.p-dropdown-panel) {
  z-index: 2000 !important;
}

/* ========================  MultiSelect Styling   ============================= */
:deep(.p-multiselect-header) {
  background-color: white !important;
  color: black !important;
}

:deep(.p-multiselect-label) {
  color: #181818 !important;
}

:deep(.p-select .p-component .p-inputwrapper) {
  background: #fff !important;
  color: #181818 !important;
  border-bottom: 1px solid #ddd;
}

/* Options list container */
:deep(.p-multiselect-list) {
  background: #fff !important;
}

/* Each option */
:deep(.p-multiselect-option) {
  background: #fff !important;
  color: #181818 !important;
}

.dark .steps-nav {
  background-color: #c53939 !important;
  color: #fff !important;
}

/* Hover/selected option */
:deep(.p-multiselect-option.p-highlight) {
  background: #f0f0f0 !important;
  color: #181818 !important;
}

:deep(.p-multiselect),
:deep(.p-multiselect-panel),
:deep(.p-multiselect-token) {
  background: #fff !important;
  color: #181818 !important;
  border-color: #a4a7aa;
}

/* Checkbox box in dropdown */
:deep(.p-multiselect-overlay .p-checkbox-box) {
  background: #fff !important;
  border: 1px solid #ccc !important;
}



/* Search filter input */
:deep(.p-multiselect-filter) {
  background: #fff !important;
  color: #181818 !important;
  border: 1px solid #ccc !important;
}

/* Optional: adjust filter container */
:deep(.p-multiselect-filter-container) {
  background: #fff !important;
}

/* Selected chip inside the multiselect */
:deep(.p-multiselect-chip) {
  background: #e9ecef !important;
  color: #181818 !important;
  border-radius: 12px !important;
  border: 1px solid #ccc !important;
  padding: 0.25rem 0.5rem !important;
}

/* Chip remove (x) icon */
:deep(.p-multiselect-chip .p-chip-remove-icon) {
  color: #555 !important;
}

:deep(.p-multiselect-chip .p-chip-remove-icon:hover) {
  color: #dc3545 !important;
  /* red on hover */
}

/* keep PrimeVue overlays above Bootstrap modal/backdrop */
:deep(.p-multiselect-panel),
:deep(.p-select-panel),
:deep(.p-dropdown-panel) {
  z-index: 2000 !important;
}

/* ====================================================== */

/* ====================Select Styling===================== */
/* Entire select container */
:deep(.p-select) {
  background-color: white !important;
  color: black !important;
  border-color: #9b9c9c;
}

/* Options container */




:deep(.p-select-option) {
  background-color: #fff !;
  /* instead of 'none' */
  color: black !important;
}

/* Hovered option */
:deep(.p-select-option:hover) {
  background-color: #f0f0f0 !important;
  color: black !important;
}

/* Focused option (when using arrow keys) */
:deep(.p-select-option.p-focus) {
  background-color: #f0f0f0 !important;
  color: black !important;
}

:deep(.p-select-label) {
  color: #181818 !important;
}

:deep(.p-placeholder) {
  color: #80878e !important;
}

:deep(.p-component) {
  background-color: white !important;

}

/* ======================== Dark Mode MultiSelect ============================= */

.dark .bg-white {
  /* background-color: #000000 !important; */
  color: #fff !important;
}

.dark .p-select-header {
  background-color: #121212 !important;
}

.dark .section {
  background-color: #181818 !important;
}

:global(.dark .p-multiselect-header) {
  background-color: #181818 !important;
  color: #fff !important;
}

:global(.dark .p-multiselect-label) {
  color: #fff !important;
}

:global(.dark .p-select .p-component .p-inputwrapper) {
  background: #181818 !important;
  color: #fff !important;
  border-bottom: 1px solid #555 !important;
}

/* Options list container */
:global(.dark .p-multiselect-list) {
  background: #181818 !important;
}

/* Each option */
:global(.dark .p-multiselect-option) {
  background: #181818 !important;
  color: #fff !important;
}

/* Hover/selected option */
:global(.dark .p-multiselect-option.p-highlight),
:global(.dark .p-multiselect-option:hover) {
  background: #222 !important;
  color: #fff !important;
}

:global(.dark .p-multiselect),
:global(.dark .p-multiselect-panel),
:global(.dark .p-multiselect-token) {
  background: #181818 !important;
  color: #fff !important;
  border-color: #555 !important;
}

/* Checkbox box in dropdown */
:global(.dark .p-multiselect-overlay .p-checkbox-box) {
  background: #181818 !important;
  border: 1px solid #555 !important;
}

/* Search filter input */
:global(.dark .p-multiselect-filter) {
  background: #181818 !important;
  color: #fff !important;
  border: 1px solid #555 !important;
}

/* Optional: adjust filter container */
:global(.dark .p-multiselect-filter-container) {
  background: #181818 !important;
}

/* Selected chip inside the multiselect */
:global(.dark .p-multiselect-chip) {
  background: #111 !important;
  color: #fff !important;
  border: 1px solid #555 !important;
  border-radius: 12px !important;
  padding: 0.25rem 0.5rem !important;
}

/* Chip remove (x) icon */
:global(.dark .p-multiselect-chip .p-chip-remove-icon) {
  color: #ccc !important;
}

:global(.dark .p-multiselect-chip .p-chip-remove-icon:hover) {
  color: #f87171 !important;
  /* lighter red */
}


:deep(.p-inputtext) {
  background: white !important;
}

/* ==================== Dark Mode Select Styling ====================== */
:global(.dark .p-select) {
  background-color: #181818 !important;
  color: #fff !important;
  border-color: #555 !important;
}

/* Options container */
:global(.dark .p-select-list-container) {
  background-color: #181818 !important;
  color: #fff !important;
}

/* Each option */
:global(.dark .p-select-option) {
  background-color: #121212 !important;
  color: #fff !important;
}

:global(.dark .p-inputtext) {
  background-color: #121212 !important;
  color: #fff !important;
}

/* Hovered option */
:global(.dark .p-select-option:hover),
:global(.dark .p-select-option.p-focus) {
  background-color: #222 !important;
  color: #fff !important;
}

:global(.dark .p-select-label) {

  color: #fff !important;
}

:global(.dark .p-select-header) {
  background-color: #121212 !important;
  color: #fff !important;
}

:global(.dark .p-placeholder) {
  color: #aaa !important;
}


.dark .logo-card {
  background-color: #181818 !important;
}

.dark .logo-frame {
  background-color: #181818 !important;
}
</style>
