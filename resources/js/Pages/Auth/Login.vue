<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import { ref, onMounted, watch, nextTick } from "vue";
import { toast } from "vue3-toastify";
import { usePage } from "@inertiajs/vue3";
import VerifyOtpModal from "@/Components/VerifyOtpModal.vue";
import { useDark, useToggle } from '@vueuse/core'

const form = useForm({
    email: "",
    password: "",
    pin: "",
    remember: false,
});

const isDark = useDark()
const toggleDark = useToggle(isDark)

const showPassword = ref(false);
const loginMethod = ref('pin'); // 'pin' or 'email'

// PIN display (masked)
const pinDisplay = ref(['', '', '', '']);

const addDigit = (digit) => {
    if (form.pin.length < 4) {
        form.pin += digit;
        updatePinDisplay();
    }
};

const removeDigit = () => {
    if (form.pin.length > 0) {
        form.pin = form.pin.slice(0, -1);
        updatePinDisplay();
    }
};

const clearPin = () => {
    form.pin = '';
    pinDisplay.value = ['', '', '', ''];
};

const updatePinDisplay = () => {
    const pins = form.pin.split('');
    pinDisplay.value = ['', '', '', ''];
    pins.forEach((digit, index) => {
        if (index < 4) {
            pinDisplay.value[index] = '•';
        }
    });
};

// Auto-submit when PIN is complete
watch(() => form.pin, (val) => {
    if (val.length === 4 && loginMethod.value === 'pin') {
        setTimeout(() => {
            submit();
        }, 300);
    }
});

const submit = () => {
    form.post(route("login"), {
        preserveScroll: true,
        onSuccess: () => {
            // Success handled by Inertia
        },
        onError: (errors) => {
            if (errors.unverified && errors.email_address) {
                registeredEmail.value = errors.email_address;
                showOtpModal.value = true;
                toast.warning(errors.unverified);
            }
            form.errors.email = errors.email;
            form.errors.password = errors.password;
            form.errors.pin = errors.pin;
            
            // Clear PIN on error
            if (errors.pin && loginMethod.value === 'pin') {
                clearPin();
            }
        },
        onFinish: () => {
            if (loginMethod.value === 'email') {
                form.reset("password");
            }
        },
    });
};

const initializeTooltips = () => {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach((el) => {
        new bootstrap.Tooltip(el);
    });
};

watch([() => form.email, () => form.pin], () => {
    nextTick(() => initializeTooltips());
});

onMounted(() => {
    if (!isDark.value) {
        toggleDark(true)
    }
    initializeTooltips();
});

const page = usePage();
function handleFlashMessages() {
    const flash = usePage().props.flash || {};
    if (flash.message) toast.success(flash.message);
    if (flash.error) toast.error(flash.error);
}

onMounted(() => {
    handleFlashMessages();
});

// OTP Modal state
const showOtpModal = ref(false);
const registeredEmail = ref("");

// Switch login method
const switchToEmail = () => {
    loginMethod.value = 'email';
    clearPin();
    form.clearErrors();
};

