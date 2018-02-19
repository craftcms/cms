<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;

/**
 * m180217_172123_tiny_ints migration.
 */
class m180217_172123_tiny_ints extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->alterColumn('{{%entrytypes}}', 'sortOrder', $this->tinyInteger()->unsigned());
        $this->alterColumn('{{%fieldlayoutfields}}', 'sortOrder', $this->tinyInteger()->unsigned());
        $this->alterColumn('{{%fieldlayouttabs}}', 'sortOrder', $this->tinyInteger()->unsigned());
        $this->alterColumn('{{%info}}', 'edition', $this->tinyInteger()->unsigned()->notNull());
        $this->alterColumn('{{%matrixblocks}}', 'sortOrder', $this->tinyInteger()->unsigned());
        $this->alterColumn('{{%matrixblocktypes}}', 'sortOrder', $this->tinyInteger()->unsigned());
        $this->alterColumn('{{%relations}}', 'sortOrder', $this->tinyInteger()->unsigned());
        $this->alterColumn('{{%routes}}', 'sortOrder', $this->tinyInteger()->unsigned());
        $this->alterColumn('{{%sites}}', 'sortOrder', $this->tinyInteger()->unsigned());
        $this->alterColumn('{{%tokens}}', 'usageLimit', $this->tinyInteger()->unsigned());
        $this->alterColumn('{{%tokens}}', 'usageCount', $this->tinyInteger()->unsigned());
        $this->alterColumn('{{%users}}', 'invalidLoginCount', $this->tinyInteger()->unsigned());
        $this->alterColumn('{{%volumes}}', 'sortOrder', $this->tinyInteger()->unsigned());
        $this->alterColumn('{{%widgets}}', 'sortOrder', $this->tinyInteger()->unsigned());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180217_172123_tiny_ints cannot be reverted.\n";
        return false;
    }
}
