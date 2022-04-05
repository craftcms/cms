<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\helpers\Db;

/**
 * m190502_122019_store_default_user_group_uid migration.
 */
class m190502_122019_store_default_user_group_uid extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $defaultGroup = $projectConfig->get('users.defaultGroup', true);
        if (is_numeric($defaultGroup)) {
            $uid = Db::uidById(Table::USERGROUPS, $defaultGroup);
            $projectConfig->set('users.defaultGroup', $uid);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190502_122019_store_default_user_group_uid cannot be reverted.\n";
        return false;
    }
}
