<script setup>
import Master from "@/Layouts/Master.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { ref, computed, onMounted, nextTick, watch, onUnmounted } from "vue";
import { toast } from "vue3-toastify";
import ConfirmOrderModal from "./ConfirmOrderModal.vue";
import ReceiptModal from "./ReceiptModal.vue";
import PromoModal from "./PromoModal.vue";
import KotModal from "./KotModal.vue";
import { useFormatters } from "@/composables/useFormatters";
import PosOrdersModal from "./PosOrdersModal.vue";
import FilterModal from "@/Components/FilterModal.vue";
import MultiSelect from 'primevue/multiselect';

import Accordion from 'primevue/accordion';
import AccordionPanel from 'primevue/accordionpanel';
import AccordionHeader from 'primevue/accordionheader';
import AccordionContent from 'primevue/accordioncontent';


const { formatMoney, formatCurrencySymbol, formatNumber, dateFmt } = useFormatters();

const props = defineProps(["client_secret", "order_code"]);

import { Popover } from 'bootstrap';

onMounted(() => {
    nextTick(() => {
        document.querySelectorAll('[data-bs-toggle="popover"]').forEach((el) => {
            new Popover(el);
        });
    });
});

/* ----------------------------
   Categories
-----------------------------*/
const menuCategories = ref([]);
const menuCategoriesLoading = ref(true);

const categorySearchQuery = ref("");
const categorySearchKey = ref(Date.now());
const categoryInputId = `category-search-${Math.random().toString(36).substr(2, 9)}`;
const isCategorySearchReady = ref(false);
const currentAddonGroupIndex = ref(0);

const currentAddonGroup = computed(() => {
    if (!selectedItem.value?.addon_groups || selectedItem.value.addon_groups.length === 0) {
        return null;
    }
    return selectedItem.value.addon_groups[currentAddonGroupIndex.value];
});

// Navigate to next addon group
const nextAddonGroup = () => {
    if (currentAddonGroupIndex.value < selectedItem.value?.addon_groups?.length - 1) {
        currentAddonGroupIndex.value++;
    }
};

// Navigate to previous addon group
const previousAddonGroup = () => {
    if (currentAddonGroupIndex.value > 0) {
        currentAddonGroupIndex.value--;
    }
};

// Skip to review step directly
const skipToReview = () => {
    currentStep.value = finalStep.value;
};

// Reset addon group index when modal opens or item changes
const resetAddonGroupIndex = () => {
    currentAddonGroupIndex.value = 0;
};


const filteredCategories = computed(() => {
    const q = categorySearchQuery.value.trim().toLowerCase();
    if (!q) return categoriesWithMenus.value;

    return categoriesWithMenus.value.filter(category =>
        category.name.toLowerCase().includes(q)
    );
});

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
    } catch (error) {
        console.error("Error fetching inventory:", error);
    }
};

/* ----------------------------
   Products by Category
-----------------------------*/

const productsByCat = computed(() => {
    const grouped = {};

    //  Step 1: Add all menu items
    menuItems.value.forEach((item) => {
        const catId = item.category?.id || "uncategorized";
        const catName = item.category?.name || "Uncategorized";
        if (!grouped[catId]) grouped[catId] = [];

        const product = {
            id: item.id,
            title: item.name,
            img: item.image_url || "/assets/img/default.png",
            stock: 0,
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
            is_saleable: item.is_saleable,
            resale_type: item.resale_type,
            resale_value: item.resale_value,
            isDeal: false, // ⭐ Regular item
        };

        grouped[catId].push(product);
    });

    //  Step 2: Add deals to their categories
    deals.value.forEach((deal) => {
        // IMPORTANT: Your deal needs a category_id field
        // If you don't have it, you need to add it to your backend
        const catId = deal.category_id || "uncategorized";

        if (!grouped[catId]) grouped[catId] = [];

        const dealProduct = {
            id: deal.id,
            dealId: deal.id,
            title: deal.title,
            img: deal.img,
            stock: 999999,
            price: Number(deal.price),
            label_color: "#dc3545", // Red for deals
            family: "Deal",
            description: deal.description,
            nutrition: {},
            tags: [],
            allergies: [],
            ingredients: [],
            variants: [],
            addon_groups: [],
            is_saleable: false,
            resale_type: null,
            resale_value: null,
            isDeal: true,
            category_id: deal.category_id,
            menu_items: deal.menu_items || [],
        };

        grouped[catId].push(dealProduct);
    });

    return grouped;
});

const selectedCardVariant = ref({});

//  Initialize variants when products load
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

//  Get selected variant price for display
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

//  Get selected variant object
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

//  Get total addon price for a product
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

//  Get selected addons for a product (format for backend)
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

// Flatten all addons from all groups into one array
const getAllAddonsForProduct = (product) => {
    if (!product.addon_groups || product.addon_groups.length === 0) return [];

    const allAddons = [];
    product.addon_groups.forEach(group => {
        if (group.addons && group.addons.length > 0) {
            group.addons.forEach(addon => {
                allAddons.push({
                    ...addon,
                    group_id: group.group_id,
                    group_name: group.group_name,
                    max_select: group.max_select
                });
            });
        }
    });

    return allAddons;
};


// Get all selected addons for a product (flatten from all groups)
const getAllSelectedAddonsForProduct = (product) => {
    const productKey = product.id;
    if (!selectedCardAddons.value[productKey]) return [];

    const allSelected = [];
    Object.values(selectedCardAddons.value[productKey]).forEach(addons => {
        allSelected.push(...addons);
    });

    return allSelected;
};

// Handle addon changes for unified multiselect
const handleAllAddonsChange = (product, selectedAddons) => {
    const productKey = product.id;

    // Group the selected addons back by their group_id
    const groupedAddons = {};

    selectedAddons.forEach(addon => {
        if (!groupedAddons[addon.group_id]) {
            groupedAddons[addon.group_id] = [];
        }
        groupedAddons[addon.group_id].push(addon);
    });

    // Check max_select constraints for each group
    product.addon_groups.forEach(group => {
        const groupAddons = groupedAddons[group.group_id] || [];

        if (group.max_select > 0 && groupAddons.length > group.max_select) {
            toast.warning(`You can only select up to ${group.max_select} items from ${group.group_name}`);
            // Keep only the first max_select items
            groupedAddons[group.group_id] = groupAddons.slice(0, group.max_select);
        }
    });

    // Update the selectedCardAddons
    selectedCardAddons.value[productKey] = groupedAddons;
};

//  total price (variant + addons)
const getTotalPrice = (product) => {
    const variantPrice = getSelectedVariantPrice(product);
    const addonsPrice = getAddonsPrice(product);
    return variantPrice + addonsPrice;
};
// ================================================
const modalRemovedIngredients = ref([]);
// Get modal ingredients based on selected variant
const getModalIngredients = () => {
    if (!selectedItem.value) return [];

    const variant = getModalSelectedVariant();
    if (variant && variant.ingredients && variant.ingredients.length > 0) {
        return variant.ingredients;
    }

    return selectedItem.value.ingredients || [];
};

// Check if an ingredient is removed
const isIngredientRemoved = (ingredientId) => {
    return modalRemovedIngredients.value.includes(ingredientId);
};

// Toggle ingredient removal
const toggleIngredient = (ingredientId) => {
    const index = modalRemovedIngredients.value.indexOf(ingredientId);
    if (index > -1) {
        modalRemovedIngredients.value.splice(index, 1);
        toast.info('Ingredient added back');
    } else {
        modalRemovedIngredients.value.push(ingredientId);
        toast.info('Ingredient removed');
    }
};



// Get formatted text for removed ingredients
const getRemovedIngredientsText = () => {
    if (modalRemovedIngredients.value.length === 0) return null;

    const ingredients = getModalIngredients();
    const removedNames = modalRemovedIngredients.value
        .map(id => {
            const ing = ingredients.find(i =>
                i.id === id || i.inventory_item_id === id
            );
            return ing ? (ing.product_name || ing.name) : null;
        })
        .filter(Boolean);

    if (removedNames.length === 0) return null;
    return `No ${removedNames.join(', ')}`;
};

const currentStep = ref(1);

const getStepNumberFor = (stepId) => {
    return visibleSteps.value.find(s => s.id === stepId)?.stepNumber || 0;
};

const goToStep = (stepNumber) => {
    if (stepNumber <= currentStep.value || stepNumber === currentStep.value + 1) {
        currentStep.value = stepNumber;
    }
};
// Stepper computed properties
const hasVariants = computed(() => {
    return selectedItem.value?.variants && selectedItem.value.variants.length > 0;
});

const hasIngredients = computed(() => {
    return getModalIngredients().length > 0;
});

const hasAddons = computed(() => {
    return selectedItem.value?.addon_groups && selectedItem.value.addon_groups.length > 0;
});

const visibleSteps = computed(() => {
    let steps = [];
    let displayOrder = 1; // This is what shows in the circles

    if (hasVariants.value) {
        steps.push({
            id: 'variants',
            name: 'Variant',
            stepNumber: displayOrder,
            actualStep: 1
        });
        displayOrder++;
    }

    if (hasIngredients.value) {
        steps.push({
            id: 'ingredients',
            name: 'Ingredients',
            stepNumber: displayOrder,
            actualStep: 2
        });
        displayOrder++;
    }

    if (hasAddons.value) {
        steps.push({
            id: 'addons',
            name: 'Add-ons',
            stepNumber: displayOrder,
            actualStep: 3
        });
        displayOrder++;
    }

    steps.push({
        id: 'review',
        name: 'Review',
        stepNumber: displayOrder,
        actualStep: 4
    });

    return steps;
});

const finalStep = computed(() => {
    return visibleSteps.value[visibleSteps.value.length - 1]?.stepNumber || 1;
});

const stepProgressWidth = computed(() => {
    if (currentStep.value === 1) return '0%';

    const totalSteps = visibleSteps.value.length;
    const circleWidth = 28;
    const progressRatio = (currentStep.value - 1) / (totalSteps - 1);
    const widthPercentage = progressRatio * 100;

    return `calc(${widthPercentage}% - ${14 - (progressRatio * 28)}px)`;
});
// Step navigation methods
const getStepCircleClass = (stepNumber) => {
    if (stepNumber < currentStep.value) {
        return 'stepper-circle-completed';
    } else if (stepNumber === currentStep.value) {
        return 'stepper-circle-active';
    }
    return 'stepper-circle-inactive';
};

const initializeStep = () => {
    currentStep.value = visibleSteps.value[0]?.stepNumber || 1;
};

const nextStep = () => {
    const currentIndex = visibleSteps.value.findIndex(s => s.stepNumber === currentStep.value);
    if (currentIndex < visibleSteps.value.length - 1) {
        currentStep.value = visibleSteps.value[currentIndex + 1].stepNumber;
    }
};

const previousStep = () => {
    const currentIndex = visibleSteps.value.findIndex(s => s.stepNumber === currentStep.value);
    if (currentIndex > 0) {
        currentStep.value = visibleSteps.value[currentIndex - 1].stepNumber;
    }
};

const selectVariant = (variantId) => {
    modalSelectedVariant.value = variantId;
    onModalVariantChange();
};

// Helper methods for review step
const getSelectedVariantName = () => {
    if (!hasVariants.value) return '';
    const variant = selectedItem.value?.variants?.find(v => v.id === modalSelectedVariant.value);
    return variant?.name || '';
};

const getSelectedAddonsText = () => {
    const allAddons = [];
    Object.values(modalSelectedAddons.value).forEach(addons => {
        addons.forEach(addon => {
            const qty = addon.quantity > 1 ? ` x${addon.quantity}` : '';
            allAddons.push(`${addon.name}${qty} (+${formatCurrencySymbol(addon.price * (addon.quantity || 1))})`);
        });
    });
    return allAddons.join(', ');
};

const openDetailsModal = async (item) => {
    selectedItem.value = item;
    modalNote.value = "";
    modalItemKitchenNote.value = "";
    modalSelectedVariant.value = null;
    modalSelectedAddons.value = {};
    currentAddonGroupIndex.value = 0;
    modalRemovedIngredients.value = [];

    currentStep.value = 1;

    // Set default variant if exists
    if (item.variants && item.variants.length > 0) {
        modalSelectedVariant.value = item.variants[0].id;
    }

    const variant = item.variants && item.variants.length > 0 ? item.variants[0] : null;
    const variantId = variant ? variant.id : null;
    const variantIngredients = getVariantIngredients(item, variantId);
    const availableToAdd = calculateAvailableStock(item, variantId, variantIngredients);
    modalQty.value = availableToAdd > 0 ? 1 : 0;
    modalRemovedIngredients.value = [];
    const modalEl = document.getElementById("chooseItem");
    const modal = new bootstrap.Modal(modalEl);

    // Wait for modal to be shown before initializing PrimeVue components
    modalEl.addEventListener('shown.bs.modal', async () => {
        await nextTick();
    }, { once: true });

    modal.show();
};

// const calculateAvailableStock = (product, variantId, variantIngredients) => {
//     const activeIngredients = variantIngredients.filter(ing =>
//         !modalRemovedIngredients.value.includes(ing.id || ing.inventory_item_id)
//     );

//     if (!activeIngredients || activeIngredients.length === 0) return 999999;

//     const ingredientUsage = {};
//     for (const item of orderItems.value) {
//         const itemIngredients = getVariantIngredients(item, item.variant_id);

//         //  Filter out ingredients that were removed from this cart item
//         const activeItemIngredients = itemIngredients.filter(ing => {
//             if (!item.removed_ingredients || item.removed_ingredients.length === 0) {
//                 return true;
//             }
//             return !item.removed_ingredients.includes(ing.id || ing.inventory_item_id);
//         });

//         activeItemIngredients.forEach(ing => {
//             const id = ing.inventory_item_id;
//             const stock = Number(ing.inventory_stock ?? ing.inventory_item?.stock ?? 0);

//             if (!ingredientUsage[id]) {
//                 ingredientUsage[id] = { totalStock: stock, used: 0 };
//             }

//             const required = Number(ing.quantity ?? ing.qty ?? 1) * item.qty;
//             ingredientUsage[id].used += required;
//         });
//     }
//     let maxPossible = 999999;

//     for (const ing of activeIngredients) {
//         const id = ing.inventory_item_id;
//         const stock = Number(ing.inventory_stock ?? ing.inventory_item?.stock ?? 0);
//         const requiredPerItem = Number(ing.quantity ?? ing.qty ?? 1);

//         if (!ingredientUsage[id]) {
//             const possible = Math.floor(stock / requiredPerItem);
//             maxPossible = Math.min(maxPossible, possible);
//         } else {
//             const available = ingredientUsage[id].totalStock - ingredientUsage[id].used;
//             const possible = Math.floor(available / requiredPerItem);
//             maxPossible = Math.min(maxPossible, possible);
//         }
//     }

//     return maxPossible;
// };


const calculateAvailableStock = (product, variantId, variantIngredients) => {
    // If inventory tracking is disabled, return unlimited stock
    if (!enableInventoryTracking.value) {
        return 999999;
    }

    const activeIngredients = variantIngredients.filter(ing =>
        !modalRemovedIngredients.value.includes(ing.id || ing.inventory_item_id)
    );

    if (!activeIngredients || activeIngredients.length === 0) return 999999;

    const ingredientUsage = {};
    for (const item of orderItems.value) {
        const itemIngredients = getVariantIngredients(item, item.variant_id);

        //  Filter out ingredients that were removed from this cart item
        const activeItemIngredients = itemIngredients.filter(ing => {
            if (!item.removed_ingredients || item.removed_ingredients.length === 0) {
                return true;
            }
            return !item.removed_ingredients.includes(ing.id || ing.inventory_item_id);
        });

        activeItemIngredients.forEach(ing => {
            const id = ing.inventory_item_id;
            const stock = Number(ing.inventory_stock ?? ing.inventory_item?.stock ?? 0);

            if (!ingredientUsage[id]) {
                ingredientUsage[id] = { totalStock: stock, used: 0 };
            }

            const required = Number(ing.quantity ?? ing.qty ?? 1) * item.qty;
            ingredientUsage[id].used += required;
        });
    }
    let maxPossible = 999999;

    for (const ing of activeIngredients) {
        const id = ing.inventory_item_id;
        const stock = Number(ing.inventory_stock ?? ing.inventory_item?.stock ?? 0);
        const requiredPerItem = Number(ing.quantity ?? ing.qty ?? 1);

        if (!ingredientUsage[id]) {
            const possible = Math.floor(stock / requiredPerItem);
            maxPossible = Math.min(maxPossible, possible);
        } else {
            const available = ingredientUsage[id].totalStock - ingredientUsage[id].used;
            const possible = Math.floor(available / requiredPerItem);
            maxPossible = Math.min(maxPossible, possible);
        }
    }

    return maxPossible;
};

// Handle Modal Variant Change
const onModalVariantChange = () => {
    //  Recheck stock when variant changes
    const variant = getModalSelectedVariant();
    const variantId = variant ? variant.id : null;
    const variantIngredients = getVariantIngredients(selectedItem.value, variantId);

    const availableToAdd = calculateAvailableStock(selectedItem.value, variantId, variantIngredients);

    if (availableToAdd <= 0) {
        toast.error(`No stock available for this variant. Please remove items from cart first.`);
        modalQty.value = 0;
        return;
    }
    modalQty.value = Math.min(1, availableToAdd);
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
            total += parseFloat(addon.price || 0) * (addon.quantity || 1);
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


// Get stock for a specific product (based on selected variant)
// const getProductStock = (product) => {
//     if (!product) return 0;

//     const variant = getSelectedVariant(product);
//     const variantId = variant ? variant.id : null;
//     const ingredients = getVariantIngredients(product, variantId);

//     if (!ingredients.length) return 999999; // No ingredients = unlimited

//     let menuStock = Infinity;

//     ingredients.forEach((ing) => {
//         const required = Number(ing.quantity ?? ing.qty ?? 1);
//         const inventoryStock = Number(ing.inventory_stock ?? ing.inventory_item?.stock ?? 0);
//         if (required <= 0) return;
//         const possible = Math.floor(inventoryStock / required);
//         menuStock = Math.min(menuStock, possible);
//     });

//     return menuStock === Infinity ? 0 : menuStock;
// };

const getProductStock = (product) => {
    if (!product) return 0;

    // If inventory tracking is disabled, return unlimited stock
    if (!enableInventoryTracking.value) {
        return 999999;
    }

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
const phoneNumber = ref(" ");
const deliveryLocation = ref(" ");
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

            //  Check for saved order type from localStorage
            const savedOrderType = localStorage.getItem("quickOrderType");
            if (savedOrderType) {
                const matchingType = orderTypes.value.find(
                    type => type.toLowerCase().replace(/_/g, ' ') === savedOrderType.toLowerCase().replace(/_/g, ' ')
                );

                if (matchingType) {
                    orderType.value = matchingType;
                } else {
                    orderType.value = orderTypes.value[0]; // fallback to first type
                }
            } else {
                orderType.value = orderTypes.value[0];
            }
        }
    } catch (error) {
        console.error("Error fetching profile tables:", error);
    }
};


const visibleProducts = computed(
    () => productsByCat.value[activeCat.value] ?? []
);


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
        products = [...products];

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

const filtersJustApplied = ref(false);

const handleApplyFilters = (appliedFilters) => {
    filters.value = { ...appliedFilters };
    filtersJustApplied.value = true;
};
const counter = ref('');
const initializeWalkInCounter = () => {
    const storedCounter = localStorage.getItem('pos_walkin_counter');
    if (storedCounter) {
        counter.value = parseInt(storedCounter);
    } else {
        counter.value = 1;
        localStorage.setItem('pos_walkin_counter', '1');
    }
};

const generateCustomerName = () => {
    const number = String(counter.value).padStart(3, '0');
    return `Walk In-${number}`;
};

