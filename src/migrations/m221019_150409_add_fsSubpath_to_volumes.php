<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;

/**
 * m221019_150409_add_fsSubpath_to_volumes migration.
 */
class m221019_150409_add_fsSubpath_to_volumes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn(Table::VOLUMES, 'fsSubpath', $this->string()->after('fs'));
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m221019_150409_add_fsSubpath_to_volumes cannot be reverted.\n";
        return false;
    }
}
