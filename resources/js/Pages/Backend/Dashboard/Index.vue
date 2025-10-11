<script setup>
import Master from "@/Layouts/Master.vue";
import { Head } from "@inertiajs/vue3";
import VueApexCharts from "vue3-apexcharts";
import { onMounted, ref } from "vue";
import { useFormatters } from '@/composables/useFormatters'
import { toast } from "vue3-toastify";

const { formatMoney, formatCurrencySymbol, formatNumber, dateFmt } = useFormatters();

const props = defineProps({
  totalSuppliers: {
    type: Number,
    default: 0
  },
  recentItems: {
    type: Array,
    default: () => []
  }
})

const series = [
  {
    name: "Sales",
    data: [50, 45, 60, 70, 50, 45, 60, 70],
  },
  {
    name: "Purchase",
    data: [-20, -80, -25, -70, -15, -20, -35, -30],
  },
];

const chartOptions = {
  chart: {
    type: "bar",
    height: 350,
    stacked: true,
    toolbar: { show: false },
  },
  colors: ["#65FA9E", "#EA5455"],
  plotOptions: {
    bar: {
      horizontal: false,
      columnWidth: "20%",
      borderRadius: 3,
      dataLabels: {
        position: "top",
      },
    },
  },
  dataLabels: {
    enabled: true,
    offsetY: -8,
    style: {
      fontSize: "12px",
      colors: ["#333"],
    },
    formatter: (val) => Math.abs(val),
  },
  xaxis: {
    categories: [
      "Jan",
      "Feb",
      "March",
      "April",
      "May",
      "June",
      "July",
      "August",
    ],
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: {
    min: -60,
    max: 90,
    labels: {
      formatter: (val) => Math.abs(val),
    },
  },
  legend: {
    position: "top",
    horizontalAlign: "left",
    markers: {
      radius: 12,
    },
  },
  grid: {
    borderColor: "#e0e0e0",
    strokeDashArray: 4,
  },
};

const totalPurchaseDueMinor = 123123123.33
const totalSalesDueMinor = 438500
const totalSaleAmountMinor = 38565650
const anotherSaleMinor = 40000

const customersCount = 1.400
const suppliersCount = 1030
const purchaseInvCnt = 1020
const salesInvCnt = 1435

import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";
const page = usePage()
const inventoryDetails = computed(() => page.props.inventoryAlerts)
const showModal = ref(page.props.showPopup)
const showReminderPicker = ref(false)
const reminderDate = ref('')
const reminderTime = ref('')

const hasAnyAlerts = computed(() => {
  return (inventoryDetails.value?.outOfStock > 0) ||
    (inventoryDetails.value?.lowStock > 0) ||
    (inventoryDetails.value?.expired > 0) ||
    (inventoryDetails.value?.nearExpiry > 0)
})

onMounted(() => {
  checkReminder()

  if (showModal.value) {
    showModal.value = true
  }

  setInterval(checkReminder, 60000)
})

const checkReminder = () => {
  const reminderData = localStorage.getItem('inventory_reminder')

  if (reminderData) {
    const { datetime, dismissed } = JSON.parse(reminderData)
    const reminderDateTime = new Date(datetime)
    const now = new Date()

    if (now >= reminderDateTime && !dismissed) {
      showModal.value = true
    }
  }
}

const closeModal = () => {
  showModal.value = false
  showReminderPicker.value = false
}

const goToInventory = () => {
  localStorage.removeItem('inventory_reminder')
  window.location.href = '/inventory'
}


// compute whether to use 24-hour or 12-hour
const is24Hour = computed(() => page?.props?.onboarding?.currency_and_locale?.time_format === '24-hour')

// adjust display format accordingly
const timeFormat = computed(() => (is24Hour.value ? 'HH:mm' : 'hh:mm a'))

const openReminderPicker = () => {
  showReminderPicker.value = true

  const now = new Date()

  // VueDatePicker expects Date objects, not strings
  reminderDate.value = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  reminderTime.value = {
    hours: now.getHours(),
    minutes: now.getMinutes()
  }
}

const setReminder = () => {
  console.log('setReminder called')
  console.log('Date:', reminderDate.value)
  console.log('Time:', reminderTime.value)

  if (!reminderDate.value || !reminderTime.value) {
    alert('Please select both date and time')
    return
  }

  try {
    // Extract date components from the Date object
    const date = new Date(reminderDate.value)
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')

    // Extract time components (VueDatePicker time-picker returns an object)
    const hours = String(reminderTime.value.hours).padStart(2, '0')
    const minutes = String(reminderTime.value.minutes).padStart(2, '0')

    // Create datetime string in ISO format
    const datetimeString = `${year}-${month}-${day}T${hours}:${minutes}:00`
    console.log('DateTime string:', datetimeString)

    const datetime = new Date(datetimeString)
    const now = new Date()

    console.log('Selected datetime:', datetime)
    console.log('Current datetime:', now)
    console.log('Is valid date:', !isNaN(datetime.getTime()))

    // Check if date is valid
    if (isNaN(datetime.getTime())) {
      alert('Invalid date or time selected. Please try again.')
      return
    }

    if (datetime <= now) {
      alert('Please select a future date and time')
      return
    }

    const reminderData = {
      datetime: datetime.toISOString(),
      dismissed: false,
      alerts: inventoryDetails.value
    }

    console.log('Saving reminder data:', reminderData)
    localStorage.setItem('inventory_reminder', JSON.stringify(reminderData))
    console.log('Reminder saved successfully')

    toast.success(`Reminder set for ${datetime.toLocaleString()}`)
    showModal.value = false
    showReminderPicker.value = false
  } catch (error) {
    console.error('Error saving reminder:', error)
    alert('Failed to save reminder. Please try again.')
  }
}

const dismissReminder = () => {
  const reminderData = localStorage.getItem('inventory_reminder')

  if (reminderData) {
    const data = JSON.parse(reminderData)
    data.dismissed = true
    localStorage.setItem('inventory_reminder', JSON.stringify(data))
  }

  showModal.value = false
  showReminderPicker.value = false
}
</script>

<template>

  <Head title="Dashboard" />

  <Master>
    <div class="page-wrapper">
      <div class="row">
        <!-- Dashboard cards (keeping your existing structure) -->
        <div class="col-lg-3 col-sm-6 col-12">
          <div class="dash-widget">
            <div class="dash-widgetimg">
              <span><img src="assets/img/icons/dash1.svg" alt="img" /></span>
            </div>
            <div class="dash-widgetcontent">
              <h5>{{ formatCurrencySymbol(totalPurchaseDueMinor) }}</h5>
              <h6>Total Purchase Due</h6>
            </div>
          </div>
        </div>

        <div class="col-lg-3 col-sm-6 col-12">
          <div class="dash-widget dash1">
            <div class="dash-widgetimg">
              <span><img src="assets/img/icons/dash2.svg" alt="img" /></span>
            </div>
            <div class="dash-widgetcontent">
              <h5>{{ formatCurrencySymbol(totalSalesDueMinor) }}</h5>
              <h6>Total Sales Due</h6>
            </div>
          </div>
        </div>

        <div class="col-lg-3 col-sm-6 col-12">
          <div class="dash-widget dash2">
            <div class="dash-widgetimg">
              <span><img src="assets/img/icons/dash3.svg" alt="img" /></span>
            </div>
            <div class="dash-widgetcontent">
              <h5>{{ formatCurrencySymbol(totalSaleAmountMinor) }}</h5>
              <h6>Total Sale Amount</h6>
            </div>
          </div>
        </div>

        <div class="col-lg-3 col-sm-6 col-12">
          <div class="dash-widget dash3">
            <div class="dash-widgetimg">
              <span><img src="assets/img/icons/dash4.svg" alt="img" /></span>
            </div>
            <div class="dash-widgetcontent">
              <h5>{{ formatCurrencySymbol(anotherSaleMinor) }}</h5>
              <h6>Total Sale Amount</h6>
            </div>
          </div>
        </div>

        <div class="col-lg-3 col-sm-6 col-12 d-flex">
          <div class="dash-count">
            <div class="dash-counts">
              <h4>{{ formatNumber(customersCount) }}</h4>
              <h5>Customers</h5>
            </div>
            <div class="dash-imgs"><i data-feather="user"></i></div>
          </div>
        </div>

        <div class="col-lg-3 col-sm-6 col-12 d-flex">
          <div class="dash-count das1">
            <div class="dash-counts">
              <h4>{{ formatNumber(totalSuppliers) }}</h4>
              <h5>Suppliers</h5>
            </div>
            <div class="dash-imgs"><i data-feather="user-check"></i></div>
          </div>
        </div>

        <div class="col-lg-3 col-sm-6 col-12 d-flex">
          <div class="dash-count das2">
            <div class="dash-counts">
              <h4>{{ formatNumber(purchaseInvCnt) }}</h4>
              <h5>Purchase Invoice</h5>
            </div>
            <div class="dash-imgs"><i data-feather="file-text"></i></div>
          </div>
        </div>

        <div class="col-lg-3 col-sm-6 col-12 d-flex">
          <div class="dash-count das3">
            <div class="dash-counts">
              <h4>{{ formatNumber(salesInvCnt) }}</h4>
              <h5>Sales Invoice</h5>
            </div>
            <div class="dash-imgs"><i data-feather="file"></i></div>
          </div>
        </div>
      </div>

      <!-- Charts section (keeping existing) -->
      <div class="row">
        <div class="col-lg-7 col-sm-12 col-12 d-flex">
          <div class="card flex-fill">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0">Purchase & Sales</h5>
              <div class="graph-sets">
                <ul>
                  <li><span>Sales</span></li>
                  <li><span>Purchase</span></li>
                </ul>
                <div class="dropdown">
                  <button class="btn btn-white btn-sm dropdown-toggle" type="button" id="dropdownMenuButton"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    2022 <img src="assets/img/icons/dropdown.svg" alt="img" class="ms-2" />
                  </button>
                  <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                    <li><a href="javascript:void(0);" class="dropdown-item">2022</a></li>
                    <li><a href="javascript:void(0);" class="dropdown-item">2021</a></li>
                    <li><a href="javascript:void(0);" class="dropdown-item">2020</a></li>
                  </ul>
                </div>
              </div>
            </div>
            <div class="card-body">
              <VueApexCharts type="bar" height="350" :options="chartOptions" :series="series" />
            </div>
          </div>
        </div>

        <div class="col-lg-5 col-sm-12 col-12 d-flex">
          <div class="card flex-fill">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center">
              <h4 class="card-title mb-0">Recently Order Items</h4>
              <div class="dropdown">
                <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false" class="dropset">
                  <i class="fa fa-ellipsis-v"></i>
                </a>
                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                  <li><a href="/productlist" class="dropdown-item">Product List</a></li>
                  <li><a href="/addproduct" class="dropdown-item">Add Product</a></li>
                </ul>
              </div>
            </div>

            <div class="card-body">
              <div class="table-responsive dataview">
                <table class="table datatable">
                  <thead>
                    <tr>
                      <th>Sno</th>
                      <th>Product</th>
                      <th>Qty</th>
                      <th>Price</th>
                      <th>Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="!props.recentItems.length">
                      <td colspan="5" class="text-center text-muted py-3">
                        No recent products found
                      </td>
                    </tr>

                    <tr v-for="(item, idx) in props.recentItems" :key="idx">
                      <td>{{ idx + 1 }}</td>
                      <td>{{ item.title }}</td>
                      <td>{{ item.quantity }}</td>
                      <td>{{ item.price }}</td>
                      <td>{{ item.total }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-0">
        <div class="card-body">
          <h4 class="card-title">Expired Products</h4>
          <div class="table-responsive dataview">
            <table class="table datatable">
              <thead>
                <tr>
                  <th>SNo</th>
                  <th>Product Code</th>
                  <th>Product Name</th>
                  <th>Brand Name</th>
                  <th>Category Name</th>
                  <th>Expiry Date</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>1</td>
                  <td><a href="javascript:void(0);">IT0001</a></td>
                  <td class="productimgname">
                    <a class="product-img" href="productlist.html">
                      <img src="assets/img/product/product2.jpg" alt="product" />
                    </a>
                    <a href="productlist.html">Orange</a>
                  </td>
                  <td>N/D</td>
                  <td>Fruits</td>
                  <td>{{ dateFmt('03-12-2022') }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Inventory Alert Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col">

        <button @click="dismissReminder"
          class="absolute top-3 right-3 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
          aria-label="Close" title="Close">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24"
            stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>

        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-100 text-center">
          <h2 class="text-xl font-semibold text-gray-800 flex justify-center items-center gap-2">
            <i class="bi bi-box-seam text-2xl text-indigo-600"></i>
            Inventory Stock alert!
          </h2>
          <p class="text-sm mt-1 text-gray-600">
            Stock issues require attention
          </p>
        </div>

        <!-- Reminder Picker (shown when user clicks remind me) -->
        <div v-if="showReminderPicker" class="bg-gray-100 border-2 border-green-300 rounded-xl p-5 m-4">
          <h3 class="text-lg font-bold mb-3 flex items-center gap-2">
            <i class="bi bi-alarm-fill" style="color: #1C0D82 !important;"></i>
            Set Reminder
          </h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
              <VueDatePicker v-model="reminderDate" :format="dateFmt" :min-date="new Date()" teleport="body"
                :enableTimePicker="false" placeholder="Select date" />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Time</label>

              <VueDatePicker v-model="reminderTime" time-picker :format="timeFormat" :is24="is24Hour" teleport="body"
                placeholder="Select time" />

            </div>
          </div>
          <div class="flex gap-2">
            <button @click="setReminder"
              class="btn btn-primary px-4 py-1  text-white rounded-pill hover:bg-green-700 font-medium">
              Save
            </button>
            <button @click="showReminderPicker = false"
              class="btn btn-secondary px-4 py-1  text-gray-700 rounded-pill hover:bg-gray-300 font-medium">
              Cancel
            </button>
          </div>
        </div>

        <!-- Summary Tabs -->
        <div v-if="!showReminderPicker" class="flex justify-center gap-3 py-4 border-b border-gray-100">
          <div
            class="flex items-center gap-1 bg-yellow-50 border border-yellow-200 text-yellow-700 text-sm px-3 py-1 rounded-full font-medium">
            <i class="bi bi-exclamation-triangle"></i>
            <span class="low-stock">Low Stock</span>
            <span class="count-low-stock">({{ inventoryDetails?.lowStock || 0 }})</span>
          </div>
          <div
            class="flex items-center gap-1 bg-red-50 border border-red-200 text-red-700 text-sm px-3 py-1 rounded-full font-medium">
            <i class="bi bi-exclamation-octagon"></i>
            <span class="expire">Expired</span>
            <span class="count-expired">({{ inventoryDetails?.expired || 0 }})</span>
          </div>
          <div
            class="flex items-center gap-1 bg-amber-50 border border-amber-200 text-amber-700 text-sm px-3 py-1 rounded-full font-medium">
            <i class="bi bi-clock-history"></i>
            <span class="near-expire">Near Expiry</span>
            <span class="near-expire-count">({{ inventoryDetails?.nearExpiry || 0 }})</span>
          </div>
        </div>

        <!-- Alerts List -->
        <div v-if="!showReminderPicker" class="p-6 space-y-5 overflow-y-auto max-h-[60vh]">

          <!-- Expired Products -->
          <div v-if="inventoryDetails?.expired > 0" class="border rounded-2xl p-4 shadow-sm flex flex-col gap-2">
            <div class="flex items-center justify-between">
              <div class="flex items-start gap-3">
                <div class="text-red-600 p-2 rounded-lg">
                  <i class="bi bi-exclamation-octagon text-xl"></i>
                </div>
                <div>
                  <div class="flex items-center gap-2">
                    <h4 class="text-gray-800">Expired Products</h4>
                    <span class="bg-red-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">URGENT</span>
                  </div>
                  <p class="text-sm text-gray-500 mt-0.5">
                    {{ inventoryDetails.expired }} items
                  </p>
                </div>
              </div>
              <span class="text-gray-500 font-medium">{{ inventoryDetails.expired }}</span>
            </div>
            <ul class="text-sm space-y-1 mt-2 pl-2">
              <li v-for="(item, index) in inventoryDetails.expiredItems" :key="`exp-${index}`"
                class="flex items-center gap-2 text-gray-700">
                <span class="text-red-500 text-xs">•</span> {{ item }}
              </li>
            </ul>
          </div>

          <!-- Low Stock -->
          <div v-if="inventoryDetails?.lowStock > 0" class="border rounded-2xl p-4 shadow-sm flex flex-col gap-2">
            <div class="flex items-center justify-between">
              <div class="flex items-start gap-3">
                <div class="text-yellow-600 p-2 rounded-lg">
                  <i class="bi bi-exclamation-triangle text-xl"></i>
                </div>
                <div>
                  <div class="flex items-center gap-2">
                    <h4 class="text-gray-800">Low Stock Items</h4>
                  </div>
                  <p class="text-sm text-gray-500 mt-0.5">
                    {{ inventoryDetails.lowStock }} items
                  </p>
                </div>
              </div>
              <span class="text-gray-500 font-medium">{{ inventoryDetails.lowStock }}</span>
            </div>
            <ul class="text-sm space-y-1 mt-2 pl-2">
              <li v-for="(item, index) in inventoryDetails.lowStockItems" :key="`low-${index}`"
                class="flex items-center gap-2 text-gray-700">
                <span class="text-yellow-500 text-xs">•</span> {{ item }}
              </li>
            </ul>
          </div>

          <!-- Near Expiry -->
          <div v-if="inventoryDetails?.nearExpiry > 0" class="border rounded-2xl p-4 shadow-sm flex flex-col gap-2">
            <div class="flex items-center justify-between">
              <div class="flex items-start gap-3">
                <div class="text-amber-600 p-2 rounded-lg">
                  <i class="bi bi-clock-history text-xl"></i>
                </div>
                <div>
                  <div class="flex items-center gap-2">
                    <h4 class="text-gray-800">Near Expiry Products</h4>
                  </div>
                  <p class="text-sm text-gray-500 mt-0.5">
                    {{ inventoryDetails.nearExpiry }} items
                  </p>
                </div>
              </div>
              <span class="text-gray-500 font-medium">{{ inventoryDetails.nearExpiry }}</span>
            </div>
            <ul class="text-sm space-y-1 mt-2 pl-2">
              <li v-for="(item, index) in inventoryDetails.nearExpiryItems" :key="`near-${index}`"
                class="flex items-center gap-2 text-gray-700">
                <span class="text-amber-500 text-xs">•</span> {{ item }}
              </li>
            </ul>
          </div>

          <!-- No Alerts -->
          <div v-if="!hasAnyAlerts" class="text-center py-6">
            <div class="flex justify-center mb-2">
              <i class="bi bi-check-circle-fill text-green-600 text-5xl  p-3 rounded-full"></i>
            </div>
            <p class="font-medium text-gray-800">All Clear!</p>
            <p class="text-sm text-gray-500">Your inventory is healthy and up-to-date.</p>
          </div>

        </div>

        <!-- Footer -->
        <div v-if="!showReminderPicker" class="flex justify-between border-t border-gray-100 bg-gray-50 px-6 py-3">
          <button @click="openReminderPicker"
            class="btn btn-primary text-white px-4 py-1 rounded-pill hover:bg-indigo-800 transition">
            Remind Me
          </button>
          <button @click="goToInventory"
            class="btn btn-primary text-white py-1 px-4 rounded-pill hover:bg-blue-800 transition">
            Manage Inventory
          </button>
          <button @click="dismissReminder"
            class="btn btn-secondary py-1 px-4 rounded-pill hover:bg-gray-300 transition">
            Dismiss
          </button>
        </div>
      </div>
    </div>

  </Master>
</template>

<style scoped>
.dark .text-gray-700 {
  color: #fff !important;
}

.dark .bi-alarm-fill {
  color: #fff !important;
}

.dark .bg-gray-100 {
  background-color: #181818 !important;
  border: 1px solid #fff !important;
}

.dark .text-gray-600 {
  color: #fff !important;
}

.dark .text-gray-500 {
  color: #fff !important;
}

.dark .border-yellow-200 {
  background-color: #f5ed95 !important;
  color: #181818 !important
}

.dark .low-stock {
  color: #181818 !important
}

.dark .count-low-stock {
  color: #181818 !important;
}

.dark .bg-red-50 {
  background-color: #fdd8d8;
}

.dark .count-expired {
  color: #181818 !important;
}

.dark .expire {
  color: #181818 !important;
}

.dark .bg-amber-50 {
  background-color: #f3e8bb !important;
  color: #181818 !important;
}

.dark .near-expire-count {
  color: #181818 !important;
}

.dark .near-expire {
  color: #181818 !important;
}
</style>