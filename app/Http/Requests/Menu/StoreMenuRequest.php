<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;

class StoreMenuRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // allow all authenticated users (you can add policies later if needed)
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255', 'unique:menu_items,slug'],
            'category'    => ['required', 'string'],
            'subcategory' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'minAlert'    => ['nullable', 'numeric', 'min:0'], // (if you're reusing this as base price)
            'allergies'   => ['array'],
            'allergies.*' => ['integer', 'exists:allergies,id'],
            'tags'        => ['array'],
            'tags.*'      => ['integer', 'exists:tags,id'],
            'image'       => ['nullable', 'image', 'max:2048'], // optional but recommended
        ];
    }
}
