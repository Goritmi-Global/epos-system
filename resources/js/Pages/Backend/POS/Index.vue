<script setup>
import Master from "@/Layouts/Master.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { ref, computed, onMounted, watch } from "vue";
import { toast } from "vue3-toastify";
import ConfirmOrderModal from "./ConfirmOrderModal.vue";
import ReceiptModal from "./ReceiptModal.vue";
import PromoModal from "./PromoModal.vue";
import KotModal from "./KotModal.vue";
import { useFormatters } from "@/composables/useFormatters";
import PosOrdersModal from "./PosOrdersModal.vue";
import { Package, ShoppingCart } from "lucide-vue-next";
import FilterModal from "@/Components/FilterModal.vue";
import MultiSelect from 'primevue/multiselect';



const { formatMoney, formatCurrencySymbol, formatNumber, dateFmt } = useFormatters();

const props = defineProps(["client_secret", "order_code"]);

/* ----------------------------
   Categories
-----------------------------*/
const menuCategories = ref([]);
const menuCategoriesLoading = ref(true);

const fetchMenuCategories = async () => {
    menuCategoriesLoading.value = true;
    try {
        const response = await axios.get("/api/pos/fetch-menu-categories");
        menuCategories.value = response.data;
        if (menuCategories.value.length > 0) {
            activeCat.value = menuCategories.value[0].id;
        }
    } catch (error) {
        console.error("Error fetching categories:", error);
    } finally {
        menuCategoriesLoading.value = false;
    }
};

const menuItems = ref([]);
const fetchMenuItems = async () => {
    try {
        const response = await axios.get("/api/pos/fetch-menu-items");
        menuItems.value = response.data.data;
        console.log("menuItems.value", menuItems.value);
    } catch (error) {
        console.error("Error fetching inventory:", error);
    }
};

// const fetchMenuItems = async () => {
//     try {
//         const response = await axios.get("/api/pos/fetch-menu-items");

//         const processedMenus = response.data.data.map(menu => {
//             // Start with top-level ingredients (for simple menus)
//             let ingredients = menu.ingredients || [];

//             // If menu has variants, add all variant ingredients
//             if (menu.variants && menu.variants.length > 0) {
//                 ingredients = [
//                     ...ingredients,
//                     ...menu.variants.flatMap(variant => variant.ingredients || [])
//                 ];
//             }

//             return {
//                 ...menu,
//                 ingredients // now contains all ingredients for simple + variants
//             };
//         });

//         menuItems.value = processedMenus;

//         console.log("Processed menu items:", menuItems.value);
//     } catch (error) {
//         console.error("Error fetching menu items:", error);
//     }
// };


/* ----------------------------
   Products by Category
-----------------------------*/
const productsByCat = computed(() => {
    const grouped = {};
    menuItems.value.forEach((item) => {
        const catId = item.category?.id || "uncategorized";
        const catName = item.category?.name || "Uncategorized";
        if (!grouped[catId]) grouped[catId] = [];

        const product = {
            id: item.id,
            title: item.name,
            img: item.image_url || "/assets/img/default.png",
            stock: 0, // Will be calculated reactively
            price: Number(item.price),
            label_color: item.label_color || "#1B1670",
            family: catName,
            description: item.description,
            nutrition: item.nutrition,
            tags: item.tags,
            allergies: item.allergies,
            ingredients: item.ingredients ?? [],
            variants: item.variants ?? [],
            addon_groups: item.addon_groups ?? [],
        };

        grouped[catId].push(product);
    });
    return grouped;
});

// ✅ Add this ref to track selected variants for each product
const selectedCardVariant = ref({});

// ✅ Initialize variants when products load
watch(() => menuItems.value, (items) => {
    if (items && items.length > 0) {
        items.forEach(item => {
            if (item.variants && item.variants.length > 0) {
                // Set first variant as default for each product
                if (!selectedCardVariant.value[item.id]) {
                    selectedCardVariant.value[item.id] = item.variants[0].id;
                }
            }
        });
    }
}, { immediate: true, deep: true });

// ✅ Get selected variant price for display
const getSelectedVariantPrice = (product) => {
    if (!product.variants || product.variants.length === 0) {
        return product.price;
    }

    const selectedVariantId = selectedCardVariant.value[product.id];
    if (!selectedVariantId) {
        return product.variants[0]?.price || product.price;
    }

    const variant = product.variants.find(v => v.id === selectedVariantId);
    return variant ? parseFloat(variant.price) : product.price;
};

// ✅ Get selected variant object
const getSelectedVariant = (product) => {
    if (!product.variants || product.variants.length === 0) {
        return null;
    }

    const selectedVariantId = selectedCardVariant.value[product.id];
    if (!selectedVariantId) {
        return product.variants[0];
    }

    return product.variants.find(v => v.id === selectedVariantId) || product.variants[0];
};


// ============================================
//  Handle variant change to revalidate stock
// ============================================
const onVariantChange = (product, event) => {
    const variantId = parseInt(event.target.value);
    selectedCardVariant.value[product.id] = variantId;

    const variant = product.variants.find(v => v.id === variantId);
    if (variant) {
        console.log(`Selected ${variant.name} for ${product.title} - ${formatCurrencySymbol(variant.price)}`);
    }
};

// ================ addons =========================
const selectedCardAddons = ref({});
const handleAddonChange = (product, addonGroupId, selectedAddons) => {
    const productKey = product.id;

    if (!selectedCardAddons.value[productKey]) {
        selectedCardAddons.value[productKey] = {};
    }

    const addonGroup = product.addon_groups.find(g => g.group_id === addonGroupId);

    // Check max_select limit
    if (addonGroup && addonGroup.max_select > 0 && selectedAddons.length > addonGroup.max_select) {
        toast.warning(`You can only select up to ${addonGroup.max_select} ${addonGroup.group_name}`);
        // Revert to previous selection
        const previous = selectedCardAddons.value[productKey][addonGroupId] || [];
        selectedCardAddons.value[productKey][addonGroupId] = previous.slice(0, addonGroup.max_select);
        return;
    }

    // Store the selected addons directly (PrimeVue already gives us the objects)
    selectedCardAddons.value[productKey][addonGroupId] = selectedAddons;
};

// ✅ Get total addon price for a product
const getAddonsPrice = (product) => {
    const productKey = product.id;
    if (!selectedCardAddons.value[productKey]) return 0;

    let total = 0;
    Object.values(selectedCardAddons.value[productKey]).forEach(addons => {
        addons.forEach(addon => {
            total += parseFloat(addon.price || 0);
        });
    });

    return total;
};

// ✅ Get selected addons for a product (format for backend)
const getSelectedAddons = (product) => {
    const productKey = product.id;
    if (!selectedCardAddons.value[productKey]) return [];

    const allAddons = [];
    Object.values(selectedCardAddons.value[productKey]).forEach(addons => {
        addons.forEach(addon => {
            allAddons.push({
                id: addon.id,
                name: addon.name,
                price: parseFloat(addon.price),
                group_id: addon.group_id || addon.pivot?.addon_group_id,
            });
        });
    });

    return allAddons;
};

// ✅ Get total price (variant + addons)
const getTotalPrice = (product) => {
    const variantPrice = getSelectedVariantPrice(product);
    const addonsPrice = getAddonsPrice(product);
    return variantPrice + addonsPrice;
};
// ================================================
const openDetailsModal = (item) => {
    selectedItem.value = item;
    modalNote.value = "";
    modalSelectedVariant.value = null;
    modalSelectedAddons.value = {};

    // Set default variant if exists
    if (item.variants && item.variants.length > 0) {
        modalSelectedVariant.value = item.variants[0].id;
    }

    // ✅ Always open modal, but set quantity based on available stock
    const variant = item.variants && item.variants.length > 0 ? item.variants[0] : null;
    const variantId = variant ? variant.id : null;
    const variantIngredients = getVariantIngredients(item, variantId);

    // Calculate how many we can still add
    const availableToAdd = calculateAvailableStock(item, variantId, variantIngredients);

    // Set quantity to 0 if no stock, otherwise 1
    modalQty.value = availableToAdd > 0 ? 1 : 0;

    const modal = new bootstrap.Modal(document.getElementById("chooseItem"));
    modal.show();
};

// ✅ Calculate how many more items can be added (considering cart)
const calculateAvailableStock = (product, variantId, variantIngredients) => {
    if (!variantIngredients || variantIngredients.length === 0) return 999999;

    const ingredientUsage = {};

    // Track what's already used in the cart
    for (const item of orderItems.value) {
        const itemIngredients = getVariantIngredients(item, item.variant_id);
        itemIngredients.forEach(ing => {
            const id = ing.inventory_item_id;
            const stock = Number(ing.inventory_stock ?? ing.inventory_item?.stock ?? 0);

            if (!ingredientUsage[id]) {
                ingredientUsage[id] = { totalStock: stock, used: 0 };
            }

            const required = Number(ing.quantity ?? ing.qty ?? 1) * item.qty;
            ingredientUsage[id].used += required;
        });
    }

    // Calculate maximum possible for THIS item
    let maxPossible = 999999;

    for (const ing of variantIngredients) {
        const id = ing.inventory_item_id;
        const stock = Number(ing.inventory_stock ?? ing.inventory_item?.stock ?? 0);
        const requiredPerItem = Number(ing.quantity ?? ing.qty ?? 1);

        if (!ingredientUsage[id]) {
            // No other items using this ingredient
            const possible = Math.floor(stock / requiredPerItem);
            maxPossible = Math.min(maxPossible, possible);
        } else {
            // Some already used by cart
            const available = ingredientUsage[id].totalStock - ingredientUsage[id].used;
            const possible = Math.floor(available / requiredPerItem);
            maxPossible = Math.min(maxPossible, possible);
        }
    }

    return maxPossible;
};

// ✅ ADD ALL THESE NEW FUNCTIONS

// Handle Modal Variant Change
const onModalVariantChange = () => {
    // ✅ Recheck stock when variant changes
    const variant = getModalSelectedVariant();
    const variantId = variant ? variant.id : null;
    const variantIngredients = getVariantIngredients(selectedItem.value, variantId);

    const availableToAdd = calculateAvailableStock(selectedItem.value, variantId, variantIngredients);

    if (availableToAdd <= 0) {
        toast.error(`No stock available for this variant. Please remove items from cart first.`);
        modalQty.value = 0;
        return;
    }

    // Reset to 1 or keep current qty if still available
    modalQty.value = Math.min(1, availableToAdd);
    console.log("Variant changed in modal:", modalSelectedVariant.value, "Available:", availableToAdd);
};

// Handle Modal Addon Change
const handleModalAddonChange = (addonGroupId, selectedAddons) => {
    if (!selectedItem.value) return;

    const addonGroup = selectedItem.value.addon_groups.find(g => g.group_id === addonGroupId);

    if (addonGroup && addonGroup.max_select > 0 && selectedAddons.length > addonGroup.max_select) {
        toast.warning(`You can only select up to ${addonGroup.max_select} ${addonGroup.group_name}`);
        modalSelectedAddons.value[addonGroupId] = selectedAddons.slice(0, addonGroup.max_select);
        return;
    }

    modalSelectedAddons.value[addonGroupId] = selectedAddons;
};

// Get Modal Selected Variant Object
const getModalSelectedVariant = () => {
    if (!selectedItem.value) return null;
    if (!selectedItem.value.variants || selectedItem.value.variants.length === 0) return null;

    const variantId = modalSelectedVariant.value;
    if (!variantId) return selectedItem.value.variants[0];

    return selectedItem.value.variants.find(v => v.id === variantId) || selectedItem.value.variants[0];
};

// Get Modal Variant Price
const getModalVariantPrice = () => {
    if (!selectedItem.value) return 0;

    const variant = getModalSelectedVariant();
    if (variant) return parseFloat(variant.price);

    return parseFloat(selectedItem.value.price || 0);
};

