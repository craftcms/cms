<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Concerns;

use CraftCms\Cms\Cp\Enums\Size;

/**
 * Fluent `size` support for components whose custom element accepts the
 * shared sizes. Strings (e.g. from Twig `ui()` config) are validated against
 * the {@see Size} enum.
 */
trait HasSize
{
    protected Size|string|null $size = null;

    public function size(Size|string|null $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getSize(): ?string
    {
        if ($this->size === null) {
            return null;
        }

        return $this->size instanceof Size ? $this->size->value : Size::from($this->size)->value;
    }
}
