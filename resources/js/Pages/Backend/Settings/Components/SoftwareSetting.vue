<script setup>
import { ref, computed } from "vue";
import axios from "axios";
import { toast } from "vue3-toastify";
// Import step components with CORRECT filenames
import Step1 from "../../../Onboarding/Steps/Step1.vue";
import Step2 from "../../../Onboarding/Steps/Step2.vue";
import Step3 from "../../../Onboarding/Steps/Step3.vue";
import Step4 from "../../../Onboarding/Steps/Step4.vue";
import Step5 from "../../../Onboarding/Steps/Step5.vue";
import Step6 from "../../../Onboarding/Steps/Step6.vue";
import Step7 from "../../../Onboarding/Steps/Step7.vue";
import Step8 from "../../../Onboarding/Steps/Step8.vue";
import Step9 from "../../../Onboarding/Steps/Step9.vue";
import RestoreModal from "@/Components/RestoreModal.vue";
import PasswordVerificationModal from '@/Components/PasswordVerificationModal.vue';

const props = defineProps({
    profile: Object,
    profileData: Object,
});

const currentSection = ref(1);
const formErrors = ref({});
const isSaving = ref(false);
const profile = ref({
    ...props.profileData.step1,
    ...props.profileData.step2,
    ...props.profileData.step3,
    ...props.profileData.step4,
    ...props.profileData.step5,
    ...props.profileData.step6,
    ...props.profileData.step7,
    ...props.profileData.step8,
    ...props.profileData.step9,
});

const showRestoreModal = ref(false);
const showPasswordModal = ref(false);
const isRestoring = ref(false);
const isVerifying = ref(false);
const passwordError = ref('');

const openRestoreModal = () => {
    showRestoreModal.value = true;
};

// Step 2: User confirmed, now ask for password
const handleRestoreConfirm = () => {
    showRestoreModal.value = false;
    showPasswordModal.value = true;
    passwordError.value = '';
};

// Step 3: Verify password then restore
const handlePasswordVerify = async (password) => {
    isVerifying.value = true;
    passwordError.value = '';

    try {
        // First, verify the password using axios
        const { data: verifyData } = await axios.post(route('settings.verify-password'), { password });

        if (!verifyData.success) {
            passwordError.value = verifyData.message;
            isVerifying.value = false;
            return;
        }

        // Password verified successfully - close modal and clear error
        isVerifying.value = false;
        showPasswordModal.value = false;
        passwordError.value = ''; // Clear any previous errors
        isRestoring.value = true;

        // Proceed to restore system using axios
        const { data: restoreData } = await axios.post(route('settings.restore'));

        if (restoreData.success === true) {
            toast.success(restoreData.message, {
                autoClose: 3000,
                position: toast.POSITION.BOTTOM_RIGHT,
            });

            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            toast.error(restoreData.message, {
                autoClose: 4000,
                position: toast.POSITION.BOTTOM_RIGHT,
            });
        }
    } catch (error) {
        console.error('Error:', error);

        // Handle axios specific errors
        if (error.response?.data?.message) {
            passwordError.value = error.response.data.message;
        } else {
            toast.error('An error occurred while restoring the system.', {
                autoClose: 4000,
                position: toast.POSITION.BOTTOM_RIGHT,
            });
        }
    } finally {
        isVerifying.value = false;
        isRestoring.value = false;
    }
};
const cancelPasswordVerification = () => {
    showPasswordModal.value = false;
    passwordError.value = '';
};

const cancelRestore = () => {
    showRestoreModal.value = false;
};