// Get Modal Addons Price
const getModalAddonsPrice = () => {
    let total = 0;
    Object.values(modalSelectedAddons.value).forEach(addons => {
        addons.forEach(addon => {
            total += parseFloat(addon.price || 0);
        });
    });
    return total;
};

// Get Modal Total Price
const getModalTotalPrice = () => {
    return getModalVariantPrice() + getModalAddonsPrice();
};

// Get Modal Nutrition
const getModalNutrition = () => {
    if (!selectedItem.value) return {};

    const variant = getModalSelectedVariant();
    if (variant && variant.nutrition) {
        return variant.nutrition;
    }

    return selectedItem.value.nutrition || {};
};

// Get Modal Allergies
const getModalAllergies = () => {
    if (!selectedItem.value) return [];

    const variant = getModalSelectedVariant();
    if (variant && variant.allergies && variant.allergies.length > 0) {
        return variant.allergies;
    }

    return selectedItem.value.allergies || [];
};

// Get Modal Tags
const getModalTags = () => {
    if (!selectedItem.value) return [];

    const variant = getModalSelectedVariant();
    if (variant && variant.tags && variant.tags.length > 0) {
        return variant.tags;
    }

    return selectedItem.value.tags || [];
};

// Get Modal Selected Addons
const getModalSelectedAddons = () => {
    const allAddons = [];
    Object.values(modalSelectedAddons.value).forEach(addons => {
        addons.forEach(addon => {
            allAddons.push({
                id: addon.id,
                name: addon.name,
                price: parseFloat(addon.price),
                group_id: addon.group_id || addon.pivot?.addon_group_id,
            });
        });
    });
    return allAddons;
};

// ===================================================================
const getAllIngredients = (item) => {
    let allIngredients = item.ingredients ?? [];

    if (item.variants && item.variants.length > 0) {
        allIngredients = [
            ...allIngredients,
            ...item.variants.flatMap(v => v.ingredients ?? [])
        ];
    }

    return allIngredients;
};


// ✅ Get stock for a specific product (based on selected variant)
const getProductStock = (product) => {
    if (!product) return 0;

    const variant = getSelectedVariant(product);
    const variantId = variant ? variant.id : null;
    const ingredients = getVariantIngredients(product, variantId);

    if (!ingredients.length) return 999999; // No ingredients = unlimited

    let menuStock = Infinity;

    ingredients.forEach((ing) => {
        const required = Number(ing.quantity ?? ing.qty ?? 1);
        const inventoryStock = Number(ing.inventory_stock ?? ing.inventory_item?.stock ?? 0);
        if (required <= 0) return;
        const possible = Math.floor(inventoryStock / required);
        menuStock = Math.min(menuStock, possible);
    });

    return menuStock === Infinity ? 0 : menuStock;
};

// calculate menu stock
const calculateMenuStock = (item) => {
    return getProductStock(item);
};

/* ----------------------------
   UI State
-----------------------------*/
const searchQuery = ref("");
const orderType = ref("dine");
const customer = ref("Walk In");
const deliveryPercent = ref(10);

const activeCat = ref(null);
const setCat = (id) => (activeCat.value = id);

const showCategories = ref(true);
const openCategory = (c) => {
    setCat(c.id);
    showCategories.value = false;
};
const backToCategories = () => (showCategories.value = true);

const profileTables = ref({});
const orderTypes = ref([]);
const selectedTable = ref(null);
/* ----------------------------
   Fetch Profile Tables
-----------------------------*/
const fetchProfileTables = async () => {
    try {
        const response = await axios.get("/api/pos/fetch-profile-tables");
        profileTables.value = response.data;

        if (profileTables.value.order_types) {
            // Capitalize first character of each type
            orderTypes.value = profileTables.value.order_types.map(type =>
                type.charAt(0).toUpperCase() + type.slice(1)
            );

            orderType.value = orderTypes.value[0];
        }
    } catch (error) {
        console.error("Error fetching profile tables:", error);
    }
};


const visibleProducts = computed(
    () => productsByCat.value[activeCat.value] ?? []
);

// const filteredProducts = computed(() => {
//     const q = searchQuery.value.trim().toLowerCase();
//     if (!q) return visibleProducts.value;
//     return visibleProducts.value.filter(
//         (p) =>
//             p.title.toLowerCase().includes(q) ||
//             (p.family || "").toLowerCase().includes(q) ||
//             (p.description || "").toLowerCase().includes(q) ||
//             (
//                 p.tags?.map((t) => t.name.toLowerCase()).join(", ") || ""
//             ).includes(q)
//     );
// });

const filters = ref({
    sortBy: '',
    priceMin: null,
    priceMax: null,
});

/* ----------------------------
   Filtered and Sorted Products
-----------------------------*/
const filteredProducts = computed(() => {
    let products = visibleProducts.value;
    const q = searchQuery.value.trim().toLowerCase();

    // 1. Search filter (existing logic)
    if (q) {
        products = products.filter(
            (p) =>
                p.title.toLowerCase().includes(q) ||
                (p.family || "").toLowerCase().includes(q) ||
                (p.description || "").toLowerCase().includes(q) ||
                (
                    p.tags?.map((t) => t.name.toLowerCase()).join(", ") || ""
                ).includes(q)
        );
    }

    // 2. Price range filter
    if (filters.value.priceMin !== null && filters.value.priceMin !== '') {
        products = products.filter(p => p.price >= filters.value.priceMin);
    }
    if (filters.value.priceMax !== null && filters.value.priceMax !== '') {
        products = products.filter(p => p.price <= filters.value.priceMax);
    }

    // 3. Sorting
    if (filters.value.sortBy) {
        products = [...products]; // Create a copy to avoid mutating original

        switch (filters.value.sortBy) {
            case 'name_asc':
                products.sort((a, b) => a.title.localeCompare(b.title));
                break;
            case 'name_desc':
                products.sort((a, b) => b.title.localeCompare(a.title));
                break;
            case 'price_asc':
                products.sort((a, b) => a.price - b.price);
                break;
            case 'price_desc':
                products.sort((a, b) => b.price - a.price);
                break;
            case 'stock_asc':
                products.sort((a, b) => (a.stock ?? 0) - (b.stock ?? 0));
                break;
            case 'stock_desc':
                products.sort((a, b) => (b.stock ?? 0) - (a.stock ?? 0));
                break;
        }
    }

    return products;
});

/* ----------------------------
   Filter Handlers
-----------------------------*/
const handleApplyFilters = (appliedFilters) => {
    filters.value = { ...appliedFilters };
};

const handleClearFilters = () => {
    filters.value = {
        sortBy: '',
        priceMin: null,
        priceMax: null,
    };
};


/* ----------------------------
   Order cart
-----------------------------*/
const orderItems = ref([]);
const addToOrder = (baseItem, qty = 1, note = "") => {
    console.log(baseItem);
    const menuStock = calculateMenuStock(baseItem);

    // Get selected variant info
    const variant = getSelectedVariant(baseItem);
    const variantId = variant ? variant.id : null;
    const variantName = variant ? variant.name : null;
    const variantPrice = variant ? parseFloat(variant.price) : baseItem.price;

    // ✅ Get selected addons
    const selectedAddons = getSelectedAddons(baseItem);
    const addonsPrice = getAddonsPrice(baseItem);
    const totalItemPrice = variantPrice + addonsPrice;

    console.log("selectedAddons", selectedAddons);

    // ✅ Create unique key including variant AND addons
    const addonIds = selectedAddons.map(a => a.id).sort().join('-');
    const uniqueKey = `${baseItem.id}-${variantId || 'no-variant'}-${addonIds || 'no-addons'}`;

    const idx = orderItems.value.findIndex((i) => {
        const itemAddonIds = (i.addons || []).map(a => a.id).sort().join('-');
        return i.id === baseItem.id &&
            i.variant_id === variantId &&
            itemAddonIds === addonIds;
    });

    if (idx >= 0) {
        const newQty = orderItems.value[idx].qty + qty;
        if (newQty <= orderItems.value[idx].stock) {
            orderItems.value[idx].qty = newQty;
            orderItems.value[idx].price = orderItems.value[idx].unit_price * newQty;
            if (note) orderItems.value[idx].note = note;
        } else {
            toast.error("Not enough Ingredients stock available for this Menu.");
        }
    } else {
        if (qty > menuStock) {
            toast.error("Not enough Ingredients stock available for this Menu.");
            return;
        }

        orderItems.value.push({
            id: baseItem.id,
            title: baseItem.title,
            img: baseItem.img,
            price: totalItemPrice * qty,
            unit_price: Number(totalItemPrice),
            qty: qty,
            note: note || "",
            stock: menuStock,
            ingredients: baseItem.ingredients ?? [],
            variant_id: variantId,
            variant_name: variantName,
            addons: selectedAddons, // ✅ INCLUDE ADDONS
        });
    }
};


// const incCart = async (i) => {
//     const it = orderItems.value[i];
//     if (!it) return;
//     if ((it.stock ?? 0) <= 0) {
//         toast.error("Item out of stock.");
//         return;
//     }
//     if (it.qty >= (it.stock ?? 0)) {
//         toast.error("Not enough stock to add more of this item.");
//         return;
//     }
//     try {
//         // await updateStock(it, 1, "stockout");
//         it.qty++;
//     } catch (err) {
//         toast.error("Failed to add item. Please try again.");
//     }
// };

// ========================================
// Fixed: Increment cart item (in cart sidebar)
// ========================================
const incCart = async (i) => {
    const it = orderItems.value[i];
    if (!it) return;

    if ((it.stock ?? 0) <= 0) {
        toast.error("Item out of stock.");
        return;
    }

    // Check if we can increment this specific item
    if (!canIncCartItem(it)) {
        const variantText = it.variant_name ? ` (${it.variant_name})` : '';
        toast.error(`Not enough ingredients for "${it.title}${variantText}".`);
        it.outOfStock = true;
        return;
    }

    // If all checks pass, increment
    it.outOfStock = false;
    it.qty++;
    it.price = it.unit_price * it.qty;
};



// ========================================
// Fixed: Decrement cart item (in cart sidebar)
// ========================================
const decCart = async (i) => {
    const it = orderItems.value[i];
    if (!it || it.qty <= 1) {
        toast.error("Cannot reduce below 1.");
        return;
    }

    it.qty--;
    it.price = it.unit_price * it.qty;
    it.outOfStock = false; // Reset out of stock flag
};




const removeCart = (i) => orderItems.value.splice(i, 1);

const subTotal = computed(() =>
    orderItems.value.reduce((s, i) => s + i.price, 0)
);

const deliveryCharges = computed(() => {
    if (orderType.value !== "Delivery") return 0;

    const config = page.props.onboarding.tax_and_vat;

    // Check if delivery charges are enabled
    if (!config.has_delivery_charges) return 0;

    // Flat rate takes precedence
    if (config.delivery_charge_flat) {
        return parseFloat(config.delivery_charge_flat);
    }

    // Otherwise use percentage
    if (config.delivery_charge_percentage) {
        return (subTotal.value * parseFloat(config.delivery_charge_percentage)) / 100;
    }

    return 0;
});


const serviceCharges = computed(() => {
    const config = page.props.onboarding.tax_and_vat;

    // Check if service charges are enabled
    if (!config.has_service_charges) return 0;

    // Flat rate takes precedence
    if (config.service_charge_flat) {
        return parseFloat(config.service_charge_flat);
    }

    // Otherwise use percentage
    if (config.service_charge_percentage) {
        return (subTotal.value * parseFloat(config.service_charge_percentage)) / 100;
    }

    return 0;
});

// const grandTotal = computed(() => {
//     const total = subTotal.value + deliveryCharges.value - promoDiscount.value;
//     return Math.max(0, total);
// });

const money = (n) => `£${(Math.round(n * 100) / 100).toFixed(2)}`;

/* ----------------------------
   Item modal
-----------------------------*/
const selectedItem = ref(null);
const modalQty = ref(0);
const modalNote = ref("");
let chooseItemModal = null;
const modalSelectedVariant = ref(null);
const modalSelectedAddons = ref({});

