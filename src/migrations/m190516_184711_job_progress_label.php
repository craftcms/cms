<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m190516_184711_job_progress_label migration.
 */
class m190516_184711_job_progress_label extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(Table::QUEUE, 'progressLabel', $this->string()->after('progress'));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190516_184711_job_progress_label cannot be reverted.\n";
        return false;
    }
}
