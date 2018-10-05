<?php

namespace craft\migrations;

use craft\db\Migration;
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
        MigrationHelper::dropIndexIfExists('{{%elements_sites}}', ['uri', 'siteId'], true, $this);
        MigrationHelper::dropIndexIfExists('{{%users}}', ['email'], true, $this);
        MigrationHelper::dropIndexIfExists('{{%users}}', ['username'], true, $this);

        if ($this->db->getIsMysql()) {
            $this->createIndex(null, '{{%elements_sites}}', ['uri', 'siteId']);
            $this->createIndex(null, '{{%users}}', ['email']);
            $this->createIndex(null, '{{%users}}', ['username']);
        } else {
            // Postgres is case-sensitive
            $this->createIndex($this->db->getIndexName('{{%elements_sites}}', ['uri', 'siteId']), '{{%elements_sites}}', ['lower([[uri]])', 'siteId']);
            $this->createIndex($this->db->getIndexName('{{%users}}', ['email']), '{{%users}}', ['lower([[email]])']);
            $this->createIndex($this->db->getIndexName('{{%users}}', ['username']), '{{%users}}', ['lower([[username]])']);
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
