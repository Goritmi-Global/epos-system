<script setup>
import { toast } from "vue3-toastify";
import Select from "primevue/select";
import { ref, computed, watch, onMounted } from "vue";
import { useFormatters } from '@/composables/useFormatters'

const { formatMoney, formatCurrencySymbol, formatNumber, dateFmt } = useFormatters()

const props = defineProps({
    suppliers: {
        type: Array,
        required: true,
    },
    items: {
        type: Array,
        required: true,
    },
});
const emit = defineEmits(["refresh-data"]);

const activeTab = ref("single");

const o_search = ref("");
const p_supplier = ref(null);
const p_submitting = ref(false);
const formErrors = ref({});
const p_search = ref("");
const o_submitting = ref(false);
const derivedUnitsCache = ref({});

const m_supplier = ref(null);
const m_submitting = ref(false);
const m_formErrors = ref({});
const multipleOrderItems = ref([]);


watch(
    () => props.items,
    (newItems) => {
        multipleOrderItems.value = newItems.map((it) => ({
            ...it,
            qty: 0,
            unitPrice: 0,
            expiry: null,
            subtotal: 0,
            supplier_id: it.preferred_supplier_id || it.supplier_id || null,
            selected_derived_unit_id: null,
            derived_units: [],
        }));
    },
    { immediate: true }
);


onMounted(() => {
    feather.replace();
    const addOrderModal = document.getElementById("addOrderModal");
    if (addOrderModal) {
        addOrderModal.addEventListener("show.bs.modal", () => {
            activeTab.value = "single"; // Reset to single order tab
        });
    }
});

const filteredItems = computed(() => {
    const term = o_search.value.trim().toLowerCase();

    if (!term) {
        props.items.forEach(item => {
            if (!('qty' in item)) item.qty = 0;
            if (!('unitPrice' in item)) item.unitPrice = 0;
            if (!('expiry' in item)) item.expiry = null;
            if (!('subtotal' in item)) item.subtotal = 0;
            if (!('selected_derived_unit_id' in item)) item.selected_derived_unit_id = null;
            if (!('selectedDerivedUnitInfo' in item)) item.selectedDerivedUnitInfo = null;
            if (!('derived_units' in item)) item.derived_units = [];
        });
        return props.items;
    }

    return props.items.filter((i) => {
        if (!('qty' in i)) i.qty = 0;
        if (!('unitPrice' in i)) i.unitPrice = 0;
        if (!('expiry' in i)) i.expiry = null;
        if (!('subtotal' in i)) i.subtotal = 0;
        if (!('selected_derived_unit_id' in i)) i.selected_derived_unit_id = null;
        if (!('selectedDerivedUnitInfo' in i)) i.selectedDerivedUnitInfo = null;
        if (!('derived_units' in i)) i.derived_units = [];

        return [
            i.name,
            i.category?.name ?? "",
            i.unit_name ?? "",
            String(i.stock ?? "")
        ]
            .join(" ")
            .toLowerCase()
            .includes(term);
    });
});



function updateSubtotal(it) {
    let qty = Number(it.qty) || 0;
    const price = Number(it.unitPrice) || 0;
    if (it.selected_derived_unit_id && it.selectedDerivedUnitInfo) {
        const conversionFactor = Number(it.selectedDerivedUnitInfo.conversion_factor) || 1;
        qty = qty;
    }

    it.subtotal = qty * price;
}

function handleQtyInput(it) {
    if (it.qty < 0) {
        it.qty = 0;
    }
    updateSubtotal(it);
}

function handlePriceInput(it) {
    if (it.unitPrice < 0) {
        it.unitPrice = 0;
    }
    updateSubtotal(it);
}

function updateMultipleSubtotal(it) {
    let qty = Number(it.qty) || 0;
    const price = Number(it.unitPrice) || 0;

    if (it.selected_derived_unit_id && it.selectedDerivedUnitInfo) {
        const conversionFactor = Number(it.selectedDerivedUnitInfo.conversion_factor) || 1;
        qty = qty;
    }

    it.subtotal = qty * price;
}

function clearRow(it) {
    it.qty = 0;
    it.unitPrice = 0;
    it.expiry = null;
    it.subtotal = 0;
    it.selected_derived_unit_id = null;
    it.selectedDerivedUnitInfo = null;
    if (it.derived_units) {
        it.derived_units = [];
    }
    clearItemErrors(it);
}


