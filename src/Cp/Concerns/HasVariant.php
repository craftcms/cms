<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Concerns;

use CraftCms\Cms\Cp\Enums\Variant;

/**
 * Fluent `variant` support for components whose custom element accepts the
 * shared semantic variants. Strings (e.g. from Twig `ui()` config) are
 * validated against the {@see Variant} enum.
 */
trait HasVariant
{
    protected Variant|string|null $variant = null;

    public function variant(Variant|string|null $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function getVariant(): ?string
    {
        if ($this->variant === null) {
            return null;
        }

        return $this->variant instanceof Variant ? $this->variant->value : Variant::from($this->variant)->value;
    }
}
