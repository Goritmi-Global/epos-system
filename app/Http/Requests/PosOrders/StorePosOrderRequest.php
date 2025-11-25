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
            'customer_name'    => 'nullable|string|max:255',
            'phone_number'            => 'nullable|string|max:20',
            'delivery_location'=> 'nullable|string|max:255',
            'sub_total'        => 'required|numeric|min:0',
            'total_amount'     => 'required|numeric|min:0',
            'tax'              => 'nullable|numeric|min:0',
            'service_charges'  => 'nullable|numeric|min:0',
            'delivery_charges' => 'nullable|numeric|min:0',
            'status'           => 'nullable|string|in:paid,unpaid,pending,cancelled',
            'note'             => 'nullable|string',
            'kitchen_note'     => 'nullable|string',
            'order_date'       => 'nullable|date',
            'order_time'       => 'nullable',
            'sales_discount'   => 'nullable|numeric|min:0',
            'approved_discounts' => 'nullable|numeric|min:0',

            // Order type
            'order_type'   => 'required|in:Dine In,Delivery,Collection,Takeaway',
            'table_number' => 'required_if:order_type,Dine In|string|nullable',

            // Payment fields
            'payment_method' => 'required|in:Cash,Card,Split',
            'cash_received'  => 'nullable|numeric|min:0',
            'change'         => 'nullable|numeric|min:0',

            // Items validation
            'items'                              => 'required|array|min:1',
            'items.*.product_id'                 => 'required|integer|exists:menu_items,id',
            'items.*.title'                      => 'required|string',
            'items.*.quantity'                   => 'required|numeric|min:1',
            'items.*.price'                      => 'required|numeric|min:0',
            'items.*.note'                       => 'nullable|string',
            'items.*.kitchen_note'               => 'nullable|string',
            'items.*.variant_id'                 => 'nullable|numeric',
            'items.*.variant_name'               => 'nullable|string',
            'items.*.item_kitchen_note'          => 'nullable|string',
            'items.*.sale_discount_per_item'     => 'nullable|numeric',
            
            // ✅ NEW: Validation for removed ingredients
            'items.*.removed_ingredients'        => 'nullable|array',
            'items.*.removed_ingredients.*'      => 'integer', // Each removed ingredient ID must be integer

            // KOT validations
            'auto_print_kot' => 'nullable|boolean',

            // Multiple Promo validations
            'promo_discount'                       => 'nullable|numeric|min:0',
            'applied_promos'                       => 'nullable|array',
            'applied_promos.*.promo_id'            => 'required|integer|exists:promos,id',
            'applied_promos.*.promo_name'          => 'required|string|max:255',
            'applied_promos.*.promo_type'          => 'required|in:flat,percent',
            'applied_promos.*.discount_amount'     => 'required|numeric|min:0',
            'applied_promos.*.applied_to_items'    => 'nullable|array',
            'applied_promos.*.applied_to_items.*'  => 'integer',

            // Keep old single promo fields for backward compatibility
            'promo_id'       => 'nullable|integer|exists:promos,id',
            'promo_name'     => 'nullable|string|max:255',
            'promo_type'     => 'nullable|in:flat,percent',
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
        ];
    }
}