<?php

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

final class SectionFactory extends Factory
{
    protected $model = Section::class;

    #[\Override]
    public function definition(): array
    {
        return [
            'uid' => $this->faker->uuid(),
            'name' => $this->faker->words(asText: true),
            'handle' => $this->faker->slug(),
            'type' => SectionType::Channel,
            'enableVersioning' => $this->faker->boolean(),
            'dateCreated' => $this->faker->dateTime(),
            'dateUpdated' => $this->faker->dateTime(),
        ];
    }
}
