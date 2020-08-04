<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\fieldlayoutelements\EntryTitleField;
use craft\models\FieldLayoutTab;
use craft\services\ProjectConfig;

/**
 * m200620_230205_field_layout_changes migration.
 */
class m200620_230205_field_layout_changes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(Table::FIELDLAYOUTTABS, 'elements', $this->text()->after('name'));

        $this->dropColumn(Table::ENTRYTYPES, 'titleLabel');
        if ($this->db->columnExists(Table::ENTRYTYPES, 'titleInstructions')) {
            $this->dropColumn(Table::ENTRYTYPES, 'titleInstructions');
        }

        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);
        if (version_compare($schemaVersion, '3.5.8', '>=')) {
            return;
        }

        $this->_updateEntryTypeFieldLayouts($projectConfig);
    }

    /**
     * Adds a Title field to all entry type field layouts, configuring them with the prior custom title label & instructions.
     *
     * @param ProjectConfig $projectConfig
     */
    private function _updateEntryTypeFieldLayouts(ProjectConfig $projectConfig)
    {
        foreach ($projectConfig->get('sections') ?? [] as $sectionUid => $sectionConfig) {
            if (empty($sectionConfig['entryTypes'])) {
                continue;
            }

            foreach ($sectionConfig['entryTypes'] as $entryTypeUid => $entryTypeConfig) {
                if (empty($entryTypeConfig['fieldLayouts'])) {
                    continue;
                }

                foreach ($entryTypeConfig['fieldLayouts'] as $fieldLayoutUid => &$fieldLayoutConfig) {
                    // Make sure there's at least one tab
                    if (empty($fieldLayoutConfig['tabs'])) {
                        $fieldLayoutConfig['tabs'] = [
                            'name' => 'Content',
                            'sortOrder' => 1,
                        ];
                    }

                    // Update the tab configs to the new format
                    foreach ($fieldLayoutConfig['tabs'] as &$tabConfig) {
                        FieldLayoutTab::updateConfig($tabConfig);
                    }

                    // Add the Title field to the first tab
                    $firstTab = &$fieldLayoutConfig['tabs'][0];
                    if (!isset($firstTab['elements'])) {
                        $firstTab['elements'] = [];
                    }
                    array_unshift($firstTab['elements'], [
                        'type' => EntryTitleField::class,
                        'label' => $entryTypeConfig['titleLabel'] ?? null,
                        'instructions' => $entryTypeConfig['titleInstructions'] ?? null,
                    ]);
                }

                unset(
                    $entryTypeConfig['titleLabel'],
                    $entryTypeConfig['titleInstructions']
                );

                $projectConfig->set("sections.$sectionUid.entryTypes.$entryTypeUid", $entryTypeConfig);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200620_230205_field_layout_changes cannot be reverted.\n";
        return false;
    }
}
