<script setup>
import { reactive, toRaw, watch, ref } from "vue"

const props = defineProps({ model: Object, formErrors: Object })
const emit = defineEmits(["save"])

// const form = reactive({
//   order_types: props.model.order_types ?? [],
//   table_management_enabled: props.model.table_management_enabled ?? 1,
//   tables: props.model.tables ?? "",
//   online_ordering: props.model.online_ordering ?? true,
//   table_details: props.model.table_details ?? [],
//   number_of_tables: props.model.number_of_tables ?? []   // new field

// })

// Step 5 reactive form
const form = reactive({
  order_types: props.model.order_types ?? [],
  table_management_enabled: props.model.table_management_enabled ?? 0,
  tables: props.model.tables ?? 0, // <-- use 0 instead of empty string
  online_ordering: props.model.online_ordering ?? true,
  table_details: props.model.table_details ?? [],
});

console.log("props.model.table_management_enabled", props.model.table_management_enabled);
console.log("table_management_enabled", form.table_management_enabled);

// watch(
//   () => form.tables,
//   (newCount) => {
//     form.table_details = Array.from({ length: newCount }, (_, i) => ({
//       name: "",
//       chairs: ""
//     }));
//   },
//   { immediate: true }
// );


const submitting = ref(false);

const saveTableDetails = () => {
  submitting.value = true;
  // You can save form.table_details to backend here
  setTimeout(() => {
    submitting.value = false;
  }, 800);
};

const resetTableDetails = () => {
  // reset logic (optional)
  form.table_details = Array.from({ length: form.tables }, () => ({
    name: "",
    chairs: ""
  }));
};


// function emitSave() { emit("save", { step: 5, data: toRaw(form) }) }

// function emitSave() {
//   const payload = {
//     step: 5,
//     data: {
//       order_types: form.order_types,
//       table_management_enabled: form.table_management_enabled,
//       online_ordering: form.online_ordering,
//     }
//   };

//   // Only include tables & table_details if dine_in is selected
//   if (form.order_types.includes("dine_in") && form.table_management_enabled === 1) {
//     payload.data.tables = form.tables;
//     payload.data.table_details = form.table_details;
//   }

//   emit("save", payload);
// }

// Watch for number of tables and initialize table_details
watch(
  () => form.tables,
  (newCount) => {
    form.table_details = Array.from({ length: newCount }, (_, i) => ({
      name: form.table_details[i]?.name ?? "",
      chairs: form.table_details[i]?.chairs ?? "",
    }));
  },
  { immediate: true }
);

// Watch entire form and emit payload
watch(
  form,
  () => {
    const payload = {
      step: 5,
      data: {
        order_types: form.order_types,
        table_management_enabled: form.table_management_enabled,
        online_ordering: form.online_ordering,
      },
    };

    if (form.order_types.includes("dine_in") && form.table_management_enabled === 1) {
      payload.data.tables = form.tables;
      payload.data.table_details = form.table_details;
    }

    emit("save", payload);
  },
  { deep: true, immediate: true }
);

watch(
  form,
  () => {
    const payload = {
      step: 5,
      data: {
        order_types: form.order_types,
        table_management_enabled: form.table_management_enabled,
        online_ordering: form.online_ordering,
      }
    };

    // Only include tables & table_details if dine_in is selected
    if (form.order_types.includes("dine_in") && form.table_management_enabled === 1) {
      payload.data.tables = form.tables;
      payload.data.table_details = form.table_details;
    }

    emit("save", payload);
  },
  { deep: true, immediate: true }
);


function toggle(type) {
  const i = form.order_types.indexOf(type)
  i === -1 ? form.order_types.push(type) : form.order_types.splice(i, 1)
  emitSave()
}

const types = [
  { key: 'dine_in', label: 'Dine-in', variant: 'default' },
  { key: 'takeaway', label: 'Takeaway', variant: 'primary' },
  { key: 'delivery', label: 'Delivery', variant: 'success' },
  { key: 'collection', label: 'Collection', variant: 'warning' }
]
</script>

