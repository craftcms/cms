<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;

/**
 * m230826_094050_fix_session_id_type migration.
 */
class m230826_094050_fix_session_id_type extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (Craft::$app->getDb()->tableExists(Table::PHPSESSIONS)) {
            $this->alterColumn(Table::PHPSESSIONS, 'id', $this->string()->notNull());
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m230826_094050_fix_session_id_type cannot be reverted.\n";
        return false;
    }
}
