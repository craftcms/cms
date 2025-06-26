<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace Craft\Cms\Database\Factories;

use Craft\Cms\Announcement\Models\Announcement;
use Craft\Cms\Plugin\Models\Plugin;
use Craft\Cms\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    public function definition(): array
    {
        return [
            'userId' => User::factory(),
            'pluginId' => null,
            'heading' => fake()->sentence(),
            'body' => fake()->paragraph(),
            'unread' => fake()->boolean(),
            'dateRead' => fake()->dateTime(),
        ];
    }

    public function unread(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'unread' => true,
                'dateRead' => null,
            ];
        });
    }

    public function read(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'unread' => false,
                'dateRead' => fake()->dateTime(),
            ];
        });
    }
}
