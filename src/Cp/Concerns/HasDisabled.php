<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Concerns;

use Closure;

/** Fluent `disabled` support. */
trait HasDisabled
{
    protected bool|Closure $disabled = false;

    public function disabled(bool|Closure $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    public function isDisabled(): bool
    {
        return (bool) $this->evaluate($this->disabled);
    }
}
