<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Auth\Models\WebAuthn;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Facades\Hash;
use Override;
use RuntimeException;

class UserFactory extends Factory
{
    #[Override]
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

    public function admin(bool $admin = true): self
    {
        return $this->state(fn () => [
            'admin' => $admin,
        ]);
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

    public function createElement(array $attributes = [], ?Model $parent = null): \CraftCms\Cms\User\Elements\User
    {
        return $this->create($attributes, $parent)->asElement();
    }

    public function withPasskey(string $credentialId): self
    {
        return $this->afterCreating(fn (User $user) => WebAuthn::factory()->create([
            'userId' => $user->id,
            'credentialId' => $credentialId,
        ]));
    }

    #[Override]
    protected function store(Collection $results): void
    {
        $results->each(function (User $model) {
            foreach ($model->getRelations() as $name => $items) {
                if ($items instanceof Enumerable && $items->isEmpty()) {
                    $model->unsetRelation($name);
                }
            }

            if (! Elements::saveElement($element = $model->asElement())) {
                dump($element->errors()->all());
                throw new RuntimeException('Could not save user.');
            }

            $model->id = $element->id;
            $model->exists = true;
            $model->save();

            $this->createChildren($model);

            // Ensure any password is set to the element as well.
            $element->password = $model->password;

            $model->refresh();
            $model->uid = $element->uid;
            $model->password = $element->password;
        });
    }
}
