<?php

namespace App\Http\Requests\PosOrders;

use Illuminate\Foundation\Http\FormRequest;

class StorePosOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'delivery_location' => 'nullable|string|max:255',
            'sub_total' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'service_charges' => 'nullable|numeric|min:0',
            'delivery_charges' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:paid,unpaid,pending,cancelled',
            'note' => 'nullable|string',
            'kitchen_note' => 'nullable|string',
            'order_date' => 'nullable|date',
            'order_time' => 'nullable',
            'sales_discount' => 'nullable|numeric|min:0',
            'approved_discounts' => 'nullable|numeric|min:0',
            'confirm_missing_ingredients' => 'sometimes|boolean',
            'source' => 'nullable|string|max:255',

            // Order type
            'order_type' => 'required|in:Eat In,Delivery,Collection,Takeaway',
            'table_number' => 'required_if:order_type,Dine In|string|nullable',

            // Payment fields
            'is_paid' => 'nullable|boolean',
            'payment_method' => 'nullable|required_if:is_paid,true|in:Cash,Card,Split',
            'cash_received' => 'nullable|numeric|min:0',
            'change' => 'nullable|numeric|min:0',

            // Items validation
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:menu_items,id',
            'items.*.title' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.note' => 'nullable|string',
            'items.*.kitchen_note' => 'nullable|string',
            'items.*.variant_id' => 'nullable|numeric',
            'items.*.variant_name' => 'nullable|string',
            'items.*.item_kitchen_note' => 'nullable|string',
            'items.*.sale_discount_per_item' => 'nullable|numeric',

            // Validation for removed ingredients
            // 'items.*.removed_ingredients'        => 'nullable|array',
            // 'items.*.removed_ingredients.*'      => 'integer',
            'items.*.removed_ingredients' => 'nullable|array',
            'items.*.removed_ingredients.*.id' => 'required|integer',
            'items.*.removed_ingredients.*.name' => 'required|string',

            // Deal-specific validations
            'items.*.is_deal' => 'nullable|boolean',
            'items.*.deal_id' => 'nullable|integer|exists:deals,id',
            'items.*.menu_items' => 'nullable|array',
            'items.*.menu_items.*.id' => 'required_with:items.*.menu_items|integer',
            'items.*.menu_items.*.name' => 'required_with:items.*.menu_items|string',
            'items.*.menu_items.*.price' => 'required_with:items.*.menu_items|numeric',
            'items.*.menu_items.*.ingredients' => 'nullable|array',
            'items.*.menu_items.*.ingredients.*.id' => 'required_with:items.*.menu_items.*.ingredients|integer',
            'items.*.menu_items.*.ingredients.*.product_name' => 'required_with:items.*.menu_items.*.ingredients|string',
            'items.*.menu_items.*.ingredients.*.quantity' => 'required_with:items.*.menu_items.*.ingredients|numeric',
            'items.*.menu_items.*.ingredients.*.stock' => 'nullable|numeric',
            'items.*.menu_items.*.ingredients.*.inventory_item_id' => 'nullable|integer',
            'items.*.menu_items.*.ingredients.*.unit' => 'nullable|string',

            // Addon validations
            'items.*.addons' => 'nullable|array',
            'items.*.addons.*.id' => 'required_with:items.*.addons|integer',
            'items.*.addons.*.name' => 'required_with:items.*.addons|string',
            'items.*.addons.*.price' => 'required_with:items.*.addons|numeric',
            'items.*.addons.*.quantity' => 'nullable|integer|min:1',

            // Additional fields that might be passed
            'items.*.unit_price' => 'nullable|numeric',
            'items.*.tax_percentage' => 'nullable|numeric',
            'items.*.tax_amount' => 'nullable|numeric',

            // KOT validations
            'auto_print_kot' => 'nullable|boolean',

            // Multiple Promo validations
            'promo_discount' => 'nullable|numeric|min:0',
            'applied_promos' => 'nullable|array',
            'applied_promos.*.promo_id' => 'required|integer|exists:promos,id',
            'applied_promos.*.promo_name' => 'required|string|max:255',
            'applied_promos.*.promo_type' => 'required|in:flat,percent',
            'applied_promos.*.discount_amount' => 'required|numeric|min:0',
            'applied_promos.*.applied_to_items' => 'nullable|array',
            'applied_promos.*.applied_to_items.*' => 'integer',

            // Keep old single promo fields for backward compatibility
            'promo_id' => 'nullable|integer|exists:promos,id',
            'promo_name' => 'nullable|string|max:255',
            'promo_type' => 'nullable|in:flat,percent',

            // Additional fields
            'approved_discount_details' => 'nullable|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'items.*.removed_ingredients.array' => 'Removed ingredients must be an array.',
            'items.*.removed_ingredients.*.integer' => 'Each removed ingredient ID must be a number.',
            'items.*.deal_id.exists' => 'The selected deal does not exist.',
            'items.*.menu_items.*.ingredients.*.id.required_with' => 'Ingredient ID is required.',
            'items.*.menu_items.*.ingredients.*.product_name.required_with' => 'Ingredient name is required.',
            'items.*.menu_items.*.ingredients.*.quantity.required_with' => 'Ingredient quantity is required.',
        ];
    }
}
