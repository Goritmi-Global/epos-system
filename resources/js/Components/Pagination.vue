<script setup>
import { computed } from "vue";

const props = defineProps({
  meta: { type: Object, required: true },
  links: { type: Array, required: true },
  disabled: { type: Boolean, default: false },
});
const emit = defineEmits(["go"]);

const canPrev = computed(() => props.meta.current_page > 1);
const canNext = computed(() => props.meta.current_page < props.meta.last_page);

const numberLinks = computed(() =>
  (props.links || []).filter(
    (l) => !String(l.label).includes("Previous") && !String(l.label).includes("Next")
  )
);

function goTo(label) {
  if (props.disabled) return;
  const page = Number(label);
  if (!Number.isFinite(page)) return;
  if (page < 1 || page > props.meta.last_page) return;
  emit("go", page);
}
</script>

<template>
  <div class="pager-wrap">
    <nav aria-label="Pagination" class="d-flex justify-content-center">
      <ul class="pager mb-0">
        <!-- Prev -->
        <li>
          <button
            class="nav-btn rounded-pill px-3"
            :class="{ disabled: disabled || !canPrev }"
            :disabled="disabled || !canPrev"
            @click="emit('go', meta.current_page - 1)"
            aria-label="Previous page"
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
              <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </li>

        <!-- Numbers -->
        <li v-for="l in numberLinks" :key="l.label + (l.url || '')">
          <button
            class="num-btn rounded-pill px-3"
            :class="{ active: l.active }"
            :disabled="disabled"
            v-html="l.label"
            @click="goTo(l.label)"
          />
        </li>

        <!-- Next -->
        <li>
          <button
            class="nav-btn rounded-pill px-3"
            :class="{ disabled: disabled || !canNext }"
            :disabled="disabled || !canNext"
            @click="emit('go', meta.current_page + 1)"
            aria-label="Next page"
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
              <path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </li>
      </ul>
    </nav>
  </div>
</template>

<style scoped>
.pager-wrap {
   
  border-radius: 12px;
  padding: 20px 28px;
   
  max-width: 760px;
  margin: 0 auto;
}

.pager {
  list-style: none;
  display: flex;
  align-items: center;
  gap: 18px;
  padding: 0;
  margin: 0;
}

/* Base styles (non-active = white bg, brand border) */
.nav-btn,
.num-btn {
  /* min-width: 36px; */
  height: 36px;
  padding: 0 10px;
  border-radius: 4px;
  border: 1px solid #1B2850;
  background: #fff;
  color: #1B2850;
  font-size: 14px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: background .2s ease, border-color .2s ease, color .2s ease;
  cursor: pointer;
}

/* Hover (non-active) */
.nav-btn:hover:not(.disabled):not(:disabled):not(.active),
.num-btn:hover:not(.disabled):not(:disabled):not(.active) {
  background: #243566;
  border-color: #243566;
  color: #fff;
}

/* Active */
.num-btn.active {
  background: #1B2850;
  border-color: #1B2850;
  color: #fff;
}
.num-btn.active:hover {
  background: #243566;
  border-color: #243566;
}

/* Disabled */
.nav-btn.disabled,
.nav-btn:disabled,
.num-btn:disabled {
  opacity: .5;
  border-color: #1B2850;
  cursor: not-allowed;
}
</style>
