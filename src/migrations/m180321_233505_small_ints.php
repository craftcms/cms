<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

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
        $this->alterColumn(Table::ENTRYTYPES, 'sortOrder', $this->smallInteger()->unsigned());
        $this->alterColumn(Table::FIELDLAYOUTFIELDS, 'sortOrder', $this->smallInteger()->unsigned());
        $this->alterColumn(Table::FIELDLAYOUTTABS, 'sortOrder', $this->smallInteger()->unsigned());
        $this->alterColumn(Table::MATRIXBLOCKS, 'sortOrder', $this->smallInteger()->unsigned());
        $this->alterColumn(Table::MATRIXBLOCKTYPES, 'sortOrder', $this->smallInteger()->unsigned());
        $this->alterColumn(Table::RELATIONS, 'sortOrder', $this->smallInteger()->unsigned());
        $this->alterColumn('{{%routes}}', 'sortOrder', $this->smallInteger()->unsigned());
        $this->alterColumn(Table::SITES, 'sortOrder', $this->smallInteger()->unsigned());
        $this->alterColumn(Table::VOLUMES, 'sortOrder', $this->smallInteger()->unsigned());
        $this->alterColumn(Table::WIDGETS, 'sortOrder', $this->smallInteger()->unsigned());
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
