<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Address\Models\Address;
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
            'id' => Element::factory()->set('type', \CraftCms\Cms\Address\Elements\Address::class),
            'countryCode' => fake()->countryCode(),
            'dateCreated' => $created = $this->faker->dateTime(),
            'dateUpdated' => $created,
        ];
    }

    public function createElement(array $attributes = []): \CraftCms\Cms\Address\Elements\Address
    {
        $model = $this->create($attributes);

        return \CraftCms\Cms\Address\Elements\Address::find()->id($model->id)->one();
    }
}
