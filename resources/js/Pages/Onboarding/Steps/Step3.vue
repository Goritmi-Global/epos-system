<script setup>
import { reactive, toRaw, watch } from "vue";
import Select from "primevue/select";

const STEP_NUMBER = 3;
const props = defineProps({ model: Object, formErrors: Object });
const emit = defineEmits(["save"]);

const form = reactive({
    currency: props.model?.currency ?? "PKR",
    currency_symbol_position: props.model?.currency_symbol_position ?? "after",
    date_format: props.model?.date_format ?? "dd/MM/yyyy",
    number_format: props.model?.number_format ?? "1,000",
    time_format: props.model?.time_format ?? "12-hour",
});

const currencies = ["PKR", "GBP", "USD", "EUR", "AED", "SAR"];
// 💰 Money Symbol Positions
const symbolPositions = [
    { label: "Before Amount (Rs 1000)", value: "before" },
    { label: "After Amount (1000 Rs)", value: "after" },
    { label: "ISO Before (USD 1000)", value: "iso-before" },
    { label: "ISO After (1000 USD)", value: "iso-after" },
    { label: "With Currency Name (1000 Rupees)", value: "name" },
];

// 📅 Date Formats (Most Common Worldwide)
const dateFormats = [
    "dd/MM/yyyy",   // 31/12/2025
    "MM/dd/yyyy",   // 12/31/2025
    "yyyy-MM-dd",   // 2025-12-31
    "dd-MM-yyyy",   // 31-12-2025
    "yyyy/MM/dd",   // 2025/12/31
    "dd MMM yyyy",  // 31 Dec 2025
    "MMM dd, yyyy", // Dec 31, 2025
];

// 🕒 Time Formats
const timeFormats = [
    { label: "12 Hour (02:30 PM)", value: "12-hour" },
    { label: "24 Hour (14:30)", value: "24-hour" },
];

// 🔢 Number Formats (Different Separators & Groupings)
const numberFormats = [
    "1,000",     // US/UK
    "1.000",     // Europe
    "1 000",     // French/International
    "1'000",     // Swiss
    "1000",      // Plain
    "12,34,567", // Indian Lakh/Crore style
    "١٬٠٠٠",    // Arabic-Indic
    "１０００",  // Full-width Japanese
];
 

watch(
    () => props.model,
    (newModel) => {
        if (newModel) {
            form.currency = newModel.currency ?? "PKR";
            form.currency_symbol_position =
                newModel.currency_symbol_position ?? "after";
            form.date_format = newModel.date_format ?? "dd/MM/yyyy";
            form.number_format = newModel.number_format ?? "1,000";
            form.time_format = newModel.time_format ?? "12-hour";
        }
    },
    { immediate: true }
);

// sync parent profile whenever something in this step changes
const emitSave = () => {
    try {
        emit("save", { step: STEP_NUMBER, data: toRaw(form) });
    } catch (e) {
        console.error(`Step ${STEP_NUMBER} emitSave error:`, e);
    }
};

watch(form, emitSave, { deep: true, immediate: true });
</script>

<template>
    <div>
        <h5 class="fw-bold mb-4">Step 3 of 9 - Currency & Locale</h5>

        <div class="row g-3">
            <!-- Currency -->
            <div class="col-md-6">
                <label class="form-label">Select Currency*</label>
                <Select
                    v-model="form.currency"
                    :options="currencies"
                    placeholder="Select currency"
                    class="w-100"
                    :class="{ 'is-invalid': formErrors?.currency }"
                    @change="emitSave"
                />
                <small v-if="formErrors?.currency" class="text-danger">
                    {{ formErrors.currency[0] }}
                </small>
            </div>

            <!-- Symbol Position -->
            <div class="col-md-6">
                <label class="form-label">Symbol Position*</label>
                <Select
                    v-model="form.currency_symbol_position"
                    :options="symbolPositions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Choose position"
                    class="w-100"
                    :class="{
                        'is-invalid': formErrors?.currency_symbol_position,
                    }"
                    @change="emitSave"
                />
                <small
                    v-if="formErrors?.currency_symbol_position"
                    class="text-danger"
                >
                    {{ formErrors.currency_symbol_position[0] }}
                </small>
            </div>

            <!-- Date Format -->
            <div class="col-md-6">
                <label class="form-label">Date Format*</label>
                <Select
                    v-model="form.date_format"
                    :options="dateFormats"
                    placeholder="Select format"
                    class="w-100"
                    :class="{ 'is-invalid': formErrors?.date_format }"
                    @change="emitSave"
                />
                <small v-if="formErrors?.date_format" class="text-danger">
                    {{ formErrors.date_format[0] }}
                </small>
            </div>

            <!-- Number Format -->
            <div class="col-md-6">
                <label class="form-label">Number Format*</label>
                <Select
                    v-model="form.number_format"
                    :options="numberFormats"
                    placeholder="Select format"
                    class="w-100"
                    :class="{ 'is-invalid': formErrors?.number_format }"
                    @change="emitSave"
                />
                <small v-if="formErrors?.number_format" class="text-danger">
                    {{ formErrors.number_format[0] }}
                </small>
            </div>

            <!-- Time Format -->
            <div class="col-md-6">
                <label class="form-label">Time Format*</label>
                <Select
                    v-model="form.time_format"
                    :options="timeFormats"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select format"
                    class="w-100"
                    :class="{ 'is-invalid': formErrors?.time_format }"
                    @change="emitSave"
                />

                <small v-if="formErrors?.time_format" class="text-danger">
                    {{ formErrors.time_format[0] }}
                </small>
            </div>
        </div>
    </div>
</template>