const openItem = (p) => {
    selectedItem.value = p;
    modalQty.value = 1;
    modalNote.value = "";
    if (chooseItemModal) chooseItemModal.show();
};

const menuStockForSelected = computed(() =>
    calculateMenuStock(selectedItem.value)
);

// const confirmAdd = async () => {
//     if (!selectedItem.value) return;
//     try {
//         addToOrder(selectedItem.value, modalQty.value, modalNote.value);
//         console.log(selectedItem.value.ingredients);

//         //  2) Stockout each ingredient
//         // if (
//         //     selectedItem.value.ingredients &&
//         //     selectedItem.value.ingredients.length
//         // ) {
//         //     for (const ingredient of selectedItem.value.ingredients) {
//         //         const requiredQty = ingredient.pivot?.qty
//         //             ? ingredient.pivot.qty * modalQty.value
//         //             : modalQty.value;

//         //         //Payload matching your request rules
//         //         await axios.post("/stock_entries", {
//         //             product_id: ingredient.inventory_item_id,
//         //             name: ingredient.product_name,
//         //             category_id: ingredient.category_id,
//         //             supplier_id: ingredient.supplier_id,
//         //             available_quantity: ingredient.inventory_stock,
//         //             quantity: ingredient.quantity * modalQty.value,
//         //             price: null,
//         //             value: 0,
//         //             operation_type: "pos_stockout",
//         //             stock_type: "stockout",
//         //             expiry_date: null,
//         //             description: null,
//         //             purchase_date: null,
//         //             user_id: ingredient.user_id,
//         //         });
//         //     }
//         // }

//         //  3) Close modal
//         if (chooseItemModal) chooseItemModal.hide();
//     } catch (err) {
//         alert(
//             "Stockout failed: " + (err.response?.data?.message || err.message)
//         );
//     }
// };

const confirmAdd = async () => {
    if (!selectedItem.value) return;

    const variant = getModalSelectedVariant();
    const variantId = variant ? variant.id : null;
    const variantText = variant ? ` (${variant.name})` : '';

    // ✅ Check if quantity is 0 or less
    if (modalQty.value <= 0) {
        toast.error(`No stock available for "${selectedItem.value.title}${variantText}". Please remove some from cart first.`);
        return;
    }

    const variantName = variant ? variant.name : null;
    const variantPrice = variant ? parseFloat(variant.price) : selectedItem.value.price;
    const selectedAddons = getModalSelectedAddons();
    const addonsPrice = getModalAddonsPrice();
    const totalItemPrice = variantPrice + addonsPrice;

    const variantIngredients = getVariantIngredients(selectedItem.value, variantId);

    // ✅ DOUBLE CHECK available stock before confirming
    const availableToAdd = calculateAvailableStock(selectedItem.value, variantId, variantIngredients);

    if (modalQty.value > availableToAdd) {
        if (availableToAdd <= 0) {
            toast.error(`No more stock available for "${selectedItem.value.title}${variantText}". Please remove some from cart first.`);
        } else {
            toast.error(`Only ${availableToAdd} item(s) available. Please reduce quantity or remove items from cart.`);
            modalQty.value = availableToAdd; // Auto-adjust to max available
        }
        return;
    }

    const ingredientStock = {};

    // Check stock from current cart
    for (const item of orderItems.value) {
        const itemIngredients = getVariantIngredients(item, item.variant_id);
        itemIngredients.forEach(ing => {
            const ingredientId = ing.inventory_item_id;
            if (!ingredientStock[ingredientId]) {
                ingredientStock[ingredientId] = parseFloat(ing.inventory_stock);
            }
            ingredientStock[ingredientId] -= parseFloat(ing.quantity) * item.qty;
        });
    }

    // Check stock for selected item
    if (variantIngredients.length > 0) {
        for (const ing of variantIngredients) {
            const ingredientId = ing.inventory_item_id;
            const availableStock = ingredientStock[ingredientId] ?? parseFloat(ing.inventory_stock);
            const requiredQty = parseFloat(ing.quantity) * modalQty.value;

            if (availableStock < requiredQty) {
                toast.error(`Not enough stock for "${selectedItem.value.title}${variantText}".`);
                return;
            }
        }
    }

    try {
        // Add to cart with modal selections
        const addonIds = selectedAddons.map(a => a.id).sort().join('-');
        const menuStock = variantIngredients.length > 0
            ? calculateStockForIngredients(variantIngredients)
            : 999999;

        const idx = orderItems.value.findIndex((i) => {
            const itemAddonIds = (i.addons || []).map(a => a.id).sort().join('-');
            return i.id === selectedItem.value.id &&
                i.variant_id === variantId &&
                itemAddonIds === addonIds;
        });

        if (idx >= 0) {
            orderItems.value[idx].qty += modalQty.value;
            orderItems.value[idx].price = orderItems.value[idx].unit_price * orderItems.value[idx].qty;
        } else {
            orderItems.value.push({
                id: selectedItem.value.id,
                title: selectedItem.value.title,
                img: selectedItem.value.img,
                price: totalItemPrice * modalQty.value,
                unit_price: Number(totalItemPrice),
                qty: modalQty.value,
                note: modalNote.value || "",
                stock: menuStock,
                ingredients: variantIngredients,
                variant_id: variantId,
                variant_name: variantName,
                addons: selectedAddons,
            });
        }

        await openPromoModal(selectedItem.value);

        const modal = bootstrap.Modal.getInstance(document.getElementById('chooseItem'));
        modal.hide();

        // ✅ Reset modal state after successful add
        modalQty.value = 0;
        modalNote.value = "";
        modalSelectedVariant.value = null;
        modalSelectedAddons.value = {};

    } catch (err) {
        toast.error("Failed to add item: " + (err.response?.data?.message || err.message));
    }
};

// Helper function
const calculateStockForIngredients = (ingredients) => {
    if (!ingredients || ingredients.length === 0) return 999999;

    let menuStock = Infinity;
    ingredients.forEach((ing) => {
        const required = Number(ing.quantity ?? ing.qty ?? 1);
        const inventoryStock = Number(ing.inventory_stock ?? ing.inventory_item?.stock ?? 0);
        if (required <= 0) return;
        const possible = Math.floor(inventoryStock / required);
        menuStock = Math.min(menuStock, possible);
    });

    return menuStock === Infinity ? 0 : menuStock;
};


/* ----------------------------
   Update Stock
-----------------------------*/
const updateStock = async (item, qty, type = "stockout") => {
    if (!item.ingredients || !item.ingredients.length) return;
    try {
        for (const ingredient of item.ingredients) {
            const ingredientQty = Number(ingredient.quantity) || 1;
            const requiredQty = ingredientQty * qty;
            const payload = {
                product_id: ingredient.inventory_item_id,
                name: ingredient.product_name,
                category_id: ingredient.category_id,
                available_quantity: ingredient.inventory_stock,
                quantity: requiredQty,
                value: 0,
                operation_type:
                    type === "stockout" ? "pos_stockout" : "pos_stockin",
                stock_type: type,
                expiry_date: null,
                description: null,
                purchase_date: null,
                user_id: ingredient.user_id,
            };
            if (type === "stockout") {
                payload.price = null;
                payload.supplier_id = null;
            } else {
                payload.price = ingredient.cost || ingredient.price || 1;
                payload.supplier_id = ingredient.supplier_id || 1;
            }
            await axios.post("/stock_entries", payload);
        }
    } catch (err) {
        alert(
            `${type} failed: ` + (err.response?.data?.message || err.message)
        );
        throw err;
    }
};

// const incQty = async () => {
//     if (modalQty.value < menuStockForSelected.value) {
//         modalQty.value++;
//         // Add .value here
//         // try {
//         //     await updateStock(selectedItem.value, 1, "stockout");
//         //     modalQty.value++; // Only increment after successful stock update
//         //     console.log(
//         //         "Stock updated successfully, new modalQty:",
//         //         modalQty.value
//         //     );
//         // } catch (error) {
//         //     console.error("Failed to update stock:", error);
//         //     // Don't increment modalQty if stock update failed
//         // }
//     } else {
//         console.log("Cannot increment: reached maximum stock limit");
//     }
// };

// Check if we can increment modal quantity
const canIncModalQty = () => {
    if (!selectedItem.value) return false;

    const variant = getModalSelectedVariant();
    const variantId = variant ? variant.id : null;
    const variantIngredients = getVariantIngredients(selectedItem.value, variantId);

    if (!variantIngredients.length) return true;

    // ✅ Check if we can add ONE MORE based on current cart + modal quantity
    const availableToAdd = calculateAvailableStock(selectedItem.value, variantId, variantIngredients);

    return (modalQty.value + 1) <= availableToAdd;
};

const incQty = () => {
    if (!canIncModalQty()) {
        const variant = getModalSelectedVariant();
        const variantText = variant ? ` (${variant.name})` : '';

        // ✅ Check if it's because there's NO stock at all
        const variantId = variant ? variant.id : null;
        const variantIngredients = getVariantIngredients(selectedItem.value, variantId);
        const availableToAdd = calculateAvailableStock(selectedItem.value, variantId, variantIngredients);

        if (availableToAdd <= 0) {
            toast.error(`No more stock available for "${selectedItem.value?.title}${variantText}". Please remove some from cart first.`);
        } else {
            toast.error(`Not enough ingredients for "${selectedItem.value?.title}${variantText}".`);
        }
        return;
    }
    modalQty.value++;
};

const decQty = () => {
    if (modalQty.value > 1) {
        modalQty.value--;
    }
};

/* ----------------------------
   Order + Receipt
-----------------------------*/
const formErrors = ref({});
// Update resetCart to clear promo
const resetCart = () => {
    orderItems.value = [];
    customer.value = "Walk In";
    selectedTable.value = null;
    orderType.value = orderTypes.value[0] || "dine_in";
    note.value = "";
    kitchenNote.value = "";
    deliveryPercent.value = 0;
    selectedPromo.value = null; // Clear promo
};
watch(orderType, () => (formErrors.value = {}));

const note = ref("");
const kitchenNote = ref("");
const showReceiptModal = ref(false);
const lastOrder = ref(null);
const showConfirmModal = ref(false);
const cashReceived = ref(0);

/* ------------------------------------
   Helper Function to calculate Stock
---------------------------------------*/
const hasEnoughStockForOrder = () => {
    console.log("=== Checking Stock for Order ===");
    console.log("Cart items:", orderItems.value);

    const ingredientUsage = {};

    // Calculate total usage
    for (const item of orderItems.value) {
        // ✅ Use the ingredients that were stored when item was added to cart
        // These are already variant-specific
        const itemIngredients = item.ingredients || [];

        console.log(`Item: ${item.title} (${item.variant_name || 'no variant'}), Qty: ${item.qty}`);
        console.log("Item ingredients:", itemIngredients);

        // ✅ If item has no ingredients, skip stock checking for this item
        if (itemIngredients.length === 0) {
            console.log(`  - No ingredients to track for ${item.title}`);
            continue;
        }

        itemIngredients.forEach(ing => {
            const ingredientId = ing.inventory_item_id;
            const availableStock = parseFloat(ing.inventory_stock ?? ing.inventory_item?.stock ?? 0);
            const requiredQty = parseFloat(ing.quantity ?? ing.qty ?? 1) * item.qty;

            console.log(`  - Ingredient: ${ing.product_name}, Required: ${requiredQty}, Available: ${availableStock}`);

            if (!ingredientUsage[ingredientId]) {
                ingredientUsage[ingredientId] = {
                    name: ing.product_name || 'Unknown',
                    totalStock: availableStock,
                    totalUsed: 0
                };
            }

            ingredientUsage[ingredientId].totalUsed += requiredQty;
        });
    }

    console.log("Ingredient Usage Summary:", ingredientUsage);

    // Check for over-allocation
    for (const [ingredientId, usage] of Object.entries(ingredientUsage)) {
        console.log(`Checking ${usage.name}: Used ${usage.totalUsed} / Available ${usage.totalStock}`);

        if (usage.totalUsed > usage.totalStock) {
            toast.error(
                `Not enough "${usage.name}". ` +
                `Need ${usage.totalUsed.toFixed(2)} but only ${usage.totalStock.toFixed(2)} available.`
            );
            return false;
        }
    }

    console.log("✅ All stock checks passed!");
    return true;
};




