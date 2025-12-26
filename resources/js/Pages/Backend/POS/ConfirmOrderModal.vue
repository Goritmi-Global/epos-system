<script setup>
import { computed, ref, watch, onMounted } from "vue";
import StripePayment from "./StripePayment.vue";
import SplitPayment from "./SplitPayment.vue";
import { useFormatters } from '@/composables/useFormatters'
import { toast } from "vue3-toastify";
import { usePage } from "@inertiajs/vue3";

const page = usePage();

const { formatMoney, formatCurrencySymbol, formatNumber, dateFmt } = useFormatters()
const props = defineProps({
    show: Boolean,
    customer: String,
    deliveryLocation: String,
    phone: String,
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
    approvedDiscounts: { type: Number, default: 0 },
    approvedDiscountDetails: { type: Array, default: () => [] },

    isPartialPayment: { type: Boolean, default: false },
    isPaymentOnly: { type: Boolean, default: false },
    currentOrderId: { type: [Number, null], default: null },
});

const autoPrintKot = ref(false);
const formErrors = ref({});

// =========================================================
//          Track selected items for partial payment
// =========================================================
const selectedItems = ref([]);
const isPartialPayment = ref(props.isPartialPayment || false);

// Helper to check if item is fully paid
const isItemPaid = (item) => {
    if (String(item.payment_status).toLowerCase() === 'paid') return true;

    // Check if amount_paid covers total item cost
    const price = Number(item.unit_price || item.price) || 0;
    const qty = Number(item.qty || item.quantity) || 0;
    const itemTotal = price * qty;
    const amountPaid = Number(item.amount_paid) || 0;

    // Use a small epsilon for float comparison if needed, but here simple >= should suffice
    return amountPaid >= (itemTotal - 0.01) && itemTotal > 0;
};

const alreadyPaidTotal = computed(() => {
    if (!Array.isArray(props.orderItems)) return 0;
    const sum = props.orderItems.reduce((sum, item) => {
        if (isItemPaid(item)) {
            const p = Number(item.unit_price || item.price) || 0;
            const q = Number(item.qty || item.quantity) || 0;
            return sum + (p * q);
        }
        return sum + (Number(item.amount_paid) || 0);
    }, 0);
    return parseFloat(sum.toFixed(2));
});

// Removed: onMounted pre-selection as requested


const emit = defineEmits([
    "close",
    "confirm",
    "update:cashReceived",
    "pay-item",
    "partial-payment",
    "payment-success"
]);

const paymentMethod = ref("Cash");

const cashReceived = computed({
    get: () => props.cashReceived,
    set: (val) => emit("update:cashReceived", val),
});

const changeAmount = computed(() => {
    if (paymentMethod.value === "Cash") {
        const diff = cashReceived.value - currentPaymentTotal.value;
        return parseFloat(diff.toFixed(2));
    }
    return 0;
});

const currentPaymentTotal = computed(() => {
    const rawItems = Array.isArray(props.orderItems) ? props.orderItems : [];
    let total = 0;

    if (isPartialPayment.value) {
        if (selectedItems.value.length > 0) {
            total = selectedItems.value.reduce((sum, item) => {
                const price = Number(item.unit_price || item.price) || 0;
                const qty = Number(item.qty || item.quantity) || 0;
                const itemTotal = price * qty;
                const paid = Number(item.amount_paid) || 0;
                const remaining = itemTotal - paid;
                return sum + (remaining > 0 ? remaining : 0);
            }, 0);
        }
    } else {
        // Full pay MUST exclude already paid items
        const unpaidSum = rawItems.reduce((sum, item) => {
            if (isItemPaid(item)) return sum;

            const price = Number(item.unit_price || item.price) || 0;
            const qty = Number(item.qty || item.quantity) || 0;
            const itemTotal = price * qty;
            const paid = Number(item.amount_paid) || 0;
            const remaining = itemTotal - paid;
            return sum + (remaining > 0 ? remaining : 0);
        }, 0);

        total = Math.max(0, unpaidSum - (props.promoDiscount || 0));
    }
    return parseFloat(total.toFixed(2));
});

