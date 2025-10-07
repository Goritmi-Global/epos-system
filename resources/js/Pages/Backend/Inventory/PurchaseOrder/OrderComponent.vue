<script setup>
import { toast } from "vue3-toastify";
import Select from "primevue/select";
import { ref, computed } from "vue";
import { useFormatters } from '@/composables/useFormatters'

const { formatMoney, formatNumber, dateFmt } = useFormatters()

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

const o_search = ref("");
const p_supplier = ref(null);
const p_submitting = ref(false);
const formErrors = ref({});
  const p_search = ref("");
const o_submitting = ref(false);
const derivedUnitsCache = ref({});

const filteredItems = computed(() => {
  const term = o_search.value.trim().toLowerCase();
  if (!term) return props.items;

  return props.items.filter((i) =>
    [
      i.name,
      i.category?.name ?? "",
      i.unit_name ?? "",
      String(i.stock ?? "")
    ]
      .join(" ")
      .toLowerCase()
      .includes(term)
  );
});
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
const money = (n, currency = "GBP") =>
    new Intl.NumberFormat("en-GB", { style: "currency", currency }).format(
        Number(n || 0)
    );
const round2 = (n) => Math.round((Number(n) || 0) * 100) / 100;

const o_cart = ref([]);
const o_total = computed(() =>
    round2(o_cart.value.reduce((s, r) => s + Number(r.cost || 0), 0))
);
const setItemError = (item, field, message) => {
    if (!formErrors.value[item.id]) formErrors.value[item.id] = {};
    formErrors.value[item.id][field] = [message];
};


// function for fetcing the derived units
async function loadDerivedUnits(item) {
    try {
        // try typical key names used in many codebases; adapt if your item uses different key
        const baseUnitId = item.unit_id ?? item.unitId ?? null;

        if (!baseUnitId) {
            // fallback: no base id available => set empty array
            item.derived_units = [];
            return;
        }

        // return cached if present
        if (derivedUnitsCache.value[baseUnitId]) {
            item.derived_units = derivedUnitsCache.value[baseUnitId];
            return;
        }

        // request units (request big per_page so we can filter on client; adjust per_page if needed)
        const res = await axios.get("/units", { params: { per_page: 200 } });
        const list = res.data?.data ?? res.data ?? [];

        // filter derived units where base_unit_id matches this item's base unit id
        const derived = list.filter(
            (u) => u.base_unit_id !== null && Number(u.base_unit_id) === Number(baseUnitId)
        );

        // cache + attach to item for use in template
        derivedUnitsCache.value[baseUnitId] = derived;
        item.derived_units = derived;
    } catch (err) {
        console.error("loadDerivedUnits error:", err);
        item.derived_units = [];
    }
}


// handle unit change (base or derived)
function onUnitChange(it) {
    if (!it.selected_derived_unit_id) {
        it.selectedDerivedUnitInfo = null // reset if base unit selected
    } else {
        it.selectedDerivedUnitInfo = it.derived_units.find(
            du => du.id === it.selected_derived_unit_id
        )
    }
}




// clear either a specific field error for an item, or all errors for that item
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

