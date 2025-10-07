<script setup>
import { computed, ref } from "vue";
import axios from "axios";
import { toast } from "vue3-toastify";
import Step1 from "./Steps/Step1.vue"
import Step2 from "./Steps/Step2.vue"
import Step3 from "./Steps/Step3.vue"
import Step4 from "./Steps/Step4.vue"
import Step5 from "./Steps/Step5.vue"
import Step6 from "./Steps/Step6.vue"
import Step7 from "./Steps/Step7.vue"
import Step8 from "./Steps/Step8.vue"
import Step9 from "./Steps/Step9.vue"
import { useDark, useToggle } from "@vueuse/core";
import { Moon, Sun } from "lucide-vue-next";

const profile = ref({})
const progress = ref({ current_step: 1, completed_steps: [] })
const current = ref(1)
const formErrors = ref({})
const isSaving = ref(false)



const isDark = useDark({
  selector: "html",
  attribute: "class",
  valueDark: "dark",
  valueLight: "light",
});

const toggleDark = useToggle(isDark);

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
]

const comp = computed(() => ({
  1: Step1, 2: Step2, 3: Step3, 4: Step4, 5: Step5,
  6: Step6, 7: Step7, 8: Step8, 9: Step9
}[current.value]))

const progressPercent = computed(() => (current.value / steps.length) * 100)


function gotoStep(n) {

  if (n === current.value) return


  if (n < current.value) {
    console.log('Prev: ', n);
    current.value = n
    return
  }


  if (progress.value.completed_steps.includes(n)) {
    console.log('Steps Completed: ', n);
    current.value = n
    return
  }

  if (n === current.value + 1 && progress.value.completed_steps.includes(current.value)) {
    current.value = n
    return
  }

  toast.error("Please complete the Current step to proceed")
}


// const saveStep = (payload) => {
//   console.log(payload);
//   try {
//     // Merge the step data into the main profile
//     Object.assign(profile.value, payload?.data || {})

//     // Log full payload
//     console.log("Full Payload Received:", payload)

//     // Log just the data from the payload
//     console.log("Step Data:", payload?.data)

//     // Log the profile after merge
//     console.log("Profile After Merge:", profile.value)

//     // optionally debounce draft save here
//   } catch (e) {
//     console.error("saveStep error:", e)
//   }
// }

const saveStep = (payload) => {
  console.log("Payload received:", payload);

  try {
    // Extract step number and data
    const stepNumber = payload?.step || current.value;
    const stepData = payload?.data || {};

    // Get existing session data
    const existingData = profile.value || {};

    // Merge step data into profile
    Object.assign(profile.value, stepData)

    // IMPORTANT: Ensure the data structure matches what backend expects
    console.log("Step Number:", stepNumber)
    console.log("Step Data:", stepData)
    console.log("Profile After Merge:", profile.value)

    // Optionally save draft to backend immediately for Step 9
    if (stepNumber === 9) {
      goNext(profile.value);
    }
  } catch (e) {
    console.error("saveStep error:", e)
  }
}

async function finish() {
  try {
    isSaving.value = true;
    const { data: res } = await axios.post('/onboarding/complete', {
      profile: profile.value,     // all steps merged here
      completed_steps: progress.value.completed_steps,
    })

    toast.success("Onboarding completed successfully! Your profile created.");

    // wait 3 seconds before redirect
    setTimeout(() => {
      window.location.href = '/dashboard'
    }, 3000)

  } catch (e) {
    console.error('final save error:', e)
    toast.error("Something went wrong while completing onboarding.")
  } finally {
    // optional cleanup (like stopping a loading spinner)
    isSaving.value = false;
  }
}

