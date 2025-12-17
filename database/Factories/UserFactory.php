<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

final class UserFactory extends Factory
{
    protected $model = User::class;

    #[Override]
    public function definition(): array
    {
        return [
            'id' => Element::factory(),
            'username' => $this->faker->userName(),
            'email' => $this->faker->email(),
        ];
    }

    public function pending(): self
    {
        return $this->state(fn () => [
            'pending' => true,
        ]);
    }

    public function locked(): self
    {
        return $this->state(fn () => [
            'locked' => true,
            'invalidLoginCount' => 2,
            'lockoutDate' => now(),
        ]);
    }

    public function active(): self
    {
        return $this->state(fn () => [
            'active' => true,
        ]);
    }

    public function suspended(): self
    {
        return $this->state(fn () => [
            'suspended' => true,
        ]);
    }
}