const openConfirmModal = () => {
    if (orderItems.value.length === 0) {
        toast.error("Please add at least one item to the cart.");
        return;
    }
    if (orderType.value === "Dine_in" && !selectedTable.value) {
        formErrors.value.table_number = [
            "Table number is required for dine-in orders.",
        ];
        toast.error("Please select a table number for Dine In orders.");
        return;
    }
    if ((orderType.value === "Delivery" || orderType.value === "Takeaway") && !customer.value) {
        formErrors.value.customer = [
            "Customer name is required for delivery.",
        ];
        toast.error("Customer name is required for delivery.");
        return;
    }
    if (!hasEnoughStockForOrder()) {
        // Stop the process if not enough stock
        return;
    }

    cashReceived.value = grandTotal.value;
    showConfirmModal.value = true;
};

async function printReceipt(order) {
    try {
        const response = await axios.post("/api/customer/print-receipt", { order });
        if (response.data.success) {
        } else {
            toast.error(response.data.message || "Print failed");
        }
    } catch (error) {
        console.error("Print failed:", error);
        toast.error("Unable to connect to the customer printer. Please ensure it is properly connected.");
    }
}


const paymentMethod = ref("cash");
const changeAmount = ref(0);

async function printKot(order) {
    try {
        const response = await axios.post("/api/kot/print-receipt", { order });
        if (response.data.success) {
        } else {
            toast.error(response.data.message || "KOT print failed");
        }
    } catch (error) {
        console.error("KOT print failed:", error);
        toast.error("Unable to connect to the kitchen printer. Please ensure it is properly connected.");
    }
}

/* ----------------------------
   Confirm Order
-----------------------------*/
const confirmOrder = async ({
    paymentMethod,
    cashReceived,
    cardAmount,  // ✅ Add this parameter
    changeAmount,
    items,
    autoPrintKot,
    done
}) => {
    formErrors.value = {};
    try {
        const payload = {
            customer_name: customer.value,
            sub_total: subTotal.value,

            tax: totalTax.value,

            service_charges: serviceCharges.value,
            delivery_charges: deliveryCharges.value,

            // Promo Details
            promo_discount: promoDiscount.value,
            promo_id: selectedPromo.value?.id || null,
            promo_name: selectedPromo.value?.name || null,
            promo_type: selectedPromo.value?.type || null,
            total_amount: grandTotal.value,
            // tax: 0,
            service_charges: 0,
            delivery_charges: deliveryCharges.value,
            note: note.value,
            kitchen_note: kitchenNote.value,
            order_date: new Date().toISOString().split("T")[0],
            order_time: new Date().toTimeString().split(" ")[0],
            order_type:
                orderType.value === "Dine_in"
                    ? "Dine In"
                    : orderType.value === "Delivery"
                        ? "Delivery"
                        : orderType.value === "Takeaway"
                            ? "Takeaway"
                            : "Collection",
            table_number: selectedTable.value?.name || null,
            payment_method: paymentMethod,
            auto_print_kot: autoPrintKot,

            // ✅ Add split payment handling
            cash_received: cashReceived,
            change: changeAmount,

            // ✅ Add these for split payments
            ...(paymentMethod === 'Split' && {
                payment_type: 'split',
                cash_amount: cashReceived,
                card_amount: cardAmount
            }),

            items: (orderItems.value ?? []).map((it) => ({
                product_id: it.id,
                title: it.title,
                quantity: it.qty,
                price: it.price,
                note: it.note ?? "",
                kitchen_note: kitchenNote.value ?? "",
                unit_price: it.unit_price,

                tax_percentage: getItemTaxPercentage(it),
                tax_amount: getItemTax(it),
                variant_id: it.variant_id || null,
                variant_name: it.variant_name || null,
                addons: it.addons || [],
            })),
        };

        const res = await axios.post("/pos/order", payload);
        resetCart();
        showConfirmModal.value = false;
        toast.success(res.data.message);

        // ✅ Merge the response with payload to include split payment data
        lastOrder.value = {
            ...res.data.order,
            ...payload,
            items: payload.items,
            // Ensure split payment data is included
            payment_type: paymentMethod === 'Split' ? 'split' : paymentMethod.toLowerCase(),
            cash_amount: paymentMethod === 'Split' ? cashReceived : null,
            card_amount: paymentMethod === 'Split' ? cardAmount : null,
        };

        // Open KOT modal after confirmation
        if (autoPrintKot) {
            kotData.value = await openPosOrdersModal();
            printKot(JSON.parse(JSON.stringify(lastOrder.value)));
            // showKotModal.value = true;
        }

        // Print receipt immediately
        printReceipt(JSON.parse(JSON.stringify(lastOrder.value)));

        // Clear promo after successful order
        selectedPromo.value = null;
    } catch (err) {
        console.error("Order submission error:", err);
        toast.error(err.response?.data?.message || "Failed to place order");
    }
    finally {
        if (done) done();
    }
};

/* ----------------------------
   Lifecycle
-----------------------------*/
onMounted(() => {
    if (window.bootstrap) {
        document
            .querySelectorAll('[data-bs-toggle="tooltip"]')
            .forEach((el) => new window.bootstrap.Tooltip(el));
        const modalEl = document.getElementById("chooseItem");
        if (modalEl) {
            chooseItemModal = new window.bootstrap.Modal(modalEl, {
                backdrop: "static",
            });
        }
    }
    fetchMenuCategories();
    fetchMenuItems();
    fetchProfileTables();
});

const page = usePage();

console.log("page data", page.props);
function bumpToasts() {
    const s = page.props.flash?.success;
    const e = page.props.flash?.error;
    if (s) toast.success(s, { autoClose: 4000 });
    if (e) toast.error(e, { autoClose: 6000 });
}
onMounted(() => bumpToasts());
onMounted(() => {
    const s = page.props.flash?.success;
    const e = page.props.flash?.error;
    // if (s) toast.success(s);
    if (e) toast.error(e);

    const payload = page.props.flash?.print_payload; // from controller
    if (payload) setTimeout(() => printReceipt(payload), 250);
    if (payload) setTimeout(() => printKot(payload), 250);
});
// Also react if flash changes due to subsequent navigations
watch(
    () => page.props.flash,
    () => bumpToasts(),
    { deep: true }
);



const handleKotStatusUpdated = ({ id, status, message }) => {

    kotData.value = kotData.value.map((kot) =>
        kot.id === id ? { ...kot, status } : kot
    );

    // toast.success(message); // optional
};


const showKotModal = ref(false);
const kotData = ref([]);
const kotLoading = ref(false);

const openOrderModal = async () => {
    showKotModal.value = true;   // show modal immediately
    kotData.value = [];           // clear old data
    kotLoading.value = true;      // start loading

    try {
        const res = await axios.get(`/api/pos/orders/today`);
        kotData.value = res.data.orders;
    } catch (err) {
        console.error("Failed to fetch today's orders:", err);
        toast.error(
            err.response?.data?.message || "Failed to fetch today's orders"
        );
        kotData.value = [];
    } finally {
        kotLoading.value = false;  // stop loading
    }
};


const showPosOrdersModal = ref(false);
const posOrdersData = ref([]);


const loading = ref(false);

const openPosOrdersModal = async () => {
    showPosOrdersModal.value = true;
    posOrdersData.value = [];
    loading.value = true;

    try {
        const res = await axios.get(`/api/pos/orders/today`);
        posOrdersData.value = res.data.orders;
        console.log("posOrdersData.value", posOrdersData.value);
    } catch (err) {
        console.error("Failed to fetch POS orders:", err);
        toast.error(
            err.response?.data?.message || "Failed to fetch POS orders"
        );
    } finally {
        loading.value = false;
    }
};

const showPromoModal = ref(false);
const loadingPromos = ref(true);
const promosData = ref([]);
const selectedPromo = ref(null);

// Update handleApplyPromo function
const handleApplyPromo = (promo) => {
    selectedPromo.value = promo;

    // Check if cart meets minimum purchase requirement
    if (promo.min_purchase && subTotal.value < parseFloat(promo.min_purchase)) {
        toast.warning(`Minimum purchase of ${formatCurrencySymbol(promo.min_purchase)} required for this promo.`);
    } else {
        toast.success(`Promo "${promo.name}" applied! You saved ${formatCurrencySymbol(promoDiscount.value)}`);
    }

    showPromoModal.value = false;
};



const handleClearPromo = () => {
    selectedPromo.value = null;
    toast.info("Promo cleared.");
};


// const openPromoModal = async () => {
//     loadingPromos.value = true;
//     try {
//         // Show the modal immediately (optional)
//         showPromoModal.value = true;

//         // Fetch promos from API
//         const response = await axios.get('/api/promos/today');
//         if (response.data.success) {
//             promosData.value = response.data.data;
//         } else {
//             console.error('Failed to fetch promos', response.data);
//             promosData.value = [];
//         }
//     } catch (error) {
//         console.error('Error fetching promos', error);
//         promosData.value = [];
//     } finally {
//         loadingPromos.value = false;
//     }
// };

const openPromoModal = async (item) => {
    console.log("OpenPromoModal: item =", item);
    loadingPromos.value = true;
    selectedItem.value = item;

    try {
        showPromoModal.value = true;

        const response = await axios.get(`/api/promos/for-item/${item.id}`);
        if (response.data.success) {
            promosData.value = response.data.data;
        } else {
            console.error('Failed to fetch promos', response.data);
            promosData.value = [];
        }
    } catch (error) {
        console.error('Error fetching promos', error);
        promosData.value = [];
    } finally {
        loadingPromos.value = false;
    }
};


const handleViewOrderDetails = (order) => {
    lastOrder.value = order;
    showReceiptModal.value = true;
    showPosOrdersModal.value = false;
};

// =============Promos Discount ======================
// Add these computed properties after your existing computed properties

const promoDiscount = computed(() => {
    if (!selectedPromo.value) return 0;

    const promo = selectedPromo.value;
    const rawDiscount = parseFloat(promo.discount_amount ?? 0) || 0;
    const subtotal = subTotal.value;

    // Check minimum purchase requirement
    if (promo.min_purchase && subtotal < parseFloat(promo.min_purchase)) {
        return 0;
    }

    // Calculate based on promo type
    if (promo.type === 'flat') {
        return rawDiscount;
    }

    if (promo.type === 'percent') {
        const discount = (subtotal * rawDiscount) / 100;
        const maxCap = parseFloat(promo.max_discount ?? 0) || 0;
        if (maxCap > 0 && discount > maxCap) {
            return maxCap;
        }
        return discount;
    }

    return 0;
});

console.log("page.props", page.props.onboarding.tax_and_vat.tax_rate);
/* ----------------------------
   Tax Calculation
-----------------------------*/
const totalTax = computed(() => {
    let tax = 0;
    const onboardingTaxRate = parseFloat(page.props.onboarding.tax_and_vat.tax_rate) || 0;
    orderItems.value.forEach((item) => {
        // Find the original menu item to get tax_percentage
        const menuItem = menuItems.value.find(m => m.id === item.id);

        if (menuItem && menuItem.is_taxable) {
            const itemSubtotal = item.unit_price * item.qty;
            const itemTax = (itemSubtotal * onboardingTaxRate) / 100;
            tax += itemTax;
        }
    });
    return tax;
});

// Update grandTotal to include tax
const grandTotal = computed(() => {
    const total = subTotal.value
        + totalTax.value
        + deliveryCharges.value
        + serviceCharges.value
        - promoDiscount.value;
    return Math.max(0, total);
});


