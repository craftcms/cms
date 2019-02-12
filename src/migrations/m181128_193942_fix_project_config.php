<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\fields\Matrix;
use craft\helpers\Json;
use craft\services\Fields;
use craft\services\UserGroups;

/**
 * m181128_193942_fix_project_config migration.
 */
class m181128_193942_fix_project_config extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $projectConfig = Craft::$app->getProjectConfig();

        // Don't make the same config changes twice
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);
        if (version_compare($schemaVersion, '3.1.8', '>=')) {
            return;
        }

        // Update Matrix settings in the project config to match the DB
        // (correction for m180901_151639_fix_matrixcontent_tables)
        $fieldsService = Craft::$app->getFields();
        $projectConfig->muteEvents = true;

        $matrixFields = (new Query())
            ->select(['uid', 'settings'])
            ->from([Table::FIELDS])
            ->where(['type' => Matrix::class, 'context' => 'global'])
            ->all();

        foreach ($matrixFields as $matrixField) {
            $path = Fields::CONFIG_FIELDS_KEY . '.' . $matrixField['uid'] . '.settings';
            $settings = Json::decode($matrixField['settings']);
            $projectConfig->set($path, $settings);
        }

        $projectConfig->muteEvents = false;

        // Update user group permissions
        // (correction for m180904_112109_permission_changes)
        $userGroups = $projectConfig->get(UserGroups::CONFIG_USERPGROUPS_KEY);
        if (!empty($userGroups)) {
            foreach ($userGroups as $uid => $userGroup) {
                if (!empty($userGroup['permissions'])) {
                    $permissions = $userGroup['permissions'];
                    $changed = false;

                    // administrateusers => moderateusers
                    if (
                        ($pos = array_search('administrateusers', $permissions, true)) !== false &&
                        !in_array('moderateusers', $permissions, true)
                    ) {
                        $permissions[$pos] = 'moderateusers';
                        $changed = true;
                    }

                    // changeuseremails => administrateusers
                    if (($pos = array_search('changeuseremails', $permissions, true)) !== false) {
                        $permissions[$pos] = 'administrateusers';
                        $changed = true;
                    }

                    if ($changed) {
                        $path = UserGroups::CONFIG_USERPGROUPS_KEY . '.' . $uid . '.permissions';
                        $projectConfig->set($path, $permissions);
                    }
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m181128_193942_fix_project_config cannot be reverted.\n";
        return false;
    }
}