function clearMultipleRow(it) {
    it.qty = 0;
    it.unitPrice = 0;
    it.expiry = null;
    it.subtotal = 0;
    it.supplier_id = null;
    it.selected_derived_unit_id = null;
    it.selectedDerivedUnitInfo = null;
    if (it.derived_units) {
        it.derived_units = [];
    }
    clearMultipleItemErrors(it);
}

const p_cart = ref([]);
const resteErrors = () => {
    formErrors.value = {};
    filteredItems.value.forEach(item => {
        item.selectedDerivedUnitInfo = null;
        item.selected_derived_unit_id = null;
    });

    // also clear cart if you need
    p_cart.value.forEach(item => {
        item.selectedDerivedUnitInfo = null;
        item.selected_derived_unit_id = null;
    });
};

const resetMultipleErrors = () => {
    m_formErrors.value = {};
    multipleOrderItems.value.forEach(item => {
        item.selectedDerivedUnitInfo = null;
        item.selected_derived_unit_id = null;
    });
};

const money = (n, currency = "GBP") =>
    new Intl.NumberFormat("en-GB", { style: "currency", currency }).format(
        Number(n || 0)
    );
const round2 = (n) => Math.round((Number(n) || 0) * 100) / 100;

const o_cart = ref([]);
const o_total = computed(() =>
    round2(filteredItems.value.reduce((s, it) => s + Number(it.subtotal || 0), 0))
);

const m_total = computed(() =>
    round2(multipleOrderItems.value.reduce((s, it) => s + Number(it.subtotal || 0), 0))
);


const setItemError = (item, field, message) => {
    if (!formErrors.value[item.id]) formErrors.value[item.id] = {};
    formErrors.value[item.id][field] = [message];
};

const setMultipleItemError = (item, field, message) => {
    if (!m_formErrors.value[item.id]) m_formErrors.value[item.id] = {};
    m_formErrors.value[item.id][field] = [message];
};

async function loadDerivedUnits(item) {
    try {
        const baseUnitId = item.unit_id ?? item.unitId ?? null;

        if (!baseUnitId) {
            item.derived_units = [];
            return;
        }

        // return cached if present
        if (derivedUnitsCache.value[baseUnitId]) {
            item.derived_units = derivedUnitsCache.value[baseUnitId];
            return;
        }
        const res = await axios.get("/units", { params: { per_page: 200 } });
        const list = res.data?.data ?? res.data ?? [];
        const derived = list.filter(
            (u) => u.base_unit_id !== null && Number(u.base_unit_id) === Number(baseUnitId)
        );
        derivedUnitsCache.value[baseUnitId] = derived;
        item.derived_units = derived;
    } catch (err) {
        console.error("loadDerivedUnits error:", err);
        item.derived_units = [];
    }
}
function onUnitChange(it) {
    if (!it.selected_derived_unit_id) {
        it.selectedDerivedUnitInfo = null 
    } else {
        it.selectedDerivedUnitInfo = it.derived_units.find(
            du => du.id === it.selected_derived_unit_id
        )
    }
    updateSubtotal(it);
}

function onMultipleUnitChange(it) {
    if (!it.selected_derived_unit_id) {
        it.selectedDerivedUnitInfo = null
    } else {
        it.selectedDerivedUnitInfo = it.derived_units.find(
            du => du.id === it.selected_derived_unit_id
        )
    }
    updateMultipleSubtotal(it);
}

const clearItemErrors = (item, field = null) => {
    if (!formErrors.value) return;
    if (!item || !item.id) return;
    if (field) {
        if (formErrors.value[item.id]) {
            delete formErrors.value[item.id][field];
            if (Object.keys(formErrors.value[item.id]).length === 0) {
                delete formErrors.value[item.id];
            }
        }
    } else {
        delete formErrors.value[item.id];
    }
};

const clearMultipleItemErrors = (item, field = null) => {
    if (!m_formErrors.value) return;
    if (!item || !item.id) return;
    if (field) {
        if (m_formErrors.value[item.id]) {
            delete m_formErrors.value[item.id][field];
            if (Object.keys(m_formErrors.value[item.id]).length === 0) {
                delete m_formErrors.value[item.id];
            }
        }
    } else {
        delete m_formErrors.value[item.id];
    }
};

