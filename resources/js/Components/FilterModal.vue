<template>
  <div>
    <!-- Filter Button -->
    <button
      class="btn btn-outline-secondary rounded-pill px-4"
      @click="openModal"
    >
      <i class="fas fa-filter me-2"></i>
      Filter
      <span v-if="hasActiveFilters" class="ms-1 badge bg-primary rounded-pill">
        {{ activeFilterCount }}
      </span>
    </button>

    <!-- Filter Modal -->
    <div
      :id="modalId"
      class="modal fade"
      tabindex="-1"
      aria-labelledby="filterModalLabel"
      aria-hidden="true"
    >
      <div class="modal-dialog modal-dialog-centered" :class="modalSize">
        <div class="modal-content rounded-4">
          <div class="modal-header border-0 pb-2">
            <h5 class="modal-title fw-bold" id="filterModalLabel">
              <i class="fas fa-filter text-primary me-2"></i>
              Filter {{ title }}
            </h5>
           <button
                        class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                        title="Close"
                        @click="clearAllFilters"
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

          <div class="modal-body pt-4">
            <div class="row g-3">
              <!-- Sort Section -->
              <div v-if="sortOptions?.length" class="col-12">
                <label class="form-label fw-semibold text-dark">
                  <i class="fas fa-sort me-2 text-muted"></i>Sort By
                </label>
                <div class="row g-2">
                  <div
                    v-for="option in sortOptions"
                    :key="option.value"
                    class="col-6"
                  >
                    <div class="form-check">
                      <input
                        :id="`sort-${option.value}`"
                        v-model="localFilters.sortBy"
                        class="form-check-input"
                        type="radio"
                        :value="option.value"
                      />
                      <label
                        :for="`sort-${option.value}`"
                        class="form-check-label small"
                      >
                        {{ option.label }}
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Category Filter -->
              <div v-if="categories?.length" class="col-md-6">
                <label class="form-label fw-semibold text-dark">
                  <i class="fas fa-tags me-2 text-muted"></i>Category
                </label>
                <select v-model="localFilters.category" class="form-select">
                  <option value="">All Categories</option>
                  <option
                    v-for="category in categories"
                    :key="category.id || category.value"
                    :value="category.id || category.value"
                  >
                    {{ category.name || category.label }}
                  </option>
                </select>
              </div>

              <!-- Stock Status Filter -->
              <div v-if="stockStatusOptions?.length" class="col-md-6">
                <label class="form-label fw-semibold text-dark">
                  <i class="fas fa-boxes me-2 text-muted"></i>Stock Status
                </label>
                <select v-model="localFilters.stockStatus" class="form-select">
                  <option value="">All Status</option>
                  <option
                    v-for="status in stockStatusOptions"
                    :key="status.value"
                    :value="status.value"
                  >
                    {{ status.label }}
                  </option>
                </select>
              </div>

              <!-- Supplier Filter -->
              <div v-if="suppliers?.length" class="col-md-6">
                <label class="form-label fw-semibold text-dark">
                  <i class="fas fa-truck me-2 text-muted"></i>Supplier
                </label>
                <select v-model="localFilters.supplier" class="form-select">
                  <option value="">All Suppliers</option>
                  <option
                    v-for="supplier in suppliers"
                    :key="supplier.id || supplier.value"
                    :value="supplier.id || supplier.value"
                  >
                    {{ supplier.name || supplier.label }}
                  </option>
                </select>
              </div>

              <!-- Price Range -->
              <div v-if="showPriceRange" class="col-md-6">
                <label class="form-label fw-semibold text-dark">
                  <i class="fas fa-dollar-sign me-2 text-muted"></i>Price Range
                </label>
                <div class="row g-2">
                  <div class="col-6">
                    <input
                      v-model.number="localFilters.priceMin"
                      type="number"
                      class="form-control"
                      placeholder="Min"
                      min="0"
                      step="0.01"
                    />
                  </div>
                  <div class="col-6">
                    <input
                      v-model.number="localFilters.priceMax"
                      type="number"
                      class="form-control"
                      placeholder="Max"
                      min="0"
                      step="0.01"
                    />
                  </div>
                </div>
              </div>

              <!-- Date Range -->
              <div v-if="showDateRange" class="col-12">
                <label class="form-label fw-semibold text-dark">
                  <i class="fas fa-calendar-alt me-2 text-muted"></i>Date Range
                </label>
                <div class="row g-2">
                  <div class="col-md-6">
                    <input
                      v-model="localFilters.dateFrom"
                      type="date"
                      class="form-control"
                    />
                  </div>
                  <div class="col-md-6">
                    <input
                      v-model="localFilters.dateTo"
                      type="date"
                      class="form-control"
                    />
                  </div>
                </div>
              </div>

              <!-- Custom Filters Slot -->
              <div v-if="$slots.customFilters" class="col-12">
                <slot name="customFilters" :filters="localFilters"></slot>
              </div>
            </div>

            <!-- Active Filters Display -->
            <div v-if="hasActiveFilters" class="mt-3">
              <small class="text-muted d-block mb-2">Active Filters:</small>
              <div class="d-flex flex-wrap gap-1">
                <span
                  v-for="filter in activeFiltersDisplay"
                  :key="filter.key"
                  class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 rounded-pill"
                >
                  {{ filter.label }}: {{ filter.value }}
                  <i
                    class="fas fa-times ms-1 cursor-pointer"
                    @click="clearSingleFilter(filter.key)"
                  ></i>
                </span>
              </div>
            </div>
          </div>

          <div class="modal-footer border-0 pt-0">
            <div class="w-100 d-flex gap-2">
               <button
                type="button"
                class="px-4 py-2 rounded-pill btn btn-primary text-white text-center"
                @click="applyFilters"
              >
                <i class="fas fa-check me-2"></i>Apply
              </button>
              <button
                type="button"
                class="btn btn-secondary rounded-pill px-4 ms-2"
                @click="clearAllFilters"
              >
                <i class="fas fa-undo me-2"></i>Clear
              </button>
             
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'

