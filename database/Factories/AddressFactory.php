<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Addresses\Models\Address;
use CraftCms\Cms\Element\Models\Element;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

final class AddressFactory extends Factory
{
    protected $model = Address::class;

    #[Override]
    public function definition(): array
    {
        return [
            'id' => Element::factory()->set('type', \CraftCms\Cms\Element\Elements\Address::class),
            'countryCode' => fake()->countryCode(),
            'dateCreated' => $created = $this->faker->dateTime(),
            'dateUpdated' => $created,
        ];
    }
}
