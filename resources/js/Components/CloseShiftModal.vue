<template>
    <!-- Close Shift Modal -->
    <div v-if="show"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div
            class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-xl font-semibold text-gray-800">Close Shift</h2>
                <p class="text-sm mt-1 text-gray-600">
                    Complete closing checklist items and add notes before closing the shift.
                </p>
            </div>

            <!-- Body - Scrollable -->
            <div class="px-6 py-4 overflow-y-auto check-list-body flex-1">
                <!-- ✅ Closing Checklists Section -->
                <div class="mb-6">
                    <h3 class="text-sm font-semibold text-white mb-3">
                        Daily Closing Checklist
                    </h3>
                    <p class="text-xs text-gray-600 mb-4">
                        Complete all required checks before closing. You can add your own checks too.
                    </p>

                    <!-- Grid Layout: Two per row -->
                    <div
                        class="space-y-3 bg-gray-50 p-4 check-list-item rounded-lg max-h-64 overflow-y-auto grid grid-cols-2 gap-4">
                        <!-- Default Checklist Items -->
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
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-2 border-t border-gray-100 px-6 py-3 bg-gray-50">
                <button @click="$emit('cancel')" :disabled="loading"
                    class="btn btn-secondary py-2 px-4 rounded-pill">
                    Cancel
                </button>
                <button @click="closeShift" :disabled="loading"
                    class="btn btn-danger py-2 px-4 rounded-pill flex items-center justify-center gap-2 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <span v-if="loading" class="spinner-border spinner-border-sm"></span>
                    <span>{{ loading ? "Closing..." : "Close Shift" }}</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import { toast } from "vue3-toastify";
import Checkbox from 'primevue/checkbox';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    shiftId: {
        type: Number,
        required: true
    },
    expectedClosingCash: {
        type: Number,
        default: 0
    }
});

const emit = defineEmits(['cancel', 'closed']);

const loading = ref(false);

const checklistItems = ref([]);
const selectedChecklists = ref({});
const customChecklistName = ref('');

// Watch for show prop changes to fetch closing checklist items
watch(() => props.show, async (newValue) => {
    if (newValue) {
        await fetchClosingChecklistItems();
    }
});

// Fetch closing checklist items
const fetchClosingChecklistItems = async () => {
    try {
        const response = await axios.get('/shift/checklist-items', {
            params: { type: 'end' }
        });
        checklistItems.value = response.data.data;

        // Initialize selected checklists object
        selectedChecklists.value = {};
        checklistItems.value.forEach(item => {
            selectedChecklists.value[item.id] = false;
        });
    } catch (error) {
        console.error('Failed to fetch closing checklist items:', error);
        toast.error('Failed to load closing checklist items');
    }
};


// Format currency
const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-GB', { 
        style: 'currency', 
        currency: 'GBP' 
    }).format(value);
};

// Add custom checklist
const addCustomChecklist = async () => {
    if (customChecklistName.value.trim()) {
        try {
            // ✅ Save to database first
            const response = await axios.post('/shift/checklist-items/custom', {
                name: customChecklistName.value,
                type: 'end', // for closing shift
            });

            if (response.data.success) {
                const newItem = response.data.data;
                
                // Add to local list
                checklistItems.value.push(newItem);
                
                // Auto-select it
                selectedChecklists.value[newItem.id] = true;
                
                // Clear input
                customChecklistName.value = '';
                
                toast.success('Custom checklist added successfully');
            }
        } catch (error) {
            console.error('Failed to add custom checklist:', error);
            toast.error('Failed to add custom checklist');
        }
    }
};

// Close shift
const closeShift = async () => {
    try {
        loading.value = true;

        // Collect selected checklist IDs
        const selectedChecklistIds = Object.keys(selectedChecklists.value)
            .filter(id => selectedChecklists.value[id] === true);

        const response = await axios.patch(`/api/shift/${props.shiftId}/close`, {
            checklists: selectedChecklistIds,
        });

        if (response.data.success) {
            toast.success(response.data.message || 'Shift closed successfully!');
            emit('closed', response.data);
            
            // Handle redirect
            if (response.data.redirect) {
                window.location.href = response.data.redirect;
            }
        }
    } catch (error) {
        console.error('Failed to close shift:', error);
        toast.error(error.response?.data?.message || 'Failed to close shift');
    } finally {
        loading.value = false;
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

.border-gray-100 {
    background-color: #212121 !important;
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