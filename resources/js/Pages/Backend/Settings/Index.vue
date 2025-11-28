<script setup>
import { ref, computed } from "vue";
import Master from "@/Layouts/Master.vue";
import SoftwareSetting from "./Components/SoftwareSetting.vue";
import Users from "./Components/Users.vue";
import Permissions from "./Components/Permissions.vue";
import Roles from "./Components/Roles.vue";
import { Head } from "@inertiajs/vue3";

const props = defineProps({
    profile: { type: Object, default: () => ({}) },
    profileData: { type: Object, default: () => ({}) },
});

const tabDefs = [
    {
        key: "software",
        label: "Software Settings",
        component: SoftwareSetting,
        icon: "bi-gear",
    },
    { key: "users", label: "Users", component: Users, icon: "bi-people" },
    {
        key: "permissions",
        label: "Permissions",
        component: Permissions,
        icon: "bi-shield-lock",
    },
    { key: "roles", label: "Roles", component: Roles, icon: "bi-diagram-3" },
];

const url = new URL(window.location.href);
const initial = url.searchParams.get("tab") || "software";
const activeTab = ref(
    tabDefs.some((t) => t.key === initial) ? initial : "software"
);

function switchTab(key) {
    activeTab.value = key;
    const u = new URL(window.location.href);
    u.searchParams.set("tab", key);
    history.replaceState({}, "", u);
}

const current = computed(
    () =>
        tabDefs.find((t) => t.key === activeTab.value)?.component ??
        SoftwareSetting
);
</script>

<template>
    <Master>
          <Head title="Settings" />
        <div class="page-wrapper">
            <div class="settings-tabs-wrapper">
                <!-- TAB BAR -->
                <ul class="nav nav-tabs gap-2 px-2 pt-2">
                    <nav class="tabs-underline">
                        <button
                            v-for="t in tabDefs"
                            f
                            :key="t.key"
                            class="tablink"
                            :class="{ active: activeTab === t.key }"
                            type="button"
                            @click="switchTab(t.key)"
                        >
                            {{ t.label }}
                        </button>
                    </nav>
                </ul>

                <!-- ACTIVE TAB CONTENT -->
                    <div class="tab-body py-3">
                        <component
                            :is="current"
                            :profile="props.profile"
                            :profile-data="props.profileData"
                            :is-onboarding="false"
                        />
                </div>
            </div>
        </div>
    </Master>
</template>

<style scoped>
.nav-tabs .nav-link {
    border: 0;
    color: #444;
}
.nav-tabs .nav-link.active {
    background: #1C0D82;;
    color: #fff;
    box-shadow: 0 4px 12px rgba(28, 13, 130, 0.25);
}

.nav-link.active {
    background: #1C0D82;;
    color: #fff;
    box-shadow: 0 4px 12px rgba(28, 13, 130, 0.25);
}


.dark .nav-tabs .nav-link {
    color: #e9e9e9;
}
.tab-body {
    padding: 8px 12px;
}

.tabs-underline {
    display: flex;
    gap: 28px;
    align-items: center;
    padding: 6px 6px 0;
    background: transparent;
}

.tablink {
    position: relative;
    appearance: none;
    border: 0;
    background: none;
    padding: 10px 2px 14px; 
    font-weight: 600;
    color: #5b6470; 
    cursor: pointer;
    transition: color 0.2s ease;
    outline: none;
}

.tablink:hover,
.tablink:focus-visible {
    color: #1c0d82; 
}

.tablink::after {
    content: "";
    position: absolute;
    left: 0;
    right: 0;
    bottom: -1px;
    height: 2px;
    background: #1c0d82; 
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.25s ease;
    border-radius: 2px;
}

.tablink.active {
    color: #1C0D82;
}
.tablink.active::after {
    transform: scaleX(1);
}
.dark .tablink {
    color: #cfd5df; 
}

.dark .tablink:hover,
.dark .tablink:focus-visible,
.dark .tablink.active {
    color: #dfe4ff;
}

.dark .tablink::after {
    background: #8ea2ff;
}

@media (max-width: 768px) {
    .tabs-underline {
        gap: 18px;
    }
    .tablink {
        padding-bottom: 12px;
    }
}
</style>
