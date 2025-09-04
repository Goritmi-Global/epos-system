<script setup>
import { ref, onMounted, onBeforeUnmount, onUpdated } from "vue";
import { Link } from "@inertiajs/vue3";

/* =========================
   Sidebar structure (array)
   ========================= */
const sidebarMenus = ref([
    { label: "Dashboard", icon: "grid", route: "dashboard" },

    {
        section: "POS Management",
        children: [
            {
                label: "Inventory",
                icon: "package",
                children: [
                    { label: "Items", icon: "box", route: "inventory.index" },
                    {
                        label: "Categories",
                        icon: "layers",
                        route: "inventory.categories.index",
                    },
                    {
                        label: "Logs Moments",
                        icon: "archive",
                        route: "stock.logs.index",
                    },
                    {
                        label: "Purchase Order",
                        icon: "shopping-cart",
                        route: "purchase.orders.index",
                    },
                    {
                        label: "Reference Management",
                        icon: "database",
                        route: "reference.index",
                    },
                ],
            },
            {
                label: "Menu",
                icon: "book-open",
                children: [
                    {
                        label: "Categories",
                        icon: "layers",
                        route: "menu.categories.index",
                    },
                    { label: "Items", icon: "box", route: "menu.index" },
                ],
            },
            { label: "POS Order", icon: "shopping-bag", route: "pos.order" },
            { label: "Orders", icon: "list", route: "orders.index" },
            { label: "Payment", icon: "credit-card", route: "payment.index" },
            {
                label: "Analytics",
                icon: "bar-chart-2",
                route: "analytics.index",
            },
        ],
    },

    {
        section: "Other Menu",
        children: [
            { label: "Settings", icon: "settings", route: "settings.index" },
            {
                label: "Log Out",
                icon: "log-out",
                route: "logout",
                method: "post",
            },
        ],
    },
]);

/* =========================
   Helpers
   ========================= */
const isActive = (routeName) => {
    try {
        return route().current(routeName);
    } catch {
        return false;
    }
};

const openGroups = ref(new Set());
const toggleGroup = (label) => {
    if (openGroups.value.has(label)) openGroups.value.delete(label);
    else openGroups.value.add(label);
};

const isAnyChildActive = (children = []) =>
    children.some((c) =>
        c.children ? isAnyChildActive(c.children) : isActive(c.route)
    );

const openActiveGroups = () => {
    const scan = (nodes = []) => {
        nodes.forEach((n) => {
            if (n.children?.length) {
                if (isAnyChildActive(n.children)) openGroups.value.add(n.label);
                scan(n.children);
            }
        });
    };
    sidebarMenus.value.forEach(
        (block) => block.children && scan(block.children)
    );
};

/* =========================
   Sidebar state (3 modes)
   ========================= */
const sidebarOpen = ref(true); // desktop: expanded by default
const isMobile = ref(false);

const evaluateMobile = () => {
    isMobile.value = window.innerWidth < 992;
};
const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};

onMounted(() => {
    evaluateMobile();
    window.addEventListener("resize", evaluateMobile, { passive: true });
    window.feather?.replace();
    openActiveGroups();
});
onBeforeUnmount(() => window.removeEventListener("resize", evaluateMobile));
onUpdated(() => window.feather?.replace());
</script>

