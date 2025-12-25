<script setup>
import Master from "@/Layouts/Master.vue";
import { Head } from "@inertiajs/vue3";
import VueApexCharts from "vue3-apexcharts";
import { onMounted, ref } from "vue";
import { useFormatters } from '@/composables/useFormatters'
import { toast } from "vue3-toastify";

const { formatMoney, formatCurrencySymbol, formatNumber, dateFmt } = useFormatters();

const props = defineProps({
  recentItems: {
    type: Array,
    default: () => []
  },
  totalCash: {
    type: Number,
    default: 0
  },
  totalCard: {
    type: Number,
    default: 0
  },
  totalPendingPurchases: {
    type: Number,
    default: 0
  },
  // Payments over different time frames
  totalPayments: {
    type: Number,
    default: 0
  },
  todayPayments: {
    type: Number,
    default: 0
  },
  threeDaysPayments: {
    type: Number,
    default: 0
  },
  sevenDaysPayments: {
    type: Number,
    default: 0
  },
  monthPayments: {
    type: Number,
    default: 0
  },
  yearPayments: {
    type: Number,
    default: 0
  },
  // Orders over different time frames
  totalOrders: {
    type: Number,
    default: 0
  },
  todayOrders: {
    type: Number,
    default: 0
  },
  threeDaysOrders: {
    type: Number,
    default: 0
  },
  sevenDaysOrders: {
    type: Number,
    default: 0
  },
  yearOrders: {
    type: Number,
    default: 0
  },
  // Total Orders Average
  totalOrderAverage: {
    type: Number,
    default: 0
  },
  todayOrderAverage: {
    type: Number,
    default: 0
  },
  threeDaysOrderAverage: {
    type: Number,
    default: 0
  },
  sevenDaysOrderAverage: {
    type: Number,
    default: 0
  },
  monthOrderAverage: {
    type: Number,
    default: 0
  },
  yearOrderAverage: {
    type: Number,
    default: 0
  },
  // Purchased totals over different time frames
  totalPurchaseCompleted: {
    type: Number,
    default: 0
  },
  todayPurchaseCompleted: {
    type: Number,
    default: 0
  },
  threeDaysPurchaseCompleted: {
    type: Number,
    default: 0
  },
  sevenDaysPurchaseCompleted: {
    type: Number,
    default: 0
  },
  monthPurchaseCompleted: {
    type: Number,
    default: 0
  },
  yearPurchaseCompleted: {
    type: Number,
    default: 0
  },
  // Suppliers over different time frames
  totalSuppliers: {
    type: Number,
    default: 0
  },
  todaySuppliers: {
    type: Number,
    default: 0
  },
  threeDaysSuppliers: {
    type: Number,
    default: 0
  },
  sevenDaysSuppliers: {
    type: Number,
    default: 0
  },
  monthSuppliers: {
    type: Number,
    default: 0
  },
  yearSuppliers: {
    type: Number,
    default: 0
  },
  // Daily Purchases and Sales for the current month
  dailyPurchases: {
    type: Array,
    default: () => []
  },
  dailySales: {
    type: Array,
    default: () => []
  },
  availableYears: {
    type: Array,
    default: () => []
  },
  selectedYear: {
    type: Number,
    default: new Date().getFullYear()
  },
  monthlySales: {
    type: Array,
    default: () => []
  },
  monthlyPurchases: {
    type: Array,
    default: () => []
  },
})