async function addOrderItem(item) {
    clearItemErrors(item);

    const inputQty = Number(item.qty || 0);

    // ensure qty entered
    if (!inputQty || inputQty <= 0) {
        setItemError(item, "qty", "Enter a valid quantity.");
        toast.error("Enter a valid quantity.");
        return;
    }
    let qty = inputQty;
    let selectedDerived = null;
    let conversionFactor = 1;

    if (item.selected_derived_unit_id) {
        await loadDerivedUnits(item);
        selectedDerived = (item.derived_units || []).find(
            (u) => Number(u.id) === Number(item.selected_derived_unit_id)
        );

        if (selectedDerived && selectedDerived.conversion_factor) {
            conversionFactor = Number(selectedDerived.conversion_factor);
            qty = Number(inputQty) * conversionFactor;
        }
    }
    const enteredPrice = item.unitPrice !== "" ? Number(item.unitPrice) : Number(item.defaultPrice || 0);
    const baseUnitPrice = selectedDerived
        ? round2(enteredPrice / conversionFactor)
        : enteredPrice;

    const expiry = item.expiry || null;
    if (!qty || qty <= 0) {
        setItemError(item, "qty", "Enter a valid quantity.");
        toast.error("Enter a valid quantity.");
        return;
    }

    if (!enteredPrice || enteredPrice <= 0) {
        setItemError(item, "unit_price", "Enter a valid unit price.");
        toast.error("Enter a valid unit price.");
        return;
    }
    const found = o_cart.value.find(
        (r) => r.id === item.id && r.unitPrice === baseUnitPrice && r.expiry === expiry
    );

    if (found) {
        found.qty = round2(found.qty + qty);
        found.cost = round2(found.qty * found.unitPrice);
    } else {
        o_cart.value.push({
            id: item.id,
            name: item.name,
            category: item.category,
            qty,
            unitPrice: baseUnitPrice,
            expiry,
            cost: round2(qty * baseUnitPrice),
            derived_unit_id: selectedDerived ? selectedDerived.id : null,
            derived_unit_name: selectedDerived ? selectedDerived.name : null,
            base_unit_name: item.unit_name || null,
            entered_price: enteredPrice,
            entered_unit: selectedDerived ? selectedDerived.name : item.unit_name,
        });
    }
    item.qty = null;
    item.unitPrice = null;
    item.expiry = null;
    item.selected_derived_unit_id = null;
    clearItemErrors(item);
}

function delOrderRow(idx) {
    o_cart.value.splice(idx, 1);
}


async function orderSubmit() {
    formErrors.value = {};

    if (!p_supplier.value) {
        formErrors.value.supplier_id = ["Please select a supplier."];
        toast.error("Please select a supplier.");
        return;
    }

    const today = new Date().toISOString().split("T")[0];
    const validItems = [];

    filteredItems.value.forEach((it) => {
        // Check if row has any data entered
        const hasAnyValue = it.qty || it.unitPrice;
        const errors = {};

        if (hasAnyValue) {
            let qty = Number(it.qty) || 0;

            // Apply conversion if derived unit is selected
            if (it.selected_derived_unit_id && it.selectedDerivedUnitInfo) {
                const conversionFactor = Number(it.selectedDerivedUnitInfo.conversion_factor) || 1;
                qty = qty * conversionFactor;
            }

            const enteredPrice = Number(it.unitPrice) || 0;
            const baseUnitPrice = it.selectedDerivedUnitInfo
                ? round2(enteredPrice / Number(it.selectedDerivedUnitInfo.conversion_factor))
                : enteredPrice;

            // Validate quantity
            if (!qty || qty <= 0) {
                errors.qty = ["Quantity is required"];
            }

            // Validate unit price
            if (!enteredPrice || enteredPrice <= 0) {
                errors.unit_price = ["Unit Price is required"];
            }


            if (Object.keys(errors).length > 0) {
                formErrors.value[it.id] = errors;
            } else {
                validItems.push({
                    product_id: it.id,
                    quantity: qty,
                    unit_price: baseUnitPrice,
                    expiry: it.expiry || null, // Optional expiry
                });
            }
        }
    });

    if (validItems.length === 0) {
        toast.error("No valid items to submit");
        return;
    }

    if (Object.keys(formErrors.value).length > 0) {
        toast.error("Please fix the highlighted errors");
        return;
    }

    const payload = {
        supplier_id: p_supplier.value,
        purchase_date: today,
        status: "pending",
        items: validItems,
    };

    o_submitting.value = true;
    try {
        await axios.post("/purchase-orders", payload);
        toast.success("Purchase order created successfully!");
        emit("refresh-data");

        // Reset form
        p_supplier.value = null;
        filteredItems.value.forEach((it) => {
            it.qty = 0;
            it.unitPrice = 0;
            it.expiry = null;
            it.subtotal = 0;
            it.selected_derived_unit_id = null;
            it.selectedDerivedUnitInfo = null;
        });

        const m = bootstrap.Modal.getInstance(
            document.getElementById("addOrderModal")
        );
        m?.hide();
    } catch (err) {
        console.error(err);
        toast.error("Failed to save order");
    } finally {
        o_submitting.value = false;
    }
}