<template>
    <div
        class="layout-root"
        :class="{
            'state-desktop': !isMobile,
            'state-mobile': isMobile,
            'sidebar-open': sidebarOpen,
            'sidebar-collapsed': !sidebarOpen && !isMobile, // desktop mini
            'sidebar-overlay': isMobile, // mobile overlay behavior
        }"
    >
        <!-- =================== HEADER =================== -->
        <header class="header">
            <div class="header-left">
                <a href="javascript:void(0)" class="logo">
                    <img src="/assets/img/logo-trans.png" alt="logo" />
                </a>
                <button
                    class="icon-btn"
                    @click="toggleSidebar"
                    aria-label="Toggle sidebar"
                >
                    <i data-feather="menu"></i>
                </button>

                
            </div>

            <div class="header-center">
                <div class="top-nav-search">
                    <i class="fa fa-search me-2"></i>
                    <input type="text" placeholder="Search Here ..." />
                </div>
            </div>

            <ul class="nav user-menu">
                <li class="nav-item dropdown has-arrow flag-nav">
                    <a
                        class="nav-link dropdown-toggle"
                        data-bs-toggle="dropdown"
                        href="javascript:void(0);"
                    >
                        <img
                            src="/assets/img/flags/us1.png"
                            alt=""
                            height="20"
                        />
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a href="javascript:void(0);" class="dropdown-item"
                            ><img src="/assets/img/flags/us.png" height="16" />
                            English</a
                        >
                        <a href="javascript:void(0);" class="dropdown-item"
                            ><img src="/assets/img/flags/fr.png" height="16" />
                            French</a
                        >
                        <a href="javascript:void(0);" class="dropdown-item"
                            ><img src="/assets/img/flags/es.png" height="16" />
                            Spanish</a
                        >
                        <a href="javascript:void(0);" class="dropdown-item"
                            ><img src="/assets/img/flags/de.png" height="16" />
                            German</a
                        >
                    </div>
                </li>

                <li class="nav-item dropdown">
                    <a
                        href="javascript:void(0);"
                        class="dropdown-toggle nav-link"
                        data-bs-toggle="dropdown"
                    >
                        <img
                            src="/assets/img/icons/notification-bing.svg"
                            alt="noti"
                        />
                        <span class="badge rounded-pill">4</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end notifications">
                        <div
                            class="topnav-dropdown-header d-flex align-items-center justify-content-between"
                        >
                            <span class="notification-title"
                                >Notifications</span
                            >
                            <a href="javascript:void(0)" class="clear-noti"
                                >Clear All</a
                            >
                        </div>
                        <div class="noti-content p-3">
                            No new notifications.
                        </div>
                        <div class="topnav-dropdown-footer">
                            <a href="javascript:void(0)"
                                >View all Notifications</a
                            >
                        </div>
                    </div>
                </li>

                <li class="nav-item dropdown has-arrow main-drop">
                    <a
                        href="javascript:void(0);"
                        class="dropdown-toggle nav-link userset"
                        data-bs-toggle="dropdown"
                    >
                        <span class="user-img">
                            <img
                                src="/assets/img/profiles/avator1.jpg"
                                alt=""
                            />
                            <span class="status online"></span>
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end menu-drop-user">
                        <div class="profilename">
                            <div class="profileset">
                                <span class="user-img">
                                    <img
                                        src="/assets/img/profiles/avator1.jpg"
                                        alt=""
                                    />
                                    <span class="status online"></span>
                                </span>
                                <div class="profilesets">
                                    <h6>John Doe</h6>
                                    <h5>Admin</h5>
                                </div>
                            </div>
                            <hr class="m-0" />
                            <a class="dropdown-item" href="javascript:void(0)"
                                ><i class="me-2" data-feather="user"></i> My
                                Profile</a
                            >
                            <a class="dropdown-item" href="javascript:void(0)"
                                ><i class="me-2" data-feather="settings"></i>
                                Settings</a
                            >
                            <hr class="m-0" />
                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="dropdown-item text-danger"
                            >
                                <i class="me-2" data-feather="log-out"></i> Log
                                Out
                            </Link>
                        </div>
                    </div>
                </li>
            </ul>
        </header>
        <!-- =================== /HEADER =================== -->

        <!-- =================== SIDEBAR =================== -->
        <aside class="sidebar" id="sidebar" aria-label="Primary">
            <div class="sidebar-inner">
                <div id="sidebar-menu" class="sidebar-menu px-2">
                    <ul class="mb-3">
                        <template
                            v-for="block in sidebarMenus"
                            :key="block.label || block.section"
                        >
                            <!-- Simple top item -->
                            <li
                                v-if="!block.section"
                                :class="{ active: isActive(block.route) }"
                            >
                                <Link
                                    :href="route(block.route)"
                                    class="d-flex align-items-center side-link px-3 py-2"
                                >
                                    <i
                                        :data-feather="block.icon"
                                        class="me-2"
                                    ></i>
                                    <span class="truncate-when-mini">{{
                                        block.label
                                    }}</span>
                                </Link>
                            </li>

                            <!-- Section -->
                            <template v-else>
                                <li
                                    class="mt-3 mb-1 px-3 text-muted text-uppercase small section-title truncate-when-mini"
                                >
                                    {{ block.section }}
                                </li>

                                <template
                                    v-for="item in block.children"
                                    :key="item.label"
                                >
                                    <!-- Dropdown group -->
                                    <li
                                        v-if="
                                            item.children &&
                                            item.children.length
                                        "
                                    >
                                        <button
                                            class="d-flex align-items-center side-link px-3 py-2 w-100 border-0"
                                            :class="{
                                                active:
                                                    openGroups.has(
                                                        item.label
                                                    ) ||
                                                    isAnyChildActive(
                                                        item.children
                                                    ),
                                            }"
                                            @click="toggleGroup(item.label)"
                                            type="button"
                                            :aria-expanded="
                                                openGroups.has(item.label) ||
                                                isAnyChildActive(item.children)
                                            "
                                        >
                                            <i
                                                :data-feather="item.icon"
                                                class="me-2"
                                            ></i>
                                            <span
                                                class="flex-grow-1 text-start truncate-when-mini"
                                                >{{ item.label }}</span
                                            >
                                            <i
                                                :data-feather="
                                                    openGroups.has(
                                                        item.label
                                                    ) ||
                                                    isAnyChildActive(
                                                        item.children
                                                    )
                                                        ? 'chevron-up'
                                                        : 'chevron-down'
                                                "
                                            ></i>
                                        </button>

                                         <!-- class="list-unstyled ms-4 my-1" -->
                                        <ul
                                            class="list-unstyled my-1"
                                            v-show="
                                                openGroups.has(item.label) ||
                                                isAnyChildActive(item.children)
                                            "
                                        >
                                            <li
                                                v-for="child in item.children"
                                                :key="child.label"
                                                :class="{
                                                    active: isActive(
                                                        child.route
                                                    ),
                                                }"
                                            >
                                                <Link
                                                    :href="route(child.route)"
                                                    :method="
                                                        child.method || 'get'
                                                    "
                                                    class="d-flex align-items-center side-link px-3 py-2"
                                                >
                                                    <i
                                                        :data-feather="
                                                            child.icon
                                                        "
                                                        class="me-2"
                                                    ></i>
                                                    <span>{{
                                                        child.label
                                                    }}</span>
                                                </Link>
                                            </li>
                                        </ul>
                                    </li>

                                    <!-- Flat item -->
                                    <li
                                        v-else
                                        :class="{
                                            active: isActive(item.route),
                                        }"
                                    >
                                        <Link
                                            :href="route(item.route)"
                                            :method="item.method || 'get'"
                                            class="d-flex align-items-center side-link px-3 py-2"
                                        >
                                            <i
                                                :data-feather="item.icon"
                                                class="me-2"
                                            ></i>
                                            <span class="truncate-when-mini">{{
                                                item.label
                                            }}</span>
                                        </Link>
                                    </li>
                                </template>
                            </template>
                        </template>
                    </ul>
                </div>

                <!-- Optional sidebar footer -->
                <!-- <div class="p-3 border-top small truncate-when-mini">
                    <div
                        class="d-flex align-items-center justify-content-between mb-2"
                    >
                        <span>Dark Mode</span>
                        <input type="checkbox" class="form-check-input" />
                    </div>
                    <button class="btn btn-danger w-100 mb-2 rounded-pill">
                        System Restore
                    </button>
                    <button class="btn btn-primary w-100 rounded-pill">
                        Upgrade to Premium
                    </button>
                </div> -->
            </div>
        </aside>

        <!-- Mobile overlay backdrop -->
        <div
            v-if="isMobile && sidebarOpen"
            class="overlay-backdrop"
            aria-hidden="true"
            @click="toggleSidebar"
        ></div>
        <!-- =================== /SIDEBAR =================== -->

        <!-- =================== PAGE CONTENT =================== -->
        <!-- <main class="page-wrapper"> -->
        <main class="">
            <slot />
        </main>
    </div>
