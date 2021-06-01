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
        $this->createTable(Table::AUTH_AUTHENTICATOR, [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'authenticatorSecret' => $this->char(32),
            'authenticatorTimestamp' => $this->bigInteger(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->addForeignKey(null, Table::AUTH_AUTHENTICATOR, ['userId'], Table::USERS, ['id'], 'CASCADE', null);
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
