<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\widgets;

use Craft;
use craft\base\Widget;
use craft\elements\Entry;
use craft\helpers\Json;
use craft\models\Section;
use craft\web\assets\recententries\RecentEntriesAsset;

/**
 * RecentEntries represents a Recent Entries dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class RecentEntries extends Widget
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Recent Entries');
    }

    /**
     * @inheritdoc
     */
    public static function icon(): ?string
    {
        return Craft::getAlias('@appicons/clock.svg');
    }

    /**
     * @var int|null The site ID that the widget should pull entries from
     */
    public ?int $siteId = null;

    /**
     * @var string|int[] The section IDs that the widget should pull entries from
     */
    public string|array $section = '*';

    /**
     * @var int The total number of entries that the widget should show
     */
    public int $limit = 10;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // Config normalization
        if (empty($config['siteId'])) {
            $config['siteId'] = Craft::$app->getSites()->getCurrentSite()->id;
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['siteId', 'limit'], 'number', 'integerOnly' => true];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('_components/widgets/RecentEntries/settings.twig',
            [
                'widget' => $this,
            ]);
    }

    /**
     * @inheritdoc
     */
    public function getTitle(): ?string
    {
        if (is_numeric($this->section)) {
            $section = Craft::$app->getSections()->getSectionById((int)$this->section);

            if ($section) {
                $title = Craft::t('app', 'Recent {section} Entries', [
                    'section' => Craft::t('site', $section->name),
                ]);
            }
        }

        /** @noinspection UnSafeIsSetOverArrayInspection - FP */
        if (!isset($title)) {
            $title = Craft::t('app', 'Recent Entries');
        }

        // See if they are pulling entries from a different site
        $targetSiteId = $this->_getTargetSiteId();

        if ($targetSiteId !== null && $targetSiteId != Craft::$app->getSites()->getCurrentSite()->id) {
            $site = Craft::$app->getSites()->getSiteById($targetSiteId);

            if ($site) {
                $title = Craft::t('app', '{title} ({site})', [
                    'title' => $title,
                    'site' => Craft::t('site', $site->getName()),
                ]);
            }
        }

        return $title;
    }

    /**
     * @inheritdoc
     */
    public function getBodyHtml(): ?string
    {
        $params = [];

        if (is_numeric($this->section)) {
            $params['sectionId'] = (int)$this->section;
        }

        $view = Craft::$app->getView();

        $view->registerAssetBundle(RecentEntriesAsset::class);
        $js = 'new Craft.RecentEntriesWidget(' . $this->id . ', ' . Json::encode($params) . ');';
        $view->registerJs($js);

        $entries = $this->_getEntries();

        return $view->renderTemplate('_components/widgets/RecentEntries/body.twig',
            [
                'entries' => $entries,
            ]);
    }

    /**
     * Returns the recent entries, based on the widget settings and user permissions.
     *
     * @return Entry[]
     */
    private function _getEntries(): array
    {
        $targetSiteId = $this->_getTargetSiteId();

        if ($targetSiteId === null) {
            // Hopeless
            return [];
        }

        // Normalize the target section ID value.
        $editableSectionIds = $this->_getEditableSectionIds();
        $targetSectionId = $this->section;

        if (!$targetSectionId || $targetSectionId === '*' || !in_array($targetSectionId, $editableSectionIds, false)) {
            $targetSectionId = array_merge($editableSectionIds);
        }

        if (!$targetSectionId) {
            return [];
        }

        /** @var Entry[] */
        return Entry::find()
            ->sectionId($targetSectionId)
            ->editable(true)
            ->status(null)
            ->siteId($targetSiteId)
            ->limit($this->limit ?: 100)
            ->with(['author'])
            ->orderBy('elements.dateCreated desc')
            ->all();
    }

    /**
     * Returns the Channel and Structure section IDs that the user is allowed to edit.
     *
     * @return array
     */
    private function _getEditableSectionIds(): array
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
     * @return int|null
     */
    private function _getTargetSiteId(): int|null
    {
        if (!Craft::$app->getIsMultiSite()) {
            return $this->siteId;
        }

        // Make sure that the user is actually allowed to edit entries in the current site. Otherwise grab entries in
        // their first editable site.

        // Figure out which sites the user is actually allowed to edit
        $editableSiteIds = Craft::$app->getSites()->getEditableSiteIds();

        // If they aren't allowed to edit *any* sites, return false
        if (empty($editableSiteIds)) {
            return null;
        }

        // Figure out which site was selected in the settings
        $targetSiteId = $this->siteId;

        // Only use that site if it still exists and they're allowed to edit it.
        // Otherwise go with the first site that they are allowed to edit.
        if (!in_array($targetSiteId, $editableSiteIds, false)) {
            $targetSiteId = $editableSiteIds[0];
        }

        return $targetSiteId;
    }
}
