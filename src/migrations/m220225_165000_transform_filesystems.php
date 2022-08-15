<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m220225_165000_transform_filesystems migration.
 */
class m220225_165000_transform_filesystems extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Add the fs column and set it to the volume handles as a starting point
        $this->addColumn(Table::VOLUMES, 'transformFs', $this->string()->after('fs'));

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m220225_165000_transform_filesystems cannot be reverted.\n";
        return false;
    }
}
