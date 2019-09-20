<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\ArrayHelper;

/**
 * m181121_001712_cleanup_field_configs migration.
 */
class m181121_001712_cleanup_field_configs extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $projectConfig = Craft::$app->getProjectConfig();

        // Don't make the same config changes twice
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);
        if (version_compare($schemaVersion, '3.1.7', '>=')) {
            return;
        }

        $projectConfig->muteEvents = true;

        $fieldConfigs = $projectConfig->get('fields') ?? [];

        foreach ($fieldConfigs as $fieldUid => $fieldConfig) {
            $oldConfigPath = 'fields.' . $fieldUid;
            $context = ArrayHelper::remove($fieldConfig, 'context', 'global');

            if ($context === 'global') {
                $projectConfig->set($oldConfigPath, $fieldConfig);
            } else {
                $projectConfig->remove($oldConfigPath);

                if (strpos($context, 'matrixBlockType:') === 0) {
                    $blockTypeUid = substr($context, 16);
                    $newConfigPath = 'matrixBlockTypes.' . $blockTypeUid . '.fields.' . $fieldUid;
                    $projectConfig->set($newConfigPath, $fieldConfig);
                }
            }
        }

        $projectConfig->muteEvents = false;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m181121_001712_cleanup_field_configs cannot be reverted.\n";
        return false;
    }
}
