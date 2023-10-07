<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m231006_071504_job_profiling migration.
 */
class m231006_071504_job_profiling extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->createTable(Table::QUEUEPROFILES, [
            'key' => $this->string(),
            'dateExecuted' => $this->dateTime()->notNull(),
            'duration' => $this->integer()->notNull(),
            'PRIMARY KEY([[key]], [[dateExecuted]])',
        ]);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m231006_071504_job_profiling cannot be reverted.\n";
        return false;
    }
}
