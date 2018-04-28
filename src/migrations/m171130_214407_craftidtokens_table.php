<?php

namespace craft\migrations;

use craft\db\Migration;

/**
 * m171130_214407_craftidtokens_table migration.
 */
class m171130_214407_craftidtokens_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // In case this (or previous incarnation) was run in a previous update attempt
        $this->dropTableIfExists('{{%pluginstoretokens}}');
        $this->dropTableIfExists('{{%oauth_tokens}}');
        $this->dropTableIfExists('{{%oauthtokens}}');
        $this->dropTableIfExists('{{%craftidtokens}}');

        // Create the new one
        $this->createTable('{{%craftidtokens}}', [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'accessToken' => $this->text()->notNull(),
            'expiryDate' => $this->dateTime(),
            'refreshToken' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->addForeignKey(null, '{{%craftidtokens}}', ['userId'], '{{%users}}', ['id'], 'CASCADE', null);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m171130_214407_craftidtokens_table cannot be reverted.\n";
        return false;
    }
}
