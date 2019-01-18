<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

/**
 * m180520_173000_matrix_context_to_uids migration.
 */
class m180520_173000_matrix_context_to_uids extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Map Matrix block type IDs to UUIDs
        $blockTypeUids = (new Query())
            ->select(['id', 'uid'])
            ->from([Table::MATRIXBLOCKTYPES])
            ->pairs();

        // Get all the Matrix sub-fields
        $fields = (new Query())
            ->select(['id', 'context'])
            ->from([Table::FIELDS])
            ->where(['like', 'context', 'matrixBlockType'])
            ->all();

        // Switch out IDs for UUIDs
        foreach ($fields as $field) {
            list(, $blockTypeId) = explode(':', $field['context'], 2);

            // Make sure the block type still exists
            if (!isset($blockTypeUids[$blockTypeId])) {
                continue;
            }

            $this->update(Table::FIELDS, [
                'context' => 'matrixBlockType:' . $blockTypeUids[$blockTypeId]
            ], [
                'id' => $field['id']
            ], [], false);
        }
    }


    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180520_173000_matrix_context_to_uids cannot be reverted.\n";

        return false;
    }

}
