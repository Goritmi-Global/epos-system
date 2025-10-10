<template>
  <form
    class="search-form"
    autocomplete="off"
    @submit.prevent
    @keydown.enter.prevent
  >
    <!-- Dummy hidden field to block Chrome autofill -->
    <input
      type="text"
      name="fakeusernameremembered"
      style="display: none"
      autocomplete="off"
    />

    <div class="search-wrap">
      <i class="bi bi-search"></i>
      <input
        :value="modelValue"
        @input="onInput"
        ref="searchInput"
        type="text"
        class="form-control search-input"
        :placeholder="placeholder"
        name="no-autofill-search"
        autocomplete="new-password"
        inputmode="search"
      />
    </div>
  </form>
</template>

<script setup>
const props = defineProps({
  modelValue: String,
  placeholder: { type: String, default: "Search..." },
});

const emit = defineEmits(["update:modelValue"]);

const onInput = (e) => {
  emit("update:modelValue", e.target.value);
};
</script>

<style scoped>
.search-form {
  width: 100%;
}

.search-wrap {
  position: relative;
  width: 100%;
}

.search-wrap i {
  position: absolute;
  top: 50%;
  left: 14px;
  transform: translateY(-50%);
  color: #6c757d;
  font-size: 1rem;
}

.search-input {
  width: 100%;
  padding: 10px 14px 10px 38px;
  border: 1px solid #dee2e6;
  border-radius: 25px;
  background-color: #fff;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
  transition: all 0.2s ease-in-out;
}

.search-input:focus {
  outline: none;
  border-color: #dee2e6;
  box-shadow: none;
}
</style>
