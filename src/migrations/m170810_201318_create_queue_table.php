<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m170810_201318_create_queue_table migration.
 */
class m170810_201318_create_queue_table extends Migration
{
    /**
     * @var string The queue table name
     */
    public $tableName = Table::QUEUE;

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // In case this was run in a previous update attempt
        $this->dropTableIfExists(Table::QUEUE);

        $this->createTable(Table::QUEUE, [
            'id' => $this->primaryKey(),
            'job' => $this->binary()->notNull(),
            'description' => $this->text(),
            'timePushed' => $this->integer()->notNull(),
            'ttr' => $this->integer()->notNull(),
            'delay' => $this->integer()->defaultValue(0)->notNull(),
            'priority' => $this->integer()->unsigned()->notNull()->defaultValue(1024),
            'dateReserved' => $this->dateTime(),
            'timeUpdated' => $this->integer(),
            'progress' => $this->smallInteger()->notNull()->defaultValue(0),
            'attempt' => $this->integer(),
            'fail' => $this->boolean()->defaultValue(false),
            'dateFailed' => $this->dateTime(),
            'error' => $this->text(),
        ]);

        $this->createIndex(null, Table::QUEUE, ['fail', 'timeUpdated', 'timePushed']);
        $this->createIndex(null, Table::QUEUE, ['fail', 'timeUpdated', 'delay']);

        // Drop the tasks table while we're at it
        $this->dropTable('{{%tasks}}');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170810_201318_create_queue_table cannot be reverted.\n";
        return false;
    }
}
