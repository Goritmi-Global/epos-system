<script setup>
import { computed, ref } from "vue";
import StripePayment from "./StripePayment.vue";

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

    tax: Number,
    serviceCharges: Number,
    deliveryCharges: Number,
    note: [String, null],
    orderDate: String,
    orderTime: String,
    paymentMethod: String,
    change: Number,
});

const emit = defineEmits(["close", "confirm", "update:cashReceived"]);

const paymentMethod = ref("Cash");

const cashReceived = computed({
    get: () => props.cashReceived,
    set: (val) => emit("update:cashReceived", val),
});

const changeAmount = computed(() => {
    if (paymentMethod.value === "Cash") {
        return cashReceived.value - props.grandTotal;
    }
    return 0;
});

const handleConfirm = () => {
    emit("confirm", {
        paymentMethod: paymentMethod.value,
        cashReceived: cashReceived.value,
        changeAmount: changeAmount.value,
    });
};

const formattedOrderType = computed(() => {
    if (!props.orderType) return "";
    return props.orderType
        .replace(/_/g, " ")
        .replace(/\b\w/g, (char) => char.toUpperCase());
});
</script>

<template>
    <div
        v-if="show"
        class="modal fade show d-block"
        tabindex="-1"
        style="background: rgba(0, 0, 0, 0.5)"
    >
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 shadow-lg border-0">
                <!-- Header -->
                <div class="modal-header brand-gradient text-white">
                    <h5
                        class="modal-title fw-bold d-flex align-items-center gap-2"
                    >
                        <i class="bi bi-receipt-cutoff"></i>
                        Confirm Order
                    </h5>

                    <button
                        class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                        title="Close"
                        @click="$emit('close')"
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

                <!-- Body -->
                <div class="modal-body">
                    <!-- Order Summary -->
                    <div class="card border-0 brand-card mb-4">
                        <div class="card-body">
                            <h6
                                class="fw-bold brand-text mb-3 d-flex align-items-center gap-2"
                            >
                                <i class="bi bi-info-circle"></i> Order Summary
                            </h6>

                            <div class="row g-3">
                                <!-- Customer -->
                                <div class="col-md-6">
                                    <div
                                        class="p-3 rounded-3 bg-light d-flex flex-column h-100"
                                    >
                                        <span class="text-muted small"
                                            >Customer</span
                                        >
                                        <span class="fw-semibold">{{
                                            customer || "Walk In"
                                        }}</span>
                                    </div>
                                </div>

                                <!-- Service -->
                                <div class="col-md-6">
                                    <div
                                        class="p-3 rounded-3 bg-light d-flex flex-column h-100"
                                    >
                                        <span class="text-muted small"
                                            >Service</span
                                        >
                                        <span class="fw-semibold">
                                            <span
                                                class="badge brand-badge px-3 py-2"
                                            >
                                                {{ formattedOrderType }}
                                            </span>
                                        </span>
                                    </div>
                                </div>

                                <!-- Table No (if dine in) -->
                                <div
                                    v-if="orderType === 'dine_in'"
                                    class="col-md-6"
                                >
                                    <div
                                        class="p-3 rounded-3 bg-light d-flex flex-column h-100"
                                    >
                                        <span class="text-muted small"
                                            >Table No</span
                                        >
                                        <span class="fw-semibold">{{
                                            selectedTable?.name || "N/A"
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 brand-card mb-4">
                        <div class="card-body">
                            <h6
                                class="fw-bold brand-text mb-3 d-flex align-items-center gap-2"
                            >
                                <i class="bi bi-bag-check"></i> Ordered Items
                            </h6>

                            <div class="table-responsive">
                                <table
                                    class="table align-middle table-hover mb-0"
                                >
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Item</th>
                                            <th class="text-center">Qty</th>
                                            <th class="text-end">Price</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(item, index) in orderItems"
                                            :key="item.id"
                                        >
                                            <td>{{ index + 1 }}</td>
                                            <td>
                                                <div class="fw-semibold">
                                                    {{ item.title }}
                                                </div>
                                                <div
                                                    v-if="item.note"
                                                    class="small text-muted"
                                                >
                                                    Note: {{ item.note }}
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                {{ item.qty }}
                                            </td>
                                            <td class="text-end">
                                                {{ money(item.price) }}
                                            </td>
                                            <td class="text-end fw-bold">
                                                {{
                                                    money(item.price * item.qty)
                                                }}
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="fw-bold">
                                            <td colspan="4" class="text-end">
                                                Grand Total
                                            </td>
                                            <td class="text-end text-success">
                                                {{ money(grandTotal) }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Section -->
                    <div class="card border-0 brand-card mb-2">
                        <div class="card-body">
                            <h6
                                class="fw-bold brand-text mb-3 d-flex align-items-center gap-2"
                            >
                                <i class="bi bi-credit-card"></i> Payment Method
                            </h6>

                            <!-- Segmented radios -->
                            <div
                                class="segmented mt-2 mb-3"
                                role="tablist"
                                aria-label="Payment method"
                            >
                                <input
                                    class="segmented-input"
                                    type="radio"
                                    id="paymentCash"
                                    :name="'payMethod'"
                                    value="Cash"
                                    v-model="paymentMethod"
                                />
                                <label
                                    class="segmented-label"
                                    for="paymentCash"
                                >
                                    <i class="bi bi-cash-coin me-1"></i> Cash
                                </label>

                                <input
                                    class="segmented-input"
                                    type="radio"
                                    id="paymentCard"
                                    :name="'payMethod'"
                                    value="Card"
                                    v-model="paymentMethod"
                                />
                                <label
                                    class="segmented-label"
                                    for="paymentCard"
                                >
                                    <i
                                        class="bi bi-credit-card-2-front me-1"
                                    ></i>
                                    Card
                                </label>

                                <input
                                    class="segmented-input"
                                    type="radio"
                                    id="paymentSplit"
                                    :name="'payMethod'"
                                    value="Split"
                                    v-model="paymentMethod"
                                />
                                <label
                                    class="segmented-label"
                                    for="paymentSplit"
                                >
                                    <i class="bi bi-columns-gap me-1"></i> Split
                                </label>
                            </div>

                            <!-- Cash -->
                            <div
                                v-if="paymentMethod === 'Cash'"
                                class="pt-2 border-top"
                            >
                                <label class="form-label fw-semibold"
                                    >Cash Received</label
                                >
                                <input
                                    type="number"
                                    v-model.number="cashReceived"
                                    class="form-control rounded-3"
                                />
                                <div class="mt-2">
                                    <strong>Change:</strong>
                                    <span
                                        :class="
                                            changeAmount < 0
                                                ? 'text-danger fw-bold'
                                                : 'text-success fw-bold'
                                        "
                                    >
                                        {{ money(changeAmount) }}
                                    </span>
                                </div>

                                <div class="d-flex gap-2 mt-3">
                                    <button
                                        class="btn btn-outline-secondary rounded-pill px-3 py-2"
                                        @click="$emit('close')"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        class="btn brand-btn rounded-pill px-3 py-2"
                                        @click="handleConfirm"
                                    >
                                        <i class="bi bi-check2-circle me-1"></i>
                                        Confirm & Place
                                    </button>
                                </div>
                            </div>

                            <!-- Card -->
                            <div
                                v-if="paymentMethod === 'Card'"
                                class="pt-3 border-top"
                            >
                                <label class="form-label fw-semibold"
                                    >Pay with Card</label
                                >

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
                                    :orderDate="
                                        new Date().toISOString().split('T')[0]
                                    "
                                    :orderTime="
                                        new Date().toTimeString().split(' ')[0]
                                    "
                                    :paymentMethod="paymentMethod"
                                    :change="changeAmount"
                                />

                                <div class="mt-2">
                                    <strong>Change:</strong>
                                    <span
                                        :class="
                                            changeAmount < 0
                                                ? 'text-danger fw-bold'
                                                : 'text-success fw-bold'
                                        "
                                    >
                                        {{ money(changeAmount) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /body -->
            </div>
        </div>
    </div>
</template>

<style scoped>
/* ===== Brand tokens ===== */
:root {
    --brand: #1c0d82;
    --brand-700: #150a6a;
    --brand-300: #4b3bb0;
}

/* ===== Header ===== */
.brand-gradient {
    background: linear-gradient(90deg, var(--brand), #3b2bb0);
}

/* ===== Cards ===== */
.brand-card {
    border: 1px solid rgba(28, 13, 130, 0.1);
    box-shadow: 0 10px 22px rgba(0, 0, 0, 0.06);
    border-radius: 1rem;
}
.brand-text {
    color: var(--brand);
}
.brand-badge {
    background: rgba(28, 13, 130, 0.08);
    color: var(--brand);
    border: 1px solid rgba(28, 13, 130, 0.15);
}

/* ===== Primary button ===== */
.brand-btn {
    background: var(--brand);
    color: #fff;
    border: none;
    box-shadow: 0 8px 18px rgba(28, 13, 130, 0.25);
}
.brand-btn:hover {
    background: var(--brand-700);
    color: #fff;
}

/* ===== Segmented radios ===== */
.segmented {
    display: inline-flex;
    gap: 0;
    background: #fff;
    border: 1px solid rgba(28, 13, 130, 0.18);
    border-radius: 999px;
    overflow: hidden;
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.06);
}
.segmented-input {
    /* hide the native */
    position: absolute;
    opacity: 0;
    pointer-events: none;
}
.segmented-label {
    padding: 10px 16px;
    font-weight: 600;
    cursor: pointer;
    user-select: none;
    color: #3a3f55;
    transition: background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
}
.segmented-label + .segmented-input + .segmented-label {
    border-left: 1px solid rgba(28, 13, 130, 0.12);
}
/* checked state */
.segmented-input:checked + .segmented-label {
    background: var(--brand);
    color: #fff;
    box-shadow: inset 0 0 0 1px var(--brand);
}
/* focus ring for a11y (tabbing) */
.segmented-input:focus-visible + .segmented-label {
    outline: 2px solid var(--brand-300);
    outline-offset: -2px;
    z-index: 1;
}

/* list items polish */
.list-group-item {
    padding: 0.65rem 0;
    border-color: rgba(28, 13, 130, 0.08) !important;
}
</style>
