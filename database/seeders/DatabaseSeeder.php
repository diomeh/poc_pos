<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Orchestrates all seeders in the correct order based on dependencies.
     */
    public function run(): void
    {
        // Seed base entities (no dependencies)
        $this->call([
            UserSeeder::class,
            CustomerSeeder::class,
            CategorySeeder::class,
        ]);

        // Seed products (depends on categories)
        $this->call(ProductSeeder::class);

        // Seed transactions (depends on users, customers, products)
        $this->call(TransactionSeeder::class);
    }
}
