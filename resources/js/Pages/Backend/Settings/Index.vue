<script setup>
import { ref, computed } from "vue";
import axios from "axios";
import { toast } from "vue3-toastify";
// Import step components with CORRECT filenames
import Step1 from "../../Onboarding/Steps/Step1.vue"
import Step2 from "../../Onboarding/Steps/Step2.vue"
import Step3 from "../../Onboarding/Steps/Step3.vue"
import Step4 from "../../Onboarding/Steps/Step4.vue"
import Step5 from "../../Onboarding/Steps/Step5.vue"
import Step6 from "../../Onboarding/Steps/Step6.vue"
import Step7 from "../../Onboarding/Steps/Step7.vue"
import Step8 from "../../Onboarding/Steps/Step8.vue"
import Step9 from "../../Onboarding/Steps/Step9.vue"
import Master from "@/Layouts/Master.vue";

// Debug: Log all imported components
console.log('Step1:', Step1?.__name)
console.log('Step2:', Step2?.__name)
console.log('Step3:', Step3?.__name)
console.log('Step4:', Step4?.__name)
console.log('Step5:', Step5?.__name)
console.log('Step6:', Step6?.__name)
console.log('Step7:', Step7?.__name)
console.log('Step8:', Step8?.__name)
console.log('Step9:', Step9?.__name)

const props = defineProps({
  profile: Object,
  profileData: Object,
})

const currentSection = ref(1)
const formErrors = ref({})
const isSaving = ref(false)

// Merge all step data into a single profile object
const profile = ref({
  ...props.profileData.step1,
  ...props.profileData.step2,
  ...props.profileData.step3,
  ...props.profileData.step4,
  ...props.profileData.step5,
  ...props.profileData.step6,
  ...props.profileData.step7,
  ...props.profileData.step8,
  ...props.profileData.step9,
})

const sections = [
  { id: 1, title: "Language & Location", icon: "bi-globe", description: "Set your timezone, language, and regional preferences" },
  { id: 2, title: "Business Information", icon: "bi-building", description: "Update restaurant name, contact details, and address" },
  { id: 3, title: "Currency & Locale", icon: "bi-currency-dollar", description: "Configure currency, number and date formats" },
  { id: 4, title: "Tax & VAT", icon: "bi-receipt", description: "Manage tax registration and rates" },
  { id: 5, title: "Service Options", icon: "bi-cart-check", description: "Configure order types and table management" },
  { id: 6, title: "Receipt & Printers", icon: "bi-printer", description: "Customize receipts and printer settings" },
  { id: 7, title: "Payment Methods", icon: "bi-credit-card", description: "Enable and configure payment options" },
  { id: 8, title: "Business Hours", icon: "bi-clock", description: "Set your operating hours" },
  { id: 9, title: "Optional Features", icon: "bi-gear", description: "Additional features and settings" },
]

const components = {
  1: Step1, 
  2: Step2, 
  3: Step3, 
  4: Step4, 
  5: Step5,
  6: Step6, 
  7: Step7, 
  8: Step8, 
  9: Step9
}

const currentComponent = computed(() => {
  console.log('Current section:', currentSection.value)
  console.log('Component name:', components[currentSection.value]?.__name)
  return components[currentSection.value]
})

const currentSectionInfo = computed(() => 
  sections.find(s => s.id === currentSection.value)
)

const progressPercent = computed(() => (currentSection.value / sections.length) * 100)

// Handle data changes from step components
const saveStep = (payload) => {
  Object.assign(profile.value, payload?.data || {})
}

