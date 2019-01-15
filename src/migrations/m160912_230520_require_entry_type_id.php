<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\MigrationHelper;
use yii\db\Expression;

/**
 * m160912_230520_require_entry_type_id migration.
 */
class m160912_230520_require_entry_type_id extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Get all of the sections' primary entry type IDs
        $subQuery = (new Query())
            ->select(['et.id'])
            ->from(['{{%entrytypes}} et'])
            ->where('[[et.sectionId]] = [[s.id]]')
            ->orderBy(['sortOrder' => SORT_ASC])
            ->limit(1);

        $results = (new Query())
            ->select([
                'sectionId' => 's.id',
                'typeId' => $subQuery
            ])
            ->from(['{{%sections}} s'])
            ->all($this->db);

        if (!empty($results)) {
            // Build the mapping case SQL
            $caseSql = 'case';

            foreach ($results as $result) {
                $caseSql .= ' when % = ' . $this->db->quoteValue($result['sectionId']) . ' then ' . $this->db->quoteValue($result['typeId']);
            }

            $caseSql .= ' end';

            // Update the entries without an entry type
            $this->update(Table::ENTRIES,
                [
                    'typeId' => new Expression(str_replace('%', $this->db->quoteColumnName('sectionId'), $caseSql)),
                ],
                'typeId is null',
                [],
                false);
        }

        // Are there any entries that still don't have a type?
        $typelessEntryIds = (new Query())
            ->select(['id'])
            ->from([Table::ENTRIES])
            ->where(['typeId' => null])
            ->column($this->db);

        if (!empty($typelessEntryIds)) {
            $this->delete(Table::ELEMENTS, ['id' => $typelessEntryIds]);
            echo "    > Deleted the following entries, because they didn't have an entry type: " . implode(',', $typelessEntryIds) . "\n";
        }

        // Make typeId required
        MigrationHelper::dropForeignKeyIfExists(Table::ENTRIES, ['typeId'], $this);
        $this->alterColumn(Table::ENTRIES, 'typeId', $this->integer()->notNull());
        $this->addForeignKey(null, Table::ENTRIES, ['typeId'], Table::ENTRYTYPES, ['id'], 'CASCADE', null);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m160912_230520_require_entry_type_id cannot be reverted.\n";

        return false;
    }
}
