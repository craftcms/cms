<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\ProjectConfig;
use craft\services\Fields;
use craft\services\Matrix;
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

        // Don't make the same config changes twice
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);
        if (version_compare($schemaVersion, '3.4.2', '>=')) {
            return;
        }

        // Pack the settings to protect them from being sorted, in case the order matters
        $projectConfig->muteEvents = true;

        // Fields
        $fields = $projectConfig->get(Fields::CONFIG_FIELDS_KEY);

        if (!empty($fields)) {
            foreach ($fields as &$field) {
                $field['settings'] = ProjectConfig::packAssociativeArrays($field['settings'] ?? []);
            }

            $projectConfig->set(Fields::CONFIG_FIELDS_KEY, $fields);
        }

        // Matrix fields
        $this->_updateNestedFields(Matrix::CONFIG_BLOCKTYPE_KEY, $projectConfig);

        // Supertable fields
        $this->_updateNestedFields('superTableBlockTypes', $projectConfig);

        // Neo fields
        $this->_updateNestedFields('neoBlockTypes', $projectConfig);

        // Volumes
        $volumes = $projectConfig->get(Volumes::CONFIG_VOLUME_KEY);

        if (!empty($volumes)) {
            foreach ($volumes as &$volume) {
                $volume['settings'] = ProjectConfig::packAssociativeArrays($volume['settings'] ?? []);
            }

            $projectConfig->set(Volumes::CONFIG_VOLUME_KEY, $volumes);
        }

        // Plugins
        $plugins = $projectConfig->get(Plugins::CONFIG_PLUGINS_KEY);
        if (!empty($plugins)) {
            foreach ($plugins as $plugin) {
                $plugin['settings'] = ProjectConfig::packAssociativeArrays($plugin['settings'] ?? []);
            }

            $projectConfig->set(Plugins::CONFIG_PLUGINS_KEY, $plugins);
        }

        $projectConfig->muteEvents = false;
    }

    /**
     * Update associated arrays in settings for nested field for a given path prefix.
     *
     * @param $pathPrefix
     * @param $projectConfig
     */
    private function _updateNestedFields($pathPrefix, $projectConfig)
    {
        $blockTypes = $projectConfig->get($pathPrefix) ?? [];

        foreach ($blockTypes as $blockTypeUid => $blockType) {
            $fields = $blockType['fields'] ?? [];

            foreach ($fields as &$field) {
                $field['settings'] = ProjectConfig::packAssociativeArrays($field['settings'] ?? []);
            }

            $projectConfig->set($pathPrefix . '.' . $blockTypeUid . '.fields', $fields);
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