const switchToPin = () => {
    loginMethod.value = 'pin';
    form.email = '';
    form.password = '';
    form.clearErrors();
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
                            <div class="login-logo d-flex justify-content-center mb-4">
                                <img src="/assets/img/10x Global.png" alt="img" class="logo-img" />
                            </div>

                            <!-- PIN Login Method -->
                            <div v-if="loginMethod === 'pin'" class="pin-login-container">
                                <div class="login-userheading text-center">
                                    <h3>Quick PIN Login</h3>
                                    <p class="text-muted mb-4">Enter your 4-digit PIN</p>
                                </div>

                                <!-- PIN Display -->
                                <div class="pin-display-container">
                                    <div class="pin-dots">
                                        <div v-for="(dot, index) in pinDisplay" :key="index" class="pin-dot"
                                            :class="{ 'filled': dot }">
                                            {{ dot }}
                                        </div>
                                    </div>
                                    <span v-if="form.errors.pin" class="text-danger d-block text-center mt-2">
                                        {{ form.errors.pin }}
                                    </span>
                                </div>

                                <!-- Numeric Keypad -->
                                <div class="numeric-keypad">
                                    <div class="keypad-row">
                                        <button type="button" class="keypad-btn" @click="addDigit('1')">1</button>
                                        <button type="button" class="keypad-btn" @click="addDigit('2')">2</button>
                                        <button type="button" class="keypad-btn" @click="addDigit('3')">3</button>
                                    </div>
                                    <div class="keypad-row">
                                        <button type="button" class="keypad-btn" @click="addDigit('4')">4</button>
                                        <button type="button" class="keypad-btn" @click="addDigit('5')">5</button>
                                        <button type="button" class="keypad-btn" @click="addDigit('6')">6</button>
                                    </div>
                                    <div class="keypad-row">
                                        <button type="button" class="keypad-btn" @click="addDigit('7')">7</button>
                                        <button type="button" class="keypad-btn" @click="addDigit('8')">8</button>
                                        <button type="button" class="keypad-btn" @click="addDigit('9')">9</button>
                                    </div>
                                    <div class="keypad-row">
                                        <button type="button" class="keypad-btn keypad-clear" @click="clearPin">
                                            <i class="fas fa-redo"></i>
                                        </button>
                                        <button type="button" class="keypad-btn" @click="addDigit('0')">0</button>
                                        <button type="button" class="keypad-btn keypad-delete" @click="removeDigit">
                                            <i class="fas fa-backspace"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Switch to Email Login -->
                                <div class="text-center mt-4">
                                    <button type="button" class="btn-link-switch" @click="switchToEmail">
                                        <i class="fas fa-envelope me-2"></i>Login with Email Instead
                                    </button>
                                </div>
                            </div>

                            <!-- Email/Password Login Method -->
                            <div v-else class="email-login-container">
                                <div class="login-userheading">
                                    <h3>Sign In</h3>
                                    <p class="text-muted mb-4">Login with your credentials</p>
                                </div>

                                <form @submit.prevent="submit">
                                    <div class="form-login">
                                        <label class="form-label">Email Address</label>
                                        <div class="form-addons">
                                            <input type="email" v-model="form.email"
                                                placeholder="Enter your email address" class="form-control" />
                                            <img src="/assets/img/icons/mail.svg" alt="img" />
                                        </div>
                                        <span v-if="form.errors.email" class="text-danger text-sm">
                                            {{ form.errors.email }}
                                        </span>
                                    </div>

                                    <div class="form-login">
                                        <label class="form-label">Password</label>
                                        <div class="pass-group">
                                            <input :type="showPassword ? 'text' : 'password'" v-model="form.password"
                                                class="pass-input form-control" placeholder="Enter your password" />
                                            <span class="fas toggle-password"
                                                :class="showPassword ? 'fa-eye' : 'fa-eye-slash'"
                                                @click="showPassword = !showPassword"></span>
                                        </div>
                                        <span v-if="form.errors.password" class="text-danger text-sm">
                                            {{ form.errors.password }}
                                        </span>
                                    </div>

                                    <div class="form-login">
                                        <div class="alreadyuser">
                                            <h4>
                                                <a :href="route('password.request')" class="hover-a">
                                                    Forgot Password?
                                                </a>
                                            </h4>
                                        </div>
                                    </div>

                                    <div class="form-login">
                                        <button type="submit" class="btn btn-login" :disabled="form.processing">
                                            <span v-if="form.processing">
                                                <i class="fas fa-spinner fa-spin me-2"></i>Signing In...
                                            </span>
                                            <span v-else>Sign In</span>
                                        </button>
                                    </div>

                                    <!-- Switch to PIN Login -->
                                    <div class="text-center mt-3">
                                        <button type="button" class="btn-link-switch" @click="switchToPin">
                                            <i class="fas fa-keyboard me-2"></i>Use PIN Instead
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side Image & Overlay -->
                    <div class="login-img position-relative">
                        <img src="/assets/img/login.jpg" alt="img" class="w-100 h-100 object-fit-cover" />
                        <div class="login-overlay text-center">
                            <div class="overlay-content">
                                <h1 class="restaurant-name">The Tasty House</h1>
                                <p class="restaurant-desc">
                                    Where taste meets comfort and every meal feels like home.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <VerifyOtpModal v-if="showOtpModal" :open="showOtpModal" :email="registeredEmail" @verified="() => {
        showOtpModal = false;
        window.location.href = route('dashboard');
    }" @closed="() => {
        showOtpModal = false;
    }" />
</template>

<style scoped>
/* Logo Styles */
.login-logo {
    margin-bottom: 2rem;
}

.logo-img {
    max-width: 200px;
    height: auto;
    width: 100%;
    margin-left: 550px;
}

@media (max-width: 768px) {
    .logo-img {
        max-width: 160px;
    }
}

/* PIN Login Styles */
.pin-login-container {
    padding: 20px 0;
}

.pin-display-container {
    margin: 30px 0;
}

.pin-dots {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-bottom: 10px;
}