// Auto-sync cash received with total in partial mode
watch(currentPaymentTotal, (newTotal) => {
    if (isPartialPayment.value) {
        cashReceived.value = newTotal;
    }
});

// Watch for changes in orderItems to remove newly paid items from selection
watch(() => props.orderItems, (newItems) => {
    if (newItems && Array.isArray(newItems)) {
        selectedItems.value = selectedItems.value.filter(selected => {
            const currentItem = newItems.find(i => getItemSelectionKey(i) === getItemSelectionKey(selected));
            return currentItem && !isItemPaid(currentItem);
        });
    }
}, { deep: true });

// Watch partial payment toggle to auto-fill initial state
watch(isPartialPayment, (isActive) => {
    if (isActive) {
        // Only CLEAR selection if it's a manual toggle
        // If it was already set (e.g. on mount), we might want to keep it
        // Check if we already have selection
        if (selectedItems.value.length === 0) {
            cashReceived.value = 0;
        }
    } else {
        // Sync with remaining balance when partial is off
        cashReceived.value = currentPaymentTotal.value;
        selectedItems.value = [];
    }
}, { immediate: true });


const handleStripeSuccess = (data) => {
    selectedItems.value = []; // Clear selection for next payment
    emit("payment-success", data);
};

const subTotal = computed(() =>
    Array.isArray(props.orderItems)
        ? props.orderItems.reduce(
            (sum, i) => sum + (Number(i.unit_price || i.price) || 0) * (Number(i.qty || i.quantity) || 0),
            0
        )
        : 0
);

const isLoading = ref(false);

// Helper to check if item is fully paid (Removed duplicate)

// Helper for unique selection key
const getItemSelectionKey = (item) => {
    if (item.server_item_id) return `server-${item.server_item_id}`;
    return `${item.isDeal ? 'deal' : 'prod'}-${item.id}`;
};

// Toggle item selection for partial payment
const toggleItemSelection = (item) => {
    // ULTRA PARANOID: Ensure we can't select paid items
    if (isItemPaid(item)) return;

    const itemKey = getItemSelectionKey(item);
    const index = selectedItems.value.findIndex(i => getItemSelectionKey(i) === itemKey);
    if (index > -1) {
        selectedItems.value.splice(index, 1);
    } else {
        selectedItems.value.push(item);
    }
};

// Check if item is selected
const isItemSelected = (item) => {
    const itemKey = getItemSelectionKey(item);
    return selectedItems.value.some(i => getItemSelectionKey(i) === itemKey);
};

// Enable partial payment mode
const enablePartialPayment = () => {
    isPartialPayment.value = true;
    selectedItems.value = [];
};

// Disable partial payment mode
const disablePartialPayment = () => {
    isPartialPayment.value = false;
    selectedItems.value = [];
};

// Computed ID arrays for both template and logic
const paidItemIds = computed(() => {
    return selectedItems.value
        .filter(item => item.server_item_id)
        .map(item => item.server_item_id);
});

const paidProductIds = computed(() => {
    return selectedItems.value
        .filter(item => !item.server_item_id && !item.isDeal)
        .map(item => item.id);
});

const paidDealIds = computed(() => {
    return selectedItems.value
        .filter(item => !item.server_item_id && item.isDeal)
        .map(item => item.id);
});

const handleOrderAndPrint = async () => {
    autoPrintKot.value = true;
    await handleConfirm();
};

