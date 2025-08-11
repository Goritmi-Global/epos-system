<script setup>
import { reactive, ref, onMounted, watch } from "vue";
import axios from "axios";
import Multiselect from "@vueform/multiselect";
import "@vueform/multiselect/themes/default.css";
import { toast } from "vue3-toastify";

const countries = ref([]);
const languages = ref([{ label: "English", value: "US" }]);

const form = reactive({
    country_code: "",
    country_name: "",
    timezone: "",
    language: "US",
    languages_supported: [],
});

// Load all countries for dropdown
async function fetchCountries() {
    const { data } = await axios.get("/api/countries");
    countries.value = data.map((c) => ({
        label: c.name,
        value: c.code,
    }));
}

// Load details (languages & timezone) for a country
async function fetchCountryDetails(code) {
    if (!code) return;

    try {
        const { data } = await axios.get(`/api/country/${code}`);

        console.log(data);
        form.timezone = data.timezone;
    } catch (error) {
        console.error("Error fetching country details:", error);

        toastr.warning(
            error.response?.data?.message || "Failed to fetch country details.",
            "Error"
        );
    }
}

// Auto-detect country on first load
async function detectCountry() {
    try {
        const { data } = await axios.get("/api/geo");
        console.log(data);
        form.country_code = data.country_code;
        form.country_name = data.country_name;
        if (data.timezone) {
            form.timezone = data.timezone;
        }
    } catch (error) {
        console.error("Error detecting country:", error);
        toastr.warning(
            error.response?.data?.message || "Failed to detect country.",
            "Error"
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
            // only if user changes country
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
                />
                <small v-if="form.country_name" class="text-muted"> </small>
            </div>

            <!-- Timezone -->
            <div class="col-md-6">
                <label class="form-label">Time Zone</label>

                <input
                    type="text"
                    class="form-control"
                    v-model="form.timezone"
                />
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
                />
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="button" class="btn btn-primary" @click="submit">
                Save
            </button>
        </div>
    </div>
</template>
