<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\elements\Entry;
use craft\enums\CmsEdition;
use craft\models\Section_SiteSettings;
use Illuminate\Support\Collection;

/**
 * m240302_212719_solo_preview_targets migration.
 */
class m240302_212719_solo_preview_targets extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (Craft::$app->edition === CmsEdition::Solo) {
            // Add a {url} preview target to all sections that have a URI format
            $entriesService = Craft::$app->getEntries();
            foreach ($entriesService->getAllSections() as $section) {
                if (Collection::make($section->getSiteSettings())->contains(
                    fn(Section_SiteSettings $siteSettings) => $siteSettings->hasUrls,
                )) {
                    $section->previewTargets = [
                        [
                            'label' => Craft::t('app', 'Primary {type} page', [
                                'type' => Entry::lowerDisplayName(),
                            ]),
                            'urlFormat' => '{url}',
                        ],
                    ];
                    $entriesService->saveSection($section);
                }
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m240302_212719_solo_preview_targets cannot be reverted.\n";
        return false;
    }
}
