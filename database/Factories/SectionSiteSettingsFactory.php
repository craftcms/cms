<?php

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Section\Models\SectionSiteSettings;
use CraftCms\Cms\Site\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

final class SectionSiteSettingsFactory extends Factory
{
    protected $model = SectionSiteSettings::class;

    #[\Override]
    public function definition(): array
    {
        return [
            'uid' => $this->faker->uuid(),
            'sectionId' => Section::factory(),
            'siteId' => Site::factory(),
            'hasUrls' => $this->faker->boolean(),
            'dateCreated' => $this->faker->dateTime(),
            'dateUpdated' => $this->faker->dateTime(),
        ];
    }
}
