<!-- SplitPayment.vue -->
<script setup>
import { ref, computed, watch } from "vue";
import StripePayment from "./StripePayment.vue";

const props = defineProps({
  // required
  total: { type: Number, required: true },

  // pass-through (same as your parent uses for StripePayment)
  client_secret: String,
  order_code: String,
  customer: String,
  orderType: String,
  selectedTable: Object,
  orderItems: Array,
  money: Function,
  note: [String, null],
  orderDate: String,
  orderTime: String,
  tax: { type: Number, default: 0 },
  serviceCharges: { type: Number, default: 0 },
  deliveryCharges: { type: Number, default: 0 },
});

const emit = defineEmits(["confirm"]);

// --- State
// Start at 50/50 split (you can tweak default)
const cashReceived = ref(Math.max(0, Math.round((props.total || 0) / 2)));
const cardCharge   = ref(Math.max(0, (props.total || 0) - cashReceived.value));

// Keep the two fields locked so cash + card === total
let internalGuard = false;
watch(cashReceived, (v) => {
  if (internalGuard) return;
  internalGuard = true;
  const x = Number(v) || 0;
  cardCharge.value = Math.max(0, props.total - x);
  internalGuard = false;
});
watch(cardCharge, (v) => {
  if (internalGuard) return;
  internalGuard = true;
  const x = Number(v) || 0;
  cashReceived.value = Math.max(0, props.total - x);
  internalGuard = false;
});

// Deriveds
const subTotal = computed(() =>
  Array.isArray(props.orderItems)
    ? props.orderItems.reduce((sum, i) => sum + (Number(i.price) || 0) * (Number(i.qty) || 0), 0)
    : 0
);

// Validation
const errors = computed(() => {
  const errs = [];
  if (props.total <= 0) errs.push("Total amount must be greater than 0.");
  if (cashReceived.value < 0) errs.push("Cash cannot be negative.");
  if (cardCharge.value < 0) errs.push("Card amount cannot be negative.");
  const sum = (Number(cashReceived.value) || 0) + (Number(cardCharge.value) || 0);
  if (sum !== props.total) errs.push("Split must add up to the total.");
  return errs;
});

const canConfirm = computed(() => errors.value.length === 0);

// Emit to parent (parent will close modal / finalize order)
function confirmSplit() {
  if (!canConfirm.value) return;
  emit("confirm", {
    paymentMethod: "Split",
    cashReceived: Number(cashReceived.value) || 0,
    cardAmount: Number(cardCharge.value) || 0,
    // No change since we force exact sum; if you want change, relax the lock above
    changeAmount: 0,
  });
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="pay-header d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-columns-gap"></i>
      <h6 class="m-0">Split Payment</h6>
    </div>

    <!-- Amounts Row -->
    <div class="row g-3">
      <div class="col-12 col-sm-6">
        <label class="form-label fw-semibold">Cash Portion</label>
        <div class="input-group">
          <span class="input-group-text">$</span>
          <input
            type="number"
            min="0"
            :max="total"
            step="0.01"
            v-model.number="cashReceived"
            class="form-control rounded-3"
          />
        </div>
        <div class="small text-muted mt-1">
          {{ money ? money(cashReceived || 0) : cashReceived }}
        </div>
      </div>

      <div class="col-12 col-sm-6">
        <label class="form-label fw-semibold">Card Portion</label>
        <div class="input-group">
          <span class="input-group-text">$</span>
          <input
            type="number"
            min="0"
            :max="total"
            step="0.01"
            v-model.number="cardCharge"
            class="form-control rounded-3"
          />
        </div>
        <div class="small text-muted mt-1">
          {{ money ? money(cardCharge || 0) : cardCharge }}
        </div>
      </div>
    </div>

    <!-- Totals & Check -->
    <div class="d-flex justify-content-between align-items-center mt-3">
      <div class="fw-semibold">
        Total: <span class="text-success">{{ money ? money(total) : total }}</span>
      </div>
      <div class="small"
           :class="(cashReceived + cardCharge) === total ? 'text-success' : 'text-danger'">
        Cash + Card = {{ money ? money((cashReceived || 0) + (cardCharge || 0)) : (cashReceived || 0) + (cardCharge || 0) }}
      </div>
    </div>

    <!-- Errors -->
    <div v-if="errors.length" class="alert alert-danger rounded-3 mt-3 mb-0">
      <ul class="m-0 ps-3">
        <li v-for="(e, i) in errors" :key="i">{{ e }}</li>
      </ul>
    </div>

    <!-- Card Processor (Stripe) -->
    <div class="pt-3 border-top mt-3">
      <label class="form-label fw-semibold">Pay Card Portion</label>
      <!-- We pass cardCharge as the grandTotal for this Stripe instance -->
      <StripePayment
        :client_secret="client_secret"
        :order_code="order_code"
        :show="true"
        :customer="customer"
        :orderType="orderType"
        :selectedTable="selectedTable"
        :orderItems="orderItems"
        :grandTotal="cardCharge"
        :money="money"
        :cashReceived="cashReceived"
        :subTotal="subTotal"
        :tax="tax ?? 0"
        :serviceCharges="serviceCharges ?? 0"
        :deliveryCharges="deliveryCharges ?? 0"
        :note="note"
        :orderDate="orderDate ?? new Date().toISOString().split('T')[0]"
        :orderTime="orderTime ?? new Date().toTimeString().split(' ')[0]"
        :paymentMethod="'Split'"
        :change="0"
      />
    </div>

    <!-- Actions -->
    <div class="d-flex gap-2 mt-3">
      <button
        class="btn brand-btn rounded-pill px-3 py-2"
        :disabled="!canConfirm"
        @click="confirmSplit"
      >
        <i class="bi bi-check2-circle me-1"></i>
        Confirm Split
      </button>
    </div>
  </div>
</template>

<style scoped>
.pay-header i { color: var(--brand); }
</style>
