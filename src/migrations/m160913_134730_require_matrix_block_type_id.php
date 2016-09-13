<?php

namespace craft\app\migrations;

use Craft;
use craft\app\db\Migration;
use craft\app\db\Query;
use craft\app\helpers\MigrationHelper;

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
            ->select('id')
            ->from('{{%matrixblocks}}')
            ->where('typeId is null')
            ->column();

        if ($typelessBlockIds) {
            $this->delete('{{%elements}}', ['in', 'id', $typelessBlockIds]);
            Craft::warning("Deleted the following Matrix blocks, because they didn't have a block type: ".implode(',', $typelessBlockIds));
        }

        // Make typeId required
        MigrationHelper::dropForeignKeyIfExists('{{%matrixblocks}}', ['typeId'], $this);
        $this->alterColumn('{{%matrixblocks}}', 'typeId', $this->integer()->notNull());
        $this->addForeignKey($this->db->getForeignKeyName('{{%matrixblocks}}', 'typeId'), '{{%matrixblocks}}', 'typeId', '{{%matrixblocktypes}}', 'id', 'CASCADE', null);
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