async function addOrderItem(item) {
    clearItemErrors(item);
    
      const inputQty = Number(item.qty || 0);

    // ensure qty entered
    if (!inputQty || inputQty <= 0) {
        setItemError(item, "qty", "Enter a valid quantity.");
        toast.error("Enter a valid quantity.");
        return;
    }

    // determine final qty (in base units)
    let qty = inputQty; // will become quantity in base unit if conversion applies
    let selectedDerived = null;

    if (item.selected_derived_unit_id) {
        // make sure derived units are loaded
        await loadDerivedUnits(item);
        selectedDerived = (item.derived_units || []).find(
            (u) => Number(u.id) === Number(item.selected_derived_unit_id)
        );

        if (selectedDerived && selectedDerived.conversion_factor) {
            // convert from selected derived unit to base unit
            qty = Number(inputQty) * Number(selectedDerived.conversion_factor);
        }
    }

    // unit price & expiry as before
    const price =
        item.unitPrice !== "" ? Number(item.unitPrice) : Number(item.defaultPrice || 0);
    const expiry = item.expiry || null;

    if (!qty || qty <= 0) {
        setItemError(item, "qty", "Enter a valid quantity.");
        toast.error("Enter a valid quantity.");
        return;
    }

    if (!price || price <= 0) {
        setItemError(item, "unit_price", "Enter a valid unit price.");
        toast.error("Enter a valid unit price.");
        return;
    }

    // MERGE: same product + same unitPrice + same expiry
    const found = o_cart.value.find(
        (r) => r.id === item.id && r.unitPrice === price && r.expiry === expiry
    );

    if (found) {
        found.qty = round2(found.qty + qty);
        found.cost = round2(found.qty * found.unitPrice);
    } else {
        o_cart.value.push({
             id: item.id,
            name: item.name,
            category: item.category,
            // qty is now in base units (after conversion)
            qty,
            unitPrice: price,
            expiry,
            cost: round2(qty * price),

            // helpful metadata:
            derived_unit_id: selectedDerived ? selectedDerived.id : null,
            derived_unit_name: selectedDerived ? selectedDerived.name : null,
            base_unit_name: item.unit_name || null, // so you know which base unit qty is in
        });
    }

    // reset only this item's fields
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
    // validate supplier
    if (!p_supplier.value) {
        formErrors.value.supplier_id = ["Please select a supplier."];
        toast.error("Please select a supplier.");
        await nextTick();
        document.getElementById("supplierSelect")?.focus();
        return;
    }

    const payload = {
        supplier_id: p_supplier.value,
        purchase_date: new Date().toISOString().split("T")[0],
        status: "pending",
        items: o_cart.value.map(({ id, qty, unitPrice, expiry }) => ({
            product_id: id,
            quantity: qty,
            unit_price: unitPrice,
            expiry: expiry || null,
        })),
    };

    o_submitting.value = true;

    try {
        const res = await axios.post("/purchase-orders", payload);

        // reset
        o_cart.value = [];
        p_supplier.value = null;

        const m = bootstrap.Modal.getInstance(
            document.getElementById("addOrderModal")
        );
        m?.hide();

        toast.success("Purchase order created successfully!");
         emit("refresh-data");
    } catch (err) {
        if (err?.response?.status === 422 && err.response.data?.errors) {
            formErrors.value = err.response.data.errors;
            toast.error("Please fill in all required fields correctly.");
        } else {
            console.error(err);
            toast.error("Something went wrong. Please try again.", {
                autoClose: 3000,
            });
        }
    } finally {
        o_submitting.value = false;
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
                        data-bs-dismiss="modal"
                        aria-label="Close"
                        title="Close"
                        @click="resteErrors"
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

                <div class="modal-body">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-5">
                            <label class="form-label small text-muted d-block"
                                >Preferred Supplier</label
                            >

                            <Select
                                v-model="p_supplier"
                                :options="suppliers"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select Supplier"
                                class="w-100"
                                :class="{
                                    'is-invalid': formErrors.supplier_id,
                                }"
                                appendTo="self"
                                :autoZIndex="true"
                                :baseZIndex="2000"
                            />

                            <small
                                v-if="formErrors.supplier_id"
                                class="text-danger"
                            >
                                {{ formErrors.supplier_id[0] }}
                            </small>
                        </div>
                    </div>

                    <div class="row g-4 mt-1">
                        <!-- Left: inventory picker -->
                        <div class="col-lg-5">
                            <div class="search-wrap mb-2">
                                <i class="bi bi-search"></i>
                                <input
                                    v-model="o_search"
                                    type="text"
                                    class="form-control search-input"
                                    placeholder="Search..."
                                />
                            </div>

                            <div
                                v-for="it in filteredItems"
                                :key="it.id"
                                class="card shadow-sm border-0 rounded-4 mb-3"
                            >
                                <div class="card-body">
                                    <div class="d-flex align-items-start gap-3">
                                        <img
                                            :src="it.image_url"
                                            alt=""
                                            style="
                                                width: 76px;
                                                height: 76px;
                                                object-fit: cover;
                                                border-radius: 6px;
                                            "
                                        />

                                        <div class="flex-grow-1">
                                            <div class="fw-semibold">
                                                {{ it.name }}
                                            </div>
                                            <div class="text-muted small">
                                                Category:
                                                {{ it.category.name }}
                                            </div>
                                            <div class="text-muted small">
                                                Unit: {{ it.unit_name }}
                                            </div>
                                            <div class="text-muted small">
                                                Stock:
                                                {{ it.stock }}
                                            </div>
                                        </div>
                                        <button
                                            class="btn btn-primary rounded-pill px-3 py-1 btn-sm"
                                            @click="addOrderItem(it)"
                                        >
                                            Add
                                        </button>
                                    </div>
     <!-- ⚡ badge for derived unit -->
                                        <div v-if="it.selectedDerivedUnitInfo" class="col-12 mt-3">
                                            <span style="
                                                    display: inline-block;
                                                    background-color: #e6f7f1;   /* light green background */
                                                    color: #155724;              /* dark green text */
                                                    padding: 4px 18px;
                                                    border-radius: 20px;
                                                    font-size: 0.9rem;
                                                    font-weight: 500;
                                                    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
                                                    ">
                                                1 {{ it.selectedDerivedUnitInfo.name }} =
                                                {{ it.selectedDerivedUnitInfo.conversion_factor }} {{ it.unit_name }}
                                            </span>
                                        </div>
                                    <div class="row g-2 mt-3">
                                        <div class="col-3">
                                            <label class="small text-muted"
                                                >Quantity</label
                                            >
                                            <input
                                                v-model.number="it.qty"
                                                type="number"
                                                min="0"
                                                class="form-control form-control"
                                                :class="{
                                                    'is-invalid':
                                                        formErrors[it.id] &&
                                                        formErrors[it.id].qty,
                                                }"
                                            />
                                            <small
                                                v-if="
                                                    formErrors[it.id] &&
                                                    formErrors[it.id].qty
                                                "
                                                class="text-danger"
                                            >
                                                {{ formErrors[it.id].qty[0] }}
                                            </small>
                                        </div>

                                           <div class="col-3">
                                                <label class="small text-muted">Unit</label>
                                                <select class="form-select" v-model="it.selected_derived_unit_id"
                                                    @change="onUnitChange(it)" @focus="loadDerivedUnits(it)">
                                                    <!-- Base unit -->
                                                    <option :value="null">Base ({{ it.unit_name }})</option>

                                                    <!-- Derived units -->
                                                    <option v-for="du in it.derived_units || []" :key="du.id"
                                                        :value="du.id">
                                                        {{ du.name }}
                                                    </option>
                                                </select>

                                                <!-- error -->
                                                <small v-if="formErrors[it.id] && formErrors[it.id].derived_unit"
                                                    class="text-danger">
                                                    {{ formErrors[it.id].derived_unit[0] }}
                                                </small>
                                            </div>

                                        <div class="col-3">
                                            <label class="small text-muted"
                                                >Unit Price</label
                                            >
                                            <input
                                                v-model.number="it.unitPrice"
                                                type="number"
                                                min="0"
                                                class="form-control form-control"
                                                :class="{
                                                    'is-invalid':
                                                        formErrors[it.id] &&
                                                        formErrors[it.id]
                                                            .unit_price,
                                                }"
                                            />
                                            <small
                                                v-if="
                                                    formErrors[it.id] &&
                                                    formErrors[it.id].unit_price
                                                "
                                                class="text-danger"
                                            >
                                                {{
                                                    formErrors[it.id]
                                                        .unit_price[0]
                                                }}
                                            </small>
                                        </div>
                                        
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: cart -->
                        <div class="col-lg-7">
                            <div class="cart card border rounded-4">
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Name</th>

                                                <th>Quantity</th>
                                                <th>unit price</th>
                                                <th>cost</th>
                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="(r, idx) in o_cart"
                                                :key="idx"
                                            >
                                                <td>
                                                    {{ r.name }}
                                                </td>

                                                <td>{{ r.qty }}</td>
                                                <td>
                                                    {{ r.unitPrice }}
                                                </td>
                                                <td>
                                                    {{ r.cost }}
                                                </td>
                                                <td class="text-end">
                                                    <button
                                                        @click="
                                                            delOrderRow(idx)
                                                        "
                                                        class="inline-flex items-center justify-center p-2.5 rounded-full text-red-600 hover:bg-red-100"
                                                        title="Delete"
                                                    >
                                                        <svg
                                                            class="w-5 h-5"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            stroke-width="2"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0H7m4-3h2a1 1 0 011 1v1H8V5a1 1 0 011-1z"
                                                            />
                                                        </svg>
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr v-if="o_cart.length === 0">
                                                <td
                                                    colspan="7"
                                                    class="text-center text-muted py-4"
                                                >
                                                    No items added.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-end p-3 fw-semibold">
                                    Total Bill: {{ formatMoney(o_total) }}
                                </div>
                            </div>

                            <div class="mt-4 text-center">
                                <button
                                    type="button"
                                    class="btn btn-primary rounded-pill px-5 py-2"
                                    :disabled="
                                        o_submitting || o_cart.length === 0
                                    "
                                    @click="orderSubmit"
                                >
                                    <span v-if="!o_submitting">Order</span>
                                    <span v-else>Saving...</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /modal-body -->
            </div>
        </div>
    </div>
