<?php
// app/Models/RestaurantProfile.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestaurantProfile extends Model
{
    protected $fillable = [
        'user_id','status',
        'timezone','language','languages_supported',
        'business_name','legal_name','phone','email','address','website','logo_path',
        'currency','currency_symbol_position','number_format','date_format','time_format',
        'is_tax_registered','tax_type','tax_rate','extra_tax_rates','price_includes_tax',
        'order_types','table_management_enabled','online_ordering_enabled','number_of_tables',
        'receipt_header','receipt_footer','receipt_logo_path','show_qr_on_receipt',
        'tax_breakdown_on_receipt','kitchen_printer_enabled','printers',
        'cash_enabled','card_enabled','integrated_terminal','custom_payment_options','default_payment_method',
        'attendance_policy','business_hours','auto_disable_after_hours',
        'loyalty_enabled','cloud_backup_enabled','theme','inventory_tracking_enabled','multi_location_enabled',
    ];

    protected $casts = [
        'languages_supported'        => 'array',
        'extra_tax_rates'            => 'array',
        'order_types'                => 'array',
        'printers'                   => 'array',
        'custom_payment_options'     => 'array',
        'attendance_policy'          => 'array',
        'business_hours'             => 'array',
        'is_tax_registered'          => 'boolean',
        'price_includes_tax'         => 'boolean',
        'table_management_enabled'   => 'boolean',
        'online_ordering_enabled'    => 'boolean',
        'show_qr_on_receipt'         => 'boolean',
        'tax_breakdown_on_receipt'   => 'boolean',
        'kitchen_printer_enabled'    => 'boolean',
        'cash_enabled'               => 'boolean',
        'card_enabled'               => 'boolean',
        'auto_disable_after_hours'   => 'boolean',
        'loyalty_enabled'            => 'boolean',
        'cloud_backup_enabled'       => 'boolean',
        'inventory_tracking_enabled' => 'boolean',
        'multi_location_enabled'     => 'boolean',
        'tax_rate'                   => 'decimal:2',
    ];
}
