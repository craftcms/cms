<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\ProjectConfig;
use craft\models\GqlToken;
use craft\services\Fields;
use craft\services\Gql;
use craft\services\Plugins;
use craft\services\Volumes;

/**
 * m191204_085100_pack_savable_component_settings migration.
 */
class m191204_085100_pack_savable_component_settings extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {

        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);

        // Pack the settings to protect them from being sorted, in case the order matters
        if (version_compare($schemaVersion, '3.4.2', '<')) {
            $projectConfig->muteEvents = true;

            // Fields
            $fields = $projectConfig->get(Fields::CONFIG_FIELDS_KEY);

            if (!empty($fields)) {
                foreach ($fields as &$field) {
                    $field['settings'] = ProjectConfig::packAssociativeArray($field['settings']);
                }

                $projectConfig->set(Fields::CONFIG_FIELDS_KEY, $fields);
            }

            // Volumes
            $volumes = $projectConfig->get(Volumes::CONFIG_VOLUME_KEY);

            if (!empty($volumes)) {
                foreach ($volumes as &$volume) {
                    $volume['settings'] = ProjectConfig::packAssociativeArray($volume['settings']);
                }

                $projectConfig->set(Volumes::CONFIG_VOLUME_KEY, $volumes);
            }

            // Plugins
            $plugins = $projectConfig->get(Plugins::CONFIG_PLUGINS_KEY);
            if (!empty($plugins)) {
                foreach ($plugins as $plugin) {
                    $plugin['settings'] = ProjectConfig::packAssociativeArray($plugin['settings']);
                }

                $projectConfig->set(Plugins::CONFIG_PLUGINS_KEY, $plugins);
            }

            $projectConfig->muteEvents = false;
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m191204_085100_pack_savable_component_settings cannot be reverted.\n";
        return false;
    }
}
