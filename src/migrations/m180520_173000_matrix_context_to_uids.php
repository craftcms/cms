<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;

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
        // Get map of IDs to UIDs
        $blockPairs = (new Query())
            ->select(['id', 'uid'])
            ->from(['{{%matrixblocktypes}}'])
            ->pairs();

        // Get all
        $fields = (new Query())
            ->select(['id', 'context'])
            ->from(['{{%fields}}'])
            ->where(['like', 'context', 'matrixBlockType'])
            ->all();

        // Switch out ids for UIDs
        foreach ($fields as $row) {
            $context = explode(':', $row['context']);
            $newContext = $context[0] . ':' . $blockPairs[$context[1]];
            $this->update('{{%fields}}', [
                'context' => $newContext
            ], [
                'id' => $row['id']
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
