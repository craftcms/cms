<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;

/**
 * m170910_142732_pluginstoretokens_table migration.
 */
class m170910_142732_pluginstoretokens_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable('{{%pluginstoretokens}}', [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'oauthTokenId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, '{{%pluginstoretokens}}', ['userId'], true);
        $this->addForeignKey(null, '{{%pluginstoretokens}}', ['userId'], '{{%users}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%pluginstoretokens}}', ['oauthTokenId'], '{{%oauthtokens}}', ['id'], 'CASCADE', null);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170910_142732_pluginstoretokens_table cannot be reverted.\n";
        return false;
    }
}
