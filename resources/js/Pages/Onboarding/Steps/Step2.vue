<script setup>
import { reactive, ref, toRaw, watch, onMounted } from "vue"
import Select from "primevue/select"

const props = defineProps({ model: Object })
const emit = defineEmits(["save"])

/* ------------------ FORM ------------------ */
const form = reactive({
  business_name: props.model?.business_name ?? "",
  business_type: props.model?.business_type ?? "",
  address:       props.model?.address ?? "",
  email:         props.model?.email ?? "",
  website:       props.model?.website ?? "",
  // phone pieces
  phone_country: props.model?.phone_country ?? "",  // ISO "GB"
  phone_code:    props.model?.phone_code ?? "",     // "+44"
  phone_local:   props.model?.phone_local ?? "",    // "1234567890"
  phone:         props.model?.phone ?? "",          // "+441234567890"
  // logo
  logo:          props.model?.logo ?? null,
  logo_file:     null,
})

/* ------------------ BUSINESS TYPE ------------------ */
const businessTypeOptions = [
  { name: "Cafe",       code: "cafe" },
  { name: "Restaurant", code: "restaurant" },
  { name: "Bakery",     code: "bakery" },
  { name: "Takeaway",   code: "takeaway" },
  { name: "Food Truck", code: "food_truck" },
]
const selectedBusinessType = ref(
  businessTypeOptions.find(o => o.name === form.business_type) || null
)
watch(selectedBusinessType, (opt) => {
  form.business_type = opt?.name || ""
  emitSave()
})

/* ------------------ PHONE (flag + dial) ------------------ */
const dialOptions = [
  { name: "United Kingdom", iso: "GB", dial: "+44" },
  { name: "Pakistan",       iso: "PK", dial: "+92" },
  { name: "United States",  iso: "US", dial: "+1"  },
  { name: "Canada",         iso: "CA", dial: "+1"  },
  { name: "United Arab Emirates", iso: "AE", dial: "+971" },
  { name: "Saudi Arabia",   iso: "SA", dial: "+966" },
  { name: "Australia",      iso: "AU", dial: "+61" },
  { name: "India",          iso: "IN", dial: "+91" },
]

const selectedDial = ref(null)

function syncDialFromIso(iso) {
  if (!iso) return
  const opt = dialOptions.find(o => o.iso === iso.toUpperCase())
  if (opt) {
    selectedDial.value = opt
    form.phone_country = opt.iso
    form.phone_code = opt.dial
  }
}
function buildFullPhone() {
  const code = (form.phone_code || "").trim()
  const local = (form.phone_local || "").replace(/\D+/g, "")
  form.phone = code + local
}

// default from Step 1’s country_code if present
onMounted(() => {
  const iso = props.model?.country_code || form.phone_country
  if (iso) syncDialFromIso(iso)
  buildFullPhone()
})

watch(selectedDial, (opt) => {
  if (!opt) return
  form.phone_country = opt.iso
  form.phone_code = opt.dial
  buildFullPhone()
  emitSave()
})
watch(() => form.phone_local, () => {
  buildFullPhone()
  emitSave()
})

/* ------------------ LOGO (crop / zoom modal hooks) ------------------ */
const showCropper = ref(false)
const showImageModal = ref(false)
const previewImage = ref(null)

function openImageModal(src) {
  previewImage.value = src || form.logo
  if (!previewImage.value) return
  showImageModal.value = true
}

function handleCropped(payload) {
  if (payload instanceof Blob) {
    const file = new File([payload], "logo.jpg", { type: payload.type || "image/jpeg" })
    form.logo_file = file
    form.logo = URL.createObjectURL(file)
  } else if (payload?.file instanceof File) {
    form.logo_file = payload.file
    form.logo = URL.createObjectURL(payload.file)
  } else if (typeof payload === "string") {
    form.logo_file = null
    form.logo = payload
  } else if (payload?.dataUrl) {
    form.logo_file = null
    form.logo = payload.dataUrl
  }
  showCropper.value = false
  emitSave()
}

function emitSave() {
  emit("save", { step: 2, data: toRaw(form) })
}

/* helper for flags */
const flagUrl = (iso, size = "24x18") =>
  `https://flagcdn.com/${size}/${String(iso || "").toLowerCase()}.png`
</script>