</template>

<style>
/* ========= CSS VARIABLES ========= */
:root {
    --header-h: 64px;
    --sidebar-w: 280px;
    --sidebar-w-collapsed: 0px;
    --brand: #1B2850;
    --bg-muted: #f5f6f8;
    --border: #eef0f3;
}

/* ========= LAYOUT ROOT ========= */
.layout-root {
    min-height: 100vh;
    background: #f7f8fb;
}

/* ========= HEADER ========= */
.header {
    position: sticky;
    top: 0;
    z-index: 1000;
    height: var(--header-h);
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 16px;
    background: #fff;
    border-bottom: 1px solid var(--border);
}
.header-left {
    display: flex;
    align-items: center;
    gap: 10px;
}
.icon-btn {
    border: 0;
    background: transparent;
    padding: 6px;
    border-radius: 8px;
}
.icon-btn:hover {
    background: var(--bg-muted);
}
.logo img {
    /* height: 48px; */
}
.header-center {
    flex: 1;
    display: flex;
    align-items: center;
}
.top-nav-search {
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--bg-muted);
    border-radius: 9999px;
    padding: 6px 10px;
    width: 100%;
    max-width: 420px;
}
.top-nav-search input {
    background: transparent;
    border: none;
    outline: none;
    width: 100%;
}

