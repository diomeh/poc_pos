<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed the users table.
     *
     * Creates admin and cashier users for the POS system.
     */
    public function run(): void
    {
        // Create admin user
        User::factory()->create([
            'name'  => 'Admin',
            'email' => 'admin@example.com',
        ]);

        // Create cashier users
        User::factory()->create([
            'name'  => 'Cashier One',
            'email' => 'cashier1@example.com',
        ]);

        User::factory()->create([
            'name'  => 'Cashier Two',
            'email' => 'cashier2@example.com',
        ]);

        // Create additional random users/cashiers
        User::factory(5)->create();
    }
}
