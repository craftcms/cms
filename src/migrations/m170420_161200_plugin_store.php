<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;

/**
 * m170420_161200_plugin_store migration.
 */
class m170420_161200_plugin_store extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable(
            '{{%plugin_store_tokens}}',
            [
                'id' => $this->integer()->notNull(),
                'userId' => $this->integer()->notNull(),
                'accessToken' => $this->text()->notNull(),
                'tokenType' => $this->string(),
                'expiresIn' => $this->integer(),
                'expiryDate' => $this->dateTime(),
                'refreshToken' => $this->text(),

                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
                'PRIMARY KEY(id)',
            ]
        );

        $this->createIndex($this->db->getIndexName('{{%plugin_store_tokens}}', 'userId', true), '{{%plugin_store_tokens}}', 'userId', true);
        $this->addForeignKey($this->db->getForeignKeyName('{{%plugin_store_tokens}}', 'userId'), '{{%plugin_store_tokens}}', 'userId', '{{%users}}', 'id', 'CASCADE', null);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170420_161200_plugin_store cannot be reverted.\n";
        return false;
    }
}
