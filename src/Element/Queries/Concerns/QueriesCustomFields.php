<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Concerns;

use CraftCms\Cms\Database\Expressions\JsonExtract;
use CraftCms\Cms\Database\QueryParam;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Element\Queries\Exceptions\QueryAbortedException;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Query;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Tpetry\QueryExpressions\Function\Conditional\Coalesce;

/**
 * @internal
 */
trait QueriesCustomFields
{
    /**
     * @var FieldInterface[]|null The fields that may be involved in this query.
     */
    public ?array $customFields = null;

    /**
     * @var array|null The generated field handles that may be involved in this query.
     */
    public ?array $generatedFields = null;

    /**
     * @var bool Whether custom fields should be factored into the query.
     *
     * @used-by withCustomFields()
     */
    public bool $withCustomFields = true;

    /**
     * @var array<string,string|string[]> Column alias => cast type
     *
     * @see prepare()
     * @see _applyOrderByParams()
     */
    private array $columnsToCast = [];

    private array $customFieldValues = [];

    protected function initQueriesCustomFields(): void
    {
        foreach ([
            ...array_keys(Fields::allFieldHandles()),
            ...array_keys(Fields::allGeneratedFieldHandles()),
        ] as $handle) {
            $this->customFieldValues[$handle] = null;
        }

        $this->beforeQuery(function (ElementQuery $elementQuery) {
            // Gather custom fields and generated field handles
            $elementQuery->customFields = [];
            $elementQuery->generatedFields = [];

            if ($elementQuery->withCustomFields) {
                foreach ($elementQuery->fieldLayouts() as $fieldLayout) {
                    foreach ($fieldLayout->getCustomFields() as $field) {
                        $elementQuery->customFields[] = $field;
                    }
                    foreach ($fieldLayout->getGeneratedFields() as $field) {
                        $elementQuery->generatedFields[] = $field;
                    }
                }
            }

            // Map custom field handles to their content values
            $this->addCustomFieldsToColumnMap();
            $this->addGeneratedFieldsToColumnMap();

            $this->applyCustomFieldParams($elementQuery);
            $this->applyGeneratedFieldParams($elementQuery);
        });
    }

    /**
     * Sets whether custom fields should be factored into the query.
     */
    public function withCustomFields(bool $value = true): static
    {
        $this->withCustomFields = $value;

        return $this;
    }

    /**
     * Returns the field layouts whose custom fields should be returned by [[customFields()]].
     *
     * @return Collection<FieldLayout>
     */
    protected function fieldLayouts(): Collection
    {
        return Fields::getLayoutsByType($this->elementType);
    }

    /**
     * Returns the field layouts that could be associated with the resulting elements.
     *
     * @return FieldLayout[]
     */
    public function getFieldLayouts(): array
    {
        return $this->fieldLayouts()->all();
    }

    /**
     * Include custom fields in the column map
     */
    private function addCustomFieldsToColumnMap(): void
    {
        foreach ($this->customFields ?? [] as $field) {
            $dbTypes = $field::dbType();

            if (is_null($dbTypes)) {
                continue;
            }

            if (is_string($dbTypes)) {
                $dbTypes = ['*' => $dbTypes];
            } else {
                $dbTypes = [
                    '*' => reset($dbTypes),
                    ...$dbTypes,
                ];
            }

            foreach ($dbTypes as $key => $dbType) {
                $alias = $field->handle.($key !== '*' ? ".$key" : '');
                $resolver = fn () => $field->getValueSql($key !== '*' ? $key : null);

                $this->addToColumnMap($alias, $resolver);

                // for mysql, we have to make sure text column type is cast to char, otherwise it won't be sorted correctly
                // see https://github.com/craftcms/cms/issues/15609
                /** @var Connection $connection */
                $connection = $this->query->getConnection();
                if ($connection->isMysql() && Query::parseColumnType($dbType) === Query::TYPE_TEXT) {
                    $this->columnsToCast[$alias] = 'CHAR(255)';
                }
            }
        }
    }