// Props
const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({})
  },
  title: {
    type: String,
    default: 'Items'
  },
  modalId: {
    type: String,
    default: 'filterModal'
  },
  modalSize: {
    type: String,
    default: 'modal-lg',
    validator: (value) => ['modal-sm', 'modal-lg', 'modal-xl'].includes(value)
  },
  sortOptions: {
    type: Array,
    default: () => [
      { value: 'stock_desc', label: 'Stock: High to Low' },
      { value: 'stock_asc', label: 'Stock: Low to High' },
      { value: 'name_asc', label: 'Name: A to Z' },
      { value: 'name_desc', label: 'Name: Z to A' }
    ]
  },
  categories: {
    type: Array,
    default: () => []
  },
  suppliers: {
    type: Array,
    default: () => []
  },
  stockStatusOptions: {
    type: Array,
    default: () => [
      { value: 'in_stock', label: 'In Stock' },
      { value: 'low_stock', label: 'Low Stock' },
      { value: 'out_of_stock', label: 'Out of Stock' },
      { value: 'expired', label: 'Expired' },
      { value: 'near_expire', label: 'Near Expiry' }
    ]
  },
  showPriceRange: {
    type: Boolean,
    default: false
  },
  showDateRange: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'apply', 'clear'])

// Local filters state
const localFilters = ref({
  sortBy: '',
  category: '',
  supplier: '',
  stockStatus: '',
  priceMin: null,
  priceMax: null,
  dateFrom: '',
  dateTo: '',
  ...props.modelValue
})

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
  localFilters.value = { ...localFilters.value, ...newValue }
}, { deep: true })

// Computed properties
const hasActiveFilters = computed(() => {
  return Object.values(localFilters.value).some(value => 
    value !== '' && value !== null && value !== undefined
  )
})

const activeFilterCount = computed(() => {
  return Object.values(localFilters.value).filter(value => 
    value !== '' && value !== null && value !== undefined
  ).length
})

