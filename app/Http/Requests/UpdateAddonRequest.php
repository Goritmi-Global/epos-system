<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAddonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Add your authorization logic here
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Get current addon ID from the route
        $addonId = $this->route('id');

        return [

            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('addons', 'name')
                    ->where('addon_group_id', $this->addon_group_id)
                    ->ignore($addonId),
            ],

            // Must belong to an existing addon group
            'addon_group_id' => 'required|exists:addon_groups,id',

            // Price must be >= 0
            'price' => 'required|numeric|min:0',

            'description' => 'nullable|string',

            'status' => 'required|in:active,inactive',

            'sort_order' => 'nullable|integer|min:0',
        ];
    }

    /**
     * Custom error messages
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Addon name is required.',
            'name.unique' => 'This addon already exists in the selected group.',
            'addon_group_id.required' => 'Please select an addon group.',
            'addon_group_id.exists' => 'Selected addon group does not exist.',
            'price.required' => 'Price is required.',
            'price.min' => 'Price cannot be less than 0.',
        ];
    }
}
