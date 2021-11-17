<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\fields\Assets;
use craft\helpers\ArrayHelper;

/**
 * m211109_083000_asset_field_mode_setting migration.
 */
class m211109_083000_asset_field_mode_setting extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // set mode based on setting if schema version
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);

        if (version_compare($schemaVersion, '4.0.0', '<')) {
            $allFields = $projectConfig->get('fields');

            foreach ($allFields as $uid => $field) {
                if ($field['type'] === Assets::class) {
                    if (!empty($field['settings']['useSingleFolder'])) {
                        $field['settings']['fieldMode'] = Assets::MODE_SINGLE_FOLDER;
                    } else {
                        $field['settings']['fieldMode'] = Assets::MODE_NORMAL;
                    }
                    unset($field['settings']['useSingleFolder']);
                    $projectConfig->set('fields.' . $uid, $field);
                }
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m211109_083000_asset_field_mode_setting cannot be reverted.\n";
        return false;
    }
}
