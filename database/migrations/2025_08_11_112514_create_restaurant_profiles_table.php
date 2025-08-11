<?php

// database/migrations/2025_08_11_000000_create_restaurant_profiles_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('restaurant_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // owner
            $table->string('status')->default('draft'); // draft|complete

            /** Step 1: Welcome & Language */
            $table->string('timezone')->nullable();
            $table->string('language')->nullable();              // e.g. en, ur
            $table->json('languages_supported')->nullable();     // ["en","ur"]

            /** Step 2: Business Information */
            $table->string('business_name')->nullable();
            $table->string('legal_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('website')->nullable();
            $table->string('logo_path')->nullable();

            /** Step 3: Currency & Locale */
            $table->string('currency')->nullable();              // USD, PKR
            $table->string('currency_symbol_position')->nullable(); // before|after
            $table->string('number_format')->nullable();         // 1,000.00 or 1.000,00
            $table->string('date_format')->nullable();           // DD/MM/YYYY
            $table->string('time_format')->nullable();           // 12-hour|24-hour

            /** Step 4: Tax & VAT */
            $table->boolean('is_tax_registered')->default(false);
            $table->string('tax_type')->nullable();              // VAT, GST, Sales Tax
            $table->decimal('tax_rate', 8, 2)->nullable();       // 5.00, 15.00
            $table->json('extra_tax_rates')->nullable();         // [{name,rate}]
            $table->boolean('price_includes_tax')->default(false);

            /** Step 5: Order Type & Services */
            $table->json('order_types')->nullable();             // ["dine_in","takeaway","delivery","collection"]
            $table->boolean('table_management_enabled')->default(false);
            $table->boolean('online_ordering_enabled')->default(false);
            $table->unsignedInteger('number_of_tables')->nullable();

            /** Step 6: Receipt & Printers */
            $table->text('receipt_header')->nullable();
            $table->text('receipt_footer')->nullable();
            $table->string('receipt_logo_path')->nullable();
            $table->boolean('show_qr_on_receipt')->default(false);
            $table->boolean('tax_breakdown_on_receipt')->default(true);
            $table->boolean('kitchen_printer_enabled')->default(false);
            $table->json('printers')->nullable(); // {receipt:{...}, kitchen:{...}}

            /** Step 7: Payment Methods */
            $table->boolean('cash_enabled')->default(true);
            $table->boolean('card_enabled')->default(false);
            $table->string('integrated_terminal')->nullable();   // SumUp, Square
            $table->json('custom_payment_options')->nullable();  // ["JustEat",...]
            $table->string('default_payment_method')->nullable();// Cash, Card, etc.

            /** Step 8: Users & Roles Policy (wizard creates users elsewhere) */
            $table->json('attendance_policy')->nullable(); // {clock_enabled, require_shift, track_location, allow_late_clockin, require_reason}
            
            /** Step 9: Business Hours */
            $table->json('business_hours')->nullable(); // {mon:{open:"09:00",close:"17:00",closed:false}, ...}
            $table->boolean('auto_disable_after_hours')->default(true);

            /** Step 10: Optional Features */
            $table->boolean('loyalty_enabled')->default(false);
            $table->boolean('cloud_backup_enabled')->default(false);
            $table->string('theme')->nullable(); // light|dark|custom
            $table->boolean('inventory_tracking_enabled')->default(true);
            $table->boolean('multi_location_enabled')->default(false);

            $table->timestamps();
        });

        Schema::create('onboarding_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('current_step')->default(1); // 1..10
            $table->json('completed_steps')->nullable();             // [1,2,3]
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_progress');
        Schema::dropIfExists('restaurant_profiles');
    }
};
