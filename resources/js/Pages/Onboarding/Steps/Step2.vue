<script setup>
import { reactive, ref, toRaw, watch, onMounted } from "vue";
import Select from "primevue/select";

const props = defineProps({ model: Object, formErrors: Object });

const emit = defineEmits(["save"]);

/* ------------------ FORM ------------------ */
const form = reactive({
    business_name: props.model?.business_name ?? "",
    business_type: props.model?.business_type ?? "",
    address: props.model?.address ?? "",
    email: props.model?.email ?? "",
    website: props.model?.website ?? "",
    legal_name: props.model?.legal_name ?? "",
    // phone pieces
    phone_country: props.model?.phone_country ?? "", // ISO "GB"
    phone_code: props.model?.phone_code ?? "", // "+44"
    phone_local: props.model?.phone_local ?? "", // "1234567890"
    phone: props.model?.phone ?? "", // "+441234567890"
    // logo
    logo: props.model?.logo ?? null,
    logo_file: null,
});



/* ------------------ BUSINESS TYPE ------------------ */
const businessTypeOptions = [
    { name: "Cafe", code: "cafe" },
    { name: "Restaurant", code: "restaurant" },
    { name: "Bakery", code: "bakery" },
    { name: "Takeaway", code: "takeaway" },
    { name: "Food Truck", code: "food_truck" },
];
const selectedBusinessType = ref(
    businessTypeOptions.find((o) => o.name === form.business_type) || null
);
watch(selectedBusinessType, (opt) => {
    form.business_type = opt?.name || "";
    emitSave();
});

/* ------------------ PHONE (flag + dial) ------------------ */
const dialOptions = [
    { name: "United Kingdom", iso: "GB", dial: "+44" },
    { name: "Pakistan", iso: "PK", dial: "+92" },
    { name: "United States", iso: "US", dial: "+1" },
    { name: "Canada", iso: "CA", dial: "+1" },
    { name: "United Arab Emirates", iso: "AE", dial: "+971" },
    { name: "Saudi Arabia", iso: "SA", dial: "+966" },
    { name: "Australia", iso: "AU", dial: "+61" },
    { name: "India", iso: "IN", dial: "+91" },
];

const selectedDial = ref(null);

function syncDialFromIso(iso) {
    if (!iso) return;

    const opt = dialOptions.find((o) => o.iso === iso.toUpperCase());

    if (opt) {
        selectedDial.value = opt;
        form.phone_country = opt.iso;
        form.phone_code = opt.dial;
    }
}

function buildFullPhone() {
    const code = (form.phone_code || "").trim();
    const local = (form.phone_local || "").replace(/\D+/g, "");
    form.phone = code + local;
}

onMounted(() => {
    console.log("onMounted -> props.model:", props.model);

    let iso = props.model?.phone_country || props.model?.country_code || "";
    let phoneLocal = props.model?.phone_local || "";

    // If phone_local is empty but full phone exists, extract it
    if (!phoneLocal && props.model?.phone) {
        const full = props.model.phone; // e.g. "+441234567890"
        const matched = dialOptions.find(opt => full.startsWith(opt.dial));
        if (matched) {
            iso = matched.iso;
            phoneLocal = full.slice(matched.dial.length);
        }
    }

    if (iso) syncDialFromIso(iso);
    form.phone_local = phoneLocal;
    buildFullPhone();
});

watch(selectedDial, (opt) => {
    if (!opt) return;
    form.phone_country = opt.iso;
    form.phone_code = opt.dial;
    buildFullPhone();
    emitSave();
});

watch(() => form.phone_local, (val) => {
    console.log("watch -> form.phone_local changed:", val);
    buildFullPhone();
    console.log("watch -> form.phone after phone_local change:", form.phone);
    emitSave();
});

watch(
    () => form.phone_local,
    () => {
        buildFullPhone();
        emitSave();
    }
);

/* ------------------ LOGO (crop / zoom modal hooks) ------------------ */
const showCropper = ref(false);
const showImageModal = ref(false);
const previewImage = ref(null);

function openImageModal(src) {
    previewImage.value = src || form.logo;
    if (!previewImage.value) return;
    showImageModal.value = true;
}

function onCropped({ file }) {
    form.logo_file = file;
    form.logo = URL.createObjectURL(file);
}



function emitSave() {
    emit("save", { step: 2, data: toRaw(form) });
}

/* helper for flags */
const flagUrl = (iso, size = "24x18") =>
    `https://flagcdn.com/${size}/${String(iso || "").toLowerCase()}.png`;
</script>

