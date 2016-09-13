<?php

namespace craft\app\migrations;

use Craft;
use craft\app\db\Migration;
use craft\app\db\Query;
use craft\app\helpers\MigrationHelper;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

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
            ->select('et.id')
            ->from('{{%entrytypes}} et')
            ->where('et.sectionId = s.id')
            ->orderBy('sortOrder asc')
            ->limit(1);

        $results = (new Query())
            ->select([
                'sectionId' => 's.id',
                'typeId' => $subQuery
            ])
            ->from('{{%sections}} s')
            ->all();

        if ($results) {
            // Build the mapping case SQL
            $caseSql = 'case';

            foreach ($results as $result) {
                $caseSql .= ' when % = '.$this->db->quoteValue($result['sectionId']).' then '.$this->db->quoteValue($result['typeId']);
            }

            $caseSql .= ' end';

            // Update the entries without an entry type
            $this->update('{{%entries}}',
                [
                    'typeId' => new Expression(str_replace('%', $this->db->quoteColumnName('sectionId'), $caseSql)),
                ],
                'typeId is null',
                [],
                false);
        }

        // Are there any entries that still don't have a type?
        $typelessEntryIds = (new Query())
            ->select('id')
            ->from('{{%entries}}')
            ->where('typeId is null')
            ->column();

        if ($typelessEntryIds) {
            $this->delete('{{%elements}}', ['in', 'id', $typelessEntryIds]);
            Craft::warning("Deleted the following entries, because they didn't have an entry type: ".implode(',', $typelessEntryIds));
        }

        // Make typeId required
        MigrationHelper::dropForeignKeyIfExists('{{%entries}}', ['typeId'], $this);
        $this->alterColumn('{{%entries}}', 'typeId', $this->integer()->notNull());
        $this->addForeignKey($this->db->getForeignKeyName('{{%entries}}', 'typeId'), '{{%entries}}', 'typeId', '{{%entrytypes}}', 'id', 'CASCADE', null);
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
