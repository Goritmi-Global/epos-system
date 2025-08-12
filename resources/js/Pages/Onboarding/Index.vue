<template>
    <div class="onboarding-wrapper">
        <!-- Top Header with Progress -->
        <div
            class="d-flex justify-content-between align-items-center mb-4 px-2"
        >
            <div>
                <h5 class="fw-bold mb-0">Onboarding Wizard</h5>
                <small class="text-muted"
                    >Step {{ current }} of {{ steps.length }} -
                    {{ steps.find((s) => s.id === current)?.title }}</small
                >
            </div>
            <div
                class="d-flex align-items-center gap-2"
                style="min-width: 200px"
            >
                <div class="progress flex-grow-1" style="height: 6px">
                    <div
                        class="progress-bar bg-primary"
                        :style="{ width: progressPercent + '%' }"
                    ></div>
                </div>
                <small class="fw-semibold"
                    >{{ current }}/{{ steps.length }}</small
                >
            </div>
        </div>

        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="steps-nav shadow-sm rounded-4 p-3 bg-white">
                    <!-- <h6 class="fw-bold text-primary mb-3">Setup Progress</h6> -->
                    <ul class="list-unstyled m-0">
                        <li
                            v-for="s in steps"
                            :key="s.id"
                            class="step-item p-2 rounded mb-1"
                            :class="{
                                'active-step': current === s.id,
                                'completed-step':
                                    progress.completed_steps?.includes(s.id),
                            }"
                            @click="gotoStep(s.id)"
                        >
                            <span>{{ s.title }}</span>
                            <i
                                v-if="progress.completed_steps?.includes(s.id)"
                                class="bi bi-check-circle-fill float-end text-success"
                            ></i>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9">
                <div class="card shadow-lg border-0 rounded-4 p-4">
                    <component
                        :is="comp"
                        :model="profile"
                        @save="saveStep"
                        @next="() => current++"
                        @back="() => current--"
                    />
                </div>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="d-flex justify-content-end mt-4">
            <button
                class="btn btn-outline-secondary me-2 rounded-pill"
                :disabled="current === 1"
                @click="current--"
            >
                Back
            </button>
            <button
                class="btn btn-primary rounded-pill px-4"
                @click="current++"
            >
                Next
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from "vue";
import Step1 from "./steps/Step1.vue";

const profile = ref({});
const progress = ref({ current_step: 1, completed_steps: [] });
const current = ref(1);

const steps = [
    { id: 1, title: "Welcome & Language Selection" },
    { id: 2, title: "Business Information" },
    { id: 3, title: "Currency & Locale" },
    { id: 4, title: "Tax & VAT Setup" },
    { id: 5, title: "Order Type & Service Options" },
    { id: 6, title: "Receipt & Printer Setup" },
    { id: 7, title: "Payment Methods" },
    { id: 8, title: "Users & Roles" },
    { id: 9, title: "Business Hours" },
    { id: 10, title: "Optional Features" },
];

const comp = computed(
    () =>
        ({
            1: Step1,
        }[current.value])
);

const progressPercent = computed(() => (current.value / steps.length) * 100);

function gotoStep(n) {
    current.value = n;
}
function saveStep(payload) {
    /* save logic */
}
</script>

<style scoped>
.onboarding-wrapper {
    background-color: #f8f9fc;
    padding: 20px;
    min-height: 100vh;
}

.steps-nav .step-item {
    cursor: pointer;
    transition: all 0.2s ease-in-out;
}
.steps-nav .step-item:hover {
    background-color: rgba(28, 13, 130, 0.08);
}
.active-step {
    background-color: #1c0d82;
    color: white;
}
.completed-step {
    background-color: rgba(25, 135, 84, 0.1);
}
</style>
