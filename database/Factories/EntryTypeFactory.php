<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

class EntryTypeFactory extends Factory
{
    #[Override]
    protected $model = EntryType::class;

    #[Override]
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

    public function withFieldLayout(FieldLayout $layout): self
    {
        return $this->state(fn () => [
            'fieldLayoutId' => $layout->id,
        ]);
    }
}
