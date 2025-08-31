<?php
// app/Services/Reference/SupplierService.php
namespace App\Services\Reference;

use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SupplierService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $q = Supplier::query();

        if (!empty($filters['q'])) {
            $s = $filters['q'];
            $q->where(function ($qq) use ($s) {
                $qq->where('name', 'like', "%{$s}%")
                   ->orWhere('email', 'like', "%{$s}%")
                   ->orWhere('contact', 'like', "%{$s}%");
            });
        }

        return $q->latest()->paginate(15);
    }

    // public function create(array $data) {
    //     $data['user_id'] = auth()->id();
    //     return Supplier::create($data);
    // }
    public function create(array $data) {

        $supplier = new Supplier();
        $supplier->user_id         = Auth::id();
        $supplier->name            = $data['name'];
        $supplier->email           = $data['email'];
        $supplier->contact         = $data['contact']         ?? null;
        $supplier->address         = $data['address']         ?? null;
        $supplier->preferred_items = $data['preferred_items'] ?? null;

        $supplier->save();

        return $supplier;
    }

   public function update(Supplier $supplier, array $data)
    {
        // Never let user_id be changed from the request
        // unset($data['user_id']);

        // Assign fields explicitly (you’re already validating in SupplierUpdateRequest)
        $supplier->name            = $data['name'];
        $supplier->email           = $data['email'];
        $supplier->contact         = $data['contact']         ?? null;
        $supplier->address         = $data['address']         ?? null;
        $supplier->preferred_items = $data['preferred_items'] ?? null;

        $supplier->save();

        return $supplier;
    }

   public function delete(Supplier $supplier)
{
    $supplier->delete();
}

}