const handleConfirm = async () => {
    formErrors.value.cashReceived = null;

    const paymentTotal = currentPaymentTotal.value;

    if (!props.cashReceived || props.cashReceived <= 0) {
        formErrors.value.cashReceived = "Enter a valid cash amount.";
        toast.error("Enter a valid cash amount.");
        return;
    }

    if (Math.round(props.cashReceived * 100) / 100 < paymentTotal) {
        formErrors.value.cashReceived = "Cash received cannot be less than payment amount.";
        toast.error("Cash received cannot be less than payment amount.");
        return;
    }

    if (isPartialPayment.value && selectedItems.value.length === 0) {
        toast.error("Please select at least one item to pay for.");
        return;
    }

    if (isLoading.value) return;
    isLoading.value = true;

    emit("confirm", {
        paymentMethod: paymentMethod.value,
        cashReceived: cashReceived.value,
        changeAmount: changeAmount.value,
        items: props.orderItems,
        autoPrintKot: autoPrintKot.value,

        // Pass existing order ID
        existingOrderId: props.currentOrderId,

        isPartialPayment: isPartialPayment.value,
        selectedItems: selectedItems.value,

        // Pass both for maximum clarity/compatibility
        selectedItemIds: paidItemIds.value.concat(paidProductIds.value).concat(paidDealIds.value),
        paidItemIds: paidItemIds.value,
        paidProductIds: paidProductIds.value,
        paidDealIds: paidDealIds.value,

        done: () => {
            isLoading.value = false;
        },
    });
};

const availableItems = computed(() => {
    return props.orderItems;
});

const getItemStatusBadge = (item) => {
    if (item.payment_status === 'paid') {
        return { text: 'Paid', class: 'badge bg-success' };
    } else if (item.payment_status === 'unpaid') {
        return { text: 'Unpaid', class: 'badge bg-warning' };
    }
    return null;
};

const formattedOrderType = computed(() => {
    if (!props.orderType) return "";
    return props.orderType
        .replace(/_/g, " ")
        .replace(/\b\w/g, (char) => char.toUpperCase());
});
</script>

