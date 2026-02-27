<?php

namespace Database\Seeders;

use App\Models\Bundle;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Database\Seeder;

class BundleSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $oilChange = Bundle::query()->create([
            'name' => 'Cambio de aceite',
            'description' => 'Incluye insumos y mano de obra para cambio de aceite.',
            'price' => 155000.00,
            'currency' => 'COP',
            'is_active' => true,
        ]);

        $preventive = Bundle::query()->create([
            'name' => 'Mantenimiento preventivo',
            'description' => 'Paquete completo para mantenimiento básico del vehículo.',
            'price' => 245000.00,
            'currency' => 'COP',
            'is_active' => true,
        ]);

        $inspection = Bundle::query()->create([
            'name' => 'Inspección general',
            'description' => 'Revisión integral de sistemas críticos del vehículo.',
            'price' => 185000.00,
            'currency' => 'COP',
            'is_active' => true,
        ]);

        $oilProduct = Product::query()->create([
            'name' => 'Aceite 5W-30',
            'description' => 'Aceite sintético 5W-30 para motor.',
            'price' => 65000.00,
            'currency' => 'COP',
            'is_active' => true,
        ]);

        $oilFilter = Product::query()->create([
            'name' => 'Filtro de aceite',
            'description' => 'Filtro de aceite premium.',
            'price' => 35000.00,
            'currency' => 'COP',
            'is_active' => true,
        ]);

        $laborOil = Service::query()->create([
            'name' => 'Mano de obra cambio de aceite',
            'description' => 'Servicio técnico para cambio de aceite.',
            'price' => 45000.00,
            'currency' => 'COP',
            'is_active' => true,
        ]);

        $brakeCheck = Service::query()->create([
            'name' => 'Revisión de frenos',
            'description' => 'Inspección de pastillas, discos y líquido.',
            'price' => 60000.00,
            'currency' => 'COP',
            'is_active' => true,
        ]);

        $suspensionCheck = Service::query()->create([
            'name' => 'Revisión de suspensión',
            'description' => 'Evaluación de amortiguadores y componentes.',
            'price' => 55000.00,
            'currency' => 'COP',
            'is_active' => true,
        ]);

        $levelsCheck = Service::query()->create([
            'name' => 'Revisión de niveles',
            'description' => 'Chequeo de fluidos y reposición básica.',
            'price' => 30000.00,
            'currency' => 'COP',
            'is_active' => true,
        ]);

        $this->attachItems($oilChange, [$oilProduct, $oilFilter], [$laborOil]);

        $this->attachItems($preventive, [$oilProduct], [$brakeCheck]);
        $this->attachBundles($preventive, [$oilChange]);

        $this->attachItems($inspection, [$oilFilter], [$suspensionCheck, $levelsCheck]);
    }

    /**
     * @param  array<int, Product>  $products
     * @param  array<int, Service>  $services
     */
    private function attachItems(Bundle $bundle, array $products, array $services): void
    {
        if ($products) {
            $bundle->products()->syncWithoutDetaching(
                collect($products)->mapWithKeys(fn (Product $product) => [$product->catalog_item_id => ['quantity' => 1]])->all()
            );
        }

        if ($services) {
            $bundle->services()->syncWithoutDetaching(
                collect($services)->mapWithKeys(fn (Service $service) => [$service->catalog_item_id => ['quantity' => 1]])->all()
            );
        }
    }

    /**
     * @param  array<int, Bundle>  $bundles
     */
    private function attachBundles(Bundle $bundle, array $bundles): void
    {
        if (! $bundles) {
            return;
        }

        $bundle->bundles()->syncWithoutDetaching(
            collect($bundles)->mapWithKeys(fn (Bundle $child) => [$child->catalog_item_id => ['quantity' => 1]])->all()
        );
    }
}
