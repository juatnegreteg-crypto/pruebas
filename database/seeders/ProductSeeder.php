<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Inspección técnica vehicular',
                'description' => 'Servicio de inspección y certificación técnica para vehículos livianos.',
                'cost' => 95000.00,
                'price' => 95000.00,
                'currency' => 'COP',
                'unit' => 'unit',
                'is_active' => true,
            ],
            [
                'name' => 'Revisión de emisiones',
                'description' => 'Medición y validación de gases según normativa vigente.',
                'cost' => 55000.00,
                'price' => 55000.00,
                'currency' => 'COP',
                'unit' => 'unit',
                'is_active' => true,
            ],
            [
                'name' => 'Certificado duplicado',
                'description' => 'Emisión de certificado en caso de pérdida o daño.',
                'cost' => 25000.00,
                'price' => 25000.00,
                'currency' => 'COP',
                'unit' => 'unit',
                'is_active' => true,
            ],
            [
                'name' => 'Inspección motocicletas',
                'description' => 'Inspección técnica completa para motocicletas.',
                'cost' => 65000.00,
                'price' => 65000.00,
                'currency' => 'COP',
                'unit' => 'unit',
                'is_active' => true,
            ],
            [
                'name' => 'Servicio express',
                'description' => 'Atención prioritaria con tiempos reducidos de espera.',
                'cost' => 35000.00,
                'price' => 35000.00,
                'currency' => 'COP',
                'unit' => 'unit',
                'is_active' => false,
            ],
        ];

        foreach ($products as $product) {
            Product::query()->create($product);
        }
    }
}