async function multipleOrderSubmit() {
    m_formErrors.value = {};

    const today = new Date().toISOString().split("T")[0];
    const validItems = [];
    const groupedBySupplier = {};

    multipleOrderItems.value.forEach((it, idx) => {
        const hasAnyValue = it.qty || it.unitPrice;
        const errors = {};

        if (hasAnyValue) {
            if (!it.supplier_id) {
                errors.supplier_id = ["Supplier is required"];
            }

            let qty = Number(it.qty) || 0;

            if (it.selected_derived_unit_id && it.selectedDerivedUnitInfo) {
                const conversionFactor = Number(it.selectedDerivedUnitInfo.conversion_factor) || 1;
                qty = qty * conversionFactor;
            }

            const enteredPrice = Number(it.unitPrice) || 0;
            const baseUnitPrice = it.selectedDerivedUnitInfo
                ? round2(enteredPrice / Number(it.selectedDerivedUnitInfo.conversion_factor))
                : enteredPrice;

            if (!qty || qty <= 0) {
                errors.qty = ["Quantity is required"];
            }

            if (!enteredPrice || enteredPrice <= 0) {
                errors.unit_price = ["Unit Price is required"];
            }

            if (Object.keys(errors).length > 0) {
                m_formErrors.value[it.id] = errors;
            } else {
                const supplierId = it.supplier_id;
                if (!groupedBySupplier[supplierId]) {
                    groupedBySupplier[supplierId] = [];
                }
                groupedBySupplier[supplierId].push({
                    product_id: it.id,
                    quantity: qty,
                    unit_price: baseUnitPrice,
                    expiry: it.expiry || null,
                });
            }
        }
    });

    if (Object.keys(groupedBySupplier).length === 0) {
        toast.error("No valid items to submit");
        return;
    }

    if (Object.keys(m_formErrors.value).length > 0) {
        toast.error("Please fix the highlighted errors");
        return;
    }

    m_submitting.value = true;
    try {
        for (const [supplierId, items] of Object.entries(groupedBySupplier)) {
            const payload = {
                supplier_id: parseInt(supplierId),
                purchase_date: today,
                status: "pending",
                items: items,
            };
            await axios.post("/purchase-orders", payload);
        }

        toast.success("Multiple orders created successfully!");
        emit("refresh-data");

        multipleOrderItems.value.forEach((it) => {
            it.qty = 0;
            it.unitPrice = 0;
            it.expiry = null;
            it.subtotal = 0;
            it.supplier_id = null;
            it.selected_derived_unit_id = null;
            it.selectedDerivedUnitInfo = null;
        });

        const m = bootstrap.Modal.getInstance(
            document.getElementById("addOrderModal")
        );
        m?.hide();
    } catch (err) {
        console.error(err);
        toast.error("Failed to save multiple orders");
    } finally {
        m_submitting.value = false;
    }
}

