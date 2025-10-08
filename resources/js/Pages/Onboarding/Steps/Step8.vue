<script setup>
import { reactive, toRaw, computed, onMounted, watch } from "vue"
import Select from "primevue/select"

const props = defineProps({ model: Object, formErrors: Object })
const emit = defineEmits(["save"])

const defaultDay = (name) => ({ name, open: true, start: "09:00", end: "17:00", breaks: [] })

const form = reactive({
  auto_disable: props.model?.auto_disable ?? "yes",
  hours: props.model?.hours?.map(h => ({
    ...h,
    name: h.name ?? h.day,         // <-- use h.name first
    open: h.open ?? h.is_open ?? true, // <-- use h.open first
    start: h.start ?? h.from?.slice(0,5) ?? "09:00",
    end: h.end ?? h.to?.slice(0,5) ?? "17:00",
    breaks: h.breaks?.map(b => ({
      ...b,
      start: b.start?.slice(0, 5) ?? "13:00",
      end: b.end?.slice(0, 5) ?? "14:00"
    })) || []
  })) ?? [
    defaultDay("Monday"), defaultDay("Tuesday"), defaultDay("Wednesday"),
    defaultDay("Thursday"), defaultDay("Friday"), defaultDay("Saturday"), defaultDay("Sunday")
  ]
})


watch(
  form,
  () => {
    const payload = {
      step: 8,
      data: {
        auto_disable: form.auto_disable, // 'yes' or 'no' as backend expects
        hours: form.hours.map(h => ({
          name: h.name,
          open: h.open,
          start: h.start,
          end: h.end,
          breaks: h.breaks.map(b => ({
            start: b.start,
            end: b.end
          }))
        }))
      }
    };

    emit("save", payload);
  },
  { deep: true, immediate: true }
);

function emitSave() { emit("save", { step: 8, data: toRaw(form) }) }



function addBreak(d) {
  d.breaks.push({ start: "13:00", end: "14:00" })
  emitSave()
}
function removeBreak(d, idx) {
  d.breaks.splice(idx, 1)
  emitSave()
}

/* Build 30-minute time options: [{label:'09:00', value:'09:00'}, ...] */
const timeItems = computed(() => {
  const items = []
  for (let i = 0; i < 48; i++) {
    const h = String(Math.floor(i / 2)).padStart(2, "0")
    const m = i % 2 ? "30" : "00"
    const t = `${h}:${m}`
    items.push({ label: t, value: t })
  }
  return items
})

