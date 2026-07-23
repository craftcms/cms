<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Concerns;

use Closure;

/**
 * Fluent `id` support. The component decides where the id lands — e.g. the
 * host element for a field, or the slotted control for a form input.
 */
trait HasId
{
    protected string|Closure|null $id = null;

    public function id(string|Closure|null $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?string
    {
        $id = $this->evaluate($this->id);

        return $id !== null ? (string) $id : null;
    }
}
