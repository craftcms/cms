<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;

/**
 * m180416_205628_resourcepaths_table migration.
 */
class m180416_205628_resourcepaths_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // In case this was run in a previous update attempt
        $this->dropTableIfExists('{{%resourcepaths}}');

        $this->createTable('{{%resourcepaths}}', [
            'hash' => $this->string()->notNull(),
            'path' => $this->string()->notNull(),
            'PRIMARY KEY([[hash]])',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180416_205628_resourcepaths_table cannot be reverted.\n";
        return false;
    }
}
