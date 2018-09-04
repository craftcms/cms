<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\fields\Matrix;
use craft\helpers\Json;
use yii\db\Expression;

/**
 * m180901_151639_fix_matrixcontent_tables migration.
 */
class m180901_151639_fix_matrixcontent_tables extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $fields = (new Query())
            ->select(['id', 'handle', 'settings'])
            ->from(['{{%fields}}'])
            ->where(['type' => Matrix::class])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        $fieldsByHandle = [];
        $duplicateHandles = [];

        foreach ($fields as $field) {
            $handle = strtolower($field['handle']);
            $field['settings'] = Json::decode($field['settings']);
            if (isset($fieldsByHandle[$handle])) {
                $duplicateHandles[$handle] = true;
                $field['settings']['contentTable'] = '{{%matrixcontent_' . $handle . '_' . count($fieldsByHandle[$handle]) . '}}';
            } else {
                $field['settings']['contentTable'] = '{{%matrixcontent_' . $handle . '}}';
            }
            $fieldsByHandle[$handle][] = $field;
            $this->update('{{%fields}}', [
                'settings' => Json::encode($field['settings']),
            ], [
                'id' => $field['id'],
            ], [], false);
        }

        if (!empty($duplicateHandles)) {
            // Disable FK checks
            $queryBuilder = $this->db->getSchema()->getQueryBuilder();
            $this->execute($queryBuilder->checkIntegrity(false));

            foreach (array_keys($duplicateHandles) as $handle) {
                $originalField = array_shift($fieldsByHandle[$handle]);
                $originalTableName = $originalField['settings']['contentTable'];
                $originalSchema = $this->db->getTableSchema($originalTableName);

                $originalFieldColumns = array_filter($originalSchema->getColumnNames(), function($columnName) {
                    return strpos($columnName, 'field_') === 0;
                });

                $seqStart = 1 + (new Query())
                        ->from([$originalTableName])
                        ->max('[[id]]');

                foreach ($fieldsByHandle[$handle] as $i => $field) {
                    // duplicate the table for this field, and clean it up
                    $tableName = $field['settings']['contentTable'];
                    $this->execute("create table {$tableName} as select * from {$originalTableName}");

                    if ($this->db->getIsPgsql()) {
                        // Manually construct the SQL for Postgres
                        // (see https://github.com/yiisoft/yii2/issues/12077)
                        $this->addPrimaryKey(null, $tableName, ['id']);
                        $rawTableName = $this->db->tablePrefix . trim($tableName, '{}%');
                        $seqName = $rawTableName . '_id_seq';
                        $this->execute("create sequence [[{$seqName}]] start with {$seqStart} owned by [[{$rawTableName}.id]]");
                        $this->execute("alter table {$tableName} alter column [[id]] set default nextval('{$seqName}')");
                        $this->execute("alter table {$tableName} alter column [[elementId]] set not null");
                        $this->execute("alter table {$tableName} alter column [[siteId]] set not null");
                        $this->execute("alter table {$tableName} alter column [[dateCreated]] set not null");
                        $this->execute("alter table {$tableName} alter column [[dateUpdated]] set not null");
                        $this->execute("alter table {$tableName} alter column [[uid]] set not null");
                    } else {
                        $this->alterColumn($tableName, 'id', $this->primaryKey());
                        $this->alterColumn($tableName, 'elementId', $this->integer()->notNull());
                        $this->alterColumn($tableName, 'siteId', $this->integer()->notNull());
                        $this->alterColumn($tableName, 'dateCreated', $this->dateTime()->notNull());
                        $this->alterColumn($tableName, 'dateUpdated', $this->dateTime()->notNull());
                        $this->alterColumn($tableName, 'uid', $this->uid());
                    }

                    $this->createIndex(null, $tableName, ['elementId', 'siteId'], true);
                    $this->addForeignKey(null, $tableName, ['elementId'], '{{%elements}}', ['id'], 'CASCADE', null);
                    $this->addForeignKey(null, $tableName, ['siteId'], '{{%sites}}', ['id'], 'CASCADE', 'CASCADE');
                    $this->_cleanUpTable($field['id'], $tableName, $originalFieldColumns);
                }

                // now clean up the original table
                $this->_cleanUpTable($originalField['id'], $originalTableName, $originalFieldColumns);
            }

            // Re-enable FK checks
            $this->execute($queryBuilder->checkIntegrity(true));
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180901_151639_fix_matrixcontent_tables cannot be reverted.\n";
        return false;
    }

    private function _cleanUpTable(int $fieldId, string $tableName, array $originalFieldColumns)
    {
        // delete the rows we don't need
        $this->delete($tableName, ['not in', 'elementId', (new Query())
            ->select(['id'])
            ->from(['{{%matrixblocks}}'])
            ->where(['fieldId' => $fieldId])
        ]);

        // get all of the columns this field needs
        $subFields = (new Query())
            ->select(['f.handle', 'mbt.handle as blockTypeHandle'])
            ->from(['{{%fields}} f'])
            ->innerJoin('{{%fieldlayoutfields}} flf', '[[flf.fieldId]] = [[f.id]]')
            ->innerJoin('{{%matrixblocktypes}} mbt', '[[mbt.fieldLayoutId]] = [[flf.layoutId]]')
            ->where(['mbt.fieldId' => $fieldId])
            ->all();

        $columns = [];

        foreach ($subFields as $subField) {
            $column = 'field_' . $subField['blockTypeHandle'] . '_' . $subField['handle'];
            if (in_array($column, $originalFieldColumns, true)) {
                $columns[] = $column;
            }
        }

        // drop the ones we don't need
        $columnsToDrop = array_diff($originalFieldColumns, $columns);
        foreach ($columnsToDrop as $columnName) {
            $this->dropColumn($tableName, $columnName);
        }
    }
}
