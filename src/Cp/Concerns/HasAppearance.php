<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Concerns;

use Closure;
use CraftCms\Cms\Cp\Enums\Appearance;

/**
 * Fluent `appearance` support for components whose custom element accepts the
 * shared appearances. Strings (e.g. from Twig `ui()` config) are validated
 * against the {@see Appearance} enum.
 */
trait HasAppearance
{
    protected Appearance|string|Closure|null $appearance = null;

    public function appearance(Appearance|string|Closure|null $appearance): static
    {
        $this->appearance = $appearance;

        return $this;
    }

    public function getAppearance(): ?string
    {
        $appearance = $this->evaluate($this->appearance);

        if ($appearance === null) {
            return null;
        }

        return $appearance instanceof Appearance ? $appearance->value : Appearance::from($appearance)->value;
    }
}
