<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Concerns;

use Closure;
use CraftCms\Cms\Cp\Enums\Size;

/**
 * Fluent `size` support for components whose custom element accepts the
 * shared sizes. Strings (e.g. from Twig `ui()` config) are validated against
 * the {@see Size} enum.
 */
trait HasSize
{
    protected Size|string|Closure|null $size = null;

    public function size(Size|string|Closure|null $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getSize(): ?string
    {
        $size = $this->evaluate($this->size);

        if ($size === null) {
            return null;
        }

        return $size instanceof Size ? $size->value : Size::from($size)->value;
    }
}
