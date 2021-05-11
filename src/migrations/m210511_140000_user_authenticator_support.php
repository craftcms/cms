<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m210511_140000_user_authenticator_support migration.
 */
class m210511_140000_user_authenticator_support extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(Table::USERS, 'authenticatorSecret', $this->char(32)->after('password'));
        $this->addColumn(Table::USERS, 'authenticatorTimestamp', $this->bigInteger()->after('authenticatorSecret'));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m210511_140000_user_authenticator_support cannot be reverted.\n";
        return false;
    }
}
