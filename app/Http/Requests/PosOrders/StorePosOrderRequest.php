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
            'sub_total'        => 'required|numeric|min:0',
            'total_amount'     => 'required|numeric|min:0',
            'tax'              => 'nullable|numeric|min:0',
            'service_charges'  => 'nullable|numeric|min:0',
            'delivery_charges' => 'nullable|numeric|min:0',
            'status'           => 'nullable|string|in:paid,unpaid,pending,cancelled',
            'note'             => 'nullable|string',
            'order_date'       => 'nullable|date',
            'order_time'       => 'nullable',

            // Order type
            'order_type'   => 'required|in:Dine In,Delivery,Collection,Takeaway',
            'table_number' => 'required_if:order_type,Dine In|string|nullable',

            // Payment fields
            'payment_method' => 'required|in:Cash,Card,Split',
            'cash_received'  => 'nullable|numeric|min:0',
        ];
    }
}
