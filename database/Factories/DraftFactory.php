<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use craft\elements\Entry;
use CraftCms\Cms\Element\Models\Draft;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

final class DraftFactory extends Factory
{
    protected $model = Draft::class;

    #[\Override]
    public function definition(): array
    {
        return [
            'canonicalId' => Element::factory(),
            'creatorId' => User::factory(),
            'provisional' => fake()->boolean(),
            'name' => fake()->words(asText: true),
            'trackChanges' => fake()->boolean(),
            'dateLastMerged' => now(),
            'saved' => fake()->boolean(),
        ];
    }

    #[\Override]
    public function configure(): self
    {
        return $this->afterCreating(function (Draft $draft) {
            $element = Element::create([
                'type' => Entry::class,
                'canonicalId' => $draft->canonicalId,
            ]);

            $draft->update(['id' => $element->id]);
        });
    }
}
