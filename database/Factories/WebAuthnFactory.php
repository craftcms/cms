<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Auth\Models\WebAuthn;
use CraftCms\Cms\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

class WebAuthnFactory extends Factory
{
    #[Override]
    protected $model = WebAuthn::class;

    #[Override]
    public function definition(): array
    {
        return [
            'userId' => User::factory(),
            'credentialId' => $this->faker->uuid(),
            'dateCreated' => now(),
            'dateUpdated' => now(),
        ];
    }
}