const incrementWalkInCounter = () => {
    counter.value++;
    localStorage.setItem('pos_walkin_counter', counter.value.toString());
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

// ========================================
// Fixed: Increment cart item (in cart sidebar)
// ========================================
// const incCart = async (i) => {
//     const it = orderItems.value[i];
//     if (!it) return;

//     if ((it.stock ?? 0) <= 0) {
//         toast.error("Item out of stock.");
//         return;
//     }

//     if (!canIncCartItem(it)) {
//         const variantText = it.variant_name ? ` (${it.variant_name})` : '';
//         toast.error(`Not enough ingredients for "${it.title}${variantText}".`);
//         it.outOfStock = true;
//         return;
//     }

//     it.outOfStock = false;
//     it.qty++;
//     it.price = it.unit_price * it.qty;

//     // Update total discount
//     if (it.resale_discount_per_item) {
//         it.total_resale_discount = it.resale_discount_per_item * it.qty;
//     }
// };

const incCart = async (i) => {
    const it = orderItems.value[i];
    if (!it) return;

    //  SPECIAL HANDLING FOR DEALS
    if (it.isDeal) {
        // Use the same deal stock check logic as incrementDealQty
        const stockCheck = calculateDealStock(
            {
                id: it.dealId,
                title: it.title,
                menu_items: it.menu_items
            },
            it.qty + 1
        );

        if (!stockCheck.canAdd) {
            console.log('🚨 Missing ingredients for deal (cart increment)');

            pendingIncrementData.value = {
                itemIndex: i,
                item: it,
                newQty: it.qty + 1,
                isDeal: true
            };

            missingIngredients.value = stockCheck.missing;
            showMissingIngredientsModal.value = true;

            toast.warning('Some ingredients are missing. Confirm to proceed.');
            return;
        }

        // Normal deal increment
        it.qty++;
        it.price = it.unit_price * it.qty;
        toast.success('Deal quantity updated');
        return;
    }

    //  REGULAR MENU ITEM HANDLING
    const stockCheck = calculateClientSideStock(it, it.qty + 1);

    if (!stockCheck.canAdd) {
        console.log('🚨 Missing ingredients detected (client-side)');

        pendingIncrementData.value = {
            itemIndex: i,
            item: it,
            newQty: it.qty + 1,
            isDeal: false
        };

        missingIngredients.value = stockCheck.missing;
        showMissingIngredientsModal.value = true;

        toast.warning('Some ingredients are missing. Confirm to proceed.');
        return;
    }

    // Normal increment
    it.outOfStock = false;
    it.qty++;
    it.price = it.unit_price * it.qty;

    if (it.resale_discount_per_item) {
        it.total_resale_discount = it.resale_discount_per_item * it.qty;
    }
};

// const calculateClientSideStock = (item, requestedQty) => {
//     const ingredients = item.ingredients || [];

//     // If no ingredients, unlimited stock
//     if (ingredients.length === 0) {
//         return { canAdd: true, missing: [] };
//     }

//     const missingIngredients = [];

//     // Build ingredient usage map from current cart
//     const ingredientUsage = {};

//     orderItems.value.forEach(cartItem => {
//         const itemIngredients = cartItem.ingredients || [];

//         // Filter out removed ingredients
//         const activeIngredients = itemIngredients.filter(ing => {
//             if (!cartItem.removed_ingredients || cartItem.removed_ingredients.length === 0) {
//                 return true;
//             }
//             return !cartItem.removed_ingredients.includes(ing.id || ing.inventory_item_id);
//         });

//         activeIngredients.forEach(ing => {
//             const id = ing.inventory_item_id;
//             const stock = Number(ing.inventory_stock ?? ing.inventory_item?.stock ?? 0);

//             if (!ingredientUsage[id]) {
//                 ingredientUsage[id] = {
//                     totalStock: stock,
//                     used: 0,
//                     name: ing.product_name || ing.name,
//                     unit: ing.unit || 'unit'
//                 };
//             }

//             const required = Number(ing.quantity ?? ing.qty ?? 1) * cartItem.qty;
//             ingredientUsage[id].used += required;
//         });
//     });

//     // Check if new quantity would exceed stock
//     // Filter out removed ingredients for this item too
//     const activeIngredients = ingredients.filter(ing => {
//         if (!item.removed_ingredients || item.removed_ingredients.length === 0) {
//             return true;
//         }
//         return !item.removed_ingredients.includes(ing.id || ing.inventory_item_id);
//     });

//     activeIngredients.forEach(ing => {
//         const id = ing.inventory_item_id;
//         const required = Number(ing.quantity ?? ing.qty ?? 1) * requestedQty;
//         const stock = Number(ing.inventory_stock ?? ing.inventory_item?.stock ?? 0);

//         if (!ingredientUsage[id]) {
//             // This ingredient isn't used by other cart items yet
//             if (stock < required) {
//                 missingIngredients.push({
//                     item_id: item.id,
//                     item_title: item.title,
//                     variant_id: item.variant_id || null,
//                     variant_name: item.variant_name || null,
//                     inventory_item_id: id,
//                     inventory_item_name: ing.product_name || ing.name,
//                     required_quantity: required,
//                     available_quantity: stock,
//                     shortage_quantity: required - stock,
//                     unit: ing.unit || 'unit',
//                     order_quantity: requestedQty
//                 });
//             }
//         } else {
//             // Ingredient is already used by other cart items
//             const available = ingredientUsage[id].totalStock - ingredientUsage[id].used;

//             // If this exact item is already in cart, add back its current usage
//             const existingItem = orderItems.value.find(ci =>
//                 ci.id === item.id &&
//                 ci.variant_id === item.variant_id &&
//                 JSON.stringify(ci.addons?.map(a => a.id).sort()) === JSON.stringify(item.addons?.map(a => a.id).sort())
//             );

//             if (existingItem) {
//                 const currentUsage = Number(ing.quantity ?? ing.qty ?? 1) * existingItem.qty;
//                 const availableForThisItem = available + currentUsage;

//                 if (availableForThisItem < required) {
//                     missingIngredients.push({
//                         item_id: item.id,
//                         item_title: item.title,
//                         variant_id: item.variant_id || null,
//                         variant_name: item.variant_name || null,
//                         inventory_item_id: id,
//                         inventory_item_name: ing.product_name || ing.name,
//                         required_quantity: required,
//                         available_quantity: availableForThisItem,
//                         shortage_quantity: required - availableForThisItem,
//                         unit: ing.unit || 'unit',
//                         order_quantity: requestedQty
//                     });
//                 }
//             } else {
//                 if (available < required) {
//                     missingIngredients.push({
//                         item_id: item.id,
//                         item_title: item.title,
//                         variant_id: item.variant_id || null,
//                         variant_name: item.variant_name || null,
//                         inventory_item_id: id,
//                         inventory_item_name: ing.product_name || ing.name,
//                         required_quantity: required,
//                         available_quantity: available,
//                         shortage_quantity: required - available,
//                         unit: ing.unit || 'unit',
//                         order_quantity: requestedQty
//                     });
//                 }
//             }
//         }
//     });

//     return {
//         canAdd: missingIngredients.length === 0,
//         missing: missingIngredients
//     };
// };


const calculateClientSideStock = (item, requestedQty) => {
    // If inventory tracking is disabled, allow unlimited
    if (!enableInventoryTracking.value) {
        return { canAdd: true, missing: [] };
    }

    const ingredients = item.ingredients || [];

    // If no ingredients, unlimited stock
    if (ingredients.length === 0) {
        return { canAdd: true, missing: [] };
    }

    const missingIngredients = [];

    // Build ingredient usage map from current cart
    const ingredientUsage = {};

    orderItems.value.forEach(cartItem => {
        const itemIngredients = cartItem.ingredients || [];

        // Filter out removed ingredients
        const activeIngredients = itemIngredients.filter(ing => {
            if (!cartItem.removed_ingredients || cartItem.removed_ingredients.length === 0) {
                return true;
            }
            return !cartItem.removed_ingredients.includes(ing.id || ing.inventory_item_id);
        });

        activeIngredients.forEach(ing => {
            const id = ing.inventory_item_id;
            const stock = Number(ing.inventory_stock ?? ing.inventory_item?.stock ?? 0);

            if (!ingredientUsage[id]) {
                ingredientUsage[id] = {
                    totalStock: stock,
                    used: 0,
                    name: ing.product_name || ing.name,
                    unit: ing.unit || 'unit'
                };
            }

            const required = Number(ing.quantity ?? ing.qty ?? 1) * cartItem.qty;
            ingredientUsage[id].used += required;
        });
    });

    // Check if new quantity would exceed stock
    // Filter out removed ingredients for this item too
    const activeIngredients = ingredients.filter(ing => {
        if (!item.removed_ingredients || item.removed_ingredients.length === 0) {
            return true;
        }
        return !item.removed_ingredients.includes(ing.id || ing.inventory_item_id);
    });

    activeIngredients.forEach(ing => {
        const id = ing.inventory_item_id;
        const required = Number(ing.quantity ?? ing.qty ?? 1) * requestedQty;
        const stock = Number(ing.inventory_stock ?? ing.inventory_item?.stock ?? 0);

        if (!ingredientUsage[id]) {
            // This ingredient isn't used by other cart items yet
            if (stock < required) {
                missingIngredients.push({
                    item_id: item.id,
                    item_title: item.title,
                    variant_id: item.variant_id || null,
                    variant_name: item.variant_name || null,
                    inventory_item_id: id,
                    inventory_item_name: ing.product_name || ing.name,
                    required_quantity: required,
                    available_quantity: stock,
                    shortage_quantity: required - stock,
                    unit: ing.unit || 'unit',
                    order_quantity: requestedQty
                });
            }
        } else {
            // Ingredient is already used by other cart items
            const available = ingredientUsage[id].totalStock - ingredientUsage[id].used;

            // If this exact item is already in cart, add back its current usage
            const existingItem = orderItems.value.find(ci =>
                ci.id === item.id &&
                ci.variant_id === item.variant_id &&
                JSON.stringify(ci.addons?.map(a => a.id).sort()) === JSON.stringify(item.addons?.map(a => a.id).sort())
            );

            if (existingItem) {
                const currentUsage = Number(ing.quantity ?? ing.qty ?? 1) * existingItem.qty;
                const availableForThisItem = available + currentUsage;

                if (availableForThisItem < required) {
                    missingIngredients.push({
                        item_id: item.id,
                        item_title: item.title,
                        variant_id: item.variant_id || null,
                        variant_name: item.variant_name || null,
                        inventory_item_id: id,
                        inventory_item_name: ing.product_name || ing.name,
                        required_quantity: required,
                        available_quantity: availableForThisItem,
                        shortage_quantity: required - availableForThisItem,
                        unit: ing.unit || 'unit',
                        order_quantity: requestedQty
                    });
                }
            } else {
                if (available < required) {
                    missingIngredients.push({
                        item_id: item.id,
                        item_title: item.title,
                        variant_id: item.variant_id || null,
                        variant_name: item.variant_name || null,
                        inventory_item_id: id,
                        inventory_item_name: ing.product_name || ing.name,
                        required_quantity: required,
                        available_quantity: available,
                        shortage_quantity: required - available,
                        unit: ing.unit || 'unit',
                        order_quantity: requestedQty
                    });
                }
            }
        }
    });

    return {
        canAdd: missingIngredients.length === 0,
        missing: missingIngredients
    };
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

    // Update total discount
    if (it.resale_discount_per_item) {
        it.total_resale_discount = it.resale_discount_per_item * it.qty;
    }

    it.outOfStock = false;
};

const removeCart = (index) => {
    const removedItem = orderItems.value[index];
    orderItems.value.splice(index, 1);
    if (orderItems.value.length === 0) {
        selectedDiscounts.value = [];
        selectedPromos.value = [];
        pendingDiscountApprovals.value = [];
        stopApprovalPolling();
        return;
    }
    if (selectedPromos.value.length > 0) {
        selectedPromos.value = selectedPromos.value.filter((promo) => {
            if (!promo.menu_items || promo.menu_items.length === 0) {
                return true;
            }

            const promoMenuIds = promo.menu_items.map(item => item.id);

            const stillApplicable = orderItems.value.some(cartItem =>
                promoMenuIds.includes(cartItem.id)
            );

            return stillApplicable;
        });
    }
    const discountsToKeep = [];

    selectedDiscounts.value.forEach(discount => {
        if (discount.min_purchase && subTotal.value < discount.min_purchase) {

            toast.info(
                `Discount "${discount.name}" removed — subtotal is lower than minimum required (${formatCurrencySymbol(discount.min_purchase)}).`
            );
        } else {
            discountsToKeep.push(discount);
        }
    });

    selectedDiscounts.value = discountsToKeep;
};

const subTotal = computed(() =>
    orderItems.value.reduce((s, i) => s + i.price, 0)
);

const deliveryCharges = computed(() => {
    if (orderType.value !== "Delivery") return 0;

    const config = page.props.onboarding.tax_and_vat;
    if (!config.has_delivery_charges) return 0;
    if (config.delivery_charge_flat) {
        return parseFloat(config.delivery_charge_flat);
    }
    if (config.delivery_charge_percentage) {
        return (subTotal.value * parseFloat(config.delivery_charge_percentage)) / 100;
    }

    return 0;
});


const serviceCharges = computed(() => {
    if (orderItems.value.length === 0) {
        return 0;
    }
    const config = page.props.onboarding.tax_and_vat;
    if (!config.has_service_charges) return 0;
    if (config.service_charge_flat) {
        return parseFloat(config.service_charge_flat);
    }
    if (config.service_charge_percentage) {
        return (subTotal.value * parseFloat(config.service_charge_percentage)) / 100;
    }

    return 0;
});
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

//  Add this reactive variable at the top with your other modal variables
const modalItemKitchenNote = ref('');

// const confirmAdd = async () => {
//     if (!selectedItem.value) return;

//     const variant = getModalSelectedVariant();
//     const variantId = variant ? variant.id : null;
//     const variantText = variant ? ` (${variant.name})` : '';

//     if (modalQty.value <= 0) {
//         toast.error(`No stock available for "${selectedItem.value.title}${variantText}". Please remove some from cart first.`);
//         return;
//     }

//     const variantName = variant ? variant.name : null;
//     const variantPrice = variant
//         ? parseFloat(variant.price)  
//         : parseFloat(selectedItem.value.price); 

//     const selectedAddons = getModalSelectedAddons();
//     const addonsPrice = getModalAddonsPrice();
//     const totalItemPrice = variantPrice + addonsPrice;
//     const resaleDiscountPerItem = variant
//         ? calculateResalePrice(variant, true)
//         : calculateResalePrice(selectedItem.value, false);

//     const variantIngredients = getVariantIngredients(selectedItem.value, variantId);
//     const availableToAdd = calculateAvailableStock(selectedItem.value, variantId, variantIngredients);

//     if (modalQty.value > availableToAdd) {
//         if (availableToAdd <= 0) {
//             toast.error(`No more stock available for "${selectedItem.value.title}${variantText}". Please remove some from cart first.`);
//         } else {
//             toast.error(`Only ${availableToAdd} item(s) available. Please reduce quantity or remove items from cart.`);
//             modalQty.value = availableToAdd;
//         }
//         return;
//     }
//     const removedIngredientsText = getRemovedIngredientsText();
//     const ingredientStock = {};
//     for (const item of orderItems.value) {
//         const itemIngredients = getVariantIngredients(item, item.variant_id);
//         itemIngredients.forEach(ing => {
//             const ingredientId = ing.inventory_item_id;
//             if (!ingredientStock[ingredientId]) {
//                 ingredientStock[ingredientId] = parseFloat(ing.inventory_stock);
//             }
//             ingredientStock[ingredientId] -= parseFloat(ing.quantity) * item.qty;
//         });
//     }

//     if (variantIngredients.length > 0) {
//         for (const ing of variantIngredients) {
//             const ingredientId = ing.inventory_item_id;
//             const availableStock = ingredientStock[ingredientId] ?? parseFloat(ing.inventory_stock);
//             const requiredQty = parseFloat(ing.quantity) * modalQty.value;

//             if (availableStock < requiredQty) {
//                 toast.error(`Not enough stock for "${selectedItem.value.title}${variantText}".`);
//                 return;
//             }
//         }
//     }

//     try {
//         const addonIds = selectedAddons.map(a => a.id).sort((a, b) => a - b).join('-');
//         const menuStock = variantIngredients.length > 0
//             ? calculateStockForIngredients(variantIngredients)
//             : 999999;

//         const idx = orderItems.value.findIndex((i) => {
//             if (i.variant_id !== variantId) return false;
//             const itemAddonIds = (i.addons || [])
//                 .map(a => a.id)
//                 .sort((a, b) => a - b)
//                 .join('-');
//             return i.id === selectedItem.value.id && itemAddonIds === addonIds;
//         });

//         if (idx >= 0) {
//             orderItems.value[idx].qty += modalQty.value;
//             orderItems.value[idx].price = orderItems.value[idx].unit_price * orderItems.value[idx].qty;
//             orderItems.value[idx].total_resale_discount = resaleDiscountPerItem * orderItems.value[idx].qty;
//             if (modalItemKitchenNote.value && modalItemKitchenNote.value.trim()) {
//                 const existingNote = orderItems.value[idx].item_kitchen_note || '';
//                 const newNote = modalItemKitchenNote.value.trim();
//                 const noteParts = [existingNote, newNote, removedIngredientsText]
//                     .filter(Boolean)
//                     .filter((v, i, a) => a.indexOf(v) === i);

//                 orderItems.value[idx].item_kitchen_note = noteParts.join('; ');
//             } else if (removedIngredientsText) {
//                 const existingNote = orderItems.value[idx].item_kitchen_note || '';
//                 orderItems.value[idx].item_kitchen_note = [existingNote, removedIngredientsText]
//                     .filter(Boolean)
//                     .join('; ');
//             }
//             if (modalRemovedIngredients.value.length > 0) {
//                 orderItems.value[idx].removed_ingredients = [...modalRemovedIngredients.value];
//             }

//             toast.success(`Quantity updated to ${orderItems.value[idx].qty}`);
//         } else {
//             const finalKitchenNote = [modalItemKitchenNote.value.trim(), removedIngredientsText]
//                 .filter(Boolean)
//                 .join('. ');

//             orderItems.value.push({
//                 id: selectedItem.value.id,
//                 title: selectedItem.value.title,
//                 img: selectedItem.value.img,
//                 price: totalItemPrice * modalQty.value, 
//                 unit_price: Number(totalItemPrice),
//                 qty: modalQty.value,
//                 note: modalNote.value || "",
//                 item_kitchen_note: finalKitchenNote, 
//                 stock: menuStock,
//                 ingredients: variantIngredients,
//                 variant_id: variantId,
//                 variant_name: variantName,
//                 addons: selectedAddons,
//                 removed_ingredients: [...modalRemovedIngredients.value], 
//                 resale_discount_per_item: resaleDiscountPerItem,
//                 total_resale_discount: resaleDiscountPerItem * modalQty.value,
//             });

//             toast.success(`${selectedItem.value.title} added to cart`);
//         }

//         await openPromoModal(selectedItem.value);

//         const modal = bootstrap.Modal.getInstance(document.getElementById('chooseItem'));
//         modal.hide();
//         modalQty.value = 0;
//         modalNote.value = "";
//         modalItemKitchenNote.value = "";
//         modalSelectedVariant.value = null;
//         modalSelectedAddons.value = {};
//         modalRemovedIngredients.value = [];

//     } catch (err) {
//         toast.error("Failed to add item: " + (err.response?.data?.message || err.message));
//     }
// };

const confirmAdd = async () => {
    if (!selectedItem.value) return;

    if (modalQty.value <= 0) {
        toast.error('Please select at least 1 item to add to cart');
        return;
    }

    const variant = getModalSelectedVariant();
    const variantId = variant ? variant.id : null;
    const variantText = variant ? ` (${variant.name})` : '';

    const variantName = variant ? variant.name : null;
    const variantPrice = variant
        ? parseFloat(variant.price)
        : parseFloat(selectedItem.value.price);

    const selectedAddons = getModalSelectedAddons();
    const addonsPrice = getModalAddonsPrice();
    const totalItemPrice = variantPrice + addonsPrice;
    const resaleDiscountPerItem = variant
        ? calculateResalePrice(variant, true)
        : calculateResalePrice(selectedItem.value, false);

    const variantIngredients = getVariantIngredients(selectedItem.value, variantId);

    const removedIngredientsText = getRemovedIngredientsText();

    try {
        const addonIds = selectedAddons.map(a => a.id).sort((a, b) => a - b).join('-');
        const menuStock = variantIngredients.length > 0
            ? calculateStockForIngredients(variantIngredients)
            : 999999;

        const idx = orderItems.value.findIndex((i) => {
            if (i.variant_id !== variantId) return false;
            const itemAddonIds = (i.addons || [])
                .map(a => a.id)
                .sort((a, b) => a - b)
                .join('-');
            return i.id === selectedItem.value.id && itemAddonIds === addonIds;
        });

        if (idx >= 0) {
            orderItems.value[idx].qty += modalQty.value;
            orderItems.value[idx].price = orderItems.value[idx].unit_price * orderItems.value[idx].qty;
            orderItems.value[idx].total_resale_discount = resaleDiscountPerItem * orderItems.value[idx].qty;

            if (modalItemKitchenNote.value && modalItemKitchenNote.value.trim()) {
                const existingNote = orderItems.value[idx].item_kitchen_note || '';
                const newNote = modalItemKitchenNote.value.trim();
                const noteParts = [existingNote, newNote, removedIngredientsText]
                    .filter(Boolean)
                    .filter((v, i, a) => a.indexOf(v) === i);

                orderItems.value[idx].item_kitchen_note = noteParts.join('; ');
            } else if (removedIngredientsText) {
                const existingNote = orderItems.value[idx].item_kitchen_note || '';
                orderItems.value[idx].item_kitchen_note = [existingNote, removedIngredientsText]
                    .filter(Boolean)
                    .join('; ');
            }

            if (modalRemovedIngredients.value.length > 0) {
                orderItems.value[idx].removed_ingredients = [...modalRemovedIngredients.value];
            }

            toast.success(`Quantity updated to ${orderItems.value[idx].qty}`);
        } else {
            const finalKitchenNote = [modalItemKitchenNote.value.trim(), removedIngredientsText]
                .filter(Boolean)
                .join('. ');

            orderItems.value.push({
                id: selectedItem.value.id,
                title: selectedItem.value.title,
                img: selectedItem.value.img,
                price: totalItemPrice * modalQty.value,
                unit_price: Number(totalItemPrice),
                qty: modalQty.value,
                note: modalNote.value || "",
                item_kitchen_note: finalKitchenNote,
                stock: menuStock,
                ingredients: variantIngredients,
                variant_id: variantId,
                variant_name: variantName,
                addons: selectedAddons,
                removed_ingredients: [...modalRemovedIngredients.value],
                resale_discount_per_item: resaleDiscountPerItem,
                total_resale_discount: resaleDiscountPerItem * modalQty.value,
            });

            toast.success(`${selectedItem.value.title} added to cart`);
        }

        // await openPromoModal(selectedItem.value);

        const modal = bootstrap.Modal.getInstance(document.getElementById('chooseItem'));
        modal.hide();
        modalQty.value = 0;
        modalNote.value = "";
        modalItemKitchenNote.value = "";
        modalSelectedVariant.value = null;
        modalSelectedAddons.value = {};
        modalRemovedIngredients.value = [];

    } catch (err) {
        toast.error("Failed to add item: " + (err.response?.data?.message || err.message));
    }
};


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
const canIncModalQty = () => {
    if (!selectedItem.value) return false;
    const variant = getModalSelectedVariant();
    const variantId = variant ? variant.id : null;
    const variantIngredients = getVariantIngredients(selectedItem.value, variantId);
    if (!variantIngredients.length) return true;
    const availableToAdd = calculateAvailableStock(selectedItem.value, variantId, variantIngredients);
    return (modalQty.value + 1) <= availableToAdd;
};

// const incQty = () => {
//     if (!canIncModalQty()) {
//         const variant = getModalSelectedVariant();
//         const variantText = variant ? ` (${variant.name})` : '';
//         const variantId = variant ? variant.id : null;
//         const variantIngredients = getVariantIngredients(selectedItem.value, variantId);
//         const availableToAdd = calculateAvailableStock(selectedItem.value, variantId, variantIngredients);
//         if (availableToAdd <= 0) {
//             toast.error(`No more stock available for "${selectedItem.value?.title}${variantText}". Please remove some from cart first.`);
//         } else {
//             toast.error(`Not enough ingredients for "${selectedItem.value?.title}${variantText}".`);
//         }
//         return;
//     }
//     modalQty.value++;
// };


const incQty = async () => {
    if (!selectedItem.value) return;

    const variant = getModalSelectedVariant();
    const variantId = variant ? variant.id : null;
    const variantIngredients = getModalIngredients();

    // Check ingredients client-side
    const stockCheck = calculateClientSideStock({
        id: selectedItem.value.id,
        title: selectedItem.value.title,
        variant_id: variantId,
        variant_name: variant ? variant.name : null,
        ingredients: variantIngredients,
        removed_ingredients: modalRemovedIngredients.value || []
    }, modalQty.value + 1);

    if (!stockCheck.canAdd) {
        console.log('🚨 Missing ingredients for modal increment (client-side)');

        pendingModalIncrementData.value = {
            currentQty: modalQty.value
        };

        missingIngredients.value = stockCheck.missing;
        showMissingIngredientsModal.value = true;

        toast.warning('Some ingredients are missing. Confirm to proceed.');
        return;
    }

    // Normal increment
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
const resetCart = () => {
    orderItems.value = [];
    customer.value = generateCustomerName();
    deliveryLocation.value = "";
    phoneNumber.value = "";
    selectedTable.value = null;
    orderType.value = orderTypes.value[0] || "eat_in";
    note.value = "";
    kitchenNote.value = "";
    deliveryPercent.value = 0;
    selectedPromos.value = [];
    selectedDiscounts.value = [];
    pendingDiscountApprovals.value = [];
    rejectedDiscounts.value = [];
    stopApprovalPolling();
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
    const ingredientUsage = {};

    // Calculate total usage
    for (const item of orderItems.value) {
        const itemIngredients = item.ingredients || [];
        if (itemIngredients.length === 0) {
            continue;
        }

        itemIngredients.forEach(ing => {
            const ingredientId = ing.inventory_item_id;
            const availableStock = parseFloat(ing.inventory_stock ?? ing.inventory_item?.stock ?? 0);
            const requiredQty = parseFloat(ing.quantity ?? ing.qty ?? 1) * item.qty;

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
    for (const [ingredientId, usage] of Object.entries(ingredientUsage)) {

        if (usage.totalUsed > usage.totalStock) {
            toast.error(
                `Not enough "${usage.name}". ` +
                `Need ${usage.totalUsed.toFixed(2)} but only ${usage.totalStock.toFixed(2)} available.`
            );
            return false;
        }
    }
    return true;
};

// const openConfirmModal = () => {
//     if (orderItems.value.length === 0) {
//         toast.error("Please add at least one item to the cart.");
//         return;
//     }
//     if (orderType.value === "Dine_in" && !selectedTable.value) {
//         formErrors.value.table_number = [
//             "Table number is required for dine-in orders.",
//         ];
//         toast.error("Please select a table number for Dine In orders.");
//         return;
//     }
//     if ((orderType.value === "Delivery" || orderType.value === "Takeaway") && !customer.value) {
//         formErrors.value.customer = [
//             "Customer name is required.",
//         ];
//         toast.error("Customer name is required.");
//         return;
//     }
//     if (orderType.value === "Delivery" && !phoneNumber.value) {
//         formErrors.value.phoneNumber = [
//             "Phone number is required for delivery.",
//         ];
//         toast.error("Phone number is required for delivery.");
//         return;
//     }
//     if (orderType.value === "Delivery" && !deliveryLocation.value) {
//         formErrors.value.deliveryLocation = [
//             "Delivery location is required.",
//         ];
//         toast.error("Delivery location is required.");
//         return;
//     }
//     if (!hasEnoughStockForOrder()) {
//         return;
//     }

//     cashReceived.value = parseFloat(grandTotal.value).toFixed(2);
//     showConfirmModal.value = true;
// };

const openConfirmModal = () => {
    if (orderItems.value.length === 0) {
        toast.error("Please add at least one item to the cart.");
        return;
    }
    if (orderType.value === "Eat_in" && !selectedTable.value) {
        formErrors.value.table_number = [
            "Table number is required for dine-in orders.",
        ];
        toast.error("Please select a table number for Dine In orders.");
        return;
    }
    if ((orderType.value === "Delivery" || orderType.value === "Takeaway") && !customer.value) {
        formErrors.value.customer = [
            "Customer name is required.",
        ];
        toast.error("Customer name is required.");
        return;
    }
    if (orderType.value === "Delivery" && !phoneNumber.value) {
        formErrors.value.phoneNumber = [
            "Phone number is required for delivery.",
        ];
        toast.error("Phone number is required for delivery.");
        return;
    }
    if (orderType.value === "Delivery" && !deliveryLocation.value) {
        formErrors.value.deliveryLocation = [
            "Delivery location is required.",
        ];
        toast.error("Delivery location is required.");
        return;
    }

    // ❌ REMOVED: Stock check
    // if (!hasEnoughStockForOrder()) {
    //     return;
    // }

    cashReceived.value = parseFloat(grandTotal.value).toFixed(2);
    showConfirmModal.value = true;
};

const incCartWithWarning = async (i) => {
    const it = orderItems.value[i];
    if (!it) return;

    // Check stock but only show warning
    if (!canIncCartItem(it)) {
        const variantText = it.variant_name ? ` (${it.variant_name})` : '';
        toast.warning(`Low stock for "${it.title}${variantText}". Missing items will be pending.`, {
            autoClose: 2000
        });
    }

    it.outOfStock = false;
    it.qty++;
    it.price = it.unit_price * it.qty;

    if (it.resale_discount_per_item) {
        it.total_resale_discount = it.resale_discount_per_item * it.qty;
    }
};


async function printReceipt(order) {
    try {

        const res = await axios.post(
            'http://localhost:8085/print',
            { order, type: 'customer' },
            {
                headers: { 'Content-Type': 'application/json' },
                timeout: 5000,
            },
        );
    } catch (error) {
        console.error("Print failed:", error);
        toast.error("Unable to connect to the customer printer. Please ensure it is properly connected.");
    }
}


const paymentMethod = ref("cash");
const changeAmount = ref(0);

async function printKot(order) {
    try {
        const res = await axios.post(
            'http://localhost:8085/print',
            { order, type: 'KOT' },
            {
                headers: { 'Content-Type': 'application/json' },
                timeout: 5000,
            },
        );
    } catch (error) {
        console.error("KOT print failed:", error);
        toast.error("Unable to connect to the kitchen printer. Please ensure it is properly connected.");
    }
}


// ================================================
// DISCOUNT APPROVAL SYSTEM - PERCENTAGE ONLY
// ================================================

const pendingDiscountApprovals = ref([]);
const approvalCheckInterval = ref(null);
const showApprovalWaitingModal = ref(false);
const selectedDiscounts = ref([]);
const rejectedDiscounts = ref([]);

// Handle discount application (request approval) - PERCENTAGE ONLY
const handleApplyDiscount = async (appliedDiscounts) => {
    if (!appliedDiscounts || appliedDiscounts.length === 0) {
        toast.warning("No discounts selected.");
        return;
    }

    //  ADD: Filter out rejected discounts
    const rejectedIds = rejectedDiscounts.value.map(d => d.id);
    const allowedDiscounts = appliedDiscounts.filter(d => !rejectedIds.includes(d.id));

    if (allowedDiscounts.length === 0) {
        toast.error("All selected discounts have been rejected for this order.");
        return;
    }

    if (allowedDiscounts.length < appliedDiscounts.length) {
        const rejectedCount = appliedDiscounts.length - allowedDiscounts.length;
        toast.warning(`${rejectedCount} discount(s) were previously rejected and cannot be requested again.`);
    }

    try {
        // Send only percentage to Super Admin
        const response = await axios.post('/api/discount-approvals/request', {
            discounts: appliedDiscounts.map(d => ({
                discount_id: d.id,
                discount_percentage: parseFloat(d.discount_amount), // Only send percentage
                discount_name: d.name
            })),
            order_items: orderItems.value.map(item => ({
                id: item.id,
                title: item.title,
                qty: item.qty,
                unit_price: item.unit_price,
                price: item.price
            })),
            order_subtotal: subTotal.value,
            request_note: `Discount approval request from ${user.value?.name || 'Cashier'}`
        });

        if (response.data.success) {
            // Store approval request IDs with PENDING status
            pendingDiscountApprovals.value = response.data.data.map(approval => ({
                ...approval,
                discountData: appliedDiscounts.find(d => d.id === approval.discount_id),
                percentage: parseFloat(approval.discount_percentage), // Store percentage
                status: 'pending'
            }));

            showDiscountModal.value = false;
            showApprovalWaitingModal.value = true;

            toast.info('Discount approval request sent to Super Admin. Waiting for approval...');
            startApprovalPolling();
        }
    } catch (error) {
        console.error('Error requesting discount approval:', error);
        toast.error(error.response?.data?.message || 'Failed to request discount approval');
    }
};
const startApprovalPolling = () => {
    if (approvalCheckInterval.value) {
        clearInterval(approvalCheckInterval.value);
    }

    approvalCheckInterval.value = setInterval(async () => {
        await checkApprovalStatus();
    }, 3000);
};
const checkApprovalStatus = async () => {
    if (pendingDiscountApprovals.value.length === 0) {
        stopApprovalPolling();
        return;
    }

    try {
        const approvalIds = pendingDiscountApprovals.value.map(a => a.id);
        const response = await axios.post('/api/discount-approvals/check-status', {
            approval_ids: approvalIds
        });

        if (response.data.success) {
            const approvals = response.data.data;

            approvals.forEach(approval => {
                if (approval.status !== 'pending') {
                    const pendingIndex = pendingDiscountApprovals.value.findIndex(
                        p => p.id === approval.id
                    );

                    if (pendingIndex >= 0) {
                        if (approval.status === 'approved') {
                            // Store approved discount with percentage only
                            const approvedDiscount = {
                                id: approval.discount_id,
                                name: approval.discount_name || approval.discount?.name,
                                percentage: parseFloat(approval.discount_percentage),
                                approval_id: approval.id
                            };

                            // Add to approved discounts
                            applyApprovedDiscount(approvedDiscount);

                            toast.success(
                                `Discount "${approvedDiscount.name}" (${approvedDiscount.percentage}%) approved!`
                            );
                        } else if (approval.status === 'rejected') {
                            //  ADD: Track rejected discount
                            rejectedDiscounts.value.push({
                                id: approval.discount_id,
                                name: approval.discount_name,
                                percentage: parseFloat(approval.discount_percentage),
                                rejection_reason: approval.approval_note
                            });

                            toast.error(
                                `Discount "${approval.discount_name}" rejected.` +
                                (approval.approval_note ? ` Reason: ${approval.approval_note}` : '')
                            );
                        }

                        // Remove from pending
                        pendingDiscountApprovals.value.splice(pendingIndex, 1);
                    }
                }
            });

            if (pendingDiscountApprovals.value.length === 0) {
                showApprovalWaitingModal.value = false;
                stopApprovalPolling();
            }
        }
    } catch (error) {
        console.error('Error checking approval status:', error);
    }
};

// Stop polling
const stopApprovalPolling = () => {
    if (approvalCheckInterval.value) {
        clearInterval(approvalCheckInterval.value);
        approvalCheckInterval.value = null;
    }
};

// Apply approved discount (store percentage only)
const applyApprovedDiscount = (discountData) => {
    selectedDiscounts.value.push(discountData);
};


// Cleanup on component unmount
onUnmounted(() => {
    stopApprovalPolling();
});

// Listen for broadcast events
onMounted(() => {
    if (window.Echo) {
        window.Echo.channel('discount-approvals')
            .listen('.approval.responded', (event) => {
                checkApprovalStatus();
            });
    }
});

// Calculate approved discount dynamically based on current subtotal
const approvedDiscountTotal = computed(() => {
    if (!selectedDiscounts.value || selectedDiscounts.value.length === 0) return 0;

    return selectedDiscounts.value.reduce((total, discount) => {
        const percentage = parseFloat(discount.percentage || 0);
        const discountAmount = (subTotal.value * percentage) / 100;
        return total + discountAmount;
    }, 0);
});

// Get individual discount amount for display
const getDiscountAmount = (percentage) => {
    return (subTotal.value * percentage) / 100;
};

// Clear discounts helper
const clearDiscounts = () => {
    selectedDiscounts.value = [];
    pendingDiscountApprovals.value = [];
    rejectedDiscounts.value = [];
    stopApprovalPolling();
};



// ===============================================
// MISSING INGREDIENTS HANDLING
// ===============================================
const showMissingIngredientsModal = ref(false);
const missingIngredients = ref([]);
const pendingIncrementData = ref(null);
const pendingCardIncrementData = ref(null);
const pendingModalIncrementData = ref(null);
const pendingDealIncrementData = ref(null);

const checkMissingIngredientsForItem = async (item, requestedQty = 1) => {
    try {
        const itemsToCheck = [{
            product_id: item.id,
            title: item.title || 'Item',
            quantity: requestedQty,
            variant_id: item.variant_id || null,
            variant_name: item.variant_name || null,
            removed_ingredients: item.removed_ingredients || []
        }];

        const response = await axios.post('/pos/check-ingredients', {
            items: itemsToCheck
        });

        if (response.data.success && response.data.missing_ingredients?.length > 0) {
            return {
                hasMissing: true,
                missingData: response.data.missing_ingredients
            };
        }

        return { hasMissing: false };
    } catch (error) {
        console.error('Error checking ingredients:', error);
        return { hasMissing: false };
    }
};

const pendingOrderData = ref(null);

const confirmOrder = async ({
    paymentMethod,
    cashReceived,
    cardAmount,
    changeAmount,
    items,
    autoPrintKot,
    done
}) => {
    formErrors.value = {};

    try {
        const payload = {
            customer_name: customer.value,
            phone_number: phoneNumber.value,
            delivery_location: deliveryLocation.value,
            sub_total: subTotal.value,
            tax: totalTax.value,
            service_charges: serviceCharges.value,
            delivery_charges: deliveryCharges.value,
            sales_discount: totalResaleSavings.value,

            approved_discounts: approvedDiscountTotal.value,
            approved_discount_details: selectedDiscounts.value.map(discount => ({
                discount_id: discount.id,
                discount_name: discount.name,
                discount_percentage: discount.percentage,
                discount_amount: getDiscountAmount(discount.percentage),
                approval_id: discount.approval_id
            })),

            promo_discount: promoDiscount.value,
            applied_promos: selectedPromos.value.map(promo => ({
                promo_id: promo.id,
                promo_name: promo.name,
                promo_type: promo.type,
                discount_amount: promo.applied_discount || 0,
                applied_to_items: promo.applied_to_items || []
            })),

            total_amount: grandTotal.value,
            note: note.value,
            kitchen_note: kitchenNote.value,
            order_date: new Date().toISOString().split("T")[0],
            order_time: new Date().toTimeString().split(" ")[0],
            order_type: orderType.value === "Eat_in"
                ? "Eat In"
                : orderType.value === "Delivery"
                    ? "Delivery"
                    : orderType.value === "Takeaway"
                        ? "Takeaway"
                        : "Collection",
            table_number: selectedTable.value?.name || null,
            payment_method: paymentMethod,
            auto_print_kot: autoPrintKot,
            cash_received: cashReceived,
            change: changeAmount,

            ...(paymentMethod === 'Split' && {
                payment_type: 'split',
                cash_amount: cashReceived,
                card_amount: cardAmount
            }),

            // items: (orderItems.value ?? []).map((it) => {
            //     const menuItem = menuItems.value.find(m => m.id === it.id);
            //     let sourceItem = menuItem;

            //     if (it.variant_id && menuItem?.variants) {
            //         const variant = menuItem.variants.find(v => v.id === it.variant_id);
            //         if (variant) sourceItem = variant;
            //     }

            //     const resaleDiscount = sourceItem ? calculateResalePrice(sourceItem, !!it.variant_id) : 0;

            //     return {
            //         product_id: it.id,
            //         title: it.title,
            //         quantity: it.qty,
            //         price: it.price,
            //         note: it.note ?? "",
            //         kitchen_note: kitchenNote.value ?? "",
            //         unit_price: it.unit_price,
            //         item_kitchen_note: it.item_kitchen_note ?? "",
            //         tax_percentage: getItemTaxPercentage(it),
            //         tax_amount: getItemTax(it),
            //         variant_id: it.variant_id || null,
            //         variant_name: it.variant_name || null,
            //         addons: it.addons || [],
            //         sale_discount_per_item: resaleDiscount,
            //         removed_ingredients: it.removed_ingredients || [],
            //     };
            // }),

            items: (orderItems.value ?? []).map((it) => {
                //  Check if this is a deal FIRST
                if (it.isDeal) {
                    return {
                        product_id: it.dealId,
                        title: it.title,
                        quantity: it.qty,
                        price: it.price,
                        note: it.note ?? "",
                        kitchen_note: kitchenNote.value ?? "",
                        unit_price: it.unit_price,
                        item_kitchen_note: it.item_kitchen_note ?? "",
                        tax_percentage: 0,
                        tax_amount: 0,
                        variant_id: null,
                        variant_name: null,
                        addons: [],
                        sale_discount_per_item: 0,
                        removed_ingredients: [],

                        // CRITICAL: These fields tell backend this is a deal
                        is_deal: true,
                        deal_id: it.dealId,
                        menu_items: it.menu_items || []
                    };
                }

                //  Regular menu item handling
                const menuItem = menuItems.value.find(m => m.id === it.id);
                let sourceItem = menuItem;

                if (it.variant_id && menuItem?.variants) {
                    const variant = menuItem.variants.find(v => v.id === it.variant_id);
                    if (variant) sourceItem = variant;
                }

                const resaleDiscount = sourceItem ? calculateResalePrice(sourceItem, !!it.variant_id) : 0;

                const removedIngredientsWithNames = (it.removed_ingredients || []).map(removedId => {
                    const ingredient = (it.ingredients || []).find(
                        ing => (ing.id === removedId || ing.inventory_item_id === removedId)
                    );

                    return {
                        id: removedId,
                        name: ingredient ? (ingredient.product_name || ingredient.name) : 'Unknown Ingredient'
                    };
                });

                return {
                    product_id: it.id,
                    title: it.title,
                    quantity: it.qty,
                    price: it.price,
                    note: it.note ?? "",
                    kitchen_note: kitchenNote.value ?? "",
                    unit_price: it.unit_price,
                    item_kitchen_note: it.item_kitchen_note ?? "",
                    tax_percentage: getItemTaxPercentage(it),
                    tax_amount: getItemTax(it),
                    variant_id: it.variant_id || null,
                    variant_name: it.variant_name || null,
                    addons: it.addons || [],
                    sale_discount_per_item: resaleDiscount,
                    removed_ingredients: removedIngredientsWithNames || [],

                    // Explicitly set is_deal to false for regular items
                    is_deal: false
                };
            }),

            // Since we check on increment, always confirm missing ingredients
            confirm_missing_ingredients: true
        };

        console.log('📤 Sending order');

        const res = await axios.post("/pos/order", payload);
        console.log(' Order created successfully:', res.data);

        if (res.data.logout === true) {
            toast.success(res.data.message || "Order created successfully. Logging out...");
            setTimeout(() => {
                window.location.href = res.data.redirect || '/login';
            }, 1000);
            if (done) done();
            return;
        }
        incrementWalkInCounter();
        resetCart();
        showConfirmModal.value = false;
        toast.success(res.data.message || "Order placed successfully!");

        lastOrder.value = {
            ...res.data.order,
            ...payload,
            items: payload.items,
            payment_type: paymentMethod === 'Split' ? 'split' : paymentMethod.toLowerCase(),
            cash_amount: paymentMethod === 'Split' ? cashReceived : null,
            card_amount: paymentMethod === 'Split' ? cardAmount : null,
        };
        console.log('🧾 Last order data prepared:', lastOrder.value);

        if (autoPrintKot) {
            printReceipt(JSON.parse(JSON.stringify(lastOrder.value)));
        }
        kotData.value = await openPosOrdersModal();
        printKot(JSON.parse(JSON.stringify(lastOrder.value)));


        selectedPromos.value = [];
        selectedDiscounts.value = [];
        pendingDiscountApprovals.value = [];
        stopApprovalPolling();

    } catch (err) {
        console.error("❌ Order submission error:", err);
        toast.error(err.response?.data?.message || "Failed to place order");
    } finally {
        if (done) {
            done();
        }
    }
};

const handleConfirmMissingIngredients = async () => {
    console.log(' User confirmed missing ingredients');
    showMissingIngredientsModal.value = false;

    // Handle regular cart increment
    if (pendingIncrementData.value && !pendingIncrementData.value.isDeal) {
        const { itemIndex, newQty } = pendingIncrementData.value;
        const it = orderItems.value[itemIndex];

        if (it) {
            it.outOfStock = false;
            it.qty = newQty;
            it.price = it.unit_price * it.qty;

            if (it.resale_discount_per_item) {
                it.total_resale_discount = it.resale_discount_per_item * it.qty;
            }
            toast.success('Quantity updated');
        }

        pendingIncrementData.value = null;
        missingIngredients.value = [];
        return;
    }

    // Handle deal cart increment
    if (pendingIncrementData.value && pendingIncrementData.value.isDeal) {
        const { itemIndex, newQty } = pendingIncrementData.value;
        const it = orderItems.value[itemIndex];

        if (it && it.isDeal) {
            it.qty = newQty;
            it.price = it.unit_price * it.qty;
            toast.success('Deal quantity updated');
        }

        pendingIncrementData.value = null;
        missingIngredients.value = [];
        return;
    }

    // Handle deal increment from deals grid
    if (pendingDealIncrementData.value) {
        const { deal, existingIndex, newQty } = pendingDealIncrementData.value;

        if (existingIndex >= 0) {
            orderItems.value[existingIndex].qty = newQty;
            orderItems.value[existingIndex].price =
                orderItems.value[existingIndex].unit_price * newQty;
            toast.success('Deal quantity updated');
        } else {
            // Add new deal to cart
            const dealStock = 999999;
            const dealIngredients = [];

            deal.menu_items.forEach(menuItem => {
                if (menuItem.ingredients && menuItem.ingredients.length > 0) {
                    dealIngredients.push(...menuItem.ingredients.map(ing => ({
                        ...ing,
                        menu_item_id: menuItem.id,
                        menu_item_name: menuItem.name
                    })));
                }
            });

            const dealItem = {
                id: deal.id,
                title: deal.title,
                price: totalDealPriceWithAddons.value,
                unit_price: parseFloat(deal.price),
                img: deal.img,
                qty: 1,
                stock: dealStock,
                ingredients: dealIngredients,
                isDeal: true,
                dealId: deal.id,
                menu_items: deal.menu_items.map(item => ({
                    id: item.id,
                    name: item.name,
                    price: item.price,
                    ingredients: (item.ingredients || []).map(ing => ({
                        inventory_item_id: ing.id,
                        inventory_item_id: ing.inventory_item_id,
                        product_name: ing.product_name || ing.name,
                        quantity: ing.quantity || ing.qty || '1.00',
                        inventory_stock: ing.inventory_stock || ing.stock || 0,
                        unit: ing.unit || 'unit',
                        category_id: ing.category_id,
                        supplier_id: ing.supplier_id,
                    })),
                    stock: calculateStockForIngredients(item.ingredients || [])
                })),
                resale_discount_per_item: 0,
                total_resale_discount: 0,
            };

            orderItems.value.push(dealItem);
            toast.success(`${deal.title} added to cart!`);
        }

        pendingDealIncrementData.value = null;
        missingIngredients.value = [];
        return;
    }

    // Handle card increment
    if (pendingCardIncrementData.value) {
        const {
            product,
            variantId,
            variantName,
            variantPrice,
            selectedAddons,
            addonsPrice,
            resaleDiscountPerItem,
            existingIndex,
            newQty
        } = pendingCardIncrementData.value;

        const totalItemPrice = variantPrice + addonsPrice;

        if (existingIndex >= 0) {
            orderItems.value[existingIndex].qty = newQty;
            orderItems.value[existingIndex].price =
                orderItems.value[existingIndex].unit_price * newQty;
            orderItems.value[existingIndex].total_resale_discount =
                resaleDiscountPerItem * newQty;
            orderItems.value[existingIndex].outOfStock = false;
            toast.success('Quantity updated');
        } else {
            const variantIngredients = getVariantIngredients(product, variantId);
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
                ingredients: variantIngredients,
                variant_id: variantId,
                variant_name: variantName,
                addons: selectedAddons,
                outOfStock: false,
                resale_discount_per_item: resaleDiscountPerItem,
                total_resale_discount: resaleDiscountPerItem,
            });
            toast.success('Item added to cart');
        }

        pendingCardIncrementData.value = null;
        missingIngredients.value = [];
        return;
    }

    // Handle modal increment
    if (pendingModalIncrementData.value) {
        modalQty.value = pendingModalIncrementData.value.currentQty + 1;
        toast.success('Quantity increased');
        pendingModalIncrementData.value = null;
        missingIngredients.value = [];
        return;
    }
};



const handleCancelMissingIngredients = () => {
    console.log('❌ User cancelled due to missing ingredients');
    showMissingIngredientsModal.value = false;
    missingIngredients.value = [];

    // Clear all pending increment data
    pendingIncrementData.value = null;
    pendingCardIncrementData.value = null;
    pendingModalIncrementData.value = null;
    pendingDealIncrementData.value = null;

    toast.info("Action cancelled");
};
/* ----------------------------
   Lifecycle
-----------------------------*/
onMounted(async () => {
    initializeWalkInCounter();
    const number = String(counter.value).padStart(3, '0');
    customer.value = `Walk In-${number}`;
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
    categorySearchQuery.value = "";
    categorySearchKey.value = Date.now();
    await nextTick();
    setTimeout(() => {
        isCategorySearchReady.value = true;
        const input = document.getElementById(categoryInputId);
        if (input) {
            input.value = '';
            categorySearchQuery.value = '';
        }
    }, 100);

    fetchMenuCategories();
    fetchMenuItems();
    fetchProfileTables();
    const filterModal = document.getElementById('menuFilterModal');
    if (filterModal) {
        filterModal.addEventListener('hidden.bs.modal', () => {
            // Only clear if filters were NOT just applied
            if (!filtersJustApplied.value) {
                handleClearFilters();
            }
            // Reset the flag for next time
            filtersJustApplied.value = false;
        });
    }

});
const page = usePage();
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

const enableInventoryTracking = computed(() => {
    return page.props.onboarding?.optional_features?.enable_inventory_tracking ?? true;
});

const handleKotStatusUpdated = ({ id, status, message }) => {
    kotData.value = kotData.value.map((kot) =>
        kot.id === id ? { ...kot, status } : kot
    );
};


const showKotModal = ref(false);
const kotData = ref([]);
const kotLoading = ref(false);

const openOrderModal = async () => {
    showKotModal.value = true;
    kotData.value = [];
    kotLoading.value = true;
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
        kotLoading.value = false;
    }
};
const showPosOrdersModal = ref(false);
const posOrdersData = ref([]);
const loading = ref(false);

const openPosOrdersModal = async () => {
    showPosOrdersModal.value = false;
    posOrdersData.value = [];
    loading.value = true;

    try {
        const res = await axios.get(`/api/pos/orders/today`);
        posOrdersData.value = res.data.orders;
    } catch (err) {
        console.error("Failed to fetch POS orders:", err);
        toast.error(
            err.response?.data?.message || "Failed to fetch POS orders"
        );
    } finally {
        loading.value = false;
    }
};

/* ----------------------------
   Promo Handling
-----------------------------*/
const showPromoModal = ref(false);
const showDiscountModal = ref(false);
const loadingPromos = ref(true);
const loadingDiscounts = ref(true);
const promosData = ref([]);
const discountsData = ref([]);
const selectedPromos = ref([]);

// 2. Update handleApplyPromo to support multiple promos
const handleApplyPromo = (promoDataArray) => {
    selectedPromos.value = promoDataArray;

    if (!promoDataArray || promoDataArray.length === 0) {
        toast.warning("No promos selected.");
        return;
    }

    const totalDiscount = promoDataArray.reduce((sum, promo) => {
        return sum + parseFloat(promo.applied_discount || 0);
    }, 0);

    if (totalDiscount <= 0) {
        toast.warning("Selected promos don't provide any discount for current cart items.");
        selectedPromos.value = [];
        return;
    }

    const totalItems = new Set(promoDataArray.flatMap(p => p.applied_to_items || [])).size;

    toast.success(
        `${promoDataArray.length} promo(s) applied! Applied to ${totalItems} item(s). You saved ${formatCurrencySymbol(totalDiscount)}`
    );
    showPromoModal.value = false;
};

// 6. Update getPromoMatchingItems (keep existing)
const getPromoMatchingItems = () => {
    if (!selectedPromos.value || selectedPromos.value.length === 0) return [];

    const allMatchingItems = new Set();

    selectedPromos.value.forEach(promo => {
        if (!promo.menu_items || promo.menu_items.length === 0) {
            orderItems.value.forEach(item => allMatchingItems.add(item));
        } else {
            const promoMenuIds = promo.menu_items.map(item => item.id);
            orderItems.value
                .filter(item => promoMenuIds.includes(item.id))
                .forEach(item => allMatchingItems.add(item));
        }
    });

    return Array.from(allMatchingItems);
};

// REPLACE THIS ENTIRE FUNCTION
const openPromoModal = async () => {
    loadingPromos.value = true;
    showPromoModal.value = true;

    try {
        const response = await axios.get('/api/promos/current');
        if (response.data?.success) {
            promosData.value = response.data.data || [];
        } else {
            console.warn("Failed to fetch promos:", response.data);
            promosData.value = [];
            toast.warning("No promos available at the moment");
        }
    } catch (error) {
        console.error("Error fetching current meal promos:", error);
        promosData.value = [];
        toast.error("Failed to load promotions");
    } finally {
        loadingPromos.value = false;
    }
};

// =======================================================
// DISCOUNT MODAL FETCHER (similar to openPromoModal)
// =======================================================
const openDiscountModal = async () => {
    loadingDiscounts.value = true;
    showDiscountModal.value = true;

    try {
        const response = await axios.get('/api/discounts/all');
        if (response.data?.success) {
            discountsData.value = response.data.data || [];
        } else {
            console.warn("Failed to fetch discounts:", response.data);
            discountsData.value = [];
            toast.warning("No discounts available at the moment");
        }
    } catch (error) {
        console.error("Error fetching current meal discounts:", error);
        discountsData.value = [];
        toast.error("Failed to load discounts");
    } finally {
        loadingDiscounts.value = false;
    }
};

// 7. Update isItemEligibleForPromo
const isItemEligibleForPromo = (cartItem) => {
    if (!selectedPromos.value || selectedPromos.value.length === 0) return false;

    return selectedPromos.value.some(promo => {
        if (!promo.menu_items || promo.menu_items.length === 0) {
            return true;
        }
        const promoMenuIds = promo.menu_items.map(item => item.id);
        return promoMenuIds.includes(cartItem.id);
    });
};
const handleViewOrderDetails = (order) => {
    lastOrder.value = order;
    showReceiptModal.value = true;
    showPosOrdersModal.value = false;
};

// 4. Update promoDiscount to calculate total from all promos
const promoDiscount = computed(() => {
    if (!selectedPromos.value || selectedPromos.value.length === 0) return 0;

    return selectedPromos.value.reduce((total, promo) => {
        const rawDiscount = parseFloat(promo.discount_amount ?? 0) || 0;

        if (promo.applied_discount !== undefined) {
            return total + parseFloat(promo.applied_discount);
        }

        const promoSubtotal = getPromoSubtotalForPromo(promo);

        if (promo.min_purchase && promoSubtotal < parseFloat(promo.min_purchase)) {
            return total;
        }

        if (promo.type === 'flat') {
            return total + rawDiscount;
        }

        if (promo.type === 'percent') {
            const discount = (promoSubtotal * rawDiscount) / 100;
            const maxCap = parseFloat(promo.max_discount ?? 0) || 0;
            if (maxCap > 0 && discount > maxCap) {
                return total + maxCap;
            }
            return total + discount;
        }

        return total;
    }, 0);
});
const getPromoSubtotalForPromo = (promo) => {
    if (!promo.menu_items || promo.menu_items.length === 0) {
        return orderItems.value.reduce((total, item) => {
            return total + (item.unit_price * item.qty);
        }, 0);
    }

    const promoMenuIds = promo.menu_items.map(item => item.id);
    return orderItems.value
        .filter(item => promoMenuIds.includes(item.id))
        .reduce((total, item) => {
            return total + (item.unit_price * item.qty);
        }, 0);
};
const getAppliedPromosData = computed(() => {
    if (!selectedPromos.value || selectedPromos.value.length === 0) return [];

    return selectedPromos.value.map(promo => ({
        promo_id: promo.id,
        promo_name: promo.name,
        promo_type: promo.type,
        discount_amount: promo.applied_discount || 0,
        applied_to_items: promo.applied_to_items || []
    }));
});

//============================
//   Tax Calculation
//============================
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

const totalResaleSavings = computed(() => {
    return orderItems.value.reduce((total, item) => {
        // Use the stored discount if available
        return total + (item.total_resale_discount || 0);
    }, 0);
});
const grandTotal = computed(() => {
    const total = subTotal.value
        + totalTax.value
        + deliveryCharges.value
        + serviceCharges.value
        - totalResaleSavings.value
        - promoDiscount.value
        - approvedDiscountTotal.value;
    return Math.max(0, total);
});

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
// ================================================
// Fixed: Get quantity for a product in the cart
// ================================================
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

// ==============================================================
// Fixed: Check if we can add more (with proper variant check)
// ==============================================================
const canAddMore = (product) => {
    const variant = getSelectedVariant(product);
    const variantId = variant ? variant.id : null;

    // Get ingredients for the SELECTED variant
    const variantIngredients = getVariantIngredients(product, variantId);

    if (!variantIngredients.length) return true;

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

// ========================================
// Fixed: Increment quantity from card with proper validation
// ========================================
// const incrementCardQty = (product) => {
//     if (getProductStock(product) <= 0) {
//         return;
//     }

//     const variant = getSelectedVariant(product);

//     // CHANGED: Use ORIGINAL prices
//     const variantPrice = variant
//         ? parseFloat(variant.price)  
//         : parseFloat(product.price); 

//     const variantId = variant ? variant.id : null;
//     const variantName = variant ? variant.name : null;

//     const selectedAddons = getSelectedAddons(product);
//     const addonsPrice = getAddonsPrice(product);
//     const totalItemPrice = variantPrice + addonsPrice;
//     const addonIds = selectedAddons.map(a => a.id).sort().join('-');

//     // NEW: Calculate resale discount
//     const resaleDiscountPerItem = variant
//         ? calculateResalePrice(variant, true)
//         : calculateResalePrice(product, false);

//     const variantIngredients = getVariantIngredients(product, variantId);

//     if (!canAddMore(product)) {
//         if (variantIngredients.length > 0) {
//             const variantText = variantName ? ` (${variantName})` : '';
//             toast.error(`Not enough ingredients for "${product.title}${variantText}".`);
//         }
//         return;
//     }

//     const existingIndex = orderItems.value.findIndex(item => {
//         const itemAddonIds = (item.addons || []).map(a => a.id).sort().join('-');
//         return item.id === product.id &&
//             item.variant_id === variantId &&
//             itemAddonIds === addonIds;
//     });

//     if (existingIndex >= 0) {
//         orderItems.value[existingIndex].qty++;
//         orderItems.value[existingIndex].price =
//             orderItems.value[existingIndex].unit_price * orderItems.value[existingIndex].qty;

//         // Update total discount
//         orderItems.value[existingIndex].total_resale_discount =
//             resaleDiscountPerItem * orderItems.value[existingIndex].qty;

//         orderItems.value[existingIndex].outOfStock = false;
//     } else {
//         const menuStock = calculateMenuStock(product);
//         orderItems.value.push({
//             id: product.id,
//             title: product.title,
//             img: product.img,
//             price: totalItemPrice, // Original price
//             unit_price: Number(totalItemPrice), // Original price
//             qty: 1,
//             note: "",
//             stock: menuStock,
//             ingredients: variantIngredients,
//             variant_id: variantId,
//             variant_name: variantName,
//             addons: selectedAddons,
//             outOfStock: false,

//             // NEW: Store resale discount info
//             resale_discount_per_item: resaleDiscountPerItem,
//             total_resale_discount: resaleDiscountPerItem,
//         });
//     }
// };

const incrementCardQty = async (product) => {
    const variant = getSelectedVariant(product);
    const variantId = variant ? variant.id : null;
    const selectedAddons = getSelectedAddons(product);
    const addonIds = selectedAddons.map(a => a.id).sort().join('-');

    // Find existing item in cart
    const existingIndex = orderItems.value.findIndex(item => {
        const itemAddonIds = (item.addons || []).map(a => a.id).sort().join('-');
        return item.id === product.id &&
            item.variant_id === variantId &&
            itemAddonIds === addonIds;
    });

    let newQty = 1;
    let existingItem = null;
    const variantIngredients = getVariantIngredients(product, variantId);

    if (existingIndex >= 0) {
        existingItem = orderItems.value[existingIndex];
        newQty = existingItem.qty + 1;
    }

    // Check ingredients client-side
    const stockCheck = calculateClientSideStock({
        id: product.id,
        title: product.title,
        variant_id: variantId,
        variant_name: variant ? variant.name : null,
        ingredients: variantIngredients,
        removed_ingredients: existingItem?.removed_ingredients || [],
        addons: selectedAddons
    }, newQty);

    if (!stockCheck.canAdd) {
        console.log('🚨 Missing ingredients for card increment (client-side)');

        const variantPrice = variant ? parseFloat(variant.price) : parseFloat(product.price);
        const addonsPrice = getAddonsPrice(product);
        const resaleDiscountPerItem = variant
            ? calculateResalePrice(variant, true)
            : calculateResalePrice(product, false);

        pendingCardIncrementData.value = {
            product,
            variant,
            variantId,
            variantName: variant ? variant.name : null,
            variantPrice,
            selectedAddons,
            addonsPrice,
            resaleDiscountPerItem,
            existingIndex,
            newQty
        };

        missingIngredients.value = stockCheck.missing;
        showMissingIngredientsModal.value = true;

        toast.warning('Some ingredients are missing. Confirm to proceed.');
        return;
    }

    // Normal increment
    const variantPrice = variant ? parseFloat(variant.price) : parseFloat(product.price);
    const addonsPrice = getAddonsPrice(product);
    const totalItemPrice = variantPrice + addonsPrice;
    const resaleDiscountPerItem = variant
        ? calculateResalePrice(variant, true)
        : calculateResalePrice(product, false);

    if (existingIndex >= 0) {
        orderItems.value[existingIndex].qty++;
        orderItems.value[existingIndex].price =
            orderItems.value[existingIndex].unit_price * orderItems.value[existingIndex].qty;
        orderItems.value[existingIndex].total_resale_discount =
            resaleDiscountPerItem * orderItems.value[existingIndex].qty;
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
            ingredients: variantIngredients,
            variant_id: variantId,
            variant_name: variant ? variant.name : null,
            addons: selectedAddons,
            outOfStock: false,
            resale_discount_per_item: resaleDiscountPerItem,
            total_resale_discount: resaleDiscountPerItem,
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


// =====================================================
// Helper: Get all ingredients for a specific variant
// =====================================================
const getVariantIngredients = (product, variantId) => {
    if (!variantId || !product.variants?.length) {
        return product.ingredients ?? [];
    }

    const variant = product.variants.find(v => v.id === variantId);
    return variant?.ingredients ?? product.ingredients ?? [];
};

// ================================================
// Fixed: Check if we can increment a cart item
// ================================================
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


//================================
//   Addon Management Modal
//================================
const selectedCartItem = ref(null);
const selectedCartItemIndex = ref(null);
let addonManagementModal = null;

const openAddonModal = (cartItem, index) => {
    selectedCartItem.value = JSON.parse(JSON.stringify(cartItem));
    selectedCartItemIndex.value = index;

    // Get the full menu item to access ALL available addons
    const menuItem = menuItems.value.find(m => m.id === cartItem.id);

    if (menuItem && menuItem.addon_groups && menuItem.addon_groups.length > 0) {
        // Create a map of currently selected addons for quick lookup
        const selectedAddonsMap = new Map();
        if (cartItem.addons) {
            cartItem.addons.forEach(addon => {
                selectedAddonsMap.set(addon.id, {
                    quantity: addon.quantity || 1,
                    selected: true
                });
            });
        }

        // Extract ALL addons from all addon groups
        const allAddons = [];
        menuItem.addon_groups.forEach(group => {
            if (group.addons && group.addons.length > 0) {
                group.addons.forEach(addon => {
                    const existingAddon = selectedAddonsMap.get(addon.id);
                    allAddons.push({
                        id: addon.id,
                        name: addon.name,
                        price: parseFloat(addon.price || 0),
                        group_id: group.group_id,
                        group_name: group.group_name,
                        quantity: existingAddon?.quantity || 1,
                        selected: existingAddon?.selected || false
                    });
                });
            }
        });

        selectedCartItem.value.addons = allAddons;
    } else {
        selectedCartItem.value.addons = [];
    }

    if (!addonManagementModal) {
        const modalEl = document.getElementById('addonManagementModal');
        if (modalEl) {
            addonManagementModal = new bootstrap.Modal(modalEl);
        }
    }

    if (addonManagementModal) {
        addonManagementModal.show();
    }
};

const toggleAddon = (addon) => {
    addon.selected = !addon.selected;
    if (!addon.selected) {
        addon.quantity = 1;
    }
};

const incrementAddon = (addon) => {
    if (addon.selected) {
        addon.quantity = (addon.quantity || 1) + 1;
    }
};

const decrementAddon = (addon) => {
    if (addon.selected && (addon.quantity || 1) > 1) {
        addon.quantity -= 1;
    }
};

const getCartItemAddonsTotal = (cartItem) => {
    if (!cartItem.addons) return 0;
    return cartItem.addons.reduce((total, addon) => {
        if (addon.selected !== false) {
            return total + (parseFloat(addon.price) * (addon.quantity || 1));
        }
        return total;
    }, 0);
};

const saveAddonChanges = () => {
    if (selectedCartItemIndex.value === null) return;

    // Filter only selected addons
    const selectedAddons = selectedCartItem.value.addons.filter(addon => addon.selected !== false);

    // Calculate new unit price
    const variant = selectedCartItem.value.variant_id
        ? menuItems.value.find(m => m.id === selectedCartItem.value.id)?.variants?.find(v => v.id === selectedCartItem.value.variant_id)
        : null;
    const basePrice = variant ? parseFloat(variant.price) : parseFloat(selectedCartItem.value.unit_price);

    const addonsTotal = selectedAddons.reduce((total, addon) => {
        return total + (parseFloat(addon.price) * (addon.quantity || 1));
    }, 0);

    const newUnitPrice = basePrice + addonsTotal;

    // Update cart item
    orderItems.value[selectedCartItemIndex.value].addons = selectedAddons;
    orderItems.value[selectedCartItemIndex.value].unit_price = newUnitPrice;
    orderItems.value[selectedCartItemIndex.value].price = newUnitPrice * orderItems.value[selectedCartItemIndex.value].qty;

    toast.success('Addons updated successfully!');

    if (addonManagementModal) {
        addonManagementModal.hide();
    }

    // Clear selection
    selectedCartItem.value = null;
    selectedCartItemIndex.value = null;
};

const getAddonTooltip = (cartItem) => {
    if (!cartItem.addons || cartItem.addons.length === 0) return '';
    return cartItem.addons.map(a => a.name).join(', ');
};

const getSelectedAddonsCount = () => {
    let count = 0;

    if (modalSelectedAddons.value && typeof modalSelectedAddons.value === 'object') {
        Object.values(modalSelectedAddons.value).forEach(addons => {
            if (Array.isArray(addons)) {
                count += addons.length;
            }
        });
    }

    return count;
};

// =================================================================
//              Customer View Screen - FIXED
// =================================================================
import { usePOSBroadcast } from '@/composables/usePOSBroadcast';
import { debounce } from 'lodash';
import DiscountModal from "./DiscountModal.vue";
import ConfirmMissingIngredientsModal from "@/Components/ConfirmMissingIngredientsModal.vue";
import PendingOrdersModal from "./PendingOrdersModal.vue";
import { Eye, Pencil } from "lucide-vue-next";

const user = computed(() => page.props.current_user);

const categoriesWithMenus = computed(() => {
    return menuCategories.value.filter(category => {
        const hasMenuItems = category.menu_items_count && category.menu_items_count > 0;
        const hasDeals = category.deals_count && category.deals_count > 0;

        return hasMenuItems || hasDeals;
    });
});

const isCashier = computed(() => {
    return user.value?.roles?.includes('Cashier') ||
        user.value?.roles?.includes('Admin') ||
        user.value?.roles?.includes('Manager');
});

const terminalId = ref(`terminal-${user.value?.id}`);
const { broadcastCartUpdate, broadcastUIUpdate } = usePOSBroadcast(terminalId.value);

//  FIXED: Faster debounce timing
const debouncedBroadcastCart = debounce((data) => {
    console.log('📤 Broadcasting cart:', data.items.length, 'items');
    broadcastCartUpdate(data);
}, 300); // Reduced from 500ms to 300ms

const debouncedBroadcastUI = debounce((data) => {
    console.log('📤 Broadcasting UI');
    broadcastUIUpdate(data);
}, 100); // Increased from 10ms to 100ms for stability

//  FIXED: Watch for cart changes with proper item mapping
watch(
    () => ({
        items: orderItems.value.map(item => ({
            id: item.id,
            title: item.name || item.title,
            img: item.image_url || item.img || '/assets/img/default.png',
            price: parseFloat(item.total || item.price || 0),
            qty: parseInt(item.qty || 1),
            variant_name: item.variant?.name || null,
            addons: (item.addons || []).map(addon => ({
                id: addon.id,
                name: addon.name,
                price: parseFloat(addon.price || 0)
            })),
            resale_discount_per_item: parseFloat(item.resale_discount_per_item || 0)
        })),
        customer: customer.value || 'Walk In',
        phone_number: phoneNumber.value || '',
        delivery_location: deliveryLocation.value || '',
        orderType: orderType.value || 'Dine In',
        table: selectedTable.value ? {
            id: selectedTable.value.id,
            name: selectedTable.value.name
        } : null,
        subtotal: parseFloat(subTotal.value || 0),
        tax: parseFloat(totalTax.value || 0),
        serviceCharges: parseFloat(serviceCharges.value || 0),
        deliveryCharges: parseFloat(deliveryCharges.value || 0),
        saleDiscount: parseFloat(totalResaleSavings.value || 0),
        promoDiscount: parseFloat(promoDiscount.value || 0),
        total: parseFloat(grandTotal.value || 0),
        note: note.value || '',
        appliedPromos: (selectedPromos.value || []).map(promo => ({
            id: promo.id,
            name: promo.name,
            discount: parseFloat(promo.discount || 0),
            applied_to_items: promo.applied_to_items || []
        }))
    }),
    (newCart) => {
        console.log('🔔 Cart changed:', newCart.items.length, 'items, Total:', newCart.total);
        debouncedBroadcastCart(newCart);
    },
    { deep: true, immediate: true }
);

// Watch for UI state changes
watch(
    () => ({
        showCategories: showCategories.value ?? true,
        activeCat: activeCat.value || null,
        menuCategories: (menuCategories.value || []).map(cat => ({
            id: cat.id,
            name: cat.name,
            menu_items_count: cat.menu_items_count || 0
        })),
        menuItems: (menuItems.value || []).map(item => ({
            id: item.id,
            name: item.name,
            price: parseFloat(item.price || 0),
            image_url: item.image_url || null,
            description: item.description || '',
            label_color: item.label_color || '#1B1670',
            category: item.category ? {
                id: item.category.id,
                name: item.category.name
            } : null,
            variants: (item.variants || []).map(v => ({
                id: v.id,
                name: v.name,
                price: parseFloat(v.price || 0),
                is_saleable: v.is_saleable ?? false,
                resale_type: v.resale_type || null,
                resale_value: parseFloat(v.resale_value || 0)
            })),
            addon_groups: (item.addon_groups || []).map(g => ({
                id: g.id,
                name: g.name,
                addons: (g.addons || []).map(a => ({
                    id: a.id,
                    name: a.name,
                    price: parseFloat(a.price || 0)
                }))
            })),
            is_saleable: item.is_saleable ?? false,
            resale_type: item.resale_type || null,
            resale_value: parseFloat(item.resale_value || 0)
        })),
        searchQuery: searchQuery.value || '',
        selectedCardVariant: selectedCardVariant.value || {},
        selectedCardAddons: selectedCardAddons.value || {}
    }),
    (newUI) => {
        console.log('🔔 UI changed - Categories:', newUI.menuCategories.length, 'Items:', newUI.menuItems.length);
        debouncedBroadcastUI(newUI);
    },
    { deep: true, immediate: true }
);

//  Customer Display function
const openCustomerDisplay = () => {
    // if (!isCashier.value) {
    //     toast.error('Only cashiers can access customer display');
    //     return;
    // }

    const url = route('customer-display.index', { terminal: terminalId.value });
    window.open(url, '_blank', 'width=1920,height=1080');
};

// ============================================
// RESALE PRICE CALCULATION HELPERS
// ============================================

const calculateResalePrice = (item, isVariant = false) => {
    if (!item) return 0;

    // 🧠 Safe extraction for both simple & variant items
    const isSaleable = item.is_saleable ?? item?.menu_item?.is_saleable;
    const resaleType = item.resale_type ?? item?.menu_item?.resale_type;
    const resaleValue = item.resale_value ?? item?.menu_item?.resale_value;
    const basePrice = parseFloat(item.price || 0);

    if (!isSaleable || !resaleType || !resaleValue) {
        return 0;
    }

    if (resaleType === 'flat') {
        return parseFloat(resaleValue);
    }

    if (resaleType === 'percentage') {
        return (basePrice * parseFloat(resaleValue)) / 100;
    }

    return 0;
};

const getFinalPrice = (item, isVariant = false) => {
    const basePrice = parseFloat(item.price || 0);
    const resalePrice = calculateResalePrice(item, isVariant);
    return Math.max(0, basePrice - resalePrice);
};

const getResaleBadgeInfo = (item, isVariant = false) => {
    if (!item) return null;
    const isSaleable = item.is_saleable ?? item?.menu_item?.is_saleable;
    const resaleType = item.resale_type ?? item?.menu_item?.resale_type;
    const resaleValue = item.resale_value ?? item?.menu_item?.resale_value;

    const resalePrice = calculateResalePrice(item, isVariant);

    if (!isSaleable || !resaleType || !resaleValue || resalePrice <= 0) {
        return null;
    }

    return {
        type: resaleType,
        value: resaleValue,
        amount: resalePrice,
        display:
            resaleType === 'flat'
                ? `Sale ${formatCurrencySymbol(resalePrice)}`
                : `${resaleValue}% OFF`
    };
};
const getTotalPriceWithResale = (product) => {
    const variant = getSelectedVariant(product);
    let basePrice;
    if (variant) {
        basePrice = getFinalPrice(variant, true);
    } else {
        basePrice = getFinalPrice(product, false);
    }
    const addonsPrice = getAddonsPrice(product);

    return basePrice + addonsPrice;
};
const getVariantPriceRangeWithResale = (product) => {
    if (!product.variants || product.variants.length === 0) {
        const hasSale = calculateResalePrice(product, false) > 0;
        return {
            min: getFinalPrice(product, false),
            max: getFinalPrice(product, false),
            minOriginal: hasSale ? parseFloat(product.price || 0) : null,
            maxOriginal: hasSale ? parseFloat(product.price || 0) : null
        };
    }

    const prices = product.variants.map(v => getFinalPrice(v, true));
    const originalPrices = product.variants.map(v => parseFloat(v.price || 0));
    const hasSale = product.variants.some(v => calculateResalePrice(v, true) > 0);

    return {
        min: Math.min(...prices),
        max: Math.max(...prices),
        minOriginal: hasSale ? Math.min(...originalPrices) : null,
        maxOriginal: hasSale ? Math.max(...originalPrices) : null
    };
};

const getModalVariantPriceWithResale = () => {
    if (!selectedItem.value) return 0;

    const variant = getModalSelectedVariant();
    if (variant) {
        return getFinalPrice(variant, true);
    }

    return getFinalPrice(selectedItem.value, false);
};
const getModalTotalPriceWithResale = () => {
    return getModalVariantPriceWithResale() + getModalAddonsPrice();
};


const deals = ref([]);
const dealsLoading = ref(false);

// Fetch deals function
const fetchDeals = async () => {
    dealsLoading.value = true;
    try {
        const response = await axios.get('/api/deals', {
            params: {
                status: 1,
                per_page: 100
            }
        });
        console.log('Fetched deals:', response.data.data);
        deals.value = response.data.data.map(deal => ({
            id: deal.id,
            title: deal.name,
            price: deal.price,
            img: deal.image_url || '/assets/img/product/product29.jpg',
            description: deal.description,
            menu_items: deal.menu_items || [],
            status: deal.status,
            category_id: deal.category_id,
            category: deal.category
        }));
    } catch (error) {
        console.error('Error fetching deals:', error);
        toast.error('Failed to load deals');
    } finally {
        dealsLoading.value = false;
    }
};

// Get quantity of a deal in cart
const getDealQty = (deal) => {
    const cartItem = orderItems.value.find(item =>
        item.isDeal && item.dealId === (deal.dealId || deal.id)
    );
    return cartItem ? cartItem.qty : 0;
};

// Get total count of items (menu items + deals) in a category
const getCategoryItemCount = (categoryId) => {
    const category = menuCategories.value.find(c => c.id === categoryId);
    const menuCount = +(category?.menu_items_count || 0);
    const dealsCount = getCategoryDealsCount(categoryId);
    return menuCount + dealsCount;
};

// Get count of deals in a specific category
const getCategoryDealsCount = (categoryId) => {
    if (!deals.value || deals.value.length === 0) return 0;
    return deals.value.filter(deal => deal.category_id === categoryId).length;
};

// Calculate client-side stock for deal
// const calculateDealStock = (deal, requestedQty) => {
//     const missingIngredients = [];

//     // Build a complete ingredient usage map from ALL cart items
//     const ingredientUsage = {};

//     // Track ALL ingredients used in the entire cart
//     orderItems.value.forEach(cartItem => {
//         let itemIngredients = [];

//         if (cartItem.isDeal) {
//             // For deals, collect ingredients from all menu items
//             cartItem.menu_items?.forEach(dealMenuItem => {
//                 itemIngredients.push(...(dealMenuItem.ingredients || []));
//             });
//         } else {
//             // For regular items, use their ingredients
//             itemIngredients = cartItem.ingredients || [];
//         }

//         // Process each ingredient
//         itemIngredients.forEach(ing => {
//             const id = ing.inventory_item_id || ing.id;
//             const stock = Number(ing.inventory_stock ?? ing.stock ?? 0);
//             const required = Number(ing.quantity ?? ing.qty ?? 1) * cartItem.qty;

//             if (!ingredientUsage[id]) {
//                 ingredientUsage[id] = {
//                     totalStock: stock,
//                     used: 0,
//                     name: ing.product_name || ing.name,
//                     unit: ing.unit || 'unit'
//                 };
//             }

//             ingredientUsage[id].used += required;
//         });
//     });

//     // Now check if the requested deal quantity can be fulfilled
//     deal.menu_items.forEach(menuItem => {
//         const ingredients = menuItem.ingredients || [];
//         if (ingredients.length === 0) return;

//         ingredients.forEach(ing => {
//             const id = ing.inventory_item_id || ing.id; // Handle both property names
//             const required = Number(ing.quantity ?? ing.qty ?? 1) * requestedQty;
//             const stock = Number(ing.inventory_stock ?? ing.stock ?? 0); // ← FIX: Check both

//             // Calculate available stock
//             let available = stock;

//             if (ingredientUsage[id]) {
//                 available = ingredientUsage[id].totalStock - ingredientUsage[id].used;
//             }

//             // If this deal is already in cart, add back its current usage
//             const existingDealItem = orderItems.value.find(ci =>
//                 ci.isDeal && ci.dealId === deal.id
//             );

//             if (existingDealItem) {
//                 const currentUsage = Number(ing.quantity ?? ing.qty ?? 1) * existingDealItem.qty;
//                 available += currentUsage;
//             }

//             // Check if we have enough stock
//             if (available < required) {
//                 missingIngredients.push({
//                     deal_id: deal.id,
//                     deal_title: deal.title,
//                     menu_item_id: menuItem.id,
//                     menu_item_name: menuItem.name,
//                     inventory_item_id: id,
//                     inventory_item_name: ing.product_name || ing.name,
//                     required_quantity: required,
//                     available_quantity: Math.max(0, available),
//                     shortage_quantity: required - available,
//                     unit: ing.unit || 'unit',
//                     order_quantity: requestedQty
//                 });
//             }
//         });
//     });

//     return {
//         canAdd: missingIngredients.length === 0,
//         missing: missingIngredients
//     };
// };


const calculateDealStock = (deal, requestedQty) => {
    // If inventory tracking is disabled, allow unlimited
    if (!enableInventoryTracking.value) {
        return { canAdd: true, missing: [] };
    }

    const missingIngredients = [];

    // Build a complete ingredient usage map from ALL cart items
    const ingredientUsage = {};

    // Track ALL ingredients used in the entire cart
    orderItems.value.forEach(cartItem => {
        let itemIngredients = [];

        if (cartItem.isDeal) {
            // For deals, collect ingredients from all menu items
            cartItem.menu_items?.forEach(dealMenuItem => {
                itemIngredients.push(...(dealMenuItem.ingredients || []));
            });
        } else {
            // For regular items, use their ingredients
            itemIngredients = cartItem.ingredients || [];
        }

        // Process each ingredient
        itemIngredients.forEach(ing => {
            const id = ing.inventory_item_id || ing.id;
            const stock = Number(ing.inventory_stock ?? ing.stock ?? 0);
            const required = Number(ing.quantity ?? ing.qty ?? 1) * cartItem.qty;

            if (!ingredientUsage[id]) {
                ingredientUsage[id] = {
                    totalStock: stock,
                    used: 0,
                    name: ing.product_name || ing.name,
                    unit: ing.unit || 'unit'
                };
            }

            ingredientUsage[id].used += required;
        });
    });

    // Now check if the requested deal quantity can be fulfilled
    deal.menu_items.forEach(menuItem => {
        const ingredients = menuItem.ingredients || [];
        if (ingredients.length === 0) return;

        ingredients.forEach(ing => {
            const id = ing.inventory_item_id || ing.id;
            const required = Number(ing.quantity ?? ing.qty ?? 1) * requestedQty;
            const stock = Number(ing.inventory_stock ?? ing.stock ?? 0);

            // Calculate available stock
            let available = stock;

            if (ingredientUsage[id]) {
                available = ingredientUsage[id].totalStock - ingredientUsage[id].used;
            }

            // If this deal is already in cart, add back its current usage
            const existingDealItem = orderItems.value.find(ci =>
                ci.isDeal && ci.dealId === deal.id
            );

            if (existingDealItem) {
                const currentUsage = Number(ing.quantity ?? ing.qty ?? 1) * existingDealItem.qty;
                available += currentUsage;
            }

            // Check if we have enough stock
            if (available < required) {
                missingIngredients.push({
                    deal_id: deal.id,
                    deal_title: deal.title,
                    menu_item_id: menuItem.id,
                    menu_item_name: menuItem.name,
                    inventory_item_id: id,
                    inventory_item_name: ing.product_name || ing.name,
                    required_quantity: required,
                    available_quantity: Math.max(0, available),
                    shortage_quantity: required - available,
                    unit: ing.unit || 'unit',
                    order_quantity: requestedQty
                });
            }
        });
    });

    return {
        canAdd: missingIngredients.length === 0,
        missing: missingIngredients
    };
};

// Increment deal quantity
const incrementDealQty = async (deal) => {
    const existingIndex = orderItems.value.findIndex(item =>
        item.isDeal && item.dealId === deal.id
    );

    const newQty = existingIndex >= 0 ? orderItems.value[existingIndex].qty + 1 : 1;

    // Check ingredients client-side
    const stockCheck = calculateDealStock(deal, newQty);

    if (!stockCheck.canAdd) {
        console.log('🚨 Missing ingredients for deal (client-side)');

        pendingDealIncrementData.value = {
            deal: deal,
            existingIndex: existingIndex,
            newQty: newQty
        };

        missingIngredients.value = stockCheck.missing;
        showMissingIngredientsModal.value = true;

        toast.warning('Some ingredients are missing. Confirm to proceed.');
        return;
    }

    // Normal increment
    if (existingIndex >= 0) {
        orderItems.value[existingIndex].qty++;
        orderItems.value[existingIndex].price =
            orderItems.value[existingIndex].unit_price * orderItems.value[existingIndex].qty;
        toast.success('Deal quantity updated');
    } else {
        // Add new deal to cart
        const dealStock = 999999; // Calculate if needed
        const dealIngredients = [];

        deal.menu_items.forEach(menuItem => {
            if (menuItem.ingredients && menuItem.ingredients.length > 0) {
                dealIngredients.push(...menuItem.ingredients.map(ing => ({
                    ...ing,
                    menu_item_id: menuItem.id,
                    menu_item_name: menuItem.name
                })));
            }
        });

        const dealItem = {
            id: deal.id,
            title: deal.title,
            price: parseFloat(deal.price),
            unit_price: parseFloat(deal.price),
            img: deal.img,
            qty: 1,
            stock: dealStock,
            ingredients: dealIngredients,
            isDeal: true,
            dealId: deal.id,
            menu_items: deal.menu_items.map(item => ({
                id: item.id,
                name: item.name,
                price: item.price,
                ingredients: item.ingredients || [],
                stock: calculateStockForIngredients(item.ingredients || [])
            })),
            resale_discount_per_item: 0,
            total_resale_discount: 0,
        };

        orderItems.value.push(dealItem);
        toast.success(`${deal.title} added to cart!`);
    }
};

// Decrement deal quantity
const decrementDealQty = (deal) => {
    const existingIndex = orderItems.value.findIndex(item =>
        item.isDeal && item.dealId === deal.id
    );

    if (existingIndex < 0) return;

    const item = orderItems.value[existingIndex];

    if (item.qty <= 1) {
        orderItems.value.splice(existingIndex, 1);
        toast.info('Deal removed from cart');
    } else {
        item.qty--;
        item.price = item.unit_price * item.qty;
        toast.success('Deal quantity decreased');
    }
};


onMounted(() => {
    fetchDeals();
    fetchPendingOrders();
});


// ======================================================
//              Deals Stepper - Customization
// ======================================================
const selectedDeal = ref(null);
const currentDealMenuItemIndex = ref(0);
const currentDealStep = ref(1);
const dealRemovedIngredients = ref({});
const dealSelectedAddons = ref({});
const dealItemKitchenNotes = ref({});
const completedDealItems = ref([]);
let customizeDealModal = null;

// Computed properties for deal customization
const currentDealMenuItem = computed(() => {
    if (!selectedDeal.value || !selectedDeal.value.menu_items) return null;
    return selectedDeal.value.menu_items[currentDealMenuItemIndex.value];
});

const hasDealIngredients = computed(() => {
    return getCurrentDealIngredients().length > 0;
});

const hasDealAddons = computed(() => {
    return currentDealMenuItem.value?.addon_groups &&
        currentDealMenuItem.value.addon_groups.length > 0;
});

const dealVisibleSteps = computed(() => {
    const steps = [];
    if (hasDealIngredients.value) {
        steps.push({ id: 'ingredients', name: 'Ingredients' });
    }
    if (hasDealAddons.value) {
        steps.push({ id: 'addons', name: 'Add-ons' });
    }
    steps.push({ id: 'review', name: 'Review' });
    return steps;
});

const dealFinalStep = computed(() => dealVisibleSteps.value.length);

const dealStepProgressWidth = computed(() => {
    if (currentDealStep.value === 1) return '0%';
    const totalSteps = dealVisibleSteps.value.length;
    const progressRatio = (currentDealStep.value - 1) / (totalSteps - 1);
    const widthPercentage = progressRatio * 100;
    return `calc(${widthPercentage}% - ${14 - (progressRatio * 28)}px)`;
});

const isLastDealItem = computed(() => {
    return currentDealMenuItemIndex.value === (selectedDeal.value?.menu_items?.length - 1);
});

const dealProgressPercentage = computed(() => {
    if (!selectedDeal.value?.menu_items?.length) return 0;
    return (completedDealItems.value.length / selectedDeal.value.menu_items.length) * 100;
});

// Get ingredients for current deal menu item
const getCurrentDealIngredients = () => {
    if (!currentDealMenuItem.value) return [];
    return currentDealMenuItem.value.ingredients || [];
};

// Check if ingredient is removed
const isDealIngredientRemoved = (ingredientId) => {
    const removed = dealRemovedIngredients.value[currentDealMenuItemIndex.value] || [];
    return removed.includes(ingredientId);
};

// Toggle ingredient removal
const toggleDealIngredient = (ingredientId) => {
    const idx = currentDealMenuItemIndex.value;
    if (!dealRemovedIngredients.value[idx]) {
        dealRemovedIngredients.value[idx] = [];
    }

    const removed = dealRemovedIngredients.value[idx];
    const index = removed.indexOf(ingredientId);

    if (index > -1) {
        removed.splice(index, 1);
        toast.info('Ingredient added back');
    } else {
        removed.push(ingredientId);
        toast.info('Ingredient removed');
    }
};

// Get remaining ingredients count
const getRemainingDealIngredientsCount = () => {
    const ingredients = getCurrentDealIngredients();
    console.log('ingredients:', ingredients);
    console.log('Type:', typeof ingredients);
    console.log('Is Array:', Array.isArray(ingredients));

    // Check if ingredients is valid and is an array
    if (!ingredients || !Array.isArray(ingredients)) {
        console.error('getCurrentDealIngredients() did not return an array:', ingredients);
        return 0;
    }
    const removed = dealRemovedIngredients.value[currentDealMenuItemIndex.value] || [];
    return ingredients.filter(ing => !removed.includes(ing.id)).length;
};

// Handle addon change
const handleDealAddonChange = (addonGroupId, selectedAddons) => {
    const idx = currentDealMenuItemIndex.value;

    if (!dealSelectedAddons.value[idx]) {
        dealSelectedAddons.value[idx] = {};
    }

    const menuItem = currentDealMenuItem.value;
    const addonGroup = menuItem?.addon_groups?.find(g => g.group_id === addonGroupId);

    // Check max_select limit
    if (addonGroup && addonGroup.max_select > 0 && selectedAddons.length > addonGroup.max_select) {
        toast.warning(`You can only select up to ${addonGroup.max_select} ${addonGroup.group_name}`);
        dealSelectedAddons.value[idx][addonGroupId] = selectedAddons.slice(0, addonGroup.max_select);
        return;
    }

    dealSelectedAddons.value[idx][addonGroupId] = selectedAddons;
};

// Get selected addons count for current item
const getDealSelectedAddonsCount = () => {
    const idx = currentDealMenuItemIndex.value;
    const addons = dealSelectedAddons.value[idx];
    if (!addons) return 0;

    let count = 0;
    Object.values(addons).forEach(addonList => {
        count += addonList.length;
    });
    return count;
};

// Get selected addons text
const getDealSelectedAddonsText = () => {
    const idx = currentDealMenuItemIndex.value;
    const addons = dealSelectedAddons.value[idx];
    if (!addons) return '';

    const allAddons = [];
    Object.values(addons).forEach(addonList => {
        addonList.forEach(addon => {
            allAddons.push(`${addon.name} (+${formatCurrencySymbol(addon.price)})`);
        });
    });
    return allAddons.join(', ');
};

// Get removed ingredients text
const getDealRemovedIngredientsText = () => {
    const idx = currentDealMenuItemIndex.value;
    const removed = dealRemovedIngredients.value[idx] || [];
    if (removed.length === 0) return '';

    const ingredients = getCurrentDealIngredients();
    const removedNames = removed
        .map(id => {
            const ing = ingredients.find(i => i.id === id);
            return ing ? (ing.product_name || ing.name) : null;
        })
        .filter(Boolean);

    if (removedNames.length === 0) return '';
    return `No ${removedNames.join(', ')}`;
};

// Step navigation
const getDealStepCircleClass = (step) => {
    if (step < currentDealStep.value) {
        return 'bg-success text-white';
    } else if (step === currentDealStep.value) {
        return 'bg-primary text-white';
    } else {
        return 'bg-light text-muted border';
    }
};

const nextDealStep = () => {
    let nextStepNum = currentDealStep.value + 1;

    while (nextStepNum <= dealFinalStep.value) {
        const stepInfo = dealVisibleSteps.value[nextStepNum - 1];

        if (stepInfo.id === 'ingredients' && !hasDealIngredients.value) {
            nextStepNum++;
            continue;
        }
        if (stepInfo.id === 'addons' && !hasDealAddons.value) {
            nextStepNum++;
            continue;
        }

        break;
    }

    if (nextStepNum <= dealFinalStep.value) {
        currentDealStep.value = nextStepNum;
    }
};

const previousDealStep = () => {
    let prevStepNum = currentDealStep.value - 1;

    while (prevStepNum >= 1) {
        const stepInfo = dealVisibleSteps.value[prevStepNum - 1];

        if (stepInfo.id === 'ingredients' && !hasDealIngredients.value) {
            prevStepNum--;
            continue;
        }
        if (stepInfo.id === 'addons' && !hasDealAddons.value) {
            prevStepNum--;
            continue;
        }

        break;
    }

    if (prevStepNum >= 1) {
        currentDealStep.value = prevStepNum;
    }
};

// Confirm current item and move to next
const confirmCurrentDealItem = () => {
    // Mark current item as completed
    if (!completedDealItems.value.includes(currentDealMenuItemIndex.value)) {
        completedDealItems.value.push(currentDealMenuItemIndex.value);
    }

    // Move to next item
    currentDealMenuItemIndex.value++;
    currentDealStep.value = 1; // Reset to first step

    toast.success('Item customized! Configure next item.');
};

// Final confirmation - add deal to cart
const confirmDealAndAddToCart = async () => {
    if (!selectedDeal.value) return;

    // Mark last item as completed
    if (!completedDealItems.value.includes(currentDealMenuItemIndex.value)) {
        completedDealItems.value.push(currentDealMenuItemIndex.value);
    }

    // Build customized deal data
    const customizedMenuItems = selectedDeal.value.menu_items.map((menuItem, idx) => {
        const removedIngredients = dealRemovedIngredients.value[idx] || [];
        const selectedAddons = dealSelectedAddons.value[idx] || {};
        const kitchenNote = dealItemKitchenNotes.value[idx] || '';

        // Flatten addons
        const allAddons = [];
        Object.values(selectedAddons).forEach(addonList => {
            addonList.forEach(addon => {
                allAddons.push({
                    id: addon.id,
                    name: addon.name,
                    price: parseFloat(addon.price || 0)
                });
            });
        });

        // Filter ingredients (remove the ones user doesn't want)
        const activeIngredients = (menuItem.ingredients || [])
            .filter(ing => !removedIngredients.includes(ing.id))
            .map(ing => ({
                id: ing.id,
                inventory_item_id: ing.inventory_item_id || ing.id,
                product_name: ing.product_name || ing.name,
                quantity: ing.quantity || ing.qty || '1.00',
                stock: ing.stock || 0,
                unit: ing.unit || 'unit'
            }));

        return {
            id: menuItem.id,
            name: menuItem.name,
            price: menuItem.price,
            ingredients: activeIngredients,
            addons: allAddons,
            kitchen_note: kitchenNote,
            removed_ingredients: removedIngredients,
            stock: calculateStockForIngredients(activeIngredients)
        };
    });

    // Check if deal already exists in cart
    const existingDealIndex = orderItems.value.findIndex(item =>
        item.isDeal && item.dealId === selectedDeal.value.id
    );

    const dealStock = 999999;
    const allDealIngredients = [];

    customizedMenuItems.forEach(menuItem => {
        if (menuItem.ingredients && menuItem.ingredients.length > 0) {
            allDealIngredients.push(...menuItem.ingredients.map(ing => ({
                ...ing,
                menu_item_id: menuItem.id,
                menu_item_name: menuItem.name
            })));
        }
    });

    if (existingDealIndex >= 0) {
        // Increment existing deal
        orderItems.value[existingDealIndex].qty++;
        orderItems.value[existingDealIndex].price =
            orderItems.value[existingDealIndex].unit_price *
            orderItems.value[existingDealIndex].qty;
        toast.success('Deal quantity updated!');
    } else {
        // Add new customized deal
        const dealItem = {
            id: selectedDeal.value.id,
            title: selectedDeal.value.title,
            price: totalDealPriceWithAddons.value,
            unit_price: parseFloat(selectedDeal.value.price),
            img: selectedDeal.value.img,
            qty: 1,
            stock: dealStock,
            ingredients: allDealIngredients,
            isDeal: true,
            dealId: selectedDeal.value.id,
            menu_items: customizedMenuItems,
            resale_discount_per_item: 0,
            total_resale_discount: 0,
        };

        orderItems.value.push(dealItem);
        toast.success(`${selectedDeal.value.title} added to cart!`);
    }

    // Close modal and reset
    const modal = bootstrap.Modal.getInstance(document.getElementById('customizeDealModal'));
    if (modal) {
        modal.hide();
    }

    resetDealCustomization();
};

// Calculate total price for current item with addons
const currentItemTotalPrice = computed(() => {
    if (!currentDealMenuItem.value) return 0;

    const basePrice = parseFloat(currentDealMenuItem.value.price || 0);
    const idx = currentDealMenuItemIndex.value;
    const addons = dealSelectedAddons.value[idx];

    if (!addons) return basePrice;

    let addonTotal = 0;
    Object.values(addons).forEach(addonList => {
        addonList.forEach(addon => {
            addonTotal += parseFloat(addon.price || 0);
        });
    });

    return basePrice + addonTotal;
});

// Calculate total deal price with all customizations
const totalDealPriceWithAddons = computed(() => {
    if (!selectedDeal.value) return 0;

    const baseDealPrice = parseFloat(selectedDeal.value.price || 0);
    let totalAddonsPrice = 0;

    // Sum up all addons from all items
    Object.values(dealSelectedAddons.value).forEach(itemAddons => {
        Object.values(itemAddons).forEach(addonList => {
            addonList.forEach(addon => {
                totalAddonsPrice += parseFloat(addon.price || 0);
            });
        });
    });

    return baseDealPrice + totalAddonsPrice;
});

// Get addons total for current item
const currentItemAddonsTotal = computed(() => {
    const idx = currentDealMenuItemIndex.value;
    const addons = dealSelectedAddons.value[idx];

    if (!addons) return 0;

    let total = 0;
    Object.values(addons).forEach(addonList => {
        addonList.forEach(addon => {
            total += parseFloat(addon.price || 0);
        });
    });

    return total;
});

// Open customization modal for a deal
const openDealCustomization = (deal) => {
    selectedDeal.value = deal;
    currentDealMenuItemIndex.value = 0;
    currentDealStep.value = 1;
    dealRemovedIngredients.value = {};
    dealSelectedAddons.value = {};
    dealItemKitchenNotes.value = {};
    completedDealItems.value = [];

    if (!customizeDealModal) {
        const modalEl = document.getElementById('customizeDealModal');
        if (modalEl) {
            customizeDealModal = new bootstrap.Modal(modalEl, {
                backdrop: 'static'
            });
        }
    }

    if (customizeDealModal) {
        customizeDealModal.show();
    }
};

// Reset deal customization state
const resetDealCustomization = () => {
    selectedDeal.value = null;
    currentDealMenuItemIndex.value = 0;
    currentDealStep.value = 1;
    dealRemovedIngredients.value = {};
    dealSelectedAddons.value = {};
    dealItemKitchenNotes.value = {};
    completedDealItems.value = [];
};


// ====================================================
//                  Pending Orders 
// ====================================================
const showPendingOrdersModal = ref(false);
const pendingOrders = ref([]);
const pendingOrdersLoading = ref(false);

// Hold current order as pending
const holdOrderAsPending = async () => {
    if (orderItems.value.length === 0) {
        toast.error("No items in cart to hold");
        return;
    }

    try {
        const payload = {
            customer_name: customer.value,
            phone_number: phoneNumber.value,
            delivery_location: deliveryLocation.value,
            order_type: orderType.value,
            table_number: selectedTable.value?.name || null,
            sub_total: subTotal.value,
            tax: totalTax.value,
            service_charges: serviceCharges.value,
            delivery_charges: deliveryCharges.value,
            sale_discount: totalResaleSavings.value,
            promo_discount: promoDiscount.value,
            approved_discounts: approvedDiscountTotal.value,
            total_amount: grandTotal.value,
            note: note.value,
            kitchen_note: kitchenNote.value,
            order_items: orderItems.value.map(item => ({
                product_id: item.id,
                title: item.title,
                quantity: item.qty,
                price: item.price,
                unit_price: item.unit_price,
                note: item.note || '',
                item_kitchen_note: item.item_kitchen_note || '',
                variant_id: item.variant_id || null,
                variant_name: item.variant_name || null,
                addons: item.addons || [],
                ingredients: item.ingredients || [],
                removed_ingredients: item.removed_ingredients || [],
                sale_discount_per_item: item.resale_discount_per_item || 0,
                total_resale_discount: item.total_resale_discount || 0,
                isDeal: item.isDeal || false,
                dealId: item.dealId || null,
                menu_items: item.menu_items || []
            })),
            applied_promos: selectedPromos.value.map(promo => ({
                promo_id: promo.id,
                promo_name: promo.name,
                promo_type: promo.type,
                discount_amount: promo.applied_discount || 0,
                applied_to_items: promo.applied_to_items || []
            })),
            approved_discount_details: selectedDiscounts.value.map(discount => ({
                discount_id: discount.id,
                discount_name: discount.name,
                discount_percentage: discount.percentage,
                discount_amount: getDiscountAmount(discount.percentage),
                approval_id: discount.approval_id
            })),
            selected_discounts: selectedDiscounts.value,
            terminal_id: terminalId.value
        };

        const response = await axios.post('/pending-orders', payload);

        if (response.data.success) {
            toast.success('Order held successfully!');
            resetCart();
            await fetchPendingOrders();
        }

    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to hold order');
    }
};

// Fetch pending orders
// const fetchPendingOrders = async () => {
//     pendingOrdersLoading.value = true;
//     try {
//         const response = await axios.get('/pending-orders');

//         if (response.data.success) {
//             pendingOrders.value = response.data.data;
//         } else {
//             pendingOrders.value = [];
//         }
//     } catch (error) {
//         toast.error('Failed to load pending orders');
//         pendingOrders.value = [];
//     } finally {
//         pendingOrdersLoading.value = false;
//     }
// };


const fetchPendingOrders = async () => {
    pendingOrdersLoading.value = true;
    try {
        const response = await axios.get('/pending-orders');

        if (response.data.success) {
            pendingOrders.value = response.data.data;
        } else {
            pendingOrders.value = [];
        }
    } catch (error) {
        console.error('Error fetching pending orders:', error);
        toast.error('Failed to load pending orders');
        pendingOrders.value = [];
    } finally {
        pendingOrdersLoading.value = false;
    }
};

// Open pending orders modal
const openPendingOrdersModal = async () => {
    showPendingOrdersModal.value = true;
    await fetchPendingOrders();
};

// Resume pending order
const resumePendingOrder = async (pendingOrder) => {

    try {
        // Restore all cart data
        customer.value = pendingOrder.customer_name || generateCustomerName();
        phoneNumber.value = pendingOrder.phone_number || '';
        deliveryLocation.value = pendingOrder.delivery_location || '';
        orderType.value = pendingOrder.order_type;
        note.value = pendingOrder.note || '';
        kitchenNote.value = pendingOrder.kitchen_note || '';

        // Restore table if exists
        if (pendingOrder.table_number) {
            const table = profileTables.value.table_details?.find(
                t => t.name === pendingOrder.table_number
            );
            selectedTable.value = table || null;
        }

        // Restore order items
        orderItems.value = pendingOrder.order_items.map(item => ({
            id: item.product_id,
            title: item.title,
            qty: item.quantity,
            price: item.price,
            unit_price: item.unit_price,
            note: item.note || '',
            item_kitchen_note: item.item_kitchen_note || '',
            img: item.img || '/assets/img/default.png',
            variant_id: item.variant_id || null,
            variant_name: item.variant_name || null,
            addons: item.addons || [],
            ingredients: item.ingredients || [],
            removed_ingredients: item.removed_ingredients || [],
            resale_discount_per_item: item.sale_discount_per_item || 0,
            total_resale_discount: item.total_resale_discount || 0,
            stock: item.stock || 999999,
            isDeal: item.isDeal || false,
            dealId: item.dealId || null,
            menu_items: item.menu_items || []
        }));

        // Restore promos
        selectedPromos.value = pendingOrder.applied_promos || [];

        // Restore discounts
        selectedDiscounts.value = pendingOrder.selected_discounts || [];

        await axios.delete(`/pending-orders/${pendingOrder.id}`);

        toast.success('Order resumed successfully!');
        showPendingOrdersModal.value = false;
        await fetchPendingOrders();

    } catch (error) {
        toast.error('Failed to resume order');
    }
};

// Reject (delete) pending order
// const rejectPendingOrder = async (pendingOrder) => {
//     try {
//         await axios.delete(`/pending-orders/${pendingOrder.id}`);
//         toast.success('Pending order rejected');
//         await fetchPendingOrders();
//     } catch (error) {
//         toast.error('Failed to reject order');
//     }
// };

const rejectPendingOrder = async (order) => {
    try {
        // Determine which endpoint to use based on order type
        let endpoint = '';
        let payload = {};

        if (order.type === 'unpaid') {
            // Unpaid POS order
            endpoint = `/pending-orders/${order.id}`;
            payload = { order_type: 'unpaid' };
        } else {
            // Held order
            endpoint = `/pending-orders/${order.id}`;
            payload = { order_type: 'held' };
        }

        await axios.delete(endpoint, {
            data: payload
        });

        toast.success('Order rejected successfully');
        await fetchPendingOrders();
    } catch (error) {
        console.error('Error rejecting order:', error);
        toast.error('Failed to reject order');
    }
};

// Check if addon is selected
const isModalAddonSelected = (groupId, addonId) => {
    return modalSelectedAddons.value[groupId]?.some(a => a.id === addonId);
};

// Get addon quantity
const getModalAddonQuantity = (groupId, addonId) => {
    const addon = modalSelectedAddons.value[groupId]?.find(a => a.id === addonId);
    return addon?.quantity || 1;
};

// Toggle addon selection
const toggleModalAddon = (groupId, addon) => {
    if (!modalSelectedAddons.value[groupId]) {
        modalSelectedAddons.value[groupId] = [];
    }

    const index = modalSelectedAddons.value[groupId].findIndex(a => a.id === addon.id);

    if (index > -1) {
        // Remove addon
        modalSelectedAddons.value[groupId].splice(index, 1);
    } else {
        // Check max_select limit
        const addonGroup = selectedItem.value.addon_groups.find(g => g.group_id === groupId);
        if (addonGroup && addonGroup.max_select > 0) {
            if (modalSelectedAddons.value[groupId].length >= addonGroup.max_select) {
                toast.warning(`You can only select up to ${addonGroup.max_select} ${addonGroup.group_name}`);
                return;
            }
        }

        // Add addon with quantity 1
        modalSelectedAddons.value[groupId].push({
            id: addon.id,
            name: addon.name,
            price: parseFloat(addon.price),
            group_id: groupId,
            quantity: 1
        });
    }
};

// Increment addon quantity
const incrementModalAddon = (groupId, addonId) => {
    const addon = modalSelectedAddons.value[groupId]?.find(a => a.id === addonId);
    if (addon) {
        addon.quantity++;
    }
};

// Decrement addon quantity
const decrementModalAddon = (groupId, addonId) => {
    const addon = modalSelectedAddons.value[groupId]?.find(a => a.id === addonId);
    if (addon && addon.quantity > 1) {
        addon.quantity--;
    }
};



// ========================================================
//                Pending Orders without payment
// ========================================================
const showPaymentModal = ref(false);
const selectedUnpaidOrder = ref(null);

const placeOrderWithoutPayment = async () => {
    if (orderItems.value.length === 0) {
        toast.error("Please add at least one item to the cart.");
        return;
    }

    // Same validations as openConfirmModal
    if (orderType.value === "Eat_in" && !selectedTable.value) {
        toast.error("Please select a table number for Dine In orders.");
        return;
    }
    if ((orderType.value === "Delivery" || orderType.value === "Takeaway") && !customer.value) {
        toast.error("Customer name is required.");
        return;
    }
    if (orderType.value === "Delivery" && !phoneNumber.value) {
        toast.error("Phone number is required for delivery.");
        return;
    }
    if (orderType.value === "Delivery" && !deliveryLocation.value) {
        toast.error("Delivery location is required.");
        return;
    }

    try {
        const payload = {
            customer_name: customer.value,
            phone_number: phoneNumber.value,
            delivery_location: deliveryLocation.value,
            sub_total: subTotal.value,
            tax: totalTax.value,
            service_charges: serviceCharges.value,
            delivery_charges: deliveryCharges.value,
            sales_discount: totalResaleSavings.value,
            approved_discounts: approvedDiscountTotal.value,
            approved_discount_details: selectedDiscounts.value.map(discount => ({
                discount_id: discount.id,
                discount_name: discount.name,
                discount_percentage: discount.percentage,
                discount_amount: getDiscountAmount(discount.percentage),
                approval_id: discount.approval_id
            })),
            promo_discount: promoDiscount.value,
            applied_promos: selectedPromos.value.map(promo => ({
                promo_id: promo.id,
                promo_name: promo.name,
                promo_type: promo.type,
                discount_amount: promo.applied_discount || 0,
                applied_to_items: promo.applied_to_items || []
            })),
            total_amount: grandTotal.value,
            note: note.value,
            kitchen_note: kitchenNote.value,
            order_date: new Date().toISOString().split("T")[0],
            order_time: new Date().toTimeString().split(" ")[0],
            order_type: orderType.value === "Eat_in" ? "Eat In" : orderType.value,
            table_number: selectedTable.value?.name || null,
            items: orderItems.value.map((it) => ({
                product_id: it.id,
                title: it.title,
                quantity: it.qty,
                price: it.price,
                note: it.note ?? "",
                kitchen_note: kitchenNote.value ?? "",
                unit_price: it.unit_price,
                item_kitchen_note: it.item_kitchen_note ?? "",
                tax_percentage: getItemTaxPercentage(it),
                tax_amount: getItemTax(it),
                variant_id: it.variant_id || null,
                variant_name: it.variant_name || null,
                addons: it.addons || [],
                sale_discount_per_item: it.resale_discount_per_item || 0,
                removed_ingredients: it.removed_ingredients || [],
                is_deal: it.isDeal || false,
                deal_id: it.dealId || null,
                menu_items: it.menu_items || []
            })),
            confirm_missing_ingredients: true
        };

        const response = await axios.post('/pos/order-without-payment', payload);

        if (response.data.success) {
            toast.success('Order placed successfully! Payment pending.');

            // // Print KOT
            // if (response.data.kot) {
            //     printKot(JSON.parse(JSON.stringify(response.data.order)));
            // }

            // Reset cart
            resetCart();

            // Refresh pending orders
            await fetchPendingOrders();
        }
    } catch (error) {
        console.error('Error placing order:', error);
        toast.error(error.response?.data?.message || 'Failed to place order');
    }
};

// Handle payment for unpaid order
const handlePayUnpaidOrder = (order) => {
    selectedUnpaidOrder.value = order;
    cashReceived.value = parseFloat(order.total_amount).toFixed(2);
    showPaymentModal.value = true;
};

// Complete payment
const completeOrderPayment = async ({ paymentMethod, cashReceived, cardAmount, changeAmount, done }) => {
    try {
        const payload = {
            payment_method: paymentMethod,
            cash_received: cashReceived,
            payment_type: paymentMethod === 'Split' ? 'split' : paymentMethod.toLowerCase(),
            cash_amount: paymentMethod === 'Split' ? cashReceived : null,
            card_amount: paymentMethod === 'Split' ? cardAmount : null,
        };

        const response = await axios.post(
            `/pos/orders/${selectedUnpaidOrder.value.pos_order_id}/complete-payment`,
            payload
        );

        if (response.data.success) {
            toast.success('Payment completed successfully!');

            // Print receipt
            printReceipt(JSON.parse(JSON.stringify(response.data.order)));

            showPaymentModal.value = false;
            selectedUnpaidOrder.value = null;

            // Refresh pending orders
            await fetchPendingOrders();
        }
    } catch (error) {
        console.error('Payment error:', error);
        toast.error(error.response?.data?.message || 'Failed to complete payment');
    } finally {
        if (done) done();
    }
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
                        <div v-if="showCategories" class="row g-3 tablet-screen">
                            <div class="d-flex justify-content-between justify align-items-center mb-3">
                                <h4 class="mb-0">Categories</h4>

                                <button @click="openCustomerDisplay"
                                    class="btn btn-primary d-flex align-items-center gap-2" type="button">
                                    <i class="bi bi-tv"></i>
                                    <span>Customer View</span>
                                </button>
                                <div style="width: 250px; position: relative;">
                                    <input type="email" name="email" autocomplete="email"
                                        style="position: absolute; left: -9999px; width: 1px; height: 1px;"
                                        tabindex="-1" aria-hidden="true" />

                                    <input v-if="isCategorySearchReady" :id="categoryInputId"
                                        v-model="categorySearchQuery" :key="categorySearchKey" type="search"
                                        class="form-control form-control-sm" placeholder="Search categories..."
                                        autocomplete="new-password" :name="categoryInputId" role="presentation"
                                        style="border-radius: 8px; padding: 8px 12px;" />
                                    <input v-else type="text" class="form-control form-control-sm"
                                        placeholder="Search categories..." disabled
                                        style="border-radius: 8px; padding: 8px 12px;" />
                                </div>
                            </div>
                            <hr>
                            </hr>
                            <div v-if="menuCategoriesLoading" class="col-12 text-center py-5">
                                <div class="spinner-border" role="status"
                                    style="color: #1B1670; width: 3rem; height: 3rem; border-width: 0.3em;"></div>
                                <div class="mt-2 fw-semibold text-muted">Loading...</div>
                            </div>
                            <!-- Categories List -->
                            <template v-else>
                                <div v-for="c in filteredCategories" :key="c.id"
                                    class="col-6 col-md-4 col-lg-4 category-cards">
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
                                            {{ getCategoryItemCount(c.id) }} Items
                                            <span v-if="getCategoryDealsCount(c.id) > 0" class="text-danger ms-1">
                                                ({{ getCategoryDealsCount(c.id) }} Deal{{ getCategoryDealsCount(c.id) >
                                                    1 ? 's' : '' }})
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <!-- No Categories Found -->
                                <div v-if="!menuCategoriesLoading && filteredCategories.length === 0" class="col-12">
                                    <div class="alert alert-light border text-center rounded-4">
                                        No categories found
                                    </div>
                                </div>
                            </template>
                        </div>
                        <!-- Items in selected category -->
                        <div v-else>
                            <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
                                <!-- Back Button -->
                                <button class="btn btn-primary rounded-pill shadow-sm px-3" @click="backToCategories()">
                                    <i class="bi bi-arrow-left me-1"></i> Back
                                </button>

                                <!-- Title -->
                                <h5 class="fw-bold mb-0">
                                    {{menuCategories.find((c) => c.id === activeCat)?.name || "Items"}}
                                </h5>

                                <!-- Search & Filter Section (only show for menu items, not deals) -->
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

                            <div v-if="filteredProducts.length === 0" class="col-12">
                                <div class="alert alert-warning border-0 rounded-4 text-center py-5">
                                    <i class="bi bi-search me-2" style="font-size: 2rem; opacity: 0.5;"></i>
                                    <h5 class="mt-2 mb-2 text-dark fw-semibold">No Menu Found</h5>
                                    <p class="text-muted mb-3">
                                        <template
                                            v-if="searchQuery && (filters.priceRange && filters.priceRange.length > 0)">
                                            No items match your search and filter criteria.
                                        </template>
                                        <template v-else-if="searchQuery">
                                            No items found matching "<strong>{{ searchQuery }}</strong>"
                                        </template>
                                        <template v-else-if="filters.priceRange && filters.priceRange.length > 0">
                                            No items match the selected price range.
                                        </template>
                                        <template v-else>
                                            No Menu available in this category.
                                        </template>
                                    </p>
                                    <button class="btn btn-sm btn-outline-secondary rounded-pill"
                                        @click="handleClearFilters">
                                        <i class="bi bi-arrow-clockwise me-1"></i>Clear Filters
                                    </button>
                                </div>
                            </div>



                            <!--  MENU ITEMS GRID (Show when showDeals is false - your existing code) -->
                            <div class="row g-3">
                                <div class="col-12 col-md-6 left-card cat-cards col-xl-6 d-flex"
                                    v-for="p in filteredProducts" :key="p.id">

                                    <!-- DEAL CARD -->
                                    <div v-if="p.isDeal"
                                        class="card rounded-4 shadow-sm overflow-hidden border-3 w-100 d-flex flex-row align-items-stretch"
                                        style="border-color: #dc3545;">

                                        <!-- Left Side (Image + Price Badge) - 40% -->
                                        <div class="position-relative" style="flex: 0 0 40%; max-width: 40%;">
                                            <img :src="p.img" alt="" class="w-100 h-100" style="object-fit: cover;" />

                                            <!-- Deal Price Badge -->
                                            <span
                                                class="position-absolute top-0 start-0 m-1 px-2 py-1 rounded-pill text-white fw-semibold bg-danger"
                                                style="font-size: 0.58rem; letter-spacing: 0.3px;">
                                                <i class="bi bi-gift-fill me-1"></i>
                                                {{ formatCurrencySymbol(p.price) }}
                                            </span>
                                        </div>

                                        <!-- Right Side (Details + Buttons) -->
                                        <div class="p-3 d-flex flex-column justify-content-between"
                                            style="flex: 1 1 60%; min-width: 0; position: relative;">

                                            <div>
                                                <!-- Deal Title -->
                                                <div class="h5 fw-bold mb-2 menu-name text-danger">
                                                    {{ p.title }}
                                                </div>

                                                <!-- Included Items -->
                                                <div class="mb-3">
                                                    <small class="text-muted fw-semibold d-block mb-1"
                                                        style="font-size: 0.75rem;">
                                                        <i class="bi bi-box-seam me-1"></i>Includes:
                                                    </small>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <span v-for="item in p.menu_items" :key="item.id"
                                                            class="badge bg-light text-dark border"
                                                            style="font-size: 0.7rem; font-weight: 500;">
                                                            {{ item.name }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Action Buttons -->
                                            <div class="mt-2">
                                                <!-- Customize Button -->
                                                <button class="btn btn-primary btn-sm w-75 mb-2"
                                                    @click.stop="openDealCustomization(p)">
                                                    <i class="bi bi-sliders me-1"></i>
                                                    Customize Deal
                                                </button>

                                                <!-- Quick Add Controls -->
                                                <div class="d-flex align-items-center justify-content-start gap-2"
                                                    @click.stop>

                                                    <!-- Minus Button -->
                                                    <button
                                                        class="qty-btn btn btn-outline-danger rounded-circle px-2 py-2"
                                                        style="width: 55px; height: 36px;"
                                                        @click.stop="decrementDealQty(p)"
                                                        :disabled="getDealQty(p) <= 0">
                                                        <strong>−</strong>
                                                    </button>

                                                    <!-- Quantity Box -->
                                                    <div class="qty-box border rounded-pill px-2 py-2 text-center fw-semibold left-card-cntrl-btn"
                                                        style="min-width: 55px;">
                                                        {{ getDealQty(p) }}
                                                    </div>

                                                    <!-- Plus Button -->
                                                    <button
                                                        class="qty-btn btn btn-outline-danger rounded-circle px-2 py-2"
                                                        style="width: 55px; height: 36px;"
                                                        @click.stop="incrementDealQty(p)">
                                                        <strong>+</strong>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!--  REGULAR MENU ITEM CARD -->
                                    <div v-else
                                        class="card rounded-4 shadow-sm overflow-hidden border-3 w-100 d-flex flex-row align-items-stretch"
                                        :style="{ borderColor: p.label_color || '#1B1670' }">

                                        <!-- Left Side (Image + Price Badge) - 40% -->
                                        <div class="position-relative" style="flex: 0 0 40%; max-width: 40%;">
                                            <img :src="p.img" alt="" class="w-100 h-100" style="object-fit: cover;" />

                                            <!--  Show Variant Price Range with Resale -->
                                            <div v-if="p.variants && p.variants.length > 0"
                                                class="position-absolute bottom-0 start-0 end-0 text-center bg-light bg-opacity-75 fw-semibold"
                                                style="font-size: 0.6rem !important; padding: 2px 4px;">
                                                <!-- Minimum price -->
                                                <span v-if="getVariantPriceRangeWithResale(p).minOriginal !== null"
                                                    class="text-muted text-decoration-line-through me-1">
                                                    {{
                                                        formatCurrencySymbol(getVariantPriceRangeWithResale(p).minOriginal)
                                                    }}
                                                </span>
                                                <span class="fw-bold text-success me-2">
                                                    {{ formatCurrencySymbol(getVariantPriceRangeWithResale(p).min) }} -
                                                </span>

                                                <!-- Maximum price -->
                                                <span v-if="getVariantPriceRangeWithResale(p).maxOriginal !== null"
                                                    class="text-muted text-decoration-line-through me-1">
                                                    {{
                                                        formatCurrencySymbol(getVariantPriceRangeWithResale(p).maxOriginal)
                                                    }}
                                                </span>
                                                <span class="fw-bold text-success">
                                                    {{ formatCurrencySymbol(getVariantPriceRangeWithResale(p).max) }}
                                                </span>
                                            </div>

                                            <!--  Dynamic Price Badge with Resale -->
                                            <span
                                                class="position-absolute top-0 start-0 m-1 px-2 py-1 rounded-pill text-white fw-semibold"
                                                :style="{ background: p.label_color || '#1B1670', fontSize: '0.58rem', letterSpacing: '0.3px' }">
                                                <template
                                                    v-if="getResaleBadgeInfo(getSelectedVariant(p) || p, !!p.variants?.length)">
                                                    <span class="text-decoration-line-through opacity-75 me-1">
                                                        {{ formatCurrencySymbol(getSelectedVariant(p)?.price || p.price)
                                                        }}
                                                    </span>
                                                    <span class="fw-bold text-white">
                                                        {{ formatCurrencySymbol(getTotalPriceWithResale(p)) }}
                                                    </span>
                                                </template>

                                                <template v-else>
                                                    {{ formatCurrencySymbol(getSelectedVariant(p)?.price || p.price) }}
                                                </template>
                                            </span>

                                            <!-- OUT OF STOCK Badge -->
                                            <span v-if="getProductStock(p) <= 0"
                                                class="position-absolute bottom-0 start-0 end-0 m-2 badge bg-danger py-2">
                                                OUT OF STOCK
                                            </span>
                                        </div>

                                        <!-- Right Side (Details + Variant + Addons + Quantity Controls) -->
                                        <div class="p-3 d-flex flex-column justify-content-between"
                                            style="flex: 1 1 60%; min-width: 0; position: relative;">

                                            <!-- Right Section Sale Badge (top-right, slightly up & right) -->
                                            <span
                                                v-if="(p.variants && p.variants.length > 0 && getResaleBadgeInfo(getSelectedVariant(p), true)) ||
                                                    (!p.variants || p.variants.length === 0) && getResaleBadgeInfo(p, false)"
                                                class="position-absolute px-2 py-1 rounded-pill bg-success text-white small fw-bold"
                                                :style="{
                                                    fontSize: '0.7rem',
                                                    top: '7px',
                                                    right: '7px'
                                                }">
                                                {{ p.variants && p.variants.length > 0
                                                    ? getResaleBadgeInfo(getSelectedVariant(p), true).display
                                                    : getResaleBadgeInfo(p, false).display }}
                                            </span>

                                            <div>
                                                <div class="h5 fw-bold mb-2 menu-name"
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
                                                            <template
                                                                v-if="variant.is_saleable && calculateResalePrice(variant, true) > 0">
                                                                (Sale: {{
                                                                    formatCurrencySymbol(calculateResalePrice(variant,
                                                                        true)) }})
                                                            </template>
                                                        </option>
                                                    </select>
                                                </div>

                                                <!-- Addons Selection -->
                                                <div v-if="p.addon_groups && p.addon_groups.length > 0" class="mb-3">
                                                    <label class="form-label small fw-semibold mb-2">
                                                        Add-ons
                                                    </label>
                                                    <MultiSelect :modelValue="getAllSelectedAddonsForProduct(p)"
                                                        @update:modelValue="(val) => handleAllAddonsChange(p, val)"
                                                        :options="getAllAddonsForProduct(p)" optionLabel="name"
                                                        dataKey="id" placeholder="Select addons" :maxSelectedLabels="3"
                                                        class="w-100 addon-multiselect" display="chip">
                                                        <template #value="slotProps">
                                                            <div v-if="slotProps.value && slotProps.value.length > 0"
                                                                class="d-flex flex-wrap gap-1">
                                                                <span v-for="addon in slotProps.value" :key="addon.id"
                                                                    class="badge bg-primary">
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
                                                                <div>
                                                                    <div>{{ slotProps.option.name }}</div>
                                                                    <small class="text-muted">{{
                                                                        slotProps.option.group_name }}</small>
                                                                </div>
                                                                <span class="fw-semibold text-success">
                                                                    +{{ formatCurrencySymbol(slotProps.option.price) }}
                                                                </span>
                                                            </div>
                                                        </template>
                                                    </MultiSelect>
                                                </div>

                                                <!-- View Details Button -->
                                                <button class="btn btn-primary btn-sm mb-2 view-details-btn"
                                                    @click="openDetailsModal(p)">
                                                    Customization
                                                </button>
                                            </div>

                                            <!--  Quantity Controls (ALWAYS SHOW, but disable when out of stock) -->
                                            <div class="mt-2 d-flex align-items-center justify-content-start gap-2"
                                                @click.stop>
                                                <button
                                                    class="qty-btn btn btn-outline-secondary rounded-circle px-2 py-2"
                                                    style="width: 55px; height: 36px;" @click.stop="decrementCardQty(p)"
                                                    :disabled="getCardQty(p) <= 0">
                                                    <strong>−</strong>
                                                </button>
                                                <div class="qty-box border rounded-pill px-2 py-2 text-center fw-semibold left-card-cntrl-btn"
                                                    style="min-width: 55px;">
                                                    {{ getCardQty(p) }}
                                                </div>
                                                <button
                                                    class="qty-btn btn btn-outline-secondary rounded-circle px-2 py-2"
                                                    style="width: 55px; height: 36px;"
                                                    @click.stop="incrementCardQty(p)">
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
                    <div class="col-lg-4 right-cart" v-if="!showCategories">

                        <div class="col-12 d-flex align-items-center justify-content-end gap-2 mb-2">
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
                            < </button> -->
                            <button class="btn btn-info px-3 py-2" @click="openPendingOrdersModal">
                                Pending
                                <span v-if="pendingOrders.length > 0" class="badge bg-danger ms-1">
                                    {{ pendingOrders.length }}
                                </span>
                            </button>
                            <button class="btn btn-warning px-3 py-2 promos-btn" @click="openPromoModal">
                                Promos
                            </button>
                            <button class="btn btn-warning px-3 py-2 discount-btn" @click="openDiscountModal">
                                Discounts
                            </button>
                        </div>

                        <div class="cart card border-0 shadow-lg rounded-4">

                            <div class="cart-header">

                                <div class="order-type">
                                    <button v-for="(type, i) in orderTypes" :key="i"
                                        class="ot-pill btn cart-header-buttons" :class="{ active: orderType === type }"
                                        @click="orderType = type">
                                        {{ type.replace(/_/g, " ") }}
                                    </button>
                                    <div class="d-flex justify-content-between mb-3">

                                    </div>
                                </div>
                            </div>

                            <div class="cart-body">
                                <!-- Dine-in table / customer -->
                                <div class="mb-3">

                                    <div v-if="orderType === 'Eat_in'" class="row g-2 ">
                                        <div class="col-6 table-dropdown">
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
                                        <div class="col-6 table-dropdown">
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

                                    <!-- if delivery then show location and phone no  -->
                                    <div v-if="orderType === 'Delivery'" class="mt-2">
                                        <label class="form-label small">Delivery Location</label>
                                        <input v-model="deliveryLocation" class="form-control form-control-sm"
                                            placeholder="Enter delivery location" />
                                        <div v-if="formErrors.delivery_location" class="invalid-feedback d-block">
                                            {{ formErrors.delivery_location[0] }}
                                        </div>
                                        <label class="form-label small mt-2">Phone Number</label>
                                        <input v-model="phoneNumber" class="form-control form-control-sm"
                                            placeholder="Enter phone number" />
                                        <div v-if="formErrors.phone_number" class="invalid-feedback d-block">
                                            {{ formErrors.phone_number[0] }}
                                        </div>
                                    </div>
                                </div>

                                <div class="cart-lines">
                                    <div v-if="orderItems.length === 0" class="empty">
                                        Add items from the left
                                    </div>

                                    <div v-for="(it, i) in orderItems" :key="it.title" class="line">
                                        <!-- Left: Image + Meta -->
                                        <!-- {{ it.title.length > 4 ? it.title.slice(0, 10) + '...' : it.title }} -->
                                        <div class="line-left">
                                            <img :src="it.img" alt="" />
                                            <div class="meta">
                                                <div class="name" :title="it.title">
                                                    {{ it.title }}
                                                </div>
                                                <div v-if="it.resale_discount_per_item > 0">
                                                    <span class="badge bg-success" style="font-size: 0.65rem;">
                                                        <i class="bi bi-tag-fill"></i>
                                                        Save {{ formatCurrencySymbol(it.resale_discount_per_item) }}

                                                    </span>
                                                </div>
                                                <!-- Addon Icons (clickable) -->


                                                <div v-if="it.addons && it.addons.length > 0" class="addon-icons mt-1">
                                                    <button class="btn-link p-0 py-0 text-decoration-none"
                                                        @click="openAddonModal(it, i)" :title="getAddonTooltip(it)">
                                                        <i class="bi bi-plus-circle-fill text-primary me-1"
                                                            style="font-size: 0.9rem;"></i>
                                                        <span class="text-muted small">{{ it.addons.length }}
                                                            addon(s)</span>
                                                    </button>
                                                </div>

                                                <span v-if="isItemEligibleForPromo(it)"
                                                    class="badge bg-success mt-1 ms-1" style="font-size: 0.65rem;">
                                                    <i class="bi bi-tag-fill"></i>
                                                </span>

                                                <!-- Show variant name badge (always show if exists) -->
                                                <div v-if="it.variant_name" class="mt-1">
                                                    <span class="badge"
                                                        style="font-size: 0.65rem; background: #1B1670; color: white;">
                                                        {{ it.variant_name }}
                                                    </span>
                                                </div>

                                                <div class="note" v-if="it.note">
                                                    {{ it.note }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Right: Controls + Price (Stacked Vertically) -->
                                        <div class="line-right">
                                            <!-- Quantity Controls on Top -->
                                            <div class="qty-controls">
                                                <button class="qty-btn" @click="decCart(i)">−</button>
                                                <div class="qty">{{ it.qty }}</div>
                                                <button class="qty-btn" @click="incCart(i)">+</button>
                                            </div>

                                            <!-- Price and Delete Below -->
                                            <div class="price-delete">
                                                <div class="price">
                                                    {{ formatCurrencySymbol(it.price) }}
                                                </div>
                                                <button class="del" @click="removeCart(i)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Totals -->
                                <div class="totals">
                                    <!-- Sub Total -->
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

                                    <!-- Service Charges -->
                                    <div v-if="serviceCharges > 0" class="trow">
                                        <span class="text-muted">Service Charges:</span>
                                        <span class="fw-semibold">{{ formatCurrencySymbol(serviceCharges) }}</span>
                                    </div>

                                    <!-- Delivery Charges -->
                                    <div v-if="deliveryCharges > 0" class="trow">
                                        <span class="text-muted">Delivery Charges:</span>
                                        <span class="fw-semibold">{{ formatCurrencySymbol(deliveryCharges) }}</span>
                                    </div>

                                    <!-- Sale Discount -->
                                    <div v-if="totalResaleSavings > 0" class="trow">
                                        <span class="d-flex align-items-center gap-2">
                                            <!-- <i class="bi bi-tag text-danger"></i> -->
                                            <span class="text-danger">Sale Discount:</span>
                                        </span>
                                        <b class="text-danger">-{{ formatCurrencySymbol(totalResaleSavings) }}</b>
                                    </div>

                                    <!-- Promo Discount -->
                                    <div v-if="selectedPromos && selectedPromos.length > 0" class="promos-section mb-3">
                                        <div class="promo-total mt-2 rounded-3 bg-success-subtle">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-success">Promo Discount:</span>
                                                <b class="text-success fs-6">-{{ formatCurrencySymbol(promoDiscount)
                                                }}</b>
                                            </div>
                                        </div>
                                    </div>

                                    <!--  Pending Percentage Discounts (NOT deducted yet) -->
                                    <div v-if="pendingDiscountApprovals.length > 0"
                                        class="pending-discounts-section mb-3">
                                        <div class="alert alert-warning py-2 px-3 mb-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-clock-history"></i>
                                                <small class="fw-semibold">Pending Approval</small>
                                            </div>
                                        </div>
                                        <div v-for="approval in pendingDiscountApprovals" :key="approval.id"
                                            class="trow">
                                            <span class="d-flex align-items-center gap-2 text-warning">
                                                <i class="bi bi-hourglass-split"></i>
                                                <span>{{ approval.discountData?.name || 'Discount' }} ({{
                                                    approval.percentage }}%)</span>
                                            </span>
                                            <span class="text-warning">
                                                -{{ formatCurrencySymbol(getDiscountAmount(approval.percentage)) }}
                                            </span>
                                        </div>
                                        <small class="text-muted d-block mt-1 ms-4">
                                            <i class="bi bi-info-circle me-1"></i>
                                            Will apply after Super Admin approval
                                        </small>
                                    </div>
                                    <!--  Approved Percentage Discounts (Dynamically calculated) -->
                                    <div v-if="selectedDiscounts.length > 0" class="approved-discounts-section mb-3">
                                        <!-- Total Approved Discount -->
                                        <div class="trow border-top pt-2 mt-2">
                                            <div class="d-flex align-items-center text-success gap-2">
                                                <i class="bi bi-check-circle"></i>
                                                <small class="fw-semibold">Approved Discounts</small>

                                                <!-- NEW: Info Icon with Popover -->
                                                <i class="bi bi-info-circle text-muted" tabindex="0" role="button"
                                                    data-bs-toggle="popover" data-bs-trigger="click" data-bs-html="true"
                                                    data-bs-placement="top" data-bs-container="body"
                                                    data-bs-content="Auto-updates with cart changes<br>Approved Discounts">
                                                </i>
                                            </div>
                                            <b class="text-success">-{{ formatCurrencySymbol(approvedDiscountTotal)
                                            }}</b>
                                        </div>
                                    </div>
                                    <!-- Total After All Discounts -->
                                    <div class="trow total">
                                        <span>Total</span>
                                        <b>{{ formatCurrencySymbol(grandTotal) }}</b>
                                    </div>
                                </div>



                                <div class="">
                                    <Accordion value="0">
                                        <AccordionPanel value="0">
                                            <AccordionHeader>Front Note</AccordionHeader>
                                            <AccordionContent>
                                                <div class="mb-3">
                                                    <textarea id="frontNote" v-model="note" rows="3"
                                                        class="form-control form-control-sm rounded-3"
                                                        placeholder="Enter front note..."></textarea>
                                                </div>

                                            </AccordionContent>
                                        </AccordionPanel>
                                        <AccordionPanel value="1">
                                            <AccordionHeader>Kitchen Note</AccordionHeader>
                                            <AccordionContent>
                                                <div class="mb-3">
                                                    <textarea id="kitchenNote" v-model="kitchenNote" rows="3"
                                                        class="form-control form-control-sm rounded-3"
                                                        placeholder="Enter kitchen note..."></textarea>
                                                </div>
                                            </AccordionContent>
                                        </AccordionPanel>
                                    </Accordion>
                                </div>




                            </div>
                            <div class="cart-footer">
                                <button class="btn btn-secondary btn-clear" @click="resetCart()">
                                    Clear
                                </button>
                                <button class="btn btn-warning" @click="holdOrderAsPending">
                                    Pending
                                </button>
                                <button class="btn btn-primary btn-place" @click="openConfirmModal">
                                    Place Order
                                </button>
                                <button class="btn btn-info" @click="placeOrderWithoutPayment">
                                    <i class="bi bi-clock me-1"></i>
                                    Pay Later
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Choose Item Modal -->
            <!-- <div class="modal fade" id="chooseItem" tabindex="-1" aria-hidden="true">
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
                                    <div class="h4 mb-3">
                                        <div class="h4 d-flex align-items-center gap-2 flex-wrap">
                                            <span>{{ formatCurrencySymbol(getModalTotalPriceWithResale()) }}</span>
                                            <template
                                                v-if="getModalSelectedVariant() && getResaleBadgeInfo(getModalSelectedVariant(), true)">
                                                <span class="text-decoration-line-through text-muted small">
                                                    {{ formatCurrencySymbol(getModalVariantPrice() +
                                                        getModalAddonsPrice()) }}
                                                </span>
                                                <span class="badge bg-success">
                                                    {{ getResaleBadgeInfo(getModalSelectedVariant(), true).display }}
                                                </span>
                                            </template>
                                            <template
                                                v-else-if="selectedItem && (!selectedItem.variants || selectedItem.variants.length === 0) && getResaleBadgeInfo(selectedItem, false)">
                                                <span class="text-decoration-line-through text-muted small">
                                                    {{ formatCurrencySymbol(getModalVariantPrice() +
                                                        getModalAddonsPrice()) }}
                                                </span>
                                                <span class="badge bg-success">
                                                    {{ getResaleBadgeInfo(selectedItem, false).display }}
                                                </span>
                                            </template>
                                        </div>
                                    </div>
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
                                    <div v-if="getModalIngredients().length > 0" class="mb-3">
                                        <label class="form-label small fw-semibold mb-2 d-flex align-items-center">
                                            <i class="bi bi-egg-fried me-2 text-warning"></i>
                                            Remove Ingredients
                                        </label>

                                        <div class="border rounded-3 p-3 bg-light"
                                            style="max-height: 180px; overflow-y: auto;">
                                            <div v-for="ingredient in getModalIngredients()"
                                                :key="ingredient.id || ingredient.inventory_item_id"
                                                class="form-check d-flex align-items-center justify-content-between py-2 px-2 mb-1 rounded hover-bg-white"
                                                style="cursor: pointer;">

                                                <div class="d-flex align-items-center flex-grow-1 gap-3">
                                                    <input type="checkbox" class="form-check-input"
                                                        :id="'ingredient-' + (ingredient.id || ingredient.inventory_item_id)"
                                                        :checked="!isIngredientRemoved(ingredient.id || ingredient.inventory_item_id)"
                                                        @change="toggleIngredient(ingredient.id || ingredient.inventory_item_id)"
                                                        style="cursor: pointer; width: 20px; height: 20px; margin: 0; flex-shrink: 0;">

                                                    <label
                                                        :for="'ingredient-' + (ingredient.id || ingredient.inventory_item_id)"
                                                        class="form-check-label mb-0 flex-grow-1" :class="{
                                                            'text-decoration-line-through text-muted opacity-50': isIngredientRemoved(ingredient.id || ingredient.inventory_item_id)
                                                        }" style="cursor: pointer;">
                                                        {{ ingredient.product_name || ingredient.name || 'Ingredient' }}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
                                                class="w-100 addon-multiselect" appendTo="self">
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
                                            class="chip chip-red">{{
                                                a.name
                                            }}</span>

                                        <div class="w-100 mt-2">
                                            <strong>Tags:</strong>
                                        </div>
                                        <span v-for="(t, i) in getModalTags()" :key="'t-' + i" class="chip chip-teal">{{
                                            t.name }}</span>
                                    </div>

                                    <div class="qty-group gap-1">
                                        <button class="qty-btn" @click="decQty">−</button>
                                        <div class="qty-box rounded-pill">
                                            {{ modalQty }}
                                        </div>
                                        <button class="qty-btn" @click="incQty">+</button>
                                    </div>

                                    <div class="mt-3">
                                        <label class="form-label small fw-semibold mb-1">
                                            Kitchen Note (Optional)
                                        </label>
                                        <textarea v-model="modalItemKitchenNote" class="form-control form-control-sm"
                                            rows="4"
                                            placeholder="Special instructions to kitchen for this item (e.g., extra spicy, no onions)..."
                                            maxlength="200"></textarea>
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
            </div> -->


            <div class="modal fade" id="chooseItem" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content rounded-3 shadow border-0" style="max-height: 90vh;">

                        <!-- HEADER -->
                        <div class="modal-header border-0 bg-light py-2 px-3 rounded-top">
                            <div>
                                <h5 class="fw-bold mb-0">
                                    {{ selectedItem?.title || "Choose Item" }}
                                </h5>
                                <p class="text-muted small mb-0" style="font-size: 0.8rem;">Customize your order</p>
                            </div>
                            <button
                                class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                                data-bs-dismiss="modal" aria-label="Close" title="Close">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- STEP INDICATOR -->
                        <div class="px-3 pt-2 progress-bar">
                            <div class="stepper-container position-relative" style="min-height: 60px;">
                                <!-- Background line -->
                                <div class="stepper-progress-bg"></div>
                                <!-- Filled line -->
                                <div class="stepper-progress-fill" :style="{ width: stepProgressWidth }"></div>

                                <!-- Steps -->
                                <div v-for="(step, index) in visibleSteps" :key="step.id"
                                    class="stepper-step text-center">
                                    <div class="stepper-circle stepper-circle-sm"
                                        :class="getStepCircleClass(step.stepNumber)" @click="goToStep(step.stepNumber)"
                                        style="cursor: pointer;">
                                        <i v-if="step.stepNumber < currentStep" class="bi bi-check-lg"
                                            style="font-size: 0.7rem;"></i>
                                        <span v-else style="font-size: 0.75rem;">{{ step.stepNumber }}</span>
                                    </div>
                                    <small class="stepper-label"
                                        :class="{ 'stepper-label-active': step.stepNumber === currentStep }"
                                        style="font-size: 0.7rem; margin-top: 4px; display: block; cursor: pointer;"
                                        @click="goToStep(step.stepNumber)">
                                        {{ step.name }}
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- BODY -->
                        <div class="modal-body px-3 py-2">
                            <div class="row g-3 mb-3">

                                <!-- LEFT COLUMN : IMAGE + PRICE -->
                                <div class="col-lg-5">
                                    <!-- Image -->
                                    <div class="position-relative mb-3">
                                        <img :src="selectedItem?.image_url || selectedItem?.img || '/assets/img/product/product29.jpg'"
                                            class="img-fluid rounded-3 w-100 object-fit-cover"
                                            style="height: 200px; object-fit: cover;" alt="Product">

                                        <span
                                            v-if="getModalSelectedVariant() && getResaleBadgeInfo(getModalSelectedVariant(), true)"
                                            class="badge bg-danger px-2 py-1 position-absolute top-0 end-0 rounded-end-0 rounded-bottom-0 shadow-sm"
                                            style="font-size: 0.7rem;">
                                            <i class="bi bi-tag-fill me-1"></i>
                                            {{ getResaleBadgeInfo(getModalSelectedVariant(), true).display }}
                                        </span>
                                    </div>

                                    <!-- Price Summary -->
                                    <div class="rounded-3 border shadow-sm p-3 bg-white">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="bi bi-receipt me-2 text-primary" style="font-size: 1rem;"></i>
                                            <h6 class="fw-bold mb-0" style="font-size: 0.9rem;">Price Breakdown</h6>
                                        </div>

                                        <div class="d-flex justify-content-between mb-1" style="font-size: 0.85rem;">
                                            <span class="text-muted">Base Price</span>
                                            <strong>{{ formatCurrencySymbol(getModalVariantPrice()) }}</strong>
                                        </div>

                                        <div v-if="getModalAddonsPrice() > 0"
                                            class="d-flex justify-content-between mb-1" style="font-size: 0.85rem;">
                                            <span class="text-muted">Add-ons</span>
                                            <strong class="text-success">+ {{
                                                formatCurrencySymbol(getModalAddonsPrice())
                                            }}</strong>
                                        </div>

                                        <hr class="my-2">

                                        <div class="d-flex justify-content-between" style="font-size: 1rem;">
                                            <span class="fw-bold">Total</span>
                                            <span class="fw-bold text-primary">{{
                                                formatCurrencySymbol(getModalTotalPriceWithResale()) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- RIGHT COLUMN : STEPS -->
                                <div class="col-lg-7">
                                    <div class="px-1">

                                        <!-- STEP 1 : VARIANTS -->
                                        <div v-show="currentStep === 1 && hasVariants" class="step-content">
                                            <div class="d-flex align-items-start mb-2">
                                                <i class="bi bi-grid-3x3-gap me-2 text-primary"
                                                    style="font-size: 1.1rem;"></i>
                                                <div>
                                                    <h6 class="fw-bold mb-0" style="font-size: 0.9rem;">Choose Variant
                                                    </h6>
                                                </div>
                                            </div>

                                            <div class="row g-2">
                                                <div v-for="variant in selectedItem?.variants" :key="variant.id"
                                                    class="col-12">
                                                    <div :class="['border rounded-3 p-2 shadow-sm variant-card',
                                                        modalSelectedVariant === variant.id ? 'border-primary bg-light' : '']"
                                                        @click="selectVariant(variant.id)" style="cursor: pointer;">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <h6 class="fw-bold mb-0" style="font-size: 0.85rem;">{{
                                                                    variant.name
                                                                }}</h6>
                                                            </div>
                                                            <div
                                                                class="d-flex justify-content-between align-items-center gap-2">
                                                                <div v-if="modalSelectedVariant === variant.id"
                                                                    class="text-primary">
                                                                    <i class="bi bi-check-circle-fill"
                                                                        style="font-size: 1rem;"></i>
                                                                </div>
                                                                <strong style="font-size: 0.9rem;">{{
                                                                    formatCurrencySymbol(variant.price) }}</strong>

                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- STEP 2 : INGREDIENTS -->
                                        <div v-show="currentStep === 2 && hasIngredients" class="step-content">
                                            <div class="d-flex align-items-start mb-2">
                                                <div>
                                                    <h6 class="fw-bold mb-0" style="font-size: 0.9rem;">Remove
                                                        Ingredients</h6>
                                                </div>
                                            </div>

                                            <div class="row g-2">
                                                <div v-for="ingredient in getModalIngredients()"
                                                    :key="ingredient.id || ingredient.inventory_item_id"
                                                    class="col-md-6">
                                                    <div class="d-flex align-items-center p-2 border rounded-2 shadow-sm"
                                                        :class="{ 'bg-light text-muted': isIngredientRemoved(ingredient.id || ingredient.inventory_item_id) }">

                                                        <input type="checkbox" class="form-check-input me-2"
                                                            style="width: 16px; height: 16px;"
                                                            :id="'ingredient-' + (ingredient.id || ingredient.inventory_item_id)"
                                                            :checked="!isIngredientRemoved(ingredient.id || ingredient.inventory_item_id)"
                                                            :disabled="!isIngredientRemoved(ingredient.id || ingredient.inventory_item_id) && getRemainingIngredientsCount() === 1"
                                                            @change="toggleIngredient(ingredient.id || ingredient.inventory_item_id)">

                                                        <label
                                                            :for="'ingredient-' + (ingredient.id || ingredient.inventory_item_id)"
                                                            class="form-check-label" style="font-size: 0.8rem;">
                                                            {{ ingredient.product_name || ingredient.name }}
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>

                                        <!-- STEP 3 : ADD-ONS -->
                                        <div v-show="currentStep === 3 && hasAddons" class="step-content">
                                            <div class="d-flex align-items-start mb-3">
                                                <i class="bi bi-plus-circle me-2 text-success"
                                                    style="font-size: 1.1rem;"></i>
                                                <div class="flex-grow-1">
                                                    <h6 class="fw-bold mb-0" style="font-size: 0.9rem;">Add Add-ons</h6>
                                                    <p class="text-muted mb-0" style="font-size: 0.75rem;">Enhance your
                                                        meal</p>
                                                </div>
                                            </div>

                                            <!-- Addon Group Progress Indicator -->
                                            <div v-if="selectedItem?.addon_groups?.length > 1" class="mb-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <small class="text-muted" style="font-size: 0.75rem;">
                                                        Group {{ currentAddonGroupIndex + 1 }} of {{
                                                            selectedItem?.addon_groups?.length }}
                                                    </small>
                                                    <div class="d-flex gap-1">
                                                        <span v-for="(g, idx) in selectedItem?.addon_groups" :key="idx"
                                                            class="rounded-circle"
                                                            :class="idx === currentAddonGroupIndex ? 'bg-primary' : 'bg-secondary'"
                                                            style="width: 8px; height: 8px; display: inline-block; opacity: 0.6;"></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Current Addon Group -->
                                            <div v-if="currentAddonGroup" class="mb-3">
                                                <div class="bg-light rounded-3 p-1 mb-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-0">
                                                        <h6 class="fw-bold mb-0" style="font-size: 0.95rem;">
                                                            {{ currentAddonGroup.group_name }}
                                                        </h6>
                                                        <span v-if="currentAddonGroup.max_select > 0"
                                                            class="badge rounded-pill bg-primary"
                                                            style="font-size: 0.7rem;">
                                                            Max {{ currentAddonGroup.max_select }}
                                                        </span>
                                                    </div>
                                                    <p v-if="currentAddonGroup.description"
                                                        class="text-muted small mb-0" style="font-size: 0.75rem;">
                                                        {{ currentAddonGroup.description }}
                                                    </p>
                                                </div>

                                                <div class="row g-2">
                                                    <div v-for="addon in currentAddonGroup.addons" :key="addon.id"
                                                        class="col-12">
                                                        <div class="border rounded-2 p-2 shadow-sm d-flex align-items-center justify-content-between"
                                                            :class="{ 'bg-light border-success': isModalAddonSelected(currentAddonGroup.group_id, addon.id) }">

                                                            <!-- Checkbox & Name -->
                                                            <div class="d-flex align-items-center flex-grow-1">
                                                                <input type="checkbox" class="form-check-input me-2"
                                                                    style="width: 18px; height: 18px; cursor: pointer;"
                                                                    :id="'addon-' + addon.id"
                                                                    :checked="isModalAddonSelected(currentAddonGroup.group_id, addon.id)"
                                                                    @change="toggleModalAddon(currentAddonGroup.group_id, addon)">

                                                                <label :for="'addon-' + addon.id"
                                                                    class="form-check-label mb-0"
                                                                    style="font-size: 0.85rem; cursor: pointer;">
                                                                    {{ addon.name }}
                                                                    <span class="text-success fw-semibold ms-1">
                                                                        +{{ formatCurrencySymbol(addon.price) }}
                                                                    </span>
                                                                </label>
                                                            </div>

                                                            <!-- Quantity Controls -->
                                                            <div v-if="isModalAddonSelected(currentAddonGroup.group_id, addon.id)"
                                                                class="d-flex align-items-center gap-1">

                                                                <!-- Minus -->
                                                                <button class="btn btn-primary rounded-circle p-0"
                                                                    style="width: 35px; height: 10px;"
                                                                    @click="decrementModalAddon(currentAddonGroup.group_id, addon.id)"
                                                                    :disabled="getModalAddonQuantity(currentAddonGroup.group_id, addon.id) <= 1">
                                                                    <i class="bi bi-dash"
                                                                        style="font-size: 0.9rem;"></i>
                                                                </button>

                                                                <!-- Quantity -->
                                                                <span class="fw-bold"
                                                                    style="min-width: 20px; text-align: center; font-size: 0.75rem;">
                                                                    {{ getModalAddonQuantity(currentAddonGroup.group_id,
                                                                        addon.id)
                                                                    }}
                                                                </span>

                                                                <!-- Plus -->
                                                                <button class="btn btn-primary rounded-circle p-0"
                                                                    style="width: 35px; height: 10px;"
                                                                    @click="incrementModalAddon(currentAddonGroup.group_id, addon.id)">
                                                                    <i class="bi bi-plus"
                                                                        style="font-size: 0.9rem;"></i>
                                                                </button>

                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Navigation Buttons for Addon Groups -->
                                            <div v-if="selectedItem?.addon_groups?.length > 1"
                                                class="d-flex justify-content-between gap-2 mt-3">
                                                <button class="btn btn-primary btn-sm px-3" @click="previousAddonGroup"
                                                    :disabled="currentAddonGroupIndex === 0">
                                                    <i class="bi bi-chevron-left me-1"></i>
                                                    Previous
                                                </button>

                                                <button class="btn btn-primary btn-sm px-3" @click="nextAddonGroup"
                                                    :disabled="currentAddonGroupIndex >= selectedItem?.addon_groups?.length - 1">
                                                    Next
                                                    <i class="bi bi-chevron-right ms-1"></i>
                                                </button>
                                            </div>

                                            <!-- Skip Add-ons Button -->
                                            <div class="text-center mt-3">
                                                <button class="btn btn-link btn-sm text-muted" @click="skipToReview">
                                                    <small>Skip add-ons</small>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- STEP 4 : REVIEW -->
                                        <div v-show="currentStep === finalStep" class="step-content">
                                            <div class="d-flex align-items-start mb-2">
                                                <i class="bi bi-cart-check me-2 text-success"
                                                    style="font-size: 1.1rem;"></i>
                                                <div>
                                                    <h6 class="fw-bold mb-0" style="font-size: 0.9rem;">Review Your
                                                        Order</h6>
                                                    <p class="text-muted mb-0" style="font-size: 0.75rem;">Confirm
                                                        selections</p>
                                                </div>
                                            </div>

                                            <!-- Summary -->
                                            <div class="summary-card p-3 rounded-3 shadow-sm mb-3">
                                                <h6 class="fw-bold mb-2 text-secondary">Order Summary</h6>

                                                <div class="summary-item" v-if="hasVariants">
                                                    <span class="summary-label">Variant:</span>
                                                    <span class="summary-value">{{ getSelectedVariantName() }}</span>
                                                </div>

                                                <div class="summary-item" v-if="modalRemovedIngredients.length > 0">
                                                    <span class="summary-label">Removed:</span>
                                                    <span class="summary-value">{{ getRemovedIngredientsText() }}</span>
                                                </div>

                                                <div class="summary-item" v-if="getSelectedAddonsCount() > 0">
                                                    <span class="summary-label">Add-ons:</span>
                                                    <span class="summary-value">{{ getSelectedAddonsText() }}</span>
                                                </div>
                                            </div>


                                            <!-- Quantity -->
                                            <div class="d-flex align-items-center mb-3">
                                                <label class="me-3 fw-semibold"
                                                    style="font-size: 0.85rem;">Quantity:</label>
                                                <div class="qty-group gap-1">
                                                    <button class="qty-btn" @click="decQty"
                                                        :disabled="modalQty <= 1">−</button>
                                                    <div class="qty-box rounded-pill">{{ modalQty }}</div>
                                                    <button class="qty-btn" @click="incQty">+</button>
                                                </div>
                                            </div>

                                            <!-- Kitchen Notes -->
                                            <div class="mb-2">
                                                <label class="fw-semibold mb-1" style="font-size: 0.85rem;">
                                                    <i class="bi bi-chat-left-text me-1"></i>
                                                    Kitchen Note (Optional)
                                                </label>
                                                <textarea v-model="modalItemKitchenNote"
                                                    class="form-control form-control-sm rounded-2 shadow-sm" rows="2"
                                                    maxlength="200" placeholder="Special instructions..."
                                                    style="font-size: 0.8rem;"></textarea>
                                                <div class="text-end small text-muted mt-1" style="font-size: 0.7rem;">
                                                    {{ modalItemKitchenNote?.length || 0 }}/200
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- FOOTER -->
                        <div class="modal-footer border-0 px-3 py-2 d-flex justify-content-end gap-2">
                            <button v-if="currentStep > 1" class="btn btn-light btn-sm px-4 py-2 shadow-sm"
                                @click="previousStep">
                                <i class="bi bi-arrow-left me-1"></i>Back
                            </button>

                            <button v-if="currentStep < finalStep" class="btn btn-primary btn-sm px-4 py-2 shadow-sm"
                                @click="nextStep">
                                Next <i class="bi bi-arrow-right ms-1"></i>
                            </button>

                            <button v-else class="btn btn-primary btn-sm px-4 py-2 shadow-sm" @click="confirmAdd">
                                <i class="bi bi-cart-plus me-1"></i>Add to Cart
                            </button>
                        </div>


                    </div>
                </div>
            </div>

            <!-- Deals customization modal -->
            <div class="modal fade" id="customizeDealModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content rounded-3 shadow border-0" style="max-height: 90vh;">

                        <!-- HEADER -->
                        <div class="modal-header border-0 bg-light py-2 px-3 rounded-top">
                            <div>
                                <h5 class="fw-bold mb-0">
                                    {{ selectedDeal?.title || "Customize Deal" }}
                                </h5>
                                <p class="text-muted small mb-0" style="font-size: 0.8rem;">
                                    Customizing {{ currentDealMenuItem?.name || 'item' }}
                                    ({{ currentDealMenuItemIndex + 1 }} of {{ selectedDeal?.menu_items?.length || 0 }})
                                </p>
                            </div>
                            <button
                                class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                                data-bs-dismiss="modal" aria-label="Close" title="Close">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>




                        <!-- STEP INDICATOR for Current Item -->
                        <div class="px-3 pt-2 progress-bar">
                            <div class="stepper-container position-relative" style="min-height: 60px;">
                                <!-- Background line -->
                                <div class="stepper-progress-bg"></div>
                                <!-- Filled line -->
                                <div class="stepper-progress-fill" :style="{ width: dealStepProgressWidth }"></div>

                                <!-- Steps -->
                                <div v-for="(step, index) in dealVisibleSteps" :key="step.id"
                                    class="stepper-step text-center">
                                    <div class="stepper-circle stepper-circle-sm"
                                        :class="getDealStepCircleClass(index + 1)">
                                        <i v-if="index + 1 < currentDealStep" class="bi bi-check-lg"
                                            style="font-size: 0.7rem;"></i>
                                        <span v-else style="font-size: 0.75rem;">{{ index + 1 }}</span>
                                    </div>
                                    <small class="stepper-label"
                                        :class="{ 'stepper-label-active': index + 1 === currentDealStep }"
                                        style="font-size: 0.7rem; margin-top: 4px; display: block;">
                                        {{ step.name }}
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- BODY -->
                        <div class="modal-body px-3 py-2">
                            <div class="row g-3 mb-3">

                                <!-- LEFT COLUMN : Deal Image + Price -->
                                <div class="col-lg-4">
                                    <!-- Deal Image -->
                                    <div class="position-relative mb-3">
                                        <img :src="selectedDeal?.img || '/assets/img/product/product29.jpg'"
                                            class="img-fluid rounded-3 w-100 object-fit-cover"
                                            style="height: 200px; object-fit: cover;" alt="Deal">
                                        <span class="badge bg-danger position-absolute top-0 end-0 m-2">
                                            <span
                                                v-if="totalDealPriceWithAddons > parseFloat(selectedDeal?.price || 0)">
                                                Deal: <del>{{ formatCurrencySymbol(selectedDeal?.price || 0) }}</del>
                                                {{ formatCurrencySymbol(totalDealPriceWithAddons) }}
                                            </span>
                                            <span v-else>
                                                Deal: {{ formatCurrencySymbol(selectedDeal?.price || 0) }}
                                            </span>
                                        </span>
                                    </div>

                                    <!-- Current Item Being Customized -->
                                    <div class="rounded-3 border shadow-sm p-3 bg-white mb-3">
                                        <h6 class="fw-bold mb-2 d-flex align-content-center" style="font-size: 0.9rem;">
                                            <Pencil class="me-2 text-primary w-4" />
                                            Currently Customizing
                                        </h6>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <strong>{{ currentDealMenuItem?.name }}</strong>
                                                <div class="text-muted small">
                                                    Base Price: {{ formatCurrencySymbol(currentDealMenuItem?.price || 0)
                                                    }}
                                                </div>
                                                <!-- ADD THIS -->
                                                <div v-if="currentItemAddonsTotal > 0"
                                                    class="text-success small fw-semibold">
                                                    + {{ formatCurrencySymbol(currentItemAddonsTotal) }} (Add-ons)
                                                </div>
                                                <div class="fw-bold text-primary">
                                                    Total: {{ formatCurrencySymbol(currentItemTotalPrice) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Items in Deal Checklist -->
                                    <div class="rounded-3 border shadow-sm p-3 bg-white">
                                        <h6 class="fw-bold mb-2" style="font-size: 0.9rem;">
                                            <i class="bi bi-list-check me-2 text-success"></i>
                                            Items in Deal
                                        </h6>
                                        <div class="list-group list-group-flush">
                                            <div v-for="(item, idx) in selectedDeal?.menu_items" :key="idx"
                                                class="list-group-item px-0 py-2 border-0">
                                                <div class="d-flex align-items-center">
                                                    <i v-if="completedDealItems.includes(idx)"
                                                        class="bi bi-check-circle-fill text-success me-2"></i>
                                                    <Pencil v-else-if="idx === currentDealMenuItemIndex"
                                                        class="text-primary me-2 w-4" />
                                                    <i v-else class="bi bi-circle text-muted me-2"></i>
                                                    <small :class="{ 'fw-bold': idx === currentDealMenuItemIndex }">
                                                        {{ item.name }}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- RIGHT COLUMN : STEPS -->
                                <div class="col-lg-8">
                                    <div class="px-1">

                                        <!-- STEP 1 : REMOVE INGREDIENTS -->
                                        <div v-show="currentDealStep === 1 && hasDealIngredients" class="step-content">
                                            <div class="d-flex align-items-start mb-2">
                                                <i class="bi bi-dash-circle me-2 text-warning"
                                                    style="font-size: 1.1rem;"></i>
                                                <div>
                                                    <h6 class="fw-bold mb-0" style="font-size: 0.9rem;">
                                                        Remove Ingredients from {{ currentDealMenuItem?.name }}
                                                    </h6>
                                                    <p class="text-muted mb-0" style="font-size: 0.75rem;">
                                                        Uncheck ingredients you don't want
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="row g-2">
                                                <div v-for="ingredient in getCurrentDealIngredients()"
                                                    :key="ingredient.id" class="col-md-6">
                                                    <div class="d-flex align-items-center p-2 border rounded-2 shadow-sm"
                                                        :class="{ 'bg-light text-muted': isDealIngredientRemoved(ingredient.id) }">

                                                        <input type="checkbox" class="form-check-input me-2"
                                                            style="width: 16px; height: 16px;"
                                                            :id="'deal-ingredient-' + ingredient.id"
                                                            :checked="!isDealIngredientRemoved(ingredient.id)"
                                                            :disabled="!isDealIngredientRemoved(ingredient.id)"
                                                            @change="toggleDealIngredient(ingredient.id)">

                                                        <label :for="'deal-ingredient-' + ingredient.id"
                                                            class="form-check-label" style="font-size: 0.8rem;">
                                                            {{ ingredient.product_name || ingredient.name }}
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>


                                        <!-- STEP 2 : ADD-ONS (if available) -->
                                        <div v-show="currentDealStep === 2 && hasDealAddons" class="step-content">
                                            <div class="d-flex align-items-start mb-2">
                                                <i class="bi bi-plus-circle me-2 text-success"
                                                    style="font-size: 1.1rem;"></i>
                                                <div>

                                                    <h6 class="fw-bold mb-0" style="font-size: 0.9rem;">
                                                        Add Add-ons to {{ currentDealMenuItem?.name }}
                                                    </h6>
                                                    <p class="text-muted mb-0" style="font-size: 0.75rem;">
                                                        Enhance this item (charges may apply)
                                                    </p>
                                                </div>
                                            </div>

                                            <div v-for="group in currentDealMenuItem?.addon_groups"
                                                :key="group.group_id" class="mb-3">
                                                <label class="fw-semibold mb-1 d-flex justify-content-between"
                                                    style="font-size: 0.85rem;">
                                                    <span>{{ group.group_name }}</span>
                                                    <span v-if="group.max_select > 0"
                                                        class="badge rounded-pill bg-primary"
                                                        style="font-size: 0.7rem;">
                                                        Max {{ group.max_select }}
                                                    </span>
                                                </label>

                                                <MultiSelect
                                                    :modelValue="dealSelectedAddons[currentDealMenuItemIndex]?.[group.group_id] || []"
                                                    @update:modelValue="(val) => handleDealAddonChange(group.group_id, val)"
                                                    :options="group.addons" optionLabel="name" dataKey="id"
                                                    placeholder="Select add-ons" :maxSelectedLabels="2" class="w-100"
                                                    appendTo="self">
                                                </MultiSelect>
                                            </div>
                                        </div>

                                        <!-- STEP 3 : REVIEW CURRENT ITEM -->
                                        <div v-show="currentDealStep === dealFinalStep" class="step-content">
                                            <div class="d-flex align-items-start mb-2">
                                                <Eye class="me-2 text-primary w-5" style="font-size: 1.1rem;" />
                                                <div>
                                                    <h6 class="fw-bold mb-0" style="font-size: 0.9rem;">
                                                        Review {{ currentDealMenuItem?.name }}
                                                    </h6>
                                                    <p class="text-muted mb-0" style="font-size: 0.75rem;">
                                                        Confirm your customizations
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Summary -->
                                            <div class="summary-card p-3 rounded-3 shadow-sm mb-3">
                                                <h6 class="fw-bold mb-2 text-secondary">Customization Summary</h6>

                                                <div class="summary-item">
                                                    <span class="summary-label">Item:</span>
                                                    <span class="summary-value">{{ currentDealMenuItem?.name }}</span>
                                                </div>

                                                <!-- ADD PRICE BREAKDOWN -->
                                                <div class="summary-item">
                                                    <span class="summary-label">Base Price:</span>
                                                    <span class="summary-value">{{
                                                        formatCurrencySymbol(currentDealMenuItem?.price
                                                            || 0) }}</span>
                                                </div>

                                                <div class="summary-item" v-if="getDealRemovedIngredientsText()">
                                                    <span class="summary-label">Removed:</span>
                                                    <span class="summary-value text-danger">
                                                        {{ getDealRemovedIngredientsText() }}
                                                    </span>
                                                </div>

                                                <div class="summary-item" v-if="getDealSelectedAddonsCount() > 0">
                                                    <span class="summary-label">Add-ons:</span>
                                                    <span class="summary-value text-success">
                                                        {{ getDealSelectedAddonsText() }}
                                                    </span>
                                                </div>

                                                <!-- ADD ADDONS PRICE -->
                                                <div class="summary-item" v-if="currentItemAddonsTotal > 0">
                                                    <span class="summary-label">Add-ons Cost:</span>
                                                    <span class="summary-value text-success fw-bold">
                                                        + {{ formatCurrencySymbol(currentItemAddonsTotal) }}
                                                    </span>
                                                </div>

                                                <!-- ADD ITEM TOTAL -->
                                                <div class="summary-item border-top pt-2 mt-2">
                                                    <span class="summary-label fw-bold">Item Total:</span>
                                                    <span class="summary-value fw-bold text-primary">
                                                        {{ formatCurrencySymbol(currentItemTotalPrice) }}
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Kitchen Note for this item -->
                                            <div class="mb-2">
                                                <label class="fw-semibold mb-1" style="font-size: 0.85rem;">
                                                    <i class="bi bi-chat-left-text me-1"></i>
                                                    Kitchen Note for {{ currentDealMenuItem?.name }} (Optional)
                                                </label>
                                                <textarea v-model="dealItemKitchenNotes[currentDealMenuItemIndex]"
                                                    class="form-control form-control-sm rounded-2 shadow-sm" rows="2"
                                                    maxlength="200" placeholder="Special instructions for this item..."
                                                    style="font-size: 0.8rem;">
                                    </textarea>
                                                <div class="text-end small text-muted mt-1" style="font-size: 0.7rem;">
                                                    {{ (dealItemKitchenNotes[currentDealMenuItemIndex] || '').length
                                                    }}/200
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- FOOTER -->
                        <div class="modal-footer border-0 px-3 py-2 d-flex justify-content-end">

                            <div class="d-flex gap-2">
                                <button v-if="currentDealStep > 1" class="btn btn-secondary btn-sm px-4 py-2 shadow-sm"
                                    @click="previousDealStep">
                                    <i class="bi bi-arrow-left me-1"></i>Back
                                </button>

                                <button v-if="currentDealStep < dealFinalStep"
                                    class="btn btn-primary btn-sm px-4 py-2 shadow-sm" @click="nextDealStep">
                                    Next <i class="bi bi-arrow-right ms-1"></i>
                                </button>

                                <button v-else-if="!isLastDealItem" class="btn btn-success btn-sm px-4 py-2 shadow-sm"
                                    @click="confirmCurrentDealItem">
                                    <i class="bi bi-check2 me-1"></i>Next Item
                                </button>

                                <button v-else class="btn btn-primary btn-sm px-4 py-2 shadow-sm"
                                    @click="confirmDealAndAddToCart">
                                    <i class="bi bi-cart-plus me-1"></i>Add Deal to Cart
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!--  Addon Management Modal -->
            <div class="modal fade" id="addonManagementModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content rounded-4 border-0 shadow-lg">
                        <div class="modal-header border-0 pb-2">
                            <h5 class="modal-title fw-bold">
                                <i class="bi bi-puzzle-fill text-primary me-2"></i>
                                Manage Add-ons
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

                        <div class="modal-body pt-2" v-if="selectedCartItem">
                            <!-- Item Info Header -->
                            <div class="alert alert-light border rounded-3 mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <img :src="selectedCartItem.img" alt="" class="rounded"
                                        style="width: 60px; height: 60px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <div class="fw-bold fs-6">{{ selectedCartItem.title }}</div>
                                        <div class="d-flex gap-3 mt-1">
                                            <small class="text-muted">
                                                <i class="bi bi-tag me-1"></i>
                                                Base Price:
                                                <span class="fw-semibold">
                                                    {{ formatCurrencySymbol(
                                                        selectedCartItem?.unit_price -
                                                        getCartItemAddonsTotal(orderItems[selectedCartItemIndex] || {
                                                            addons: []
                                                        })
                                                    ) }}
                                                </span>
                                            </small>

                                            <small class="text-muted">
                                                <i class="bi bi-box me-1"></i>
                                                Quantity: <span class="fw-semibold">{{ selectedCartItem.qty }}</span>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Addons Table -->
                            <div v-if="selectedCartItem.addons && selectedCartItem.addons.length > 0"
                                class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 60px;" class="text-center">Select</th>
                                            <th>Items</th>
                                            <th style="width: 120px;" class="text-center">Price</th>
                                            <th style="width: 200px;" class="text-center">Quantity</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="addon in selectedCartItem.addons" :key="addon.id"
                                            :class="{ 'table-active': addon.selected !== false }">
                                            <!-- Checkbox Column -->
                                            <td class="text-center">
                                                <div class="form-check d-flex justify-content-center">
                                                    <input type="checkbox" class="form-check-input"
                                                        :checked="addon.selected !== false" @change="toggleAddon(addon)"
                                                        style="width: 22px; height: 22px; cursor: pointer;">
                                                </div>
                                            </td>

                                            <!-- Item Name Column -->
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="addon-status-indicator"
                                                        :class="addon.selected !== false ? 'bg-success' : 'bg-secondary'">
                                                    </div>
                                                    <div>
                                                        <div class="fw-semibold">{{ addon.name }}</div>
                                                        <small class="text-muted" v-if="addon.group_name">
                                                            {{ addon.group_name }}
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Price Column -->
                                            <td class="text-center">
                                                <span class="badge bg-success-subtle text-success px-3 py-2 fs-6">
                                                    +{{ formatCurrencySymbol(addon.price) }}
                                                </span>
                                            </td>

                                            <!-- Quantity Controls Column -->
                                            <td>
                                                <div class="d-flex align-items-center justify-content-center gap-2">
                                                    <button
                                                        class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center"
                                                        style="width: 36px; height: 36px;"
                                                        @click="decrementAddon(addon)"
                                                        :disabled="!addon.selected || (addon.quantity || 1) <= 1">
                                                        <i class="bi bi-dash-lg"></i>
                                                    </button>
                                                    <span class="fw-bold fs-5 px-3"
                                                        style="min-width: 50px; text-align: center;">
                                                        {{ addon.quantity || 1 }}
                                                    </span>
                                                    <button
                                                        class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center"
                                                        style="width: 36px; height: 36px;"
                                                        @click="incrementAddon(addon)" :disabled="!addon.selected">
                                                        <i class="bi bi-plus-lg"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div v-else class="text-center text-muted py-5">
                                <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mb-0 mt-3 fw-semibold">No add-ons available for this item</p>
                            </div>

                            <!-- Price Summary Footer -->
                            <div class="mt-3 p-3 rounded-3">

                                <!-- Selected Add-ons Summary -->
                                <div v-if="getSelectedAddonsCount() > 0"
                                    class="mt-3 p-3 rounded-3 border border-success bg-light">
                                    <small class="text-success fw-semibold">
                                        <i class="bi bi-check-circle-fill me-1"></i>
                                        {{ getSelectedAddonsCount() }} add-on(s) selected •
                                        Add-ons Total: {{ formatCurrencySymbol(getCartItemAddonsTotal(selectedCartItem))
                                        }}
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0 bg-light">
                            <button type="button" class="btn btn-secondary px-2 py-2" data-bs-dismiss="modal">
                                Cancel
                            </button>
                            <button type="button" class="btn btn-primary px-2 py-2" @click="saveAddonChanges">
                                Save
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <KotModal :show="showKotModal" :kot="kotData" :loading="kotLoading" @close="showKotModal = false"
                @status-updated="handleKotStatusUpdated" />
            <ReceiptModal :show="showReceiptModal" :order="lastOrder" :money="money"
                @close="showReceiptModal = false" />
            <ConfirmOrderModal :show="showConfirmModal" :customer="customer" :delivery-location="deliveryLocation"
                :phone="phoneNumber" :order-type="orderType" :selected-table="selectedTable" :order-items="orderItems"
                :grand-total="grandTotal" :money="money" v-model:cashReceived="cashReceived"
                :client_secret="client_secret" :order_code="order_code" :sub-total="subTotal" :tax="totalTax"
                :service-charges="serviceCharges" :delivery-charges="deliveryCharges" :promo-discount="promoDiscount"
                :promo-id="selectedPromos?.id" :promo-name="selectedPromos?.name" :promo-type="selectedPromos?.type"
                :promo-discount-amount="promoDiscount" :note="note" :kitchen-note="kitchenNote"
                :order-date="new Date().toISOString().split('T')[0]"
                :order-time="new Date().toTimeString().split(' ')[0]" :payment-method="paymentMethod"
                :change="changeAmount" @close="showConfirmModal = false" @confirm="confirmOrder"
                :appliedPromos="getAppliedPromosData" :approved-discounts="approvedDiscountTotal"
                :approved-discount-details="selectedDiscounts.map(d => ({
                    discount_id: d.id,
                    discount_name: d.name,
                    discount_percentage: d.percentage,
                    discount_amount: getDiscountAmount(d.percentage),
                    approval_id: d.approval_id
                }))" />
            <PosOrdersModal :show="showPosOrdersModal" :orders="posOrdersData" @close="showPosOrdersModal = false"
                @view-details="handleViewOrderDetails" :loading="loading" />

            <PromoModal :show="showPromoModal" :loading="loadingPromos" :promos="promosData" :order-items="orderItems"
                @apply-promo="handleApplyPromo" @close="showPromoModal = false" />
            <DiscountModal :show="showDiscountModal" :discounts="discountsData" :order-items="orderItems"
                :loading="loadingDiscounts" :applied-discounts="selectedDiscounts"
                :rejected-discounts="rejectedDiscounts" @close="showDiscountModal = false"
                @apply-discount="handleApplyDiscount" @clear-discount="clearDiscounts" />

            <ConfirmMissingIngredientsModal :show="showMissingIngredientsModal"
                :missing-ingredients="missingIngredients"
                @close="showMissingIngredientsModal = false; pendingOrderData = null"
                @confirm="handleConfirmMissingIngredients" />

            <PendingOrdersModal :show="showPendingOrdersModal" :pending-orders="pendingOrders"
                :loading="pendingOrdersLoading" @close="showPendingOrdersModal = false" @resume="resumePendingOrder"
                @reject="rejectPendingOrder" @pay-now="handlePayUnpaidOrder" />
            <ConfirmOrderModal v-if="selectedUnpaidOrder" :show="showPaymentModal"
                :customer="selectedUnpaidOrder.customer_name" :delivery-location="selectedUnpaidOrder.delivery_location"
                :phone="selectedUnpaidOrder.phone_number" :order-type="selectedUnpaidOrder.order_type"
                :selected-table="{ name: selectedUnpaidOrder.table_number }"
                :order-items="selectedUnpaidOrder.order_items" :grand-total="selectedUnpaidOrder.total_amount"
                :sub-total="selectedUnpaidOrder.sub_total" :tax="selectedUnpaidOrder.tax"
                :service-charges="selectedUnpaidOrder.service_charges"
                :delivery-charges="selectedUnpaidOrder.delivery_charges"
                :promo-discount="selectedUnpaidOrder.promo_discount" :note="selectedUnpaidOrder.note"
                :kitchen-note="selectedUnpaidOrder.kitchen_note" v-model:cashReceived="cashReceived" :money="money"
                @close="showPaymentModal = false; selectedUnpaidOrder = null" @confirm="completeOrderPayment"
                :is-payment-only="true" />
        </div>
    </Master>
</template>

<style scoped>
.summary-card {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
}

.dark .summary-card {
    background-color: #181818;
}

.dark .list-group-item {
    background-color: #181818 !important;
    color: #fff !important;
}

.summary-item {
    padding: 6px 0;
    display: flex;
    font-size: 0.82rem;
    border-bottom: 1px dashed #e3e3e3;
}

.summary-item:last-child {
    border-bottom: 0;
}

.summary-label {
    font-weight: 600;
    color: #6c757d;
    min-width: 70px;
}

.summary-value {
    color: #444;
}

.stepper-circle-sm {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #dee2e6;
    background: white;
    font-weight: 600;
    transition: all 0.3s;
}

.dark .bg-white {
    border-bottom: 1px solid #fff !important;
}

:global(.dark .p-accordionheader) {
    background-color: #1A1A1A !important;
    color: #fff !important;
}

:global(.dark .p-accordioncontent-content) {
    background-color: #1A1A1A !important;
    color: #fff !important;
    padding: 0px !important;
}

.dark .stepper-circle {
    background-color: #1B1670 !important;
}

.stepper-progress-bg {
    position: absolute;
    top: 14px;
    left: 14px;
    right: 14px;
    height: 2px;
    background: #dee2e6;
    z-index: 0;
}



.stepper-progress-fill {
    position: absolute;
    top: 14px;
    left: 14px;
    height: 2px;
    background: #1B1670;
    transition: width 0.3s;
    z-index: 1;
    max-width: calc(100% - 28px);
}

.stepper-container {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    position: relative;
    padding: 0 8px;
}

.stepper-step {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 0 0 auto;
}

.stepper-label {
    display: block;
    margin-top: 4px;
    color: #6c757d;
    font-weight: 500;
    text-align: left;
}

.stepper-label-active {
    color: #fff;
    font-weight: 600;
}

.progress-bar {
    background-color: #fff !important;
}

.dark .progress-bar {
    background-color: #141414 !important;
}




.variant-card {
    transition: all 0.2s;
}

.variant-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1) !important;
}

/* Compact modal scrollbar */
.modal-dialog-scrollable .modal-body {
    overflow-y: auto;
}

.modal-body::-webkit-scrollbar {
    width: 6px;
}

.modal-body::-webkit-scrollbar-track {
    background: #f8f9fa;
    border-radius: 10px;
}

.modal-body::-webkit-scrollbar-thumb {
    background: #ced4da;
    border-radius: 10px;
}

.modal-body::-webkit-scrollbar-thumb:hover {
    background: #adb5bd;
}

/* Smooth transitions */
.variant-card,
.stepper-circle-sm,
.btn {
    transition: all 0.2s ease;
}

/* Image styling */
.object-fit-cover {
    object-fit: cover;
    object-position: center;
}

/* Quantity Controls */
.qty-group {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.qty-btn {
    width: 32px;
    height: 32px;
    border: 1px solid #dee2e6;
    background: white;
    border-radius: 50%;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #495057;
}


.qty-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.qty-box {
    min-width: 50px;
    padding: 0.25rem 1rem;
    text-align: center;
    font-weight: 600;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    font-size: 0.9rem;
}

/* Rounded pill buttons */
.btn.rounded-pill {
    border-radius: 50rem !important;
}


.dark .ot-pill {
    background-color: #181818;
    color: #fff;
}

.bg-light {
    font-size: 1.2rem !important;
}

:deep(.p-multiselect-overlay) {
    background: #fff !important;
    color: #000 !important;
}

:deep(.p-multiselect-header) {
    background: #fff !important;
    color: #000 !important;
    border-bottom: 1px solid #ddd;
}

:deep(.p-multiselect-list) {
    background: #fff !important;
}

:deep(.p-multiselect-option) {
    background: #fff !important;
    color: #000 !important;
}

:deep(.p-multiselect-option.p-highlight) {
    background: #f0f0f0 !important;
    color: #000 !important;
}

:deep(.p-multiselect),
:deep(.p-multiselect-panel),
:deep(.p-multiselect-token) {
    background: #fff !important;
    color: #000 !important;
}

:deep(.p-multiselect-overlay .p-checkbox-box) {
    background: #fff !important;
    border: 1px solid #ccc !important;
}

:deep(.p-multiselect-overlay .p-checkbox-box.p-highlight) {
    background: #007bff !important;
    border-color: #007bff !important;
}

:deep(.p-multiselect-filter) {
    background: #fff !important;
    color: #000 !important;
    border: 1px solid #ccc !important;
}

:deep(.p-multiselect-filter-container) {
    background: #fff !important;
}

:deep(.p-multiselect-chip) {
    background: #e9ecef !important;
    color: #000 !important;
    border-radius: 12px !important;
    border: 1px solid #ccc !important;
    padding: 0.25rem 0.5rem !important;
}

:deep(.p-multiselect-chip .p-chip-remove-icon) {
    color: #555 !important;
}

:deep(.p-multiselect-chip .p-chip-remove-icon:hover) {
    color: #dc3545 !important;
}

:deep(.p-multiselect-panel),
:deep(.p-select-panel),
:deep(.p-dropdown-panel) {
    z-index: 2000 !important;
}

:deep(.p-multiselect-label) {
    color: #000 !important;
}

:deep(.p-select) {
    background-color: white !important;
    color: black !important;
    border-color: #9b9c9c;
}

:deep(.p-select-list-container) {
    background-color: white !important;
    color: black !important;
}

:deep(.p-select-option) {
    background-color: transparent !important;
    color: black !important;
}

:deep(.p-select-option:hover) {
    background-color: #f0f0f0 !important;
    color: black !important;
}

:deep(.p-select-option.p-focus) {
    background-color: #f0f0f0 !important;
    color: black !important;
}

:deep(.p-select-label) {
    color: #000 !important;
}

:deep(.p-placeholder) {
    color: #80878e !important;
}

:global(.dark .p-multiselect-header) {
    background-color: #181818 !important;
    color: #fff !important;
}

:global(.dark .p-multiselect-label) {
    color: #fff !important;
}

:global(.dark .p-select .p-component .p-inputwrapper) {
    background: #181818 !important;
    color: #fff !important;
    border-bottom: 1px solid #555 !important;
}

:global(.dark .p-multiselect-list) {
    background: #181818 !important;
}

:global(.dark .p-multiselect-option) {
    background: #181818 !important;
    color: #fff !important;
}

:global(.dark .p-multiselect-option.p-highlight),
:global(.dark .p-multiselect-option:hover) {
    background: #222 !important;
    color: #fff !important;
}

:global(.dark .p-multiselect),
:global(.dark .p-multiselect-panel),
:global(.dark .p-multiselect-token) {
    background: #181818 !important;
    color: #fff !important;
    border-color: #555 !important;
}

:global(.dark .p-multiselect-overlay .p-checkbox-box) {
    background: #181818 !important;
    border: 1px solid #555 !important;
}

:global(.dark .p-multiselect-filter) {
    background: #181818 !important;
    color: #fff !important;
    border: 1px solid #555 !important;
}

:global(.dark .p-multiselect-filter-container) {
    background: #181818 !important;
}

:global(.dark .p-multiselect-chip) {
    background: #111 !important;
    color: #fff !important;
    border: 1px solid #555 !important;
    border-radius: 12px !important;
    padding: 0.25rem 0.5rem !important;
}

:global(.dark .p-multiselect-chip .p-chip-remove-icon) {
    color: #ccc !important;
}

:global(.dark .p-multiselect-chip .p-chip-remove-icon:hover) {
    color: #f87171 !important;
}

/* ==================== Dark Mode Select Styling ====================== */
:global(.dark .p-select) {
    background-color: #181818 !important;
    color: #fff !important;
    border-color: #555 !important;
}

:global(.dark .p-select-list-container) {
    background-color: #181818 !important;
    color: #fff !important;
}

:global(.dark .p-select-option) {
    background-color: transparent !important;
    color: #fff !important;
}

:global(.dark .form-control:focus) {
    border-color: #fff !important;
}


:global(.dark .p-select-option:hover),
:global(.dark .p-select-option.p-focus) {
    background-color: #222 !important;
    color: #fff !important;
}

:global(.dark .p-select-label) {
    color: #fff !important;
}

.col-lg-4:has(.cart) {
    position: fixed;
    right: 0;
    top: 65px;
    width: 28%;
    padding-right: 15px;
    padding-left: 15px;
    max-height: calc(100vh - 140px);
    overflow: auto;
    z-index: 100;
}

@media only screen and (max-width: 1024px) {
    .col-md-6 {
        width: 51% !important;
        flex: 0 0 100% !important;
        max-width: 51% !important;
    }

    /* .left-card{
        margin-top: 35px !important;
    } */

    .justify {
        padding-right: 110px;
    }

    .col-lg-4 {
        margin-top: 36px;
        width: 50% !important;
        max-width: 48% !important;
    }

    .category-cards {
        margin-top: 36px;
        width: 43% !important;
        max-width: 43% !important;
    }

    .right-cart {
        margin-top: 100px;
    }

    .tablet-screen {
        margin: 0px -131px 0px -14px !important;
    }
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

.col-lg-8:not(:has(+ .col-lg-4:has(.cart))) {
    margin-right: 0;
}

.row:has(.col-lg-4:has(.cart)) .col-lg-8 {
    padding-right: 15px;
}

.cart {
    max-height: calc(85vh);
    display: flex;
    flex-direction: column;
}

.cart-body {
    overflow-y: auto;
    flex: 1;
    min-height: 0;
}

.cart-header {
    flex-shrink: 0;
}

.cart-footer {
    flex-shrink: 0;
}

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
    color: #f9fafb !important;
}

.dark .table thead {
    background-color: #181818 !important;
    color: #ffffff;
}

.dark .table thead th {
    background-color: #212121 !important;
    color: #ffffff;
}

.page-wrapper {
    background: #f5f7fb;
}

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

.cat-icon-wrap {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #eee;
    display: grid;
    place-items: center;
    margin-bottom: 0.25rem;
}

.cat-icon {
    font-size: 1.35rem;
    line-height: 1;
}

.cat-name {
    font-weight: 700;
    font-size: 1rem;
    color: #141414;
}

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

.item-card.out-of-stock {
    cursor: not-allowed;
    opacity: 0.9;
    position: relative;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
}

.item-card.out-of-stock::after {
    content: "";
    position: absolute;
    inset: 0;
    background: rgba(90, 85, 85, 0.192);
    border-radius: 16px;
    z-index: 2;
}

.dark .form-select {
    background-color: #212121;
    color: #fff;
}

.item-price {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #1b1670;
    color: #fff;
    padding: 0.25rem 0.75rem;
    font-weight: 600;
    font-size: 0.8rem;
    border-radius: 999px;
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
    grid-template-columns: 1fr auto;
    align-items: flex-start;
    gap: 0.8rem;
    padding: 0.6rem 0.35rem;
    border-bottom: 1px solid #f1f2f6;
}

.line:last-child {
    border-bottom: 0;
}

.line-left {
    display: flex;
    gap: 0.6rem;
    align-items: flex-start;
    min-width: 0;
}

.line-left img {
    width: 42px;
    height: 42px;
    object-fit: cover;
    border-radius: 8px;
    flex-shrink: 0;
}

.meta {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    min-width: 0;
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

.line-right {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    align-items: flex-end;
}

.qty-controls {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    background: #f1f2f6;
    border-radius: 8px;
    padding: 0.2rem;
}

.qty-btn {
    width: 26px;
    height: 26px;
    border-radius: 6px;
    border: 0;
    background: #1b1670;
    color: #fff;
    font-weight: 800;
    line-height: 1.5;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.qty-btn:hover {
    background: #0f0d4d;
}

.qty-btn:disabled {
    background: #b9bdd4;
    cursor: not-allowed;
}

.qty {
    min-width: 28px;
    text-align: center;
    font-weight: 700;
    font-size: 0.9rem;
}

.price-delete {
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.price {
    font-weight: 700;
    font-size: 0.95rem;
    min-width: 64px;
    text-align: right;
}

.del {
    border: 0;
    background: #ffeded;
    color: #c0392b;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.del:hover {
    background: #ff6b6b;
    color: #fff;
}

.dark .qty-controls {
    background: #212121;
}

.dark .qty {
    color: #fff;
}

.dark .price {
    color: #fff;
}

.dark .del {
    background: #3a3a3a;
    color: #ff6b6b;
}

.dark .del:hover {
    background: #c0392b;
    color: #fff;
}

@media only screen and (min-device-width: 1024px) and (max-device-width: 1366px) {
    .line {
        gap: 0.6rem;
        padding: 0.5rem 0.3rem;
    }

    .line-left img {
        width: 40px;
        height: 40px;
    }

    .meta .name {
        font-size: 0.88rem;
    }

    .qty-btn {
        width: 24px;
        height: 24px;
        font-size: 0.85rem;
    }

    .qty {
        min-width: 26px;
        font-size: 0.85rem;
    }

    .price {
        font-size: 0.9rem;
    }

    .del {
        width: 26px;
        height: 26px;
    }
}

/* Mobile adjustments */
@media only screen and (max-width: 768px) {
    .line {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }

    .line-right {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        width: 100%;
    }
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
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.6rem;
    border-bottom-left-radius: 1rem;
    border-bottom-right-radius: 1rem;
}

.cart-footer .btn {
    width: 100%;
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

@media only screen and (min-device-width: 1024px) and (max-device-width: 1366px) {
    .cart-header {
        padding: 0.7rem;
    }

    .cart-header-buttons {
        font-size: 14px !important;
        height: 30px !important;
    }

    .promos-btn,
    .discount-btn {
        height: 30px !important;
        padding-top: 0.19rem !important;

    }

    .table-dropdown {
        margin-top: 0px !important;
    }

    .view-details-btn {
        height: 30px !important;
        font-size: 14px !important;
    }

    .left-card-cntrl-btn {
        height: 30px !important;
    }

    .menu-name {
        margin-top: 15px !important;
        font-size: 16px !important;
    }

    .cart {
        max-height: calc(70vh);
        display: flex;
        flex-direction: column;
    }

}
</style>
