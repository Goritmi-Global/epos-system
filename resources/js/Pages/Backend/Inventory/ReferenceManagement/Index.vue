<script setup>
import { ref } from "vue";
import Master from "@/Layouts/Master.vue";

import SuppliersTab from "./modules/SuppliersTab.vue";
import TagsTab from "./modules/TagsTab.vue";
import AllergiesTab from "./modules/AllergiesTab.vue";
import UnitsTab from "./modules/UnitsTab.vue";

const tabs = [
    { key: "suppliers", label: "Suppliers", comp: SuppliersTab },
    { key: "tags", label: "Tags", comp: TagsTab },
    { key: "allergies", label: "Allergies", comp: AllergiesTab },
    { key: "units", label: "Units", comp: UnitsTab },
];
const active = ref("suppliers");
</script>

<template>
    <Master>
        <div class="page-wrapper">
            <div class="container-fluid py-3">
                <!-- Tabs -->
                <ul class="nav nav-tabs border-0">
                    <li class="nav-item" v-for="t in tabs" :key="t.key">
                        <button
                            class="nav-link px-3"
                            :class="{ active: active === t.key }"
                            @click="active = t.key"
                        >
                            {{ t.label }}
                        </button>
                    </li>
                </ul>

                <div class="mt-3">
                    <component :is="tabs.find((t) => t.key === active)?.comp" />
                </div>
            </div>
        </div>
    </Master>
</template>

<style scoped>

.dark .nav-tabs .nav-link{
    color: #ffffff;
}

.dark .nav-link{
    color: #ffffff;
}

.dark .nav-tabs .nav-link.active{
    color: #ffffff;
     border-bottom: 2px solid #ffffff;
}
.nav-tabs .nav-link {
    border: 0;
    color: #6c757d;
}
.nav-tabs .nav-link.active {
    color: #0f172a;
    border-bottom: 2px solid #1c0d82;
    background: transparent;
}
</style>
