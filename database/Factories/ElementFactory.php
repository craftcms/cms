<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use craft\elements\Entry;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

final class ElementFactory extends Factory
{
    protected $model = Element::class;

    #[\Override]
    public function definition(): array
    {
        return [
            'type' => Entry::class,
            'enabled' => true,
            'dateCreated' => $created = $this->faker->dateTime(),
            'dateUpdated' => $created,
        ];
    }

    #[\Override]
    public function configure(): self
    {
        return $this->afterCreating(function (Element $element) {
            DB::table(Table::ELEMENTS_SITES)
                ->insert([
                    'elementId' => $element->id,
                    'enabled' => true,
                    'siteId' => Site::first()->id,
                    'dateCreated' => $element->dateCreated,
                    'dateUpdated' => $element->dateUpdated,
                    'uid' => Str::uuid()->toString(),
                ]);
        });
    }
}
