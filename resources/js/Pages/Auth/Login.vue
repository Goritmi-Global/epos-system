<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import { ref } from "vue";

const form = useForm({
    email: "",
    password: "",
    pin: "",
    remember: false,
});

const showPassword = ref(false);
const showPin = ref(false);

const submit = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password", "pin"),
    });
};
</script>

<template>
    <Head title="Login" />
    <div class="account-page">
        <div class="main-wrapper">
            <div class="account-content">
                <div class="login-wrapper">
                    <div class="login-content">
                        <div class="login-userset">
                            <div class="login-logo">
                                <img src="/assets/img/logo.png" alt="img" />
                            </div>
                            <div class="login-userheading">
                                <h3>Sign In</h3>
                                <h4>Please login to your account</h4>
                            </div>
                            <form @submit.prevent="submit">
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
                                <div class="form-login text-center">
                                    <label class="text-start w-100"
                                        >PIN Code</label
                                    >
                                    <div
                                        class="pin-group d-flex justify-content-center"
                                    >
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
                                    </div>
                                     <span class="text-danger text-sm">{{
                                        form.errors.pin
                                    }}</span>
                                </div>

                                <div class="form-login">
                                    <div class="alreadyuser">
                                        <h4>
                                            <a
                                                :href="
                                                    route('password.request')
                                                "
                                                class="hover-a"
                                                >Forgot Password?</a
                                            >
                                        </h4>
                                    </div>
                                </div>
                                <div class="form-login">
                                    <button
                                        type="submit"
                                        class="btn btn-login"
                                        :disabled="form.processing"
                                    >
                                        Sign In
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

/* Image and Overlay Styles */
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
