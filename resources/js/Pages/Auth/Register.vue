<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import VerifyOtpModal from "@/Components/VerifyOtpModal.vue";
import { toast } from "vue3-toastify";

const form = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    pin: "",
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);
const showPin = ref(false);

const submit = () => {
    form.post(route("register"), {
        preserveScroll: true,

        // ✅ Success: go to dashboard
        onSuccess: () => {
            window.location.href = route("login");
        },

        // ✅ Error: check if it's unverified and show modal
        onError: (errors) => {
            if (errors.unverified && errors.email_address) {
                registeredEmail.value = errors.email_address;
                showOtpModal.value = true;

                
                toast.warning(errors.unverified);
            }

            // Keep form field errors too
            if (errors.email) form.errors.email = errors.email;
            if (errors.password) form.errors.password = errors.password;
            if (errors.pin) form.errors.pin = errors.pin;
        },

       onFinish: () => form.reset("password", "password_confirmation", "pin"),
    });
};
 


// OTP Modal state
const showOtpModal = ref(false);
const registeredEmail = ref("");
</script>

<template>
    <Head title="Register" />

    <div class="account-page">
        <div class="main-wrapper">
            <div class="account-content">
                <div class="login-wrapper">
                    <div class="login-content">
                        <div class="login-userset">
                            <div
                                class="login-logo d-flex justify-content-center"
                            >
                                <img
                                    src="/assets/img/10x Global.png"
                                    alt="img"
                                />
                            </div>
                            <div class="login-userheading">
                                <h3>Create Super Admin Account</h3>
                            </div>

                            <form @submit.prevent="submit">
                                <div class="form-login">
                                    <label>Full Name</label>
                                    <div class="form-addons">
                                        <input
                                            type="text"
                                            v-model="form.name"
                                            placeholder="Enter your full name"
                                        />
                                        <img
                                            src="/assets/img/icons/users1.svg"
                                            alt="img"
                                        />
                                    </div>
                                    <span class="text-danger text-sm">{{
                                        form.errors.name
                                    }}</span>
                                </div>

                                <div class="form-login">
                                    <label>Email</label>
                                    <div class="form-addons">
                                        <input
                                            type="email"
                                            v-model="form.email"
                                            placeholder="Enter your email address"
                                        />
                                        <img
                                            src="/assets/img/icons/mail.svg"
                                            alt="img"
                                        />
                                    </div>
                                    <span class="text-danger text-sm">{{
                                        form.errors.email
                                    }}</span>
                                </div>

                                <div class="form-login">
                                    <label>Password</label>
                                    <div class="pass-group">
                                        <input
                                            :type="
                                                showPassword
                                                    ? 'text'
                                                    : 'password'
                                            "
                                            v-model="form.password"
                                            class="pass-input"
                                            placeholder="Enter your password"
                                        />
                                        <span
                                            class="fas toggle-password"
                                            :class="
                                                showPassword
                                                    ? 'fa-eye'
                                                    : 'fa-eye-slash'
                                            "
                                            @click="
                                                showPassword = !showPassword
                                            "
                                        ></span>
                                    </div>
                                    <span class="text-danger text-sm">{{
                                        form.errors.password
                                    }}</span>
                                </div>

                                <div class="form-login">
                                    <label>Confirm Password</label>
                                    <div class="pass-group">
                                        <input
                                            :type="
                                                showConfirmPassword
                                                    ? 'text'
                                                    : 'password'
                                            "
                                            v-model="form.password_confirmation"
                                            class="pass-input"
                                            placeholder="Confirm your password"
                                        />
                                        <span
                                            class="fas toggle-password"
                                            :class="
                                                showConfirmPassword
                                                    ? 'fa-eye'
                                                    : 'fa-eye-slash'
                                            "
                                            @click="
                                                showConfirmPassword =
                                                    !showConfirmPassword
                                            "
                                        ></span>
                                    </div>
                                    <span class="text-danger text-sm">{{
                                        form.errors.password_confirmation
                                    }}</span>
                                </div>

                                <div class="form-login">
                                    <label
                                        >Enter 4-digit PIN (for quick
                                        login)</label
                                    >
                                    <div class="pin-group">
                                        <input
                                            type="text"
                                            v-model="form.pin"
                                            class="pass-input text-center"
                                            placeholder="••••"
                                            maxlength="4"
                                            inputmode="numeric"
                                            pattern="[0-9]*"
                                            @input="
                                                form.pin = form.pin
                                                    .replace(/\D/g, '')
                                                    .slice(0, 4)
                                            "
                                        />
                                        <span
                                            class="fas toggle-password"
                                            :class="
                                                showPin
                                                    ? 'fa-eye'
                                                    : 'fa-eye-slash'
                                            "
                                            @click="showPin = !showPin"
                                        ></span>
                                    </div>
                                    <span class="text-danger text-sm">{{
                                        form.errors.pin
                                    }}</span>
                                </div>

                                <div class="form-login">
                                    <button
                                        type="submit"
                                        class="btn btn-login"
                                        :disabled="form.processing"
                                    >
                                        Sign Up
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="login-img position-relative">
                        <img
                            src="/assets/img/login.jpg"
                            alt="img"
                            class="w-100 h-100 object-fit-cover"
                        />
                        <div class="login-overlay text-center">
                            <div class="overlay-content">
                                <h1 class="restaurant-name">The Tasty House</h1>
                                <p class="restaurant-desc">
                                    Where taste meets comfort and every meal
                                    feels like home.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <VerifyOtpModal
        v-if="showOtpModal"
        :open="showOtpModal"
        :email="registeredEmail"
        @verified="
            () => {
                showOtpModal = false;
                window.location.href = route('login');
            }
        "
        @closed="
            () => {
                showOtpModal = false;
            }
        "
    />
        <p v-if="showOtpModal" style="color:red;">Modal is open for {{ registeredEmail }}</p>

</template>

<style scoped>
.toggle-password {
    cursor: pointer;
    position: absolute;
    top: 50%;
    right: 15px;
    transform: translateY(-50%);
    color: #888;
}

.pass-group {
    position: relative;
}

.pin-group .pass-input {
    width: 100px;
    font-size: 24px;
    letter-spacing: 8px;
}

/* Overlay styles */

.login-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.login-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.overlay-content {
    color: #fff;
    z-index: 2;
}

.restaurant-name {
    font-size: 3rem;
    font-weight: bold;
    text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.8);
    margin-bottom: 0.5rem;
    color: white;
}

.restaurant-desc {
    font-size: 1.25rem;
    color: #ffffffcc;
    text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.6);
    max-width: 600px;
    margin: 0 auto;
}
</style>
