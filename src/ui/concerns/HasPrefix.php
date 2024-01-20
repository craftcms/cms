<?php

namespace craft\ui\concerns;

use Closure;

trait HasPrefix
{
    protected string|Closure|null $prefix = null;

    public function prefix(string|Closure|null $prefix): static
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function getPrefix(): ?string
    {
        return $this->evaluate($this->prefix);
    }
}