// Helper function to get tax amount for a specific item
const getItemTax = (item) => {
    const menuItem = menuItems.value.find(m => m.id === item.id);
    if (menuItem && menuItem.is_taxable && menuItem.tax_percentage) {
        const itemSubtotal = item.unit_price * item.qty;
        return (itemSubtotal * parseFloat(menuItem.tax_percentage)) / 100;
    }
    return 0;
};

// Helper function to get tax percentage for display
const getItemTaxPercentage = (item) => {
    const menuItem = menuItems.value.find(m => m.id === item.id);
    return (menuItem && menuItem.is_taxable && menuItem.tax_percentage)
        ? parseFloat(menuItem.tax_percentage)
        : 0;
};


// ========================================
// Fixed: Get quantity for a product in the cart
// ========================================
const getCardQty = (product) => {
    const variant = getSelectedVariant(product);
    const variantId = variant ? variant.id : null;

    const selectedAddons = getSelectedAddons(product);
    const addonIds = selectedAddons.map(a => a.id).sort().join('-');

    const cartItem = orderItems.value.find(item => {
        const itemAddonIds = (item.addons || []).map(a => a.id).sort().join('-');
        return item.id === product.id &&
            item.variant_id === variantId &&
            itemAddonIds === addonIds;
    });

    return cartItem ? cartItem.qty : 0;
};

// ========================================
// Fixed: Check if we can add more (with proper variant check)
// ========================================
const canAddMore = (product) => {
    const variant = getSelectedVariant(product);
    const variantId = variant ? variant.id : null;

    // Get ingredients for the SELECTED variant
    const variantIngredients = getVariantIngredients(product, variantId);

    if (!variantIngredients.length) return true; // No ingredients = unlimited

    const currentQty = getCardQty(product);
    const menuStock = calculateMenuStock(product);

    if (menuStock <= 0) return false;

    // Build ingredient usage map from current cart
    const ingredientUsage = {};

    for (const item of orderItems.value) {
        const itemIngredients = getVariantIngredients(item, item.variant_id);
        itemIngredients.forEach(ing => {
            const id = ing.inventory_item_id;
            const stock = Number(ing.inventory_stock ?? ing.inventory_item?.stock ?? 0);

            if (!ingredientUsage[id]) {
                ingredientUsage[id] = {
                    totalStock: stock,
                    used: 0
                };
            }

            const required = Number(ing.quantity ?? ing.qty ?? 1) * item.qty;
            ingredientUsage[id].used += required;
        });
    }

    // Check if we can add ONE MORE of this product with selected variant
    for (const ing of variantIngredients) {
        const id = ing.inventory_item_id;
        const stock = Number(ing.inventory_stock ?? ing.inventory_item?.stock ?? 0);
        const required = Number(ing.quantity ?? ing.qty ?? 1) * (currentQty + 1);

        if (!ingredientUsage[id]) {
            // This ingredient isn't used by other cart items yet
            if (stock < required) return false;
        } else {
            // Check if there's enough stock after accounting for other cart items
            const available = ingredientUsage[id].totalStock - ingredientUsage[id].used;

            // If this exact item is already in cart, we need to add back its current usage
            const selectedAddons = getSelectedAddons(product);
            const addonIds = selectedAddons.map(a => a.id).sort().join('-');

            const existingItem = orderItems.value.find(item => {
                const itemAddonIds = (item.addons || []).map(a => a.id).sort().join('-');
                return item.id === product.id &&
                    item.variant_id === variantId &&
                    itemAddonIds === addonIds;
            });

            if (existingItem) {
                // Add back current usage of this exact item
                const currentUsage = Number(ing.quantity ?? ing.qty ?? 1) * existingItem.qty;
                const availableForThisItem = available + currentUsage;
                if (availableForThisItem < required) return false;
            } else {
                // New item, just check available stock
                if (available < required) return false;
            }
        }
    }

    return true;
};

// Check if ingredients are available for a specific quantity
const checkIngredientAvailability = (product, targetQty) => {
    const allIngredients = getAllIngredients(product);
    if (!allIngredients.length) return true;

    const ingredientStock = {};

    // Reduce stock for items already in cart
    for (const item of orderItems.value) {
        const itemIngredients = getAllIngredients(item);
        itemIngredients.forEach(ing => {
            const id = ing.inventory_item_id;
            if (!ingredientStock[id]) ingredientStock[id] = Number(ing.inventory_stock ?? ing.inventory_item?.stock ?? 0);
            ingredientStock[id] -= Number(ing.quantity ?? ing.qty ?? 1) * item.qty;
        });
    }

    // Check if enough stock for targetQty
    for (const ing of allIngredients) {
        const id = ing.inventory_item_id;
        const available = ingredientStock[id] ?? Number(ing.inventory_stock ?? ing.inventory_item?.stock ?? 0);
        const required = Number(ing.quantity ?? ing.qty ?? 1) * targetQty;
        if (available < required) return false;
    }

    return true;
};


// ========================================
// Fixed: Increment quantity from card with proper validation
// ========================================
const incrementCardQty = (product) => {
    // ✅ Check stock first (silent check, no toast since button is disabled)
    if (getProductStock(product) <= 0) {
        return; // Exit silently, button is already disabled
    }

    const variant = getSelectedVariant(product);
    const variantPrice = variant ? parseFloat(variant.price) : product.price;
    const variantId = variant ? variant.id : null;
    const variantName = variant ? variant.name : null;

    const selectedAddons = getSelectedAddons(product);
    const addonsPrice = getAddonsPrice(product);
    const totalItemPrice = variantPrice + addonsPrice;
    const addonIds = selectedAddons.map(a => a.id).sort().join('-');

    // Get ingredients for the SELECTED variant
    const variantIngredients = getVariantIngredients(product, variantId);

    // Check if we can add more (only show toast if there ARE ingredients but not enough)
    if (!canAddMore(product)) {
        if (variantIngredients.length > 0) {
            const variantText = variantName ? ` (${variantName})` : '';
            toast.error(`Not enough ingredients for "${product.title}${variantText}".`);
        }
        return;
    }

    const existingIndex = orderItems.value.findIndex(item => {
        const itemAddonIds = (item.addons || []).map(a => a.id).sort().join('-');
        return item.id === product.id &&
            item.variant_id === variantId &&
            itemAddonIds === addonIds;
    });

    if (existingIndex >= 0) {
        orderItems.value[existingIndex].qty++;
        orderItems.value[existingIndex].price =
            orderItems.value[existingIndex].unit_price * orderItems.value[existingIndex].qty;
        orderItems.value[existingIndex].outOfStock = false;
    } else {
        const menuStock = calculateMenuStock(product);
        orderItems.value.push({
            id: product.id,
            title: product.title,
            img: product.img,
            price: totalItemPrice,
            unit_price: Number(totalItemPrice),
            qty: 1,
            note: "",
            stock: menuStock,
            ingredients: variantIngredients, // Use variant-specific ingredients
            variant_id: variantId,
            variant_name: variantName,
            addons: selectedAddons,
            outOfStock: false,
        });
    }
};


// ========================================
// Fixed: Decrement quantity from card
// ========================================
const decrementCardQty = (product) => {
    const variant = getSelectedVariant(product);
    const variantId = variant ? variant.id : null;

    const selectedAddons = getSelectedAddons(product);
    const addonIds = selectedAddons.map(a => a.id).sort().join('-');

    const existingIndex = orderItems.value.findIndex(item => {
        const itemAddonIds = (item.addons || []).map(a => a.id).sort().join('-');
        return item.id === product.id &&
            item.variant_id === variantId &&
            itemAddonIds === addonIds;
    });

    if (existingIndex < 0) return;

    const item = orderItems.value[existingIndex];
    if (item.qty <= 1) {
        orderItems.value.splice(existingIndex, 1);
    } else {
        item.qty--;
        item.price = item.unit_price * item.qty;
        item.outOfStock = false;
    }
};


// ========================================
// Helper: Get all ingredients for a specific variant
// ========================================
const getVariantIngredients = (product, variantId) => {
    if (!variantId || !product.variants?.length) {
        return product.ingredients ?? [];
    }

    const variant = product.variants.find(v => v.id === variantId);
    return variant?.ingredients ?? product.ingredients ?? [];
};

// ========================================
// Fixed: Check if we can increment a cart item
// ========================================
const canIncCartItem = (cartItem) => {
    if (!cartItem || !cartItem.ingredients?.length) return true;

    // Build ingredient usage map from current cart
    const ingredientUsage = {};

    for (const item of orderItems.value) {
        const itemIngredients = item.ingredients ?? [];
        itemIngredients.forEach(ing => {
            const id = ing.inventory_item_id;
            const stock = Number(ing.inventory_stock ?? ing.inventory_item?.stock ?? 0);

            if (!ingredientUsage[id]) {
                ingredientUsage[id] = {
                    totalStock: stock,
                    used: 0
                };
            }

            const required = Number(ing.quantity ?? ing.qty ?? 1) * item.qty;
            ingredientUsage[id].used += required;
        });
    }

    // Check if we can add ONE MORE of this specific cart item
    for (const ing of cartItem.ingredients) {
        const id = ing.inventory_item_id;
        const stock = Number(ing.inventory_stock ?? ing.inventory_item?.stock ?? 0);

        if (!ingredientUsage[id]) {
            if (stock < Number(ing.quantity ?? ing.qty ?? 1)) return false;
        } else {
            const available = ingredientUsage[id].totalStock - ingredientUsage[id].used;
            const currentItemUsage = Number(ing.quantity ?? ing.qty ?? 1) * cartItem.qty;
            const availableForThisItem = available + currentItemUsage;
            const requiredForNewQty = Number(ing.quantity ?? ing.qty ?? 1) * (cartItem.qty + 1);

            if (availableForThisItem < requiredForNewQty) return false;
        }
    }

    return true;
};


</script>

