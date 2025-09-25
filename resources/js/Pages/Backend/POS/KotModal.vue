<template>
    <div v-if="show" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5)">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0">
                <!-- Header -->
                <div class="modal-header text-black">
                    <h5 class="modal-title"> Kitchen Order Ticket
                    </h5>
                    <button type="button" class="btn-close" @click="$emit('close')"></button>
                </div>

                <!-- Body -->
                <div class="modal-body" v-if="kot">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Item Name</th>
                                    <th>Variant</th>
                                    <th>Ingredients</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in kot.items" :key="item.id || index">
                                    <td>{{ index + 1 }}</td>
                                    <td>{{ item.item_name }}</td>
                                    <td>{{ item.variant_name || '-' }}</td>
                                    <td>{{ item.ingredients.join(', ') }}</td>
                                    <td>{{ kot.status }}</td>
                                    <td>
                                        <div class="dropdown position-static">
                                            <a class="text-dark" href="#" role="button" data-bs-toggle="dropdown"
                                                aria-expanded="false" style="font-size: 18px;">
                                                &#8942;
                                            </a>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#"
                                                        @click.prevent="updateKotStatus(kot, 'Waiting')">Waiting</a>
                                                </li>
                                                <li><a class="dropdown-item" href="#"
                                                        @click.prevent="updateKotStatus(kot, 'Done')">Done</a></li>
                                                <li><a class="dropdown-item" href="#"
                                                        @click.prevent="updateKotStatus(kot, 'Cancelled')">Cancelled</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Footer -->
                <div class="modal-footer">
                    <button class="btn btn-primary rounded-pill py-2" @click="$emit('close')">Close</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import axios from 'axios';
import { toast } from 'vue3-toastify'; 

// Props
defineProps({
  show: Boolean,
  kot: Object
});

// Emit to parent
const emit = defineEmits(['close', 'status-updated']);

// Function to update KOT status
// In KotModal.vue
const updateKotStatus = async (kot, status) => {
  try {
    const response = await axios.put(`/pos/kot/${kot.id}/status`, { status });
    // don't mutate prop directly
    emit('status-updated', { id: kot.id, status: response.data.status, message: response.data.message });
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to update status');
  }
};

</script>
