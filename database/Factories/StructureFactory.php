<?php

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Structure\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

final class StructureFactory extends Factory
{
    protected $model = Structure::class;

    #[\Override]
    public function definition(): array
    {
        return [];
    }
}
