<?php

namespace craft\migrations;

use Craft;
use craft\base\ElementInterface;
use craft\db\Migration;
use craft\db\Query;
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
                /** @var string|ElementInterface $elementType */
                $nativeSources = $elementType::sources('index');
                $settings = Json::decode($settings);
                $sourceConfigs = [];
                $indexedSourceConfigs = [];

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
                                $sourceConfigs[] = $indexedSourceConfigs[$value] = [
                                    'type' => ElementSources::TYPE_NATIVE,
                                    'key' => $value,
                                ];
                                break;
                        }
                    }

                    // Make sure all native sources are accounted for
                    $missingSources = array_filter($nativeSources, fn($s) => isset($s['key']) && !isset($indexedSourceConfigs[$s['key']]));
                    if (!empty($missingSources)) {
                        if (!empty($sourceConfigs)) {
                            $sourceConfigs[] = [
                                'type' => ElementSources::TYPE_HEADING,
                                'heading' => '',
                            ];
                        }
                        foreach ($missingSources as $source) {
                            $sourceConfigs[] = $indexedSourceConfigs[$source['key']] = [
                                'type' => ElementSources::TYPE_NATIVE,
                                'key' => $source['key'],
                            ];
                        }
                    }
                } else {
                    foreach ($nativeSources as $source) {
                        if (array_key_exists('heading', $source)) {
                            $sourceConfigs[] = [
                                'type' => ElementSources::TYPE_HEADING,
                                'heading' => $source['heading'],
                            ];
                        } else if (isset($source['key'])) {
                            $sourceConfigs[] = $indexedSourceConfigs[$source['key']] = [
                                'type' => ElementSources::TYPE_NATIVE,
                                'key' => $source['key'],
                            ];
                        }
                    }
                }

                // Merge in any custom table attribute selections
                if (!empty($settings['sources'])) {
                    foreach ($settings['sources'] as $key => $sourceSettings) {
                        if (isset($indexedSourceConfigs[$key]) && !empty($sourceSettings['tableAttributes'])) {
                            $indexedSourceConfigs[$key]['tableAttributes'] = array_values($sourceSettings['tableAttributes']);
                        }
                    }
                }

                $newSettings[$elementType] = $sourceConfigs;
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
