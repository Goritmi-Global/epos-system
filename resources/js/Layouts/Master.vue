<script setup>
import {
    ref,
    onMounted,
    computed,
    nextTick,
    onBeforeUnmount,
    onUpdated,
} from "vue";
import { Link } from "@inertiajs/vue3";
import { useDark, useToggle, } from "@vueuse/core";
import { Moon, Sun } from "lucide-vue-next";
import { usePage } from "@inertiajs/vue3";
import { toast } from "vue3-toastify";
import { router } from "@inertiajs/vue3";
import ConfirmModal from "@/Components/ConfirmModal.vue";
import LogoutModal from "@/Components/LogoutModal.vue";
import RestoreSystemModal from "@/Components/RestoreSystemModal.vue";
import { useAutoLogout } from '@/composables/useAutoLogout';

import Drawer from 'primevue/drawer';
import Button from 'primevue/button';
/* =========================
   Sidebar structure (array)
   ========================= */
const page = usePage();
const showConfirmRestore = ref(false);
const drawerVisible = ref(false);


// Get user info from Inertia shared data
const user = computed(() => page.props.current_user);

// ✅ Corrected: roles is an array of strings, not objects
const isCashierRole = computed(() => {
    return user.value?.roles?.includes('Cashier');
});


// Only activate auto-logout for cashiers
if (isCashierRole.value) {
    useAutoLogout();
}
// Add this function to handle the sidebar action
const handleSidebarAction = (action) => {
    console.log('Sidebar action triggered:', action);
    if (action === "systemRestore") {
        showRestoreModal.value = true; // Use the new modal
        console.log('showConfirmRestore set to:', showConfirmRestore.value);
    } else if (action === "databaseBackup") {
        showBackupModal.value = true; // 👈 Add this
    }
};

const showLogoutModal = ref(false);
const showRestoreModal = ref(false);
const showBackupModal = ref(false);
const isFullscreen = ref(false);
const showQuickOrderModal = ref(false);
const quickOrderType = ref(null);


const isCashier = computed(() => {
    const roles = logedIUser.value.roles || [];
    return roles.includes('Cashier');
});

const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen();
        isFullscreen.value = true;
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
            isFullscreen.value = false;
        }
    }
};
const handleQuickOrder = () => {
    quickOrderType.value = null;
    showQuickOrderModal.value = true;
};

const proceedToQuickOrder = () => {
    if (!quickOrderType.value) {
        toast.error("Please select an order type");
        return;
    }

    // Save to localStorage
    localStorage.setItem("quickOrderType", quickOrderType.value);

    showQuickOrderModal.value = false;
    router.visit(route('pos.order', { type: quickOrderType.value }));
};


const orderTypes = computed(() => {
    return page.props.onboarding?.service_options?.order_types || [];
});

const getOrderTypeIcon = (type) => {
    const icons = {
        'eat_in': 'home',
        'dine_in': 'home',
        'take_away': 'shopping-bag',
        'takeaway': 'shopping-bag',
        'delivery': 'truck',
    };
    return icons[type] || 'package';
};

const formatOrderTypeName = (type) => {
    const names = {
        'eat_in': 'Eat In',
        'dine_in': 'Dine In',
        'take_away': 'Take Away',
        'takeaway': 'Take Away',
        'delivery': 'Delivery',
    };
    return names[type] || type.replace(/_/g, ' ').toUpperCase();
};

const getOrderTypeDescription = (type) => {
    const descriptions = {
        'eat_in': 'Customer dines here',
        'dine_in': 'Customer dines here',
        'takeaway': 'Customer take order',
        'delivery': 'Deliver to customer',
    };
    return descriptions[type] || '';
};

const handleFullscreenChange = () => {
    isFullscreen.value = !!document.fullscreenElement;

    // If Cashier exits fullscreen (via ESC or other means), force them back in
    if (isCashier.value && !document.fullscreenElement) {
        // Small delay to prevent infinite loop
        setTimeout(() => {
            document.documentElement.requestFullscreen().catch(err => {
                console.warn('Could not re-enter fullscreen:', err);
            });
        }, 100);
    }
};

const handleKeyDown = (event) => {
    // Check if ESC key is pressed AND user is Cashier AND in fullscreen
    if (event.key === 'Escape' && isCashier.value && document.fullscreenElement) {
        event.preventDefault();
        event.stopPropagation();
        return false;
    }
};

const initializeFullscreen = async () => {
    if (isCashier.value) {
        try {
            // Wait a bit for the page to fully load
            await nextTick();

            // Small delay to ensure smooth transition
            setTimeout(async () => {
                if (!document.fullscreenElement) {
                    await document.documentElement.requestFullscreen();
                    isFullscreen.value = true;
                }
            }, 500);
        } catch (error) {
            console.error('Failed to enter fullscreen:', error);
            toast.warning('Please allow fullscreen mode for better experience');
        }
    }
};

import { watch } from 'vue';

watch(drawerVisible, (newVal) => {
    if (newVal) {
        // Wait for drawer to fully render
        nextTick(() => {
            window.feather?.replace();
        });
    }
});
watch(showQuickOrderModal, (newVal) => {
    if (newVal) {
        const modal = new bootstrap.Modal(document.getElementById('quickOrderModal'));
        modal.show();
    } else {
        const modal = bootstrap.Modal.getInstance(document.getElementById('quickOrderModal'));
        modal?.hide();
    }
});

// Optional: Listen to ESC or manual exit
document.addEventListener("fullscreenchange", () => {
    isFullscreen.value = !!document.fullscreenElement;
});
const isLoggingOut = ref(false);

const handleLogout = async () => {
    // Step 1: Close the modal instantly
    showLogoutModal.value = false;

    // Step 2: Show the fullscreen spinner
    isLoggingOut.value = true;

    // Optional short delay for a smoother effect
    await new Promise(resolve => setTimeout(resolve, 800));

    // Step 3: Perform the logout request
    try {
        await router.post(route("logout"));
    } finally {
        isLoggingOut.value = false;
    }
};
const handleSystemRestore = async () => {
    try {
        const response = await axios.post(route('system.restore'));

        if (response.data.success) {
            toast.success('System restored successfully!');
            showConfirmRestore.value = false;
            // Optionally redirect or reload
            window.location.href = route('front-page');
        }
    } catch (error) {
        console.error('System restore error:', error);

        // Extract the most meaningful message
        let message = 'Failed to restore system. Please try again.';

        if (error.response) {
            // Laravel returned a response with status code and message
            if (error.response.data?.message) {
                message = error.response.data.message;
            } else if (error.response.data?.error) {
                message = error.response.data.error;
            } else {
                message = `Error ${error.response.status}: ${error.response.statusText}`;
            }
        } else if (error.message) {
            // Network error or other JS error
            message = error.message;
        }

        toast.error(message);
    }
};



/**
 * Handle Database Backup
 * Creates and downloads a SQL backup file
 */
const handleDatabaseBackup = async () => {
    try {
        // Make API call with blob response type for file download
        const response = await axios.post(route('database.backup'), {}, {
            responseType: 'blob' // Critical: tells axios to expect binary data
        });

        // Create a temporary URL for the blob
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;

        // Extract filename from response headers or use default
        const contentDisposition = response.headers['content-disposition'];
        let filename = 'database_backup_' + new Date().toISOString().slice(0, 10) + '.sql';

        if (contentDisposition) {
            const filenameMatch = contentDisposition.match(/filename="?(.+)"?/i);
            if (filenameMatch) {
                filename = filenameMatch[1];
            }
        }

        // Trigger download
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();

        // Cleanup
        link.remove();
        window.URL.revokeObjectURL(url);

        // Show success message
        toast.success('Database backup downloaded successfully!');

        // Close modal
        showBackupModal.value = false;

    } catch (error) {
        console.error('Database backup error:', error);

        let message = 'Failed to create database backup. Please try again.';

        // Handle blob error responses
        if (error.response?.data) {
            if (error.response.data instanceof Blob) {
                try {
                    const text = await error.response.data.text();
                    const json = JSON.parse(text);
                    message = json.message || message;
                } catch (e) {
                    message = 'An error occurred while creating the backup.';
                }
            } else if (error.response.data.message) {
                message = error.response.data.message;
            }
        } else if (error.message) {
            message = error.message;
        }

        toast.error(message);
        showBackupModal.value = false;
    }
};

