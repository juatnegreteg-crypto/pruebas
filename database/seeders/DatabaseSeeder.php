<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(IamSeeder::class);
        $this->call(DemoUserSeeder::class);
        $this->call(TaxSeeder::class);

        $this->call(CustomerSeeder::class);
        $this->call(ProductSeeder::class);
        $this->call(ServiceSeeder::class);
        $this->call(BundleSeeder::class);
        $this->call(VehicleSeeder::class);
        $this->call(PartyAddressSeeder::class);
        $this->call(ScheduleSeeder::class);
        $this->call(TechnicianSeeder::class);
        $this->call(QuoteSeeder::class);
    }
}
