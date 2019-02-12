<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\fields\Assets;
use craft\services\Categories;
use craft\services\Fields;
use craft\services\Matrix;
use craft\services\Sections;
use craft\services\Sites;

/**
 * m190110_150000_prune_nonexisting_sites migration.
 */
class m190110_150000_prune_nonexisting_sites extends Migration
{
    /**
     * @var array List of volume UIDs keyed by folder UIds.
     */
    private $_volumesByFolderUids = [];

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $projectConfig->muteEvents = true;

        // Get the site data from the project config
        $sites = $projectConfig->get(Sites::CONFIG_SITES_KEY) ?? [];
        $existingSites = array_keys($sites);

        // Prune non-existing sites from sections
        $sections = $projectConfig->get(Sections::CONFIG_SECTIONS_KEY) ?? [];
        foreach ($sections as $sectionUid => $section) {
            if (!empty($section['siteSettings'])) {
                foreach ($section['siteSettings'] as $siteUid => $siteSettings) {
                    if (!in_array($siteUid, $existingSites, false)) {
                        $projectConfig->remove(Sections::CONFIG_SECTIONS_KEY . '.' . $sectionUid . '.siteSettings.' . $siteUid);
                    }
                }
            }
        }

        // Prune non-existing sites from categroy groups
        $categories = $projectConfig->get(Categories::CONFIG_CATEGORYROUP_KEY) ?? [];
        foreach ($categories as $categoryUid => $category) {
            if (!empty($category['siteSettings'])) {
                foreach ($category['siteSettings'] as $siteUid => $siteSettings) {
                    if (!in_array($siteUid, $existingSites, false)) {
                        $projectConfig->remove(Categories::CONFIG_CATEGORYROUP_KEY . '.' . $categoryUid . '.siteSettings.' . $siteUid);
                    }
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
        echo "m190110_150000_prune_nonexisting_sites cannot be reverted.\n";
        return false;
    }
}
