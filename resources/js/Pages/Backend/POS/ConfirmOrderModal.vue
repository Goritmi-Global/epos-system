<script setup>
import { computed, ref } from "vue";
import StripePayment from "./StripePayment.vue";
import SplitPayment from "./SplitPayment.vue";
import { useFormatters } from '@/composables/useFormatters'
import { toast } from "vue3-toastify";

const { formatMoney, formatCurrencySymbol, formatNumber, dateFmt } = useFormatters()
const props = defineProps({
    show: Boolean,
    customer: String,
    orderType: String,
    selectedTable: Object,
    orderItems: Array,
    grandTotal: Number,
    money: Function,
    cashReceived: Number,
    order_code: String,
    subTotal: Number,
    tax: Number,
    serviceCharges: Number,
    deliveryCharges: Number,
    note: [String, null],
    kitchenNote: [String, null],
    orderDate: String,
    orderTime: String,
    paymentMethod: String,
    change: Number,
    promoDiscount: { type: Number, default: 0 },
    promoId: { type: [Number, String, null], default: null },
    promoName: { type: [String, null], default: null },
    promoType: { type: [String, null], default: null },
    promoDiscountAmount: { type: Number, default: 0 },
    appliedPromos: { type: Array, default: () => [] },
});
const autoPrintKot = ref(false);
const formErrors = ref({});


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

// Subtotal (since you pass :subTotal to StripePayment)
// const subTotal = computed(() =>
//     Array.isArray(props.orderItems)
//         ? props.orderItems.reduce(
//             (sum, i) => sum + (Number(i.price) || 0) * (Number(i.qty) || 0),
//             0
//         )
//         : 0
// );

const subTotal = computed(() =>
    Array.isArray(props.orderItems)
        ? props.orderItems.reduce(
            (sum, i) => sum + (Number(i.unit_price) || 0) * (Number(i.qty) || 0),
            0
        )
        : 0
);

const isLoading = ref(false);

const handleConfirm = async () => {

    formErrors.value.cashReceived = null;

    if (!props.cashReceived || props.cashReceived <= 0) {
        formErrors.value.cashReceived = "Enter a valid cash amount.";
        toast.error("Enter a valid cash amount.");
        return;
    }
    if (props.cashReceived < props.grandTotal) {
        formErrors.value.cashReceived = "Cash received cannot be less than total amount.";
        toast.error("Cash received cannot be less than total amount.");
        return;
    }
    // prevent multiple clicks
    if (isLoading.value) return;

    isLoading.value = true;

    // emit the event
    emit("confirm", {
        paymentMethod: paymentMethod.value,
        cashReceived: cashReceived.value,
        changeAmount: changeAmount.value,
        items: props.orderItems,
        autoPrintKot: autoPrintKot.value,
        done: () => {
            // parent can call this to stop loader
            isLoading.value = false;
        },
    });
};


const formattedOrderType = computed(() => {
    if (!props.orderType) return "";
    return props.orderType
        .replace(/_/g, " ")
        .replace(/\b\w/g, (char) => char.toUpperCase());
});

// Payment confirm handler
function handleSplitConfirm(payload) {
    // bubble up to the parent exactly like other flows
    // payload = { paymentMethod: 'Split', cashReceived, cardAmount, changeAmount: 0 }
    // You can also create the order here if that’s your pattern.
    // Keep your original event name:
    emit("confirm", payload);
}
function handleCardConfirm(payload) {
    // bubble up to the parent exactly like other flows
    // payload = { paymentMethod: 'Split', cashReceived, cardAmount, changeAmount: 0 }
    // You can also create the order here if that’s your pattern.
    // Keep your original event name:
    emit("confirm", payload);
}
</script>

