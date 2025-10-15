<template>
    <div
        v-if="open"
        class="modal fade show d-block"
        tabindex="-1"
        style="background: rgba(0, 0, 0, 0.6)"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Verify Your Email</h5>
                    <button
                        type="button"
                        class="btn-close"
                        @click="$emit('closed')"
                    ></button>
                </div>
                <div class="modal-body">
                    <p>
                        Enter the 6-digit OTP sent to
                        <strong>{{ email }}</strong>
                    </p>
                    <input
                        v-model="otp"
                        type="text"
                        class="form-control mt-2 pin-input text-center pass-input"
                        maxlength="6"
                        @input="otp = otp.replace(/\D/g, '').slice(0, 6)"
                        placeholder="••••••"
                        inputmode="numeric"
                        pattern="[0-9]*"
                    />
                    <span class="text-danger text-sm" v-if="error">{{
                        error
                    }}</span>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary rounded-pill py-2" @click="$emit('closed')">
                        Cancel
                    </button>
                    <button
                        class="btn btn-primary rounded-pill py-2"
                        @click="verifyOtp"
                        :disabled="loading"
                    >
                        {{ loading ? "Verifying..." : "Verify" }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";
import axios from "axios";
import { toast } from "vue3-toastify";

const props = defineProps({
    open: Boolean,
    email: String,
});

const emits = defineEmits(["verified", "closed"]);

const otp = ref("");
const loading = ref(false);
const error = ref("");

const verifyOtp = async () => {
    loading.value = true;
    error.value = "";
    try {
        await axios.post("/verify-otp", {
            email: props.email,
            otp: otp.value,
        });

        toast.success("Account Verified successfully!");

        //  Close modal and redirect after 2 seconds
        setTimeout(() => {
            emits("closed"); // close the modal
            window.location.href = route("dashboard"); // redirect
        }, 2000);
    } catch (err) {
        error.value = err.response?.data?.message || "Verification failed";
    } finally {
        loading.value = false;
    }
};

</script>

<style scoped>
/* Prevent background scrolling */
body.modal-open {
    overflow: hidden;
}

/* Optional fade-in effect for modal */
.modal.fade.show {
    display: block;
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.98);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
.pin-input {
    font-size: 24px;
    position: relative;

    letter-spacing: 8px;
}
.btn{
    border-radius: 10px !important;
    height: 42px !important;
    font-size: 16px !important;
}
</style>
