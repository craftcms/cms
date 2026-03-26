<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Database\Factories\Concerns\HasFieldFactory;
use CraftCms\Cms\Element\Element as BaseElement;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Arr;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

class EntryFactory extends Factory
{
    use HasFieldFactory;

    #[Override]
    protected $model = Entry::class;

    #[Override]
    public function definition(): array
    {
        return [
            'id' => Element::factory()->set('type', EntryElement::class),
            'sectionId' => Section::factory(),
            'typeId' => EntryType::factory(),
            'status' => EntryElement::STATUS_LIVE,
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

    public function title(string $title): static
    {
        return $this->state(fn (array $attributes) => [
            'id' => $attributes['id']->title($title),
        ]);
    }

    public function slug(string $slug): static
    {
        return $this->state(fn (array $attributes) => [
            'id' => $attributes['id']->slug($slug),
        ]);
    }

    public function forSection(Section $section): static
    {
        return $this->state(fn () => ['sectionId' => $section->id]);
    }

    public function forEntryType(EntryType $type): static
    {
        return $this->state(fn () => ['typeId' => $type->id]);
    }

    public function createElement(array $attributes = []): EntryElement
    {
        $factory = $this;

        if (Arr::has($attributes, 'title')) {
            $factory = $factory->title(Arr::pull($attributes, 'title'));
        }

        if (Arr::has($attributes, 'slug')) {
            $factory = $factory->slug(Arr::pull($attributes, 'slug'));
        }

        $model = $factory->create($attributes);

        return EntryElement::find()->id($model->id)->one();
    }

    #[Override]
    protected function getElementClass(): string
    {
        return EntryElement::class;
    }

    #[Override]
    protected function attachFieldLayoutToModel(mixed $model, FieldLayout $fieldLayout): void
    {
        $model->element->update([
            'fieldLayoutId' => $fieldLayout->id,
        ]);

        $model->entryType->update([
            'fieldLayoutId' => $fieldLayout->id,
        ]);
    }

    #[Override]
    protected function queryElement(int $id): BaseElement
    {
        return EntryElement::find()->id($id)->one();
    }
}
