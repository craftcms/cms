<?php

namespace craft\migrations;

use craft\db\Migration;

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
            $this->alterColumn('{{%queue}}', 'job', $this->binary()->notNull());
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