<template>
  <div>
    <h5 class="fw-bold mb-3">Step 5 of 9 - Order Type & Service Options</h5>

    <!-- Order types -->
    <label class="mb-2 fw-semibold">Enable Order types</label>
    <div class="check-list mb-3">
      <label v-for="t in types" :key="t.key" class="check-tile" :class="[
        `check-tile--${t.variant}`,
        { 'is-checked': form.order_types.includes(t.key) }
      ]">
        <input class="check-tile__input" type="checkbox" :checked="form.order_types.includes(t.key)"
          @change="toggle(t.key)" :class="{ 'is-invalid': formErrors?.order_types }" />


        <span class="check-tile__badge">
          <svg class="check-icon" viewBox="0 0 20 20" width="14" height="14" aria-hidden="true">
            <path fill="currentColor" d="M7.7 14.1 3.9 10.3l1.4-1.4 2.4 2.4 6-6 1.4 1.4-7.4 7.4z" />
          </svg>
        </span>
        <span class="check-tile__text">{{ t.label }}</span>
      </label>

      <small v-if="formErrors?.order_types" class="text-danger">
        {{ formErrors.order_types[0] }}
      </small>
    </div>

    <!-- Table management -->
    <div v-if="form.order_types.includes('dine_in')" class="mb-3">
      <label class="form-label d-block mb-2">Enable Table Management?</label>
      <div class="segmented">
        <!-- YES option -->
        <input class="segmented__input" type="radio" id="tm-yes" :value="1" v-model="form.table_management_enabled"
          @change="emitSave" :class="{ 'is-invalid': formErrors?.table_management_enabled }">
        <label class="segmented__btn" :class="{ 'is-active': form.table_management_enabled === 1 }"
          for="tm-yes">YES</label>

        <!-- NO option -->
        <input class="segmented__input" type="radio" id="tm-no" :value="0" v-model="form.table_management_enabled"
          @change="emitSave">
        <label class="segmented__btn" :class="{ 'is-active': form.table_management_enabled === 0 }"
          for="tm-no">NO</label>
      </div>

      <br>
      <small v-if="formErrors?.table_management_enabled" class="text-danger">
        {{ formErrors.table_management_enabled[0] }}
      </small>
    </div>

    <div v-if="form.table_management_enabled === 1 && form.order_types.includes('dine_in')" class="mb-3" style="max-width:420px">
      <label class="form-label">Number of Tables</label>

      <div v-if="form.order_types.includes('dine_in')" class="d-flex align-items-center gap-2">
        <input type="number" min="1" class="form-control" :class="{ 'is-invalid': formErrors?.tables }"
          v-model.number="form.tables" @input="emitSave" />
      
        <button data-bs-toggle="modal" data-bs-target="#modalTableDetails"
          class="btn btn-primary btn-sm rounded-pill px-4 py-2" style="white-space: nowrap;">
          <i class="fa fa-plus me-1"></i> Enter Names
        </button>
        
        
      </div>
      <!-- <small v-if="formErrors?.number_of_tables" class="text-danger">
          {{ formErrors.number_of_tables[0] }}
        </small> -->
      <small v-if="formErrors?.tables" class="text-danger">
        {{ formErrors.tables[0] }}
      </small>
    </div>

    <!-- Enter Table Details Modal -->

    <div class="modal fade" id="modalTableDetails" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4">

          <div class="modal-header">
            <h5 class="modal-title fw-semibold">Enter table details</h5>
            <button class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <button class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
            data-bs-dismiss="modal" aria-label="Close" title="Close">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24"
              stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>



          <div class="modal-body">
            <div class="d-flex flex-column gap-4">
              <div v-for="(table, i) in form.table_details" :key="i" class="p-3 shadow-sm rounded bg-light">
                <label class="form-label small mb-2">Table {{ i + 1 }}</label>

                <input type="text" class="form-control mb-2" v-model="table.name" placeholder="Enter a table name" />

                <input type="number" class="form-control" v-model="table.chairs" min="1"
                  placeholder="Enter number of chairs" />
              </div>
            </div>
          </div>

          <div class="modal-footer mt-4">
            <button class="btn btn-primary rounded-pill px-4" :disabled="submitting" @click="saveTableDetails"
              data-bs-dismiss="modal">
              <span v-if="!submitting">Save</span>
              <span v-else>Saving...</span>
            </button>

            <button class="btn btn-secondary rounded-pill px-4 ms-2" data-bs-dismiss="modal" @click="resetTableDetails">
              Cancel
            </button>
          </div>


        </div>
      </div>
    </div>



    <!-- Online ordering -->
    <div class="mb-3">
      <label class="form-label d-block mb-2">Enable Online Ordering Integration</label>
      <div class="segmented">
        <input class="segmented__input" type="radio" :class="{ 'is-invalid': formErrors?.online_ordering }" id="oo-yes"
          :value="true" v-model="form.online_ordering" @change="emitSave">
        <label class="segmented__btn" :class="{ 'is-active': form.online_ordering === true }" for="oo-yes">YES</label>

        <input class="segmented__input" type="radio" id="oo-no" :value="false" v-model="form.online_ordering"
          @change="emitSave">
        <label class="segmented__btn" :class="{ 'is-active': form.online_ordering === false }" for="oo-no">NO</label>
      </div>
      <small v-if="formErrors?.online_ordering" class="text-danger">
        {{ formErrors.online_ordering[0] }}
      </small>
    </div>
  </div>
