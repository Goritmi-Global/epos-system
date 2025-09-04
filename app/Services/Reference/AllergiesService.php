<?php

namespace App\Services\Reference;

use App\Models\Allergy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AllergiesService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $q = Allergy::query();

        if (!empty($filters['q'])) {
            $s = $filters['q'];
            $q->where('name', 'like', "%{$s}%");
        }

        return $q->latest()->paginate(15);
    }

    public function create(array $data)
    { 
        // dd($data);
        // Loop through each allergy name
        foreach ($data['allergies'] as $name) { 
            $allergy = new Allergy();
            $allergy->name = $name['name'];
            $allergy->save();
        }

        return true;
    }

    public function update(Allergy $allergy, array $data): Allergy
    {
        // if it's a single value
        if (isset($data['name'])) {
            $allergy->name = $data['name'];
        }

        $allergy->save();

        return $allergy;
    }

    public function delete(Allergy $allergy): void
    {
        $allergy->delete();
    }
}
