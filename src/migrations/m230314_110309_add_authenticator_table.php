<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m230314_110309_add_authenticator_table migration.
 */
class m230314_110309_add_authenticator_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->dropTableIfExists(Table::AUTHENTICATOR);

        $this->createTable(Table::AUTHENTICATOR, [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'auth2faSecret' => $this->string()->defaultValue(null),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
        ]);

        $this->addForeignKey(null, Table::AUTHENTICATOR, ['userId'], Table::USERS, ['id'], 'CASCADE', null);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropTableIfExists(Table::AUTHENTICATOR);
        return true;
    }
}
