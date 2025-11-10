<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns;

use craft\base\FieldInterface;
use craft\behaviors\CustomFieldBehavior;
use craft\db\mysql\Schema;
use craft\helpers\Db as DbHelper;
use craft\models\FieldLayout;
use CraftCms\Cms\Database\Expressions\JsonExtract;
use CraftCms\Cms\Database\Queries\ElementQuery;
use CraftCms\Cms\Database\Queries\Exceptions\QueryAbortedException;
use CraftCms\Cms\Database\QueryParam;
use Illuminate\Contracts\Database\Query\Expression;
use Tpetry\QueryExpressions\Function\Conditional\Coalesce;

/**
 * @mixin \CraftCms\Cms\Database\Queries\ElementQuery
 *
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

    protected function initQueriesCustomFields(): void
    {
        // Gather custom fields and generated field handles
        $this->customFields = [];
        $this->generatedFields = [];

        if ($this->withCustomFields) {
            foreach ($this->fieldLayouts() as $fieldLayout) {
                foreach ($fieldLayout->getCustomFields() as $field) {
                    $this->customFields[] = $field;
                }
                foreach ($fieldLayout->getGeneratedFields() as $field) {
                    $this->generatedFields[] = $field;
                }
            }
        }

        // Map custom field handles to their content values
        $this->addCustomFieldsToColumnMap();

        $this->beforeQuery(function (ElementQuery $query) {
            $this->applyCustomFieldParams($query);
        });
    }

    /**
     * {@inheritdoc}
     *
     * @uses $withCustomFields
     */
    public function withCustomFields(bool $value = true): static
    {
        $this->withCustomFields = $value;

        return $this;
    }

    /**
     * Returns the field layouts whose custom fields should be returned by [[customFields()]].
     *
     * @return FieldLayout[]
     */
    protected function fieldLayouts(): array
    {
        return \Craft::$app->getFields()->getLayoutsByType($this->elementType);
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldLayouts(): array
    {
        return $this->fieldLayouts();
    }

    /**
     * Include custom fields in the column map
     */
    private function addCustomFieldsToColumnMap(): void
    {
        foreach ($this->customFields as $field) {
            $dbTypes = $field::dbType();

            if ($dbTypes !== null) {
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
                    if ($this->getConnection()->getDriverName() === 'mysql' && DbHelper::parseColumnType($dbType) === Schema::TYPE_TEXT) {
                        $this->columnsToCast[$alias] = 'CHAR(255)';
                    }
                }
            }
        }

        if (! empty($this->generatedFields)) {
            foreach ($this->generatedFields as $field) {
                if (($field['handle'] ?? '') !== '') {
                    $this->addToColumnMap($field['handle'], new JsonExtract('elements_sites.content', '$.'.$field['uid']));
                }
            }
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
    private function applyCustomFieldParams(ElementQuery $query): void
    {
        if (empty($this->customFields) && empty($this->generatedFields)) {
            return;
        }

        $fieldAttributes = $this->getBehavior('customFields');
        /** @var FieldInterface[][][] $fieldsByHandle */
        $fieldsByHandle = [];

        if (! empty($this->customFields)) {
            // Group the fields by handle and field UUID
            foreach ($this->customFields as $field) {
                $fieldsByHandle[$field->handle][$field->uid][] = $field;
            }

            foreach (array_keys(CustomFieldBehavior::$fieldHandles) as $handle) {
                // $fieldAttributes->$handle will return true even if it's set to null, so can't use isset() here
                if ($handle === 'owner') {
                    continue;
                }
                if (($fieldAttributes->$handle ?? null) === null) {
                    continue;
                }
                // Make sure the custom field exists in one of the field layouts
                if (! isset($fieldsByHandle[$handle])) {
                    // If it looks like null/:empty: is a valid option, let it slide
                    $value = is_array($fieldAttributes->$handle) && isset($fieldAttributes->$handle['value'])
                        ? $fieldAttributes->$handle['value']
                        : $fieldAttributes->$handle;

                    if (is_array($value) && in_array(null, $value, true)) {
                        $values = [...$value];
                        $operator = QueryParam::extractOperator($values) ?? QueryParam::OR;
                        if ($operator === QueryParam::OR) {
                            continue;
                        }
                    }

                    throw new QueryAbortedException("No custom field with the handle \"$handle\" exists in the field layouts involved with this element query.");
                }

                $conditions = [];
                $params = [];

                foreach ($fieldsByHandle[$handle] as $instances) {
                    $firstInstance = $instances[0];
                    $condition = $firstInstance::queryCondition($instances, $fieldAttributes->$handle, $params);

                    // aborting?
                    if ($condition === false) {
                        throw new QueryAbortedException;
                    }

                    if ($condition !== null) {
                        $conditions[] = $condition;
                    }
                }

                if (! empty($conditions)) {
                    if (count($conditions) === 1) {
                        $this->subQuery->andWhere(reset($conditions), $params);
                    } else {
                        $this->subQuery->andWhere(['or', ...$conditions], $params);
                    }
                }
            }
        }

        if (! empty($this->generatedFields)) {
            $generatedFieldColumns = [];

            foreach ($this->generatedFields as $field) {
                $handle = $field['handle'] ?? '';
                if ($handle !== '' && isset($fieldAttributes->$handle) && ! isset($fieldsByHandle[$handle])) {
                    $generatedFieldColumns[$handle][] = new JsonExtract('elements_sites.content', '$.'.$field['uid']);
                }
            }

            foreach ($generatedFieldColumns as $handle => $columns) {
                $column = count($columns) === 1
                    ? $columns[0]
                    : new Coalesce($columns)->getValue($query->subQuery->getGrammar());

                $query->subQuery->where(DbHelper::parseParam($column, $fieldAttributes->$handle));
            }
        }
    }
}
