<!-- components/Receipt.vue -->
<template>
  <div ref="receiptRef" class="receipt p-2" style="font-family: monospace; width: 280px;">
    <!-- Business Logo + Name -->
    <div class="text-center mb-2">
      <img
        v-if="restaurant.logo"
        :src="restaurant.logo"
        alt="Logo"
        style="max-width: 80px; margin-bottom: 4px;"
      />
      <div class="fw-bold text-uppercase" style="font-size: 16px;">
        {{ restaurant.name }}
      </div>
    </div>

    <!-- Order ID and Date/Time -->
    <div class="text-center my-1">
      <div class="fw-bold">Order ID: #{{ order.id }}</div>
      <div style="display:flex; justify-content: space-between; font-size: 12px;">
        <span>{{ order.order_date }}</span>
        <span>{{ order.order_time }}</span>
      </div>
    </div>

    <hr />

    <!-- Customer & Payment Details -->
    <div style="font-size: 12px;">
      <div style="display:flex; justify-content: space-between;">
        <span>Payment Type:</span>
        <span>{{ order.payment_method }}</span>
      </div>
      <div style="display:flex; justify-content: space-between;">
        <span>Order Type:</span>
        <span>{{ order.order_type }}</span>
      </div>
      <div style="display:flex; justify-content: space-between;">
        <span>Customer Name:</span>
        <span>{{ order.customer_name || "Walk In" }}</span>
      </div>
    </div>

    <hr />

    <!-- Items -->
    <div style="font-size: 12px;">
      <div style="display:flex; justify-content: space-between; font-weight:bold;">
        <span>Item</span>
        <span>Qty</span>
        <span>Price</span>
      </div>
      <div v-for="item in order.items" :key="item.product_id" style="display:flex; justify-content: space-between;">
        <span>{{ item.title }}</span>
        <span>{{ item.quantity }}</span>
        <span>{{ money(item.price) }}</span>
      </div>
    </div>

    <hr />

    <!-- Totals -->
    <div style="font-size: 12px; display:flex; justify-content: space-between;">
      <span>SubTotal:</span>
      <span>{{ money(order.sub_total) }}</span>
    </div>
    <div style="font-size: 12px; display:flex; justify-content: space-between; font-weight:bold;">
      <span>Total Price:</span>
      <span>{{ money(order.total_amount) }}</span>
    </div>

    <hr />

    <!-- Business Info -->
    <div style="font-size: 10px; text-align:center;">
      <div>Location: {{ restaurant.location }}</div>
      <div>Email: {{ restaurant.email }}</div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

// Props
const props = defineProps({
  order: Object,
  money: Function,
  restaurant: {
    type: Object,
    default: () => ({
      name: "My Restaurant",
      logo: null,
      location: "Peshawar, Pakistan",
      email: "kashifkhan4unoor@gmail.com",
    }),
  },
});

// Ref for the receipt div (useful for printing)
const receiptRef = ref(null);
</script>


<style scoped>
.receipt {
  background: white;
  padding: 6px;
}
hr {
  border: dashed 1px #000;
  margin: 4px 0;
}
</style>
