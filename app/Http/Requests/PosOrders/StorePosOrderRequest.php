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
            'sub_total' => 'required|numeric',
            'total_amount' => 'required|numeric',
            'tax' => 'nullable|numeric',
            'service_charges' => 'nullable|numeric',
            'delivery_charges' => 'nullable|numeric',
            'status' => 'nullable|string',
            'note' => 'nullable|string',
            'order_date' => 'nullable|date',
            'order_time' => 'nullable',
            'order_type' => 'required|in:Dine In,Delivery,Collection,Takeaway',
            'table_number' => 'nullable|string',
        ];
    }
}