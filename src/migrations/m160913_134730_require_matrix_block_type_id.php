<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\MigrationHelper;

/**
 * m160913_134730_require_matrix_block_type_id migration.
 */
class m160913_134730_require_matrix_block_type_id extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Are there any Matrix blocks that don't have a type?
        $typelessBlockIds = (new Query())
            ->select(['id'])
            ->from(['{{%matrixblocks}}'])
            ->where(['typeId' => null])
            ->column($this->db);

        if (!empty($typelessBlockIds)) {
            $this->delete('{{%elements}}', ['id' => $typelessBlockIds]);
            echo "    > Deleted the following Matrix blocks, because they didn't have a block type: ".implode(',', $typelessBlockIds)."\n";
        }

        // Make typeId required
        MigrationHelper::dropForeignKeyIfExists('{{%matrixblocks}}', ['typeId'], $this);
        $this->alterColumn('{{%matrixblocks}}', 'typeId', $this->integer()->notNull());
        $this->addForeignKey(null, '{{%matrixblocks}}', ['typeId'], '{{%matrixblocktypes}}', ['id'], 'CASCADE', null);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m160913_134730_require_matrix_block_type_id cannot be reverted.\n";

        return false;
    }
}
