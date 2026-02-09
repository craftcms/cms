<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Migrations;

use craft\base\ElementInterface;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\MissingField;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tpetry\QueryExpressions\Language\Alias;
use Tpetry\QueryExpressions\Language\CaseGroup;
use Tpetry\QueryExpressions\Language\CaseRule;
use Tpetry\QueryExpressions\Operator\Comparison\Equal;
use Tpetry\QueryExpressions\Value\Value;
use yii\db\Schema as YiiSchema;

use function Laravel\Prompts\progress;

/**
 * Base content refactor migration class
 *
 * @since 5.0.0
 */
class BaseContentRefactorMigration extends Migration
{
    /**
     * @var bool Whether the old content table data should be preserved after it has been migrated to the
     *           `elements_sites` table.
     */
    protected bool $preserveOldData = false;

    /**
     * Updates the `elements_sites.content` value for elements.
     *
     * @param  int[]|Builder  $ids  The element IDs to update, or a query that selects them.
     *                              If a query is passed but `select` is not set, it will default to `'id'`.
     * @param  \CraftCms\Cms\FieldLayout\FieldLayout|null  $fieldLayout  The field layout that the elements use, if any
     * @param  string  $contentTable  The table that the elements stored their field values in.
     * @param  string  $fieldColumnPrefix  The column prefix that the content table used for these elements’ fields.
     */
    protected function updateElements(
        Builder|array $ids,
        ?FieldLayout $fieldLayout,
        string $contentTable = 'content',
        string $fieldColumnPrefix = 'field_',
    ): void {
        if (is_array($ids) && empty($ids)) {
            return;
        }

        // make sure the table hasn't already been deleted
        if (! Schema::hasTable($contentTable)) {
            return;
        }

        $fieldsByUid = [];
        $fieldColumns = [];
        $flatFieldColumns = [];

        if ($fieldLayout) {
            foreach ($fieldLayout->getCustomFieldElements() as $layoutElement) {
                $field = $layoutElement->getField();

                if ($this->findColumnsForField(
                    $fieldColumnPrefix,
                    $contentTable,
                    $layoutElement,
                    $field,
                    $fieldColumns,
                    $flatFieldColumns,
                )) {
                    $fieldsByUid[$layoutElement->uid] = $field;
                }
            }
        }

        if ($ids instanceof Builder) {
            $ids->select('id');
        }

        $query = DB::table(Table::ELEMENTS_SITES, 'es')
            ->select([
                'es.id',
                'es.elementId',
                'es.siteId',
                'e.draftId',
                'e.revisionId',
                'e.type',
                ...$flatFieldColumns,
            ])
            ->join(new Alias($contentTable, 'c'), function (JoinClause $join) {
                $join->on('c.elementId', '=', 'es.elementId')
                    ->where('c.siteId', '=', 'es.siteId');
            })
            ->join(new Alias(Table::ELEMENTS, 'e'), 'e.id', '=', 'es.elementId')
            ->whereIn('es.elementId', $ids);

        if (Schema::hasColumn($contentTable, 'title')) {
            $query->addSelect('c.title');
        }

        $progress = progress(label: 'Updating', steps: $query->count());

        $query
            ->lazyById(column: 'es.id')
            ->each(function (object $element) use ($contentTable, $fieldsByUid, $fieldColumns, $progress) {
                $progress
                    ->label($this->elementLabel($element))
                    ->render();

                $content = [];

                foreach ($fieldColumns as $layoutElementUid => $column) {
                    /** @var \CraftCms\Cms\Field\Field $field */
                    $field = $fieldsByUid[$layoutElementUid];

                    if ($field instanceof MissingField) {
                        // Figure it out from the actual DB column
                        $dbType = Schema::getColumnType($contentTable, $column);
                    } else {
                        $dbType = $field::dbType();
                    }

                    if (is_array($column)) {
                        /** @var array $dbType */
                        $value = [];
                        foreach (array_keys($dbType) as $i => $key) {
                            if (! isset($column[$key])) {
                                continue;
                            }
                            $c = $column[$key];
                            $v = $this->decodeValue($element[$c], $dbType[$key], $contentTable, $c);

                            if ($v !== null) {
                                $value[$key] = $v;
                            } elseif ($i === 0) {
                                // the primary column is null, so consider the whole value to be null
                                continue 2;
                            }
                        }
                    } else {
                        $value = $this->decodeValue($element[$column], $dbType, $contentTable, $column);
                        if ($value === null) {
                            continue;
                        }
                    }

                    $content[$layoutElementUid] = $value;
                }

                // don't call $this->update() so it doesn't mess with the CLI output
                DB::table(Table::ELEMENTS_SITES)
                    ->where('id', $element['id'])
                    ->update([
                        'title' => $element['title'] ?? null,
                        'content' => $content ?: null,
                    ]);

                $progress->advance();
            });

        $progress->finish();

        // make sure the elements’ fieldLayoutId values are accurate
        if ($fieldLayout) {
            DB::table(Table::ELEMENTS)
                ->whereIn('id', $ids)
                ->update([
                    'fieldLayoutId' => $fieldLayout->id,
                ]);
        }

        if (! empty($fieldsByUid)) {
            $cases = [];
            foreach ($fieldsByUid as $uid => $field) {
                $cases[] = new CaseRule($uid, new Equal('fieldId', new Value($field->id)));
            }

            DB::table(Table::CHANGEDFIELDS)
                ->whereIn('elementId', $ids)
                ->update([
                    'layoutElementUid' => new CaseGroup($cases, new Value(0)),
                ]);
        }

        if (! $this->preserveOldData) {
            // drop these content rows completely
            DB::table($contentTable)
                ->whereIn('elementId', $ids)
                ->delete();

            // if the content table is totally empty now, drop it
            $rowsExist = DB::table($contentTable)
                ->select('id')
                ->limit(1)
                ->exists();

            if (! $rowsExist) {
                Schema::drop($contentTable);
            }
        }
    }