.nav.user-menu {
    list-style: none;
    display: flex;
    align-items: center;
    gap: 16px;
    margin: 0;
}
.header .nav.user-menu {
  height: var(--header-h);
  align-items: center;
}
.header .nav.user-menu > li {
  display: flex;
  align-items: center;
}

/* Make the clickable area full header height and center the icon/text */
.header .nav.user-menu .nav-link,
.header .nav.user-menu .dropdown-toggle {
  display: flex;
  align-items: center;
  height: var(--header-h);
  padding: 0 10px;
  line-height: 1;            /* kill anchor baseline drift */
}


/* Remove extra baseline gap caused by inline images */
.header .nav.user-menu img {
  display: block;            /* no inline baseline */
}

/* Notification bell + badge tidy */
.header .nav.user-menu .nav-item.dropdown > .nav-link {
  position: relative;
}
.header .nav.user-menu .badge.rounded-pill {
  position: absolute;
  top: 14px;                 /* tweak to taste */
  right: 6px;
  transform: translateY(-50%);
}

/* Profile avatar + online dot alignment */
.user-img {
  position: relative;
  display: inline-block;
  width: 34px;
  height: 34px;
}
.user-img img {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
}
.status.online {
  position: absolute;
  right: -2px;
  bottom: -2px;
  width: 8px;
  height: 8px;
  border: 2px solid #fff;
  margin: 0;                 /* remove negative margins */
}
.nav .badge {
    background: var(--brand);
    color: #fff;
    font-size: 12px;
}

/* ========= SIDEBAR ========= */
.sidebar {
    position: fixed;
    top: var(--header-h);
    left: 0;
    bottom: 0;
    width: var(--sidebar-w);
    background: #fff;
    border-right: 1px solid var(--border);
    transition: width 0.2s ease, transform 0.2s ease;
    z-index: 1020;
}
.sidebar-inner {
    width: 100%;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.sidebar-menu {
    flex: 1 1 auto;
    overflow-y: auto;
    padding-top: 8px;
}
.section-title {
    letter-spacing: 0.08em;
}

/* Links */
.side-link {
    text-decoration: none;
    color: var(--brand);
    border-radius: 10px;
    transition: background 0.15s ease, color 0.15s ease;
}
.side-link:hover {
    background: var(--brand);
    color: #fff;
}
.side-link.active,
li.active > .side-link {
    background: var(--brand);
    color: #eef2ff;
    font-weight: 600;
}

/* ========= PAGE WRAPPER ========= */
.page-wrapper {
    margin-left: var(--sidebar-w);
    padding: 20px;
    min-height: calc(100vh - var(--header-h));
    transition: margin-left 0.2s ease;
}

/* ========= DESKTOP STATES ========= */
.state-desktop.sidebar-collapsed .sidebar {
    width: var(--sidebar-w-collapsed);
}
.state-desktop.sidebar-collapsed .page-wrapper {
    margin-left: var(--sidebar-w-collapsed);
}

/* Hide long text when mini */
.state-desktop.sidebar-collapsed .truncate-when-mini {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ========= MOBILE (OVERLAY) ========= */
.state-mobile .page-wrapper {
    margin-left: 0;
}
.state-mobile .sidebar {
    width: var(--sidebar-w);
    transform: translateX(-100%);
}
.state-mobile.sidebar-open .sidebar {
    transform: translateX(0);
}

/* Backdrop for mobile overlay */
.overlay-backdrop {
    position: fixed;
    inset: var(--header-h) 0 0 0;
    background: rgba(0, 0, 0, 0.35);
    z-index: 1015;
}

/* ========= UTIL ========= */
.truncate-when-mini {
    display: inline-block;
    max-width: 100%;
}

/* Scrollbars (optional) */
.sidebar-menu::-webkit-scrollbar {
    width: 8px;
}
.sidebar-menu::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 8px;
}

/* Edit delete icon */
.icon-action {
  cursor: pointer;            /* hand cursor */
  transition: transform 0.15s, opacity 0.15s;
}

.icon-action:hover {
  transform: scale(1.2);      /* slight zoom on hover */
  opacity: 0.8;               /* subtle fade effect */
}


/* Form field errors */
.is-invalid {
  background-color: #f8d7da !important;  
  border-color: #dc3545 !important;    
}
</style>
