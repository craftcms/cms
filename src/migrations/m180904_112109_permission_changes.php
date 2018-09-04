<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;

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
        $this->update('{{%userpermissions}}', [
            'name' => 'moderateusers',
        ], [
            'name' => 'administrateusers'
        ], [], false);

        $this->update('{{%userpermissions}}', [
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
