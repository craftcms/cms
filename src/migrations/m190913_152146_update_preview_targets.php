<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\elements\Entry;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;

/**
 * m190913_152146_update_preview_targets migration.
 */
class m190913_152146_update_preview_targets extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);
        if (version_compare($schemaVersion, '3.4.0', '>=')) {
            return;
        }

        $sections = $projectConfig->get('sections') ?? [];

        foreach ($sections as $uid => $section) {
            $dirty = false;

            // Make sure there are no completely blank preview targets
            $previewTargets = $section['previewTargets'] ?? [];
            foreach ($previewTargets as &$previewTarget) {
                if (isset($previewTarget['urlFormat']) && $previewTarget['urlFormat'] === '') {
                    $previewTarget['urlFormat'] = '/';
                    $dirty = true;
                }
            }

            // If the section has URLs (in any site) then add the Primary Page target
            if (!empty($section['siteSettings']) && ArrayHelper::contains($section['siteSettings'], 'hasUrls')) {
                array_unshift($previewTargets, [
                    'label' => Craft::t('app', 'Primary {type} page', [
                        'type' => StringHelper::toLowerCase(Entry::displayName()),
                    ]),
                    'urlFormat' => '{url}',
                ]);
                $dirty = true;
            }

            if ($dirty) {
                $projectConfig->set("sections.{$uid}.previewTargets", $previewTargets);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190913_152146_update_preview_targets cannot be reverted.\n";
        return false;
    }
}
