<template>
  <div class="mt-3 mb-2">
    <div class="pay-header d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-credit-card-2-front-fill"></i>
      <h6 class="m-0">Pay with Card</h6>
    </div>

    <div id="payment-element" class="stripe-box rounded-4 p-3"></div>

    <button type="button"
      class="btn btn-primary pay-btn rounded-pill mt-3 d-inline-flex align-items-center justify-content-center"
      :disabled="!isReady || isPaying" @click="pay">
      <span v-if="!isPaying">Pay £{{ (grandTotal ?? 0).toFixed(2) }}</span>
      <span v-else class="d-inline-flex align-items-center gap-2">
        Processing…
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
      </span>
    </button>
  </div>
</template>

<script setup>
import { toast } from "vue3-toastify";
import { ref, onMounted, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import axios from "axios";

const BRAND = "#1C0D82";

const props = defineProps({
  // removed: client_secret
  order_code: String,

  show: Boolean,
  customer: String,
  orderType: String,
  selectedTable: Object,
  orderItems: Array,
  grandTotal: Number,
  money: Function,
  cashReceived: Number,
  cardPayment: Number,
  subTotal: Number,
  tax: Number,
  serviceCharges: Number,
  deliveryCharges: Number,
  note: [String, null],
  kitchenNote: [String, null],
  orderDate: String,
  orderTime: String,
  paymentMethod: String,
  paymentType: String,
  change: Number,
  type: String, // 'full-payment' | 'split-payment'
  cardCharge: Number, // required if type is 'split-payment'  

  // ✅ ADD PROMO PROPS HERE
  promoDiscount: { type: Number, default: 0 },
  promoId: { type: [Number, String, null], default: null },
  promoName: { type: [String, null], default: null },
  promoType: { type: [String, null], default: null },
  promoDiscountAmount: { type: Number, default: 0 },
});




const page = usePage();

const stripe = ref(null);
const elements = ref(null);
const clientSecret = ref(null);
const isReady = ref(false);
const isPaying = ref(false);
let paymentElement = null;

// --- Create PI on the server with FINAL amount (£) ---
async function createPI() {
  try {
    const payload = {
      amount: props.grandTotal ?? 0,  // final amount from UI
      currency: "gbp",                // £
      order_code: props.order_code ?? null,
      // (optional) items: send line items to let server recompute totals
    };

    const { data } = await axios.post(route("stripe.pi.create"), payload, {
      headers: { "X-Idempotency-Key": crypto.randomUUID?.() ?? String(Date.now()) },
    });

    clientSecret.value = data.client_secret;
  } catch (e) {
    console.error(e);
    toast.error("Could not start card payment. Please try again.");
    throw e;
  }
}

async function initStripe() {
  stripe.value = window.Stripe(page.props.stripe_public_key);

  // 1) Create PI → get client_secret
  await createPI();

  // 2) Mount Elements with that client_secret
  const appearance = {
    theme: "stripe",
    variables: {
      colorPrimary: BRAND,
      colorText: "#0b1020",
      colorTextPlaceholder: "#98A2B3",
      colorBackground: "#FFFFFF",
      colorDanger: "#E5484D",
      borderRadius: "12px",
      fontFamily:
        'Inter, Poppins, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial',
    },
    rules: {
      ".Label": { fontWeight: "600" },
      ".Input": { boxShadow: "0 1px 2px rgba(0,0,0,.06)", padding: "12px" },
      ".Tab, .Block": { borderRadius: "12px" },
      ".Error": { color: "#E5484D" },
    },
  };

  elements.value = stripe.value.elements({ clientSecret: clientSecret.value, appearance });

  if (paymentElement) paymentElement.unmount?.();
  paymentElement = elements.value.create("payment");
  paymentElement.mount("#payment-element");

  isReady.value = true;
}

// async function pay() {
//   if (!stripe.value || !elements.value || !isReady.value) {
//     toast.error("Payment is not ready yet. Please wait a moment.");
//     return;
//   }
//   isPaying.value = true;

//   const params = new URLSearchParams({
//     order_code: props.order_code ?? "",
//     customer_name: props.customer ?? "",
//     sub_total: String(props.subTotal ?? props.grandTotal ?? 0),
//     total_amount: String(props.grandTotal ?? 0),
//     // for split payment
//     cardCharge: String(props.cardCharge ?? 0),
//     type: String(props.type ?? 0),

//     tax: String(props.tax ?? 0),
//     service_charges: String(props.serviceCharges ?? 0),
//     delivery_charges: String(props.deliveryCharges ?? 0),

//     note: props.note ?? "",
//     order_date: props.orderDate ?? new Date().toISOString().split("T")[0],
//     order_time: props.orderTime ?? new Date().toTimeString().split(" ")[0],
//     order_type:
//       props.orderType === "Dine_in"
//         ? "Dine In"
//         : props.orderType === "Delivery"
//         ? "Delivery"
//         : props.orderType === "Takeaway"
//         ? "Takeaway"
//         : "Collection",
//     table_number: props.selectedTable?.name ?? "",
//     payment_method: props.paymentMethod ?? "Stripe",
//     payment_type: props.paymentType ?? props.paymentMethod,
//     cash_received: String(props.cashReceived ?? props.grandTotal ?? 0),
//     card_payment: String(props.cardPayment ?? 0),
//     change: String(props.change ?? 0),
//     items: JSON.stringify(
//       (props.orderItems ?? []).map((it) => ({
//         product_id: it.id,
//         title: it.title,
//         quantity: it.qty,
//         price: it.price,
//         note: it.note ?? null,
//       }))
//     ),
//   });

//   try {
//     const { error } = await stripe.value.confirmPayment({
//       elements: elements.value,
//       confirmParams: {
//         return_url: `${window.location.origin}/pos/place-stripe-order?${params.toString()}`,
//       },
//     });

//     if (error) {
//       toast.error(error.message || "Payment failed. Please try again.");
//       isPaying.value = false;
//       return;
//     }

//     // Stripe will redirect; keep spinner
//     toast.success("Payment authorized. Finalizing your order…");
//   } catch (err) {
//     console.error(err);
//     toast.error("Something went wrong while confirming payment.");
//     isPaying.value = false;
//   }
// }


// In your Stripe payment component (Document 2), update the pay() function

async function pay() {
  if (!stripe.value || !elements.value || !isReady.value) {
    toast.error("Payment is not ready yet. Please wait a moment.");
    return;
  }
  isPaying.value = true;

  const params = new URLSearchParams({
    order_code: props.order_code ?? "",
    customer_name: props.customer ?? "",
    sub_total: String(props.subTotal ?? props.grandTotal ?? 0),
    total_amount: String(props.grandTotal ?? 0),

    // Add promo fields
    promo_discount: String(props.promoDiscount ?? 0),
    promo_id: String(props.promoId ?? ''),
    promo_name: String(props.promoName ?? ''),
    promo_type: String(props.promoType ?? ''),

    // for split payment
    cardCharge: String(props.cardCharge ?? 0),
    type: String(props.type ?? 0),

    tax: String(props.tax ?? 0),
    service_charges: String(props.serviceCharges ?? 0),
    delivery_charges: String(props.deliveryCharges ?? 0),

    note: props.note ?? "",
    kitchen_note: props.kitchenNote ?? "",
    order_date: props.orderDate ?? new Date().toISOString().split("T")[0],
    order_time: props.orderTime ?? new Date().toTimeString().split(" ")[0],
    order_type:
      props.orderType === "Dine_in"
        ? "Dine In"
        : props.orderType === "Delivery"
          ? "Delivery"
          : props.orderType === "Takeaway"
            ? "Takeaway"
            : "Collection",
    table_number: props.selectedTable?.name ?? "",
    payment_method: props.paymentMethod ?? "Stripe",
    payment_type: props.paymentType ?? props.paymentMethod,
    cash_received: String(props.cashReceived ?? props.grandTotal ?? 0),
    card_payment: String(props.cardPayment ?? 0),
    change: String(props.change ?? 0),
    items: JSON.stringify(
      (props.orderItems ?? []).map((it) => ({
        product_id: it.id,
        title: it.title,
        quantity: it.qty,
        price: it.price,
        note: it.note ?? null,
        kitchen_note: it.kitchenNote ?? null,
      }))
    ),
  });

  try {
    const { error } = await stripe.value.confirmPayment({
      elements: elements.value,
      confirmParams: {
        return_url: `${window.location.origin}/pos/place-stripe-order?${params.toString()}`,
      },
    });

    if (error) {
      toast.error(error.message || "Payment failed. Please try again.");
      isPaying.value = false;
      return;
    }
  } catch (err) {
    console.error(err);
    toast.error("Something went wrong while confirming payment.");
    isPaying.value = false;
  }
}

// (optional) if the amount can change, re-create PI so Stripe total stays in sync
watch(() => props.grandTotal, async (val, oldVal) => {
  if (val !== oldVal && stripe.value) {
    isReady.value = false;
    await initStripe();
  }
});

onMounted(async () => {
  if (!window.Stripe) {
    const script = document.createElement("script");
    script.src = "https://js.stripe.com/v3/";
    script.addEventListener("load", initStripe);
    script.addEventListener("error", () =>
      toast.error("Could not load Stripe. Check your network and try again.")
    );
    document.body.appendChild(script);
  } else {
    await initStripe();
  }
});
</script>

<style scoped>
:root {
  --brand: #1c0d82;
  --brand-600: #000000;
  --brand-300: #4b3bb0;
}

.pay-header {
  color: var(--brand);
}

.pay-header i {
  font-size: 1.25rem;
}

.pay-header h2 {
  font-size: 1.05rem;
  font-weight: 700;
  letter-spacing: 0.2px;
}

.stripe-box {
  border: 1px solid rgba(28, 13, 130, 0.15);
  background: #fff;
  box-shadow: 0 10px 18px rgba(0, 0, 0, 0.06);
}

.pay-btn {
  height: 56px;
  width: 100%;
  font-weight: 700;
  font-size: 18px;
  border: none;
  color: #fff;
  background: var(--brand);
  transition: transform 0.05s ease, box-shadow 0.2s ease, background 0.2s ease;
  box-shadow: 0 8px 18px rgba(28, 13, 130, 0.25);
}

.pay-btn:hover:not(:disabled) {
  box-shadow: 0 10px 22px rgba(28, 13, 130, 0.35);
}

.pay-btn:active:not(:disabled) {
  transform: translateY(1px);
}

.pay-btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
  box-shadow: none;
}

.dark .stripe-box {
  background-color: #212121 !important;
}
</style>
