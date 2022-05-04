<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m210809_124211_remove_superfluous_uids migration.
 */
class m210809_124211_remove_superfluous_uids extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->dropIndexIfExists(Table::USERS, ['uid'], false);

        $tables = [
            Table::ASSETS,
            Table::CATEGORIES,
            Table::ENTRIES,
            Table::MATRIXBLOCKS,
            Table::TAGS,
            Table::USERS,
        ];

        foreach ($tables as $table) {
            $this->dropColumn($table, 'uid');
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m210809_124211_remove_superfluous_uids cannot be reverted.\n";
        return false;
    }
}
