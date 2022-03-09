<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\fieldlayoutelements\assets\AltField;
use craft\fieldlayoutelements\assets\AssetTitleField;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\services\ProjectConfig;

/**
 * m220309_152006_rename_field_layout_elements migration.
 */
class m220309_152006_rename_field_layout_elements extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);

        if (version_compare($schemaVersion, '4.0.0.4', '<')) {
            $this->_updateFieldLayouts($projectConfig, ProjectConfig::PATH_VOLUMES, [
                'craft\fieldlayoutelements\AssetTitleField' => AssetTitleField::class,
                'craft\fieldlayoutelements\AssetAltField' => AltField::class,
            ]);

            $this->_updateFieldLayouts($projectConfig, ProjectConfig::PATH_ENTRY_TYPES, [
                'craft\fieldlayoutelements\EntryTitleField' => EntryTitleField::class,
            ]);
        }

        return true;
    }

    private function _updateFieldLayouts(ProjectConfig $projectConfig, string $basePath, array $map): void
    {
        $baseConfigs = $projectConfig->get($basePath) ?? [];

        foreach ($baseConfigs as $uid => $baseConfig) {
            if (isset($baseConfig['fieldLayouts'])) {
                $fieldLayoutConfigs = &$baseConfig['fieldLayouts'];
                $modified = false;

                foreach ($fieldLayoutConfigs as &$fieldLayoutConfig) {
                    if (isset($fieldLayoutConfig['tabs'])) {
                        foreach ($fieldLayoutConfig['tabs'] as &$tabConfig) {
                            if (isset($tabConfig['elements'])) {
                                foreach ($tabConfig['elements'] as &$elementConfig) {
                                    if (isset($elementConfig['type'])) {
                                        foreach ($map as $from => $to) {
                                            if ($elementConfig['type'] === $from) {
                                                $elementConfig['type'] = $to;
                                                $modified = true;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                if ($modified) {
                    $projectConfig->set("$basePath.$uid.fieldLayouts", $fieldLayoutConfigs);
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m220309_152006_rename_field_layout_elements cannot be reverted.\n";
        return false;
    }
}
