<script setup>
import Master from "@/Layouts/Master.vue";
import { Head } from "@inertiajs/vue3";
import VueApexCharts from "vue3-apexcharts";
import { onMounted, ref } from "vue";
import { useFormatters } from '@/composables/useFormatters'

const { formatMoney, formatNumber, dateFmt } = useFormatters();

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
  colors: ["#65FA9E", "#EA5455"], // green, red
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
    formatter: (val) => Math.abs(val), // Hide minus sign
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
const totalPurchaseDueMinor = 123123123.33      // 307,144.00
const totalSalesDueMinor = 438500        // 4,385.00
const totalSaleAmountMinor = 38565650      // 385,656.50
const anotherSaleMinor = 40000         // 400.00

const customersCount = 1.400
const suppliersCount = 1030
const purchaseInvCnt = 1020
const salesInvCnt = 1435
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

// 🌟 Access backend props through Inertia
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

// Check for active reminder on mount
onMounted(() => {
  checkReminder()

  if (showModal.value) {
    showModal.value = true
  }

  // Check reminder every minute
  setInterval(checkReminder, 60000)
})

const checkReminder = () => {
  const reminderData = localStorage.getItem('inventory_reminder')

  if (reminderData) {
    const { datetime, dismissed } = JSON.parse(reminderData)
    const reminderDateTime = new Date(datetime)
    const now = new Date()

    // Show modal if reminder time has passed and not dismissed
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
  // Clear reminder when user takes action
  localStorage.removeItem('inventory_reminder')
  window.location.href = '/inventory'
}

const openReminderPicker = () => {
  showReminderPicker.value = true

  // Set default to 1 hour from now
  const now = new Date()
  now.setHours(now.getHours() + 1)

  const year = now.getFullYear()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  const day = String(now.getDate()).padStart(2, '0')
  const hours = String(now.getHours()).padStart(2, '0')
  const minutes = String(now.getMinutes()).padStart(2, '0')

  reminderDate.value = `${year}-${month}-${day}`
  reminderTime.value = `${hours}:${minutes}`
}

const setReminder = () => {
  if (!reminderDate.value || !reminderTime.value) {
    alert('Please select both date and time')
    return
  }

  const datetime = new Date(`${reminderDate.value}T${reminderTime.value}`)
  const now = new Date()

  if (datetime <= now) {
    alert('Please select a future date and time')
    return
  }

  // Save reminder to localStorage
  localStorage.setItem('inventory_reminder', JSON.stringify({
    datetime: datetime.toISOString(),
    dismissed: false,
    alerts: inventoryDetails.value
  }))

  alert(`Reminder set for ${datetime.toLocaleString()}`)
  showModal.value = false
  showReminderPicker.value = false
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
        <!-- Total Purchase Due -->
        <div class="col-lg-3 col-sm-6 col-12">
          <div class="dash-widget">
            <div class="dash-widgetimg">
              <span><img src="assets/img/icons/dash1.svg" alt="img" /></span>
            </div>
            <div class="dash-widgetcontent">
              <h5>{{ formatMoney(totalPurchaseDueMinor) }}</h5>
              <h6>Total Purchase Due</h6>
            </div>
          </div>
        </div>

        <!-- Total Sales Due -->
        <div class="col-lg-3 col-sm-6 col-12">
          <div class="dash-widget dash1">
            <div class="dash-widgetimg">
              <span><img src="assets/img/icons/dash2.svg" alt="img" /></span>
            </div>
            <div class="dash-widgetcontent">
              <h5>{{ formatMoney(totalSalesDueMinor) }}</h5>
              <h6>Total Sales Due</h6>
            </div>
          </div>
        </div>

        <!-- Total Sale Amount -->
        <div class="col-lg-3 col-sm-6 col-12">
          <div class="dash-widget dash2">
            <div class="dash-widgetimg">
              <span><img src="assets/img/icons/dash3.svg" alt="img" /></span>
            </div>
            <div class="dash-widgetcontent">
              <h5>{{ formatMoney(totalSaleAmountMinor) }}</h5>
              <h6>Total Sale Amount</h6>
            </div>
          </div>
        </div>

        <!-- Another Amount (rename to what it actually is) -->
        <div class="col-lg-3 col-sm-6 col-12">
          <div class="dash-widget dash3">
            <div class="dash-widgetimg">
              <span><img src="assets/img/icons/dash4.svg" alt="img" /></span>
            </div>
            <div class="dash-widgetcontent">
              <h5>{{ formatMoney(anotherSaleMinor) }}</h5>
              <h6>Total Sale Amount</h6>
            </div>
          </div>
        </div>

        <!-- Counts -->
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
              <h4>{{ formatNumber(suppliersCount) }}</h4>
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

      <!-- Charts + Recently Added Products -->
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
              <h4 class="card-title mb-0">Recently Added Products</h4>
              <div class="dropdown">
                <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false" class="dropset">
                  <i class="fa fa-ellipsis-v"></i>
                </a>
                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                  <li><a href="productlist.html" class="dropdown-item">Product List</a></li>
                  <li><a href="addproduct.html" class="dropdown-item">Product Add</a></li>
                </ul>
              </div>
            </div>

            <div class="card-body">
              <div class="table-responsive dataview">
                <table class="table datatable">
                  <thead>
                    <tr>
                      <th>Sno</th>
                      <th>Products</th>
                      <th>Price</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(p, idx) in recentProducts" :key="p.id">
                      <td>{{ idx + 1 }}</td>
                      <td class="productimgname">
                        <a href="productlist.html" class="product-img">
                          <img :src="p.img" :alt="p.name" />
                        </a>
                        <a href="productlist.html">{{ p.name }}</a>
                      </td>
                      <td>{{ formatMoney(p.priceMinor) }}</td>
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
                  <td>
                    <a href="javascript:void(0);">IT0001</a>
                  </td>
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
                <tr>
                  <td>2</td>
                  <td>
                    <a href="javascript:void(0);">IT0002</a>
                  </td>
                  <td class="productimgname">
                    <a class="product-img" href="productlist.html">
                      <img src="assets/img/product/product3.jpg" alt="product" />
                    </a>
                    <a href="productlist.html">Pineapple</a>
                  </td>
                  <td>N/D</td>
                  <td>Fruits</td>
                  <td>25-11-2022</td>
                </tr>
                <tr>
                  <td>3</td>
                  <td>
                    <a href="javascript:void(0);">IT0003</a>
                  </td>
                  <td class="productimgname">
                    <a class="product-img" href="productlist.html">
                      <img src="assets/img/product/product4.jpg" alt="product" />
                    </a>
                    <a href="productlist.html">Stawberry</a>
                  </td>
                  <td>N/D</td>
                  <td>Fruits</td>
                  <td>19-11-2022</td>
                </tr>
                <tr>
                  <td>4</td>
                  <td>
                    <a href="javascript:void(0);">IT0004</a>
                  </td>
                  <td class="productimgname">
                    <a class="product-img" href="productlist.html">
                      <img src="assets/img/product/product5.jpg" alt="product" />
                    </a>
                    <a href="productlist.html">Avocat</a>
                  </td>
                  <td>N/D</td>
                  <td>Fruits</td>
                  <td>20-11-2022</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

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

        <!-- Summary Tabs -->
        <div class="flex justify-center gap-3 py-4 border-b border-gray-100">
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
        <div class="p-6 space-y-5 overflow-y-auto max-h-[60vh]">

          <!-- Expired Products -->
          <div v-if="inventoryDetails?.expired > 0" class="border rounded-2xl p-4 shadow-sm flex flex-col gap-2">

            <!-- Header: Icon + Title + Badge (in one row) -->
            <div class="flex items-center justify-between">
              <div class="flex items-start gap-3">
                <!-- Icon -->
                <div class=" text-red-600 p-2 rounded-lg">
                  <i class="bi bi-exclamation-octagon text-xl"></i>
                </div>

                <!-- Title + Item count -->
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
              <!-- Total count on right side -->
              <span class="text-gray-500 font-medi">{{ inventoryDetails.expired }}</span>
            </div>
            <!-- Item list -->
            <ul class="text-sm space-y-1 mt-2 pl-2">
              <li v-for="(item, index) in inventoryDetails.expiredItems" :key="`exp-${index}`"
                class="flex items-center gap-2 text-gray-700">
                <span class="text-red-500 text-xs">•</span> {{ item }}
              </li>
            </ul>
          </div>


          <!-- Low Stock -->
          <div v-if="inventoryDetails?.lowStock > 0" class="border rounded-2xl p-4 shadow-sm flex flex-col gap-2">

            <!-- Header: Icon + Title + Count -->
            <div class="flex items-center justify-between">
              <div class="flex items-start gap-3">
                <!-- Icon -->
                <div class="text-yellow-600 p-2 rounded-lg">
                  <i class="bi bi-exclamation-triangle text-xl"></i>
                </div>

                <!-- Title + Item count -->
                <div>
                  <div class="flex items-center gap-2">
                    <h4 class="text-gray-800">Low Stock Items</h4>
                  </div>
                  <p class="text-sm text-gray-500 mt-0.5">
                    {{ inventoryDetails.lowStock }} items
                  </p>
                </div>
              </div>

              <!-- Total count on right side -->
              <span class="text-gray-500 font-medium">{{ inventoryDetails.lowStock }}</span>
            </div>

            <!-- Item list -->
            <ul class="text-sm space-y-1 mt-2 pl-2">
              <li v-for="(item, index) in inventoryDetails.lowStockItems" :key="`low-${index}`"
                class="flex items-center gap-2 text-gray-700">
                <span class="text-yellow-500 text-xs">•</span> {{ item }}
              </li>
            </ul>
          </div>

          <!-- Near Expiry -->
          <div v-if="inventoryDetails?.nearExpiry > 0" class="border rounded-2xl p-4 shadow-sm flex flex-col gap-2">

            <!-- Header: Icon + Title + Count -->
            <div class="flex items-center justify-between">
              <div class="flex items-start gap-3">
                <!-- Icon -->
                <div class="text-amber-600 p-2 rounded-lg">
                  <i class="bi bi-clock-history text-xl"></i>
                </div>

                <!-- Title + Item count -->
                <div>
                  <div class="flex items-center gap-2">
                    <h4 class="text-gray-800">Near Expiry Products</h4>
                  </div>
                  <p class="text-sm text-gray-500 mt-0.5">
                    {{ inventoryDetails.nearExpiry }} items
                  </p>
                </div>
              </div>

              <!-- Total count on right side -->
              <span class="text-gray-500 font-medium">{{ inventoryDetails.nearExpiry }}</span>
            </div>

            <!-- Item list -->
            <ul class="text-sm space-y-1 mt-2 pl-2">
              <li v-for="(item, index) in inventoryDetails.nearExpiryItems" :key="`near-${index}`"
                class="flex items-center gap-2 text-gray-700">
                <span class="text-amber-500 text-xs">•</span> {{ item }}
              </li>
            </ul>
          </div>


          <!-- No Alerts -->
          <div v-if="!hasAnyAlerts" class="text-center py-6">
            <div class="text-4xl mb-2">✅</div>
            <p class="font-medium">All Clear!</p>
            <p class="text-sm text-gray-500">Your inventory is healthy and up-to-date.</p>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex justify-between border-t border-gray-100 bg-gray-50 px-6 py-3">
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