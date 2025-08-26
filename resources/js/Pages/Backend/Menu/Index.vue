<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed } from "vue";

/* Tabs (Menu active like screenshot) */
const tabs = [
  { key: "menu",         label: "Menu" },
  { key: "menuCategory", label: "Menu Category" },
  { key: "deals",        label: "Deals" },
];
const activeTab = ref("menu");

/* Demo data (swap with API later) */
const items = ref([
  {
    id: 1,
    name: "French Fries",
    image: "https://picsum.photos/seed/fries/64",
    category: "Fast Foods",
    price: 100,          // GBP
    active: true,
  },
  {
    id: 2,
    name: "Crown Crust",
    image: "https://picsum.photos/seed/crowncrust/64",
    category: "Fast Foods",
    price: 0,            // GBP
    active: true,
  },
]);

/* Search */
const q = ref("");
const filteredItems = computed(() => {
  const term = q.value.trim().toLowerCase();
  if (!term) return items.value;
  return items.value.filter(
    (i) =>
      i.name.toLowerCase().includes(term) ||
      i.category.toLowerCase().includes(term) ||
      String(i.price).includes(term)
  );
});

/* KPIs */
const totalMenuCount   = computed(() => items.value.length);
const activeMenuCount  = computed(() => items.value.filter(i => i.active).length);
const deactiveMenuCount= computed(() => items.value.filter(i => !i.active).length);

const kpiCards = computed(() => [
  { label: "Total Menus",   value: totalMenuCount.value },
  { label: "Active Menus",  value: activeMenuCount.value },
  { label: "Deactive Menus",value: deactiveMenuCount.value },
]);

/* Helpers */
const money = (amount, currency = "GBP") =>
  new Intl.NumberFormat("en-GB", { style: "currency", currency }).format(amount);

/* Buttons (wire later) */
const onAddMenu = () => {};
const onDownloadAll = () => {};
</script>

<template>
  <Head title="Menu" />

  <Master>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800">
        Menu
      </h2>
    </template>

    <div class="py-12">
      <div class="page-wrapper">
        <div class="content">

          <div class="container-fluid py-3">

            <!-- Tabs -->
            <ul class="nav nav-tabs border-0">
              <li class="nav-item" v-for="t in tabs" :key="t.key">
                <button
                  class="nav-link px-3"
                  :class="{ active: activeTab === t.key }"
                  @click="activeTab = t.key"
                >
                  {{ t.label }}
                </button>
              </li>
            </ul>

            <div class="mt-3">
              <h4 class="mb-3">Overall Menus</h4>

              <!-- KPI cards -->
              <div class="row g-3">
                <div class="col-sm-6 col-md-3" v-for="card in kpiCards" :key="card.label">
                  <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body text-center">
                      <div class="text-muted small mb-1">{{ card.label }}</div>
                      <div class="h4 mb-0">{{ card.value }}</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Menus table -->
              <div class="card border-0 shadow-lg rounded-4 mt-4">
                <div class="card-body">
                  <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Menus</h5>

                    <div class="d-flex flex-wrap gap-2">
                      <div class="input-group">
                        <span class="input-group-text bg-white">
                          <i class="bi bi-search"></i>
                        </span>
                        <input v-model="q" type="text" class="form-control" placeholder="Search" />
                      </div>
                      <button class="btn btn-primary rounded-pill" @click="onAddMenu">Add Menu</button>
                      <button class="btn btn-outline-secondary rounded-pill" @click="onDownloadAll">
                        Download all
                      </button>
                    </div>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-hover align-middle">
                      <thead class="border-top">
                        <tr>
                          <th class="text-muted">S.#</th>
                          <th class="text-muted">Image</th>
                          <th class="text-muted">Menu</th>
                          <th class="text-muted">Category</th>
                          <th class="text-muted">Price</th>
                          <th class="text-muted">Status</th>
                          <th class="text-muted">Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(item, idx) in filteredItems" :key="item.id">
                          <td>{{ idx + 1 }}</td>
                          <td>
                            <img
                              :src="item.image"
                              alt=""
                              class="rounded"
                              style="width:40px;height:40px;object-fit:cover"
                            />
                          </td>
                          <td class="fw-semibold">{{ item.name }}</td>
                          <td>{{ item.category }}</td>
                          <td>{{ money(item.price, 'GBP') }}</td>
                          <td>
                            <span v-if="item.active" class="text-success">Active</span>
                            <button v-else class="btn btn-link p-0 text-success">Activate</button>
                          </td>
                          <td>
                            <button class="btn btn-link text-secondary p-0" title="Actions">⋮</button>
                          </td>
                        </tr>

                        <tr v-if="filteredItems.length === 0">
                          <td colspan="7" class="text-center text-muted py-4">
                            No menus found.
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                </div>
              </div>
            </div>

          </div>

        </div>
      </div>
    </div>
  </Master>
</template>

<style scoped>
/* Tabs like the screenshot: minimal, underline on active */
.nav-tabs .nav-link {
  border: 0;
  color: #6c757d;
}
.nav-tabs .nav-link.active {
  color: #000;
  border-bottom: 2px solid var(--bs-primary);
  background-color: transparent;
}
</style>
