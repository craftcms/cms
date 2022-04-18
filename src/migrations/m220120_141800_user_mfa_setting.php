<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m220120_141800_user_mfa_setting migration.
 */
class m220120_141800_user_mfa_setting extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn(Table::USERS, 'enable2fa', $this->boolean()->defaultValue(false)->after('password'));
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m220120_141800_user_mfa_setting cannot be reverted.\n";
        return false;
    }
}
