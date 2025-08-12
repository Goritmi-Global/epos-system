<script setup>
import { computed, ref } from "vue"
import Step1 from "./steps/Step1.vue"
import Step2 from "./steps/Step2.vue"
import Step3 from "./steps/Step3.vue"
import Step4 from "./steps/Step4.vue"
import Step5 from "./steps/Step5.vue"
import Step6 from "./steps/Step6.vue"
import Step7 from "./steps/Step7.vue"
import Step8 from "./steps/Step8.vue"
import Step9 from "./steps/Step9.vue"

const profile = ref({})
const progress = ref({ current_step: 1, completed_steps: [] })
const current = ref(1)

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

function gotoStep(n) { current.value = n }
function saveStep(payload) {
  // Receive { step, data } from children.
  // Merge into profile or call your API here.
  Object.assign(profile.value, payload.data)
  // console.log("Save from step", payload.step, payload.data)
}
function finish() {
  // Final submit using the aggregated profile
  // await axios.post('/onboarding/finish', profile.value)
  // router.visit('/dashboard')
  // For now:
  console.log("FINAL PROFILE:", profile.value)
}
</script>

<template>
  <div class="onboarding-wrapper">
    <!-- Top header -->
    <div class="d-flex justify-content-between align-items-center mb-4 px-2">
      <div>
        <h5 class="fw-bold mb-0">Onboarding Wizard</h5>
        <small class="text-muted">
          Step {{ current }} of {{ steps.length }} -
          {{ steps.find(s => s.id === current)?.title }}
        </small>
      </div>
      <div class="d-flex align-items-center gap-2" style="min-width: 220px">
        <div class="progress flex-grow-1" style="height: 6px">
          <div class="progress-bar bg-primary" :style="{ width: progressPercent + '%' }"></div>
        </div>
        <small class="fw-semibold">{{ current }}/{{ steps.length }}</small>
      </div>
    </div>

    <div class="row">
      <!-- Sidebar -->
      <div class="col-lg-3 mb-4">
        <div class="steps-nav shadow-sm rounded-4 p-3 bg-white">
          <ul class="list-unstyled m-0">
            <li v-for="s in steps" :key="s.id"
                class="step-item p-2 rounded mb-1"
                :class="{'active-step': current === s.id, 'completed-step': progress.completed_steps?.includes(s.id)}"
                @click="gotoStep(s.id)">
              <span>{{ s.title }}</span>
              <i v-if="progress.completed_steps?.includes(s.id)" class="bi bi-check-circle-fill float-end text-success"></i>
            </li>
          </ul>
        </div>
      </div>

      <!-- Main -->
      <div class="col-lg-9">
        <div class="card shadow-lg border-0 rounded-4 p-4">
          <component :is="comp"
                     :model="profile"
                     @save="saveStep" />
        </div>
      </div>
    </div>

    <!-- Nav buttons -->
    <div class="d-flex justify-content-end mt-4">
      <button class="btn btn-outline-secondary me-2 rounded-pill"
              :disabled="current === 1"
              @click="current--">Back</button>

      <button v-if="current < steps.length"
              class="btn btn-primary rounded-pill px-4"
              @click="current++">Next</button>

      <button v-else
              class="btn btn-success rounded-pill px-4"
              @click="finish">Finish & Save</button>
    </div>
  </div>
</template>

<style scoped>
.onboarding-wrapper { background-color:#f8f9fc; padding:20px; min-height:100vh; }
.steps-nav .step-item { cursor:pointer; transition: all .2s; }
.steps-nav .step-item:hover { background-color: rgba(28,13,130,.08); }
.active-step { background-color:#1C0D82; color:#fff; }
.completed-step { background-color: rgba(25,135,84,.1); }
</style>
