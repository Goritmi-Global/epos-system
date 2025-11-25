<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePromoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:promos,name',
            'type' => 'required|in:flat,percent',
            'status' => 'required|in:active,inactive',
            'start_date' => 'required',
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

    public function messages(): array
    {
        return [
            'name.required' => 'Promo name is required',
            'name.unique' => 'A promo with this name already exists',
            'type.required' => 'Promo type is required',
            'type.in' => 'Promo type must be either flat or percent',
            'status.required' => 'Status is required',
            'start_date.required' => 'Start date is required',
            'start_date.after_or_equal' => 'Start date must be today or later',
            'end_date.required' => 'End date is required',
            'end_date.after' => 'End date must be after start date',
            'min_purchase.required' => 'Minimum purchase amount is required',
            'min_purchase.min' => 'Minimum purchase must be at least 0',
            'max_discount.min' => 'Maximum discount must be at least 0',
            'max_discount.max' => 'Maximum discount percentage cannot exceed 100%.',
            'min_purchase.lt' => 'Minimum purchase must be less than maximum discount.',
            'max_discount.gt' => 'Maximum discount must be greater than minimum purchase.',
        ];
    }
}
