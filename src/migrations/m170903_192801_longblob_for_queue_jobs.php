<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m170903_192801_longblob_for_queue_jobs migration.
 */
class m170903_192801_longblob_for_queue_jobs extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if ($this->db->getIsMysql()) {
            // "binary" resolves to LONGBLOB now rather than BLOB
            $this->alterColumn(Table::QUEUE, 'job', $this->binary()->notNull());
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170903_192801_longblob_for_queue_jobs cannot be reverted.\n";
        return false;
    }
}