async function goNext(stepData) {
  formErrors.value = {}

  try {
    let payload;

    // Handle file uploads for Step 2 (logo) and Step 6 (receipt_logo)
    if ((current.value === 2 && stepData.logo_file) ||
      (current.value === 6 && stepData.receipt_logo_file)) {
      payload = new FormData();

      // Append all fields
      Object.keys(stepData).forEach(key => {
        if (stepData[key] !== null && stepData[key] !== undefined) {
          payload.append(key, stepData[key]);
        }
      });
    } else {
      payload = stepData;
    }

    const { data } = await axios.post(
      `/onboarding/step/${current.value}`,
      payload,
      // Send as multipart/form-data if we have files
      ((current.value === 2 && stepData.logo_file) ||
        (current.value === 6 && stepData.receipt_logo_file)) ? {
        headers: { 'Content-Type': 'multipart/form-data' }
      } : {}
    )

    Object.assign(profile.value, data.profile || {})

    if (!progress.value.completed_steps.includes(current.value)) {
      progress.value.completed_steps.push(current.value)
    }

    if (current.value < steps.length) {
      current.value++
    }
  } catch (err) {
    if (err?.response?.status === 422 && err.response.data?.errors) {
      formErrors.value = err.response.data.errors
      toast.error("Please fill in all required fields correctly.")
    } else {
      toast.error("An unexpected error occurred.")
      console.error(err)
    }
  }
}



// function finish(){ console.log("FINAL PROFILE:", profile.value) }
</script>

<template>
  <div class="onboarding-wrapper">
    <!-- Top header -->
    <div class="d-flex justify-content-between align-items-center mb-4 px-2">
      <div>
        <h5 class="fw-bold mb-0">Onboarding Wizard</h5>
        <small class="text-muted">
          Step {{ current }} of {{ steps.length }} -
          {{steps.find(s => s.id === current)?.title}}
        </small>
      </div>

      <div class="d-flex align-items-center gap-2" style="min-width: 220px">
        <div class="progress flex-grow-1" style="height: 6px">
          <div class="progress-bar progress-bar-striped" role="progressbar" :style="{ width: progressPercent + '%' }">
          </div>
        </div>
        <small class="fw-semibold">{{ current }}/{{ steps.length }}</small>
           <button style="border-radius: 50%; " class="btn btn-sm btn-outline-secondary py-2" @click="toggleDark()">
          <Moon v-if="isDark" :size="20" />
          <Sun v-else :size="20" />
        </button>

      </div>
    </div>

    <div class="row g-lg-4">
      <!-- Sidebar (sticky + same height as card) -->
      <div class="col-lg-3 mb-4">
        <div class="wizard-aside">
          <div class="steps-nav shadow-sm rounded-4 p-3 bg-white">
            <ul class="steps-list list-unstyled m-0">
              <li v-for="s in steps" :key="s.id" class="step-item p-2 rounded mb-1"
                :class="{ 'active-step': current === s.id, 'completed-step': progress.completed_steps?.includes(s.id) }"
                @click="gotoStep(s.id)">
                <span>{{ s.title }}</span>
                <i v-if="progress.completed_steps?.includes(s.id)"
                  class="bi bi-check-circle-fill float-end text-success"></i>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Main (fixed height + internal scroll) -->
      <div class="col-lg-9">
        <div class="card wizard-card shadow-lg border-0 rounded-4 d-flex flex-column">
          <div class="wizard-scroll p-3 p-md-4">
            <component :is="comp" :model="profile" :form-errors="formErrors" @save="saveStep" />
          </div>

          <div class="wizard-footer footer d-flex justify-content-end gap-2 p-3">
            <button class="btn btn-outline-secondary rounded-pill" :disabled="current === 1"
              @click="current--">Back</button>

            <button v-if="current < steps.length" class="btn btn-primary rounded-pill px-4" @click="goNext(profile)">
              Next
            </button>



            <button v-else class="btn btn-success rounded-pill px-4" @click="finish" :disabled="isSaving">
              <span v-if="isSaving">Saving...</span>
              <span v-else>Finish & Save</span>
            </button>

          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
:root {
  --brand: #1C0D82;
}

.dark .steps-nav{
   background-color: #181818 !important;
    /* gray-800 */
    color: #f9fafb !important;
}

.dark .icons {
  color: #fff;
}

.dark .onboarding-wrapper{
   background-color: #181818 !important;
    /* gray-800 */
    color: #f9fafb !important;
}

/* style for daek mode  */
.dark .modal-body {
    background-color: #181818 !important;
    /* gray-800 */
    color: #f9fafb !important;
}

.dark .modal-header {
    background-color: #181818 !important;
    /* gray-800 */
    color: #f9fafb !important;
}


.dark input {
    background-color: #181818 !important;
    /* gray-800 */
    color: #f9fafb !important;
}