</template>

<style scoped>
:root {
  --brand: #1C0D82;
}

/* ---------- Segmented radios (YES/NO) ---------- */
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
  transition: all .15s ease;
  user-select: none;
}

.segmented__btn:hover {
  background: rgba(28, 13, 130, .08);
}

.segmented__btn.is-active {
  background: #1c0d82;
  color: #fff;
  box-shadow: 0 2px 6px rgba(28, 13, 130, .25);
}

.segmented__btn:active {
  transform: translateY(1px);
}

/* ---------- Checkbox tiles (compact) ---------- */
.check-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-width: 480px;
}

.check-tile {
  --accent: #1c0d82;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  border: 1px solid #e6eaf2;
  border-radius: 8px;
  background: #fff;
  box-shadow: 0 1px 2px rgba(17, 38, 146, .04);
  cursor: pointer;
  transition: border-color .15s ease, box-shadow .15s ease, transform .05s ease;
  font-size: 0.85rem;
}

.dark .check-tile{
      --accent: #1c0d82;
     background-color: #111827 !important;
    color: #ffffff !important;
}

.check-tile:hover {
  border-color: #cfd6e4;
  box-shadow: 0 4px 10px rgba(17, 38, 146, .06);
}

.check-tile:active {
  transform: translateY(1px);
}

.check-tile__input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

/* badge smaller */
.check-tile__badge {
  width: 28px;
  height: 28px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid var(--accent);
  background: transparent;
  color: transparent;
  transition: background .15s ease, color .15s ease;
}

.check-icon {
  opacity: 0;
  transition: opacity .12s ease;
}

/* checked -> fill + show white check */
.check-tile.is-checked {
  border-color: var(--accent);
}

.check-tile.is-checked .check-tile__badge {
  background: var(--accent);
  color: #fff;
}

.check-tile.is-checked .check-icon {
  opacity: 1;
}

.check-tile__text {
  color: #2b2f3b;
}

.dark .check-tile__text{
  color: #fff !important;
}

/* Variants (all same brand color now) */
.check-tile--default {
  --accent: #1c0d82;
}
.dark .check-tile--default{
  color: #fff !important;
}


.check-tile--primary {
  --accent: #1c0d82;
}

.check-tile--success {
  --accent: #1c0d82;
}

.check-tile--warning {
  --accent: #1c0d82;
}
</style>
