<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;

class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        // Create some categories
        $categories = [
            'Unisex Apparel',
            'Performance Nutrition',
            'Advanced Supplements',
            'Gym Accessories',
        ];

        $categoryModels = [];

        foreach ($categories as $name) {
            $categoryModels[$name] = Category::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name'       => $name,
                    'description'=> null,
                    'is_active'  => true,
                ]
            );
        }

        // Simple demo products
        Product::firstOrCreate(
            ['slug' => 'whey-protein-1kg'],
            [
                'category_id'        => $categoryModels['Performance Nutrition']->id,
                'name'               => 'Whey Protein Isolate 1kg',
                'short_description'  => 'High-protein, low-sugar whey isolate.',
                'price_penny'        => 2499,
                'old_price_penny'    => 2999,
                'image_url'          => 'products/whey-1kg.jpg',
                'is_active'          => true,
            ]
        );

        Product::firstOrCreate(
            ['slug' => 'training-hoodie'],
            [
                'category_id'        => $categoryModels['Unisex Apparel']->id,
                'name'               => 'Unisex Training Hoodie',
                'short_description'  => 'Soft, breathable hoodie for warm-ups.',
                'price_penny'        => 3900,
                'old_price_penny'    => null,
                'image_url'          => 'products/hoodie.jpg',
                'is_active'          => true,
            ]
        );

        Product::firstOrCreate(
            ['slug' => 'lifting-straps'],
            [
                'category_id'        => $categoryModels['Gym Accessories']->id,
                'name'               => 'Lifting Straps',
                'short_description'  => 'Extra grip for heavy pulls.',
                'price_penny'        => 1299,
                'old_price_penny'    => null,
                'image_url'          => 'products/straps.jpg',
                'is_active'          => true,
            ]
        );
    }
}