.dark textarea {
    background-color: #181818 !important;
    /* gray-800 */
    color: #f9fafb !important;
}

.dark .select {
    background-color: #181818 !important;
    /* gray-800 */
    color: #f9fafb !important;
}

.dark h4 {
  color: white;
}

.dark h5 {
  color: white;
}

.dark button {
  color: #f9fafb !important;
}

.dark .card {
    background-color: #181818 !important;
    /* gray-800 */
    color: #ffffff !important;
    /* gray-50 */
}

.dark .table {
    background-color: #181818 !important;
    /* gray-900 */
    color: #f9fafb !important;
}

.dark .table thead {
    background-color: #181818;
    color: #f9fafb;
}

.dark .table thead th {
    background-color: #181818;
    color: #f9fafb;
}

.dark .table tbody td {
    background-color: #181818;
    color: #f9fafb;
}

html.dark {
    --brand: #ffffff;
    --bg-muted: #212121;
    --border: #212121;
    background: #181818;
    color: #f9fafb;
}

html.dark .page-wrapper {
  background: #212121;
  border-bottom-color: #212121;
}

html.dark .header {
  background: #212121;
  border-bottom-color: #212121;
}


html.dark .sidebar {
  background: #212121;
  /* border-right-color: #212121; */
  color: white;
}

html.dark .side-link {
  color: #ffffff;
}

html.dark .side-link:hover {
  background: #212121;
  color: #fff;
}

html.dark .main {
  background: #212121;
  color: #fff;
}

.dark .dash-widget {
    background-color: #181818 !important;
    ;
    color: #ffffff;
}

.dark .dash-widgetcontent h5 {
    background-color: #181818 !important;
    ;
    color: #ffffff;
}

.dark .dash-widgetcontent h6 {
    background-color: #181818 !important;
    ;
    color: #ffffff;
}

.onboarding-wrapper {
  /* card + sidebar share this height; “just up to the screen size” */
  --wizard-h: clamp(520px, 72vh, 820px);
  background: #f8f9fc;
  padding: 20px;
  min-height: 100vh;
}

/* Sidebar */
.wizard-aside {
  position: sticky;
  top: 8px;
  /* space under the page header */
  height: var(--wizard-h);
}

.steps-nav {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.steps-list {
  flex: 1 1 auto;
  overflow: auto;
}

.steps-list::-webkit-scrollbar {
  width: 8px;
}

.steps-list::-webkit-scrollbar-thumb {
  background: #cfd6e4;
  border-radius: 8px;
}

.steps-list::-webkit-scrollbar-thumb:hover {
  background: #b9c3d6;
}

.steps-nav .step-item {
  cursor: pointer;
  transition: all .2s;
}

.steps-nav .step-item:hover {
  background-color: rgba(28, 13, 130, .08);
}

.active-step {
  background-color: #1C0D82;
  color: #fff;
}

.completed-step {
  background-color: rgba(25, 135, 84, .1);
}

/* Main card */
.wizard-card {
  height: var(--wizard-h);
}

.wizard-scroll {
  flex: 1 1 auto;
  overflow: auto;
}

.wizard-scroll::-webkit-scrollbar {
  width: 10px;
}

.wizard-scroll::-webkit-scrollbar-thumb {
  background: #cfd6e4;
  border-radius: 8px;
}

.wizard-scroll::-webkit-scrollbar-thumb:hover {
  background: #b9c3d6;
}

.wizard-footer {
  flex: 0 0 auto;
  border-top: 1px solid #edf0f6;
  background: linear-gradient(180deg, rgba(255, 255, 255, .92) 0%, #fff 40%);
  border-bottom-left-radius: 1rem;
  border-bottom-right-radius: 1rem;
}

.dark .wizard-footer{
 
   flex: 0 0 auto;
  background:  #181818;
  border-bottom-left-radius: 1rem;
  border-bottom-right-radius: 1rem;

}



.dark .btn {
  background-color: #181818;
}

/* Stack on small screens */
@media (max-width: 991.98px) {
  .wizard-aside {
    position: static;
    height: auto;
  }

  .steps-nav {
    height: auto;
  }

  .wizard-card {
    height: auto;
  }
}
</style>
