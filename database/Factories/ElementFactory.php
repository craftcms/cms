<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Override;

class ElementFactory extends Factory
{
    #[Override]
    protected $model = Element::class;

    public function title(string $title): static
    {
        return $this->afterCreating(function (Element $element) use ($title) {
            DB::table(Table::ELEMENTS_SITES)
                ->where('elementId', $element->id)
                ->update(['title' => $title]);
        });
    }

    public function slug(string $slug): static
    {
        return $this->afterCreating(function (Element $element) use ($slug) {
            DB::table(Table::ELEMENTS_SITES)
                ->where('elementId', $element->id)
                ->update(['slug' => $slug]);
        });
    }

    #[Override]
    public function definition(): array
    {
        return [
            'type' => Entry::class,
            'enabled' => true,
            'dateCreated' => $created = $this->faker->dateTime(),
            'dateUpdated' => $created,
        ];
    }

    #[Override]
    public function configure(): self
    {
        return $this->afterCreating(function (Element $element) {
            DB::table(Table::ELEMENTS_SITES)
                ->insert([
                    'elementId' => $element->id,
                    'title' => fake()->words(asText: true),
                    'slug' => fake()->slug(),
                    'uri' => fake()->slug(),
                    'enabled' => true,
                    'siteId' => Site::first()->id,
                    'dateCreated' => $element->dateCreated,
                    'dateUpdated' => $element->dateUpdated,
                    'uid' => Str::uuid()->toString(),
                ]);
        });
    }
}
