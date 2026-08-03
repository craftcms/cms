<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Concerns;

use CraftCms\Cms\Cp\Enums\Appearance;

/**
 * Fluent `appearance` support for components whose custom element accepts the
 * shared appearances. Strings (e.g. from Twig `ui()` config) are validated
 * against the {@see Appearance} enum.
 */
trait HasAppearance
{
    protected Appearance|string|null $appearance = null;

    public function appearance(Appearance|string|null $appearance): static
    {
        $this->appearance = $appearance;

        return $this;
    }

    public function getAppearance(): ?string
    {
        if ($this->appearance === null) {
            return null;
        }

        return $this->appearance instanceof Appearance ? $this->appearance->value : Appearance::from($this->appearance)->value;
    }
}