const userPermissions = computed(() => page.props.current_user?.permissions ?? []);
const userRoles = computed(() => page.props.current_user?.roles ?? []);
// helper
const hasPermission = (perm) => {
    if (!perm) return true; // sections or headers without route
    if (userRoles.value.includes("Super Admin")) return true;

    const permissions = Array.isArray(userPermissions.value)
        ? userPermissions.value
        : [];
    return permissions.includes(perm);
};


const formErrors = ref({});
const logedIUser = computed(() => page.props.current_user ?? {});
const businessInfo = computed(() => page.props.business_info ?? {});

const isDark = useDark({
    selector: "html",
    attribute: "class",
    valueDark: "dark",
    valueLight: "light",
});

const toggleDark = useToggle(isDark);

onMounted(() => {
    window.feather?.replace();
});

// Add verification form state
const verificationForm = ref({
    username: '',
    password: ''
});

const verificationErrors = ref({});
const isVerifying = ref(false);

// Reset verification form
const resetVerificationForm = () => {
    verificationForm.value = {
        username: '',
        password: ''
    };
    verificationErrors.value = {};
};

// Verify credentials before opening profile modal
const verifyCredentials = async () => {
    // Clear previous errors
    verificationErrors.value = {};

    // Validate inputs
    if (!verificationForm.value.username) {
        verificationErrors.value.username = 'Username is required';
        // 👇 Show toast error
        toast.error('Username is required');
        return;
    }

    if (!verificationForm.value.password) {
        verificationErrors.value.password = 'Password is required';
        // 👇 Show toast error
        toast.error('Password is required');
        return;
    }

    isVerifying.value = true;

    try {
        const response = await axios.post('/api/profile/verify-credentials', {
            username: verificationForm.value.username,
            password: verificationForm.value.password
        });

        if (response.data.success) {
            // Close verification modal
            const verifyModal = bootstrap.Modal.getInstance(
                document.getElementById('verifyPasswordModal')
            );
            verifyModal.hide();

            // Reset form
            resetVerificationForm();

            // Show success message
            toast.success('Identity verified successfully');

            // Small delay then open profile modal
            setTimeout(() => {
                const profileModal = new bootstrap.Modal(
                    document.getElementById('userProfileModal')
                );
                profileModal.show();
            }, 300);
        }
    } catch (error) {
        console.error('Verification error:', error);

        if (error.response?.status === 422) {
            // Validation errors - show first error in toast
            verificationErrors.value = error.response.data.errors || {};

            // 👇 Get first error message and show in toast
            const firstError = Object.values(verificationErrors.value)[0];
            if (Array.isArray(firstError)) {
                toast.error(firstError[0]);
            } else {
                toast.error(firstError || 'Validation error occurred');
            }
        } else if (error.response?.status === 401) {
            // Invalid credentials - show in toast
            const errorMessage = 'Invalid username or password';
            verificationErrors.value.general = errorMessage;
            // 👇 Show toast error
            toast.error(errorMessage);
        } else {
            // Generic error - show in toast
            const errorMessage = error.response?.data?.message || 'An error occurred. Please try again.';
            verificationErrors.value.general = errorMessage;
            // 👇 Show toast error
            toast.error(errorMessage);
        }
    } finally {
        isVerifying.value = false;
    }
};

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
                        route: "menu-categories.index",
                    },
                    { 
                        label: "Menus", 
                        icon: "box", 
                        route: "menu.index" 
                    },
                    { 
                        label: "Deals", 
                        icon: "gift", 
                        route: "deals.index" 
                    },
                
                    {
                        label: "Addon Groups",
                        icon: "layers",
                        route: "addon-groups.index",
                    },
                    {
                        label: "Addons",
                        icon: "plus-circle",
                        route: "addons.index",
                    },
                    {
                        label: "Promo",
                        icon: "tag",
                        route: "promos.index",
                    },
                    {
                        label: "Discount",
                        icon: "percent",
                        route: "discounts.index",
                    },
                    {
                        label: "Meals",
                        icon: "coffee",
                        route: "meals.index",
                    },
                ],
            },
            { label: "Sale", icon: "shopping-bag", route: "pos.order" },
            { label: "Orders", icon: "list", route: "orders.index" },
            { label: "KOT", icon: "clipboard", route: "kots.index" },
            { label: "Payment", icon: "credit-card", route: "payment.index" },
            {
                label: "Analytics",
                icon: "bar-chart-2",
                route: "analytics.index",
            },
            {
                label: "Shift Management",
                icon: "users",
                route: "shift.index",
            },
            { label: "Settings", icon: "settings", route: "settings.index" },
            { label: "Restore System", icon: "refresh-cw", action: "systemRestore" },
            { label: "Backup", icon: "database", action: "databaseBackup" },
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
const breakpoint = ref("desktop");
const sidebarOpen = ref(true); // desktop: expanded by default
const isMobile = ref(false);
const isTablet = ref(false);
const isDesktop = ref(true);

const sidebarExpanded = ref(true); // desktop: expanded by default
const overlayOpen = ref(false); // only meaningful on mobile

const evaluateBreakpoint = () => {
    const w = window.innerWidth;
    if (w < 768) {
        // Mobile
        breakpoint.value = "mobile";
        isMobile.value = true;
        isTablet.value = false;
        isDesktop.value = false;
        overlayOpen.value = false; // hide overlay by default on resize
        sidebarExpanded.value = false; // persistent state irrelevant on mobile
    } else if (w < 992) {
        // Tablet
        breakpoint.value = "tablet";
        isMobile.value = false;
        isTablet.value = true;
        isDesktop.value = false;
        // Collapse the sidebar on tablet by default (icons-only).
        sidebarExpanded.value = false;
        overlayOpen.value = false;
    } else {
        // Desktop
        breakpoint.value = "desktop";
        isMobile.value = false;
        isTablet.value = false;
        isDesktop.value = true;
        sidebarExpanded.value = true; // expanded on desktop
        overlayOpen.value = false;
    }
};

const toggleSidebar = () => {
    if (isMobile.value || isTablet.value) {
        // Use drawer for mobile and tablet
        drawerVisible.value = !drawerVisible.value;
    } else {
        // Desktop uses expand/collapse
        sidebarExpanded.value = !sidebarExpanded.value;
    }
};

const handleMenuClick = (routeName) => {
    if (routeName) {
        router.visit(route(routeName));
        // Close drawer on mobile/tablet after navigation
        if (isMobile.value || isTablet.value) {
            drawerVisible.value = false;
        }
    }
};

const handleDrawerAction = (action) => {
    handleSidebarAction(action);
    // Close drawer after action
    if (isMobile.value || isTablet.value) {
        drawerVisible.value = false;
    }
};

const evaluateMobile = () => {
    isMobile.value = window.innerWidth < 992;
};

onMounted(() => {

    document.addEventListener('fullscreenchange', handleFullscreenChange);

    document.addEventListener('keydown', handleKeyDown, true);

    // Initialize fullscreen for Cashier
    initializeFullscreen();
    evaluateBreakpoint();
    window.addEventListener("resize", evaluateBreakpoint, { passive: true });
    // feather icons setup and open active groups (same as before)
    window.feather?.replace();
    openActiveGroups();
});
onBeforeUnmount(() => {
    document.removeEventListener('fullscreenchange', handleFullscreenChange);
    document.removeEventListener('keydown', handleKeyDown, true);
    window.removeEventListener('resize', evaluateBreakpoint);
});
onUpdated(() => window.feather?.replace());

// const toggleSidebar = () => {
//     sidebarOpen.value = !sidebarOpen.value;
// };

// onMounted(() => {
//     evaluateMobile();
//     window.addEventListener("resize", evaluateMobile, { passive: true });
//     window.feather?.replace();
//     openActiveGroups();
// });
// onBeforeUnmount(() => window.removeEventListener("resize", evaluateMobile));
// onUpdated(() => window.feather?.replace());