// Update section in backend
async function updateSection() {
  formErrors.value = {}
  isSaving.value = true

  try {
    let payload;
    let config = {}

    // If step 2 or step 6, use FormData
    if ((currentSection.value === 2 && profile.value.logo_file) ||
        (currentSection.value === 6 && profile.value.receipt_logo_file)) {
      payload = new FormData()
      Object.keys(profile.value).forEach(key => {
        if (profile.value[key] !== null && profile.value[key] !== undefined) {
          payload.append(key, profile.value[key])
        }
      })
      config.headers = { 'Content-Type': 'multipart/form-data' }
    } else {
      payload = profile.value
    }

    const { data } = await axios.post(
      `/settings/update/${currentSection.value}`, 
      payload,
      config
    )

    Object.assign(profile.value, data.data || {})

    toast.success("Settings updated successfully!")
  } catch (err) {
    if (err?.response?.status === 422 && err.response.data?.errors) {
      formErrors.value = err.response.data.errors
      toast.error("Please fix the validation errors.")
    } else {
      toast.error(err.response?.data?.message || "An unexpected error occurred.")
      console.error(err)
    }
  } finally {
    isSaving.value = false
  }
}


function changeSection(sectionId) {
  console.log('Changing to section:', sectionId)
  formErrors.value = {} // Clear errors when changing sections
  currentSection.value = sectionId
  console.log('Current section after change:', currentSection.value)
}
</script>

<template>
    <Master>
  <div class="settings-wrapper">
    <!-- Page Header -->
    <div class="settings-header mb-4 px-2">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h3 class="fw-bold mb-1">Restaurant Settings</h3>
          <p class="text-muted mb-0">
            Section {{ currentSection }} of {{ sections.length }} - 
            {{ currentSectionInfo?.title }}
          </p>
        </div>
        
        <!-- Progress Bar (replacing Back to Dashboard button) -->
        <div class="d-flex align-items-center gap-2" style="min-width: 220px">
          <div class="progress flex-grow-1" style="height: 6px">
            <div 
              class="progress-bar progress-bar-striped" 
              role="progressbar" 
              :style="{ width: progressPercent + '%' }"
              :aria-valuenow="progressPercent"
              aria-valuemin="0"
              aria-valuemax="100"
            ></div>
          </div>
          <small class="fw-semibold">{{ currentSection }}/{{ sections.length }}</small>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <!-- Left Sidebar Navigation -->
      <div class="col-lg-3">
        <div class="settings-sidebar">
          <div class="settings-nav shadow-sm rounded-4 bg-white p-3">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
              <h6 class="text-muted text-uppercase small fw-bold mb-0">
                <i class="bi bi-gear-fill me-2"></i>
                Settings
              </h6>
            </div>
            
            <ul class="nav nav-pills flex-column gap-1">
              <li v-for="section in sections" :key="section.id" class="nav-item">
                <a 
                  class="nav-link d-flex align-items-center gap-2 py-2 px-3"
                  :class="{ active: currentSection === section.id }"
                  @click.prevent="changeSection(section.id)"
                  href="#"
                  role="button"
                >
                  <i :class="section.icon"></i>
                  <div class="flex-grow-1">
                    <div class="fw-semibold small">{{ section.title }}</div>
                  </div>
                  <i class="bi bi-chevron-right small opacity-50"></i>
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Main Content Area -->
      <div class="col-lg-9">
        <div class="card shadow-sm border-0 rounded-4">
          <!-- Section Header -->
          <div class="card-header bg-gradient border-0 p-4">
            <div class="d-flex align-items-start gap-3">
              <div class="icon-wrapper">
                <i :class="currentSectionInfo?.icon" class="fs-3"></i>
              </div>
              <div class="flex-grow-1">
                <h5 class="mb-1 fw-bold">{{ currentSectionInfo?.title }}</h5>
                <p class="text-muted small mb-0">{{ currentSectionInfo?.description }}</p>
              </div>
            </div>
          </div>

          <!-- Section Content (Step Component) -->
          <div class="card-body p-4 settings-content">
            <component 
              :is="currentComponent" 
              :key="currentSection"
              :model="profile" 
              :form-errors="formErrors"
              @save="saveStep" 
            />
          </div>

          <!-- Footer Actions -->
          <div class="card-footer bg-light border-0 p-4">
            <div class="d-flex justify-content-between align-items-center">
              <small class="text-muted">
                <i class="bi bi-info-circle me-1"></i>
                Changes will be saved immediately
              </small>
              <button 
                class="btn btn-primary px-4 rounded-pill"
                @click="updateSection"
                :disabled="isSaving"
              >
                <span v-if="isSaving">
                  <span class="spinner-border spinner-border-sm me-2"></span>
                  Saving...
                </span>
                <span v-else>
                  <i class="bi bi-check-circle me-2"></i>
                  Save Changes
                </span>
              </button>
            </div>
          </div>
        </div>

        <!-- Quick Navigation -->
        <div class="quick-nav mt-3 d-flex justify-content-between">
          <button 
            class="btn btn-outline-secondary rounded-pill"
            :disabled="currentSection === 1"
            @click.prevent="changeSection(currentSection - 1)"
          >
            <i class="bi bi-chevron-left me-2"></i>
            Previous
          </button>
          <button 
            class="btn btn-outline-secondary rounded-pill"
            :disabled="currentSection === sections.length"
            @click.prevent="changeSection(currentSection + 1)"
          >
            Next
            <i class="bi bi-chevron-right ms-2"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
  </Master>