const series = computed(() => {
  // Ensure all values are numbers
  const salesData = props.monthlySales.map(val => parseFloat(val) || 0);
  const purchasesData = props.monthlyPurchases.map(val => parseFloat(val) || 0);
  
  return [
    {
      name: "Sales",
      data: salesData,
    },
    {
      name: "Purchases",
      data: purchasesData,
    },
  ];
});
const chartOptions = {
  chart: {
    type: "line",
    height: 350,
    toolbar: { show: true },
    zoom: { enabled: false },
  },
  stroke: {
    curve: "smooth",
    width: 3,
  },
  colors: ["#65FA9E", "#EA5455"],
  dataLabels: {
    enabled: false,
  },
  markers: {
    size: 4,
    hover: { sizeOffset: 3 },
  },
  xaxis: {
    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    title: { text: "Month" },
    axisBorder: { show: true },
    axisTicks: { show: true },
    labels: {
      rotate: 0,
      style: {
        fontSize: '12px'
      }
    }
  },
  yaxis: {
    title: { text: "Amount" },
    labels: { 
      formatter: (val) => {
        return val ? val.toFixed(0) : '0';
      }
    },
    min: 0
  },
  tooltip: {
    shared: true,
    intersect: false,
    y: { 
      formatter: (val) => val ? `${formatCurrencySymbol(val.toFixed(2))}` : '0'
    },
  },
  legend: {
    position: "top",
    horizontalAlign: "left",
    markers: { 
      radius: 12,
      width: 12,
      height: 12
    },
  },
  grid: { 
    borderColor: "#e0e0e0", 
    strokeDashArray: 4,
    row: {
      colors: ['transparent', 'transparent'],
      opacity: 0.5
    },
  },
};



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

  if (showModal.value && hasAnyAlerts) {
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
const goToInventory = () => {
  localStorage.removeItem('inventory_reminder')
  window.location.href = '/inventory'
}
const is24Hour = computed(() => page?.props?.onboarding?.currency_and_locale?.time_format === '24-hour')
const timeFormat = computed(() => (is24Hour.value ? 'HH:mm' : 'hh:mm a'))
const openReminderPicker = () => {
  showReminderPicker.value = true
  const now = new Date()
  reminderDate.value = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  reminderTime.value = {
    hours: now.getHours(),
    minutes: now.getMinutes()
  }
}

const setReminder = () => {
  if (!reminderDate.value || !reminderTime.value) {
    alert('Please select both date and time')
    return
  }

  try {
    const date = new Date(reminderDate.value)
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    const hours = String(reminderTime.value.hours).padStart(2, '0')
    const minutes = String(reminderTime.value.minutes).padStart(2, '0')
    const datetimeString = `${year}-${month}-${day}T${hours}:${minutes}:00`
    const datetime = new Date(datetimeString)
    const now = new Date()
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
    localStorage.setItem('inventory_reminder', JSON.stringify(reminderData))
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
const selectedTimeFilter = ref('all');

const timeFilters = [
  { value: 'all', label: 'All', icon: 'bi-infinity' },
  { value: 'today', label: 'Today', icon: 'bi-calendar-day' },
  { value: '3d', label: '3D', icon: 'bi-calendar-day' },
  { value: '7d', label: '7D', icon: 'bi-calendar-week' },
  { value: '1y', label: '1Y', icon: 'bi-calendar-range' }
];

const setTimeFilter = (filter) => {
  selectedTimeFilter.value = filter;
};
const filteredPayments = computed(() => {
  switch (selectedTimeFilter.value) {
    case 'today':
      return props.todayPayments;
    case '3d':
      return props.threeDaysPayments;
    case '7d':
      return props.sevenDaysPayments;
    case '1y':
      return props.yearPayments;
    case 'all':
    default:
      return props.totalPayments;
  }
});

const filteredOrders = computed(() => {
  switch (selectedTimeFilter.value) {
    case 'today':
      return props.todayOrders;
    case '3d':
      return props.threeDaysOrders;
    case '7d':
      return props.sevenDaysOrders;
    case '1y':
      return props.yearOrders;
    case 'all':
    default:
      return props.totalOrders;
  }
});

// Order Averages
const orderAverages = computed(() => {
  switch (selectedTimeFilter.value) {
    case 'today':
      return props.todayOrderAverage ?? 0;
    case '3d':
      return props.threeDaysOrderAverage ?? 0;
    case '7d':
      return props.sevenDaysOrderAverage ?? 0;
    case '1y':
      return props.yearOrderAverage ?? 0;
    case 'all':
    default:
      return props.totalOrderAverage ?? 0;
  }
});

// Purchase Completed
const purchaseCompleted = computed(() => {
  switch (selectedTimeFilter.value) {
    case 'today':
      return props.todayPurchaseCompleted;
    case '3d':
      return props.threeDaysPurchaseCompleted;
    case '7d':
      return props.sevenDaysPurchaseCompleted;
    case '1y':
      return props.yearPurchaseCompleted;
    case 'all':
    default:
      return props.totalPurchaseCompleted;
  }
});

// Suppliers Count
const suppliersCount = computed(() => {
  switch (selectedTimeFilter.value) {
    case 'today':
      return props.todaySuppliers;
    case '3d':
      return props.threeDaysSuppliers;
    case '7d':
      return props.sevenDaysSuppliers;
    case '1y':
      return props.yearSuppliers;
    case 'all':
    default:
      return props.totalSuppliers;
  }
});

const changeYear = (year) => {
  router.get(route('dashboard'), { year: year }, {
    preserveState: true,
    preserveScroll: true,
  });
};

</script>

<template>

  <Head title="Dashboard" />

  <Master>
    <div class="page-wrapper">
      <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div class="d-flex gap-2 mb-2 mb-md-0">
          <button v-for="filter in timeFilters" :key="filter.value" @click="setTimeFilter(filter.value)" :class="[
            'btn',
            'rounded-pill',
            'px-4',
            'py-2',
            'd-flex',
            'align-items-center',
            'gap-2',
            'transition-all',
            'fw-medium',
            selectedTimeFilter === filter.value
              ? 'btn-primary shadow-sm scale-active'
              : 'btn-outline-secondary hover-lift'
          ]" style="min-width: 90px;">
            <i :class="filter.icon" class="fs-6"></i>
            <span>{{ filter.label }}</span>
          </button>
        </div>


      </div>
      <div class="row">
        <!-- Dashboard cards (keeping your existing structure) -->
        <div class="col-lg-3 col-sm-6 col-12">
          <div class="dash-widget">
            <div class="dash-widgetimg">
              <span><img src="assets/img/icons/dash1.svg" alt="img" /></span>
            </div>
            <div class="dash-widgetcontent">
              <!-- Payment amount -->
              <h5>{{ formatCurrencySymbol(filteredPayments) }}</h5>
              <h6>Total Income</h6>
            </div>
          </div>
        </div>

        <div class="col-lg-3 col-sm-6 col-12">
          <div class="dash-widget dash1">
            <div class="dash-widgetimg">
              <span><img src="assets/img/icons/dash2.svg" alt="img" /></span>
            </div>
            <div class="dash-widgetcontent">
              <h5>{{ filteredOrders }}</h5>
              <h6>Total Orders</h6>
            </div>
          </div>
        </div>

        <div class="col-lg-3 col-sm-6 col-12">
          <div class="dash-widget dash2">
            <div class="dash-widgetimg">
              <span><img src="assets/img/icons/dash3.svg" alt="img" /></span>
            </div>
            <div class="dash-widgetcontent">
              <h5>{{ formatCurrencySymbol(formatNumber(orderAverages)) }}</h5>
              <h6>Orders Average</h6>
            </div>
          </div>
        </div>

        <div class="col-lg-3 col-sm-6 col-12">
          <div class="dash-widget dash3">
            <div class="dash-widgetimg">
              <span><img src="assets/img/icons/dash4.svg" alt="img" /></span>
            </div>
            <div class="dash-widgetcontent">
              <h5>{{ formatCurrencySymbol(purchaseCompleted) }}</h5>
              <h6>Total Purchase</h6>
            </div>
          </div>
        </div>

        <div class="col-lg-3 col-sm-6 col-12 d-flex">
          <div class="dash-count">
            <div class="dash-counts">
              <h4>{{ formatNumber(suppliersCount) }}</h4>
              <h5>Suppliers</h5>
            </div>
            <div class="dash-imgs"><i data-feather="user"></i></div>
          </div>
        </div>

        <div class="col-lg-3 col-sm-6 col-12 d-flex">
          <div class="dash-count das1">
            <div class="dash-counts">
              <h4>{{ formatCurrencySymbol(formatNumber(totalCash)) }}</h4>
              <h5>Cash Payments</h5>
            </div>
            <div class="dash-imgs"><i data-feather="user-check"></i></div>
          </div>
        </div>

        <div class="col-lg-3 col-sm-6 col-12 d-flex">
          <div class="dash-count das2">
            <div class="dash-counts">
              <h4>{{ formatCurrencySymbol(formatNumber(totalCard)) }}</h4>
              <h5>Card Payments</h5>
            </div>
            <div class="dash-imgs"><i data-feather="file-text"></i></div>
          </div>
        </div>

        <div class="col-lg-3 col-sm-6 col-12 d-flex">
          <div class="dash-count das3">
            <div class="dash-counts">
              <h4>{{ formatCurrencySymbol(formatNumber(totalPendingPurchases)) }}</h4>
              <h5>Pending Purchase Amount</h5>
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
                    {{ selectedYear }}
                    <img src="assets/img/icons/dropdown.svg" alt="img" class="ms-2" />
                  </button>
                  <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                    <li v-for="year in availableYears" :key="year">
                      <a href="javascript:void(0);" class="dropdown-item" :class="{ 'active': year === selectedYear }"
                        @click="changeYear(year)">
                        {{ year }}
                      </a>
                    </li>
                    <li v-if="!availableYears.length">
                      <span class="dropdown-item text-muted">No data available</span>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
            <div class="card-body">
              <VueApexCharts type="line" height="350" :options="chartOptions" :series="series" />
            </div>
          </div>
        </div>
        <!-- Top Selling Items -->
        <div class="col-lg-5 col-sm-12 col-12 d-flex">
          <div class="card flex-fill">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center">
              <h4 class="card-title mb-0">Top Selling Items</h4>
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
                        No recent items found
                      </td>
                    </tr>

                    <tr v-for="(item, idx) in props.recentItems" :key="idx">
                      <td>{{ idx + 1 }}</td>
                      <td>{{ item.title }}</td>
                      <td>{{ item.quantity }}</td>
                      <td>{{ formatCurrencySymbol(formatNumber(item.price)) }}</td>
                      <td>{{ formatCurrencySymbol(formatNumber(item.total)) }}</td>
                    </tr>

                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-5 col-sm-6 col-12">
        <div class="dash-widget dash2">
          <div class="dash-widgetimg">
            <span><img src="assets/img/icons/dash3.svg" alt="img" /></span>
          </div>
          <div class="dash-widgetcontent">
            <h5>Inventory Alerts</h5>

            <div class="badge-container d-flex flex-wrap gap-1 mt-2">
              <span class="badge bg-danger">
                Out of Stock: {{ page.props.inventoryAlerts?.outOfStock ?? 0 }}
              </span>
              <span class="badge bg-warning text-dark">
                Low Stock: {{ page.props.inventoryAlerts?.lowStock ?? 0 }}
              </span>
              <span class="badge bg-secondary">
                Expired: {{ page.props.inventoryAlerts?.expired ?? 0 }}
              </span>
              <span class="badge bg-info text-dark">
                Near Expiry: {{ page.props.inventoryAlerts?.nearExpiry ?? 0 }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div v-if="showModal"
      class="inventory-stock-alert fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
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
        <div v-if="showReminderPicker" class="bg-gray-100 border-2 border-green-300 rounded-xl p-4 m-4">
          <h3 class="text-lg font-bold mb-3 flex items-center justify-center gap-2 text-center">
            <i class="bi bi-alarm-fill" style="color: #1C0D82 !important;"></i>
            Set Reminder
          </h3>


          <div class="grid grid-cols-1 md:grid-cols-1 gap-4 mb-4">
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
        <div v-if="!showReminderPicker"
          class="flex justify-between border-t border-gray-100 bg-gray-50 px-6 py-3 mt-auto"
          :class="{ 'justify-end': !hasAnyAlerts }">
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

.dark .inventory-stock-alert {
  border: 1px solid #fff !important;

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


.dark .btn-white {
  background-color: #181818 !important;
  border: 1px solid #fff !important;
  color: #fff !important;
}

.dark .text-gray-500 {
  color: #fff !important;
}

.dark a:hover {
  color: #fff !important;
  background-color: #212121 !important;
}

.dark .bg-white {
  border: 1px solid #fff !important;
  height: 80vh;
  margin-top: 50px;
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
:global(.dark .form-control:focus){
    border-color: #fff !important;
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
:deep(.apexcharts-tooltip) {
  background: #1f2937 !important;
  border: 1px solid #374151 !important;
  color: #f9fafb !important;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3) !important;
}

:deep(.apexcharts-tooltip-title) {
  background: #111827 !important;
  border-bottom: 1px solid #374151 !important;
  color: #f9fafb !important;
  padding: 8px !important;
  font-weight: 600 !important;
}

:deep(.apexcharts-tooltip-series-group) {
  background: transparent !important;
  color: #f9fafb !important;
  padding: 4px 8px !important;
}

:deep(.apexcharts-tooltip-text-y-label),
:deep(.apexcharts-tooltip-text-y-value) {
  color: #f9fafb !important;
}

:deep(.apexcharts-tooltip-marker) {
  margin-right: 8px !important;
}

/* Light mode - keep default styles */
:deep(.light .apexcharts-tooltip) {
  background: white !important;
  border: 1px solid #e5e7eb !important;
  color: #1f2937 !important;
}

:deep(.light .apexcharts-tooltip-title) {
  background: #f9fafb !important;
  border-bottom: 1px solid #e5e7eb !important;
  color: #1f2937 !important;
}

:deep(.light .apexcharts-tooltip-text-y-label),
:deep(.light .apexcharts-tooltip-text-y-value) {
  color: #1f2937 !important;
}
</style>