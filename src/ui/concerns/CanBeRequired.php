<?php

namespace craft\ui\concerns;

use Closure;

trait CanBeRequired
{
    protected bool|Closure|null $required = null;

    public function required(bool|Closure|null $condition = true): static
    {
        $this->required = $condition;

        return $this;
    }

    public function isRequired(): bool
    {
        return $this->evaluate($this->required) ?? $this->required;
    }
}