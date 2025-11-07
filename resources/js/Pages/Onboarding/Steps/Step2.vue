<script setup>
import { reactive, ref, toRaw, watch, onMounted, computed } from "vue";
import Select from "primevue/select";
import ImageCropperModal from "@/Components/ImageCropperModal.vue";

const props = defineProps({ model: Object, formErrors: Object, isOnboarding: { type: Boolean, default: false } });

const emit = defineEmits(["save"]);

/* UK Phone Configuration */
const UK_COUNTRY_CODE = "+44";
const UK_PHONE_LENGTH = 10; // UK local number length without country code
const UK_AREA_CODES = {
    "20": "London",
    "121": "Birmingham",
    "161": "Manchester",
    "113": "Leeds",
    "141": "Glasgow",
    "151": "Liverpool",
    "191": "Newcastle",
    "118": "Reading",
    "131": "Edinburgh",
    "29": "Cardiff"
};

/* ------------------ FORM ------------------ */
const form = reactive({
    business_name: props.model?.business_name ?? "",
    business_type: props.model?.business_type ?? "",
    address: props.model?.address ?? "",
    email: props.model?.email ?? "",
    website: props.model?.website ?? "",
    legal_name: props.model?.legal_name ?? "",
    // phone - UK only
    phone_country: "GB",
    phone_code: UK_COUNTRY_CODE,
    phone_local: props.model?.phone_local ?? "",
    phone: props.model?.phone ?? "",
    logo: props.model?.logo ?? null,
    logo_url: props.model?.logo_url ?? null,
    logo_file: null,
});

// Phone validation state
const phoneError = ref('');
const isPhoneValid = ref(false);
const phoneWarnings = ref([]);

// Validate UK phone number (International format: 10 digits without +44)
const validatePhone = () => {
    phoneError.value = '';
    phoneWarnings.value = [];
    isPhoneValid.value = false;

    const phoneLocal = String(form.phone_local || '').trim();

    if (!phoneLocal) {
        phoneError.value = 'Phone number is required';
        return false;
    }

    // Remove any non-digit characters
    let cleanPhone = phoneLocal.replace(/\D+/g, '');

    // International UK format: 10 digits (without +44)
    // e.g., 7311865859
    const UK_INTL_LENGTH = 10;

    // Check length
    if (cleanPhone.length < UK_INTL_LENGTH) {
        phoneError.value = `UK phone number must be exactly ${UK_INTL_LENGTH} digits`;
        return false;
    }

    if (cleanPhone.length > UK_INTL_LENGTH) {
        phoneError.value = `UK phone number cannot exceed ${UK_INTL_LENGTH} digits. You entered ${cleanPhone.length} digits`;
        return false;
    }

    // Check valid UK patterns (must start with valid number)
    const validPatterns = /^[1-9]\d{9}$/;
    if (!validPatterns.test(cleanPhone)) {
        phoneError.value = 'Invalid UK phone number format';
        return false;
    }

    isPhoneValid.value = true;
    return true;
};

/* ------------------ BUSINESS TYPE ------------------ */
const businessTypeOptions = ref([
    { name: "Cafe", code: "cafe" },
    { name: "Restaurant", code: "restaurant" },
    { name: "Bakery", code: "bakery" },
    { name: "Takeaway", code: "takeaway" },
    { name: "Food Truck", code: "food_truck" },
]);

const initializeBusinessType = () => {
    const savedType = props.model?.business_type;

    if (!savedType) {
        selectedBusinessType.value = null;
        return;
    }

    let found = businessTypeOptions.value.find((o) => o.name === savedType);

    if (!found) {
        const customOption = {
            name: savedType,
            code: savedType.toLowerCase().replace(/\s+/g, '_'),
            custom: true
        };
        businessTypeOptions.value.push(customOption);
        found = customOption;
    }

    selectedBusinessType.value = found;
};

const selectedBusinessType = ref(null);
const businessTypeFilterValue = ref('');

const isNewBusinessType = computed(() => {
    if (!businessTypeFilterValue.value || businessTypeFilterValue.value.trim() === '') return false;
    const searchTerm = businessTypeFilterValue.value.toLowerCase().trim();
    return !businessTypeOptions.value.some(opt =>
        opt.name.toLowerCase() === searchTerm
    );
});

const addCustomBusinessType = () => {
    const customName = businessTypeFilterValue.value.trim();
    if (!customName) return;

    const newOption = {
        name: customName,
        code: customName.toLowerCase().replace(/\s+/g, '_'),
        custom: true
    };

    businessTypeOptions.value.push(newOption);
    selectedBusinessType.value = newOption;
    businessTypeFilterValue.value = '';
};

watch(selectedBusinessType, (opt) => {
    form.business_type = opt?.name || "";
    emitSave();
});

function buildFullPhone() {
    const local = String(form.phone_local || "").replace(/\D+/g, "");
    form.phone = form.phone_code + local;
}

onMounted(async () => {
    initializeBusinessType();
    
    // Initialize phone with existing data if available
    if (props.model?.phone_local) {
        form.phone_local = props.model.phone_local;
    } else if (props.model?.phone) {
        // Extract local number from full phone if only full phone exists
        const full = props.model.phone;
        form.phone_local = full.replace(UK_COUNTRY_CODE, '').trim();
    }

    buildFullPhone();

    // Initialize logo if needed
    if (!form.logo_url && props.model?.upload_id && props.model?.logo_path) {
        form.logo_url = props.model?.logo_url || `/storage/${props.model?.logo_path}`;
    }
});

watch(() => form.phone_local, (val) => {
    buildFullPhone();
    validatePhone();
    emitSave();
});

