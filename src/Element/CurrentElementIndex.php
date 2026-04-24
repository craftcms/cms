<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use Illuminate\Container\Attributes\Scoped;

#[Scoped]
class CurrentElementIndex
{
    private bool $active = false;

    private ?ElementQueryInterface $query = null;

    public function activate(?ElementQueryInterface $query = null): void
    {
        $this->active = true;
        $this->query = $query;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function query(): ?ElementQueryInterface
    {
        return $this->query;
    }

    public function reset(): void
    {
        $this->active = false;
        $this->query = null;
    }
}
