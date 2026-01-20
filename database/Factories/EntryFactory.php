<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Section\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

final class EntryFactory extends Factory
{
    protected $model = Entry::class;

    #[Override]
    public function definition(): array
    {
        return [
            'id' => Element::factory()->set('type', \craft\elements\Entry::class),
            'sectionId' => Section::factory(),
            'typeId' => EntryType::factory(),
            'status' => \craft\elements\Entry::STATUS_LIVE,
            'postDate' => $created = $this->faker->dateTime(),
            'dateCreated' => $created,
            'dateUpdated' => $created,
        ];
    }

    #[Override]
    public function configure(): self
    {
        return $this->afterCreating(function (Entry $entry) {
            $entry->element?->update([
                'dateCreated' => $entry->postDate,
                'dateUpdated' => $entry->postDate,
            ]);

            if (! $entry->section->entryTypes()->where('id', $entry->entryType->id)->exists()) {
                $entry->section->entryTypes()->attach($entry->entryType, ['sortOrder' => 1]);
            }
        });
    }

    public function trashed(bool $trashed = true): self
    {
        return $this->state(fn (array $attributes) => [
            'id' => $attributes['id']->trashed($trashed),
        ]);
    }

    public function archived(bool $archived = true): self
    {
        return $this->state(fn (array $attributes) => [
            'id' => $attributes['id']->set('archived', $archived),
        ]);
    }

    public function enabled(bool $enabled = true): self
    {
        return $this->state(fn (array $attributes) => [
            'id' => $attributes['id']->set('enabled', $enabled),
        ]);
    }

    public function disabled(bool $disabled = true): self
    {
        return $this->state(fn (array $attributes) => [
            'id' => $attributes['id']->set('enabled', ! $disabled),
        ]);
    }

    public function pending(bool $pending = true): self
    {
        return $this->state(fn (array $attributes) => [
            'postDate' => $pending
                ? fake()->dateTimeBetween('+1 day', '+1 year')
                : fake()->dateTime(),
        ]);
    }

    public function expired(bool $expired = true): self
    {
        return $this->state(fn (array $attributes) => [
            'expiryDate' => $expired
                ? fake()->dateTime()
                : fake()->dateTimeBetween('+1 day', '+1 year'),
        ]);
    }
}