// Date formate
const formatDate = (date) => {
    if (!date) return "—";
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${month}/${day}/${year}`;
};
</script>
<template>
    <div class="modal fade" id="addOrderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Order</h5>
                    <button
                        class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                        data-bs-dismiss="modal" aria-label="Close" title="Close" @click="resteErrors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="modal-body">
                    <!-- 👇 ADD THIS: TAB NAVIGATION -->
                    <ul class="nav nav-tabs mb-4" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" :class="{ active: activeTab === 'single' }"
                                @click="activeTab = 'single'" type="button" role="tab">
                                Single Order
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" :class="{ active: activeTab === 'multiple' }"
                                @click="activeTab = 'multiple'" type="button" role="tab">
                                Multiple Order
                            </button>
                        </li>
                    </ul>

                    <!-- 👇 SINGLE PURCHASE TAB (existing code) -->
                    <div v-if="activeTab === 'single'">
                        <div class="mb-3">
                            <label class="form-label">Preferred Supplier</label>
                            <Select v-model="p_supplier" :options="suppliers" optionLabel="name" optionValue="id"
                                placeholder="Select Supplier" class="w-100" appendTo="self" :autoZIndex="true"
                                :class="{ 'is-invalid': formErrors.supplier_id }" :baseZIndex="2000" />
                            <small v-if="formErrors.supplier_id" class="text-danger">
                                {{ formErrors.supplier_id[0] }}
                            </small>
                        </div>

                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Unit</th>
                                        <th>Stock</th>
                                        <th>Qty</th>
                                        <th>Unit</th>
                                        <th>Unit Price</th>
                                        <th>Subtotal</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(it, idx) in filteredItems" :key="it.id">
                                        <td>{{ it.name }}</td>
                                        <td>{{ it.category?.name || "-" }}</td>
                                        <td>{{ it.unit_name }}</td>
                                        <td>{{ it.stock }}</td>
                                        <td>
                                            <input type="number" min="0" v-model.number="it.qty" class="form-control"
                                                @input="handleQtyInput(it)" :class="{'is-invalid': formErrors[idx]?.qty}"/>
                                            <small v-if="formErrors[idx]?.qty" class="text-danger">
                                                {{ formErrors[idx].qty }}
                                            </small>
                                        </td>
                                        <td>
                                            <select class="form-select" v-model="it.selected_derived_unit_id"
                                                @change="onUnitChange(it)" @focus="loadDerivedUnits(it)">
                                                <option :value="null">Base ({{ it.unit_name }})</option>
                                                <option v-for="du in it.derived_units || []" :key="du.id"
                                                    :value="du.id">
                                                    {{ du.name }}
                                                </option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" min="0" v-model.number="it.unitPrice"
                                                class="form-control" @input="handlePriceInput(it)" :class="{'is-invalid': formErrors[idx]?.unitPrice}"/>
                                            <small v-if="formErrors[idx]?.unitPrice" class="text-danger">
                                                {{ formErrors[idx].unitPrice }}
                                            </small>
                                        </td>
                                        <td>{{ formatCurrencySymbol((it.subtotal || 0).toFixed(2)) }}</td>
                                        <td>


                                            <button
                                                class="btn btn-sm btn-outline-danger d-flex align-items-center justify-content-center"
                                                @click="clearRow(it)" title="Clear" style="width: 36px; height: 36px;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    fill="currentColor" viewBox="0 0 16 16">
                                                    <path fill-rule="evenodd"
                                                        d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z" />
                                                    <path
                                                        d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="text-end fw-bold mt-3">
                            Total: {{ formatCurrencySymbol(o_total.toFixed(2)) }}
                        </div>
                    </div>

                    <!-- 👇 ADD THIS: MULTIPLE PURCHASE TAB -->
                    <div v-if="activeTab === 'multiple'">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Unit</th>
                                        <th>Supplier</th>
                                        <th>Qty</th>
                                        <th>Unit</th>
                                        <th>Unit Price</th>
                                        <th>Subtotal</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(it, idx) in multipleOrderItems" :key="it.id">
                                        <td>{{ it.name }}</td>
                                        <td>{{ it.category?.name || "-" }}</td>
                                        <td>{{ it.unit_name }}</td>

                                        <td>
                                            <select v-model="it.supplier_id" class="form-select w-100"
                                                :class="{ 'is-invalid': m_formErrors[it.id]?.supplier_id }">

                                                <option value="" disabled hidden>Select Supplier</option>

                                                <option v-for="supplier in suppliers" :key="supplier.id"
                                                    :value="supplier.id">
                                                    {{ supplier.name }}
                                                </option>
                                            </select>

                                            <small v-if="m_formErrors[it.id]?.supplier_id" class="text-danger d-block">
                                                {{ m_formErrors[it.id].supplier_id[0] }}
                                            </small>
                                        </td>


                                        <td>
                                            <input type="number" min="0" v-model.number="it.qty" class="form-control"
                                                @input="handleQtyInput(it)" :class="{'is-invalid': formErrors[idx]?.qty}"/>
                                            <small v-if="formErrors[idx]?.qty" class="text-danger">
                                                {{ formErrors[idx].qty }}
                                            </small>
                                        </td>
                                        <td>
                                            <select class="form-select" v-model="it.selected_derived_unit_id"
                                                @change="onMultipleUnitChange(it)" @focus="loadDerivedUnits(it)">
                                                <option :value="null">Base ({{ it.unit_name }})</option>
                                                <option v-for="du in it.derived_units || []" :key="du.id"
                                                    :value="du.id">
                                                    {{ du.name }}
                                                </option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" min="0" v-model.number="it.unitPrice"
                                                class="form-control" @input="handlePriceInput(it)" :class="{'is-invalid': formErrors[idx]?.unitPrice}"/>
                                            <small v-if="formErrors[idx]?.unitPrice" class="text-danger">
                                                {{ formErrors[idx].unitPrice }}
                                            </small>
                                        </td>
                                        <td>{{ formatCurrencySymbol((it.subtotal || 0).toFixed(2)) }}</td>
                                        <td>


                                            <button
                                                class="btn btn-sm btn-outline-danger d-flex align-items-center justify-content-center"
                                                @click="clearMultipleRow(it)" title="Clear"
                                                style="width: 36px; height: 36px;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    fill="currentColor" viewBox="0 0 16 16">
                                                    <path fill-rule="evenodd"
                                                        d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z" />
                                                    <path
                                                        d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="text-end fw-bold mt-3">
                            Total: {{ formatCurrencySymbol(m_total.toFixed(2)) }}
                        </div>
                    </div>
                </div>

                <!-- 👇 UPDATE THIS: Modal footer with conditional buttons -->
                <div class="modal-footer">
                    <button v-if="activeTab === 'single'" class="btn btn-primary rounded-pill px-4 py-2"
                        :disabled="o_submitting" @click="orderSubmit">
                        <span v-if="!o_submitting">Save</span>
                        <span v-else>Saving...</span>
                    </button>
                    <button v-if="activeTab === 'multiple'" class="btn btn-primary rounded-pill px-4 py-2"
                        :disabled="m_submitting" @click="multipleOrderSubmit">
                        <span v-if="!m_submitting">Save</span>
                        <span v-else>Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
<style scoped>
.dark .modal-body {
    background-color: #181818 !important;
    color: #f9fafb !important;
}

.dark .modal-header {
    background-color: #181818 !important;
    color: #f9fafb !important;
}

.dark .table {
    background-color: #181818 !important;
    color: #f9fafb !important;
}

.dark .table thead {
    background-color: #181818;
    color: #f9fafb;
}

.dark .table thead th {
    background-color: #181818;
    color: #f9fafb;
}

.dark .table tbody td {
    background-color: #181818;
    color: #f9fafb;
}

.dark .cart {
    background-color: #181818;
    color: #f9fafb;
}

.dark .card-body {
    background-color: #181818;
    color: #f9fafb;
}

.dark .form-select {
    background-color: #181818 !important;
    color: #fff !important;
}

.dark input {
    background-color: #181818;
    color: #f9fafb;
}

:deep(.p-select) {
    background-color: white !important;
    color: black !important;
    border-color: #9b9c9c
}

:deep(.p-select-list-container) {
    background-color: white !important;
    color: black !important;
}
:deep(.p-select-option) {
    background-color: transparent !important;
    color: black !important;
}
:deep(.p-select-option:hover) {
    background-color: #f0f0f0 !important;
    color: black !important;
}
:deep(.p-select-option.p-focus) {
    background-color: #f0f0f0 !important;
    color: black !important;
}

:deep(.p-select-label) {
    color: #000 !important;
}

:deep(.p-placeholder) {
    color: #80878e !important;
}
</style>
