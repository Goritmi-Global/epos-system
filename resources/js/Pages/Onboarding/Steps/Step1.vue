<script setup>
import { reactive, ref, onMounted, watch } from "vue";
import axios from "axios";
import Multiselect from "@vueform/multiselect";
import "@vueform/multiselect/themes/default.css";
import { toast } from "vue3-toastify";

const countries = ref([]);

// Predefined languages with flags
const languages = ref([
   
  { label: 'English', value: 'en', flag: 'https://flagcdn.com/w20/gb.png' }
]);

const form = reactive({
  country_code: "",
  country_name: "",
  timezone: "",
  language: "en",
  languages_supported: [],
});

// Load all countries for dropdown
async function fetchCountries() {
  const { data } = await axios.get('/api/countries');
  countries.value = data.map(c => ({
    label: c.label ?? c.name,
    value: (c.value ?? c.code ?? c.iso2 ?? '').toUpperCase(),
    flag:  c.flag  ?? `https://flagcdn.com/w20/${(c.code ?? c.iso2 ?? '').toLowerCase()}.png`
  }));
}


// Load details (languages & timezone) for a country
async function fetchCountryDetails(code) {
  if (!code) return;
  try {
    const { data } = await axios.get(`/api/country/${code}`);
    form.timezone = data.timezone;
  } catch (error) {
    toast.warning(
      error.response?.data?.message || "Failed to fetch country details"
    );
  }
}

// Auto-detect country on first load
async function detectCountry() {
  try {
    const { data } = await axios.get("/api/geo");
    form.country_code = data.country_code;
    form.country_name = data.country_name;
    if (data.timezone) {
      form.timezone = data.timezone;
    }
  } catch (error) {
    toast.warning(
      error.response?.data?.message || "Failed to detect country"
    );
  }
}

onMounted(async () => {
  await fetchCountries();
  await detectCountry();
});

watch(
  () => form.country_code,
  async (newCode, oldCode) => {
    if (newCode && oldCode) {
      await fetchCountryDetails(newCode);
    }
  }
);

function submit() {
  console.log(form);
}
</script>

<template>
  <div class="card p-4">
    <h5 class="mb-3">Welcome & Language Selection</h5>

    <div class="row g-3">
      <!-- Country -->
      <div class="col-12">
        <label class="form-label">Country</label>
       
        <Multiselect
          v-model="form.country_code"
          :options="countries"
          placeholder="Select country"
          track-by="value"
          label="label"
          value-prop="value"
          searchable
        >
        
          <!-- Dropdown items -->
          <template #option="{ option }">
            <span class="d-flex align-items-center">
              <img v-if="option?.flag" :src="option.flag" class="me-2" style="width:20px; height:14px;" />
              {{ option?.label || '' }}
            </span>
          </template>

          <!-- Selected item display -->
          <template #singlelabel="{ option }">
            <span class="d-flex align-items-center">
              <img v-if="option?.flag" :src="option.flag" class="me-2" style="width:20px; height:14px;" />
              {{ option?.label || '' }}
            </span>
          </template>
        </Multiselect>
      </div>

      <!-- Timezone -->
      <div class="col-md-6">
        <label class="form-label">Time Zone</label>
        <input type="text" class="form-control" v-model="form.timezone" />
      </div>

      <!-- Language -->
      <div class="col-md-6">
        <label class="form-label">Language</label>
        <Multiselect
          v-model="form.language"
          :options="languages"
          placeholder="Select language"
          track-by="value"
          label="label"
          value-prop="value"
          searchable
        >
          <!-- Dropdown items -->
          <template #option="{ option }">
            <span class="d-flex align-items-center">
              <img v-if="option?.flag" :src="option.flag" class="me-2" style="width:20px; height:14px;" />
              {{ option?.label || '' }}
            </span>
          </template>

          <!-- Selected item display -->
          <template #singlelabel="{ option }">
            <span class="d-flex align-items-center">
              <img v-if="option?.flag" :src="option.flag" class="me-2" style="width:20px; height:14px;" />
              {{ option?.label || '' }}
            </span>
          </template>
        </Multiselect>
      </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
      <button type="button" class="btn btn-primary" @click="submit">
        Save
      </button>
    </div>
  </div>
</template>
