<?php

namespace Workbench\Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;
use Workbench\App\Models\Business;
use Workbench\App\Models\Customer;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
    {
        Customer::create([
            'first_name' => fake()->name,
            'last_name' => fake()->lastName(),
            'phone' => fake()->phoneNumber(),
        ]);

        Customer::create([
            'first_name' => fake()->name,
            'last_name' => fake()->lastName(),
            'phone' => fake()->phoneNumber(),
        ]);

        Customer::create([
            'first_name' => fake()->name,
            'last_name' => fake()->lastName(),
            'phone' => fake()->phoneNumber(),
        ]);

        foreach (Customer::all() as $row) {
            Business::create(
                [
                    'id_customer' => $row->id,
                    'name' => fake()->company,
                ]
            );
        }
    }
}
