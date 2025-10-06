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

    <div v-if="showModal" 
         class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[85vh] overflow-hidden flex flex-col">
        
        <!-- Header -->
        <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <span class="text-3xl">📦</span>
                Inventory Alerts
              </h2>
              <p class="text-sm text-gray-600 mt-1">
                Review and take action on items that need attention
              </p>
            </div>
            <button @click="dismissReminder"
                    class="text-gray-500 hover:text-gray-700 transition-colors text-xl font-bold">
              ✕
            </button>
          </div>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto px-8 py-5 space-y-6">
          
          <!-- Reminder Picker (shown when user clicks remind me) -->
          <div v-if="showReminderPicker" class="bg-green-50 border-2 border-green-300 rounded-xl p-5 mb-4">
            <h3 class="text-lg font-bold text-green-800 mb-3 flex items-center gap-2">
              ⏰ Set Reminder
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input 
                  type="date" 
                  v-model="reminderDate"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Time</label>
                <input 
                  type="time" 
                  v-model="reminderTime"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                />
              </div>
            </div>
            <div class="flex gap-2">
              <button @click="setReminder"
                      class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">
                Save Reminder
              </button>
              <button @click="showReminderPicker = false"
                      class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-medium">
                Cancel
              </button>
            </div>
          </div>

          <!-- Summary Cards -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-center hover:shadow-sm transition">
              <div class="text-4xl font-extrabold text-red-700">{{ inventoryDetails?.outOfStock || 0 }}</div>
              <div class="text-sm text-red-600 font-semibold mt-1 uppercase tracking-wide">Out of Stock</div>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-center hover:shadow-sm transition">
              <div class="text-4xl font-extrabold text-yellow-700">{{ inventoryDetails?.lowStock || 0 }}</div>
              <div class="text-sm text-yellow-600 font-semibold mt-1 uppercase tracking-wide">Low Stock</div>
            </div>

            <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 text-center hover:shadow-sm transition">
              <div class="text-4xl font-extrabold text-gray-700">{{ inventoryDetails?.expired || 0 }}</div>
              <div class="text-sm text-gray-600 font-semibold mt-1 uppercase tracking-wide">Expired</div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-center hover:shadow-sm transition">
              <div class="text-4xl font-extrabold text-blue-700">{{ inventoryDetails?.nearExpiry || 0 }}</div>
              <div class="text-sm text-blue-600 font-semibold mt-1 uppercase tracking-wide">Expiring Soon</div>
            </div>
          </div>

          <!-- Alerts List -->
          <div v-if="hasAnyAlerts" class="space-y-6">
            
            <!-- Out of Stock -->
            <div v-if="inventoryDetails?.outOfStock > 0">
              <div class="flex items-center justify-between mb-2 bg-red-100 px-4 py-2 rounded-lg">
                <h3 class="font-bold text-red-700 flex items-center gap-2">🚨 Out of Stock Items</h3>
                <span class="bg-red-600 text-white text-xs font-semibold px-2.5 py-1 rounded-full">
                  {{ inventoryDetails.outOfStock }}
                </span>
              </div>
              <ul class="space-y-1">
                <li v-for="(item, index) in inventoryDetails.outOfStockItems" :key="`out-${index}`"
                    class="bg-red-50 border-l-4 border-red-500 text-red-800 px-4 py-2 rounded-r-md text-sm hover:bg-red-100 transition-colors">
                  {{ item }}
                </li>
              </ul>
            </div>

            <!-- Low Stock -->
            <div v-if="inventoryDetails?.lowStock > 0">
              <div class="flex items-center justify-between mb-2 bg-yellow-100 px-4 py-2 rounded-lg">
                <h3 class="font-bold text-yellow-700 flex items-center gap-2">⚠️ Low Stock Items</h3>
                <span class="bg-yellow-600 text-white text-xs font-semibold px-2.5 py-1 rounded-full">
                  {{ inventoryDetails.lowStock }}
                </span>
              </div>
              <ul class="space-y-1">
                <li v-for="(item, index) in inventoryDetails.lowStockItems" :key="`low-${index}`"
                    class="bg-yellow-50 border-l-4 border-yellow-500 text-yellow-800 px-4 py-2 rounded-r-md text-sm hover:bg-yellow-100 transition-colors">
                  {{ item }}
                </li>
              </ul>
            </div>

            <!-- Expired -->
            <div v-if="inventoryDetails?.expired > 0">
              <div class="flex items-center justify-between mb-2 bg-gray-100 px-4 py-2 rounded-lg">
                <h3 class="font-bold text-gray-700 flex items-center gap-2">❌ Expired Products</h3>
                <span class="bg-gray-600 text-white text-xs font-semibold px-2.5 py-1 rounded-full">
                  {{ inventoryDetails.expired }}
                </span>
              </div>
              <ul class="space-y-1">
                <li v-for="(item, index) in inventoryDetails.expiredItems" :key="`exp-${index}`"
                    class="bg-gray-50 border-l-4 border-gray-400 text-gray-700 px-4 py-2 rounded-r-md text-sm hover:bg-gray-100 transition-colors">
                  {{ item }}
                </li>
              </ul>
            </div>

            <!-- Near Expiry -->
            <div v-if="inventoryDetails?.nearExpiry > 0">
              <div class="flex items-center justify-between mb-2 bg-blue-100 px-4 py-2 rounded-lg">
                <h3 class="font-bold text-blue-700 flex items-center gap-2">⏳ Expiring Soon</h3>
                <span class="bg-blue-600 text-white text-xs font-semibold px-2.5 py-1 rounded-full">
                  {{ inventoryDetails.nearExpiry }}
                </span>
              </div>
              <ul class="space-y-1">
                <li v-for="(item, index) in inventoryDetails.nearExpiryItems" :key="`near-${index}`"
                    class="bg-blue-50 border-l-4 border-blue-500 text-blue-800 px-4 py-2 rounded-r-md text-sm hover:bg-blue-100 transition-colors">
                  {{ item }}
                </li>
              </ul>
            </div>
          </div>

          <!-- No Alerts -->
          <div v-else class="text-center py-10">
            <div class="text-6xl mb-4">✅</div>
            <h3 class="text-lg font-semibold text-gray-700 mb-1">All Clear!</h3>
            <p class="text-gray-500">Your inventory is healthy and up-to-date.</p>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-3">
          <button @click="dismissReminder"
                  class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 font-medium">
            Dismiss
          </button>
          <button @click="openReminderPicker"
                  class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium flex items-center gap-2">
            <span>⏰</span> Remind Me Later
          </button>
          <button @click="goToInventory"
                  class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium flex items-center gap-2">
            <span>📋</span> Manage Inventory
          </button>
        </div>

      </div>
    </div>


  </Master>
</template>