<template>
  <div>
    <h5 class="fw-bold mb-4">Step 2 of 9 - Business Information</h5>

    <div class="row g-4">
      <div class="col-12">
        <label class="form-label">Business Name*</label>
        <input class="form-control" v-model="form.business_name" @input="emitSave" />
      </div>

      <!-- Logo card -->
      <div class="col-md-3">
        <div class="logo-card">
          <div class="logo-frame" @click="form.logo && openImageModal(form.logo)">
            <img v-if="form.logo" :src="form.logo" alt="Logo" />
            <div v-else class="placeholder">
              <i class="bi bi-image"></i>
            </div>
          </div>

          <small class="text-muted mt-2">Upload Logo</small>

          <button type="button" class="btn btn-primary rounded-pill mt-3 px-4"
                  @click="showCropper = true">
            Upload & Crop Image
          </button>
        </div>
      </div>

      <div class="col-md-9">
        <label class="form-label">Business Type*</label>
        <Select
          v-model="selectedBusinessType"
          :options="businessTypeOptions"
          optionLabel="name"
          :filter="true"
          placeholder="Select business type"
          class="w-100"
        >
          <template #value="{ value, placeholder }">
            <span v-if="value">{{ value.name }}</span>
            <span v-else>{{ placeholder }}</span>
          </template>
          <template #option="{ option }">
            <span>{{ option.name }}</span>
          </template>
        </Select>

        <label class="form-label mt-3">Address*</label>
        <input class="form-control" v-model="form.address" @input="emitSave" />

        <div class="row g-3 mt-1">
          <div class="col-md-6">
            <label class="form-label">Phone*</label>

            <!-- Phone input group: flag + dial + number -->
            <div class="input-group">
              <span class="input-group-text p-0">
                <Select
                  v-model="selectedDial"
                  :options="dialOptions"
                  optionLabel="name"
                  :filter="true"
                  placeholder="Code"
                  class="dial-select"
                >
                  <!-- selected -->
                  <template #value="{ value, placeholder }">
                    <div v-if="value" class="d-flex align-items-center gap-2 px-2">
                      <img :src="flagUrl(value.iso,'16x12')" width="16" height="12" alt="" />
                      <span class="fw-semibold">{{ value.dial }}</span>
                    </div>
                    <span v-else class="px-2">{{ placeholder }}</span>
                  </template>
                  <!-- options -->
                  <template #option="{ option }">
                    <div class="d-flex align-items-center gap-2">
                      <img :src="flagUrl(option.iso,'16x12')" width="16" height="12" alt="" />
                      <span class="fw-semibold">{{ option.dial }}</span>
                      <small class="text-muted">— {{ option.name }}</small>
                    </div>
                  </template>
                </Select>
              </span>

              <input
                class="form-control"
                inputmode="numeric"
                placeholder="Phone number"
                v-model="form.phone_local"
              />
            </div>
          </div>

          <div class="col-md-6">
            <label class="form-label">Email*</label>
            <input type="email" class="form-control" v-model="form.email" @input="emitSave" />
          </div>
        </div>

        <label class="form-label mt-3">Website</label>
        <input class="form-control" v-model="form.website" @input="emitSave" />
      </div>
    </div>
  </div>

  <!-- Modals -->
  <ImageCropperModal
    :show="showCropper"
    @close="showCropper = false"
    @cropped="handleCropped"
  />
  <ImageZoomModal
    :show="showImageModal"
    :image="previewImage"
    @close="showImageModal = false"
  />
</template>

<style scoped>
.logo-card{
  background:#fff;
  border:1px solid #edf0f5;
  border-radius:1rem;
  padding:1rem;
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:flex-start;
  min-height:220px;
  box-shadow: 0 6px 18px rgba(17,38,146,0.05);
}
.logo-frame{
  width:140px; height:140px;
  border-radius:1rem;
  border:1px dashed #cfd6e4;
  background:#f7f9fc;
  display:flex; align-items:center; justify-content:center;
  overflow:hidden;
  cursor: pointer;
}
.logo-frame img{
  max-width:100%; max-height:100%; object-fit:contain;
}
.logo-frame .placeholder{
  color:#8b97a7; font-size:28px; line-height:0;
}
.dial-select{ min-width:130px; border:0; }
.input-group > .input-group-text{ background:transparent; border-right:0; }
.input-group .form-control{ border-left:0; }
</style>
