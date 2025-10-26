<template>
    <Master>
        <!-- Show Shift Management Modal  -->
        <div v-if="showShiftModal"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col">

                <!-- Header -->
                <div class="px-6 py-4 border-b border-gray-100 text-center">
                    <h2 class="text-xl font-semibold text-gray-800">Start Shift</h2>
                    <p class="text-sm mt-1 text-gray-600">Enter opening cash and notes to begin the shift.</p>
                </div>

                <!-- Body -->
                <div class="px-6 py-4 flex flex-col gap-4 body">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Opening Cash</label>
                        <input type="number" v-model="openingCash" step="0.01" class="form-control w-full"
                            placeholder="0.00" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea v-model="notes" class="form-control w-full" rows="3"
                            placeholder="Optional notes..."></textarea>
                    </div>
                </div>

                <!-- Footer -->
                <!-- Footer -->
                <div class="flex justify-end gap-2 border-t border-gray-100 px-6 py-3">
                    <button @click="startShift" :disabled="loadingStart"
                        class="btn btn-primary py-1 px-4 rounded-pill flex items-center justify-center gap-2 transition">
                        <span v-if="loadingStart" class="spinner-border spinner-border-sm"></span>
                        <span>{{ loadingStart ? "Starting..." : "Start Shift" }}</span>
                    </button>
                </div>


            </div>
        </div>

        <!-- No Active Shift modal for other users -->

        <!-- No Active Shift Modal for other users -->
        <div v-if="showNoShiftModal"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 flex flex-col items-center">
                <h2 class="text-xl font-semibold mb-2 text-gray-800">No active shift</h2>
                <p class="text-sm text-gray-600 mb-4 text-center">
                    There is no currently active shift available.
                    Please ask manager or admin to start session or try again later.
                </p>

                <button @click="retryCheck" :disabled="loadingRetry"
                    class="btn btn-primary py-2 px-4 w-100 rounded-pill flex items-center justify-center gap-2 transition">
                    <span v-if="loadingRetry" class="spinner-border spinner-border-sm"></span>
                    <span>{{ loadingRetry ? "Checking..." : "Retry" }}</span>
                </button>
            </div>
        </div>



    </Master>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';
import { toast } from "vue3-toastify";
const page = usePage();

// const showShiftModal = ref(true);
// const showNoShiftModal = ref(true);
console.log('page.props.showShiftModal', page.props.showShiftModal);
const showShiftModal = ref(page.props.showShiftModal || false);
const showNoShiftModal = ref(page.props.showNoShiftModal || false);

const openingCash = ref('');
const notes = ref('');
const loadingStart = ref(false);
const loadingRetry = ref(false);


const startShift = async () => {
    try {
        loadingStart.value = true;
        const response = await axios.post('/shift/start', {
            opening_cash: openingCash.value,
            notes: notes.value
        });

        // If backend returns success, redirect manually
        if (response.status === 200) {
            window.location.href = '/dashboard';
        }
    } catch (error) {
        console.error(error);
        alert('Failed to start shift');
    } finally {
        loadingStart.value = false;
    }
};

// Retry check for other users
const retryCheck = async () => {
    try {
        loadingRetry.value = true;
        const res = await axios.post('/shift/check-active-shift');

        if (res.data.active) {
            // The user is already joined in the backend
            window.location.href = res.data.redirect_url;
        } else {
            toast.error("Shift is still not active. Please try again later.");
        }
    } catch (error) {
        console.error(error);
        alert('Failed to check shift status');
    } finally {
        loadingRetry.value = false;
    }
};

</script>


<style scoped>


.btn {
    border-radius: 10px !important;
    height: 42px !important;
    font-size: 16px !important;
}

.fixed{
    background-color: #181818 !important;
}

 .border-gray-100{
    background-color: #212121 !important;
}

.body{
    background-color: #212121 !important;
    color: #fff !important;
}
h2{
    color: #fff !important;
}
p{
    color: #fff !important;
}

.text-gray-700{
    color: #fff !important;
}

input{
    background: #212121 !important;
    color: #fff !important;
}

textarea{
    background: #212121 !important;
    color: #fff !important;
}
.bg-white{
    border: 1px solid #fff !important;
}

</style>
 