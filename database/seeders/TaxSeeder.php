<?php

namespace Database\Seeders;

use App\Models\Tax;
use Illuminate\Database\Seeder;

class TaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $taxes = [
            [
                'name' => 'IVA general',
                'code' => 'IVA-19',
                'jurisdiction' => 'Colombia',
                'rate' => 19,
            ],
            [
                'name' => 'IVA reducido',
                'code' => 'IVA-5',
                'jurisdiction' => 'Colombia',
                'rate' => 5,
            ],
            [
                'name' => 'IVA 0%',
                'code' => 'IVA-0',
                'jurisdiction' => 'Colombia',
                'rate' => 0,
            ],
            [
                'name' => 'Aranceles',
                'code' => 'ARANCEL-VAR',
                'jurisdiction' => 'Colombia',
                'rate' => 0,
            ],
            [
                'name' => 'IVA sobre importacion',
                'code' => 'IVA-IMP-19',
                'jurisdiction' => 'Colombia',
                'rate' => 19,
            ],
            [
                'name' => 'Impuesto al consumo (INC)',
                'code' => 'INC-VAR',
                'jurisdiction' => 'Colombia',
                'rate' => 0,
            ],
            [
                'name' => 'Impuestos saludables',
                'code' => 'SALUDABLE-VAR',
                'jurisdiction' => 'Colombia',
                'rate' => 0,
            ],
        ];

        foreach ($taxes as $tax) {
            Tax::query()->updateOrCreate(
                ['code' => $tax['code']],
                $tax,
            );
        }
    }
}