const activeFiltersDisplay = computed(() => {
  const filters = []
  
  if (localFilters.value.sortBy) {
    const sortOption = props.sortOptions.find(opt => opt.value === localFilters.value.sortBy)
    filters.push({
      key: 'sortBy',
      label: 'Sort',
      value: sortOption?.label || localFilters.value.sortBy
    })
  }
  
  if (localFilters.value.category) {
    const category = props.categories.find(cat => 
      (cat.id || cat.value) === localFilters.value.category
    )
    filters.push({
      key: 'category',
      label: 'Category',
      value: category?.name || category?.label || localFilters.value.category
    })
  }
  
  if (localFilters.value.supplier) {
    const supplier = props.suppliers.find(sup => 
      (sup.id || sup.value) === localFilters.value.supplier
    )
    filters.push({
      key: 'supplier',
      label: 'Supplier',
      value: supplier?.name || supplier?.label || localFilters.value.supplier
    })
  }
  
  if (localFilters.value.stockStatus) {
    const status = props.stockStatusOptions.find(opt => 
      opt.value === localFilters.value.stockStatus
    )
    filters.push({
      key: 'stockStatus',
      label: 'Stock Status',
      value: status?.label || localFilters.value.stockStatus
    })
  }
  
  if (localFilters.value.priceMin || localFilters.value.priceMax) {
    filters.push({
      key: 'priceRange',
      label: 'Price',
      value: `${localFilters.value.priceMin || 0} - ${localFilters.value.priceMax || '∞'}`
    })
  }
  
  if (localFilters.value.dateFrom || localFilters.value.dateTo) {
    filters.push({
      key: 'dateRange',
      label: 'Date',
      value: `${localFilters.value.dateFrom || '...'} - ${localFilters.value.dateTo || '...'}`
    })
  }
  
  return filters
})

// Methods
const openModal = () => {
  const modalElement = document.getElementById(props.modalId)
  if (modalElement) {
    const modal = new bootstrap.Modal(modalElement)
    modal.show()
  }
}

const applyFilters = () => {
  emit('update:modelValue', { ...localFilters.value })
  emit('apply', { ...localFilters.value })
  
  const modalElement = document.getElementById(props.modalId)
  if (modalElement) {
    const modal = bootstrap.Modal.getInstance(modalElement)
    modal?.hide()
  }
}

const clearAllFilters = () => {
  localFilters.value = {
    sortBy: '',
    category: '',
    supplier: '',
    stockStatus: '',
    priceMin: null,
    priceMax: null,
    dateFrom: '',
    dateTo: ''
  }
  emit('update:modelValue', { ...localFilters.value })
  emit('clear')
}

const clearSingleFilter = (filterKey) => {
  if (filterKey === 'priceRange') {
    localFilters.value.priceMin = null
    localFilters.value.priceMax = null
  } else if (filterKey === 'dateRange') {
    localFilters.value.dateFrom = ''
    localFilters.value.dateTo = ''
  } else {
    localFilters.value[filterKey] = ''
  }
}

// Lifecycle
onMounted(() => {
  // Initialize local filters with prop values
  localFilters.value = { ...localFilters.value, ...props.modelValue }
})
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}

.modal-content {
  border: none;
  box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
}

.form-check-input:checked {
  background-color: var(--bs-primary);
  border-color: var(--bs-primary);
}

.badge {
  font-size: 0.75rem;
  font-weight: 500;
}

.form-label {
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
}

.form-select,
.form-control {
  border-radius: 0.5rem;
  border: 1px solid #e2e8f0;
  transition: all 0.2s;
}

.form-select:focus,
.form-control:focus {
  border-color: var(--bs-primary);
  box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.25);
}

.dark .form-select:focus,
.form-control:focus {
  border-color: #212122 !important;
  box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.25);
}

.dark .form-label{
  color: #fff !important;
}

.dark input{
  background-color: #212121 !important;
}

.dark .form-select{
  background-color: #212121 !important;
  color: #fff !important;
}

.dark .form-check-input{
  outline: 1px solid #fff !important;
}
</style>