</template>

<style scoped>
.settings-wrapper {
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  padding: 30px 20px;
  min-height: 100vh;
  padding-left: 300px;
}

.settings-header {
  padding: 10px 0 20px;
}

.progress {
  background-color: rgba(28, 13, 130, 0.1);
  border-radius: 10px;
  overflow: hidden;
}

.progress-bar {
  background: linear-gradient(90deg, #1C0D82 0%, #2d1ba8 50%, #1C0D82 100%);
  background-size: 200% 100%;
  animation: shimmer 2s infinite;
  transition: width 0.4s ease;
  border-radius: 10px;
}

@keyframes shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

/* Sidebar */
.settings-sidebar {
  position: sticky;
  top: 20px;
}

.settings-nav {
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.3);
}

.nav-link {
  color: #495057;
  border-radius: 10px;
  transition: all 0.25s ease;
  cursor: pointer;
  border: 1px solid transparent;
}

.nav-link:hover {
  background-color: rgba(28, 13, 130, 0.06);
  color: #1C0D82;
  border-color: rgba(28, 13, 130, 0.1);
  transform: translateX(4px);
}

.nav-link.active {
  background: linear-gradient(135deg, #1C0D82 0%, #2d1ba8 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(28, 13, 130, 0.3);
  border-color: #1C0D82;
}

.nav-link.active:hover {
  transform: translateX(0);
}

/* Card Styling */
.card {
  border: none;
  overflow: hidden;
}

.card-header.bg-gradient {
  background: linear-gradient(135deg, rgba(28, 13, 130, 0.03) 0%, rgba(28, 13, 130, 0.08) 100%);
  border-bottom: 2px solid #edf0f6;
}

.icon-wrapper {
  width: 50px;
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #1C0D82 0%, #2d1ba8 100%);
  color: white;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(28, 13, 130, 0.2);
}

.settings-content {
  min-height: 400px;
  max-height: 600px;
  overflow-y: auto;
}

.settings-content::-webkit-scrollbar {
  width: 8px;
}

.settings-content::-webkit-scrollbar-track {
  background: #f1f3f5;
  border-radius: 10px;
}

.settings-content::-webkit-scrollbar-thumb {
  background: #cbd5e0;
  border-radius: 10px;
}

.settings-content::-webkit-scrollbar-thumb:hover {
  background: #a0aec0;
}

.card-footer {
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.5) 0%, #f8f9fa 100%);
  border-top: 1px solid #edf0f6;
}

/* Quick Navigation */
.quick-nav {
  padding: 0 10px;
}

.quick-nav .btn {
  min-width: 130px;
}

/* Responsive */
@media (max-width: 991.98px) {
  .settings-sidebar {
    position: static;
    margin-bottom: 20px;
  }

  .settings-content {
    max-height: none;
  }

  .quick-nav {
    flex-direction: column;
    gap: 10px;
  }

  .quick-nav .btn {
    width: 100%;
  }
}

/* Animation */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.card {
  animation: fadeIn 0.3s ease-out;
}
</style>