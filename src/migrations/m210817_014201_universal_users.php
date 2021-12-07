<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\helpers\ArrayHelper;

/**
 * m210817_014201_universal_users migration.
 */
class m210817_014201_universal_users extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Drop NOT NULL from `username` & `email`
        if ($this->db->getIsPgsql()) {
            $this->execute('alter table {{%users}} alter column [[username]] type varchar(255), alter column [[username]] drop not null');
            $this->execute('alter table {{%users}} alter column [[email]] type varchar(255), alter column [[email]] drop not null');
        } else {
            $this->alterColumn(Table::USERS, 'username', $this->string());
            $this->alterColumn(Table::USERS, 'email', $this->string());
        }

        // Allow `firstName` & `lastName` to be 255 chars
        $this->alterColumn(Table::USERS, 'firstName', $this->string());
        $this->alterColumn(Table::USERS, 'lastName', $this->string());

        // Add the `active` column
        $this->addColumn(Table::USERS, 'active', $this->boolean()->defaultValue(false)->notNull()->after('admin'));

        // Add the new indexes
        $this->createIndex(null, Table::USERS, ['active'], false);
        $this->createIndex(null, Table::USERS, ['locked'], false);
        $this->createIndex(null, Table::USERS, ['pending'], false);
        $this->createIndex(null, Table::USERS, ['suspended'], false);

        // Mark all non-pending users as active
        $this->update(Table::USERS, [
            'active' => true,
        ], [
            'pending' => false,
        ]);

        // Mark all suspended-by-default users as inactive
        $this->update(Table::USERS, [
            'active' => false,
            'pending' => false,
            'suspended' => false,
        ], [
            'suspended' => true,
            'lastLoginDate' => null,
        ]);

        // suspendByDefault => deactivateByDefault
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);

        if (version_compare($schemaVersion, '4.0.0', '<')) {
            $userSettings = $projectConfig->get('users');
            if ($userSettings) {
                $userSettings['deactivateByDefault'] = ArrayHelper::remove($userSettings, 'suspendByDefault', false);
                $projectConfig->set('users', $userSettings);
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m210817_014201_universal_users cannot be reverted.\n";
        return false;
    }
}
