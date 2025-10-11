<!-- SplitPayment.vue -->
<script setup>
import { ref, computed, watch } from "vue";
import StripePayment from "./StripePayment.vue";
import { useFormatters } from '@/composables/useFormatters'

const { formatMoney, formatCurrencySymbol, formatNumber, dateFmt } = useFormatters()

const props = defineProps({
  order_code: String,

  show: Boolean,
  customer: String,
  orderType: String,
  selectedTable: Object,
  orderItems: Array,
  grandTotal: Number,          // total to be paid (cash + card)
  money: Function,
  cashReceived: Number,        // optional initial cash
  subTotal: Number,
  tax: Number,
  serviceCharges: Number,
  deliveryCharges: Number,
  note: [String, null],
  orderDate: String,
  orderTime: String,
  paymentMethod: String,        
  paymentType: String,        
  change: Number,
});



const emit = defineEmits(["confirm"]);

// ---------- helpers ----------
const total = computed(() => Number(props.grandTotal ?? 0));

// Split state (default: 50/50 integer amounts)
const half = Math.floor(total.value / 2);

// Cash = floor half
const cash = ref(half);

// Card = remainder (ensures sum == total)
let remainder = total.value - half;
const card = ref(remainder);

// Guarantee card portion ≥ 0.50
if (card.value < 0.5) {
  card.value = 0.5;
  cash.value = total.value - card.value;
}

 
/** lock to avoid recursive watch loops */
let guard = false;
watch(cash, (v) => {
  if (guard) return;
  guard = true;
  const x = Number(v) || 0;
  card.value = Math.max(0, Math.round((total.value * 100 - x * 100)) / 100);
  guard = false;
});
watch(card, (v) => {
  if (guard) return;
  guard = true;
  const x = Number(v) || 0;
  cash.value = Math.max(0, Math.round((total.value * 100 - x * 100)) / 100);
  guard = false;
});

// Derived subTotal (from items) if not provided
const derivedSubTotal = computed(() => {
  if (!Array.isArray(props.orderItems)) return 0;
  return props.orderItems.reduce((sum, i) => {
    const price = Number(i.price) || 0;
    const qty = Number(i.qty ?? i.quantity) || 0;
    return sum + price * qty;
  }, 0);
});

// Use provided subTotal or derived
const subTotalToSend = computed(() =>
  Number.isFinite(props.subTotal) ? Number(props.subTotal) : derivedSubTotal.value
);

// No change for strict split
const changeAmount = computed(() => 0);

// ---------- validation ----------
function cents(n) { return Math.round((Number(n) || 0) * 100); }
const errors = computed(() => {
  const out = [];
  if (total.value <= 0) out.push("Total amount must be greater than 0.");
  if (cash.value < 0) out.push("Cash cannot be negative.");
  if (card.value < 0) out.push("Card amount cannot be negative.");
  if (cents(cash.value) + cents(card.value) !== cents(total.value)) {
    out.push("Cash + Card must equal the total.");
  }
  return out;
});
const canConfirm = computed(() => errors.value.length === 0);

// ---------- emit to parent (if you also save a cash movement right away) ----------
function confirmSplit() {
  if (!canConfirm.value) return;
  emit("confirm", {
    paymentMethod: "Split",
    cashReceived: Number(cash.value) || 0,
    cardAmount: Number(card.value) || 0,
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
          <span class="input-group-text">£</span>
          <input
            type="number"
            min="0"
            :max="total"
            step="0.01"
            v-model.number="cash"
            class="form-control rounded-3"
          />
        </div>
        <div class="small text-muted mt-1">
          {{ money ? formatCurrencySymbol(cash || 0) : (cash || 0) }}
        </div>
      </div>

      <div class="col-12 col-sm-6">
        <label class="form-label fw-semibold">Card Portion</label>
        <div class="input-group">
          <span class="input-group-text">£</span>
          <input
            type="number"
            min="0"
            :max="total"
            step="0.01"
            v-model.number="card"
            class="form-control rounded-3"

          />
        </div>
        <div class="small text-muted mt-1">
          {{ money ? formatCurrencySymbol(card || 0) : (card || 0) }}
        </div>
      </div>
    </div>

    <!-- Totals & Check -->
    <div class="d-flex justify-content-between align-items-center mt-3">
      <div class="fw-semibold">
        Total:
        <span class="text-success">{{ money ? formatCurrencySymbol(total) : total }}</span>
      </div>
      <div
        class="small"
        :class="cents(cash) + cents(card) === cents(total) ? 'text-success' : 'text-danger'"
      >
        Cash + Card =
        {{
          money
            ? formatCurrencySymbol((cash || 0) + (card || 0))
            : (Number(cash || 0) + Number(card || 0))
        }}
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

      <!-- We pass card as grandTotal for Stripe; include split metadata -->
        
      <StripePayment
        :order_code="order_code"
        :show="show"
        :customer="customer"
        :orderType="orderType"
        :selectedTable="selectedTable"
        :orderItems="orderItems"

        :grandTotal="grandTotal"           

        :money="money"
        :cashReceived="cash"           
        :cardPayment="card"           
        :subTotal="subTotalToSend"
        :tax="tax ?? 0"
        :serviceCharges="serviceCharges ?? 0"
        :deliveryCharges="deliveryCharges ?? 0"
        :note="note"
        :orderDate="orderDate ?? new Date().toISOString().split('T')[0]"
        :orderTime="orderTime ?? new Date().toTimeString().split(' ')[0]"

        :paymentMethod="paymentMethod"       
        :change="changeAmount"

        :paymentType="paymentType"
        :cardCharge="cardCharge"
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

.dark .input-group-text{
  color: #fff !important;
  background-color: #212121 !important;

}
</style>
