<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Section\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

final class EntryFactory extends Factory
{
    protected $model = Entry::class;

    #[\Override]
    public function definition(): array
    {
        return [
            'id' => Element::factory()->set('type', \craft\elements\Entry::class),
            'sectionId' => Section::factory(),
            'typeId' => EntryType::factory(),
            'status' => \craft\elements\Entry::STATUS_LIVE,
            'postDate' => $created = $this->faker->dateTime(),
            'dateCreated' => $created,
            'dateUpdated' => $created,
        ];
    }
}
