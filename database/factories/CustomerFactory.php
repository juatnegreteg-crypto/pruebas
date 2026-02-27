<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $documentType = $this->faker->randomElement(DocumentType::cases());

        return [
            'full_name' => $this->faker->name(),
            'email' => $this->faker
                ->unique()
                ->safeEmail(),
            'document_type' => $documentType,
            'document_number' => (string) $this->faker
                ->unique()
                ->numberBetween(10000000, 9999999999),
            'phone_number' => (string) $this->faker->numerify('3##########'),
        ];
    }
}