<template>

    <Head title="Sale" />

    <Master>
        <div class="page-wrapper">
            <div class="container-fluid px-3 py-3">
                <div class="row gx-3 gy-3">
                    <!-- LEFT: Menu -->
                    <div :class="showCategories ? 'col-md-12' : 'col-lg-8'">
                        <!-- Categories Grid -->
                        <div v-if="showCategories" class="row g-3">
                            <div class="col-12 mb-3">
                                <!-- <h5 class="fw-bold  mb-0"></h5> -->
                                <h4 class="mb-3">Menu Categories</h4>
                                <hr class="mt-2 mb-3">
                            </div>
                            <div v-if="menuCategoriesLoading" class="col-12 text-center py-5">
                                <div class="spinner-border" role="status"
                                    style="color: #1B1670; width: 3rem; height: 3rem; border-width: 0.3em;"></div>
                                <div class="mt-2 fw-semibold text-muted">Loading...</div>
                            </div>


                            <!-- Categories List -->
                            <template v-else>
                                <div v-for="c in menuCategories" :key="c.id" class="col-6 col-md-4 col-lg-4">
                                    <div class="cat-card" @click="openCategory(c)">
                                        <div class="cat-icon-wrap">
                                            <span class="cat-icon">
                                                <img v-if="c.image_url" :src="c.image_url" alt="Category Image"
                                                    class="cat-image" />
                                                <span v-else>🍵</span>
                                            </span>
                                        </div>
                                        <div class="cat-name">{{ c.name }}</div>
                                        <div class="cat-pill">
                                            {{ c.menu_items_count }} Menu
                                        </div>
                                    </div>
                                </div>


                                <!-- No Categories Found -->
                                <div v-if="!menuCategoriesLoading && menuCategories.length === 0" class="col-12">
                                    <div class="alert alert-light border text-center rounded-4">
                                        No categories found
                                    </div>
                                </div>
                            </template>
                        </div>


                        <!-- Items in selected category -->
                        <div v-else>
                            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
                                <button class="btn btn-primary rounded-pill shadow-sm px-3" @click="backToCategories">
                                    <i class="bi bi-arrow-left me-1"></i> Back
                                </button>

                                <h5 class="fw-bold mb-0">
                                    {{
                                        menuCategories.find(
                                            (c) => c.id === activeCat
                                        )?.name || "Items"
                                    }}
                                </h5>

                                <!-- Search & Filter Section -->
                                <div class="d-flex gap-2 ms-auto align-items-center">
                                    <!-- Filter Button -->
                                    <FilterModal v-model="filters" title="Menu Items" modal-id="menuFilterModal"
                                        :sort-options="[
                                            { value: 'name_asc', label: 'Name: A to Z' },
                                            { value: 'name_desc', label: 'Name: Z to A' },
                                            { value: 'price_asc', label: 'Price: Low to High' },
                                            { value: 'price_desc', label: 'Price: High to Low' },
                                        ]" :show-price-range="true" :show-category="false" :show-stock-status="false"
                                        :show-date-range="false" :categories="[]" :suppliers="[]"
                                        @apply="handleApplyFilters" @clear="handleClearFilters" />

                                    <!-- Search Input -->
                                    <div class="search-wrap position-relative">
                                        <i class="bi bi-search position-absolute"
                                            style="left: 12px; top: 50%; transform: translateY(-50%); z-index: 10;"></i>
                                        <input v-model="searchQuery" class="form-control search-input ps-5" type="text"
                                            placeholder="Search items..." style="min-width: 250px;" />
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-12 col-md-6 col-xl-6 d-flex" v-for="p in filteredProducts" :key="p.id">
                                    <div class="card rounded-4 shadow-sm overflow-hidden border-3 w-100 d-flex flex-row align-items-stretch"
                                        :style="{ borderColor: p.label_color || '#1B1670' }">

                                        <!-- Left Side (Image + Price Badge) - 40% -->
                                        <div class="position-relative" style="flex: 0 0 40%; max-width: 40%;">
                                            <img :src="p.img" alt="" class="w-100 h-100" style="object-fit: cover;" />

                                            <!-- Dynamic Price Badge including addons -->
                                            <span
                                                class="position-absolute top-0 start-0 m-2 px-3 py-1 rounded-pill text-white small fw-semibold"
                                                :style="{ background: p.label_color || '#1B1670' }">
                                                {{ formatCurrencySymbol(getTotalPrice(p)) }}
                                            </span>

                                            <!-- OUT OF STOCK Badge (only if selected variant is out of stock) -->
                                            <span v-if="getProductStock(p) <= 0"
                                                class="position-absolute bottom-0 start-0 end-0 m-2 badge bg-danger py-2">
                                                OUT OF STOCK
                                            </span>
                                        </div>

                                        <!-- Right Side (Details + Variant + Addons + Quantity Controls) -->
                                        <div class="p-3 d-flex flex-column justify-content-between"
                                            style="flex: 1 1 60%; min-width: 0;">
                                            <div>
                                                <div class="h5 fw-bold mb-2"
                                                    :style="{ color: p.label_color || '#1B1670' }">
                                                    {{ p.title }}
                                                </div>

                                                <!-- Variant Dropdown -->
                                                <div v-if="p.variants && p.variants.length > 0" class="mb-3">
                                                    <label class="form-label small fw-semibold mb-1">
                                                        Variants:
                                                    </label>
                                                    <select v-model="selectedCardVariant[p.id]"
                                                        class="form-select form-select-sm variant-select"
                                                        @change="onVariantChange(p, $event)" @click.stop>
                                                        <option v-for="variant in p.variants" :key="variant.id"
                                                            :value="variant.id">
                                                            {{ variant.name }} - {{ formatCurrencySymbol(variant.price)
                                                            }}
                                                        </option>
                                                    </select>
                                                </div>

                                                <!-- Addons Selection -->
                                                <div v-if="p.addon_groups && p.addon_groups.length > 0">
                                                    <div v-for="group in p.addon_groups" :key="group.group_id"
                                                        class="mb-3">
                                                        <label class="form-label small fw-semibold mb-2">
                                                            {{ group.group_name }}
                                                            <span v-if="group.max_select > 0" class="text-muted"
                                                                style="font-size: 0.75rem;">
                                                                (Max {{ group.max_select }})
                                                            </span>
                                                        </label>
                                                        <MultiSelect
                                                            :modelValue="selectedCardAddons[p.id]?.[group.group_id] || []"
                                                            @update:modelValue="(val) => handleAddonChange(p, group.group_id, val)"
                                                            :options="group.addons" optionLabel="name" dataKey="id"
                                                            placeholder="Select addons" :maxSelectedLabels="3"
                                                            class="w-100 addon-multiselect">
                                                            <template #value="slotProps">
                                                                <div v-if="slotProps.value && slotProps.value.length > 0"
                                                                    class="d-flex flex-wrap gap-1">
                                                                    <span v-for="addon in slotProps.value"
                                                                        :key="addon.id" class="badge bg-primary">
                                                                        {{ addon.name }} +{{
                                                                            formatCurrencySymbol(addon.price) }}
                                                                    </span>
                                                                </div>
                                                                <span v-else>
                                                                    {{ slotProps.placeholder }}
                                                                </span>
                                                            </template>
                                                            <template #option="slotProps">
                                                                <div
                                                                    class="d-flex justify-content-between align-items-center w-100">
                                                                    <span>{{ slotProps.option.name }}</span>
                                                                    <span class="fw-semibold text-success">
                                                                        +{{ formatCurrencySymbol(slotProps.option.price)
                                                                        }}
                                                                    </span>
                                                                </div>
                                                            </template>
                                                        </MultiSelect>
                                                    </div>
                                                </div>

                                                <!-- View Details Button -->
                                                <button class="btn btn-primary btn-sm mb-2"
                                                    @click="openDetailsModal(p)">
                                                    View Details
                                                </button>
                                            </div>

                                            <!-- ✅ Quantity Controls (ALWAYS SHOW, but disable when out of stock) -->
                                            <div class="mt-2 d-flex align-items-center justify-content-start gap-2"
                                                @click.stop>
                                                <button
                                                    class="qty-btn btn btn-outline-secondary rounded-circle px-2 py-2"
                                                    style="width: 55px; height: 36px;" @click.stop="decrementCardQty(p)"
                                                    :disabled="getCardQty(p) <= 0">
                                                    <strong>−</strong>
                                                </button>
                                                <div class="qty-box border rounded-pill px-2 py-2 text-center fw-semibold"
                                                    style="min-width: 55px;">
                                                    {{ getCardQty(p) }}
                                                </div>
                                                <button
                                                    class="qty-btn btn btn-outline-secondary rounded-circle px-2 py-2"
                                                    style="width: 55px; height: 36px;" @click.stop="incrementCardQty(p)"
                                                    :disabled="!canAddMore(p) || getProductStock(p) <= 0"
                                                    :class="{ 'opacity-50': getProductStock(p) <= 0 }">
                                                    <strong>+</strong>
                                                </button>
                                            </div>


                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- RIGHT: Cart -->

                    <div class="col-lg-4" v-if="!showCategories">
                        <div class="col-lg-4 d-flex align-items-center gap-2 mb-2">
                            <!-- KOT Orders button -->
                            <!-- <button class="btn btn-primary rounded-pill d-flex align-items-center gap-2 px-4"
                                @click="openOrderModal">
                                <Package class="lucide-icon" width="16" height="16" />
                                KOT
                            </button> -->

                            <!-- POS Orders button -->
                            <!-- <button class="btn btn-success rounded-pill d-flex align-items-center gap-2 px-3"
                                @click="openPosOrdersModal">
                                <ShoppingCart class="lucide-icon" width="16" height="16" />
                                Orders
                            </button> -->
                            <!-- <button class="btn btn-warning rounded-pill px-3 py-2" @click="openPromoModal">
                                Promos
                            </button> -->

                        </div>

                        <div class="cart card border-0 shadow-lg rounded-4">

                            <div class="cart-header">

                                <div class="order-type">
                                    <button v-for="(type, i) in orderTypes" :key="i" class="ot-pill btn"
                                        :class="{ active: orderType === type }" @click="orderType = type">
                                        {{ type.replace(/_/g, " ") }}
                                    </button>
                                    <div class="d-flex justify-content-between mb-3">

                                    </div>
                                </div>
                            </div>

                            <div class="cart-body">
                                <!-- Dine-in table / customer -->
                                <div class="mb-3">

                                    <div v-if="orderType === 'Dine_in'" class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label small">Table</label>
                                            <select v-model="selectedTable" class="form-select form-select-sm">
                                                <option :value="null">Select Table</option>
                                                <option v-for="(table, idx) in profileTables.table_details" :key="idx"
                                                    :value="table">
                                                    {{ table.name }}
                                                </option>
                                            </select>
                                            <div v-if="formErrors.table_number" class="invalid-feedback d-block">
                                                {{ formErrors.table_number[0] }}
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">Customer</label>
                                            <input v-model="customer" class="form-control form-control-sm"
                                                placeholder="Walk In" />
                                        </div>
                                    </div>

                                    <div v-else>
                                        <label class="form-label small">Customer</label>
                                        <input v-model="customer" class="form-control form-control-sm"
                                            placeholder="Walk In" :class="{ 'is-invalid': formErrors.customer }" />

                                        <div v-if="formErrors.customer" class="invalid-feedback d-block">
                                            {{ formErrors.customer[0] }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Line items -->
                                <div class="cart-lines">
                                    <div v-if="orderItems.length === 0" class="empty">
                                        Add items from the left
                                    </div>

                                    <div v-for="(it, i) in orderItems" :key="it.title" class="line">
                                        <div class="line-left">
                                            <img :src="it.img" alt="" />
                                            <div class="meta">
                                                <div class="name" :title="it.title">
                                                    {{ it.title }}
                                                    <!-- ✅ Show variant name badge -->
                                                    <span v-if="it.variant_name" class="badge ms-1"
                                                        style="font-size: 0.65rem; background: #1B1670; color: white;">
                                                        {{ it.variant_name }}
                                                    </span>
                                                </div>
                                                <!-- <div v-if="it.addons && it.addons.length > 0" class="addons-list mt-1">
                                                    <small class="text-muted d-block" style="font-size: 0.7rem;">
                                                        <i class="bi bi-plus-circle me-1"></i>
                                                        <span v-for="(addon, idx) in it.addons" :key="addon.id">
                                                            {{ addon.name }} (+{{ formatCurrencySymbol(addon.price) }})
                                                            <span v-if="idx < it.addons.length - 1">, </span>
                                                        </span>
                                                    </small>
                                                </div> -->
                                                <div class="note" v-if="it.note">
                                                    {{ it.note }}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="line-mid">
                                            <button class="qty-btn" @click="decCart(i)">−</button>
                                            <div class="qty">{{ it.qty }}</div>

                                            <button class="qty-btn" @click="incCart(i)"
                                                :disabled="it.outOfStock || !canIncCartItem(it)"
                                                :class="{ 'bg-secondary text-white cursor-not-allowed opacity-70': it.outOfStock || !canIncCartItem(it) }">
                                                +
                                            </button>
                                        </div>

                                        <div class="line-right">
                                            <div class="price">
                                                {{ formatCurrencySymbol(it.price) }}
                                            </div>
                                            <button class="del" @click="removeCart(i)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>

                                        <!-- <button class="btn btn-warning rounded-pill px-3 py-2"
                                            @click="openPromoModal(it)">
                                            Promos
                                        </button> -->
                                    </div>
                                </div>

                                <!-- Totals -->
                                <div class="totals">
                                    <div class="trow">
                                        <span>Sub Total</span>
                                        <b class="sub-total">{{ formatCurrencySymbol(subTotal) }}</b>
                                    </div>

                                    <!-- Tax Row -->
                                    <div class="trow" v-if="totalTax > 0">
                                        <span class="d-flex align-items-center gap-2">
                                            <i class="bi bi-receipt text-info"></i>
                                            Tax
                                        </span>
                                        <b class="text-info">{{ formatCurrencySymbol(totalTax) }}</b>
                                    </div>

                                    <div v-if="serviceCharges > 0" class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Service Charges:</span>
                                        <span class="fw-semibold">{{ formatCurrencySymbol(serviceCharges) }}</span>
                                    </div>

                          
                                    <div v-if="deliveryCharges > 0" class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Delivery Charges:</span>
                                        <span class="fw-semibold">{{ formatCurrencySymbol(deliveryCharges) }}</span>
                                    </div>


                                    <!-- Promo Discount -->
                                    <div class="trow promo-discount" v-if="selectedPromo && promoDiscount > 0">
                                        <span class="d-flex align-items-center gap-2">
                                            <i class="bi bi-tag-fill text-success"></i>
                                            Promo: {{ selectedPromo.name }}
                                        </span>
                                        <b class="text-success">-{{ formatCurrencySymbol(promoDiscount) }}</b>
                                    </div>

                                    <!-- Total -->
                                    <div class="trow total">
                                        <span>Total</span>
                                        <b>{{ formatCurrencySymbol(grandTotal) }}</b>
                                    </div>
                                </div>

                                <!-- <textarea v-model="note" rows="3" class="form-control form-control-sm rounded-3"
                                    placeholder="Note"></textarea> -->

                                <div class="mb-3">
                                    <label for="frontNote" class="form-label small fw-semibold">Front Note</label>
                                    <textarea id="frontNote" v-model="note" rows="3"
                                        class="form-control form-control-sm rounded-3"
                                        placeholder="Enter front note..."></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="kitchenNote" class="form-label small fw-semibold">Kitchen Note</label>
                                    <textarea id="kitchenNote" v-model="kitchenNote" rows="3"
                                        class="form-control form-control-sm rounded-3"
                                        placeholder="Enter kitchen note..."></textarea>
                                </div>

                            </div>

                            <div class="cart-footer">
                                <button class="btn btn-secondary btn-clear" @click="resetCart()">
                                    Clear
                                </button>
                                <button class="btn btn-primary btn-place" @click="openConfirmModal">
                                    Place Order
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Choose Item Modal -->
            <div class="modal fade" id="chooseItem" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content rounded-4 border-0 shadow">
                        <div class="modal-header border-0">
                            <h5 class="modal-title fw-bold">
                                {{ selectedItem?.title || "Choose Item" }}
                            </h5>
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
                                <div class="col-md-5">
                                    <img :src="selectedItem?.image_url ||
                                        selectedItem?.img ||
                                        '/assets/img/product/product29.jpg'
                                        " class="img-fluid rounded-3 w-100" alt="" />
                                </div>
                                <div class="col-md-7">
                                    <!-- Dynamic Price (variant + addons) -->
                                    <div class="h4 mb-3">
                                        {{ formatCurrencySymbol(getModalTotalPrice()) }}
                                    </div>

                                    <!-- Variant Dropdown -->
                                    <div v-if="selectedItem?.variants && selectedItem.variants.length > 0" class="mb-3">
                                        <label class="form-label small fw-semibold mb-1">
                                            Variants:
                                        </label>
                                        <select v-model="modalSelectedVariant" class="form-select form-select-sm"
                                            @change="onModalVariantChange">
                                            <option v-for="variant in selectedItem.variants" :key="variant.id"
                                                :value="variant.id">
                                                {{ variant.name }} - {{ formatCurrencySymbol(variant.price) }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Addons Selection -->
                                    <div v-if="selectedItem?.addon_groups && selectedItem.addon_groups.length > 0">
                                        <div v-for="group in selectedItem.addon_groups" :key="group.group_id"
                                            class="mb-3">
                                            <label class="form-label small fw-semibold mb-2">
                                                {{ group.group_name }}
                                                <span v-if="group.max_select > 0" class="text-muted"
                                                    style="font-size: 0.75rem;">
                                                    (Max {{ group.max_select }})
                                                </span>
                                            </label>
                                            <MultiSelect :modelValue="modalSelectedAddons[group.group_id] || []"
                                                @update:modelValue="(val) => handleModalAddonChange(group.group_id, val)"
                                                :options="group.addons" optionLabel="name" dataKey="id"
                                                placeholder="Select addons" :maxSelectedLabels="3"
                                                class="w-100 addon-multiselect">
                                                <template #value="slotProps">
                                                    <div v-if="slotProps.value && slotProps.value.length > 0"
                                                        class="d-flex flex-wrap gap-1">
                                                        <span v-for="addon in slotProps.value" :key="addon.id"
                                                            class="badge bg-primary">
                                                            {{ addon.name }} +{{ formatCurrencySymbol(addon.price) }}
                                                        </span>
                                                    </div>
                                                    <span v-else>
                                                        {{ slotProps.placeholder }}
                                                    </span>
                                                </template>
                                                <template #option="slotProps">
                                                    <div
                                                        class="d-flex justify-content-between align-items-center w-100">
                                                        <span>{{ slotProps.option.name }}</span>
                                                        <span class="fw-semibold text-success">
                                                            +{{ formatCurrencySymbol(slotProps.option.price) }}
                                                        </span>
                                                    </div>
                                                </template>
                                            </MultiSelect>
                                        </div>
                                    </div>

                                    <!-- Nutrition, Allergies, Tags (Dynamic based on variant) -->
                                    <div class="chips mb-3">
                                        <div class="mb-1">
                                            <strong>Nutrition:</strong>
                                        </div>
                                        <span v-if="getModalNutrition()?.calories" class="chip chip-orange">
                                            Cal: {{ getModalNutrition().calories }}
                                        </span>
                                        <span v-if="getModalNutrition()?.carbs" class="chip chip-green">
                                            Carbs: {{ getModalNutrition().carbs }}
                                        </span>
                                        <span v-if="getModalNutrition()?.fat" class="chip chip-purple">
                                            Fat: {{ getModalNutrition().fat }}
                                        </span>
                                        <span v-if="getModalNutrition()?.protein" class="chip chip-blue">
                                            Protein: {{ getModalNutrition().protein }}
                                        </span>

                                        <div class="w-100 mt-2">
                                            <strong>Allergies:</strong>
                                        </div>
                                        <span v-for="(a, i) in getModalAllergies()" :key="'a-' + i"
                                            class="chip chip-red">{{ a.name
                                            }}</span>

                                        <div class="w-100 mt-2">
                                            <strong>Tags:</strong>
                                        </div>
                                        <span v-for="(t, i) in getModalTags()" :key="'t-' + i" class="chip chip-teal">{{
                                            t.name }}</span>
                                    </div>

                                    <!-- Quantity Controls -->
                                    <div class="qty-group gap-1">
                                        <button class="qty-btn" @click="decQty">
                                            −
                                        </button>
                                        <div class="qty-box rounded-pill">
                                            {{ modalQty }}
                                        </div>
                                        <button class="qty-btn" @click="incQty" :disabled="!canIncModalQty()">
                                            +
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-0">
                            <button class="btn btn-primary btn-sm py-2 rounded-pill px-4" @click="confirmAdd">
                                Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <KotModal :show="showKotModal" :kot="kotData" :loading="kotLoading" @close="showKotModal = false"
                @status-updated="handleKotStatusUpdated" />


            <!-- Confirm / Receipt (unchanged props) -->
            <!-- <ConfirmOrderModal :show="showConfirmModal" :customer="customer" :order-type="orderType"
                :selected-table="selectedTable" :order-items="orderItems" :grand-total="grandTotal" :money="money"
                v-model:cashReceived="cashReceived" :client_secret="client_secret" :order_code="order_code"
                :sub-total="subTotal" :tax="0" :service-charges="0" :delivery-charges="0" :note="note"
                :order-date="new Date().toISOString().split('T')[0]"
                :order-time="new Date().toTimeString().split(' ')[0]" :payment-method="paymentMethod"
                :change="changeAmount" @close="showConfirmModal = false" @confirm="confirmOrder" /> -->
            <ReceiptModal :show="showReceiptModal" :order="lastOrder" :money="money"
                @close="showReceiptModal = false" />


            <ConfirmOrderModal :show="showConfirmModal" :customer="customer" :order-type="orderType"
                :selected-table="selectedTable" :order-items="orderItems" :grand-total="grandTotal" :money="money"
                v-model:cashReceived="cashReceived" :client_secret="client_secret" :order_code="order_code"
                :sub-total="subTotal" :tax="0" :service-charges="0" :delivery-charges="deliveryCharges"
                :promo-discount="promoDiscount" :promo-id="selectedPromo?.id" :promo-name="selectedPromo?.name"
                :promo-type="selectedPromo?.type" :promo-discount-amount="promoDiscount" :note="note"
                :kitchen-note="kitchenNote" :order-date="new Date().toISOString().split('T')[0]"
                :order-time="new Date().toTimeString().split(' ')[0]" :payment-method="paymentMethod"
                :change="changeAmount" @close="showConfirmModal = false" @confirm="confirmOrder" />

            <PosOrdersModal :show="showPosOrdersModal" :orders="posOrdersData" @close="showPosOrdersModal = false"
                @view-details="handleViewOrderDetails" :loading="loading" />

            <PromoModal :show="showPromoModal" :loading="loadingPromos" :promos="promosData" :order-items="orderItems"
                @apply-promo="handleApplyPromo" @close="showPromoModal = false" />


        </div>
    </Master>
</template>

<style scoped>
.dark .ot-pill {
    background-color: #181818;
    color: #fff;
}

/* Add this CSS to make the cart fixed on scroll */

/* Make the cart column fixed */
.col-lg-4:has(.cart) {
    position: fixed;
    right: 0;
    top: 85px;
    width: 28%;
    /* lg-4 column width */
    padding-right: 15px;
    padding-left: 15px;
    max-height: calc(100vh - 40px);
    overflow: hidden;
    z-index: 100;
}



.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}



.dark .fw-bold {
    color: #fff !important;
}

/* Add padding to the left column to prevent overlap */
.col-lg-8:not(:has(+ .col-lg-4:has(.cart))) {
    margin-right: 0;
}

.row:has(.col-lg-4:has(.cart)) .col-lg-8 {
    padding-right: 15px;
}

/* Make the cart itself scrollable if content is too long */
.cart {
    max-height: calc(70vh);
    display: flex;
    flex-direction: column;
}

/* Make cart body scrollable */
.cart-body {
    overflow-y: auto;
    flex: 1;
    min-height: 0;
}

/* Ensure cart header and footer stay fixed within the card */
.cart-header {
    flex-shrink: 0;
}

.cart-footer {
    flex-shrink: 0;
}

/* Custom scrollbar for cart body (optional) */
.cart-body::-webkit-scrollbar {
    width: 6px;
}

.cart-body::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.cart-body::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

.cart-body::-webkit-scrollbar-thumb:hover {
    background: #555;
}

.dark .cart-body {
    background-color: #181818;
}

.dark .item-title {
    color: #fff !important;
}

.dark .bg-light {
    background-color: #181818 !important;
}

.dark b {
    color: #fff !important;
}

.dark .item-sub {
    color: #fff !important;
}

.dark .cart-footer {
    background-color: #181818;
}

.dark .sub-total {
    color: #fff !important;
}

.dark .form-control {
    background-color: #181818;
    color: white;
}

.dark .item-card {
    background-color: #181818 !important;
    color: white !important;
}

.dark .cart-lines {
    background-color: #181818;
}

.dark .chip-orange {
    color: #0000;
}

.dark .modal-footer {
    background-color: #121212 !important;
}

.dark .alert {
    background-color: #181818;
}

.dark .table {
    background-color: #181818 !important;
    /* gray-900 */
    color: #f9fafb !important;
}

.dark .table thead {
    background-color: #4d4d4d !important;
    color: #ffffff;
}

.dark .table thead th {
    background-color: #4d4d4d !important;
    color: #ffffff;
}

/* ========== Page Base ========== */
.page-wrapper {
    background: #f5f7fb;
}

/* ========== Categories Grid ========== */
.cat-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    background: #fff;
    border-radius: 14px;
    padding: 1.75rem 1rem;
    text-align: center;
    cursor: pointer;
    box-shadow: 0 6px 16px rgba(17, 23, 31, 0.06);
    transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.cat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 24px rgba(17, 23, 31, 0.1);
}