    private function elementLabel(object $element): string
    {
        $elementType = $element->type;

        if ($elementType && class_exists($elementType) && is_subclass_of($elementType, ElementInterface::class)) {
            $label = $elementType::lowerDisplayName();
        } else {
            $label = 'element';
        }

        if ($element->draftId) {
            $label .= ' draft';
        } elseif ($element->revisionId) {
            $label .= ' revision';
        }

        $label .= " {$element->elementId}";

        if (! empty($element->title)) {
            $label .= sprintf(' "%s"', $element->title);
        }

        return $label;
    }

    private function findColumnsForField(
        string $fieldColumnPrefix,
        string $contentTable,
        CustomField $layoutElement,
        FieldInterface $field,
        array &$fieldColumns,
        array &$flatFieldColumns,
    ): bool {
        if ($field instanceof MissingField) {
            $dbType = YiiSchema::TYPE_TEXT;
        } else {
            $dbType = $field::dbType();
            if ($dbType === null) {
                return false;
            }
        }

        $primaryColumn = sprintf(
            '%s%s%s',
            $fieldColumnPrefix,
            $field->handle,
            (property_exists($field, 'columnSuffix') && $field->columnSuffix ? "_$field->columnSuffix" : ''),
        );

        if (! Schema::hasColumn($contentTable, $primaryColumn)) {
            return false;
        }

        // was this a multi-column field?
        if (is_array($dbType) && count($dbType) > 1) {
            $dbTypeKeys = array_keys($dbType);
            $extraColumns = array_map(
                fn (string $key) => sprintf('%s%s_%s_%s', $fieldColumnPrefix, $field->handle, $key,
                    property_exists($field, 'columnSuffix') ? $field->columnSuffix : ''),
                array_slice($dbTypeKeys, 1),
            );

            if (Collection::make($extraColumns)->contains(fn (string $column) => Schema::hasColumn($contentTable, $column))) {
                $columns = [$primaryColumn, ...$extraColumns];
                foreach ($columns as $i => $column) {
                    if (Schema::hasColumn($contentTable, $column)) {
                        $fieldColumns[$layoutElement->uid][$dbTypeKeys[$i]] = $column;
                        $flatFieldColumns[] = "c.$column";
                    }
                }

                return true;
            }
        }

        $fieldColumns[$layoutElement->uid] = $primaryColumn;
        $flatFieldColumns[] = "c.$primaryColumn";

        return true;
    }

    private function decodeValue(mixed $value, string|array|null $dbType, string $contentTable, string $column): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        // if dbType is null or text, the content column type may be more reliable
        if (! $dbType || $dbType === YiiSchema::TYPE_TEXT) {
            $dbType = Schema::getColumnType($contentTable, $column);
        }

        if (is_array($dbType)) {
            // dbType() returned an array but there was only one field column,
            // so see if the field was storing JSON
            if (Str::isJson($value)) {
                try {
                    return Json::decode($value);
                } catch (InvalidArgumentException) {
                }
            }

            // if we're still here, go with the first type listed instead
            $dbType = reset($dbType);

            // special case for a datetime that was stored in a single column
            // we need to do it here, cause if it was stored in 2 cols, the $dbType wouldn't have been an array
            if ($dbType === YiiSchema::TYPE_DATETIME && is_string($value)) {
                return ['date' => $value];
            }
        }

        return match ($dbType) {
            YiiSchema::TYPE_TINYINT, YiiSchema::TYPE_SMALLINT, YiiSchema::TYPE_INTEGER, YiiSchema::TYPE_BIGINT => (int) $value,
            YiiSchema::TYPE_FLOAT, YiiSchema::TYPE_DOUBLE, YiiSchema::TYPE_DECIMAL, YiiSchema::TYPE_MONEY => (float) $value,
            YiiSchema::TYPE_BOOLEAN => (bool) $value,
            YiiSchema::TYPE_JSON => Json::decodeIfJson($value),
            default => $value,
        };
    }
}
