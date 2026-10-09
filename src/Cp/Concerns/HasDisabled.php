<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Concerns;

/** Fluent `disabled` support. */
trait HasDisabled
{
    protected bool $disabled = false;

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }
}
