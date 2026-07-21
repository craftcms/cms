<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Concerns;

use Closure;
use CraftCms\Cms\Cp\Enums\Variant;

/**
 * Fluent `variant` support for components whose custom element accepts the
 * shared semantic variants. Strings (e.g. from Twig `ui()` config) are
 * validated against the {@see Variant} enum.
 */
trait HasVariant
{
    protected Variant|string|Closure|null $variant = null;

    public function variant(Variant|string|Closure|null $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function getVariant(): ?string
    {
        $variant = $this->evaluate($this->variant);

        if ($variant === null) {
            return null;
        }

        return $variant instanceof Variant ? $variant->value : Variant::from($variant)->value;
    }
}
