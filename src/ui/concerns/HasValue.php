<?php

namespace craft\ui\concerns;

use Closure;

trait HasValue
{
    /**
     * @var string|Closure|null The input value
     */
    protected string|Closure|null $value = null;

    public function value(?string $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function getValue(): ?string
    {
        return $this->evaluate($this->value);
    }
}