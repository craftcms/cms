<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m270511_120000_webauthn_support migration.
 */
class m270511_120000_webauthn_support extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable(Table::AUTH_WEBAUTHN, [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'credentialId' => $this->string(),
            'credential' => $this->text(),
            'name' => $this->string(),
            'dateLastUsed' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->addForeignKey(null, Table::AUTH_WEBAUTHN, ['userId'], Table::USERS, ['id'], 'CASCADE', null);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m270511_120000_webauthn_support cannot be reverted.\n";
        return false;
    }
}
