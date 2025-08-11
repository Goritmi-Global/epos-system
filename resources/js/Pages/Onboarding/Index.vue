<script setup>
import { onMounted, ref, computed } from 'vue'
import axios from 'axios'
import Step1 from './steps/Step1.vue'
// import Step2 from './steps/Step2.vue'
// ... Step3..Step10 imports

const loading = ref(true)
const profile = ref({})
const progress = ref({ current_step: 1, completed_steps: [] })
const current = ref(1)

const steps = [
  { id:1, title:'Welcome & Language Selection' },
  { id:2, title:'Business Information' },
  { id:3, title:'Currency & Locale' },
  { id:4, title:'Tax & VAT Setup' },
  { id:5, title:'Order Type & Service Options' },
  { id:6, title:'Receipt & Printer Setup' },
  { id:7, title:'Payment Methods' },
  { id:8, title:'Users & Roles' },
  { id:9, title:'Business Hours' },
  { id:10, title:'Optional Features' },
]

const comp = computed(() => ({
  1: Step1,
   /*2: Step2,  3: Step3, ... 10: Step10 */
}[current.value]))

async function load() {
    alert("called");
  loading.value = true
  const { data } = await axios.get('/onboarding/data')
  profile.value = data.profile
  progress.value = data.progress
  current.value = progress.value.current_step || 1
  loading.value = false
}

function gotoStep(n) { current.value = n }

async function saveStep(payload) {
  const { data } = await axios.post(`/onboarding/step/${current.value}`, payload)
  profile.value = data.profile
  progress.value = data.progress
  current.value = progress.value.current_step
}

onMounted(load)
</script>

<template>
  <div class="container py-4" v-if="!loading">
    <div class="row">
      <!-- Left nav -->
      <div class="col-md-3">
        <ul class="list-group">
          <li v-for="s in steps" :key="s.id"
              class="list-group-item d-flex justify-content-between align-items-center"
              :class="{'active': current === s.id}"
              role="button"
              @click="gotoStep(s.id)">
            <span>{{ s.title }}</span>
            <span v-if="progress.completed_steps?.includes(s.id)" class="badge bg-success">✓</span>
          </li>
        </ul>
      </div>

      <!-- Right panel -->
      <div class="col-md-9">
        <component :is="comp" :model="profile" @save="saveStep" @next="() => current++" @back="() => current--" />
      </div>
    </div>
  </div>
  <h1>Testing</h1>
</template>