/* ------------------ LOGO (crop / zoom modal hooks) ------------------ */
const showCropper = ref(false);
const showImageModal = ref(false);
const previewImage = ref(null);

function openImageModal(src) {
    previewImage.value = src || form.logo_url;
    if (!previewImage.value) return;
    showImageModal.value = true;
}

function onCropped({ file }) {
    form.logo_file = file;
    form.logo_url = URL.createObjectURL(file);
    emitSave();
}

function emitSave() {
    const isValidNow = validatePhone();

    emit("save", {
        step: 2,
        data: toRaw(form),
        valid: isValidNow
    });
}
</script>

<template>
    <div>
        <h5 class="fw-bold mb-4" v-if="props.isOnboarding">Step 2 of 9 - Business Information</h5>

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
            <div class="col-md-4">
                <small class="text-muted mt-2">Upload Logo</small>
                <div class="logo-card">
                    <div class="logo-frame" @click="form.logo_url && openImageModal()">
                        <img v-if="form.logo_url" :src="form.logo_url" alt="Logo" />
                        <div v-else class="placeholder">
                            <i class="bi bi-image"></i>
                        </div>
                    </div>

                    <ImageCropperModal :show="showCropper" @close="showCropper = false" @cropped="onCropped" />
                    <small v-if="formErrors?.logo_file" class="text-danger">
                        {{ formErrors.logo_file[0] }}
                    </small>
                </div>
            </div>

            <div class="col-md-8">
                <!-- Business Type -->
                <label class="form-label">Business Type*</label>
                <Select v-model="selectedBusinessType" :options="businessTypeOptions" optionLabel="name" :filter="true"
                    @filter="(e) => businessTypeFilterValue = e.value" :pt="{
                        listContainer: { class: 'bg-white text-black' },
                        option: { class: 'text-black hover:bg-gray-100' },
                        header: { class: 'bg-white text-black' },
                        IconField: { class: 'bg-white' },
                        InputText: { class: 'bg-white' },
                        pcFilter: { class: 'bg-white' },
                        pcFilterContainer: { class: 'bg-white' }
                    }" placeholder="Select or type business type" class="w-100"
                    :class="{ 'is-invalid': formErrors?.business_type }">

                    <template #value="{ value, placeholder }">
                        <span v-if="value">{{ value.name }}</span>
                        <span v-else>{{ placeholder }}</span>
                    </template>

                    <template #option="{ option }">
                        <div class="d-flex align-items-center gap-2">
                            <span>{{ option.name }}</span>
                            <span v-if="option.custom" class="badge bg-primary text-white"
                                style="font-size: 10px;">Custom</span>
                        </div>
                    </template>

                    <template #footer>
                        <div v-if="isNewBusinessType" class="p-2 border-top">
                            <button type="button" class="btn btn-sm btn-primary w-100" @click="addCustomBusinessType">
                                <i class="bi bi-plus-circle me-1"></i>
                                Add "{{ businessTypeFilterValue }}"
                            </button>
                        </div>
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
                    <!-- UK Phone Number -->
                    <div class="col-md-6">
                        <label class="form-label">UK Phone*</label>
                        <div class="input-group">
                            <!-- UK Country Code (readonly) -->
                            <span class="input-group-text p-0">
                                <input type="text" class="form-control text-center border-0 bg-light fw-semibold"
                                    value="+44" readonly style="width: 70px;" />
                            </span>

                            <!-- Phone Input - UK International Format -->
                            <input class="form-control phone-input" inputmode="numeric" placeholder="7311865859"
                                v-model="form.phone_local" @blur="validatePhone" :class="{
                                    'is-invalid': phoneError,
                                    'is-valid': isPhoneValid && form.phone_local
                                }" maxlength="10" />
                        </div>

                        <!-- Validation messages -->
                        <small v-if="phoneError" class="text-danger d-block mt-1">
                            <i class="bi bi-exclamation-circle me-1"></i>{{ phoneError }}
                        </small>
                        <small v-else-if="formErrors?.phone_local" class="text-danger d-block mt-1">
                            {{ formErrors.phone_local[0] }}
                        </small>
                        <small v-if="isPhoneValid && form.phone_local" class="text-success d-block mt-1">
                            <i class="bi bi-check-circle me-1"></i>✓ Valid UK phone number
                        </small>

                        <!-- Warnings (area code, mobile type) -->
                        <small v-for="(warning, idx) in phoneWarnings" :key="idx" class="text-info d-block mt-1">
                            <i class="bi bi-info-circle me-1"></i>{{ warning }}
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
            </div>
        </div>
    </div>
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

.p-inputtext {
    background: white;
    color: #000000 !important;
}

:global(.p-inputtext) {
    color: #000 !important;
}

.btn-custom {
    background-color: #1C0D82 !important;
    color: #fff !important;
    padding: 8px 0px !important;
}

.custom-btn-border {
    border-top: 1px solid #555;
    background-color: #181818 !important;
}

.dark .logo-card {
    background-color: #181818;
    color: #fff !important;
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

.dark .logo-frame {
    background-color: #181818;
    color: #fff !important;
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

.dark input {
    background-color: #181818 !important;
    color: #ffffff;
}

.dark textarea {
    background-color: #181818 !important;
    color: #ffffff;
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
:deep(.p-select-list-container) {
    background-color: white !important;
    color: black !important;
}

/* Each option */
:deep(.p-select-option) {
    background-color: transparent !important;
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

/* ======================== Dark Mode MultiSelect ============================= */

.dark .bg-white {
    background-color: #000000 !important;
    color: #fff !important;
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
    background-color: transparent !important;
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
