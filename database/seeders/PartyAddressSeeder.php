<?php

namespace Database\Seeders;

use App\Models\PartyAddress;
use Illuminate\Database\Seeder;

class PartyAddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PartyAddress::factory()
            ->count(10)
            ->create();
    }
}
