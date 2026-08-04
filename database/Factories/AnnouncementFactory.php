<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Announcement\Models\Announcement;
use CraftCms\Cms\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

class AnnouncementFactory extends Factory
{
    #[Override]
    protected $model = Announcement::class;

    #[Override]
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
        return $this->state(fn (array $attributes) => [
            'unread' => true,
            'dateRead' => null,
        ]);
    }

    public function read(): self
    {
        return $this->state(fn (array $attributes) => [
            'unread' => false,
            'dateRead' => fake()->dateTime(),
        ]);
    }
}