    /**
     * Include custom fields in the column map
     */
    private function addGeneratedFieldsToColumnMap(): void
    {
        foreach ($this->generatedFields ?? [] as $field) {
            if (empty($field['handle'] ?? '')) {
                continue;
            }

            $this->addToColumnMap(
                $field['handle'],
                new JsonExtract(Table::ELEMENTS_SITES.'.content', $field['uid']),
            );
        }
    }

    private function addToColumnMap(string $alias, string|callable|Expression $column): void
    {
        if (! isset($this->columnMap[$alias])) {
            $this->columnMap[$alias] = [];
        }

        if (! is_array($this->columnMap[$alias])) {
            $this->columnMap[$alias] = [$this->columnMap[$alias]];
        }

        $this->columnMap[$alias][] = $column;
    }

    /**
     * Allow the custom fields to modify the query.
     *
     * @throws QueryAbortedException
     */
    private function applyCustomFieldParams(ElementQuery $elementQuery): void
    {
        if (empty($elementQuery->customFields)) {
            return;
        }

        $fieldsByHandle = $this->fieldsByHandle($elementQuery);

        foreach (array_keys(Fields::allFieldHandles()) as $handle) {
            // $fieldAttributes->$handle will return true even if it's set to null, so can't use isset() here
            if ($handle === 'owner') {
                continue;
            }
            if (($elementQuery->customFieldValues[$handle] ?? null) === null) {
                continue;
            }
            // Make sure the custom field exists in one of the field layouts
            if (! isset($fieldsByHandle[$handle])) {
                // If it looks like null/:empty: is a valid option, let it slide
                $value = is_array($elementQuery->customFieldValues[$handle]) && isset($elementQuery->customFieldValues[$handle]['value'])
                    ? $elementQuery->customFieldValues[$handle]['value']
                    : $elementQuery->customFieldValues[$handle];

                if (is_array($value) && in_array(null, $value, true)) {
                    $values = [...$value];
                    $operator = QueryParam::extractOperator($values) ?? QueryParam::OR;
                    if ($operator === QueryParam::OR) {
                        continue;
                    }
                }

                throw new QueryAbortedException("No custom field with the handle \"$handle\" exists in the field layouts involved with this element query.");
            }

            $glue = $elementQuery->customFieldValues[$handle] === ':empty:'
                ? QueryParam::AND
                : QueryParam::OR;

            $this->subQuery->where(function (Builder $query) use ($fieldsByHandle, $glue, $handle, $elementQuery) {
                foreach ($fieldsByHandle[$handle] as $instances) {
                    $query->where(function (Builder $query) use ($handle, $elementQuery, $instances) {
                        $instances[0]::modifyQuery($query, $instances, $elementQuery->customFieldValues[$handle]);
                    }, boolean: $glue);
                }
            });
        }
    }

    private function applyGeneratedFieldParams(ElementQuery $elementQuery): void
    {
        if (empty($elementQuery->generatedFields)) {
            return;
        }

        $fieldsByHandle = $this->fieldsByHandle($elementQuery);

        $generatedFieldColumns = [];

        foreach ($elementQuery->generatedFields as $field) {
            $handle = $field['handle'] ?? '';
            if ($handle !== '' && isset($elementQuery->customFieldValues[$handle]) && ! isset($fieldsByHandle[$handle])) {
                $generatedFieldColumns[$handle][] = new JsonExtract('elements_sites.content', $field['uid']);
            }
        }

        foreach ($generatedFieldColumns as $handle => $columns) {
            $column = count($columns) === 1
                ? $columns[0]
                : new Coalesce($columns);

            $elementQuery->subQuery->whereParam($column, $elementQuery->customFieldValues[$handle]);
        }
    }

    /**
     * @return FieldInterface[][][]
     */
    private function fieldsByHandle(ElementQuery $elementQuery): array
    {
        /** @var FieldInterface[][][] $fieldsByHandle */
        $fieldsByHandle = [];

        // Group the fields by handle and field UUID
        foreach ($elementQuery->customFields as $field) {
            $fieldsByHandle[$field->handle][$field->uid][] = $field;
        }

        return $fieldsByHandle;
    }
}
