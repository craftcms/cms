<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m180824_193422_case_sensitivity_fixes migration.
 */
class m180824_193422_case_sensitivity_fixes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Unique URIs and user emails/usernames should no longer be enforced by the DB
        MigrationHelper::dropIndexIfExists(Table::ELEMENTS_SITES, ['uri', 'siteId'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::USERS, ['email'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::USERS, ['username'], true, $this);

        if ($this->db->getIsMysql()) {
            $this->createIndex(null, Table::ELEMENTS_SITES, ['uri', 'siteId']);
            $this->createIndex(null, Table::USERS, ['email']);
            $this->createIndex(null, Table::USERS, ['username']);
        } else {
            // Postgres is case-sensitive
            $this->createIndex($this->db->getIndexName(Table::ELEMENTS_SITES, ['uri', 'siteId']), Table::ELEMENTS_SITES, ['lower([[uri]])', 'siteId']);
            $this->createIndex($this->db->getIndexName(Table::USERS, ['email']), Table::USERS, ['lower([[email]])']);
            $this->createIndex($this->db->getIndexName(Table::USERS, ['username']), Table::USERS, ['lower([[username]])']);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180824_193422_case_sensitivity_fixes cannot be reverted.\n";
        return false;
    }
}
