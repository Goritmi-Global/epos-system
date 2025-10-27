<?php

namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class StoreAddonGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Add your authorization logic here
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $groupId = $this->route('addon_group'); // For update requests
        
        return [
            // Name must be unique (except for current record during update)
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:addon_groups,name,' . $groupId
            ],
            
            // Min select must be >= 0 and <= max_select
            'min_select' => [
                'required',
                'integer',
                'min:0',
                'lte:max_select' // Less than or equal to max_select
            ],
            
            // Max select must be >= min_select
            'max_select' => [
                'required',
                'integer',
                'min:1',
                'gte:min_select' // Greater than or equal to min_select
            ],
            
            'description' => 'nullable|string',
            
            'status' => 'required|in:active,inactive',
        ];
    }

    /**
     * Custom error messages
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Group name is required.',
            'name.unique' => 'This group name already exists.',
            'min_select.lte' => 'Minimum select cannot be greater than maximum select.',
            'max_select.gte' => 'Maximum select cannot be less than minimum select.',
        ];
    }
}