<template>
    <div v-if="show" class="modal fade show d-block" tabindex="-1" style="background: rgba(0, 0, 0, 0.5)">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 s border-0">
                <!-- Header -->
                <div class="modal-header brand-gradient text-white py-3 position-sticky top-0 z-3">
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2 m-0">
                        <i class="bi bi-receipt-cutoff"></i>
                        Confirm Order
                    </h5>

                    <button
                        class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                        @click="$emit('close')" data-bs-dismiss="modal" aria-label="Close" title="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                </div>

                <!-- Body -->
                <div class="modal-body p-3 p-md-4 body-scroll">
                    <div class="row g-3">
                        <!-- ===== Left 6: Order Info ===== -->
                        <div class="col-12 col-md-6 d-flex">
                            <div class="w-100 d-flex flex-column gap-3">
                                <!-- Order Summary -->
                                <div class="card border-0 brand-card h-100">
                                    <div class="card-body">
                                        <h6 class="fw-bold brand-text mb-3 d-flex align-items-center gap-2">
                                            <i class="bi bi-info-circle"></i>
                                            Order Summary
                                        </h6>

                                        <div class="row g-3">
                                            <!-- Customer -->
                                            <div class="col-12 col-sm-6">
                                                <div class="p-3 rounded-3 bg-light d-flex flex-column h-100">
                                                    <span class="text-muted small">Customer</span>
                                                    <span class="fw-semibold">{{
                                                        customer || "Walk In"
                                                        }}</span>
                                                </div>
                                            </div>

                                            <!-- Service -->
                                            <div class="col-12 col-sm-6">
                                                <div class="p-3 rounded-3 bg-light d-flex flex-column h-100">
                                                    <span class="text-muted small">Service</span>
                                                    <span class="fw-semibold">
                                                        <span class="badge brand-badge px-3 py-2">
                                                            {{
                                                                formattedOrderType
                                                            }}
                                                        </span>
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Table No (if dine in) -->
                                            <div v-if="orderType === 'Dine_in'" class="col-12 col-sm-6">
                                                <div class="p-3 rounded-3 bg-light d-flex flex-column">
                                                    <span class="text-muted small">Table No</span>
                                                    <span class="fw-semibold">{{
                                                        selectedTable?.name ||
                                                        "N/A"
                                                        }}</span>
                                                </div>
                                            </div>

                                            <div class="table-responsive compact-table">
                                                <table class="table align-middle table-hover mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Item</th>
                                                            <th class="text-center">
                                                                Qty
                                                            </th>
                                                            <th class="text-end">
                                                                Price
                                                            </th>
                                                            <th class="text-end">
                                                                Total
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(item, index) in orderItems" :key="item.id || index
                                                            ">
                                                            <td>
                                                                {{ index + 1 }}
                                                            </td>
                                                            <td>
                                                                <div class="fw-semibold">
                                                                    {{
                                                                        item.title
                                                                    }}
                                                                </div>
                                                                <div v-if="
                                                                    item.note
                                                                " class="small text-muted">
                                                                    Note:
                                                                    {{
                                                                        item.note
                                                                    }}
                                                                </div>
                                                                <div v-if="
                                                                    item.kitchen_note
                                                                " class="small text-muted">
                                                                    Kitchen Note:
                                                                    {{
                                                                        item.kitchen_note
                                                                    }}
                                                                </div>
                                                                <div v-if="item.item_kitchen_note"
                                                                    class="small text-warning fw-semibold">
                                                                    🍳 Item Note: {{ item.item_kitchen_note }}
                                                                </div>
                                                            </td>
                                                            <td class="text-center">
                                                                {{ item.qty }}
                                                            </td>
                                                            <td class="text-end">
                                                                {{
                                                                    formatCurrencySymbol(
                                                                        item.unit_price
                                                                    )
                                                                }}
                                                            </td>
                                                            <td class="text-end fw-bold">
                                                                {{
                                                                    formatCurrencySymbol(
                                                                        (Number(
                                                                            item.unit_price
                                                                        ) ||
                                                                            0) *
                                                                        (Number(
                                                                            item.qty
                                                                        ) ||
                                                                            0)
                                                                    )
                                                                }}
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                    <tfoot>

                                                        <tr v-if="promoDiscount > 0">
                                                            <td colspan="4" class="text-end text-muted">
                                                                Promo <span v-if="promoName">({{ promoName }})</span>
                                                            </td>
                                                            <td class="text-end text-success">
                                                                -{{ formatCurrencySymbol(promoDiscount) }}
                                                            </td>
                                                        </tr>

                                                        <tr class="fw-bold">
                                                            <td colspan="4" class="text-end">
                                                                Grand Total
                                                            </td>
                                                            <td class="text-end text-success">
                                                                {{ formatCurrencySymbol(grandTotal) }}
                                                            </td>
                                                        </tr>
                                                    </tfoot>

                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ===== Right 6: Payment Options ===== -->
                        <div class="col-12 col-md-6 d-flex">
                            <div class="w-100">
                                <div class="card border-0 brand-card h-100">
                                    <div class="card-body d-flex flex-column">
                                        <!-- Segmented radios -->

                                        <div class="segmented mt-1 mb-3" role="tablist" aria-label="Payment method">
                                            <input class="segmented-input" type="radio" id="paymentCash"
                                                :name="'payMethod'" value="Cash" v-model="paymentMethod" />
                                            <label class="segmented-label" for="paymentCash">
                                                <i class="bi bi-cash-coin me-1"></i>
                                                Cash
                                            </label>

                                            <input class="segmented-input" type="radio" id="paymentCard"
                                                :name="'payMethod'" value="Card" v-model="paymentMethod" />
                                            <label class="segmented-label" for="paymentCard">
                                                <i class="bi bi-credit-card-2-front me-1"></i>
                                                Card
                                            </label>

                                            <input class="segmented-input" type="radio" id="paymentSplit"
                                                :name="'payMethod'" value="Split" v-model="paymentMethod" />
                                            <label class="segmented-label" for="paymentSplit">
                                                <i class="bi bi-columns-gap me-1"></i>
                                                Split
                                            </label>
                                        </div>

                                        <!-- Cash -->
                                        <div v-if="paymentMethod === 'Cash'" class="pt-2 border-top">
                                            <div class="pay-header d-flex align-items-center gap-2 mb-3">
                                                <i class="bi bi-credit-card-2-front-fill"></i>
                                                <h6 class="m-0">
                                                    Cash Received
                                                </h6>
                                            </div>
                                            <input type="number" v-model.number="cashReceived"
                                                class="form-control rounded-3" :class="{
                                                    'is-invalid':
                                                        formErrors.cashReceived,
                                                }" />

                                            <small v-if="formErrors.cashReceived" class="text-danger">
                                                {{ formErrors.cashReceived }}
                                            </small>
                                            <div class="mt-2">
                                                <strong>Change:</strong>
                                                <span :class="[changeAmount] < 0
                                                    ? 'text-danger fw-bold'
                                                    : 'text-success fw-bold'
                                                    ">
                                                    {{ formatCurrencySymbol(changeAmount) }}
                                                </span>
                                            </div>
                                            <!-- Auto Print KOT CheckBox -->
                                            <div class="form-check mt-3">
                                                <input class="form-check-input" type="checkbox" id="autoPrintKot"
                                                    v-model="autoPrintKot" />
                                                <label class="form-check-label" for="autoPrintKot">
                                                    Auto Print KOT
                                                </label>
                                            </div>


                                            <div class="d-flex gap-2 mt-3">
                                                <button class="btn btn-secondary btn-sm rounded-pill px-3 py-2"
                                                    @click="$emit('close')">
                                                    Cancel
                                                </button>
                                                <button
                                                    class="btn btn-primary brand-btn rounded-pill btn-sm px-3 py-2 d-flex align-items-center gap-2"
                                                    @click="handleConfirm" :disabled="isLoading">
                                                    <span v-if="isLoading" class="spinner-border spinner-border-sm"
                                                        role="status" aria-hidden="true"></span>
                                                    <i v-else class="bi bi-check2-circle"></i>
                                                    <span>{{ isLoading ? 'Processing...' : 'Confirm & Place' }}</span>
                                                </button>

                                            </div>
                                        </div>

                                        <!-- Card -->
                                        <div v-if="paymentMethod === 'Card'" class="pt-3 border-top">
                                            <StripePayment :order_code="order_code" :show="show" :customer="customer"
                                                :orderType="orderType" :selectedTable="selectedTable"
                                                :orderItems="orderItems" :grandTotal="grandTotal" :money="money"
                                                :cashReceived="cashReceived" :subTotal="subTotal" :tax="tax ?? 0"
                                                :serviceCharges="serviceCharges ?? 0"
                                                :deliveryCharges="deliveryCharges ?? 0" :note="note"
                                                :kitchen-note="kitchenNote"
                                                :orderDate="orderDate ?? new Date().toISOString().split('T')[0]"
                                                :orderTime="orderTime ?? new Date().toTimeString().split(' ')[0]"
                                                :paymentMethod="paymentMethod" :change="changeAmount"
                                                :promo-discount="promoDiscount" :promo-id="promoId"
                                                :promo-name="promoName" :promo-type="promoType"
                                                :promo-discount-amount="promoDiscountAmount"
                                                :applied-promos="appliedPromos" />

                                            <div class="mt-2">
                                                <strong>Change:</strong>
                                                <span :class="changeAmount < 0
                                                    ? 'text-danger fw-bold'
                                                    : 'text-success fw-bold'
                                                    ">
                                                    {{ formatCurrencySymbol(changeAmount) }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Split (placeholder to be wired up later if needed) -->
                                        <div v-if="paymentMethod === 'Split'" class="pt-3 border-top">
                                            <!-- <SplitPayment
                                                :total="grandTotal"
                                                 
                                                :order_code="order_code"
                                                :customer="customer"
                                                :orderType="orderType"
                                                :selectedTable="selectedTable"
                                                :orderItems="orderItems"
                                                :money="money"
                                                :note="note"
                                                :orderDate="orderDate"
                                                :orderTime="orderTime"
                                                :tax="tax ?? 0"
                                                :serviceCharges="
                                                    serviceCharges ?? 0
                                                "
                                                :deliveryCharges="
                                                    deliveryCharges ?? 0
                                                "
                                                @confirm="handleSplitConfirm"
                                            /> -->

                                            <SplitPayment :order_code="order_code" :show="show" :customer="customer"
                                                :orderType="orderType" :selectedTable="selectedTable"
                                                :orderItems="orderItems" :grandTotal="grandTotal" :money="money"
                                                :cashReceived="cashReceived" :subTotal="subTotal" :tax="tax ?? 0"
                                                :serviceCharges="serviceCharges ?? 0"
                                                :deliveryCharges="deliveryCharges ?? 0" :note="note"
                                                :kitchen-note="kitchenNote"
                                                :orderDate="orderDate ?? new Date().toISOString().split('T')[0]"
                                                :orderTime="orderTime ?? new Date().toTimeString().split(' ')[0]"
                                                :paymentMethod="paymentMethod" :change="changeAmount"
                                                :paymentType="paymentMethod" :promo-discount="promoDiscount"
                                                :promo-id="promoId" :promo-name="promoName" :promo-type="promoType"
                                                :promo-discount-amount="promoDiscountAmount"
                                                :applied-promos="appliedPromos" />
                                        </div>

                                        <div class="mt-auto small text-muted pt-2">
                                            <i class="bi bi-shield-check me-1"></i>
                                            Secured checkout • Keep this window
                                            open until payment completes
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /Right -->
                    </div>
                </div>
                <!-- /Body -->
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
    background: #1b2850;
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

.dark .brand-badge {
    background-color: #212121 !important;
    border: 1px solid #fff !important;
}

/* ===== Primary button ===== */
.brand-btn {
    background: var(--brand);
    color: #fff;
    border: none;
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

.dark .segmented {
    display: inline-flex;
    gap: 0;
    background: #212121 !important;
    border: 1px solid rgba(28, 13, 130, 0.18);
    border-radius: 999px;
    overflow: hidden;
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.06);
}

.segmented-input {
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

.dark .segmented-label {
    padding: 10px 16px;
    font-weight: 600;
    cursor: pointer;
    user-select: none;
    color: #fff !important;
    transition: background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
}

.segmented-label+.segmented-input+.segmented-label {
    border-left: 1px solid rgba(28, 13, 130, 0.12);
}

.segmented-input:checked+.segmented-label {
    background: var(--brand);
    color: #fff;
    box-shadow: inset 0 0 0 1px var(--brand);
}

.dark .segmented-input:checked+.segmented-label {
    background: #1C0D82 !important;
    color: #fff;
    box-shadow: none;

}

.dark .bg-light {
    background-color: #212121 !important;
    color: #fff !important;
}


.segmented-input:focus-visible+.segmented-label {
    outline: 2px solid var(--brand-300);
    outline-offset: -2px;
    z-index: 1;
}

/* ===== Compact table ===== */
.compact-table table {
    font-size: 0.95rem;
}

.compact-table th,
.compact-table td {
    white-space: nowrap;
}

.form-check-input:checked {
    background-color: #1b1670;
    border-color: #1b1670;
}


/* ===== Body scroll to keep within one screen ===== */
.body-scroll {
    max-height: calc(90vh - 64px);
    /* header ~64px */
    overflow: auto;
}
</style>
