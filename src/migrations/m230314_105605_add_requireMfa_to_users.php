<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\services\ProjectConfig;

/**
 * m230314_105605_add_requireMfa_to_users migration.
 */
class m230314_105605_add_requireMfa_to_users extends Migration
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

        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);

        if (version_compare($schemaVersion, '4.5.0', '<')) {
            $userSettings = $projectConfig->get(ProjectConfig::PATH_USERS);
            $userSettings['requireMfa'] = [];
            $projectConfig->set(ProjectConfig::PATH_USERS, $userSettings);
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

        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);

        if (version_compare($schemaVersion, '4.5.0', '<')) {
            $userSettings = $projectConfig->get(ProjectConfig::PATH_USERS);
            if (isset($userSettings['requireMfa'])) {
                unset($userSettings['requireMfa']);
                $projectConfig->set(ProjectConfig::PATH_USERS, $userSettings);
            }
        }

        return true;
    }
}
