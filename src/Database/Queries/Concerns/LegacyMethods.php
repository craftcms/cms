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

    public function afterPopulate(array $elements): array
    {
        throw new BadMethodCallException;
    }

    public function andWhere($condition): never
    {
        throw new BadMethodCallException;
    }

    public function filterWhere(array $condition): never
    {
        throw new BadMethodCallException;
    }

    public function andFilterWhere(array $condition): never
    {
        throw new BadMethodCallException;
    }

    public function orFilterWhere(array $condition): never
    {
        throw new BadMethodCallException;
    }

    public function addOrderBy($columns)
    {
        $this->forwardCallTo($this->query, 'orderBy', func_get_args());

        return $this;
    }

    public function emulateExecution($value = true): never
    {
        throw new RuntimeException('This method is not supported.');
    }

    public function where($column, $operator = null, $value = null)
    {
        $this->forwardCallTo($this->subQuery, 'where', func_get_args());

        return $this;
    }

    public function orWhere($column, $operator = null, $value = null)
    {
        $this->forwardCallTo($this->subQuery, 'orWhere', func_get_args());

        return $this;
    }

    public function exists($db = null)
    {
        return $this->query->exists();
    }
}