<template>
    <div v-if="show" class="modal fade show d-block" tabindex="-1" style="background: rgba(0, 0, 0, 0.5)">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content rounded-4 s border-0">
                <!-- Header -->
                <div class="modal-header brand-gradient text-white py-3 position-sticky top-0 z-3">
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2 m-0">
                        <i class="bi bi-receipt-cutoff"></i>
                        {{ isPartialPayment ? 'Partial Payment' : 'Confirm Order' }}
                    </h5>
                    <button
                        class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                        @click="$emit('close')" aria-label="Close" title="Close">
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
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="fw-bold brand-text m-0 d-flex align-items-center gap-2">
                                                <i class="bi bi-info-circle"></i>
                                                Order Summary
                                            </h6>

                                            <!-- Modern Pill Toggle -->
                                            <div class="mode-toggle-container"
                                                @click="isPartialPayment = !isPartialPayment; isPartialPayment ? enablePartialPayment() : disablePartialPayment()">
                                                <div class="mode-option" :class="{ 'active': !isPartialPayment }">Full
                                                    Pay</div>
                                                <div class="mode-option" :class="{ 'active': isPartialPayment }">Partial
                                                </div>
                                                <div class="mode-slider" :class="{ 'slide-right': isPartialPayment }">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row g-3">
                                            <!-- Customer -->
                                            <div class="col-12 col-sm-6">
                                                <div class="p-3 rounded-3 bg-light d-flex flex-column h-100">
                                                    <span class="text-muted small">Customer</span>
                                                    <span class="fw-semibold">{{ customer || "Walk In" }}</span>
                                                </div>
                                            </div>

                                            <!-- Service -->
                                            <div class="col-12 col-sm-6">
                                                <div class="p-3 rounded-3 bg-light d-flex flex-column h-100">
                                                    <span class="text-muted small">Service</span>
                                                    <span class="fw-semibold">
                                                        <span class="badge brand-badge px-3 py-2 service-badge">
                                                            {{ formattedOrderType }}
                                                        </span>
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Table No (if dine in) -->
                                            <div v-if="orderType === 'Dine_in'" class="col-12 col-sm-6">
                                                <div class="p-3 rounded-3 bg-light d-flex flex-column">
                                                    <span class="text-muted small">Table No</span>
                                                    <span class="fw-semibold">{{ selectedTable?.name || "N/A" }}</span>
                                                </div>
                                            </div>

                                            <!-- Delivery Location (if delivery) -->
                                            <div v-if="orderType === 'Delivery'" class="col-12 col-sm-6">
                                                <div class="p-3 rounded-3 bg-light d-flex flex-column">
                                                    <span class="text-muted small">Delivery Location</span>
                                                    <span class="fw-semibold">{{ deliveryLocation || "N/A" }}</span>
                                                </div>
                                            </div>

                                            <div v-if="orderType === 'Delivery'" class="col-12 col-sm-6">
                                                <div class="p-3 rounded-3 bg-light d-flex flex-column">
                                                    <span class="text-muted small">Phone No</span>
                                                    <span class="fw-semibold">{{ phone || "N/A" }}</span>
                                                </div>
                                            </div>

                                            <!-- Items Table -->
                                            <div class="table-responsive compact-table">
                                                <table class="table align-middle table-hover mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th style="width: 70px;">
                                                                <input v-if="isPartialPayment" type="checkbox"
                                                                    class="form-check-input"
                                                                    @change="$event.target.checked ? (selectedItems = orderItems.filter(i => !isItemPaid(i))) : (selectedItems = [])"
                                                                    :checked="selectedItems.length > 0 && selectedItems.length === orderItems.filter(i => !isItemPaid(i)).length">
                                                                <span v-else class="text-muted small">Status</span>
                                                            </th>
                                                            <th>#</th>
                                                            <th>Item</th>
                                                            <th class="text-center">Qty</th>
                                                            <th class="text-end">Price</th>
                                                            <th class="text-end">Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(item, index) in availableItems"
                                                            :key="getItemSelectionKey(item) || index" :class="{
                                                                'table-primary shadow-sm': isPartialPayment && isItemSelected(item),
                                                                'bg-success-subtle border-success-subtle transition-all duration-300': isItemPaid(item)
                                                            }">
                                                            <td>
                                                                <template v-if="!isItemPaid(item) && isPartialPayment">
                                                                    <input type="checkbox"
                                                                        class="form-check-input cursor-pointer"
                                                                        :checked="isItemSelected(item)"
                                                                        @change="toggleItemSelection(item)">
                                                                </template>
                                                                <div v-else-if="isItemPaid(item)"
                                                                    class="d-flex align-items-center justify-content-center gap-1 text-success px-3 py-1 rounded-pill"
                                                                    style="min-width: 70px; font-weight: 700;">
                                                                    <i class="bi bi-check-circle-fill text-success"></i>
                                                                    <span
                                                                        style="font-size: 0.85rem; color: #198754 !important;">Paid</span>
                                                                </div>
                                                                <div v-else class="text-center">
                                                                    <i class="bi bi-dash text-muted"></i>
                                                                </div>
                                                            </td>

                                                            <td>{{ index + 1 }}</td>

                                                            <td>
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <div class="fw-semibold">{{ item.title }}</div>
                                                                </div>

                                                                <div v-if="item.note" class="small text-muted">
                                                                    Note: {{ item.note }}
                                                                </div>
                                                                <div v-if="item.kitchen_note" class="small text-muted">
                                                                    Kitchen Note: {{ item.kitchen_note }}
                                                                </div>
                                                                <div v-if="item.item_kitchen_note"
                                                                    class="small text-warning fw-semibold">
                                                                    Item Note: {{ item.item_kitchen_note }}
                                                                </div>
                                                            </td>

                                                            <td class="text-center">{{ item.qty || item.quantity }}</td>

                                                            <td class="text-end text-nowrap">
                                                                {{ formatCurrencySymbol(item.unit_price || item.price)
                                                                }}
                                                            </td>

                                                            <td class="text-end fw-bold text-nowrap">
                                                                {{ formatCurrencySymbol((Number(item.unit_price ||
                                                                    item.price) || 0) * (Number(item.qty || item.quantity)
                                                                        || 0)) }}
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                    <tfoot>
                                                        <!-- Already Paid Row (Always visible for context) -->
                                                        <tr v-if="alreadyPaidTotal > 0" class="">
                                                            <td colspan="5"
                                                                class="text-end text-muted small fw-semibold">
                                                                Already Paid
                                                            </td>
                                                            <td class="text-end text-success fw-bold">
                                                                -{{ formatCurrencySymbol(alreadyPaidTotal) }}
                                                            </td>
                                                        </tr>

                                                        <!-- Show selected items subtotal in partial payment mode -->
                                                        <tr v-if="isPartialPayment && selectedItems.length > 0"
                                                            class="table-success">
                                                            <td colspan="5" class="text-end fw-semibold">
                                                                Selected to Pay ({{ selectedItems.length }} items)
                                                            </td>
                                                            <td class="text-end fw-bold text-primary">
                                                                {{ formatCurrencySymbol(currentPaymentTotal) }}
                                                            </td>
                                                        </tr>

                                                        <tr v-if="promoDiscount > 0 && !isPartialPayment">
                                                            <td colspan="5" class="text-end text-muted">
                                                                Promo <span v-if="promoName">({{ promoName }})</span>
                                                            </td>
                                                            <td class="text-end text-success">
                                                                -{{ formatCurrencySymbol(promoDiscount) }}
                                                            </td>
                                                        </tr>

                                                        <tr class="fw-bold border-top-0">
                                                            <td colspan="5" class="text-end py-2">
                                                                {{ isPartialPayment ? 'Amount to Pay' :
                                                                    'Remaining Balance' }}
                                                            </td>
                                                            <td class="text-end text-success fs-5 py-2">
                                                                {{ formatCurrencySymbol(currentPaymentTotal) }}
                                                            </td>
                                                        </tr>

                                                        <!-- Order Grand Total display for context -->
                                                        <tr v-if="isPartialPayment"
                                                            class="border-top-0 bg-light-subtle">
                                                            <td :colspan="5"
                                                                class="text-end text-muted py-2 fw-semibold"
                                                                style="font-size: 0.85rem;">
                                                                <i class="bi bi-cart-check me-1"></i> Full Order Total
                                                            </td>
                                                            <td class="text-end text-muted py-2 fw-bold"
                                                                style="font-size: 0.85rem;">
                                                                {{ formatCurrencySymbol(grandTotal - (promoDiscount ||
                                                                    0)) }}
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

                                            <template v-if="page.props?.onboarding?.payment_methods?.card_enabled">
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
                                            </template>
                                        </div>

                                        <!-- Cash Payment -->
                                        <div v-if="paymentMethod === 'Cash'" class="pt-2 border-top">
                                            <div class="pay-header d-flex align-items-center gap-2 mb-3">
                                                <i class="bi bi-credit-card-2-front-fill"></i>
                                                <h6 class="m-0">Cash Received</h6>
                                            </div>

                                            <input type="number" v-model.number="cashReceived"
                                                class="form-control rounded-3"
                                                :class="{ 'is-invalid': formErrors.cashReceived }" />

                                            <small v-if="formErrors.cashReceived" class="text-danger">
                                                {{ formErrors.cashReceived }}
                                            </small>

                                            <div class="mt-2">
                                                <strong>Change:</strong>
                                                <span
                                                    :class="changeAmount < 0 ? 'text-danger fw-bold' : 'text-success fw-bold'">
                                                    {{ formatCurrencySymbol(changeAmount) }}
                                                </span>
                                            </div>

                                            <div class="d-flex gap-2 mt-3">
                                                <button class="btn btn-secondary btn-sm rounded-pill px-3 py-2"
                                                    @click="$emit('close')">
                                                    Cancel
                                                </button>

                                                <button
                                                    class="btn btn-primary brand-btn rounded-pill btn-sm px-2 py-2 d-flex align-items-center gap-2"
                                                    @click="handleConfirm"
                                                    :disabled="isLoading || (isPartialPayment && selectedItems.length === 0)">
                                                    <span v-if="isLoading" class="spinner-border spinner-border-sm"
                                                        role="status" aria-hidden="true"></span>
                                                    <i v-else class="bi bi-check2-circle"></i>
                                                    <span>{{ isLoading ? 'Processing...' : (isPartialPayment ?
                                                        (selectedItems.length > 0 ? 'Pay Selected' : 'Select Items') :
                                                        'Confirm') }}</span>
                                                </button>

                                                <button v-if="!isPartialPayment"
                                                    class="btn btn-primary rounded-pill btn-sm d-flex align-items-center justify-content-center text-nowrap"
                                                    style="font-size: 0.75rem; padding: 6px 10px;"
                                                    @click="handleOrderAndPrint" :disabled="isLoading">
                                                    Order &amp; Print
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Card Payment -->
                                        <div v-if="paymentMethod === 'Card'" class="pt-3 border-top">
                                            <StripePayment :order_code="order_code" :show="show" :customer="customer"
                                                :phone="phone" :delivery-location="deliveryLocation"
                                                :orderType="orderType" :selectedTable="selectedTable"
                                                :orderItems="orderItems" :grandTotal="currentPaymentTotal"
                                                :money="money" :cashReceived="cashReceived" :subTotal="subTotal"
                                                :tax="tax ?? 0" :serviceCharges="serviceCharges ?? 0"
                                                :deliveryCharges="deliveryCharges ?? 0" :note="note"
                                                :kitchen-note="kitchenNote"
                                                :orderDate="orderDate ?? new Date().toISOString().split('T')[0]"
                                                :orderTime="orderTime ?? new Date().toTimeString().split(' ')[0]"
                                                :payment-method="paymentMethod" :change="changeAmount"
                                                :promo-discount="promoDiscount" :promo-id="promoId"
                                                :promo-name="promoName" :promo-type="promoType"
                                                :promo-discount-amount="promoDiscountAmount"
                                                :applied-promos="appliedPromos" :approved-discounts="approvedDiscounts"
                                                :approved-discount-details="approvedDiscountDetails"
                                                :current-order-id="currentOrderId"
                                                :is-partial-payment="isPartialPayment" :paid-item-ids="paidItemIds"
                                                :paid-product-ids="paidProductIds" :paid-deal-ids="paidDealIds"
                                                :selected-item-ids="selectedItems.map(i => i.server_item_id || i.id)"
                                                @payment-success="handleStripeSuccess"
                                                @payment-error="handleStripeError" />
                                        </div>

                                        <!-- Split Payment -->
                                        <div v-if="paymentMethod === 'Split'" class="pt-3 border-top">
                                            <SplitPayment :order_code="order_code" :show="show" :customer="customer"
                                                :phone="phone" :delivery-location="deliveryLocation"
                                                :orderType="orderType" :selectedTable="selectedTable"
                                                :orderItems="orderItems" :grandTotal="currentPaymentTotal"
                                                :money="money" :cashReceived="cashReceived" :subTotal="subTotal"
                                                :tax="tax ?? 0" :serviceCharges="serviceCharges ?? 0"
                                                :deliveryCharges="deliveryCharges ?? 0" :note="note"
                                                :kitchen-note="kitchenNote"
                                                :orderDate="orderDate ?? new Date().toISOString().split('T')[0]"
                                                :orderTime="orderTime ?? new Date().toTimeString().split(' ')[0]"
                                                :payment-method="paymentMethod" :change="changeAmount"
                                                :payment-type="'split'" :promo-discount="promoDiscount"
                                                :promo-id="promoId" :promo-name="promoName" :promo-type="promoType"
                                                :promo-discount-amount="promoDiscountAmount"
                                                :applied-promos="appliedPromos" :approved-discounts="approvedDiscounts"
                                                :approved-discount-details="approvedDiscountDetails"
                                                :current-order-id="currentOrderId"
                                                :is-partial-payment="isPartialPayment" :paid-item-ids="paidItemIds"
                                                :paid-product-ids="paidProductIds" :paid-deal-ids="paidDealIds"
                                                :selected-item-ids="selectedItems.map(i => i.server_item_id || i.id)"
                                                @confirm="(data) => $emit('confirm', data)"
                                                @payment-success="handleStripeSuccess" />
                                        </div>

                                        <div class="mt-auto small text-muted pt-2">
                                            <i class="bi bi-shield-check me-1"></i>
                                            Secured checkout • Keep this window open until payment completes
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
:root {
    --brand: #1c0d82;
    --brand-700: #150a6a;
    --brand-300: #4b3bb0;
}