// Modal form state
const profileForm = ref({
    username: logedIUser.value.name ?? "",
    password: "",
    pin: "",
    role: logedIUser.value.roles[0] ?? "",
});

const updateProfile = async () => {
    try {
        const response = await axios.post("/api/profile/update", profileForm.value);

        if (response.data.success) {

            toast.success("Profile updated successfully");
            const modal = bootstrap.Modal.getInstance(
                document.getElementById("userProfileModal")
            );
            modal.hide();
        } else {
            alert("Something went wrong!");
        }
    } catch (error) {
        if (error?.response?.status === 422 && error.response.data?.errors) {
            formErrors.value = error.response.data.errors;

            toast.error("Please fill in all required fields correctly.");
        }

    }
};

// Reset form errors and optionally fields when modal closes
onMounted(() => {
    const modalEl = document.getElementById("userProfileModal");
    modalEl.addEventListener("hidden.bs.modal", () => {
        formErrors.value = {};
        profileForm.value.password = "";
        profileForm.value.pin = "";
    });
});
// ------------------ Notifications --------------------------

const notifications = ref([]);
const unreadCount = ref(0);

const fetchNotifications = async () => {
    const response = await axios.get("/api/notifications");
    notifications.value = response.data;
    unreadCount.value = notifications.value.filter(n => !n.is_read).length;
};




// Mark single notification as read
const markAsRead = async (id) => {
    try {
        await axios.post(`/api/notifications/mark-as-read/${id}`)
        const notif = notifications.value.find(n => n.id === id)
        if (notif && !notif.is_read) {
            notif.is_read = true
            unreadCount.value--
        }
    } catch (error) {
        console.error('Error marking as read:', error)
    }
}

// Mark all as read
const markAllAsRead = async () => {
    try {
        await axios.post('/api/notifications/mark-all-as-read')
        notifications.value.forEach(n => n.is_read = true)
        unreadCount.value = 0
    } catch (error) {
        console.error('Error marking all as read:', error)
    }
}


onMounted(fetchNotifications);

</script>

