<script setup>
import { reactive, toRaw, watch } from "vue"
const props = defineProps({ model: Object })
const emit = defineEmits(["save"])

const form = reactive({
  receipt_header: props.model.receipt_header ?? "",
  receipt_footer: props.model.receipt_footer ?? "",
  receipt_logo: props.model.receipt_logo ?? null,
  receipt_logo_file: null,
  show_qr: props.model.show_qr ?? "yes",
  tax_breakdown: props.model.tax_breakdown ?? "yes",
  kitchen_printer: props.model.kitchen_printer ?? "yes",
  store_phone: props.model.store_phone ?? "03101186261",
  store_name: props.model.store_name ?? "Enter store name"
})
watch(form, () => emit("save",{ step:6, data: toRaw(form) }), { deep:true })

function onLogo(e){
  const f = e.target.files?.[0]
  form.receipt_logo_file = f || null
  if (f) form.receipt_logo = URL.createObjectURL(f)
}
</script>

<template>
  <div>
    <h5 class="fw-bold mb-3">Step 6 of 9 - Receipt & Printer Setup</h5>

    <div class="row g-4">
      <div class="col-lg-7">
        <label class="form-label">Receipt header</label>
        <input class="form-control" v-model="form.receipt_header"/>

        <label class="form-label mt-3">Upload Receipt Logo</label>
        <div class="d-flex align-items-center gap-3">
          <div class="border rounded-4 p-2 text-center" style="height:120px; width:120px;">
            <img v-if="form.receipt_logo" :src="form.receipt_logo" alt="" style="max-height:100%; max-width:100%;"/>
            <div v-else class="h-100 d-flex align-items-center justify-content-center text-muted">
              <i class="bi bi-upload fs-2"></i>
            </div>
          </div>
          <input type="file" class="form-control" accept="image/*" @change="onLogo">
        </div>

        <label class="form-label mt-3">Receipt Footer</label>
        <input class="form-control" v-model="form.receipt_footer"/>

        <div class="mt-3 d-flex align-items-center justify-content-between">
          <span>Show QR Code on Receipt</span>
          <div>
            <label class="me-3"><input class="form-check-input me-1" type="radio" value="yes" v-model="form.show_qr"> Yes</label>
            <label><input class="form-check-input me-1" type="radio" value="no" v-model="form.show_qr"> No</label>
          </div>
        </div>

        <div class="mt-2 d-flex align-items-center justify-content-between">
          <span>Tax Breakdown</span>
          <div>
            <label class="me-3"><input class="form-check-input me-1" type="radio" value="yes" v-model="form.tax_breakdown"> Yes</label>
            <label><input class="form-check-input me-1" type="radio" value="no" v-model="form.tax_breakdown"> No</label>
          </div>
        </div>

        <div class="mt-2 d-flex align-items-center justify-content-between">
          <span>Add Kitchen Printer</span>
          <div>
            <label class="me-3"><input class="form-check-input me-1" type="radio" value="yes" v-model="form.kitchen_printer"> Yes</label>
            <label><input class="form-check-input me-1" type="radio" value="no" v-model="form.kitchen_printer"> No</label>
          </div>
        </div>
      </div>

      <!-- Preview -->
      <div class="col-lg-5">
        <div class="border rounded-4 p-3" style="background:#fff">
          <h6 class="text-center">{{ form.store_name }}</h6>
          <div class="text-center text-muted small">{{ new Date().toLocaleString() }}</div>
          <div class="text-center my-2">
            <div class="border rounded-pill px-3 py-1 d-inline-block">{{ form.store_phone }}</div>
          </div>
          <hr/>
          <div class="small">
            <div class="d-flex justify-content-between"><span>Payment Type:</span><span>Credit</span></div>
            <div class="d-flex justify-content-between"><span>Customer Name:</span><span>Lorem Ipsum</span></div>
            <div class="d-flex justify-content-between"><span>Address:</span><span>abc</span></div>
          </div>
          <hr/>
          <div class="small">
            <div class="d-flex justify-content-between"><strong>Name</strong><strong>Qty</strong><strong>Price</strong></div>
            <div class="d-flex justify-content-between"><span>Item A</span><span>1</span><span>Rs 950</span></div>
            <div class="d-flex justify-content-between"><span>Item B</span><span>2</span><span>Rs 50</span></div>
          </div>
          <hr/>
          <div class="small">
            <div class="d-flex justify-content-between"><span>Total Price:</span><span>Rs 2000</span></div>
            <div class="d-flex justify-content-between"><span>Tax:</span><span>23%</span></div>
          </div>
          <hr/>
          <div class="text-center small">{{ form.receipt_footer || 'Thank you!' }}</div>
        </div>
      </div>
    </div>
  </div>
</template>
