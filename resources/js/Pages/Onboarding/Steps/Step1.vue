<script setup>
import { reactive, ref, onMounted, watch, computed } from "vue"
import axios from "axios"
import Select from "primevue/select"
import { toast } from "vue3-toastify"

const selectedCountry = ref(null)   // { name, code }
const selectedLanguage = ref(null)  // { name, code, flag }
const countries = ref([])

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

async function fetchCountries() {
  const { data } = await axios.get("/api/countries")
  countries.value = data.map(c => ({
    name: c.name ?? c.label,
    code: String(c.code ?? c.iso2 ?? c.value ?? "").toUpperCase(),
  }))
}

async function fetchCountryDetails(code) {
  if (!code) return
  try {
    const { data } = await axios.get(`/api/country/${code}`)
    form.timezone = data.timezone
  } catch (error) {
    toast.warning(error.response?.data?.message || "Failed to fetch country details")
  }
}

async function detectCountry() {
  try {
    const { data } = await axios.get("/api/geo")
    form.country_code = data.country_code || ""
    form.country_name = data.country_name || ""
    if (data.timezone) form.timezone = data.timezone
  } catch (error) {
    toast.warning(error.response?.data?.message || "Failed to detect country")
  }
}

function syncSelectedCountry() {
  selectedCountry.value =
    countries.value.find(o => o.code === form.country_code) || null
}
function syncSelectedLanguage() {
  selectedLanguage.value =
    languagesOptions.value.find(o => o.code === form.language) ||
    languagesOptions.value[0] ||
    null
}

onMounted(async () => {
  await fetchCountries()
  await detectCountry()
  syncSelectedCountry()
  syncSelectedLanguage()
  if (form.country_code) await fetchCountryDetails(form.country_code)
})

watch(selectedCountry, (opt) => {
  if (!opt) return
  form.country_code = opt.code
  form.country_name = opt.name
  fetchCountryDetails(opt.code)
})
watch(selectedLanguage, (opt) => {
  if (!opt) return
  form.language = opt.code
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
        </div>

        <!-- Timezone -->
        <div class="col-md-6">
          <label class="form-label d-flex align-items-center gap-2">
            
            Time Zone
          </label>
          <input type="text" class="form-control" v-model="form.timezone" readonly />
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
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
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
