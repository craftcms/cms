<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories\Concerns;

trait CreatesElement
{
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
}
