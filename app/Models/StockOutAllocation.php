<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOutAllocation extends Model
{
    protected $fillable = [
        'stock_out_entry_id','stock_in_entry_id','product_id',
        'quantity','unit_price','expiry_date'
    ];

    public function stockIn()  { return $this->belongsTo(StockEntry::class, 'stock_in_entry_id'); }
    public function stockOut() { return $this->belongsTo(StockEntry::class, 'stock_out_entry_id'); }
}
