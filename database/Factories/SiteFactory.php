<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Site\Models\SiteGroup;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Translation\I18N;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

class SiteFactory extends Factory
{
    #[Override]
    protected $model = Site::class;

    #[Override]
    public function definition(): array
    {
        return [
            'uid' => $this->faker->uuid(),
            'groupId' => SiteGroup::factory(),
            'handle' => $this->faker->slug(),
            'name' => $this->faker->words(asText: true),
            'primary' => false,
            'language' => $this->faker->randomElement(app(I18N::class)->getAllLocaleIds()->all()),
            'sortOrder' => $this->faker->numberBetween(1, 100),
        ];
    }

    #[Override]
    public function configure(): self
    {
        return $this->afterCreating(function (Site $site) {
            app(Sites::class)->refreshSites();
        });
    }
}
