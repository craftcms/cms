<?php

namespace craft\migrations;

use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\fields\BaseOptionsField;
use craft\fields\Lightswitch;
use craft\fields\Table as TableField;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\models\FieldLayout;
use yii\db\ColumnSchema;
use yii\db\Query as YiiQuery;
use yii\db\Schema;

/**
 * Base content refactor migration class
 *
 * @since 5.0.0
 */
class BaseContentRefactorMigration extends Migration
{
    /**
     * Updates the `elements_sites.content` value for elements.
     *
     * @param int[]|YiiQuery $ids The elmenet IDs to update, or a query that selects them.
     * If a query is passed but `select` is not set, it will default to `'id'`.
     * @param FieldLayout|null $fieldLayout The field layout that the elements use, if any
     * @param string $contentTable The table that the elements stored their field values in.
     * @param string $fieldColumnPrefix The column prefix that the content table used for these elements’ fields.
     */
    protected function updateElements(
        YiiQuery|array $ids,
        ?FieldLayout $fieldLayout,
        string $contentTable = Table::CONTENT,
        string $fieldColumnPrefix = 'field_',
    ): void {
        $contentTableSchema = $this->db->getSchema()->getTableSchema($contentTable);
        $fieldsByUid = [];
        $fieldColumns = [];
        $flatFieldColumns = [];

        if ($fieldLayout) {
            foreach ($fieldLayout->getCustomFields() as $field) {
                $dbType = $field::dbType();

                if ($dbType === null) {
                    continue;
                }

                $primaryColumn = sprintf(
                    '%s%s%s',
                    $fieldColumnPrefix,
                    $field->handle,
                    ($field->columnSuffix ? "_$field->columnSuffix" : ''),
                );

                if ($contentTableSchema->getColumn($primaryColumn)) {
                    $fieldsByUid[$field->uid] = $field;

                    // is this a multi-column field?
                    if (is_array($dbType)) {
                        foreach (array_keys($dbType) as $i => $key) {
                            $column = $i === 0 ? $primaryColumn : sprintf(
                                '%s%s_%s_%s',
                                $fieldColumnPrefix,
                                $field->handle,
                                $key,
                                $field->columnSuffix,
                            );
                            $fieldColumns[$field->uid][$key] = $column;
                            $flatFieldColumns[] = "c.$column";
                        }
                    } else {
                        $fieldColumns[$field->uid] = $primaryColumn;
                        $flatFieldColumns[] = $primaryColumn;
                    }
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

            foreach ($fieldColumns as $fieldUid => $column) {
                $field = $fieldsByUid[$fieldUid];
                if (is_array($column)) {
                    $value = array_map(
                        fn($c) => $this->decodeValue($element[$c], $field, $contentTableSchema->getColumn($c)),
                        $column,
                    );
                    // if the primary column is null, consider the whole value to be null
                    if (reset($value) === null) {
                        continue;
                    }
                    // prune out any null values in the secondary columns
                    $value = array_filter($value, fn($v) => $v !== null);
                } else {
                    $value = $this->decodeValue($element[$column], $field, $contentTableSchema->getColumn($column));
                    if ($value === null) {
                        continue;
                    }
                }
                $content[$fieldUid] = $value;
            }

            // don't call $this->update() so it doesn't mess with the CLI output
            Db::update(Table::ELEMENTS_SITES, [
                'title' => $element['title'] ?? null,
                'content' => !empty($content) ? Db::prepareForJsonColumn($content, $this->db) : null,
            ], ['id' => $element['id']], updateTimestamp: false, db: $this->db);

            echo " done\n";
        }

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

    private function elementLabel(array $element): string
    {
        $elementType = $element['type'];
        if ($elementType && class_exists($elementType) && is_subclass_of($elementType, ElementInterface::class)) {
            /** @var string|ElementInterface $elementType */
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

    private function decodeValue(mixed $value, FieldInterface $field, ColumnSchema $column): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (
            $field instanceof TableField ||
            ($field instanceof BaseOptionsField && $field->getIsMultiOptionsField())
        ) {
            return Json::decodeIfJson($value);
        }

        if ($field instanceof Lightswitch) {
            return (bool)$value;
        }

        switch ($column->type) {
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
        }

        return $value;
    }
}
