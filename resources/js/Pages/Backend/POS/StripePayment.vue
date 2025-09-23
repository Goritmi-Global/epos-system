<template>
    <div class="mt-3 mb-2">
        <!-- Header -->
        <div class="pay-header d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-credit-card-2-front-fill"></i>
            <h6 class="m-0">Pay with Card</h6>
        </div>

        <!-- Stripe Element -->
        <div id="payment-element" class="stripe-box rounded-4 p-3"></div>

        <!-- Pay button (shows spinner while processing) -->
        <button
            type="button"
            class="btn pay-btn rounded-pill mt-3 d-inline-flex align-items-center justify-content-center"
            :disabled="!isReady || isPaying"
            @click="pay"
        >
            <span v-if="!isPaying"
                >Pay £{{ (grandTotal ?? 0).toFixed(2) }}</span
            >
            <span v-else class="d-inline-flex align-items-center gap-2">
                Processing…
                <span
                    class="spinner-border spinner-border-sm"
                    role="status"
                    aria-hidden="true"
                ></span>
            </span>
        </button>
    </div>
</template>

<script setup>
import { toast } from "vue3-toastify";
import { ref, onMounted } from "vue";
import { usePage } from "@inertiajs/vue3";

const BRAND = "#1C0D82"; 

const props = defineProps({
    client_secret: String,
    order_code: String,

    show: Boolean,
    customer: String,
    orderType: String,
    selectedTable: Object,
    orderItems: Array,
    grandTotal: Number,
    money: Function,
    cashReceived: Number,

    // pass-through fields
    subTotal: Number,
    tax: Number,
    serviceCharges: Number,
    deliveryCharges: Number,
    note: [String, null],
    orderDate: String,
    orderTime: String,
    paymentMethod: String,
    change: Number,
});

const page = usePage();

const stripe = ref(null);
const elements = ref(null);
const isReady = ref(false);
const isPaying = ref(false);

function setLoaded() {
    try {
        if (!window.Stripe) return;

        stripe.value = window.Stripe(page.props.stripe_public_key);

        // Theme Stripe Elements to your color & rounded corners
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
                ".Input": {
                    boxShadow: "0 1px 2px rgba(0,0,0,.06)",
                    padding: "12px",
                },
                ".Tab, .Block": { borderRadius: "12px" },
                ".Error": { color: "#E5484D" },
            },
        };

        elements.value = stripe.value.elements({
            clientSecret: props.client_secret,
            appearance,
        });

        const paymentElement = elements.value.create("payment", {
            clientSecret: props.client_secret,
        });
        paymentElement.mount("#payment-element");

        isReady.value = true;
    } catch (e) {
        console.error(e);
        toast.error(
            "Stripe failed to initialize. Please reload and try again."
        );
    }
}

async function pay() {
    if (!stripe.value || !elements.value || !isReady.value) {
        toast.error("Payment is not ready yet. Please wait a moment.");
        return;
    }

    isPaying.value = true;

    // Build query safely
    const params = new URLSearchParams({
        order_code: props.order_code ?? "",
        customer_name: props.customer ?? "",
        sub_total: String(props.subTotal ?? props.grandTotal ?? 0),
        total_amount: String(props.grandTotal ?? 0),
        tax: String(props.tax ?? 0),
        service_charges: String(props.serviceCharges ?? 0),
        delivery_charges: String(props.deliveryCharges ?? 0),
        note: props.note ?? "",
        order_date: props.orderDate ?? new Date().toISOString().split("T")[0],
        order_time: props.orderTime ?? new Date().toTimeString().split(" ")[0],
        order_type:
            props.orderType === "dine_in"
                ? "Dine In"
                : props.orderType === "delivery"
                ? "Delivery"
                : props.orderType === "takeaway"
                ? "Takeaway"
                : "Collection",
        table_number: props.selectedTable?.name ?? "",
        payment_method: props.paymentMethod ?? "Stripe",
        cash_received: String(props.cashReceived ?? props.grandTotal ?? 0),
        change: String(props.change ?? 0),
        items: JSON.stringify(
            (props.orderItems ?? []).map((it) => ({
                product_id: it.id,
                title: it.title,
                quantity: it.qty,
                price: it.price,
                note: it.note ?? null,
            }))
        ),
    });

    try {
        const result = await stripe.value.confirmPayment({
            elements: elements.value,
            confirmParams: {
                return_url: `${
                    window.location.origin
                }/pos/place-stripe-order?${params.toString()}`,
            },
        });

        if (result?.error) {
            console.log(result.error.message);
            toast.error(
                result.error.message || "Payment failed. Please try again."
            );
            isPaying.value = false;
            return;
        }

        // success (pre-redirect)
        toast.success("Payment authorized. Finalizing your order…");
        // keep spinner; Stripe will redirect
    } catch (err) {
        console.error(err);
        toast.error("Something went wrong while confirming payment.");
        isPaying.value = false;
    }
}

onMounted(() => {
    if (!window.Stripe) {
        const script = document.createElement("script");
        script.src = "https://js.stripe.com/v3/";
        script.addEventListener("load", setLoaded);
        script.addEventListener("error", () =>
            toast.error(
                "Could not load Stripe. Check your network and try again."
            )
        );
        document.body.appendChild(script);
    } else {
        setLoaded();
    }
});
</script>

<style scoped>
/* Brand token */
:root {
    --brand: #1c0d82;
    --brand-600: #000000; /* hover */
    --brand-300: #4b3bb0; /* focus ring */
}

/* Header */
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

/* Stripe container */
.stripe-box {
    border: 1px solid rgba(28, 13, 130, 0.15);
    background: #fff;
    box-shadow: 0 10px 18px rgba(0, 0, 0, 0.06);
}

/* Primary pay button */
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
</style>
