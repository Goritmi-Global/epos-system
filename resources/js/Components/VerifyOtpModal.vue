<template>
  <div v-if="open" class="modal-backdrop">
    <div class="modal">
      <h3 class="mb-3">Verify Your Email</h3>
      <p>Enter the 6-digit OTP sent to <strong>{{ email }}</strong></p>

      <input
        v-model="otp"
        type="text"
        class="form-control mt-2"
        placeholder="Enter OTP"
        maxlength="6"
        @input="otp = otp.replace(/\\D/g, '').slice(0, 6)"
      />
      <span class="text-danger text-sm" v-if="error">{{ error }}</span>

      <div class="mt-3 d-flex justify-content-between">
        <button class="btn btn-secondary" @click="$emit('closed')">Cancel</button>
        <button class="btn btn-primary" @click="verifyOtp" :disabled="loading">
          {{ loading ? 'Verifying...' : 'Verify' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { toast } from 'vue3-toastify';

const props = defineProps({
  open: Boolean,
  email: String,
});

const emits = defineEmits(['verified', 'closed']);

const otp = ref('');
const loading = ref(false);
const error = ref('');

const verifyOtp = async () => {
  loading.value = true;
  error.value = '';
  try {
    await axios.post('/verify-otp', {
      email: props.email,
      otp: otp.value,
    });

    toast.success('✅ Verified successfully!');
    emits('verified');
  } catch (err) {
    error.value = err.response?.data?.message || 'Verification failed';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}
.modal {
  background: white;
  border-radius: 12px;
  padding: 24px;
  max-width: 400px;
  width: 100%;
}
</style>
