
<script setup>
import { computed, ref } from "vue";
 
import StripePayment from "./StripePayment.vue";
// Props
// const props = defineProps({
//     show: Boolean,
//     customer: String,
//     orderType: String,
//     selectedTable: Object,
//     orderItems: Array,
//     grandTotal: Number,
//     money: Function,
//     cashReceived: Number,
//     client_secret : String,
//     order_code: String,
// });

const props = defineProps({
  show: Boolean,
  customer: String,
  orderType: String,
  selectedTable: Object,
  orderItems: Array,
  grandTotal: Number,
  money: Function,
  cashReceived: Number,
  client_secret: String,
  order_code: String,

  // NEW (missing) fields
  subTotal: Number,           // (if you used kebab in parent, this becomes subTotal here)
  tax: Number,
  serviceCharges: Number,
  deliveryCharges: Number,
  note: [String, null],
  orderDate: String,          // "YYYY-MM-DD"
  orderTime: String,          // "HH:MM:SS"
  paymentMethod: String,
  change: Number,
});



// Emits
const emit = defineEmits(["close", "confirm", "update:cashReceived"]);

const paymentMethod = ref("Cash");

// Two-way binding for cashReceived
const cashReceived = computed({
    get: () => props.cashReceived,
    set: (val) => emit("update:cashReceived", val),
});

// Computed change amount
const changeAmount = computed(() => {
    if (paymentMethod.value === "Cash") {
        return cashReceived.value - props.grandTotal;
    }
    return 0;
});

// Only emit when button is clicked
const handleConfirm = () => {
    emit("confirm", {
        paymentMethod: paymentMethod.value,
        cashReceived: cashReceived.value,
        changeAmount: changeAmount.value,
    });
};

const formattedOrderType = computed(() => {
    if (!props.orderType) return "";
    return props.orderType.replace(/_/g, " ").replace(/\b\w/g, (char) => char.toUpperCase());
});
</script>

<template>
    <div v-if="show" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 shadow-lg border-0">

                <!-- Header -->
                <div class="modal-header bg-gradient" style="background: linear-gradient(90deg,#0d6efd,#4e9fff);">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-receipt-cutoff me-2"></i> Confirm Order
                    </h5>
                    <button type="button" class="btn-close btn-close-white" @click="$emit('close')"></button>
                </div>

                <!-- Body -->
                <div class="modal-body">
                    <!-- Order Summary -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="bi bi-info-circle me-2"></i> Order Summary
                            </h6>
                            <ul class="list-group list-group-flush small">
                                <li class="list-group-item d-flex justify-content-between">
                                    <span><strong>Customer</strong></span>
                                    <span>{{ customer || "Walk In" }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span><strong>Service</strong></span>
                                    <span>
                                        <span class="badge bg-primary px-3 py-2">{{ formattedOrderType }}</span>
                                    </span>
                                </li>
                                <li v-if="orderType === 'dine_in'"
                                    class="list-group-item d-flex justify-content-between">
                                    <span><strong>Table No</strong></span>
                                    <span>{{ selectedTable?.name || "N/A" }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span><strong>Total Items</strong></span>
                                    <span>{{ orderItems.length }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between fs-5 fw-bold text-success">
                                    <span>Total</span>
                                    <span>{{ money(grandTotal) }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Payment Section -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="bi bi-credit-card me-2"></i> Payment Method
                            </h6>

                            <!-- Radio Buttons -->
                            <div class="d-flex gap-4 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" id="paymentCash" value="Cash"
                                        v-model="paymentMethod" />
                                    <label class="form-check-label" for="paymentCash">Cash</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" id="paymentCard" value="Card"
                                        v-model="paymentMethod" />
                                    <label class="form-check-label" for="paymentCard">Card</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" id="paymentSplit" value="Split"
                                        v-model="paymentMethod" />
                                    <label class="form-check-label" for="paymentSplit">Split</label>
                                </div>
                            </div>

                            <!-- Cash Input -->
                            <div v-if="paymentMethod === 'Cash'" class="border-top pt-3">
                                <label class="form-label fw-semibold">Cash Received</label>
                                <input type="number" v-model.number="cashReceived" class="form-control" />
                                <div class="mt-2">
                                    <strong>Change:</strong>
                                    <span :class="changeAmount < 0 ? 'text-danger fw-bold' : 'text-success fw-bold'">
                                        {{ money(changeAmount) }}
                                    </span>
                                </div>

                                <button class="btn btn-outline-secondary bg-secondary text-white btn-sm px-3 rounded-pill py-2"
                        @click="$emit('close')">
                        Cancel
                    </button>
                    <button class="btn btn-success btn-sm px-3 rounded-pill py-2" @click="handleConfirm">
                        <i class="bi bi-check2-circle me-1"></i> Confirm & Place
                    </button>
                    
                            </div>
                            <div v-if="paymentMethod === 'Card'" class="border-top pt-3">
                                <label class="form-label fw-semibold">Card Payment</label>
                                    <!-- <StripePayment
                                        :client_secret="client_secret" :order_code="order_code"
                                        
                                        :show="show"
                                        :customer="customer"
                                        :orderType="orderType"
                                        :selectedTable="selectedTable"
                                        :orderItems="orderItems"
                                        :grandTotal="grandTotal"
                                        :money="money"
                                        :cashReceived="cashReceived"
                                    /> -->

                                    <StripePayment
  :client_secret="client_secret"
  :order_code="order_code"

  :show="show"
  :customer="customer"
  :orderType="orderType"
  :selectedTable="selectedTable"
  :orderItems="orderItems"
  :grandTotal="grandTotal"
  :money="money"
  :cashReceived="cashReceived"

  :subTotal="subTotal"
  :tax="0"
  :serviceCharges="0"
  :deliveryCharges="0"
  :note="note"
  :orderDate="new Date().toISOString().split('T')[0]"
  :orderTime="new Date().toTimeString().split(' ')[0]"
  :paymentMethod="paymentMethod"
  :change="changeAmount"
/>


                                <div class="mt-2">
                                    <strong>Change:</strong>
                                    <span :class="changeAmount < 0 ? 'text-danger fw-bold' : 'text-success fw-bold'">
                                        {{ money(changeAmount) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                 
            </div>
        </div>
    </div>
</template>
