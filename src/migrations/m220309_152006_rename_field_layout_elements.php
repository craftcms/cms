<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\fieldlayoutelements\assets\AltField;
use craft\fieldlayoutelements\assets\AssetTitleField;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\records\FieldLayoutTab as FieldLayoutTabRecord;
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
                            $this->delete(Table::FIELDLAYOUTTABS, [
                                'layoutId' => $fieldLayoutId,
                            ]);
                            foreach ($fieldLayoutConfig['tabs'] as $sortOrder => $tabConfig) {
                                $tabRecord = new FieldLayoutTabRecord();
                                $tabRecord->layoutId = $fieldLayoutId;
                                $tabRecord->uid = $tabConfig['uid'] ?? StringHelper::UUID();
                                $tabRecord->sortOrder = $tabConfig['sortOrder'] ?? $sortOrder;
                                $tabName = $tabConfig['name'] ?? 'Content';
                                if ($this->db->getIsMysql()) {
                                    $tabRecord->name = StringHelper::encodeMb4($tabName);
                                } else {
                                    $tabRecord->name = $tabName;
                                }
                                $tabRecord->settings = [
                                    'userCondition' => $tabConfig['userCondition'] ?? null,
                                    'elementCondition' => $tabConfig['elementCondition'] ?? null,
                                ];
                                $tabRecord->elements = $tabConfig['elements'];
                                $tabRecord->save();
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
