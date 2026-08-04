<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m230314_111234_add_webauthn_table migration.
 */
class m230314_111234_add_webauthn_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->dropTableIfExists(Table::WEBAUTHN);

        $this->createTable(Table::WEBAUTHN, [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'credentialId' => $this->string()->defaultValue(null),
            'credential' => $this->text()->defaultValue(null),
            'credentialName' => $this->string()->defaultValue(null),
            'dateLastUsed' => $this->dateTime()->defaultValue(null),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->addForeignKey(null, Table::WEBAUTHN, ['userId'], Table::USERS, ['id'], 'CASCADE', null);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropTableIfExists(Table::WEBAUTHN);
        return true;
    }
}
