<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m150815_133521_last_login_attempt_ip migration.
 */
class m150815_133521_last_login_attempt_ip extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        MigrationHelper::renameColumn(Table::USERS, 'lastLoginAttemptIPAddress', 'lastLoginAttemptIp', $this);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m150815_133521_last_login_attempt_ip cannot be reverted.\n";

        return false;
    }
}
