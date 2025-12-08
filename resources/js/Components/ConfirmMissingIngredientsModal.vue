<script setup>
import { ref, computed, watch, nextTick } from 'vue';

const props = defineProps({
    show: Boolean,
    missingIngredients: {
        type: Array,
        default: () => []
    },
});

const emit = defineEmits(['close', 'confirm']);

// ✅ Add debugging
watch(() => props.show, (newVal) => {
    console.log('📺 Modal show changed:', newVal);
    if (newVal) {
        console.log('📦 Missing ingredients:', props.missingIngredients);
    }
}, { immediate: true });

watch(() => props.missingIngredients, (newVal) => {
    console.log('📊 Missing ingredients changed:', newVal);
    console.log('📊 Array length:', newVal?.length);
    console.log('📊 Is array?:', Array.isArray(newVal));
}, { immediate: true, deep: true });

const groupedByItem = computed(() => {
    console.log('🔄 Computing groupedByItem');
    console.log('🔄 Input missingIngredients:', props.missingIngredients);
    
    if (!Array.isArray(props.missingIngredients) || props.missingIngredients.length === 0) {
        console.log('⚠️ No ingredients to group (not array or empty)');
        return {};
    }

    const grouped = {};
    
    props.missingIngredients.forEach((ing, index) => {
        console.log(`📝 Processing ingredient ${index}:`, ing);
        
        const key = `${ing.item_id}-${ing.variant_id || 'default'}`;
        
        if (!grouped[key]) {
            grouped[key] = {
                item_title: ing.item_title,
                variant_name: ing.variant_name,
                ingredients: []
            };
        }
        
        grouped[key].ingredients.push(ing);
    });

    console.log('✅ Final grouped data:', grouped);
    return grouped;
});

// ✅ Add helper computed for debugging
const hasData = computed(() => {
    return props.missingIngredients && props.missingIngredients.length > 0;
});

const groupCount = computed(() => {
    return Object.keys(groupedByItem.value).length;
});
</script>

<template>
    <!-- ✅ Add v-show for better debugging -->
    <div v-if="show" v-show="show" class="modal fade show d-block" tabindex="-1" 
         style="background: rgba(0,0,0,0.5); z-index: 9999;">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Ingredient Shortage Warning
                    </h5>

                    <button
                        class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                        title="Close"
                        @click="emit('close')"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6 text-red-500"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <div class="modal-body">
                    <!-- ✅ Add debug info -->
                    <div class="alert alert-info mb-3" v-if="!hasData">
                        <strong>Debug Info:</strong>
                        <ul class="mb-0">
                            <li>Has data: {{ hasData }}</li>
                            <li>Array length: {{ missingIngredients?.length || 0 }}</li>
                            <li>Is array: {{ Array.isArray(missingIngredients) }}</li>
                            <li>Group count: {{ groupCount }}</li>
                        </ul>
                    </div>

                    <div class="alert alert-warning">
                        <strong>Warning:</strong> The following items have ingredient shortages. 
                        The order can still be placed, but pending ingredient deductions will be recorded 
                        and automatically deducted when stock arrives.
                    </div>

                    <!-- Show message if no data -->
                    <div v-if="!hasData" class="alert alert-danger">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        No missing ingredient data available.
                        <br>
                        <small class="text-muted">
                            Received {{ missingIngredients?.length || 0 }} ingredient(s)
                        </small>
                    </div>

                    <!-- Show grouped items -->
                    <div v-else>
                        <div v-for="(group, key) in groupedByItem" :key="key" class="mb-4">
                            <h6 class="fw-bold">
                                {{ group.item_title }}
                                <span v-if="group.variant_name" class="badge bg-secondary ms-2">
                                    {{ group.variant_name }}
                                </span>
                            </h6>

                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Ingredient</th>
                                        <th class="text-end">Required</th>
                                        <th class="text-end">Available</th>
                                        <th class="text-end">Short</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="ing in group.ingredients" :key="ing.inventory_item_id">
                                        <td>{{ ing.inventory_item_name }}</td>
                                        <td class="text-end">
                                            {{ ing.required_quantity }} {{ ing.unit }}
                                        </td>
                                        <td class="text-end">
                                            {{ ing.available_quantity }} {{ ing.unit }}
                                        </td>
                                        <td class="text-end text-danger fw-bold">
                                            {{ ing.shortage_quantity }} {{ ing.unit }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary px-2 py-2" @click="emit('close')">
                        Cancel Order
                    </button>
                    <button type="button" class="btn btn-primary px-4 py-2" @click="emit('confirm')">
                        Proceed Anyway
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>