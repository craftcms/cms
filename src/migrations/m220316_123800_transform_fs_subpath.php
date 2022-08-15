<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m220316_123800_transform_fs_subpath migration.
 */
class m220316_123800_transform_fs_subpath extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn(Table::VOLUMES, 'transformSubpath', $this->string()->after('transformFs'));
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m220316_123800_transform_fs_subpath cannot be reverted.\n";
        return false;
    }
}
