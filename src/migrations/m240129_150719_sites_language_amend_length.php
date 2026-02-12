<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;

/**
 * m240129_150719_sites_language_amend_length migration.
 */
class m240129_150719_sites_language_amend_length extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (Craft::$app->getDb()->tableExists(Table::SITES)) {
            $this->alterColumn(Table::SITES, 'language', $this->string()->notNull());
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m240129_150719_sites_language_amend_length cannot be reverted.\n";
        return false;
    }
}