const handleRestoreSystem = async () => {
    isRestoring.value = true;

    try {
        const { data } = await axios.post(route('settings.restore'));

        // Check the success flag from the response
        if (data.success === true) {
            toast.success(data.message, {
                autoClose: 3000,
                position: toast.POSITION.BOTTOM_RIGHT,
            });
            showRestoreModal.value = false;

            // Reload the page after successful restore
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            toast.error(data.message, {
                autoClose: 4000,
                position: toast.POSITION.BOTTOM_RIGHT,
            });
        }
    } catch (error) {
        console.error('Error restoring system:', error);
        const message = error.response?.data?.message || 'An error occurred while restoring the system.';
        toast.error(message, {
            autoClose: 4000,
            position: toast.POSITION.BOTTOM_RIGHT,
        });
    } finally {
        isRestoring.value = false;
    }
};

const sections = [
    {
        id: 1,
        title: "Language & Location",
        icon: "bi-globe",
        description: "Set your timezone, language, and regional preferences",
    },
    {
        id: 2,
        title: "Business Information",
        icon: "bi-building",
        description: "Update restaurant name, contact details, and address",
    },
    {
        id: 3,
        title: "Currency & Locale",
        icon: "bi-currency-dollar",
        description: "Configure currency, number and date formats",
    },
    {
        id: 4,
        title: "Tax & VAT",
        icon: "bi-receipt",
        description: "Manage tax registration and rates",
    },
    {
        id: 5,
        title: "Service Options",
        icon: "bi-cart-check",
        description: "Configure order types and table management",
    },
    {
        id: 6,
        title: "Receipt & Printers",
        icon: "bi-printer",
        description: "Customize receipts and printer settings",
    },
    {
        id: 7,
        title: "Payment Methods",
        icon: "bi-credit-card",
        description: "Enable and configure payment options",
    },
    {
        id: 8,
        title: "Business Hours",
        icon: "bi-clock",
        description: "Set your operating hours",
    },
    {
        id: 9,
        title: "Advanced Features",
        icon: "bi-gear",
        description: "Advanced features and settings",
    },
];

const components = {
    1: Step1,
    2: Step2,
    3: Step3,
    4: Step4,
    5: Step5,
    6: Step6,
    7: Step7,
    8: Step8,
    9: Step9,
};

const currentComponent = computed(() => {
    return components[currentSection.value];
});

const currentSectionInfo = computed(() =>
    sections.find((s) => s.id === currentSection.value)
);

const progressPercent = computed(
    () => (currentSection.value / sections.length) * 100
);

// Handle data changes from step components
const saveStep = (payload) => {
    Object.assign(profile.value, payload?.data || {});
};

// Update section in backend
async function updateSection() {
    formErrors.value = {};
    isSaving.value = true;

    try {
        let payload;
        let config = {};

        // If step 2 or step 6, use FormData
        if (
            (currentSection.value === 2 && profile.value.logo_file) ||
            (currentSection.value === 6 && profile.value.receipt_logo_file)
        ) {
            payload = new FormData();
            Object.keys(profile.value).forEach((key) => {
                if (
                    profile.value[key] !== null &&
                    profile.value[key] !== undefined
                ) {
                    payload.append(key, profile.value[key]);
                }
            });
            config.headers = { "Content-Type": "multipart/form-data" };
        } else {
            payload = profile.value;
        }

        const { data } = await axios.post(
            `/settings/update/${currentSection.value}`,
            payload,
            config
        );

        Object.assign(profile.value, data.data || {});

        toast.success("Settings updated successfully!");
    } catch (err) {
        if (err?.response?.status === 422 && err.response.data?.errors) {
            formErrors.value = err.response.data.errors;
            toast.error("Please fix the validation errors.");
        } else {
            toast.error(
                err.response?.data?.message || "An unexpected error occurred."
            );
            console.error(err);
        }
    } finally {
        isSaving.value = false;
    }
}

function changeSection(sectionId) {
    formErrors.value = {};
    currentSection.value = sectionId;
}
</script>

