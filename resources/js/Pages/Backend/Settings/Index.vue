<script setup>
import { ref, computed } from "vue";
import Step1 from "../../Onboarding/Steps/Step1.vue";
import Step2 from "../../Onboarding/Steps/Step2.vue";
import Step3 from "../../Onboarding/Steps/Step3.vue";
import Step4 from "../../Onboarding/Steps/Step4.vue";
import Step5 from "../../Onboarding/Steps/Step5.vue";
import Step6 from "../../Onboarding/Steps/Step6.vue";
import Step7 from "../../Onboarding/Steps/Step7.vue";
import Step8 from "../../Onboarding/Steps/Step8.vue";
import Step9 from "../../Onboarding/Steps/Step9.vue";
// ... up to Step9
import axios from "axios";
import { toast } from "vue3-toastify";

const props = defineProps({ profile: Object, completed_steps: Array });

const profile = ref({ ...props.profile });
const current = ref(1);
const formErrors = ref({});
const steps = [
    { id: 1, title: "Welcome & Language Selection" },
    { id: 2, title: "Business Information" },
    { id: 3, title: "Currency & Locale" },
    { id: 4, title: "Tax & VAT Setup" },
    { id: 5, title: "Order Type & Service Options" },
    { id: 6, title: "Receipt & Printer Setup" },
    { id: 7, title: "Payment Methods" },
    { id: 8, title: "Business Hours" },
    { id: 9, title: "Optional Features" },
];

const comp = computed(() => ({
    1: Step1, 2: Step2, 3: Step3, 4: Step4, 5: Step5,
    6: Step6, 7: Step7, 8: Step8, 9: Step9
}[current.value]));

function gotoStep(n) {
    current.value = n;
}

async function saveStep(stepData) {
    try {
        const { data } = await axios.post(`/settings/save-step/${current.value}`, stepData);
        Object.assign(profile.value, data.profile);
        toast.success("Step saved successfully");
    } catch (err) {
        toast.error("Failed to save step");
    }
}

async function nextStep(stepData) {
  try {
    await saveStep(stepData);
    if (current.value < steps.length) current.value++;
  } catch (err) {
    // Already handled inside saveStep
  }
}
</script>

<template>
    <Master>
    <div class="settings-wizard">
        <div class="steps-nav">
            <ul>
                <li v-for="s in steps" :key="s.id" :class="{ active: current === s.id }" @click="gotoStep(s.id)">
                    {{ s.title }}
                </li>
            </ul>
        </div>

        <div class="step-content">
            <component :is="comp" :model="profile" :form-errors="formErrors" @save="saveStep" />
        </div>

        <div class="wizard-footer">
            <button @click="current--" :disabled="current === 1">Back</button>
            <button v-if="current < steps.length" @click="nextStep(profile)">Next</button>
            <button v-else @click="saveStep(profile)">Save Changes</button>
        </div>
    </div>
    </Master>
</template>
