<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m180904_112109_permission_changes migration.
 */
class m180904_112109_permission_changes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->update(Table::USERPERMISSIONS, [
            'name' => 'moderateusers',
        ], [
            'name' => 'administrateusers'
        ], [], false);

        $this->update(Table::USERPERMISSIONS, [
            'name' => 'administrateusers',
        ], [
            'name' => 'changeuseremails'
        ], [], false);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180904_112109_permission_changes cannot be reverted.\n";
        return false;
    }
}
