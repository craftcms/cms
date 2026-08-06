<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Concerns;

/**
 * Fluent `id` support. The component decides where the id lands — e.g. the
 * host element for a field, or the slotted control for a form input.
 */
trait HasId
{
    protected ?string $id = null;

    public function id(?string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }
}
