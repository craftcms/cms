<?php

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Element\Models\EntryType;
use Illuminate\Database\Eloquent\Factories\Factory;

final class EntryTypeFactory extends Factory
{
    protected $model = EntryType::class;

    #[\Override]
    public function definition(): array
    {
        return [
            'uid' => $this->faker->uuid(),
            'name' => $this->faker->words(asText: true),
            'handle' => $this->faker->slug(),
            'dateCreated' => $this->faker->dateTime(),
            'dateUpdated' => $this->faker->dateTime(),
        ];
    }
}
