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
        $groupId = $this->route('id');

        return [

            'name' => [
                'required',
                'string',
                'max:255',
                'unique:addon_groups,name,'.$groupId,
            ],

            'min_select' => [
                'required',
                'integer',
                'min:0',
                'lte:max_select',
            ],

            'max_select' => [
                'required',
                'integer',
                'min:1',
                'gte:min_select',
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
