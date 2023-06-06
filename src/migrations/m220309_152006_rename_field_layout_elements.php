<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\fieldlayoutelements\assets\AltField;
use craft\fieldlayoutelements\assets\AssetTitleField;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;
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
            $projectConfig->muteEvents = true;

            $this->_updateFieldLayouts($projectConfig, ProjectConfig::PATH_VOLUMES, [
                'craft\fieldlayoutelements\AssetTitleField' => AssetTitleField::class,
                'craft\fieldlayoutelements\AssetAltField' => AltField::class,
            ]);

            $this->_updateFieldLayouts($projectConfig, ProjectConfig::PATH_ENTRY_TYPES, [
                'craft\fieldlayoutelements\EntryTitleField' => EntryTitleField::class,
            ]);

            $projectConfig->muteEvents = false;
        }

        return true;
    }

    private function _updateFieldLayouts(ProjectConfig $projectConfig, string $basePath, array $map): void
    {
        $baseConfigs = $projectConfig->get($basePath) ?? [];

        foreach ($baseConfigs as $uid => $baseConfig) {
            if (isset($baseConfig['fieldLayouts'])) {
                $fieldLayoutConfigs = &$baseConfig['fieldLayouts'];
                $anyModified = false;

                foreach ($fieldLayoutConfigs as $fieldLayoutUid => &$fieldLayoutConfig) {
                    $modified = false;

                    if (isset($fieldLayoutConfig['tabs'])) {
                        foreach ($fieldLayoutConfig['tabs'] as &$tabConfig) {
                            if (isset($tabConfig['elements'])) {
                                foreach ($tabConfig['elements'] as &$elementConfig) {
                                    if (isset($elementConfig['type'])) {
                                        foreach ($map as $from => $to) {
                                            if ($elementConfig['type'] === $from) {
                                                $elementConfig['type'] = $to;
                                                $anyModified = $modified = true;
                                            }
                                        }
                                    }
                                }
                                unset($elementConfig);
                            }
                        }
                        unset($tabConfig);
                    }

                    if ($modified) {
                        $fieldLayoutId = Db::idByUid(Table::FIELDLAYOUTS, $fieldLayoutUid);
                        if ($fieldLayoutId) {
                            $this->delete('{{%fieldlayouttabs}}', [
                                'layoutId' => $fieldLayoutId,
                            ]);
                            foreach ($fieldLayoutConfig['tabs'] as $sortOrder => $tabConfig) {
                                $tabName = $tabConfig['name'] ?? 'Content';
                                if (!$this->db->getSupportsMb4()) {
                                    $tabName = StringHelper::encodeMb4($tabName);
                                }
                                Db::insert('{{%fieldlayouttabs}}', [
                                    'layoutId' => $fieldLayoutId,
                                    'name' => $tabName,
                                    'sortOrder' => $tabConfig['sortOrder'] ?? $sortOrder,
                                    'settings' => Json::encode([
                                        'userCondition' => $tabConfig['userCondition'] ?? null,
                                        'elementCondition' => $tabConfig['elementCondition'] ?? null,
                                    ]),
                                    'elements' => Json::encode($tabConfig['elements']),
                                    'uid' => $tabConfig['uid'] ?? StringHelper::UUID(),
                                ]);
                            }
                        }
                    }
                }
                unset($fieldLayoutConfig);

                if ($anyModified) {
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
