<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:meals,name',
            // Accept both H:i and H:i:s formats
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Meal name is required',
            'name.unique' => 'A meal with this name already exists',

            'start_time.required' => 'Start time is required',
            'start_time.date_format' => 'Start time must be in HH:MM format',

            'end_time.required' => 'End time is required',
            'end_time.date_format' => 'End time must be in HH:MM format',
            'end_time.after' => 'End time must be after start time',
        ];
    }
    
    /**
     * Prepare data for validation
     */
    protected function prepareForValidation()
    {
        // Ensure time format is consistent
        if ($this->start_time) {
            $this->merge([
                'start_time' => $this->formatTimeString($this->start_time),
            ]);
        }
        
        if ($this->end_time) {
            $this->merge([
                'end_time' => $this->formatTimeString($this->end_time),
            ]);
        }
    }
    
    /**
     * Format time string to H:i format
     */
    private function formatTimeString($time)
    {
        // If already in correct format, return as is
        if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            return $time;
        }
        
        // If it's a full datetime, extract time
        try {
            $dt = new \DateTime($time);
            return $dt->format('H:i');
        } catch (\Exception $e) {
            return $time;
        }
    }
}