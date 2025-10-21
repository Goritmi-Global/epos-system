<template>
  <nav class="flex justify-center mt-6 space-x-1">
    <button
      v-for="link in pagination"
      :key="link.label"
      v-html="link.label"
      @click.prevent="go(link.url)"
      class="px-3 py-1 text-sm rounded transition-colors duration-200"
      :class="{
        'active-link text-white': link.active,
        'text-gray-400 cursor-not-allowed': !link.url,
        'hover:bg-gray-100 text-gray-700': link.url && !link.active
      }"
    ></button>
  </nav>
</template>

<script setup>
defineProps({
  pagination: Array,
  isApiDriven: Boolean,
});
const emit = defineEmits(["page-changed"]);

const go = (url) => {
  if (url) emit("page-changed", url);
};
</script>

<style scoped>
.active-link {
  background-color: #1C0D82;
  color: #fff !important;
  border-radius: 6px;
}

.active-link:hover {
  background-color: #2b1fa0; /* slightly lighter hover color */
}
</style>
