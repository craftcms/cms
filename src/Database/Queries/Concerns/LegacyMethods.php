<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns;

use BadMethodCallException;
use RuntimeException;

/** @TODO: Remove this after ElementQueryInterface is removed */
trait LegacyMethods
{
    public function fields(): never
    {
        throw new BadMethodCallException;
    }

    public function extraFields(): never
    {
        throw new BadMethodCallException;
    }

    public function toArray(array $fields = [], array $expand = [], $recursive = true): never
    {
        throw new BadMethodCallException;
    }

    public function ref(mixed $value): static
    {
        /** @phpstan-ignore property.notFound */
        $this->ref = $value;

        return $this;
    }

    public function emulateExecution($value = true): never
    {
        throw new RuntimeException('This method is not supported.');
    }
}
