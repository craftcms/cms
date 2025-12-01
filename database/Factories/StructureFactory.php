<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\Structure\Models\Structure;
use CraftCms\Cms\Structure\Models\StructureElement;
use Illuminate\Database\Eloquent\Factories\Factory;

final class StructureFactory extends Factory
{
    protected $model = Structure::class;

    #[\Override]
    public function definition(): array
    {
        return [
            'dateCreated' => $created = $this->faker->dateTime(),
            'dateUpdated' => $created,
        ];
    }

    #[\Override]
    public function configure(): self
    {
        return $this->afterCreating(function (Structure $structure) {
            $element = new StructureElement([
                'structureId' => $structure->id,
                'elementId' => Element::factory()->create()->id,
            ]);

            $element->makeRoot();
        });
    }
}
