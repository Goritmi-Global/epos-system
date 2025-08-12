<script setup>
import { reactive, toRaw } from "vue"

const props = defineProps({ model: Object })
const emit  = defineEmits(["save"])

const form = reactive({
  order_types: props.model.order_types ?? [],        // ['dine_in','takeaway',...]
  table_mgmt:  props.model.table_mgmt ?? "yes",
  tables:      props.model.tables ?? 1,
  online_ordering: props.model.online_ordering ?? "yes"
})

function emitSave(){ emit("save",{ step:5, data: toRaw(form) }) }

function toggle(type){
  const i = form.order_types.indexOf(type)
  i===-1 ? form.order_types.push(type) : form.order_types.splice(i,1)
  emitSave()
}

const types = [
  { key:'dine_in',   label:'Dine-in',    variant:'default' },
  { key:'takeaway',  label:'Takeaway',   variant:'primary' },
  { key:'delivery',  label:'Delivery',   variant:'success' },
  { key:'collection',label:'Collection', variant:'warning' }
]
</script>

<template>
  <div>
    <h5 class="fw-bold mb-3">Step 5 of 9 - Order Type & Service Options</h5>

    <!-- Order types -->
    <label class="mb-2 fw-semibold">Enable Order types</label>
    <div class="check-list mb-3">
      <label
        v-for="t in types"
        :key="t.key"
        class="check-tile"
        :class="[
          `check-tile--${t.variant}`,
          { 'is-checked': form.order_types.includes(t.key) }
        ]"
      >
        <input
          class="check-tile__input"
          type="checkbox"
          :checked="form.order_types.includes(t.key)"
          @change="toggle(t.key)"
        />
        <span class="check-tile__badge">
          <svg class="check-icon" viewBox="0 0 20 20" width="14" height="14" aria-hidden="true">
            <path fill="currentColor" d="M7.7 14.1 3.9 10.3l1.4-1.4 2.4 2.4 6-6 1.4 1.4-7.4 7.4z"/>
          </svg>
        </span>
        <span class="check-tile__text">{{ t.label }}</span>
      </label>
    </div>

    <!-- Table management -->
    <div class="mb-3">
      <label class="form-label d-block mb-2">Enable Table Management?</label>
      <div class="segmented">
        <input class="segmented__input" type="radio" id="tm-yes" value="yes" v-model="form.table_mgmt" @change="emitSave">
        <label class="segmented__btn" :class="{ 'is-active': form.table_mgmt==='yes' }" for="tm-yes">YES</label>

        <input class="segmented__input" type="radio" id="tm-no" value="no" v-model="form.table_mgmt" @change="emitSave">
        <label class="segmented__btn" :class="{ 'is-active': form.table_mgmt==='no' }" for="tm-no">NO</label>
      </div>
    </div>

    <div v-if="form.table_mgmt==='yes'" class="mb-3" style="max-width:420px">
      <label class="form-label">Number of Tables</label>
      <input type="number" min="1" class="form-control" v-model.number="form.tables" @input="emitSave">
    </div>

    <!-- Online ordering -->
    <div class="mb-3">
      <label class="form-label d-block mb-2">Enable Online Ordering Integration</label>
      <div class="segmented">
        <input class="segmented__input" type="radio" id="oo-yes" value="yes" v-model="form.online_ordering" @change="emitSave">
        <label class="segmented__btn" :class="{ 'is-active': form.online_ordering==='yes' }" for="oo-yes">YES</label>

        <input class="segmented__input" type="radio" id="oo-no" value="no" v-model="form.online_ordering" @change="emitSave">
        <label class="segmented__btn" :class="{ 'is-active': form.online_ordering==='no' }" for="oo-no">NO</label>
      </div>
    </div>
  </div>
</template>

<style scoped>
:root { --brand:#1C0D82; }

/* ---------- Segmented radios (YES/NO) ---------- */
.segmented {
  display:inline-flex;
  border-radius:999px;
  background:#f4f6fb;
  border:1px solid #e3e8f2;
  box-shadow:0 2px 6px rgba(25,28,90,.05);
  overflow:hidden;
}
.segmented__input {
  position:absolute;
  opacity:0;
  pointer-events:none;
}
.segmented__btn {
  padding:0.3rem 0.8rem;
  font-size:0.8rem;
  color:#2b2f3b;
  background:transparent;
  cursor:pointer;
  transition:all .15s ease;
  user-select:none;
}
.segmented__btn:hover {
  background:rgba(28,13,130,.08);
}
.segmented__btn.is-active {
  background:#1c0d82;
  color:#fff;
  box-shadow:0 2px 6px rgba(28,13,130,.25);
}
.segmented__btn:active {
  transform:translateY(1px);
}

/* ---------- Checkbox tiles (compact) ---------- */
.check-list {
  display:flex;
  flex-direction:column;
  gap:8px;
  max-width:480px;
}
.check-tile {
  --accent:#1c0d82;
  display:flex;
  align-items:center;
  gap:8px;
  padding:8px 12px;
  border:1px solid #e6eaf2;
  border-radius:8px;
  background:#fff;
  box-shadow:0 1px 2px rgba(17,38,146,.04);
  cursor:pointer;
  transition:border-color .15s ease, box-shadow .15s ease, transform .05s ease;
  font-size:0.85rem;
}
.check-tile:hover {
  border-color:#cfd6e4;
  box-shadow:0 4px 10px rgba(17,38,146,.06);
}
.check-tile:active {
  transform:translateY(1px);
}
.check-tile__input {
  position:absolute;
  opacity:0;
  pointer-events:none;
}

/* badge smaller */
.check-tile__badge {
  width:28px;
  height:28px;
  border-radius:6px;
  display:flex;
  align-items:center;
  justify-content:center;
  border:2px solid var(--accent);
  background:transparent;
  color:transparent;
  transition:background .15s ease, color .15s ease;
}
.check-icon {
  opacity:0;
  transition:opacity .12s ease;
}

/* checked -> fill + show white check */
.check-tile.is-checked { border-color:var(--accent); }
.check-tile.is-checked .check-tile__badge {
  background:var(--accent);
  color:#fff;
}
.check-tile.is-checked .check-icon { opacity:1; }

.check-tile__text { color:#2b2f3b; }

/* Variants (all same brand color now) */
.check-tile--default{ --accent:#1c0d82; }
.check-tile--primary{ --accent:#1c0d82; }
.check-tile--success{ --accent:#1c0d82; }
.check-tile--warning{ --accent:#1c0d82; }

</style>
