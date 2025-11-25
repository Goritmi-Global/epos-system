<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ============================================================
 * StoreDiscountRequest - For creating new discounts
 * ============================================================
 */
class StoreDiscountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:discounts,name',
            'type' => 'required|in:flat,percent',
            'status' => 'required|in:active,inactive',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'min_purchase' => 'required|numeric|min:0|lt:max_discount',
            'max_discount' => 'required|numeric|min:0|gt:min_purchase',
            'description' => 'nullable|string|max:1000',
            'discount_amount' => [
                'required',
                'numeric',
                'min:0',
                Rule::when($this->type === 'percent', ['max:100']),
            ],
        ];
    }

    /**
     * Get custom error messages for validation failures.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Discount name is required',
            'name.unique' => 'A discount with this name already exists',
            'type.required' => 'Discount type is required',
            'type.in' => 'Discount type must be either flat or percent',
            'status.required' => 'Status is required',
            'start_date.required' => 'Start date is required',
            'start_date.date' => 'Start date must be a valid date',
            'end_date.required' => 'End date is required',
            'end_date.date' => 'End date must be a valid date',
            'end_date.after' => 'End date must be after start date',
            'min_purchase.required' => 'Minimum purchase amount is required',
            'min_purchase.min' => 'Minimum purchase must be at least 0',
            'max_discount.min' => 'Maximum discount must be at least 0',
            'discount_amount.required' => 'Discount amount is required',
            'discount_amount.min' => 'Discount amount must be at least 0',
            'discount_amount.max' => 'Percentage discount cannot exceed 100%',
            'discount_amount.max' => 'Percentage discount cannot exceed 100%.',
            'min_purchase.lt' => 'Minimum purchase must be less than maximum discount.',
            'max_discount.gt' => 'Maximum discount must be greater than minimum purchase.',

        ];
    }
}
