<?php

namespace craft\app\migrations;

use Craft;
use craft\app\db\Migration;
use craft\app\helpers\MigrationHelper;
use yii\db\Schema;

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
        MigrationHelper::renameColumn('{{%users}}', 'lastLoginAttemptIPAddress', 'lastLoginAttemptIp', $this);
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