<template>
    <div class="layout-root" :class="{
        /* explicit breakpoint classes */
        'state-desktop': isDesktop,
        'state-tablet': isTablet,
        'state-mobile': isMobile,

        /* whether sidebar is logically open:
       - on mobile -> overlayOpen (overlay visible)
       - on tablet/desktop -> sidebarExpanded (persistent expanded)
    */
        'sidebar-open': isMobile ? overlayOpen : sidebarExpanded,

        /* collapsed mini state applies to persistent sidebar (desktop/tablet) */
        'sidebar-collapsed': (isDesktop || isTablet) && !sidebarExpanded,

        /* keeps mobile-specific overlay behaviour */
        'sidebar-overlay': isMobile,
    }">
        <!-- =================== HEADER =================== -->
        <header class="header">
            <div class="header-left">
                <img :src="businessInfo.image_url" alt="logo" width="50" height="50px"
                    class="rounded-full border shadow" />

                <h5 class="fw-bold">{{ businessInfo.business_name }}</h5>

                <!-- Toggle button: uses new toggleSidebar (behaviour differs by breakpoint) -->
                <button class="icon-btn" @click="toggleSidebar" aria-label="Toggle sidebar">
                    <i data-feather="menu"></i>
                </button>
            </div>

            <div class="header-center">
                <!-- <div class="top-nav-search">
                    < 
                    <input type="text" placeholder="Search Here ..." />
                </div> -->
            </div>

            <ul class="nav user-menu">
                <button class="btn btn-primary rounded-pill py-2 px-3" @click="handleQuickOrder">
                    Quick Order
                </button>

                <button class="btn btn-danger rounded-pill py-2 px-3 d-flex align-items-center"
                    @click="showLogoutModal = true">
                    Logout
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="feather feather-log-out ms-2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                </button>


                <li class="nav-item" v-if="!isCashier">
                    <button class="icon-btn" @click="toggleFullscreen" title="Toggle Fullscreen">
                        <i :data-feather="isFullscreen ? 'minimize' : 'maximize'"></i>
                    </button>
                </li>



                <li class="nav-item">
                    <button class="icon-btn" @click="toggleDark()">
                        <Sun v-if="isDark" :size="20" />
                        <Moon v-else :size="20" />
                    </button>
                </li>
                <!-- <li class="nav-item dropdown has-arrow flag-nav">
                    <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="javascript:void(0);">
                        <img src="/assets/img/flags/us1.png" alt="" height="20" />
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a href="javascript:void(0);" class="dropdown-item"><img src="/assets/img/flags/us.png"
                                height="16" />
                            English</a>
                        <a href="javascript:void(0);" class="dropdown-item"><img src="/assets/img/flags/fr.png"
                                height="16" />
                            French</a>
                        <a href="javascript:void(0);" class="dropdown-item"><img src="/assets/img/flags/es.png"
                                height="16" />
                            Spanish</a>
                        <a href="javascript:void(0);" class="dropdown-item"><img src="/assets/img/flags/de.png"
                                height="16" />
                            German</a>
                    </div>
                </li> -->

                <!-- <li class="nav-item dropdown">
                    <a href="javascript:void(0);" class="dropdown-toggle nav-link" data-bs-toggle="dropdown">
                        <img src="/assets/img/icons/notification-bing.svg" alt="noti" />
                        <span class="badge rounded-pill">4</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end notifications">
                        <div class="topnav-dropdown-header d-flex align-items-center justify-content-between">
                            <span class="notification-title">Notifications</span>
                            <a href="javascript:void(0)" class="clear-noti">Clear All</a>
                        </div>
                        <div class="noti-content p-3">
                            No new notifications.
                        </div>
                        <div class="topnav-dropdown-footer">
                            <a href="javascript:void(0)">View all Notifications</a>
                        </div>
                    </div>
                </li> -->

                <li class="nav-item dropdown">
                    <a href="javascript:void(0);" class="dropdown-toggle nav-link" data-bs-toggle="dropdown">
                        <img src="/assets/img/icons/notification-bing.svg" alt="noti" />
                        <span class="badge rounded-pill bg-danger" v-if="unreadCount > 0">{{ unreadCount }}</span>
                    </a>

                    <div class="dropdown-menu dropdown-menu-end notifications shadow-lg">
                        <!-- Header -->
                        <div
                            class="topnav-dropdown-header d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                            <span class="notification-title fw-bold">Notifications</span>

                            <!-- Mark all as read -->
                            <a href="javascript:void(0)" class="text-primary fw-semibold" style="font-size: 0.9rem;"
                                @click.stop="markAllAsRead">
                                Mark all as read
                                <span v-if="unreadCount > 0" class="text-primary">({{ unreadCount }})</span>
                            </a>

                            <!-- <a href="javascript:void(0)" class="small text-danger"
                                    @click="notifications = []; unreadCount = 0">
                                    Clear All
                                </a> -->
                        </div>

                        <!-- Notification List -->
                        <div class="noti-content max-h-[350px] overflow-y-auto">
                            <template v-if="notifications.length">
                                <div v-for="n in notifications" :key="n.id"
                                    class="d-flex align-items-start justify-content-between p-3 border-bottom  cursor-pointer transition-all"
                                    :class="n.is_read ? 'bg-gray-50 m-2 mb-2' : 'bg-white shadow-sm m-2'"
                                    @click.stop="markAsRead(n.id)">
                                    <!-- Left content -->
                                    <div class="flex-grow-1 me-2">
                                        <div class="fw-semibold text-sm text-gray-800">{{ n.message }}</div>

                                        <!-- Tailwind-style Status badge -->
                                        <span
                                            class="inline-flex notifi-span items-center rounded-full px-2 py-0.5 text-xs font-medium mt-1"
                                            :class="{
                                                'text-red-700 bg-red-300': n.status?.toLowerCase() === 'out_of_stock',
                                                'text-yellow-700 bg-yellow-100': n.status?.toLowerCase() === 'low_stock',
                                                'text-orange-700 bg-orange-200': n.status?.toLowerCase() === 'expired',
                                                'text-blue-700 bg-blue-100': n.status?.toLowerCase() === 'near_expiry'
                                            }">
                                            {{ n.status.replace(/_/g, ' ').toUpperCase() }}
                                        </span>



                                        <!-- Date -->
                                        <small class="text-muted mt-1 d-block" style="font-size: 0.7rem;">
                                            {{ new Date(n.created_at).toLocaleString('en-US', {
                                                dateStyle: 'medium',
                                                timeStyle: 'short'
                                            }) }}
                                        </small>
                                    </div>

                                    <!-- Right side NEW badge -->
                                    <div v-if="!n.is_read" class="align-self-start">
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full new-badge bg-green-100 text-green-600">
                                            NEW
                                        </span>
                                    </div>
                                </div>
                            </template>

                            <div v-else class="p-3 text-center text-muted small">No notifications</div>
                        </div>

                        <!-- Footer (optional) -->
                        <!-- <div class="topnav-dropdown-footer text-center pb-2 fw-bold border-top">
                            <a href="javascript:void(0)">View all Notifications</a>
                        </div> -->
                    </div>
                </li>






                <li class="nav-item dropdown has-arrow main-drop cursor-pointer" data-bs-toggle="modal"
                    data-bs-target="#verifyPasswordModal">
                    <span class="user-img">
                        <i class="bi bi-person-circle"></i>
                    </span>
                    <div class="ms-2 d-none d-sm-block">
                        <b class="fw-bold text-black">{{ logedIUser.name }}</b>
                        <br />
                        <small class="super-admin text-black">{{ logedIUser.roles[0] }}</small>
                    </div>
                </li>
            </ul>
        </header>
        <!-- =================== /HEADER =================== -->

        <!-- =================== SIDEBAR =================== -->
        <!-- Replace your sidebar section in the template with this updated version -->

        <aside v-if="isDesktop" class="sidebar" id="sidebar" aria-label="Primary">
            <div class="sidebar-inner">
                <div id="sidebar-menu" class="sidebar-menu px-2">
                    <ul class="mb-3">
                        <template v-for="block in sidebarMenus" :key="block.label || block.section">
                            <!-- Simple top item -->
                            <li v-if="!block.section && hasPermission(block.route)"
                                :class="{ active: isActive(block.route) }">
                                <button class="d-flex align-items-center side-link px-3 py-2 w-100 border-0  text-start"
                                    @click="router.visit(route(block.route))">
                                    <i :data-feather="block.icon" class="me-2 icons"></i>
                                    <span class="truncate-when-mini">{{ block.label }}</span>
                                </button>
                            </li>

                            <!-- Section -->
                            <template v-else>
                                <li class="mt-3 mb-1 px-3 text-muted text-uppercase small section-title truncate-when-mini"
                                    v-if="block.section && block.children.some(child => hasPermission(child.route))">
                                    {{ block.section }}
                                </li>

                                <template v-for="item in block.children" :key="item.label">
                                    <!-- Dropdown group -->
                                    <li v-if="item.children && item.children.length && item.children.some(child => hasPermission(child.route))"
                                        class="dropdown-parent">
                                        <button class="d-flex align-items-center side-link px-3 py-2 w-100 border-0"
                                            :class="{ active: openGroups.has(item.label) || isAnyChildActive(item.children) }"
                                            @click="toggleGroup(item.label)" type="button" :title="item.label">
                                            <i :data-feather="item.icon" class="me-2"></i>
                                            <span class="flex-grow-1 text-start truncate-when-mini">{{ item.label
                                            }}</span>
                                            <i class="chevron-icon"
                                                :data-feather="openGroups.has(item.label) || isAnyChildActive(item.children) ? 'chevron-up' : 'chevron-down'"></i>
                                        </button>

                                        <ul class="list-unstyled my-1 submenu-dropdown"
                                            :class="{ 'expanded': openGroups.has(item.label) || isAnyChildActive(item.children) }">
                                            <li v-for="child in item.children" :key="child.label"
                                                v-if="hasPermission(child?.route)"
                                                :class="{ active: isActive(child.route) }">
                                                <button
                                                    class="d-flex align-items-center side-link px-3 py-2 w-100 border-0 text-start"
                                                    @click="router.visit(route(child.route))">
                                                    <i :data-feather="child.icon" class="me-2"></i>
                                                    <span>{{ child.label }}</span>
                                                </button>
                                            </li>
                                        </ul>
                                    </li>

                                    <!-- Flat item -->
                                    <li v-else-if="item.route && hasPermission(item.route)"
                                        :class="{ active: item.route ? isActive(item.route) : false }"
                                        class="side-link">
                                        <button
                                            class="d-flex align-items-center side-link px-3 py-2 w-100 border-0  text-start"
                                            @click="router.visit(route(item.route))" :title="item.label">
                                            <i :data-feather="item.icon" class="me-2"></i>
                                            <span class="truncate-when-mini">{{ item.label }}</span>
                                        </button>
                                    </li>

                                    <!-- Action item -->
                                    <li v-else-if="item.action && hasPermission(item.action)" class="side-link">
                                        <button @click="handleSidebarAction(item.action)"
                                            class="d-flex align-items-center side-link px-3 py-2 w-100 border-0"
                                            :title="item.label">
                                            <i :data-feather="item.icon" class="me-2"></i>
                                            <span class="truncate-when-mini">{{ item.label }}</span>
                                        </button>
                                    </li>
                                </template>
                            </template>
                        </template>
                    </ul>
                </div>
            </div>
        </aside>
        <Drawer v-model:visible="drawerVisible" :position="'left'" class="sidebar-drawer">
            <template #container="{ closeCallback }">
                <div class="flex flex-col h-full bg-white dark:bg-gray-900">
                    <!-- Drawer Header -->
                    <div class="flex items-center justify-between px-4 pt-4 pb-3 border-b dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <img :src="businessInfo.image_url" alt="logo" width="40" height="40"
                                class="rounded-full border shadow" />
                            <span class="font-bold text-lg text-black dark:text-white">{{ businessInfo.business_name
                                }}</span>
                        </div>


                        <button
                            class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                            @click="closeCallback" data-bs-dismiss="modal" aria-label="Close" title="Close">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Drawer Menu Content -->
                    <div class="overflow-y-auto flex-1 px-2 py-3">
                        <ul class="list-none p-0 m-0">
                            <template v-for="block in sidebarMenus" :key="block.label || block.section">
                                <!-- Simple top item -->
                                <li v-if="!block.section && hasPermission(block.route)"
                                    :class="{ 'active': isActive(block.route) }" class="mb-1">
                                    <button
                                        class="drawer-link w-full flex items-center px-4 py-3 rounded-lg text-left transition-colors"
                                        @click="handleMenuClick(block.route)">
                                        <i :data-feather="block.icon" class="w-5 h-5 mr-3"></i>
                                        <span class="font-medium">{{ block.label }}</span>
                                    </button>
                                </li>

                                <!-- Section -->
                                <template v-else>
                                    <li class="drawer-section-title mt-4 mb-2 px-4 text-dark text-xs font-semibold uppercase tracking-wider"
                                        v-if="block.section && block.children.some(child => hasPermission(child.route))">
                                        {{ block.section }}
                                    </li>

                                    <template v-for="item in block.children" :key="item.label">
                                        <!-- Dropdown group -->
                                        <li v-if="item.children && item.children.length && item.children.some(child => hasPermission(child.route))"
                                            class="mb-1">
                                            <button
                                                class="drawer-link w-full flex items-center justify-between px-4 py-3 rounded-lg text-left transition-colors"
                                                :class="{ 'active': openGroups.has(item.label) || isAnyChildActive(item.children) }"
                                                @click="toggleGroup(item.label)" type="button">
                                                <div class="flex items-center">
                                                    <i :data-feather="item.icon" class="w-5 h-5 mr-3"></i>
                                                    <span class="font-medium">{{ item.label }}</span>
                                                </div>
                                                <i class="chevron-icon"
                                                    :data-feather="openGroups.has(item.label) || isAnyChildActive(item.children) ? 'chevron-up' : 'chevron-down'"></i>

                                            </button>

                                            <!-- Submenu -->
                                            <ul v-show="openGroups.has(item.label) || isAnyChildActive(item.children)"
                                                class="list-none pl-8 mt-1 space-y-1">
                                                <li v-for="child in item.children" :key="child.label"
                                                    v-if="hasPermission(child?.route)"
                                                    :class="{ 'active': isActive(child.route) }">
                                                    <button
                                                        class="drawer-link w-full flex items-center px-4 py-2 rounded-lg text-left text-sm transition-colors"
                                                        @click="handleMenuClick(child.route)">
                                                        <i :data-feather="child.icon" class="w-4 h-4 mr-3"></i>
                                                        <span>{{ child.label }}</span>
                                                    </button>
                                                </li>
                                            </ul>
                                        </li>

                                        <!-- Flat item -->
                                        <li v-else-if="item.route && hasPermission(item.route)"
                                            :class="{ 'active': isActive(item.route) }" class="mb-1">
                                            <button
                                                class="drawer-link w-full flex items-center px-4 py-3 rounded-lg text-left transition-colors"
                                                @click="handleMenuClick(item.route)">
                                                <i :data-feather="item.icon" class="w-5 h-5 mr-3"></i>
                                                <span class="font-medium">{{ item.label }}</span>
                                            </button>
                                        </li>

                                        <!-- Action item -->
                                        <li v-else-if="item.action && hasPermission(item.action)" class="mb-1">
                                            <button
                                                class="drawer-link w-full flex items-center px-4 py-3 rounded-lg text-left transition-colors"
                                                @click="handleDrawerAction(item.action)">
                                                <i :data-feather="item.icon" class="w-5 h-5 mr-3"></i>
                                                <span class="font-medium">{{ item.label }}</span>
                                            </button>
                                        </li>
                                    </template>
                                </template>
                            </template>
                        </ul>
                    </div>
                </div>
            </template>
        </Drawer>
        <!-- Confirm Modal (keep outside sidebar) -->
        <RestoreSystemModal v-if="showRestoreModal" :show="showRestoreModal" title="Confirm System Restore"
            message="Are you sure you want to restore the system? This will reset all data to default settings. This action cannot be undone."
            confirmLabel="Yes, Restore" @confirm="handleSystemRestore" @cancel="showRestoreModal = false" />

        <RestoreSystemModal v-if="showBackupModal" :show="showBackupModal" title="Confirm Database Backup"
            message="Are you sure you want to create a database backup? The backup file will be downloaded to your computer."
            confirmLabel="Yes, Backup" @confirm="handleDatabaseBackup" @cancel="showBackupModal = false" />

        <LogoutModal v-if="showLogoutModal" :show="showLogoutModal" :loading="isLoggingOut" @confirm="handleLogout"
            @cancel="showLogoutModal = false" />

        <!-- Full Page Centered Spinner -->
        <div v-if="isLoggingOut"
            class="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center bg-dark bg-opacity-50"
            style="z-index: 9999;">
            <div class="spinner-border text-light" role="status" style="width: 3rem; height: 3rem;"></div>
        </div>



        <!-- Mobile overlay backdrop -->
        <div v-if="isMobile && overlayOpen" class="overlay-backdrop" aria-hidden="true" @click="toggleSidebar"></div>
        <!-- =================== /SIDEBAR =================== -->

        <!-- =================== PAGE CONTENT =================== -->
        <!-- <main class="page-wrapper"> -->
        <main class="content bg-white dark:bg-gray-900 text-black dark:text-white">
            <slot />
        </main>
    </div>

    <footer class="footer bg-white dark:bg-gray-800 border-top sticky bottom-0">
        <div class="container-fluid">
            <div class="row align-items-center py-3">
                <!-- Left: Empty or additional content -->
                <div class="col-md-4"></div>

                <!-- Center: Powered by -->
                <div class="col-md-4 text-center">
                    <span class="text-muted">
                        Powered by <strong class="text-primary">10XGLOBAL</strong>
                    </span>
                </div>

                <!-- Right: Contact Link -->
                <div class="col-md-4 text-end">
                    <a href="#" class="text-decoration-none hover:text-primary-dark">
                        Need Help? <strong class="text-primary">Contact Us</strong>
                    </a>
                </div>
            </div>
        </div>
    </footer>


    <!-- Password Verification Modal -->
    <div class="modal fade" id="verifyPasswordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-black rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Verify Your Identity</h5>
                    <button
                        class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                        data-bs-dismiss="modal" aria-label="Close" title="Close" @click="resetVerificationForm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="modal-body">
                    <p class="text-muted mb-3">Please verify your credentials to access profile settings</p>

                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" :class="{ 'is-invalid': verificationErrors.username }"
                            v-model="verificationForm.username" @keyup.enter="verifyCredentials"
                            placeholder="Enter your username" />
                        <div v-if="verificationErrors.username" class="invalid-feedback">
                            {{ verificationErrors.username }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control"
                            :class="{ 'is-invalid': verificationErrors.password }" v-model="verificationForm.password"
                            @keyup.enter="verifyCredentials" placeholder="Enter your password" />
                        <div v-if="verificationErrors.password" class="invalid-feedback">
                            {{ verificationErrors.password }}
                        </div>
                    </div>

                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-2 py-2" data-bs-dismiss="modal"
                        @click="resetVerificationForm">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-primary rounded-pill px-2 py-2" @click="verifyCredentials"
                        :disabled="isVerifying">
                        <span v-if="isVerifying" class="spinner-border spinner-border-sm me-2"></span>
                        {{ isVerifying ? 'Verifying...' : 'Verify' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- user data update modal -->
    <div class="modal fade" id="userProfileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content text-black rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">User Profile</h5>
                    <button
                        class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                        data-bs-dismiss="modal" aria-label="Close" title="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Username -->
                        <div class="col-md-6">
                            <label class="form-label">UserName</label>
                            <input type="text" class="form-control" :class="{ 'is-invalid': formErrors.username }"
                                v-model="profileForm.username" />
                            <div v-if="formErrors.username" class="invalid-feedback">
                                {{ formErrors.username[0] }}
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control"
                                placeholder="Enter new password (leave blank to keep current)"
                                :class="{ 'is-invalid': formErrors.password }" v-model="profileForm.password" />
                            <div v-if="formErrors.password" class="invalid-feedback">
                                {{ formErrors.password[0] }}
                            </div>
                        </div>

                        <!-- Pin -->
                        <div class="col-md-6">
                            <label class="form-label">Pin</label>
                            <input type="text" class="form-control" :class="{ 'is-invalid': formErrors.pin }"
                                placeholder="Enter new PIN (leave blank to keep current)" v-model="profileForm.pin" />
                            <div v-if="formErrors.pin" class="invalid-feedback">
                                {{ formErrors.pin[0] }}
                            </div>
                        </div>

                        <!-- Role -->
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control" :class="{ 'is-invalid': formErrors.role }"
                                v-model="profileForm.role" readonly />
                            <div v-if="formErrors.role" class="invalid-feedback">
                                {{ formErrors.role[0] }}
                            </div>
                        </div>
                    </div>
                </div>


                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-primary py-2 px-2 w-30 rounded-pill" @click="updateProfile">
                        Update
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- Quick Order Type Modal -->
    <div class="modal fade" id="quickOrderModal" tabindex="-1" aria-hidden="true" ref="quickOrderModalRef">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Select Order Type</h5>
                    <button
                        class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                        data-bs-dismiss="modal" aria-label="Close" title="Close" @click="showQuickOrderModal = false">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="modal-body">
                    <p class="text-muted mb-4">Please select how the customer will receive their order:</p>
                    <div class="row g-3">
                        <!-- Dynamic Order Types -->
                        <div v-for="orderType in orderTypes" :key="orderType" class="col-md-6">
                            <button
                                class="card-option w-100 p-4 border rounded-3 cursor-pointer transition d-flex align-items-center justify-content-center gap-3"
                                :class="{ 'border-primary bg-light': !isDark && quickOrderType === orderType, 'border-primary dark-selected': isDark && quickOrderType === orderType }"
                                @click="quickOrderType = orderType">
                                <i :data-feather="getOrderTypeIcon(orderType)" class="flex-shrink-0"
                                    style="width: 28px; height: 28px;"></i>
                                <div class="text-start">
                                    <div class="fw-bold">{{ formatOrderTypeName(orderType) }}</div>
                                    <small class="text-muted">{{ getOrderTypeDescription(orderType) }}</small>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4 py-2" data-bs-dismiss="modal"
                        @click="showQuickOrderModal = false">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-primary rounded-pill px-4 py-2" @click="proceedToQuickOrder">
                        Quick Order
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
/* ========= CSS VARIABLES ========= */
:root {
    --header-h: 64px;
    --sidebar-w: 280px;
    /* desktop full width */
    --sidebar-w-tablet: 220px;
    /* tablet full width (slightly smaller) */
    --sidebar-w-collapsed: 70px;
    /* collapsed (icons-only) width for desktop/tablet */
    --brand: #1b2850;
    --bg-muted: #f5f6f8;
    --border: #eef0f3;
}



/* Show submenu on hover when sidebar is collapsed */
.sidebar-collapsed .dropdown-parent:hover .submenu-dropdown {
    display: block !important;
    opacity: 1;
    visibility: visible;
    transform: translateX(100%);
    transition: all 0.2s ease;
}



@media (max-width: 991px) {
    .header .header-left {
        position: absolute;
        width: 23%;
    }

    .state-tablet .page-wrapper {
        margin-left: 0 !important;
    }

    .sidebar {
        display: none !important;
    }

    .page-wrapper {
        margin-left: 0 !important;
    }

}

.sidebar-drawer .p-drawer-content {
    padding: 0 !important;
}

/* Drawer link styles */
.drawer-link {
    color: #1b2850;
    background: transparent;
    border: none;
}

.drawer-link:hover {
    background: #1b2850;
    color: #fff !important;
}

.drawer-link.active,
li.active .drawer-link {
    background: #1b2850;
    color: #ffffff;
}

/* Dark mode drawer styles */
html.dark .drawer-link {
    color: #ffffff;
}

html.dark .drawer-link:hover {
    background: #374151;
    color: #ffffff;
}

html.dark .drawer-link.active,
html.dark li.active .drawer-link {
    background: #1b2850;
    color: #ffffff;
}


/* Ensure feather icons render properly in drawer */
.drawer-link i[data-feather] {
    width: 20px;
    height: 20px;
    stroke-width: 2;
}

/* Adjust submenu positioning */
.sidebar-collapsed .submenu-dropdown {
    position: absolute;
    top: 0;
    left: 100%;
    background: #fff;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
    border-radius: 6px;
    min-width: 200px;
    display: none;
    opacity: 0;
    visibility: hidden;
    z-index: 1000;
}

/* Style links inside submenu */
.sidebar-collapsed .submenu-dropdown a {
    padding: 8px 15px;
    display: block;
    color: #333;
    text-decoration: none;
}


.card-option {
    background: white;
    border: 1px solid #e0e0e0 !important;
    cursor: pointer;
    transition: all 0.3s ease;
}

.card-option:hover {
    border-color: #0d6efd !important;
    background-color: #f8f9fa;
}

.card-option.border-primary {
    background-color: #e7f1ff !important;
    border-color: #0d6efd !important;
}

.dark .card-option.border-primary {
    background-color: #318d25 !important;
    border-color: #0d6efd !important;
}

.dark .card-option{
    background-color: #212121 !important;
    color: #fff !important;
}

:global(.dark) .card-option:hover {
    border-color: #0d6efd !important;
    background-color: #333333;
}

:global(.dark) .card-option.dark-selected {
    background-color: #1a3a5c !important;
    border-color: #0d6efd !important;
}

:global(.dark) .card-option small {
    color: #999999 !important;
}


/* ========= SUBMENU HOVER FOR COLLAPSED SIDEBAR ========= */
/* ========= SUBMENU STYLES ========= */

/* Normal state - collapsed submenu */
.sidebar .submenu-dropdown {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

/* Expanded state - show submenu */
.sidebar .submenu-dropdown.expanded {
    max-height: 500px;
}

/* ========= COLLAPSED SIDEBAR SPECIFIC ========= */

/* Position parent for absolute submenu */
.state-desktop.sidebar-collapsed .sidebar-menu>ul>li.dropdown-parent,
.state-tablet.sidebar-collapsed .sidebar-menu>ul>li.dropdown-parent {
    position: relative;
}

/* Hide chevron when collapsed */
.state-desktop.sidebar-collapsed .sidebar .chevron-icon,
.state-tablet.sidebar-collapsed .sidebar .chevron-icon {
    display: none !important;
}

/* When collapsed: position submenu to the right */
.state-desktop.sidebar-collapsed .sidebar .submenu-dropdown,
.state-tablet.sidebar-collapsed .sidebar .submenu-dropdown {
    position: absolute;
    left: 100%;
    top: 0;
    background: #fff;
    border: 1px solid #eef0f3;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    min-width: 220px;
    max-width: 280px;
    z-index: 9999;
    padding: 8px 0;
    margin-left: 8px;
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

/* Show on parent hover - CRITICAL */
.state-desktop.sidebar-collapsed .dropdown-parent:hover>.submenu-dropdown,
.state-tablet.sidebar-collapsed .dropdown-parent:hover>.submenu-dropdown {
    max-height: 600px;
    overflow-y: auto;
}

/* Style submenu items */
.state-desktop.sidebar-collapsed .sidebar .submenu-dropdown li .side-link {
    padding: 12px 16px !important;
    display: flex !important;
    align-items: center !important;
    white-space: nowrap !important;
    padding-left: 16px !important;
}

/* Show submenu text when collapsed */
.state-desktop.sidebar-collapsed .sidebar .submenu-dropdown li .side-link span,
.state-tablet.sidebar-collapsed .sidebar .submenu-dropdown li .side-link span {
    display: inline-block !important;
    max-width: none !important;
    overflow: visible !important;
    white-space: nowrap !important;
}

/* Icon spacing */
.state-desktop.sidebar-collapsed .sidebar .submenu-dropdown li .side-link i,
.state-tablet.sidebar-collapsed .sidebar .submenu-dropdown li .side-link i {
    margin-right: 12px !important;
}

/* Remove left border when collapsed */
.state-desktop.sidebar-collapsed .sidebar .submenu-dropdown,
.state-tablet.sidebar-collapsed .sidebar .submenu-dropdown {
    border-left: none;
    margin-left: 8px;
    padding-left: 0;
}

/* Dark mode */
html.dark .state-desktop.sidebar-collapsed .sidebar .submenu-dropdown,
html.dark .state-tablet.sidebar-collapsed .sidebar .submenu-dropdown {
    background: #181818;
    border-color: #333;
}

/* Make sure expanded class works in collapsed mode too */
.state-desktop.sidebar-collapsed .dropdown-parent:hover>.submenu-dropdown.expanded,
.state-tablet.sidebar-collapsed .dropdown-parent:hover>.submenu-dropdown.expanded {
    max-height: 600px;
}

/* ========= COLLAPSED SIDEBAR (ICONS ONLY) ========= */
.state-desktop.sidebar-collapsed .sidebar,
.state-tablet.sidebar-collapsed .sidebar {
    width: var(--sidebar-w-collapsed);
    overflow: visible;
}

/* Remove padding from menu container */
.state-desktop.sidebar-collapsed .sidebar-menu,
.state-tablet.sidebar-collapsed .sidebar-menu {
    padding-left: 0 !important;
    padding-right: 0 !important;
}

/* Center all list items */
.state-desktop.sidebar-collapsed .sidebar-menu>ul>li,
.state-tablet.sidebar-collapsed .sidebar-menu>ul>li {
    text-align: center;
    display: block;
}

/* Center icons and remove all padding/margins */
.state-desktop.sidebar-collapsed .sidebar .side-link,
.state-tablet.sidebar-collapsed .sidebar .side-link {
    justify-content: center !important;
    padding: 12px 0 !important;
    margin: 0 !important;
    display: flex !important;
    align-items: center !important;
}

/* Icons only — remove margins and ensure centering */
.state-desktop.sidebar-collapsed .sidebar .side-link i,
.state-tablet.sidebar-collapsed .sidebar .side-link i {
    margin: 0 !important;
}

/* Hide all text labels */
.state-desktop.sidebar-collapsed .sidebar .truncate-when-mini,
.state-tablet.sidebar-collapsed .sidebar .truncate-when-mini {
    display: none !important;
}

/* Hide section titles and submenu arrows */
.state-desktop.sidebar-collapsed .sidebar .section-title,
.state-tablet.sidebar-collapsed .sidebar .section-title,
.state-desktop.sidebar-collapsed .sidebar .side-link i[data-feather="chevron-down"],
.state-tablet.sidebar-collapsed .sidebar .side-link i[data-feather="chevron-down"],
.state-desktop.sidebar-collapsed .sidebar .side-link i[data-feather="chevron-up"],
.state-tablet.sidebar-collapsed .sidebar .side-link i[data-feather="chevron-up"] {
    display: none !important;
}

/* Hide all submenus by default */
.state-desktop.sidebar-collapsed .sidebar ul .list-unstyled,
.state-tablet.sidebar-collapsed .sidebar ul .list-unstyled {
    display: none !important;
}

/* Optional tooltip on hover */
.state-desktop.sidebar-collapsed .sidebar .side-link[title]:hover::after,
.state-tablet.sidebar-collapsed .sidebar .side-link[title]:hover::after {
    content: attr(title);
    position: absolute;
    left: 70px;
    background: #1b2850;
    color: #fff;
    padding: 4px 8px;
    border-radius: 4px;
    white-space: nowrap;
    font-size: 0.8rem;
    z-index: 2000;
}


/* ========= FIX ICON HOVER SIZE IN COLLAPSED MODE ========= */

/* Make the link container fit the icon only when collapsed */
.state-desktop.sidebar-collapsed .sidebar .side-link,
.state-tablet.sidebar-collapsed .sidebar .side-link {
    width: 48px !important;
    height: 48px !important;
    padding: 12px !important;
    margin: 0 auto !important;
    justify-content: center !important;
    border-radius: 10px !important;
}

/* Center the icon */
.state-desktop.sidebar-collapsed .sidebar .side-link i,
.state-tablet.sidebar-collapsed .sidebar .side-link i {
    margin: 0 !important;
}

/* For dropdown parent buttons in collapsed mode */
.state-desktop.sidebar-collapsed .dropdown-parent>button.side-link,
.state-tablet.sidebar-collapsed .dropdown-parent>button.side-link {
    width: 53px !important;
    height: 53px !important;
    padding: 12px !important;
    margin: -10px auto !important;
    justify-content: center !important;
}

.state-desktop.sidebar-collapsed .sidebar .side-link {
    width: 53px !important;
    height: 53px !important;
    padding: 12px !important;
    justify-content: center !important;
}

/* Center icons within list items */
.state-desktop.sidebar-collapsed .sidebar-menu>ul>li,
.state-tablet.sidebar-collapsed .sidebar-menu>ul>li {
    display: flex;
    justify-content: center;
    padding: 4px 0;
}

/* Add positioning to parent items - CRITICAL FOR POSITIONING */
.state-desktop.sidebar-collapsed .sidebar-menu>ul>li.dropdown-parent,
.state-tablet.sidebar-collapsed .sidebar-menu>ul>li.dropdown-parent {
    position: relative !important;
}

/* Ensure the button inside doesn't interfere */
.state-desktop.sidebar-collapsed .dropdown-parent>button,
.state-tablet.sidebar-collapsed .dropdown-parent>button {
    position: relative;
}

/* Css for dark mood */
.dark .icons {
    color: #fff;
}

.dark .bg-yellow-100 {
    background-color: #79733b;
    color: #fff !important;
}


.dark a:hover {
    color: #fff !important;
    background-color: #212121 !important;
}

.dark .cat-card {
    background: #181818 !important;
    color: #fff !important;
}

.dark .cat-name {
    color: #fff !important;
}

.dark .cat-icon-wrap {
    background: #212121 !important;
    color: #fff !important;
}

.dark .bg-blue-100 {
    background-color: rgb(71 140 231) !important;
}

.dark .notifi-span {
    color: #A16207;
}

.dark .user-menu.nav>li>a.dropdown-toggle.nav-link.show {
    background-color: #212121 !important;
    color: #fff !important;
}

.dark .notifications .noti-content {
    background: #212121 !important;
}

.dark .bg-white {
    background-color: #181818 !important;
    border-bottom: #181818 !important;
    color: #fff !important;
}

.dark .bg-gray-50 {
    background-color: #181818 !important;
    border-bottom: #181818 !important;
}

.dark .text-gray-800 {
    color: #fff !important;
}

.dark .text-orange-700 {
    color: rgb(194 65 12)
}

.sidebar .sidebar-menu>ul>li>a span {
    margin-left: 0px !important;
}

.dark .bg-orange-200 {
    background-color: orange;
}

.dark input {
    background-color: #181818 !important;
    /* gray-800 */
    color: #f9fafb !important;
}

.dark textarea {
    background-color: #181818 !important;
    /* gray-800 */
    color: #f9fafb !important;
}

.dark .select {
    background-color: #181818 !important;
    /* gray-800 */
    color: #f9fafb !important;
}

.dark h4 {
    color: #fff !important;
}

.dark h5 {
    color: #fff !important;
}

.dark h6 {
    color: #fff !important;
}

.dark span {
    color: #fff !important;
}

/* .dark button {
    background-color: #181818 !important;
    color: #f9fafb !important;
} */


.dark a {
    color: #fff !important;
}

.dark .text-black {
    color: #fff !important;
}


.dark .card {
    background-color: #181818 !important;
    /* gray-800 */
    color: #ffffff !important;
    /* gray-50 */
}

.dark .table {
    background-color: #181818 !important;
    /* gray-900 */
    color: #f9fafb !important;
}

.dark .table thead {
    background-color: #181818;
    color: #f9fafb;
}

.dark .table thead th {
    background-color: #181818;
    color: #f9fafb;
}

.dark .table tbody td {
    background-color: #181818;
    color: #f9fafb;
}

html.dark {
    --brand: #ffffff;
    --bg-muted: #212121;
    --border: #212121;
    background: #181818;
    color: #f9fafb;
}

html.dark .page-wrapper {
    background: #212121;
    border-bottom-color: #212121;
}

html.dark .header {
    background: #181818;
    border-bottom-color: #ffffff;
}

html.dark .sidebar {
    background: #181818;
    /* border-right-color: #212121; */
    color: white;
}

html.dark .side-link {
    color: #ffffff;
}

.dark .modal-footer {
    background-color: #181818 !important;
}

.dark .p-select-header {
    background-color: #121212 !important;
}


.dark .text-muted {
    color: #fff !important;
}

.dark .dropdown-menu {
    background-color: #1b2431 !important;
    color: #fff ip !important;
}


html.dark .side-link:hover {
    color: #fff !important;
    /* your brand color */
    background: #1b2850 !important;
}

html.dark .main {
    background: #212121;
    color: #fff;
}

:global(.dark .form-control:focus) {
    border-color: #fff !important;
}

.dark .form-check-input {
    outline: 1px solid #fff !important;
}

.dark .dash-widget {
    background-color: #181818 !important;
    color: #ffffff;
}

.dark .dash-widgetcontent h5 {
    background-color: #181818 !important;
    color: #ffffff;
}

.dark .dash-widgetcontent h6 {
    background-color: #181818 !important;
    color: #ffffff;
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

.dark .help-popover {
    background-color: #212121 !important;
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

    border-radius: 9999px;
    padding: 6px 10px;
    width: 100%;
    max-width: 420px;
}

.top-nav-search input {
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--bg-muted);
    border-radius: 9999px;
    padding: 6px 10px;
    width: 100%;
    max-width: 420px;
}

.dark .top-nav-search input {
    display: flex;
    align-items: center;
    gap: 8px;
    background: white !important;
    border-radius: 9999px;
    padding: 6px 10px;
    width: 100%;
    max-width: 420px;
    color: black !important;
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

.dark .p-checkbox-checked .p-checkbox-icon {
    color: #fff !important;
}

.header .nav.user-menu>li {
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
    line-height: 1;
    /* kill anchor baseline drift */
}

/* Remove extra baseline gap caused by inline images */
.header .nav.user-menu img {
    display: block;
    /* no inline baseline */
}

/* Notification bell + badge tidy */
.header .nav.user-menu .nav-item.dropdown>.nav-link {
    position: relative;
}

.header .nav.user-menu .badge.rounded-pill {
    position: absolute;
    top: 14px;
    /* tweak to taste */
    right: 6px;
    transform: translateY(-50%);
}

/* Profile avatar + online dot alignment */
/* .user-img {
    position: relative;
    display: inline-block;
    width: 34px;
    height: 34px;
} */

/* .user-img img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
} */

.status.online {
    position: absolute;
    right: -2px;
    bottom: -2px;
    width: 8px;
    height: 8px;
    border: 2px solid #fff;
    margin: 0;
    /* remove negative margins */
}

.nav .badge {
    background: var(--brand);
    color: #fff;
    font-size: 12px;
}

.state-mobile .header-center {
    display: none;
    /* keep header compact on small screens */
}

.state-tablet .header-center {
    max-width: 320px;
}

/* top-nav-search smaller on small devices */
@media (max-width: 767px) {
    .top-nav-search {
        max-width: 160px;
    }
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
    /* color: var(--brand); */
    border-radius: 10px;
    /* transition: background 0.15s ease, color 0.15s ease; */
}

.side-link:hover {
    background: var(--brand);
    color: #fff;
}

.dark .side-link:hover {
    background: #181818;
    color: #fff;
}

.side-link.active,
li.active>.side-link {
    background: var(--brand);
    color: #ffffff;
    font-weight: 600;
}

.dark .side-link.active,
li.active>.side-link {
    color: #fff !important;
    /* your brand color */
    background: #1b2850 !important;
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

.state-desktop.sidebar-collapsed .truncate-when-mini,
.state-tablet.sidebar-collapsed .truncate-when-mini {
    /* icons-only: hide long text but keep icons visible */
    display: inline-block;
    max-width: 0;
    overflow: hidden;
    white-space: nowrap;
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
    cursor: pointer;
    /* hand cursor */
    transition: transform 0.15s, opacity 0.15s;
}

.icon-action:hover {
    transform: scale(1.2);
    /* slight zoom on hover */
    opacity: 0.8;
    /* subtle fade effect */
}

.is-invalid {
    background-color: #f8d7da !important;
    border-color: #dc3545 !important;
}

.invalid-feedback {
    color: #dc3545;
    font-size: 0.875rem;
    margin-top: 4px;
}

/* Submenu styling */
.sidebar .list-unstyled li .side-link {
    padding-left: 2.5rem;
    /* indent compared to parent */
    font-size: 0.9rem;
    /* slightly smaller */
    color: #520808;
    /* muted text */
}

.sidebar .sidebar-menu>ul>li>a svg {
    width: 24px;
}

.dark .icon-btn {
    color: #fff !important;
}

.dark .top-nav-search input {
    background-color: #181818 !important;
    color: #fff !important;
}

.dark .sidebar .sidebar-menu>ul>li.active a {
    color: #fff;
    /* your brand color */
    background: #1b2850 !important;
    /* light hover */
}

.dark .sidebar .list-unstyled li .side-link {
    padding-left: 2.5rem;
    /* indent compared to parent */
    font-size: 0.9rem;
    /* slightly smaller */
    color: #fff;
    /* muted text */
}

.sidebar .list-unstyled li .side-link:hover {
    color: #fff;
    /* your brand color */
    background: #1b2850;
    /* light hover */
}

.dark .sidebar .list-unstyled li .side-link:hover {
    background: #1b2850 !important;
    /* brand background */
    color: #fff;
}

.sidebar .list-unstyled li.active>.side-link {
    background: var(--brand) !important;
    /* brand background */
    color: #fff !important;
    font-weight: 500;
}

.dark .sidebar .list-unstyled li.active>.side-link {
    background: #1b2850 !important;
    /* brand background */
    color: #fff;
    font-weight: 500;
}


.sidebar .list-unstyled {
    border-left: 2px solid #e5e7eb;
    margin-left: 1rem;
    padding-left: 0.5rem;
}

.logo-card {
    background: #fff;
    border: 1px solid #edf0f5;
    border-radius: 1rem;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    min-height: 220px;
    box-shadow: 0 6px 18px rgba(17, 38, 146, 0.05);
}

.logo-frame {
    width: 140px;
    height: 140px;
    border-radius: 1rem;
    border: 1px dashed #cfd6e4;
    background: #f7f9fc;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    cursor: pointer;
}

.logo-frame img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.logo-frame .placeholder {
    color: #8b97a7;
    font-size: 28px;
    line-height: 0;
}

.state-tablet .sidebar {
    width: var(--sidebar-w-tablet);
}

.state-tablet .page-wrapper {
    margin-left: var(--sidebar-w-tablet);
}

/* Tablet collapsed: use the same collapsed icons-only width */
.state-tablet.sidebar-collapsed .sidebar {
    width: var(--sidebar-w-collapsed);
}

.state-tablet.sidebar-collapsed .page-wrapper {
    margin-left: var(--sidebar-w-collapsed);
}

/* ========= MOBILE (OVERLAY) ========= */
/* Mobile uses overlay approach: sidebar is off-canvas by default */
.state-mobile .page-wrapper {
    margin-left: 0;
}

/* On mobile keep full width for the sidebar when it slides in */
.state-mobile .sidebar {
    width: var(--sidebar-w);
    transform: translateX(-100%);
}

/* When mobile overlay 'sidebar-open' class present -> slide in */
.state-mobile.sidebar-open .sidebar {
    transform: translateX(0);
}

/* Backdrop for mobile overlay (unchanged) */
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

/* Scrollbars (optional) - keep as before */
.sidebar-menu::-webkit-scrollbar {
    width: 8px;
}

.sidebar-menu::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 8px;
}

/* Submenu indentation when collapsed still respects icons-only */
.sidebar .list-unstyled li .side-link {
    padding-left: 2.5rem;
    font-size: 0.9rem;
    color: #6c757d;
}

/* When the sidebar is collapsed, ensure nested labels are hidden */
.state-desktop.sidebar-collapsed .sidebar .list-unstyled li .side-link span,
.state-tablet.sidebar-collapsed .sidebar .list-unstyled li .side-link span {
    /* hide text of nested items when in icons-only mode */
    display: inline-block;
    max-width: 0;
    overflow: hidden;
    white-space: nowrap;
}


h1 {
    font-size: 32px !important;
}

h2 {
    font-size: 24px !important;
}

h3 {
    font-size: 20px;
}

h4 {
    font-size: 18px;
}

.btn {
    border-radius: 10px !important;
    height: 42px !important;
    font-size: 16px !important;
}

.btn-danger {
    border-radius: 10px !important;
    height: 42px !important;
    font-size: 16px !important;
}


@media (max-width: 767px) {
    .logo img {
        height: 36px;
    }
}

@media only screen and (min-device-width: 1024px) and (max-device-width: 1366px) and (orientation: portrait) {

    /* Example: Adjust sidebar width */
    .sidebar {
        width: 200px;
        /* smaller than desktop */
    }

    .page-wrapper {
        margin-left: 190px;
        /* align content */
    }

    /* Adjust header for tablet */
    .header {
        padding: 0 12px;
    }
}



/* dark code for modal */
.dark .modal-content {
    color: #f9fafb !important;
    border: 1px solid #ffffff !important;
    border-radius: 1px !important;
}

.dark .modal-body {
    background-color: #141414 !important;
    color: #f9fafb !important;
}

.dark .modal-header {
    background-color: #141414 !important;
    color: #f9fafb !important;
}

.header .user-img {
    font-size: 35px;
}


.new-badge {
    background: #1C0D92 !important;
    color: white !important;
}
</style>
