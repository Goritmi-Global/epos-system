<?php

namespace App\Http\Requests\Reference;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUnitRequest extends FormRequest
{
    public function authorize()
    {
        return true; // tweak this if you have permission logic
    }

    public function rules()
    {
        $unitId = $this->route('unit') ? $this->route('unit')->id : null;

        return [
            'name' => 'required|string|max:100|unique:units,name,'.$unitId,
            'base_unit_id' => 'nullable|exists:units,id',
            'conversion_factor' => 'nullable|numeric|min:0',
        ];
    }
}
