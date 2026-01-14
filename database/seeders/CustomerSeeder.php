<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Seed the customers table.
     *
     * Creates a variety of customers representing different customer profiles.
     */
    public function run(): void
    {
        // Create specific VIP/regular customers
        Customer::factory()->create([
            'name'    => 'John Doe',
            'email'   => 'john.doe@example.com',
            'phone'   => '+1234567890',
            'address' => '123 Main St, Springfield, USA',
        ]);

        Customer::factory()->create([
            'name'    => 'Jane Smith',
            'email'   => 'jane.smith@example.com',
            'phone'   => '+1234567891',
            'address' => '456 Oak Ave, Springfield, USA',
        ]);

        Customer::factory()->create([
            'name'    => 'Corporate Customer LLC',
            'email'   => 'contact@corporate.com',
            'phone'   => '+1234567892',
            'address' => '789 Business Blvd, Springfield, USA',
        ]);

        // Create walk-in customer (minimal info)
        Customer::factory()->create([
            'name'    => 'Walk-in Customer',
            'email'   => 'walkin@pos.local',
            'phone'   => null,
            'address' => null,
        ]);

        // Create additional random customers
        Customer::factory(3)->create();
    }
}