</template>
<style scoped>
.dark .modal-body{
    background-color: #181818 !important; /* gray-800 */
  color: #f9fafb !important;  
}

.dark .modal-header{
      background-color: #181818 !important; /* gray-800 */
  color: #f9fafb !important;  
}

.dark .table {
  background-color: #181818 !important; /* gray-900 */
  color: #f9fafb !important;
}

.dark .btn-primary{
    background-color: #181818 !important;
    border: #181818 !important;
}
.dark .table thead{
    background-color:  #181818;
    color: #f9fafb;
}
.dark .table thead th{
    background-color:  #181818;
    color: #f9fafb;
}

.dark .table tbody td{
    background-color:  #181818;
    color: #f9fafb;
}
.dark .cart{
     background-color:  #181818;
    color: #f9fafb;
}

.dark .card-body{
      background-color:  #181818;
    color: #f9fafb;
}

.dark input{
      background-color:  #181818;
    color: #f9fafb;
}

/* ====================Select Styling===================== */
/* Entire select container */
:deep(.p-select) {
  background-color: white !important;
  color: black !important;
  border-color: #9b9c9c
}

/* Options container */
:deep(.p-select-list-container) {
  background-color: white !important;
  color: black !important;
}

/* Each option */
:deep(.p-select-option) {
  background-color: transparent !important; /* instead of 'none' */
  color: black !important;
}

/* Hovered option */
:deep(.p-select-option:hover) {
  background-color: #f0f0f0 !important;
  color: black !important;
}

/* Focused option (when using arrow keys) */
:deep(.p-select-option.p-focus) {
  background-color: #f0f0f0 !important;
  color: black !important;
}
:deep(.p-select-label){
    color: #000 !important;
}
:deep(.p-placeholder){
    color: #80878e !important;
}
</style>
