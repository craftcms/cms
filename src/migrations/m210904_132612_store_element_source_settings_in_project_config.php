<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\services\ElementSources;
use craft\services\ProjectConfig;

/**
 * m210904_132612_store_element_source_settings_in_project_config migration.
 */
class m210904_132612_store_element_source_settings_in_project_config extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);

        if (version_compare($schemaVersion, '4.0.0', '<')) {
            $dbSettings = (new Query())
                ->select(['type', 'settings'])
                ->from(['{{%elementindexsettings}}'])
                ->pairs();
            $newSettings = [];

            foreach ($dbSettings as $elementType => $settings) {
                $settings = Json::decode($settings);
                $sourceConfigs = [];

                if (!empty($settings['sourceOrder'])) {
                    foreach ($settings['sourceOrder'] as [$sourceType, $value]) {
                        switch ($sourceType) {
                            case 'heading':
                                $sourceConfigs[] = [
                                    'type' => ElementSources::TYPE_HEADING,
                                    'heading' => $value,
                                ];
                                break;
                            case 'key':
                                $sourceConfigs[$value] = [
                                    'type' => ElementSources::TYPE_NATIVE,
                                    'key' => $value,
                                ];
                                break;
                        }
                    }
                }

                // Merge in any custom table attribute selections
                if (!empty($settings['sources'])) {
                    foreach ($settings['sources'] as $key => $sourceSettings) {
                        if (isset($sourceConfigs[$key]) && !empty($sourceSettings['tableAttributes'])) {
                            $tableAttributes = [];
                            foreach ($sourceSettings['tableAttributes'] as $attribute) {
                                // field:id => field:uid
                                if (preg_match('/^field:(\d+)$/', $attribute, $matches)) {
                                    $fieldUid = Db::uidById(Table::FIELDS, (int)$matches[1]);
                                    if ($fieldUid) {
                                        $tableAttributes[] = "field:$fieldUid";
                                    }
                                } else {
                                    $tableAttributes[] = $attribute;
                                }
                            }

                            $sourceConfigs[$key]['tableAttributes'] = $tableAttributes;
                        }
                    }
                }

                $newSettings[$elementType] = array_values($sourceConfigs);
            }

            $projectConfig->set(ProjectConfig::PATH_ELEMENT_SOURCES, $newSettings);
        }

        $this->dropTable('{{%elementindexsettings}}');

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m210904_132612_store_element_source_settings_in_project_config cannot be reverted.\n";
        return false;
    }
}
