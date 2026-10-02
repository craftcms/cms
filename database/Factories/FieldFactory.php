<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

class FieldFactory extends Factory
{
    #[Override]
    protected $model = Field::class;

    #[Override]
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(asText: true),
            'handle' => $this->faker->slug(1),
            'type' => PlainText::class,
            'dateCreated' => $created = $this->faker->dateTime(),
            'dateUpdated' => $created,
        ];
    }
}
