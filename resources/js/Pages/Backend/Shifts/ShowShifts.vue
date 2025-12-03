<template>
    <div v-if="showShiftModal"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div
            class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-xl font-semibold text-gray-800">Start Shift</h2>
                <p class="text-sm mt-1 text-gray-600">
                    Enter opening cash, complete checklist items, and add notes.
                </p>
            </div>

            <!-- Body - Scrollable -->
            <div class="px-6 py-4 overflow-y-auto check-list-body flex-1">
                <div class="mb-6">
                    <h3 class="text-sm font-semibold text-white mb-3">
                        Daily Safety Checklist
                    </h3>
                    <p class="text-xs text-gray-600 mb-4">
                        Complete all required checks before opening. You can add your own
                        checks too.
                    </p>
                    <div
                        class="space-y-3 bg-gray-50 p-4 check-list-item rounded-lg max-h-64 overflow-y-auto grid grid-cols-2 gap-4">
                        <div v-for="(item, index) in checklistItems" :key="'item-' + index"
                            class="flex items-start gap-2">
                            <Checkbox v-model="selectedChecklists[item.id]" :inputId="'check-' + index" binary
                                class="cursor-pointer" />
                            <label :for="'check-' + index" class="flex-1 cursor-pointer">
                                <span class="text-sm font-medium text-gray-700 block">{{
                                    item.name
                                    }}</span>
                                <span v-if="item.description" class="text-xs text-gray-500">{{ item.description
                                    }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Add Custom Checklist -->
                    <div class="mt-4 flex gap-2">
                        <input v-model="customChecklistName" type="text" class="form-control flex-1"
                            placeholder="Add your own check..." />
                        <button @click="addCustomChecklist"
                            class="btn btn-outline-secondary px-4 py-2 rounded-lg text-sm">
                            Add
                        </button>
                    </div>
                </div>

                <!-- Opening Cash Section -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Opening Cash</label>
                    <input type="number" v-model="openingCash" step="0.01" class="form-control w-full"
                        placeholder="0.00" />
                </div>

                <!-- Notes Section -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea v-model="notes" class="form-control w-full" rows="2"
                        placeholder="Optional notes..."></textarea>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-2 border-t border-gray-100 px-6 py-3 bg-gray-50">
                <button @click="startShift" :disabled="loadingStart || !isFormValid"
                    class="btn btn-primary py-2 px-4 rounded-pill flex items-center justify-center gap-2 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <span v-if="loadingStart" class="spinner-border spinner-border-sm mx-1"></span>
                    <span>{{ loadingStart ? " Starting..." : "Start Shift" }}</span>
                </button>
            </div>
        </div>
    </div>

    <!-- No Active Shift Modal -->
    <div v-if="showNoShiftModal"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 flex flex-col items-center">
            <h2 class="text-xl font-semibold mb-2 text-gray-800">No active shift</h2>
            <p class="text-sm text-gray-600 mb-4 text-center">
                There is no currently active shift available. Please ask manager or
                admin to start session or try again later.
            </p>

            <button @click="retryCheck" :disabled="loadingRetry"
                class="btn btn-primary py-2 px-4 w-100 rounded-pill flex items-center justify-center gap-2 transition">
                <span v-if="loadingRetry" class="spinner-border spinner-border-sm"></span>
                <span>{{ loadingRetry ? "Checking..." : "Retry" }}</span>
            </button>
        </div>
    </div>
</template>


<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';
import { toast } from "vue3-toastify";
import Checkbox from 'primevue/checkbox';


const page = usePage();

const showShiftModal = ref(page.props.showShiftModal || false);
const showNoShiftModal = ref(page.props.showNoShiftModal || false);

const openingCash = ref('');
const notes = ref('');
const loadingStart = ref(false);
const loadingRetry = ref(false);

const checklistItems = ref([]);
const selectedChecklists = ref({});
const customChecklistName = ref('');

onMounted(async () => {
    try {
        const response = await axios.get('/shift/checklist-items', {params: { type: 'start' }});
        checklistItems.value = response.data.data;
        checklistItems.value.forEach(item => {
            selectedChecklists.value[item.id] = false;
        });
    } catch (error) {
        console.error('Failed to fetch checklist items:', error);
        toast.error('Failed to load checklist items');
    }
});
const isFormValid = computed(() => {
    return openingCash.value !== '' && Object.values(selectedChecklists.value).some(val => val === true);
});
const addCustomChecklist = async () => {
    if (customChecklistName.value.trim()) {
        try {
            const response = await axios.post('/shift/checklist-items/custom', {
                name: customChecklistName.value,
                type: 'start', 
            });

            if (response.data.success) {
                const newItem = response.data.data;
                checklistItems.value.push(newItem);
                selectedChecklists.value[newItem.id] = true;
                customChecklistName.value = '';
                toast.success('Custom checklist added successfully');
            }
        } catch (error) {
            console.error('Failed to add custom checklist:', error);
            toast.error('Failed to add custom checklist');
        }
    }
};
const startShift = async () => {
    try {
        loadingStart.value = true;
        const selectedChecklistIds = Object.keys(selectedChecklists.value)
            .filter(id => selectedChecklists.value[id] === true);

        const response = await axios.post('/shift/start', {
            opening_cash: openingCash.value,
            notes: notes.value,
            checklists: selectedChecklistIds,
        });

        if (response.status === 200) {
            toast.success('Shift started successfully!');
            window.location.href = '/dashboard';
        }
    } catch (error) {
        console.error(error);
        toast.error(error.response?.data?.message || 'Failed to start shift');
    } finally {
        loadingStart.value = false;
    }
};

const retryCheck = async () => {
    try {
        loadingRetry.value = true;
        const res = await axios.post('/shift/check-active-shift');

        if (res.data.active) {
            window.location.href = res.data.redirect_url;
        } else {
            toast.error("Shift is still not active. Please try again later.");
        }
    } catch (error) {
        console.error(error);
        toast.error('Failed to check shift status');
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

.check-list-item {
    background-color: #212121 !important;
    color: #fff !important;
}

.check-list-body {
    background-color: #212121 !important;
}

.check-list-item input[type="checkbox"] {
    accent-color: #f5f5f5 !important;
    background-color: #3d3d3d !important;
    border: 1px solid #ccc !important;
}
.fixed {
    background-color: #181818 !important;
}

.dark .bg-white{
    background: #212121 !important;
}
.border-gray-100 {
    background-color: #212121 !important;
}

.body {
    background-color: #212121 !important;
    color: #fff !important;
}

h2 {
    color: #fff !important;
}

p {
    color: #fff !important;
}

.text-gray-700 {
    color: #fff !important;
}

input {
    background: #212121 !important;
    color: #fff !important;
}

textarea {
    background: #212121 !important;
    color: #fff !important;
}

.bg-white {
    border: 1px solid #fff !important;
}
</style>