.brand-gradient {
    background: #1b2850;
}

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

.brand-btn {
    background: var(--brand);
    color: #fff;
    border: none;
}

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
    color: #fff !important;
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

.segmented-input:checked+.segmented-label {
    background: var(--brand);
    color: #fff;
    box-shadow: inset 0 0 0 1px var(--brand);
}

.dark .segmented-input:checked+.segmented-label {
    background: #1c0d82 !important;
    color: #fff !important;
    box-shadow: none !important;
}

/* Modern Pill Toggle Design */
.mode-toggle-container {
    position: relative;
    display: flex;
    background: #f1f3f5;
    border-radius: 50px;
    padding: 4px;
    width: 160px;
    cursor: pointer;
    border: 1px solid #dee2e6;
    transition: all 0.3s ease;
    user-select: none;
}

.mode-option {
    flex: 1;
    text-align: center;
    padding: 6px 0;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    z-index: 2;
    color: #6c757d;
    transition: color 0.3s ease;
}

.mode-option.active {
    color: #fff;
}

.mode-slider {
    position: absolute;
    top: 4px;
    left: 4px;
    width: calc(50% - 4px);
    height: calc(100% - 8px);
    background: #495057;
    /* Default non-active color */
    border-radius: 50px;
    z-index: 1;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.mode-toggle-container:has(.mode-option.active:nth-child(2)) .mode-slider {
    background: var(--brand);
}

.mode-slider.slide-right {
    transform: translateX(100%);
    background: var(--brand) !important;
}

/* Dark Mode Toggle */
.dark .mode-toggle-container {
    background: #2d2d2d;
    border-color: #444;
}

.dark .mode-option {
    color: #adb5bd;
}

.dark .mode-slider {
    background: #444;
}

.dark .mode-slider.slide-right {
    background: var(--brand) !important;
}

.compact-table table {
    font-size: 0.95rem;
}

.form-check-input:checked {
    background-color: #1b1670;
    border-color: #1b1670;
}

.body-scroll {
    max-height: calc(90vh - 64px);
    overflow: auto;
}

/* Highlight selected rows */
.table-primary {
    background-color: rgba(28, 13, 130, 0.1) !important;
}

.dark .table-primary {
    background-color: rgba(28, 13, 130, 0.2) !important;
}

/* Updated: Light Green */
.paid-row {
    background-color: #d1e7dd !important;
    color: #0f5132 !important;
}

.grayscale {
    filter: opacity(0.7);
}

.cursor-pointer {
    cursor: pointer;
}

.hover-shadow:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.transition-all {
    transition: all 0.25s ease-in-out;
}

.bg-primary-subtle {
    background-color: rgba(28, 13, 130, 0.05) !important;
}

/* Custom Switch Styling */
.custom-switch {
    width: 2.5em !important;
    height: 1.25em !important;
    cursor: pointer;
}

.custom-switch:checked {
    background-color: var(--brand) !important;
    border-color: var(--brand) !important;
}

.custom-switch:focus {
    box-shadow: 0 0 0 0.25rem rgba(28, 13, 130, 0.15) !important;
}

.dark .bg-light {
    background-color: #212121 !important;
}

.dark .service-badge {
    border: 1px solid #fff !important;
    border-radius: 5px !important;
    font-size: 0.75rem !important;
}
</style>