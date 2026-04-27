<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Section\Models\SectionSiteSettings;
use CraftCms\Cms\Site\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

class SectionFactory extends Factory
{
    #[Override]
    protected $model = Section::class;

    #[Override]
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

    #[Override]
    public function configure(): self
    {
        return $this->afterCreating(function (Section $section) {
            SectionSiteSettings::factory()->create([
                'sectionId' => $section->id,
                'siteId' => Site::first()->id,
                'dateCreated' => $section->dateCreated,
                'dateUpdated' => $section->dateUpdated,
            ]);
        });
    }

    public function withEntryTypes(EntryType ...$types): static
    {
        return $this->afterCreating(function (Section $section) use ($types) {
            foreach ($types as $index => $type) {
                $section->entryTypes()->attach($type, ['sortOrder' => $index + 1]);
            }
        });
    }

    public function withSites(Site ...$sites): static
    {
        return $this->afterCreating(function (Section $section) use ($sites) {
            foreach ($sites as $site) {
                SectionSiteSettings::query()->firstOrCreate([
                    'sectionId' => $section->id,
                    'siteId' => $site->id,
                ], [
                    'uid' => (string) str()->uuid(),
                    'dateCreated' => $section->dateCreated,
                    'dateUpdated' => $section->dateUpdated,
                    'hasUrls' => true,
                ]);
            }

            app(Fields::class)->invalidateCaches();
            app(Fields::class)->refreshFields();
        });
    }
}
