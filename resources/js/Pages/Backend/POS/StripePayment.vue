<template>
    <div class="mt-3 mb-2">
        <h1 class="c-card-title">
            {{ "Pay with Stripe" }}
        </h1>

        <div id="payment-element"></div>

        <!-- Pay button (shows spinner while processing) -->
        <button
            type="button"
            class="btn btn-success pay-btn mt-2 d-inline-flex align-items-center justify-content-center"
            :disabled="!isReady || isPaying"
            @click="pay"
        >
            <span v-if="!isPaying">{{ "Pay now" }}</span>
            <span v-else class="d-inline-flex align-items-center gap-2">
                {{ "Processing…" }}
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
        elements.value = stripe.value.elements({
            clientSecret: props.client_secret,
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
        // internal tracking
        order_code: props.order_code ?? "",

        // customer + order totals
        customer_name: props.customer ?? "",
        sub_total: String(props.subTotal ?? props.grandTotal ?? 0),
        total_amount: String(props.grandTotal ?? 0),
        tax: String(props.tax ?? 0),
        service_charges: String(props.serviceCharges ?? 0),
        delivery_charges: String(props.deliveryCharges ?? 0),

        // meta
        note: props.note ?? "",
        order_date: props.orderDate ?? new Date().toISOString().split("T")[0],
        order_time: props.orderTime ?? new Date().toTimeString().split(" ")[0],

        // type + table
        order_type:
            props.orderType === "dine_in"
                ? "Dine In"
                : props.orderType === "delivery"
                ? "Delivery"
                : props.orderType === "takeaway"
                ? "Takeaway"
                : "Collection",
        table_number: props.selectedTable?.name ?? "",

        // payment
        payment_method: props.paymentMethod ?? "Stripe",
        cash_received: String(props.cashReceived ?? props.grandTotal ?? 0),
        change: String(props.change ?? 0),

        // items as JSON
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
                // Stripe will append payment_intent, redirect_status, etc.
                return_url: `${
                    window.location.origin
                }/pos/place-stripe-order?${params.toString()}`,
            },
        });

        // If there's an immediate error (e.g. validation, card declined)
        if (result?.error) {
            console.log(result.error.message);
            toast.error(
                result.error.message || "Payment failed. Please try again."
            );
            isPaying.value = false;
            return;
        }

        // No immediate error: Stripe will redirect now (or completed without redirect)
        toast.success("Payment authorized. Finalizing your order…");
        // We keep the button in 'processing' state; the page will redirect.
    } catch (err) {
        console.error(err);
        toast.error("Something went wrong while confirming payment.");
        isPaying.value = false;
    }
}

onMounted(() => {
    // Avoid duplicate loads if component re-mounts
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
.pay-btn {
    height: 70px;
    width: 100%;
    font-weight: bold;
    font-size: 24px;
}
.c-card-title {
    font-size: 18px;
    font-weight: 500;
    color: #006860;
    font-family: "Poppins", sans-serif;
}
</style>
