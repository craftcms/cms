<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace Craft\Cms\Database\Factories;

use Craft\Cms\Plugin\Models\Plugin;
use Illuminate\Database\Eloquent\Factories\Factory;

class PluginFactory extends Factory
{
    protected $model = Plugin::class;

    public function definition()
    {
        return [
            'uid' => $this->faker->uuid(),
            'handle' => $this->faker->slug(),
            'version' => $this->faker->semver(),
            'schemaVersion' => $this->faker->semver(),
            'installDate' => $this->faker->dateTime(),
        ];
    }
}
