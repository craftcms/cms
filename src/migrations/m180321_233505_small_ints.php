<?php

namespace craft\migrations;

use craft\db\Migration;

/**
 * m180321_233505_small_ints migration.
 */
class m180321_233505_small_ints extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->alterColumn('{{%entrytypes}}', 'sortOrder', $this->smallInteger()->unsigned());
        $this->alterColumn('{{%fieldlayoutfields}}', 'sortOrder', $this->smallInteger()->unsigned());
        $this->alterColumn('{{%fieldlayouttabs}}', 'sortOrder', $this->smallInteger()->unsigned());
        $this->alterColumn('{{%matrixblocks}}', 'sortOrder', $this->smallInteger()->unsigned());
        $this->alterColumn('{{%matrixblocktypes}}', 'sortOrder', $this->smallInteger()->unsigned());
        $this->alterColumn('{{%relations}}', 'sortOrder', $this->smallInteger()->unsigned());
        $this->alterColumn('{{%routes}}', 'sortOrder', $this->smallInteger()->unsigned());
        $this->alterColumn('{{%sites}}', 'sortOrder', $this->smallInteger()->unsigned());
        $this->alterColumn('{{%volumes}}', 'sortOrder', $this->smallInteger()->unsigned());
        $this->alterColumn('{{%widgets}}', 'sortOrder', $this->smallInteger()->unsigned());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180321_233505_small_ints cannot be reverted.\n";
        return false;
    }
}
