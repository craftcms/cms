<?php

namespace CraftCms\Cms\Database\Factories;

use craft\elements\Entry;
use CraftCms\Cms\Element\Models\Element;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ElementFactory extends Factory
{
    protected $model = Element::class;

    #[\Override]
    public function definition(): array
    {
        return [
            'type' => Entry::class,
            'dateCreated' => $created = $this->faker->dateTime(),
            'dateUpdated' => $created,
        ];
    }
}
