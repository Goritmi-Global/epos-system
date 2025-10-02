<template>
  <div v-if="show" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5)">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <!-- Modal Header -->
        <div class="modal-header">
          <h5 class="modal-title">Today's POS Orders</h5>
          <button type="button" class="btn-close" @click="$emit('close')"></button>
        </div>

        <!-- Modal Body -->
        <div class="modal-body">
          <!-- If no orders -->
          <div v-if="!orders || orders.length === 0" class="alert alert-info">
            No orders found for today
          </div>

          <!-- Orders Grid -->
          <div v-else class="row g-3">
            <div class="col-md-6 col-lg-4" v-for="order in orders" :key="order.id">
              <div class="card shadow-sm h-100">
                <div class="card-body">
                  <!-- Top Row: Order ID, Time & Amount -->
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                      <strong>Order ID: #{{ order.id }}</strong><br />
                      <small>Customer Name: {{ order.pos_order_type.order_type === 'Collection' ? 'Walk In' : order.customer_name }}</small>
                    </div>
                    <div class="text-end">
                      <span class="fw-bold">£{{ formatCurrency(order.pos_order_type.order.payment.amount_received) }}</span>
                      <span class="fw-bold">{{order.pos_order_type.order.payment.payment_type }}</span>
                    </div>
                  </div>

                  <!-- Order Status and Type -->
                  <div class="mb-2 text-center">
                    <span class="badge bg-secondary me-1">{{ order.pos_order_type.order_type }}</span>
                    <span 
                      class="badge"
                      :class="{
                        'bg-success': order.status?.toLowerCase() === 'paid',
                        'bg-warning text-dark': order.status?.toLowerCase() === 'waiting',
                        'bg-danger': order.status?.toLowerCase() === 'cancelled',
                        'bg-secondary': !order.status
                      }"
                    >
                      {{ order.status || 'Unknown' }}
                    </span>
                  </div>

                  <!-- Order Time -->
                  <div class="mb-2 text-center">
                    <small class="text-muted">Order Time: {{ order.order_time }}</small>
                  </div>
                  <!-- Order Items (if available) -->
                  <div v-if="order.items && order.items.length > 0">
                    <ul class="list-unstyled">
                      <li v-for="item in order.pos_order_type.order.items" :key="item.id">
                        <span>{{ item.title  }}</span> - £{{ formatCurrency(item.price) }} x {{ item.quantity }}
                      </li>
                    </ul>
                  </div>

                  <!-- Actions Row: View Order + Print -->
                  <div class="d-flex justify-content-between align-items-center">
                    <button class="btn btn-sm btn-outline-primary me-1" @click="viewOrderDetails(order)">
                      <i class="bi bi-info-circle"></i> View
                    </button>
                    <button class="btn btn-sm btn-outline-dark">
                      <i class="bi bi-printer"></i> Print
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- End Orders Grid -->
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from "vue";

// Define the props and emits for the modal
const props = defineProps({
  show: Boolean,
  orders: Array
});

const emit = defineEmits(["close", "view-details"]);

// Emit the view details event
const viewOrderDetails = (order) => {
  emit("view-details", order);
};

// Helper function to format currency
const formatCurrency = (amount) => {
  return parseFloat(amount).toFixed(2);
};
</script>

<style scoped>
/* Card styling */
.card {
  border-radius: 12px;
}

/* Badge styling for status and order types */
.badge {
  font-size: 0.8rem;
}

/* Button spacing */
.btn {
  font-size: 0.8rem;
}
</style>