/* circular gray icon holder */
.cat-icon-wrap {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #eee;
    /* light gray like the screen */
    display: grid;
    place-items: center;
    margin-bottom: 0.25rem;
}

/* the actual icon (emoji/text/svg) */
.cat-icon {
    font-size: 1.35rem;
    /* tweak to match your cup size */
    line-height: 1;
}

/* title */
.cat-name {
    font-weight: 700;
    font-size: 1rem;
    color: #141414;
    /* per your dark accent preference */
}

/* little purple pill with count */
.cat-pill {
    display: inline-block;
    font-size: 0.72rem;
    line-height: 1;
    padding: 0.35rem 0.6rem;
    border-radius: 999px;
    color: #fff;
    background: #1b1670;
    box-shadow: 0 2px 6px rgba(75, 43, 183, 0.25);
}

/* spacing on small screens */
@media (max-width: 575.98px) {
    .cat-card {
        padding: 1.25rem 0.75rem;
    }

    .cat-icon-wrap {
        width: 52px;
        height: 52px;
    }

    .cat-name {
        font-size: 0.95rem;
    }
}

/* ========== Search Pill ========== */
.search-wrap {
    position: relative;
    min-width: 220px;
}

.search-wrap .bi-search {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
}

.search-input {
    padding-left: 34px;
    border-radius: 999px;
    background: #fff;
}