<template>
    <div>
        <h5 class="fw-bold mb-4">Step 2 of 9 - Business Information</h5>

        <div class="row g-4">
            <!-- Business Name -->
            <div class="col-12">
                <label class="form-label">Business Name*</label>
                <input class="form-control" v-model="form.business_name" @input="emitSave"
                    :class="{ 'is-invalid': formErrors?.business_name }" />
                <small v-if="formErrors?.business_name" class="text-danger">
                    {{ formErrors.business_name[0] }}
                </small>
            </div>

            <!-- Logo -->
            <div class="col-md-3">
                <small class="text-muted mt-2">Upload Logo</small>
                <div class="logo-card">
                    <div class="logo-frame" @click="form.logo && openImageModal(form.logo)">
                        <img v-if="form.logo" :src="form.logo" alt="Logo" />
                        <div v-else class="placeholder">
                            <i class="bi bi-image"></i>
                        </div>
                    </div>

                    <!-- Validation for logo -->
                    <small v-if="formErrors?.logo" class="text-danger">
                        {{ formErrors.logo[0] }}
                    </small>

                    <ImageCropperModal :show="showCropper" @close="showCropper = false" @cropped="onCropped" />
                </div>
            </div>

            <div class="col-md-9">
                <!-- Business Type -->
                <label class="form-label">Business Type*</label>
                <Select v-model="selectedBusinessType" :options="businessTypeOptions" optionLabel="name" :filter="true"
                    placeholder="Select business type" class="w-100"
                    :class="{ 'is-invalid': formErrors?.business_type }">
                    <template #value="{ value, placeholder }">
                        <span v-if="value">{{ value.name }}</span>
                        <span v-else>{{ placeholder }}</span>
                    </template>
                    <template #option="{ option }">
                        <span>{{ option.name }}</span>
                    </template>
                </Select>
                <small v-if="formErrors?.business_type" class="text-danger">
                    {{ formErrors.business_type[0] }}
                </small>
<br>
                <!-- Address -->
                <label class="form-label mt-3">Address*</label>
                <input class="form-control" v-model="form.address" @input="emitSave"
                    :class="{ 'is-invalid': formErrors?.address }" />
                <small v-if="formErrors?.address" class="text-danger">
                    {{ formErrors.address[0] }}
                </small>

                <div class="row g-3 mt-1">
                    <!-- Phone -->
                    <div class="col-md-6">
                        <label class="form-label">Phone*</label>
                        <div class="input-group">
                            <span class="input-group-text p-0">
                                <Select v-model="selectedDial" :options="dialOptions" optionLabel="name" :filter="true"
                                    placeholder="Code" class="dial-select" />
                            </span>
                            <input class="form-control" inputmode="numeric" placeholder="Phone number"
                                v-model="form.phone_local" :class="{ 'is-invalid': formErrors?.phone_local }" />
                        </div>
                        <small v-if="formErrors?.phone_local" class="text-danger">
                            {{ formErrors.phone_local[0] }}
                        </small>
                    </div>

                    <!-- Email -->
                    <div class="col-md-6">
                        <label class="form-label">Email*</label>
                        <input type="email" class="form-control" v-model="form.email" @input="emitSave"
                            :class="{ 'is-invalid': formErrors?.email }" />
                        <small v-if="formErrors?.email" class="text-danger">
                            {{ formErrors.email[0] }}
                        </small>
                    </div>
                </div>

                <!-- Website -->
                <label class="form-label mt-3">Website</label>
                <input class="form-control" v-model="form.website" @input="emitSave"
                    :class="{ 'is-invalid': formErrors?.website }" />
                <small v-if="formErrors?.website" class="text-danger">
                    {{ formErrors.website[0] }}
                </small>
                <br>
                <!-- Legal Name -->
                <label class="form-label mt-3">Legal Name*</label>
                <input class="form-control" v-model="form.legal_name" @input="emitSave"
                    :class="{ 'is-invalid': formErrors?.legal_name }" />
                <small v-if="formErrors?.legal_name" class="text-danger">
                    {{ formErrors.legal_name[0] }}
                </small>
            </div>
        </div>
    </div>


    <!-- Modals -->
</template>

<style scoped>
.logo-card {
    background: #fff;
    border: 1px solid #edf0f5;
    border-radius: 1rem;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    min-height: 220px;
    box-shadow: 0 6px 18px rgba(17, 38, 146, 0.05);
}

.logo-frame {
    width: 140px;
    height: 140px;
    border-radius: 1rem;
    border: 1px dashed #cfd6e4;
    background: #f7f9fc;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    cursor: pointer;
}

.logo-frame img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.logo-frame .placeholder {
    color: #8b97a7;
    font-size: 28px;
    line-height: 0;
}

.dial-select {
    min-width: 130px;
    border: 0;
}

.input-group>.input-group-text {
    background: transparent;
    border-right: 0;
}

.input-group .form-control {
    border-left: 0;
}
</style>
