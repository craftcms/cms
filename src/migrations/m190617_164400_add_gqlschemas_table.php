<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m190617_164400_add_gqlschemas_table migration.
 */
class m190617_164400_add_gqlschemas_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->dropTableIfExists(Table::GQLSCHEMAS);
        $this->createTable(Table::GQLSCHEMAS, [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'accessToken' => $this->string()->notNull(),
            'enabled' => $this->boolean()->notNull()->defaultValue(true),
            'expiryDate' => $this->dateTime(),
            'lastUsed' => $this->dateTime(),
            'scope' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, Table::GQLSCHEMAS, ['accessToken'], true);
        $this->createIndex(null, Table::GQLSCHEMAS, ['name'], true);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190617_164400_add_gqlschemas_table cannot be reverted.\n";
        return false;
    }
}
