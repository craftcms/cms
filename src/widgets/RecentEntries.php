<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\widgets;

use Craft;
use craft\app\base\Widget;
use craft\app\elements\Entry;
use craft\app\helpers\Json;
use craft\app\models\Section;

/**
 * RecentEntries represents a Recent Entries dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class RecentEntries extends Widget
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName()
    {
        return Craft::t('app', 'Recent Entries');
    }

    // Properties
    // =========================================================================

    /**
     * @var string|integer[] The section IDs that the widget should pull entries from
     */
    public $section = '*';

    /**
     * string The site ID that the widget should pull entries from
     */
    public $siteId;

    /**
     * integer The total number of entries that the widget should show
     */
    public $limit = 10;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->siteId === null) {
            $this->siteId = Craft::$app->getSites()->currentSite->id;
        }
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('_components/widgets/RecentEntries/settings',
            [
                'widget' => $this
            ]);
    }

    /**
     * @inheritdoc
     */
    public function getIconPath()
    {
        return Craft::$app->getPath()->getResourcesPath().'/images/widgets/recent-entries.svg';
    }

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        if (is_numeric($this->section)) {
            $section = Craft::$app->getSections()->getSectionById($this->section);

            if ($section) {
                $title = Craft::t('app', 'Recent {section} Entries', [
                    'section' => Craft::t('site', $section->name)
                ]);
            }
        }

        if (!isset($title)) {
            $title = Craft::t('app', 'Recent Entries');
        }

        // See if they are pulling entries from a different site
        $targetSiteId = $this->_getTargetSiteId();

        if ($targetSiteId && $targetSiteId != Craft::$app->getSites()->currentSite->id) {
            $site = Craft::$app->getSites()->getSiteById($targetSiteId);

            if ($site) {
                $title = Craft::t('app', '{title} ({site})', [
                    'title' => $title,
                    'site' => Craft::t('site', $site->name),
                ]);
            }
        }

        return $title;
    }

    /**
     * @inheritdoc
     */
    public function getBodyHtml()
    {
        $params = [];

        if (is_numeric($this->section)) {
            $params['sectionId'] = (int)$this->section;
        }

        $js = 'new Craft.RecentEntriesWidget('.$this->id.', '.Json::encode($params).');';

        Craft::$app->getView()->registerJsResource('js/RecentEntriesWidget.js');
        Craft::$app->getView()->registerJs($js);
        Craft::$app->getView()->registerTranslations('app', [
            'by {author}',
        ]);

        $entries = $this->_getEntries();

        return Craft::$app->getView()->renderTemplate('_components/widgets/RecentEntries/body',
            [
                'entries' => $entries
            ]);
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the recent entries, based on the widget settings and user permissions.
     *
     * @return array
     */
    private function _getEntries()
    {
        $targetSiteId = $this->_getTargetSiteId();

        if (!$targetSiteId) {
            // Hopeless
            return [];
        }

        // Normalize the target section ID value.
        $editableSectionIds = $this->_getEditableSectionIds();
        $targetSectionId = $this->section;

        if (!$targetSectionId || $targetSectionId == '*' || !in_array($targetSectionId,
                $editableSectionIds)
        ) {
            $targetSectionId = array_merge($editableSectionIds);
        }

        if (!$targetSectionId) {
            return [];
        }

        return Entry::find()
            ->status(null)
            ->enabledForSite(false)
            ->siteId($targetSiteId)
            ->sectionId($targetSectionId)
            ->editable(true)
            ->limit($this->limit ?: 100)
            ->orderBy('elements.dateCreated desc')
            ->all();
    }

    /**
     * Returns the Channel and Structure section IDs that the user is allowed to edit.
     *
     * @return array
     */
    private function _getEditableSectionIds()
    {
        $sectionIds = [];

        foreach (Craft::$app->getSections()->getEditableSections() as $section) {
            if ($section->type != Section::TYPE_SINGLE) {
                $sectionIds[] = $section->id;
            }
        }

        return $sectionIds;
    }

    /**
     * Returns the target site ID for the widget.
     *
     * @return string|false
     */
    private function _getTargetSiteId()
    {
        // Make sure that the user is actually allowed to edit entries in the current site. Otherwise grab entries in
        // their first editable site.

        // Figure out which sites the user is actually allowed to edit
        $editableSiteIds = Craft::$app->getSites()->getEditableSiteIds();

        // If they aren't allowed to edit *any* sites, return false
        if (!$editableSiteIds) {
            return false;
        }

        // Figure out which site was selected in the settings
        $targetSiteId = $this->siteId;

        // Only use that site if it still exists and they're allowed to edit it.
        // Otherwise go with the first site that they are allowed to edit.
        if (!in_array($targetSiteId, $editableSiteIds)) {
            $targetSiteId = $editableSiteIds[0];
        }

        return $targetSiteId;
    }
}
