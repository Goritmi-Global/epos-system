<script setup>
import { reactive, ref, onMounted, watch, computed } from "vue";
import axios from "axios";
import Select from "primevue/select";
import { toast } from "vue3-toastify";

const selectedCountry = ref(null);   // object { name, code }
const selectedLanguage = ref(null);  // object { name, code, flag }
const countries = ref([]);

// Your existing languages source (only English right now)
const languages = ref([
  { label: "English", value: "en", flag: "https://flagcdn.com/w20/gb.png" },
]);

// Map languages -> { name, code, flag } for PrimeVue Select
const languagesOptions = computed(() =>
  languages.value.map((l) => ({
    name: l.label,
    code: l.value,
    flag: l.flag,
  }))
);

const form = reactive({
  country_code: "",
  country_name: "",
  timezone: "",
  language: "en",
  languages_supported: [],
});

// Helpers
const flagUrl = (code, size = "24x18") =>
  `https://flagcdn.com/${size}/${String(code || "").toLowerCase()}.png`;

// Load all countries -> shape { name, code }
async function fetchCountries() {
  const { data } = await axios.get("/api/countries");
  countries.value = data.map((c) => ({
    name: c.name ?? c.label,
    code: String(c.code ?? c.iso2 ?? c.value ?? "").toUpperCase(),
  }));
}

// Load details (timezone etc.)
async function fetchCountryDetails(code) {
  if (!code) return;
  try {
    const { data } = await axios.get(`/api/country/${code}`);
    form.timezone = data.timezone;
  } catch (error) {
    toast.warning(error.response?.data?.message || "Failed to fetch country details");
  }
}

// Detect country and prefill
async function detectCountry() {
  try {
    const { data } = await axios.get("/api/geo");
    form.country_code = data.country_code || "";
    form.country_name = data.country_name || "";
    if (data.timezone) form.timezone = data.timezone;
  } catch (error) {
    toast.warning(error.response?.data?.message || "Failed to detect country");
  }
}

// Sync the Selects from form values
function syncSelectedCountry() {
  selectedCountry.value = countries.value.find((o) => o.code === form.country_code) || null;
}
function syncSelectedLanguage() {
  selectedLanguage.value =
    languagesOptions.value.find((o) => o.code === form.language) ||
    languagesOptions.value[0] ||
    null;
}

onMounted(async () => {
  await fetchCountries();
  await detectCountry();
  syncSelectedCountry();
  syncSelectedLanguage();
  if (form.country_code) await fetchCountryDetails(form.country_code);
});

// Update form when user changes selects
watch(selectedCountry, (opt) => {
  if (!opt) return;
  form.country_code = opt.code;
  form.country_name = opt.name;
  fetchCountryDetails(opt.code);
});
watch(selectedLanguage, (opt) => {
  if (!opt) return;
  form.language = opt.code; // 'en'
});

 
</script>

<template>
  <div class="card p-4">
    <h5 class="mb-3">Welcome & Language Selection</h5>

    <div class="row g-3">
      <!-- Country (full width) -->
      <div class="col-12">
        <label class="form-label">Country</label>

        <Select
          v-model="selectedCountry"
          :options="countries"
          optionLabel="name"
          :filter="true"
          placeholder="Select a Country"
          class="w-100"
        >
          <!-- Selected value -->
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
        <label class="form-label">Time Zone</label>
        <input type="text" class="form-control" v-model="form.timezone" />
      </div>

      <!-- Language (same Select as countries; only English option) -->
      <div class="col-md-6">
        <label class="form-label">Language</label>

        <Select
          v-model="selectedLanguage"
          :options="languagesOptions"
          optionLabel="name"
          :filter="false"
          placeholder="Select a Language"
          class="w-100"
        >
          <!-- Selected value -->
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
</template>