.pin-dot {
    width: 50px;
    height: 50px;
    border: 2px solid #ddd;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    background: #f8f9fa;
    transition: all 0.3s ease;
}

.dark .pin-dot {
    border-color: #444;
    background: #1e1e1e;
    color: #fff;
}

.pin-dot.filled {
    border-color: #0d6efd;
    background: #e7f1ff;
    transform: scale(1.05);
}

.dark .pin-dot.filled {
    border-color: #0d6efd;
    background: #1a3a5c;
}

/* Numeric Keypad */
.numeric-keypad {
    max-width: 300px;
    margin: 0 auto;
}

.keypad-row {
    display: flex;
    gap: 24px;
    margin-bottom: 15px;
    justify-content: center;
}

.keypad-btn {
    width: 70px;
    height: 70px;
    border: none;
    border-radius: 50%;
    background: #f8f9fa;
    font-size: 24px;
    font-weight: 600;
    color: #333;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.keypad-btn:hover {
    background: #e9ecef;
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.keypad-btn:active {
    transform: scale(0.95);
}

.dark .keypad-btn {
    background: #2a2a2a;
    color: #fff;
}

.dark .keypad-btn:hover {
    background: #3a3a3a;
}

.keypad-clear,
.keypad-delete {
    background: #fff3cd !important;
    color: #856404;
    font-size: 20px;
}

.dark .keypad-clear,
.dark .keypad-delete {
    background: #3a3a2a !important;
    color: #ffc107;
}

/* Switch Button */
.btn-link-switch {
    background: none;
    border: none;
    color: #0d6efd;
    text-decoration: none;
    cursor: pointer;
    padding: 10px 20px;
    font-size: 14px;
    transition: all 0.3s ease;
    border-radius: 8px;
}

.btn-link-switch:hover {
    background: #e7f1ff;
    color: #0a58ca;
}

.dark .btn-link-switch {
    color: #6ea8fe;
}

.dark .btn-link-switch:hover {
    background: #1a3a5c;
    color: #9ec5fe;
}

/* Email Login Form Styles */
.email-login-container {
    padding: 20px 0;
}

.form-login {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
}

.dark .form-label {
    color: #fff;
}

.form-addons {
    position: relative;
}

.form-addons input {
    width: 100%;
    padding: 12px 45px 12px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.form-addons input:focus {
    outline: none;
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

.form-addons img {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    opacity: 0.5;
}

.pass-group {
    position: relative;
}

.pass-input {
    width: 100%;
    padding: 12px 45px 12px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.pass-input:focus {
    outline: none;
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

.toggle-password {
    cursor: pointer;
    position: absolute;
    top: 50%;
    right: 15px;
    transform: translateY(-50%);
    color: #888;
    transition: color 0.3s ease;
}

.toggle-password:hover {
    color: #0d6efd;
}

/* Dark Mode Adjustments */
.dark h3 {
    color: #fff !important;
}

.dark h4,
.dark p {
    color: #b0b0b0 !important;
}

.dark h4 a {
    color: #6ea8fe !important;
}

.dark .login-wrapper {
    background-color: #121212;
}

.dark input,
.dark .form-control {
    background-color: #1e1e1e;
    color: #fff;
    border-color: #444;
}

.dark input:focus,
.dark .form-control:focus {
    background-color: #2a2a2a;
    border-color: #0d6efd;
}

.btn-login {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 8px;
    background: #0d6efd;
    color: #fff;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-login:hover:not(:disabled) {
    background: #0b5ed7;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
}

.btn-login:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Image and Overlay */
.login-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.login-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.3));
    display: flex;
    align-items: center;
    justify-content: center;
}

.overlay-content {
    color: #fff;
    padding: 20px;
}

.restaurant-name {
    font-size: 3rem;
    font-weight: bold;
    text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.8);
    margin-bottom: 1rem;
    color: white;
}

.restaurant-desc {
    font-size: 1.25rem;
    color: #ffffffcc;
    text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.6);
    max-width: 600px;
    margin: 0 auto;
}

.text-sm {
    font-size: 0.875rem;
}

.alreadyuser h4 {
    font-size: 14px;
    margin: 0;
}

.hover-a {
    transition: color 0.3s ease;
}

.hover-a:hover {
    color: #0d6efd !important;
}

/* Responsive */
@media (max-width: 768px) {
    .keypad-btn {
        width: 60px;
        height: 60px;
        font-size: 20px;
    }

    .pin-dot {
        width: 45px;
        height: 45px;
        font-size: 28px;
    }

    .restaurant-name {
        font-size: 2rem;
    }

    .restaurant-desc {
        font-size: 1rem;
    }
}
</style>