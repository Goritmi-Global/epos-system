<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountriesTableSeeder extends Seeder
{
    public function run(): void
    {
        // Disable FK checks for truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('countries')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $countries = [
            [
                'id'          => 'GB',
                'name'        => 'United Kingdom',
                'iso3'        => 'GBR',
                'iso2'        => 'GB',
                'numeric_code'=> '826',
                'phone_code'  => '+44',
                'capital'     => 'London',
                'currency'    => 'GBP',
                'currency_name'=> 'Pound Sterling',
                'currency_symbol'=> '£',
                'tld'         => '.uk',
                'native'      => 'United Kingdom',
                'region'      => 'Europe',
                'subregion'   => 'Northern Europe',
            ],
        ];

        DB::table('countries')->insert($countries);
    }
}
