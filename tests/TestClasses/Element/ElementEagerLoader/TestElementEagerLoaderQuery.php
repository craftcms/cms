<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\Element\ElementEagerLoader;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use Illuminate\Support\Collection;

class TestElementEagerLoaderQuery extends ElementQuery
{
    public static array $afterHydrateCalls = [];

    public static array $createdElements = [];

    public static array $querySiteIds = [];

    public static array $rowsByType = [];

    public static array $whereInCalls = [];

    public mixed $order = null;

    public mixed $orderBy = null;

    private array $whereInFilters = [];

    public static function resetTestState(): void
    {
        self::$afterHydrateCalls = [];
        self::$createdElements = [];
        self::$querySiteIds = [];
        self::$rowsByType = [];
        self::$whereInCalls = [];
    }

    public static function setRows(string $elementType, array $rows): void
    {
        self::$rowsByType[$elementType] = $rows;
    }

    public function whereIn($column, $values, $boolean = 'and', $not = false): static
    {
        $values = array_values($values);
        $this->whereInFilters[(string) $column] = $values;
        self::$whereInCalls[$this->elementType][] = $values;

        return $this;
    }

    #[\Override]
    public function all($columns = ['*']): array
    {
        self::$querySiteIds[$this->elementType][] = $this->siteId;

        $rows = $this->filteredRows();

        if ($this->asArray) {
            return $rows;
        }

        return array_map($this->createElement(...), $rows);
    }

    #[\Override]
    public function ids(): array
    {
        self::$querySiteIds[$this->elementType][] = $this->siteId;

        return array_column($this->filteredRows(), 'id');
    }

    #[\Override]
    public function createElement(array $row): ElementInterface
    {
        self::$createdElements[$this->elementType][] = $row['id'];

        return new $this->elementType($row);
    }

    #[\Override]
    public function afterHydrate(Collection $items): Collection
    {
        self::$afterHydrateCalls[$this->elementType][] = $items
            ->map(fn (ElementInterface $element) => $element->id)
            ->all();

        return $items;
    }

    private function filteredRows(): array
    {
        $rows = self::$rowsByType[$this->elementType] ?? [];

        $allowedSiteIds = $this->normalizeFilterValues($this->siteId);
        if ($allowedSiteIds !== null) {
            $rows = array_values(array_filter(
                $rows,
                fn (array $row) => in_array($row['siteId'], $allowedSiteIds, true),
            ));
        }

        $allowedIds = $this->normalizeFilterValues($this->id);
        if ($allowedIds !== null) {
            $rows = array_values(array_filter(
                $rows,
                fn (array $row) => in_array($row['id'], $allowedIds, true),
            ));
        }

        if (isset($this->whereInFilters['elements.id'])) {
            $whereInIds = $this->whereInFilters['elements.id'];
            $rows = array_values(array_filter(
                $rows,
                fn (array $row) => in_array($row['id'], $whereInIds, true),
            ));
        }

        return $rows;
    }

    private function normalizeFilterValues(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if ($value === '*') {
            return null;
        }

        return array_values(is_array($value) ? $value : [$value]);
    }
}
