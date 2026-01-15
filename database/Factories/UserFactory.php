<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use Craft;
use CraftCms\Cms\Auth\Models\WebAuthn;
use CraftCms\Cms\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Facades\Hash;
use Override;
use RuntimeException;

final class UserFactory extends Factory
{
    protected $model = User::class;

    #[Override]
    public function definition(): array
    {
        return [
            'id' => null,
            'fullName' => $this->faker->name(),
            'firstName' => $this->faker->firstName(),
            'lastName' => $this->faker->lastName(),
            'username' => $this->faker->userName(),
            'email' => $this->faker->email(),
            'password' => Hash::make('password'),
            'active' => true,
            'pending' => false,
            'locked' => false,
            'suspended' => false,
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

    public function withPasskey(string $credentialId): self
    {
        return $this->afterCreating(fn (User $user) => WebAuthn::factory()->create([
            'userId' => $user->id,
            'credentialId' => $credentialId,
            'publicKey' => 'test-public-key',
        ]));
    }

    #[\Override]
    protected function store(Collection $results): void
    {
        $results->each(function (User $model) {
            foreach ($model->getRelations() as $name => $items) {
                if ($items instanceof Enumerable && $items->isEmpty()) {
                    $model->unsetRelation($name);
                }
            }

            if (! Craft::$app->getElements()->saveElement($element = $model->asElement())) {
                dump($element->getErrors());
                throw new RuntimeException('Could not save user.');
            }

            $model->id = $element->id;
            $model->exists = true;
            $model->save();

            $this->createChildren($model);

            $model->refresh();
            $model->uid = $element->uid;
        });
    }
}
