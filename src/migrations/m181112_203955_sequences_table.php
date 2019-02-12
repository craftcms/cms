<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m181112_203955_sequences_table migration.
 */
class m181112_203955_sequences_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable(Table::SEQUENCES, [
            'name' => $this->string()->notNull(),
            'next' => $this->integer()->unsigned()->notNull()->defaultValue(1),
            'PRIMARY KEY([[name]])',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m181112_203955_sequences_table cannot be reverted.\n";
        return false;
    }
}
