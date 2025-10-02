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
            [
                'id'          => 'PK',
                'name'        => 'Pakistan',
                'iso3'        => 'PAK',
                'iso2'        => 'PK',
                'numeric_code'=> '586',
                'phone_code'  => '+92',
                'capital'     => 'Islamabad',
                'currency'    => 'PKR',
                'currency_name'=> 'Pakistani Rupee',
                'currency_symbol'=> '₨',
                'tld'         => '.pk',
                'native'      => 'Pakistan',
                'region'      => 'Asia',
                'subregion'   => 'Southern Asia',
            ],
            [
                'id'          => 'IN',
                'name'        => 'India',
                'iso3'        => 'IND',
                'iso2'        => 'IN',
                'numeric_code'=> '356',
                'phone_code'  => '+91',
                'capital'     => 'New Delhi',
                'currency'    => 'INR',
                'currency_name'=> 'Indian Rupee',
                'currency_symbol'=> '₹',
                'tld'         => '.in',
                'native'      => 'भारत',
                'region'      => 'Asia',
                'subregion'   => 'Southern Asia',
            ],
            [
                'id'          => 'AE',
                'name'        => 'United Arab Emirates',
                'iso3'        => 'ARE',
                'iso2'        => 'AE',
                'numeric_code'=> '784',
                'phone_code'  => '+971',
                'capital'     => 'Abu Dhabi',
                'currency'    => 'AED',
                'currency_name'=> 'United Arab Emirates Dirham',
                'currency_symbol'=> 'د.إ',
                'tld'         => '.ae',
                'native'      => 'الإمارات',
                'region'      => 'Asia',
                'subregion'   => 'Western Asia',
            ],
            [
                'id'          => 'US',
                'name'        => 'United States',
                'iso3'        => 'USA',
                'iso2'        => 'US',
                'numeric_code'=> '840',
                'phone_code'  => '+1',
                'capital'     => 'Washington, D.C.',
                'currency'    => 'USD',
                'currency_name'=> 'United States Dollar',
                'currency_symbol'=> '$',
                'tld'         => '.us',
                'native'      => 'United States',
                'region'      => 'Americas',
                'subregion'   => 'Northern America',
            ],
        ];

        DB::table('countries')->insert($countries);
    }
}
