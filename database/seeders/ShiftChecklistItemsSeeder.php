<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShiftChecklistItem;

class ShiftChecklistItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ========================================
        // OPENING SHIFT CHECKLISTS (type = 'start')
        // ========================================
        $openingChecklists = [
            [
                'name' => 'Fridges and freezers working',
                'description' => 'Check all cooling units are at the right temperature.',
                'type' => 'start',
            ],
            [
                'name' => 'Cooking equipment functional',
                'description' => 'Ensure ovens and other kitchen tools work properly.',
                'type' => 'start',
            ],
            [
                'name' => 'Staff ready and dressed',
                'description' => 'All staff should be fit and in clean uniforms.',
                'type' => 'start',
            ],
            [
                'name' => 'Prep areas cleaned',
                'description' => 'Surfaces and utensils must be sanitized.',
                'type' => 'start',
            ],
            [
                'name' => 'No pest signs',
                'description' => 'Inspect all areas for any pest activity.',
                'type' => 'start',
            ],
            [
                'name' => 'Handwash items available',
                'description' => 'Soap, paper towels, and sanitizer are stocked.',
                'type' => 'start',
            ],
            [
                'name' => 'Hot water available',
                'description' => 'Check sinks and basins for running hot water.',
                'type' => 'start',
            ],
            [
                'name' => 'Thermometer working',
                'description' => 'Test probe and ensure wipes are available.',
                'type' => 'start',
            ],
            [
                'name' => 'Allergen info updated',
                'description' => 'Confirm allergen labels are accurate.',
                'type' => 'start',
            ],
            [
                'name' => 'Daily cleaning done',
                'description' => 'Verify cleaning tasks are completed.',
                'type' => 'start',
            ],
        ];

        // ========================================
        // CLOSING SHIFT CHECKLISTS (type = 'end')
        // ========================================
        $closingChecklists = [
            [
                'name' => 'All food is covered, labelled and put in the fridge/freezer',
                'description' => 'Where appropriate, ensure all food is properly stored and labelled.',
                'type' => 'end',
            ],
            [
                'name' => 'Food on its Use By date has been thrown away',
                'description' => 'Check and dispose of expired food items.',
                'type' => 'end',
            ],
            [
                'name' => 'Dirty cleaning equipment has been cleaned or thrown away',
                'description' => 'Sanitize or discard all used cleaning equipment.',
                'type' => 'end',
            ],
            [
                'name' => 'Waste has been removed and new bags put into the bins',
                'description' => 'Empty all waste bins and replace with fresh bags.',
                'type' => 'end',
            ],
            [
                'name' => 'Food preparation areas are clean and disinfected',
                'description' => 'Work surfaces, equipment, and utensils must be sanitized.',
                'type' => 'end',
            ],
            [
                'name' => 'All washing up has been finished',
                'description' => 'Ensure all dishes and utensils are washed and dried.',
                'type' => 'end',
            ],
            [
                'name' => 'Floors are swept and clean',
                'description' => 'Clean floors thoroughly in all areas.',
                'type' => 'end',
            ],
            [
                'name' => 'Prove it checks have been recorded',
                'description' => 'Document all quality assurance checks performed.',
                'type' => 'end',
            ],
            [
                'name' => 'Cleaning has been carried out according to the cleaning schedule',
                'description' => 'Verify that all scheduled cleaning tasks have been completed.',
                'type' => 'end',
            ],
        ];

        // ========================================
        // Merge both arrays
        // ========================================
        $allChecklists = array_merge($openingChecklists, $closingChecklists);

        // ========================================
        // Insert or update each checklist item
        // ========================================
        foreach ($allChecklists as $checklist) {
            ShiftChecklistItem::updateOrCreate(
                ['name' => $checklist['name']],
                [
                    'description' => $checklist['description'],
                    'is_default' => true,
                    'type' => $checklist['type'],
                ]
            );
        }
    }
}