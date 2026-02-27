<?php

namespace Database\Factories;

use App\Enums\PartyAddressType;
use App\Models\Party;
use App\Models\PartyAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<PartyAddress>
 */
class PartyAddressFactory extends Factory
{
    protected $model = PartyAddress::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(PartyAddressType::cases());

        return [
            'party_id' => Party::query()->create([
                'type' => 'person',
                'display_name' => $this->faker->name(),
                'is_active' => true,
            ])->id,
            'type' => $type->value,
            'is_primary' => true,
            'street' => $this->faker->streetAddress(),
            'complement' => $this->faker->optional()->secondaryAddress(),
            'neighborhood' => $this->faker->optional()->citySuffix(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'postal_code' => $this->faker->optional()->postcode(),
            'country' => $this->faker->randomElement(['Colombia', 'Perú', 'Ecuador']),
            'reference' => $this->faker->optional()->sentence(3),
        ];
    }
}
