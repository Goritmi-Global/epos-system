<script setup>
import { ref, onMounted, onUpdated } from "vue";

import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import NavLink from "@/Components/NavLink.vue";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";
import { Link } from "@inertiajs/vue3";
import { toast } from "vue3-toastify";

// -------- Sidebar: Array-driven ----------
const sidebarMenus = ref([
    // Single top item
    { label: "Dashboard", icon: "grid", route: "dashboard" },

    // Section: POS Management
    {
        section: "POS Management",
        children: [
            { label: "Inventory", icon: "package", route: "inventory.index" },
            { label: "Menu", icon: "book-open", route: "menu.index" },
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

    // Section: Other Menu
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

// Helper to mark active links (safe if route helper not ready)
const isActive = (routeName) => {
    try {
        return route().current(routeName);
    } catch {
        return false;
    }
};

onMounted(() => {
    window.feather?.replace();

    // Rebind Bootstrap dropdowns (use jQuery if needed)
    window.$?.fn?.dropdown && window.$(".dropdown-toggle").dropdown();

    // Handle custom submenu toggles (kept from your original file)
    window.$?.fn &&
        window.$(".submenu > a").on("click", function (e) {
            e.preventDefault();
            const $li = window.$(this).parent("li");
            const $ul = window.$(this).next("ul");

            if (!$li.hasClass("active")) {
                window.$(".submenu ul").slideUp(350);
                window.$(".submenu").removeClass("active");
                $ul.slideDown(350);
                $li.addClass("active");
            } else {
                $ul.slideUp(350);
                $li.removeClass("active");
            }
        });
});

onUpdated(() => {
    window.feather?.replace();
});
</script>

<template>
    <!-- <div id="global-loader"><div class="whirly-loader"></div></div> -->

    <div class="main-wrapper">
        <!-- =================== HEADER =================== -->
        <div class="header">
            <div class="header-left active">
              <a href="index.html" class="logo">
                    <img src="assets/img/logo-trans.png" alt="" />
                </a>
                <a href="index.html" class="logo-small">
                    <img src="assets/img/logo-small.png" alt="" />
                </a>
                <a id="toggle_btn" href="javascript:void(0);"> </a>
            </div>

            <a id="mobile_btn" class="mobile_btn" href="#sidebar">
                <span class="bar-icon">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
            </a>

            <ul class="nav user-menu">
                <li class="nav-item">
                    <div class="top-nav-search">
                        <a href="javascript:void(0);" class="responsive-search">
                            <i class="fa fa-search"></i>
                        </a>
                        <form action="#">
                            <div class="searchinputs">
                                <input
                                    type="text"
                                    placeholder="Search Here ..."
                                />
                                <div class="search-addon">
                                    <span>
                                        <img
                                            src="assets/img/icons/closes.svg"
                                            alt="img"
                                        />
                                    </span>
                                </div>
                            </div>
                            <a class="btn" id="searchdiv">
                                <img
                                    src="assets/img/icons/search.svg"
                                    alt="img"
                                />
                            </a>
                        </form>
                    </div>
                </li>

                <li class="nav-item dropdown has-arrow flag-nav">
                    <a
                        class="nav-link dropdown-toggle"
                        data-bs-toggle="dropdown"
                        href="javascript:void(0);"
                        role="button"
                    >
                        <img
                            src="assets/img/flags/us1.png"
                            alt=""
                            height="20"
                        />
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a href="javascript:void(0);" class="dropdown-item">
                            <img
                                src="assets/img/flags/us.png"
                                alt=""
                                height="16"
                            />
                            English
                        </a>
                        <a href="javascript:void(0);" class="dropdown-item">
                            <img
                                src="assets/img/flags/fr.png"
                                alt=""
                                height="16"
                            />
                            French
                        </a>
                        <a href="javascript:void(0);" class="dropdown-item">
                            <img
                                src="assets/img/flags/es.png"
                                alt=""
                                height="16"
                            />
                            Spanish
                        </a>
                        <a href="javascript:void(0);" class="dropdown-item">
                            <img
                                src="assets/img/flags/de.png"
                                alt=""
                                height="16"
                            />
                            German
                        </a>
                    </div>
                </li>

                <li class="nav-item dropdown">
                    <a
                        href="javascript:void(0);"
                        class="dropdown-toggle nav-link"
                        data-bs-toggle="dropdown"
                    >
                        <img
                            src="assets/img/icons/notification-bing.svg"
                            alt="img"
                        />
                        <span class="badge rounded-pill">4</span>
                    </a>
                    <div class="dropdown-menu notifications">
                        <div class="topnav-dropdown-header">
                            <span class="notification-title"
                                >Notifications</span
                            >
                            <a href="javascript:void(0)" class="clear-noti">
                                Clear All
                            </a>
                        </div>
                        <div class="noti-content">
                            <ul class="notification-list">
                                <li class="notification-message">
                                    <a href="activities.html">
                                        <div class="media d-flex">
                                            <span class="avatar flex-shrink-0">
                                                <img
                                                    alt=""
                                                    src="assets/img/profiles/avatar-02.jpg"
                                                />
                                            </span>
                                            <div class="media-body flex-grow-1">
                                                <p class="noti-details">
                                                    <span class="noti-title"
                                                        >John Doe</span
                                                    >
                                                    added new task
                                                    <span class="noti-title"
                                                        >Patient appointment
                                                        booking</span
                                                    >
                                                </p>
                                                <p class="noti-time">
                                                    <span
                                                        class="notification-time"
                                                        >4 mins ago</span
                                                    >
                                                </p>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li class="notification-message">
                                    <a href="activities.html">
                                        <div class="media d-flex">
                                            <span class="avatar flex-shrink-0">
                                                <img
                                                    alt=""
                                                    src="assets/img/profiles/avatar-03.jpg"
                                                />
                                            </span>
                                            <div class="media-body flex-grow-1">
                                                <p class="noti-details">
                                                    <span class="noti-title"
                                                        >Tarah Shropshire</span
                                                    >
                                                    changed the task name
                                                    <span class="noti-title"
                                                        >Appointment booking
                                                        with payment
                                                        gateway</span
                                                    >
                                                </p>
                                                <p class="noti-time">
                                                    <span
                                                        class="notification-time"
                                                        >6 mins ago</span
                                                    >
                                                </p>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li class="notification-message">
                                    <a href="activities.html">
                                        <div class="media d-flex">
                                            <span class="avatar flex-shrink-0">
                                                <img
                                                    alt=""
                                                    src="assets/img/profiles/avatar-06.jpg"
                                                />
                                            </span>
                                            <div class="media-body flex-grow-1">
                                                <p class="noti-details">
                                                    <span class="noti-title"
                                                        >Misty Tison</span
                                                    >
                                                    added
                                                    <span class="noti-title"
                                                        >Domenic Houston</span
                                                    >
                                                    and
                                                    <span class="noti-title"
                                                        >Claire Mapes</span
                                                    >
                                                    to project
                                                    <span class="noti-title"
                                                        >Doctor available
                                                        module</span
                                                    >
                                                </p>
                                                <p class="noti-time">
                                                    <span
                                                        class="notification-time"
                                                        >8 mins ago</span
                                                    >
                                                </p>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li class="notification-message">
                                    <a href="activities.html">
                                        <div class="media d-flex">
                                            <span class="avatar flex-shrink-0">
                                                <img
                                                    alt=""
                                                    src="assets/img/profiles/avatar-17.jpg"
                                                />
                                            </span>
                                            <div class="media-body flex-grow-1">
                                                <p class="noti-details">
                                                    <span class="noti-title"
                                                        >Rolland Webber</span
                                                    >
                                                    completed task
                                                    <span class="noti-title"
                                                        >Patient and Doctor
                                                        video conferencing</span
                                                    >
                                                </p>
                                                <p class="noti-time">
                                                    <span
                                                        class="notification-time"
                                                        >12 mins ago</span
                                                    >
                                                </p>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li class="notification-message">
                                    <a href="activities.html">
                                        <div class="media d-flex">
                                            <span class="avatar flex-shrink-0">
                                                <img
                                                    alt=""
                                                    src="assets/img/profiles/avatar-13.jpg"
                                                />
                                            </span>
                                            <div class="media-body flex-grow-1">
                                                <p class="noti-details">
                                                    <span class="noti-title"
                                                        >Bernardo Galaviz</span
                                                    >
                                                    added new task
                                                    <span class="noti-title"
                                                        >Private chat
                                                        module</span
                                                    >
                                                </p>
                                                <p class="noti-time">
                                                    <span
                                                        class="notification-time"
                                                        >2 days ago</span
                                                    >
                                                </p>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="topnav-dropdown-footer">
                            <a href="activities.html">View all Notifications</a>
                        </div>
                    </div>
                </li>

                <li class="nav-item dropdown has-arrow main-drop">
                    <a
                        href="javascript:void(0);"
                        class="dropdown-toggle nav-link userset"
                        data-bs-toggle="dropdown"
                    >
                        <span class="user-img"
                            ><img
                                src="assets/img/profiles/avator1.jpg"
                                alt="" />
                            <span class="status online"></span
                        ></span>
                    </a>
                    <div class="dropdown-menu menu-drop-user">
                        <div class="profilename">
                            <div class="profileset">
                                <span class="user-img"
                                    ><img
                                        src="assets/img/profiles/avator1.jpg"
                                        alt="" />
                                    <span class="status online"></span
                                ></span>
                                <div class="profilesets">
                                    <h6>John Doe</h6>
                                    <h5>Admin</h5>
                                </div>
                            </div>
                            <hr class="m-0" />
                            <a class="dropdown-item" href="profile.html">
                                <i class="me-2" data-feather="user"></i>
                                My Profile</a
                            >
                            <a class="dropdown-item" href="generalsettings.html"
                                ><i class="me-2" data-feather="settings"></i
                                >Settings</a
                            >
                            <hr class="m-0" />

                            <DropdownLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="flex items-center gap-2 px-4 py-2 text-gray-700 hover:bg-gray-100 w-full text-start"
                            >
                                <img
                                    src="assets/img/icons/log-out.svg"
                                    alt="Logout Icon"
                                    class="w-5 h-5 object-contain text-red-500"
                                />
                                <span class="text-red-500">Log Out</span>
                            </DropdownLink>
                        </div>
                    </div>
                </li>
            </ul>

            <div class="dropdown mobile-user-menu">
                <a
                    href="javascript:void(0);"
                    class="nav-link dropdown-toggle"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    ><i class="fa fa-ellipsis-v"></i
                ></a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="profile.html">My Profile</a>
                    <a class="dropdown-item" href="generalsettings.html"
                        >Settings</a
                    >
                    <a class="dropdown-item" href="signin.html">Logout</a>
                </div>
            </div>
        </div>
        <!-- =================== /HEADER =================== -->

        <!-- =================== SIDEBAR =================== -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-inner slimscroll">
                <div id="sidebar-menu" class="sidebar-menu px-2">
                    <ul class="mb-3">
                        <!-- Iterate Array -->
                        <template
                            v-for="block in sidebarMenus"
                            :key="block.label || block.section"
                        >
                            <!-- Simple Item -->
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
                                    <span>{{ block.label }}</span>
                                </Link>
                            </li>

                            <!-- Section -->
                            <template v-else>
                                <li
                                    class="mt-3 mb-1 px-3 text-muted text-uppercase small"
                                >
                                    {{ block.section }}
                                </li>
                                <li
                                    v-for="item in block.children"
                                    :key="item.label"
                                    :class="{ active: isActive(item.route) }"
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
                                        <span>{{ item.label }}</span>
                                    </Link>
                                </li>
                            </template>
                        </template>
                    </ul>
                </div>

                <!-- Optional footer like screenshot -->
                <div class="p-3 border-top small">
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
                </div>
            </div>
        </div>
        <!-- =================== /SIDEBAR =================== -->
    </div>

    <!-- Page Content -->
    <main>
        <slot />
    </main>
</template>

<style>
.page-wrapper {
    margin: 0 0 0 260px;
    padding: 0px 0 0;
    position: relative;
    left: 0;
    transition: all 0.2s ease;
}
.sidebar {
    background-color: white !important;
}
.slimscroll {
    width: 259px !important;
}

/* small helper to make links feel like the screenshot */
.side-link {
    text-decoration: none;
    color: #444;
    border-radius: 8px;
}
.side-link:hover {
    background: #f5f6f8;
}
li.active > .side-link {
    background: #eef2ff;
    color: #1c0d82; /* your POS primary color if desired */
    font-weight: 600;
}
</style>