/* ========== Items Grid ========== */
.item-card {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    width: 100%;
    box-shadow: 0 8px 18px rgba(17, 23, 31, 0.06);
    cursor: pointer;
    transition: 0.2s;
    display: flex;
    flex-direction: column;
    border: 2px solid #1b1670;
    /* fallback if no inline style */
}

.item-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 22px rgba(17, 23, 31, 0.1);
}

.item-img {
    position: relative;
    aspect-ratio: 1/1;
    overflow: hidden;
}

.item-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* out-of-stock look */
.item-card.out-of-stock {
    cursor: not-allowed;
    opacity: 0.9;
    position: relative;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
}

/* overlay background */
.item-card.out-of-stock::after {
    content: "";
    position: absolute;
    inset: 0;
    /* cover whole card */
    background: rgba(90, 85, 85, 0.192);
    /* semi-transparent dark overlay */
    border-radius: 16px;
    /* match card radius */
    z-index: 2;
    /* sit above content */
}

/* make text & badge still visible above overlay */
/* .item-card.out-of-stock .item-body,
.item-card.out-of-stock .item-badge {
    position: relative;
    z-index: 3;
} */
.dark .form-select {
    background-color: #212121;
    color: #fff;
}

.item-price {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #1b1670;
    /* theme purple */
    color: #fff;
    padding: 0.25rem 0.75rem;
    font-weight: 600;
    font-size: 0.8rem;
    border-radius: 999px;
    /* pill shape */
    line-height: 1;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    z-index: 2;
}

.item-badge {
    position: absolute;
    left: 10px;
    top: 10px;
    background: #ffeded;
    color: #c0392b;
    padding: 0.1rem 0.5rem;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.75rem;
}

.item-body {
    padding: 0.6rem 0.75rem 0.8rem;
}

.item-title {
    font-weight: 700;
    font-size: 0.98rem;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.item-sub {
    color: #8a8fa7;
    font-size: 0.8rem;
}

/* ========== Cart Panel ========== */
.cart {
    display: flex;
    flex-direction: column;
    border-top-left-radius: 1rem;
    border-top-right-radius: 1rem;
}

.cart-header {
    background: #1b1670;
    color: #fff;
    padding: 0.9rem 1rem;
    border-top-left-radius: 1rem;
    border-top-right-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.cart-title {
    font-weight: 800;
    letter-spacing: 0.3px;
}

.order-type {
    display: flex;
    gap: 0.4rem;
}

.ot-pill {
    background: rgba(255, 255, 255, 0.18);
    color: #fff;
    border: 0;
    border-radius: 999px;
    padding: 0.25rem 0.65rem;
    font-size: 0.8rem;
}

.dark .ot-pill {
    background: rgba(255, 255, 255, 0.18);
    color: #fff;
    border: 0;
    border-radius: 999px;
    padding: 0.25rem 0.65rem;
    font-size: 0.8rem;
}

.ot-pill.active {
    background: #fff;
    color: #1b1670;
    font-weight: 700;
}

.dark .ot-pill.active {
    background-color: #181818;
    color: #fff;
    font-weight: 700;
}

.cart-body {
    padding: 1rem;
    background: #fff;
}

.cart-lines {
    background: #fff;
    border: 1px dashed #e8e9ef;
    border-radius: 12px;
    padding: 0.5rem;
    max-height: 360px;
    overflow: auto;
}

.empty {
    color: #9aa0b6;
    text-align: center;
    padding: 1.25rem 0;
}

.line {
    display: grid;
    grid-template-columns: 1fr auto auto;
    align-items: center;
    gap: 0.6rem;
    padding: 0.45rem 0.35rem;
    border-bottom: 1px solid #f1f2f6;
}

.line:last-child {
    border-bottom: 0;
}

.line-left {
    display: flex;
    gap: 0.6rem;
    align-items: center;
    min-width: 0;
}

.line-left img {
    width: 38px;
    height: 38px;
    object-fit: cover;
    border-radius: 8px;
}

.meta .name {
    font-weight: 700;
    font-size: 0.92rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.meta .note {
    font-size: 0.75rem;
    color: #8a8fa7;
}

.line-mid {
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.qty-btn {
    width: 28px;
    height: 28px;
    border-radius: 999px;
    border: 0;
    background: #1b1670;
    color: #fff;
    font-weight: 800;
    line-height: 1.5;
}

.dark .qty {
    width: 28px;
    height: 28px;
    border-radius: 999px;
    border: 0;
    background: #1b1670;
    color: #fff;
    font-weight: 800;
    line-height: 1.5;
}

.dark .del {
    background-color: #212121 !important;
}

.qty-btn.disabled {
    background: #b9bdd4;
}

.qty {
    min-width: 30px;
    text-align: center;
    font-weight: 700;
    background: #f1f2f6;
    border-radius: 999px;
    padding: 0.15rem 0.4rem;
}

.line-right {
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.price {
    font-weight: 700;
    min-width: 64px;
    text-align: right;
}

.del {
    border: 0;
    background: #ffeded;
    color: #c0392b;
    width: 30px;
    height: 30px;
    border-radius: 8px;
}

.totals {
    padding: 0.75rem 0 0.25rem;
}

.trow {
    display: flex;
    justify-content: space-between;
    padding: 0.25rem 0;
    color: #4b5563;
}

.trow.total {
    border-top: 1px solid #eef0f6;
    margin-top: 0.25rem;
    padding-top: 0.6rem;
    color: #181818;
    font-size: 16px;
    font-weight: 800;
}

.cart-footer {
    background: #f7f8ff;
    padding: 0.75rem;
    display: flex;
    gap: 0.6rem;
    border-bottom-left-radius: 1rem;
    border-bottom-right-radius: 1rem;
}

.btn-clear {
    flex: 1;
    border: 0;
    background: #eef1ff;
    color: #1b1670;
    font-weight: 700;
    padding: 0.6rem;
    border-radius: 999px;
}

.dark .btn-clear {
    flex: 1;
    border: 0;
    background-color: #4b5563;
    color: #fff;
    font-weight: 700;
    padding: 0.6rem;
    border-radius: 999px;
}

.btn-place {
    flex: 1;
    border: 0;
    background: #1b1670;
    color: #fff;
    font-weight: 800;
    padding: 0.6rem;
    border-radius: 999px;
}

/* ========== Chips & Qty in Modal ========== */
.chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
}

.chip {
    font-size: 0.75rem;
    padding: 0.25rem 0.55rem;
    border-radius: 999px;
    background: #f5f6fb;
    border: 1px solid #eceef7;
}

.dark .chip {
    font-size: 0.75rem;
    padding: 0.25rem 0.55rem;
    border-radius: 999px;
    background: #181818;
    border: 1px solid #eceef7;
}

.chip-green {
    background: #e9f8ef;
    border-color: #d2f1de;
}

.chip-blue {
    background: #e8f3ff;
    border-color: #d2e6ff;
}

.chip-purple {
    background: #f1e9ff;
    border-color: #e1d2ff;
}

.chip-orange {
    background: #fff3e6;
    border-color: #ffe1bf;
}

.chip-red {
    background: #ffe9ea;
    border-color: #ffd3d6;
}

.chip-teal {
    background: #e8fffb;
    border-color: #c9f4ee;
}

.qty-group {
    display: inline-flex;
    /* border: 1px solid #d0cfd7; */
    border-radius: 12px;
    overflow: hidden;
}

.qty-box {
    min-width: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #1b1670;
    color: #fff;
    font-weight: 800;
}

.qty-group .qty-btn {
    width: 38px;
    height: 38px;
}

/* ========== Responsive ========== */
@media (max-width: 1199.98px) {
    .item-title {
        font-size: 0.92rem;
    }
}

@media (max-width: 991.98px) {
    .cart-lines {
        max-height: 260px;
    }

    .search-wrap {
        width: 100%;
        margin-top: 0.4rem;
    }
}

@media (max-width: 575.98px) {
    .cat-tile {
        padding: 1.5rem 1rem;
    }

    .cat-name {
        font-size: 0.9rem;
    }

    .search-input {
        width: 100%;
    }
}




/* Variant dropdown styling */
.variant-select {
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
    cursor: pointer;
}

.variant-select:hover {
    border-color: #1B1670;
}

.variant-select:focus {
    border-color: #1B1670;
    box-shadow: 0 0 0 3px rgba(27, 22, 112, 0.1);
}

.dark .variant-select {
    background-color: #212121;
    color: #fff;
    border-color: #4b5563;
}




/* ========== PrimeVue MultiSelect Styling ========== */
.addon-multiselect {
    font-size: 0.875rem;
}

.addon-multiselect :deep(.p-multiselect) {
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.addon-multiselect :deep(.p-multiselect:hover) {
    border-color: #1B1670;
}

.addon-multiselect :deep(.p-multiselect.p-focus) {
    border-color: #1B1670;
    box-shadow: 0 0 0 3px rgba(27, 22, 112, 0.1);
}

.addon-multiselect :deep(.p-multiselect-label) {
    padding: 0.5rem 0.75rem;
}

.addon-multiselect :deep(.p-multiselect-panel) {
    border-radius: 8px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.addon-multiselect :deep(.p-multiselect-item) {
    padding: 0.5rem 0.75rem;
    transition: background-color 0.2s ease;
}

.addon-multiselect :deep(.p-multiselect-item:hover) {
    background-color: #f3f4f6;
}

.addon-multiselect :deep(.p-multiselect-item.p-highlight) {
    background-color: #1B1670;
    color: white;
}

/* Dark mode for MultiSelect */
.dark .addon-multiselect :deep(.p-multiselect) {
    background-color: #212121;
    border-color: #4b5563;
    color: #fff;
}

.dark .addon-multiselect :deep(.p-multiselect-panel) {
    background-color: #212121;
    border-color: #4b5563;
}

.dark .addon-multiselect :deep(.p-multiselect-item) {
    color: #fff;
}

.dark .addon-multiselect :deep(.p-multiselect-item:hover) {
    background-color: #374151;
}

.dark .addon-multiselect :deep(.p-multiselect-item.p-highlight) {
    background-color: #1B1670;
    color: white;
}

/* Addon badges in cart */
.addons-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    margin-top: 0.25rem;
}

.addons-list .badge {
    font-weight: 500;
    padding: 0.2rem 0.4rem;
    line-height: 1.2;
}
</style>