<template>
    <div class="settings-wrapper">
        <div class="settings-header mb-2 px-2">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold mb-0">Restaurant Settings</h3>
                </div>
                <div>
                    <button @click="openRestoreModal"
                        class="btn btn-danger d-flex align-items-center gap-2 px-3 py-2 rounded-pill">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Restore System
                    </button>
                </div>
            </div>
        </div>
        <div class="settings-sidebar-horizontal mb-3">
            <div class="settings-nav-horizontal shadow-sm rounded-4 p-3">
                <div class="mb-3">
                    <h6 class="text-muted text-uppercase small fw-bold mb-3">
                        <i class="bi bi-gear-fill me-2"></i>
                        Settings
                    </h6>
                </div>

                <div class="nav-horizontal-container">
                    <ul class="nav nav-pills nav-horizontal gap-2">
                        <li class="nav-item" v-for="section in sections" :key="section.id">
                            <a class="nav-link-horizontal d-flex align-items-center gap-2 py-2 px-4"
                                :class="{ active: currentSection === section.id }"
                                @click.prevent="changeSection(section.id)" href="#" role="button">
                                <i :class="section.icon"></i>
                                <span class="fw-semibold small">{{ section.title }}</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="row g-0">
            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header border-0 p-4">
                        <div class="d-flex align-items-start gap-3">
                            <div class="icon-wrapper">
                                <i :class="currentSectionInfo?.icon" class="fs-3"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="mb-1 fw-bold">
                                    {{ currentSectionInfo?.title }}
                                </h5>
                                <p class="text-muted small mb-0">
                                    {{ currentSectionInfo?.description }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4 settings-content">
                        <component :is="currentComponent" :key="currentSection" :model="profile"
                            :form-errors="formErrors" @save="saveStep" />
                    </div>
                    <div class="card-footer bg-light border-0 p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Changes will be saved immediately
                            </small>
                            <button class="btn btn-primary px-4 rounded-pill" @click="updateSection"
                                :disabled="isSaving">
                                <span v-if="isSaving">
                                    <span class="spinner-border spinner-border-sm me-2"></span>
                                    Saving...
                                </span>
                                <span v-else>
                                    <i class="bi bi-check-circle me-2"></i>
                                    Save Changes
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <RestoreModal :show="showRestoreModal" :isLoading="false" title="Restore System"
        message="Are you sure you want to restore the system? This will permanently delete all orders, payments, purchases, kitchen orders, and shifts data. This action cannot be undone!"
        @confirm="handleRestoreConfirm" @cancel="cancelRestore" />

    <PasswordVerificationModal :show="showPasswordModal" :isVerifying="isVerifying || isRestoring"
        :errorMessage="passwordError" @confirm="handlePasswordVerify" @cancel="cancelPasswordVerification" />
</template>

<style scoped>
.settings-wrapper {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    padding: 5px 20px;
}

.settings-header {
    padding: 0px;
    padding-bottom: 4px;
}

/* Dark mode */
.dark .settings-wrapper {
    background: #212121;
    color: #fff;
}

.dark .card-header {
    background: linear-gradient(135deg, #181818 0%, #181818 100%);
}

.settings-sidebar-horizontal {
    width: 100%;
}

.settings-nav-horizontal {
    background-color: #fff;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(250, 250, 250, 0.3);
}

.dark .settings-nav-horizontal {
    background: #181818;
    color: #fff !important;
    border-color: #181818;
}

.nav-horizontal-container {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.dark .nav-link-horizontal {
    color: #fff;
}

.nav-link-horizontal:hover {
    background-color: rgba(28, 13, 130, 0.06);
    color: #1c0d82;
    border-radius: 7px;
    border-color: rgba(28, 13, 130, 0.1);
}

.nav-link-horizontal.active {
    background-color: #1C0D82;
    color: #fff;
    box-shadow: 0 4px 12px rgba(28, 13, 130, 0.3);
    border-radius: 7px;
    border-color: #1c0d82;
}

.nav-link-horizontal.active:hover {
    background-color: #1C0D82;
}

/* ===== Cards ===== */
.card {
    border: none;
    overflow: hidden;
    animation: fadeIn 0.3s ease-out;
}

.card-header.bg-gradient {
    background: linear-gradient(135deg,
            rgba(28, 13, 130, 0.03) 0%,
            rgba(28, 13, 130, 0.08) 100%);
    border-bottom: 2px solid #edf0f6;
}

.icon-wrapper {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #1c0d82 0%, #2d1ba8 100%);
    color: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(28, 13, 130, 0.2);
}

.settings-content {
    min-height: 400px;
    max-height: 800px;
    overflow-y: auto;
}

.settings-content::-webkit-scrollbar {
    width: 8px;
}

.settings-content::-webkit-scrollbar-track {
    background: #f1f3f5;
    border-radius: 10px;
}

.settings-content::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 10px;
}

.settings-content::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}

.card-footer {
    background: linear-gradient(180deg,
            rgba(255, 255, 255, 0.5) 0%,
            #f8f9fa 100%);
    border-top: 1px solid #edf0f6;
}

.dark .card-footer {
    background: linear-gradient(180deg, #181818 0%, #181818 100%);
}

/* ===== Animations ===== */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

:global(.dark .p-multiselect-header),
:global(.dark .p-multiselect-label),
:global(.dark .p-multiselect),
:global(.dark .p-multiselect-panel),
:global(.dark .p-multiselect-token),
:global(.dark .p-select),
:global(.dark .p-select-list-container),
:global(.dark .p-select-label) {
    background: #181818 !important;
    color: #fff !important;
}

:global(.dark .p-multiselect-list),
:global(.dark .p-multiselect-option),
:global(.dark .p-multiselect-overlay .p-checkbox-box),
:global(.dark .p-multiselect-filter),
:global(.dark .p-multiselect-filter-container) {
    background: #181818 !important;
    border-color: #555 !important;
    color: #fff !important;
}

:global(.dark .p-multiselect-option.p-highlight),
:global(.dark .p-multiselect-option:hover),
:global(.dark .p-select-option:hover),
:global(.dark .p-select-option.p-focus) {
    background: #222 !important;
    color: #fff !important;
}

:global(.dark .p-multiselect-chip) {
    background: #111 !important;
    color: #fff !important;
    border: 1px solid #555 !important;
    border-radius: 12px !important;
    padding: 0.25rem 0.5rem !important;
}

:global(.dark .p-multiselect-chip .p-chip-remove-icon) {
    color: #ccc !important;
}

:global(.dark .p-multiselect-chip .p-chip-remove-icon:hover) {
    color: #f87171 !important;
}

:global(.dark .p-placeholder) {
    color: #aaa !important;
}

/* ===== Responsive ===== */
@media (max-width: 991.98px) {
    .nav-horizontal {
        gap: 8px;
    }

    .nav-link-horizontal {
        padding: 8px 12px !important;
        font-size: 0.85rem;
    }

    .settings-content {
        min-height: 300px;
        max-height: 600px;
    }
}

@media (max-width: 576px) {
    .settings-wrapper {
        padding: 20px 15px;
    }

    .settings-header h3 {
        font-size: 1.3rem;
    }

    .nav-link-horizontal {
        padding: 7px 10px !important;
        font-size: 0.8rem;
    }

    .nav-link-horizontal i {
        display: none;
    }

    .icon-wrapper {
        width: 40px;
        height: 40px;
        font-size: 1.5rem;
    }

    .card-body,
    .card-header,
    .card-footer {
        padding: 12px !important;
    }
}

@media only screen and (min-device-width: 1024px) and (max-device-width: 1366px) {
    .settings-wrapper {
        padding-left: 0px;
        padding-right: 0px;
    }

    .settings-header h3 {
        font-size: 1.3rem;
    }

    .nav-link-horizontal {
        padding: 8px 16px !important;
        font-size: 0.9rem;
    }

    .settings-content {
        max-height: 700px;
    }
}
</style>