</script>
<template>
  <div>
    <h5 class="fw-bold mb-3">Step 8 of 9 - Business Hours</h5>

    <!-- Auto disable -->
    <div class="mb-3 d-flex align-items-center justify-content-between">
      <span>Auto-disable ordering after hours</span>
      <div class="segmented">
        <input class="segmented__input" type="radio" id="ad-yes" value="yes" v-model="form.auto_disable"
          @change="emitSave">
        <label class="segmented__btn" :class="{ 'is-active': form.auto_disable === 'yes' }" for="ad-yes">YES</label>

        <input class="segmented__input" type="radio" id="ad-no" value="no" v-model="form.auto_disable"
          @change="emitSave">
        <label class="segmented__btn" :class="{ 'is-active': form.auto_disable === 'no' }" for="ad-no">NO</label>
      </div>
    </div>

    <!--  Attendance Policy Error -->
    <small v-if="props.formErrors?.attendance_policy" class="text-danger d-block mb-3">
      {{ props.formErrors.attendance_policy[0] }}
    </small>

    <!-- Day rows -->
    <div class="vstack gap-3">
      <div v-for="d in form.hours" :key="d.name" class="day-row row g-2 align-items-center">
        <div class="col-md-2 fw-semibold">{{ d.name }}</div>

        <!-- Start -->
        <div class="col-md-3">
          <Select class="w-100" v-model="d.start" :options="timeItems" optionLabel="label" optionValue="value" :pt="{
                    root: { class: 'bg-white text-black' },
                    label: { class: 'text-black' },
                    listContainer: { class: 'bg-white text-black' },
                    option: { class: 'text-black hover:bg-gray-100' },
                    header: { class: 'bg-white text-black' },
                    IconField: { class: 'bg-white' },
                    InputText: { class: 'bg-white' },
                    pcFilter: { class: 'bg-white' },
                    pcFilterContainer: { class: 'bg-white' }
                }"
            :disabled="!d.open" @change="emitSave" placeholder="Start" />
        </div>

        <!-- End -->
        <div class="col-md-3">
          <Select class="w-100" v-model="d.end" :options="timeItems" optionLabel="label" optionValue="value" :pt="{
                    root: { class: 'bg-white text-black' },
                    label: { class: 'text-black' },
                    listContainer: { class: 'bg-white text-black' },
                    option: { class: 'text-black hover:bg-gray-100' },
                    header: { class: 'bg-white text-black' },
                    IconField: { class: 'bg-white' },
                    InputText: { class: 'bg-white' },
                    pcFilter: { class: 'bg-white' },
                    pcFilterContainer: { class: 'bg-white' }
                }"
            :disabled="!d.open" @change="emitSave" placeholder="End" />
        </div>

        <!-- Open/Close segmented -->
        <div class="col-md-2">
          <div class="segmented">
            <input class="segmented__input" type="radio" :id="`${d.name}-open`" :value="true" v-model="d.open"
              @change="emitSave">
            <label class="segmented__btn" :class="{ 'is-active': d.open === true }" :for="`${d.name}-open`">Open</label>

            <input class="segmented__input" type="radio" :id="`${d.name}-close`" :value="false" v-model="d.open"
              @change="emitSave">
            <label class="segmented__btn" :class="{ 'is-active': d.open === false }"
              :for="`${d.name}-close`">Close</label>
          </div>
        </div>

        <!-- Add break -->
        <div class="col-md-2 d-flex justify-content-end">
          <button class="btn btn-sm btn-success rounded-pill px-3" :disabled="!d.open" @click="addBreak(d)">
            <i class="bi bi-plus-lg me-1"></i> Add break
          </button>
        </div>

        <!-- Break list -->
        <div v-for="(b, bi) in d.breaks" :key="`${d.name}-b${bi}`" class="offset-md-2 col-md-8">
          <div class="d-flex align-items-center gap-2">
            <small class="text-muted">Break</small>

            <Select class="w-auto" v-model="b.start" :options="timeItems" optionLabel="label" optionValue="value" :pt="{
                    root: { class: 'bg-white text-black' },
                    label: { class: 'text-black' },
                    listContainer: { class: 'bg-white text-black' },
                    option: { class: 'text-black hover:bg-gray-100' },
                    header: { class: 'bg-white text-black' },
                    IconField: { class: 'bg-white' },
                    InputText: { class: 'bg-white' },
                    pcFilter: { class: 'bg-white' },
                    pcFilterContainer: { class: 'bg-white' }
                }"
              :disabled="!d.open" @change="emitSave" />
            <Select class="w-auto" v-model="b.end" :options="timeItems" optionLabel="label" optionValue="value" :pt="{
                    root: { class: 'bg-white text-black' },
                    label: { class: 'text-black' },
                    listContainer: { class: 'bg-white text-black' },
                    option: { class: 'text-black hover:bg-gray-100' },
                    header: { class: 'bg-white text-black' },
                    IconField: { class: 'bg-white' },
                    InputText: { class: 'bg-white' },
                    pcFilter: { class: 'bg-white' },
                    pcFilterContainer: { class: 'bg-white' }
                }"
              :disabled="!d.open" @change="emitSave" />

            <button class="btn btn-outline-danger btn-sm rounded-pill ms-auto" @click="removeBreak(d, bi)">
              <i class="bi bi-trash me-1"></i> Remove
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

/* Segmented YES/NO — same compact style */
.segmented {
  display: inline-flex;
  border-radius: 999px;
  background: #f4f6fb;
  border: 1px solid #e3e8f2;
  box-shadow: 0 2px 6px rgba(25, 28, 90, .05);
  overflow: hidden;
}

.segmented__input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.segmented__btn {
  padding: 0.3rem 0.8rem;
  font-size: 0.8rem;
  color: #2b2f3b;
  background: transparent;
  cursor: pointer;
  user-select: none;
  transition: all .15s ease;
}

.segmented__btn:hover {
  background: rgba(28, 13, 130, .08);
}

.segmented__btn.is-active {
  background: #1C0D82;
  color: #fff;
  box-shadow: 0 2px 6px rgba(28, 13, 130, .25);
}

.segmented__btn:active {
  transform: translateY(1px);
}

.dark .segmented{
  background-color: #121212 !important;
  color: #fff !important;
}

.dark .segmented__btn{
  color: #fff !important;
}

/* Row visuals */
.day-row {
  padding: 10px 12px;
  border: 1px solid #edf0f6;
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 4px 14px rgba(17, 38, 146, .05);
}

.dark .day-row{
 background-color: #121212 !important;
    color: #ffffff !important;
}
</style>
