<?php

namespace craft\migrations;

use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\fieldlayoutelements\CustomField;
use craft\fields\MissingField;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\models\FieldLayout;
use yii\base\InvalidArgumentException;
use yii\db\ColumnSchema;
use yii\db\Expression;
use yii\db\Query as YiiQuery;
use yii\db\Schema;
use yii\db\TableSchema;

/**
 * Base content refactor migration class
 *
 * @since 5.0.0
 */
class BaseContentRefactorMigration extends Migration
{
    /**
     * @var bool Whether the old content table data should be preserved after it has been migrated to the
     * `elements_sites` table.
     */
    protected bool $preserveOldData = false;

    /**
     * Updates the `elements_sites.content` value for elements.
     *
     * @param int[]|YiiQuery $ids The element IDs to update, or a query that selects them.
     * If a query is passed but `select` is not set, it will default to `'id'`.
     * @param FieldLayout|null $fieldLayout The field layout that the elements use, if any
     * @param string $contentTable The table that the elements stored their field values in.
     * @param string $fieldColumnPrefix The column prefix that the content table used for these elements’ fields.
     */
    protected function updateElements(
        YiiQuery|array $ids,
        ?FieldLayout $fieldLayout,
        string $contentTable = '{{%content}}',
        string $fieldColumnPrefix = 'field_',
    ): void {
        if (is_array($ids) && empty($ids)) {
            return;
        }

        $contentTableSchema = $this->db->getSchema()->getTableSchema($contentTable);

        // make sure the table hasn't already been deleted
        if (!$contentTableSchema) {
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
                    $contentTableSchema,
                    $layoutElement,
                    $field,
                    $fieldColumns,
                    $flatFieldColumns,
                )) {
                    $fieldsByUid[$layoutElement->uid] = $field;
                }
            }
        }

        if ($ids instanceof YiiQuery && !$ids->select) {
            $ids->select('id');
        }

        $query = (new YiiQuery())
            ->select([
                'es.id',
                'es.elementId',
                'es.siteId',
                'e.draftId',
                'e.revisionId',
                'e.type',
                ...$flatFieldColumns,
            ])
            ->from(['es' => Table::ELEMENTS_SITES])
            ->innerJoin(['c' => $contentTable], '[[c.elementId]] = [[es.elementId]] and [[c.siteId]] = [[es.siteId]]')
            ->innerJoin(['e' => Table::ELEMENTS], '[[e.id]] = [[es.elementId]]')
            ->where(['in', 'es.elementId', $ids]);

        if ($contentTableSchema->getColumn('title')) {
            $query->addSelect('c.title');
        }

        $total = (string)$query->count();
        $totalLen = strlen($total);
        $i = 0;

        foreach (Db::each($query) as $element) {
            $i++;
            echo sprintf(
                '    > [%s/%s] Updating %s ...',
                str_pad((string)$i, $totalLen, '0', STR_PAD_LEFT),
                $total,
                $this->elementLabel($element),
            );
            $content = [];

            foreach ($fieldColumns as $layoutElementUid => $column) {
                $field = $fieldsByUid[$layoutElementUid];

                if ($field instanceof MissingField) {
                    // Figure it out from the actual DB column
                    $dbType = $contentTableSchema->getColumn($column)?->dbType;
                } else {
                    $dbType = $field::dbType();
                }

                if (is_array($column)) {
                    /** @var array $dbType */
                    $value = [];
                    foreach (array_keys($dbType) as $i => $key) {
                        if (!isset($column[$key])) {
                            continue;
                        }
                        $c = $column[$key];
                        $v = $this->decodeValue($element[$c], $dbType[$key], $contentTableSchema->getColumn($c));

                        if ($v !== null) {
                            $value[$key] = $v;
                        } elseif ($i === 0) {
                            // the primary column is null, so consider the whole value to be null
                            continue 2;
                        }
                    }
                } else {
                    $value = $this->decodeValue($element[$column], $dbType, $contentTableSchema->getColumn($column));
                    if ($value === null) {
                        continue;
                    }
                }

                $content[$layoutElementUid] = $value;
            }

            // don't call $this->update() so it doesn't mess with the CLI output
            Db::update(Table::ELEMENTS_SITES, [
                'title' => $element['title'] ?? null,
                'content' => $content ?: null,
            ], ['id' => $element['id']], updateTimestamp: false, db: $this->db);

            echo " done\n";
        }

        // make sure the elements’ fieldLayoutId values are accurate
        if ($fieldLayout) {
            $this->update(Table::ELEMENTS, [
                'fieldLayoutId' => $fieldLayout->id,
            ], ['in', 'id', $ids], updateTimestamp: false);
        }

        if (!empty($fieldsByUid)) {
            $caseSql = 'CASE ';
            $params = [];
            $i = 0;
            foreach ($fieldsByUid as $uid => $field) {
                $i++;
                $caseSql .= "WHEN [[fieldId]] = :fieldId$i THEN :uid$i ";
                $params += [
                    ":fieldId$i" => $field->id,
                    ":uid$i" => $uid,
                ];
            }
            $caseSql .= "ELSE '0' END";
            $this->update(Table::CHANGEDFIELDS, [
                'layoutElementUid' => new Expression($caseSql),
            ], ['in', 'elementId', $ids], $params, false);
        }

        if (!$this->preserveOldData) {
            // drop these content rows completely
            $this->delete($contentTable, ['in', 'elementId', $ids]);

            // if the content table is totally empty now, drop it
            $rowsExist = (new Query())
                ->select('id')
                ->from($contentTable)
                ->limit(1)
                ->exists($this->db);

            if (!$rowsExist) {
                $this->dropTable($contentTable);
            }
        }
    }

    private function elementLabel(array $element): string
    {
        $elementType = $element['type'];
        if ($elementType && class_exists($elementType) && is_subclass_of($elementType, ElementInterface::class)) {
            $label = $elementType::lowerDisplayName();
        } else {
            $label = 'element';
        }
        if ($element['draftId']) {
            $label .= ' draft';
        } elseif ($element['revisionId']) {
            $label .= ' revision';
        }
        $label .= " {$element['elementId']}";
        if (!empty($element['title'])) {
            $label .= sprintf(' "%s"', $element['title']);
        }
        return $label;
    }

    private function findColumnsForField(
        string $fieldColumnPrefix,
        TableSchema $contentTableSchema,
        CustomField $layoutElement,
        FieldInterface $field,
        array &$fieldColumns,
        array &$flatFieldColumns,
    ): bool {
        if ($field instanceof MissingField) {
            $dbType = Schema::TYPE_TEXT;
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
            ($field->columnSuffix ? "_$field->columnSuffix" : ''),
        );

        if (!$contentTableSchema->getColumn($primaryColumn)) {
            return false;
        }

        // was this a multi-column field?
        if (is_array($dbType) && count($dbType) > 1) {
            $dbTypeKeys = array_keys($dbType);
            $extraColumns = array_map(
                fn(string $key) => sprintf('%s%s_%s_%s', $fieldColumnPrefix, $field->handle, $key, $field->columnSuffix),
                array_slice($dbTypeKeys, 1),
            );

            if (ArrayHelper::contains($extraColumns, fn(string $column) => $contentTableSchema->getColumn($column))) {
                $columns = [$primaryColumn, ...$extraColumns];
                foreach ($columns as $i => $column) {
                    if ($contentTableSchema->getColumn($column)) {
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

    private function decodeValue(mixed $value, string|array|null $dbType, ColumnSchema $column): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        // if dbType is null or text, the content column type may be more reliable
        if (!$dbType || $dbType === Schema::TYPE_TEXT) {
            $dbType = $column->type;
        }

        if (is_array($dbType)) {
            // dbType() returned an array but there was only one field column,
            // so see if the field was storing JSON
            if (Json::isJsonObject($value)) {
                try {
                    return Json::decode($value);
                } catch (InvalidArgumentException) {
                }
            }

            // if we're still here, go with the first type listed instead
            $dbType = reset($dbType);

            // special case for a datetime that was stored in a single column
            // we need to do it here, cause if it was stored in 2 cols, the $dbType wouldn't have been an array
            if ($dbType === Schema::TYPE_DATETIME && is_string($value)) {
                return ['date' => $value];
            }
        }

        switch ($dbType) {
            case Schema::TYPE_TINYINT:
            case Schema::TYPE_SMALLINT:
            case Schema::TYPE_INTEGER:
            case Schema::TYPE_BIGINT:
                return (int)$value;

            case Schema::TYPE_FLOAT:
            case Schema::TYPE_DOUBLE:
            case Schema::TYPE_DECIMAL:
            case Schema::TYPE_MONEY:
                return (float)$value;

            case Schema::TYPE_BOOLEAN:
                return (bool)$value;

            case Schema::TYPE_JSON:
                return Json::decodeIfJson($value);
        }

        return $value;
    }
}
