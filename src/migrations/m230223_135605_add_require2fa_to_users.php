<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m230223_135605_add_require2fa_to_users migration.
 */
class m230223_135605_add_require2fa_to_users extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $table = $this->db->schema->getTableSchema(Table::USERS);
        if (!isset($table->columns['requireMfa'])) {
            $this->addColumn(
                Table::USERS,
                'requireMfa',
                $this->boolean()->defaultValue(false)->notNull()->after('lastPasswordChangeDate')
            );
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $table = $this->db->schema->getTableSchema(Table::USERS);
        if (isset($table->columns['requireMfa'])) {
            $this->dropColumn(Table::USERS, 'requireMfa');
        }

        return true;
    }
}
