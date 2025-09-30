<script setup>
import { reactive, ref, onMounted, watch, computed } from "vue"
import axios from "axios"
import Select from "primevue/select"
import { toast } from "vue3-toastify"

const emit = defineEmits(["save"])

const selectedCountry = ref(null)
const selectedLanguage = ref(null)
const countries = ref([])
const props = defineProps({ formErrors: Object });
const languages = ref([
  { label: "English", value: "en", flag: "https://flagcdn.com/w20/gb.png" },
])

const languagesOptions = computed(() =>
  languages.value.map(l => ({ name: l.label, code: l.value, flag: l.flag }))
)

const form = reactive({
  country_code: "",
  country_name: "",
  timezone: "",
  language: "en",
  languages_supported: [],
})

const flagUrl = (code, size = "24x18") =>
  `https://flagcdn.com/${size}/${String(code || "").toLowerCase()}.png`

const emitSave = () => {
  try {
    emit("save", { step: 1, data: { ...form } })
  } catch (e) {
    console.error("Step1 emitSave error:", e)
  }
}

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

const fetchCountryDetails = async (code) => {
  if (!code) return
  try {
    const { data } = await axios.get(`/api/country/${code}`)
    form.timezone = data.timezone
    emitSave()
  } catch (error) {
    console.error("fetchCountryDetails error:", error)
    toast.warning(error.response?.data?.message || "Failed to fetch country details")
  }
}

const detectCountry = async () => {
  try {
    const { data } = await axios.get("/api/geo")
    form.country_code = data.country_code || ""
    form.country_name = data.country_name || ""
    if (data.timezone) form.timezone = data.timezone
    emitSave()
  } catch (error) {
    console.error("detectCountry error:", error)
    toast.warning(error.response?.data?.message || "Failed to detect country")
  }
}

const syncSelectedCountry = () => {
  try {
    selectedCountry.value =
      countries.value.find(o => o.code === form.country_code) || null
  } catch (e) { console.error("syncSelectedCountry error:", e) }
}
const syncSelectedLanguage = () => {
  try {
    selectedLanguage.value =
      languagesOptions.value.find(o => o.code === form.language) ||
      languagesOptions.value[0] || null
  } catch (e) { console.error("syncSelectedLanguage error:", e) }
}

onMounted(async () => {
  try {
    await fetchCountries()
    await detectCountry()
    syncSelectedCountry()
    syncSelectedLanguage()
    if (form.country_code) await fetchCountryDetails(form.country_code)
  } catch (e) {
    console.error("Step1 onMounted error:", e)
  }
})

watch(
  [countries, () => form.country_code],
  () => {
    console.log("WATCH TRIGGERED")
    console.log("form.country_code:", form.country_code)
    console.log("countries.value:", countries.value)
    if (!form.country_code || !countries.value.length) return
    selectedCountry.value =
      countries.value.find(
        c => String(c.code).toUpperCase() === String(form.country_code).toUpperCase()
      ) || null
    console.log("selectedCountry synced:", selectedCountry.value)
  },
  { immediate: true }
)


watch(selectedCountry, (opt) => {
  try {
    if (!opt) return
    form.country_code = opt.code
    form.country_name = opt.name
    fetchCountryDetails(opt.code)
    emitSave()
  } catch (e) { console.error("selectedCountry watch error:", e) }
}) 

watch(selectedLanguage, (opt) => {
  try {
    if (!opt) return
    form.language = opt.code
    emitSave()
  } catch (e) { console.error("selectedLanguage watch error:", e) }
})

</script>


<template>
  <div>
    <h5 class="fw-bold mb-3">Step 1 of 9 - Welcome & Language Selection</h5>

    <div class="section p-3 p-md-4">
      <div class="row g-3">
        <!-- Country -->
        <div class="col-12">
          <label class="form-label d-flex align-items-center gap-2">
           
            Country
          </label>

          <Select
            v-model="selectedCountry"
            :options="countries"
            optionLabel="name"
            :filter="true"
            placeholder="Select a Country"
            class="w-100"
            :class="{'is-invalid': formErrors.country_name}"
          >
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
            <small v-if="formErrors?.country_name" class="text-danger">
                    {{ formErrors.country_name[0] }}
                </small>
        </div>

        <!-- Timezone -->
        <div class="col-md-6">
          <label class="form-label d-flex align-items-center gap-2">
            
            Time Zone
          </label>
          <input type="text" class="form-control" v-model="form.timezone" :class="{'is-invalid': formErrors.timezone}"  />
          <small v-if="formErrors?.timezone" class="text-danger">
                    {{ formErrors.timezone[0] }}
                </small>
        </div>

        <!-- Language -->
        <div class="col-md-6">
          <label class="form-label d-flex align-items-center gap-2">
             
            Language
          </label>

          <Select
            v-model="selectedLanguage"
            :options="languagesOptions"
            optionLabel="name"
            :filter="false"
            placeholder="Select a Language"
            class="w-100"
            :class="{'is-invalid': formErrors.language}"
          >
            <!-- Selected -->
            <template #value="{ value, placeholder }">
              <div v-if="value" class="d-flex align-items-center gap-2">
                <img :alt="value.name" :src="value.flag" width="18" height="14" style="object-fit:cover" />
                <span>{{ value.name }}</span>
              </div>
              <span v-else>{{ placeholder }}</span>
            </template>

            <!-- Options -->
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
.dark .section{
   background-color: #111827 !important;
    /* gray-800 */
    color: #f9fafb !important;
}


:root { --brand:#1C0D82; }

/* Section wrapper to match other steps */
.section{
  border:1px solid #edf0f6;
  border-radius:12px;
  background:#fff;
  box-shadow:0 6px 18px rgba(17,38,146,.06);
}

/* Label icon chip (same style you’ve used elsewhere) */
.fi{
  display:inline-flex; align-items:center; justify-content:center;
  width:32px; height:32px; border-radius:8px;
  background:rgba(28,13,130,.08); color:var(--brand);
}
</style>
