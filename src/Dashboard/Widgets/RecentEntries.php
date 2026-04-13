<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Widgets;

use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InternalAssets;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Json;
use Override;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

class RecentEntries extends Widget
{
    #[Override]
    public static function displayName(): string
    {
        return t('Recent Entries');
    }

    #[Override]
    public static function icon(): string
    {
        return 'clock';
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

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this->siteId ??= Sites::getCurrentSite()->id;
    }

    #[Override]
    public function getRules(): array
    {
        return [
            'siteId' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer'],
        ];
    }

    #[Override]
    public function getSettingsHtml(): string
    {
        return template('_components/widgets/RecentEntries/settings',
            [
                'widget' => $this,
            ]);
    }

    #[Override]
    public function getTitle(): string
    {
        if (is_numeric($this->section) && $section = Sections::getSectionById((int) $this->section)) {
            $title = t('Recent {section} Entries', [
                'section' => t($section->name, category: 'site'),
            ]);
        }

        /** @noinspection UnSafeIsSetOverArrayInspection - FP */
        if (! isset($title)) {
            $title = t('Recent Entries');
        }

        // See if they are pulling entries from a different site
        $targetSiteId = $this->getTargetSiteId();

        if ($targetSiteId !== null && $targetSiteId !== Sites::getCurrentSite()->id) {
            $site = Sites::getSiteById($targetSiteId);

            if ($site) {
                $title = t('{title} ({site})', [
                    'title' => $title,
                    'site' => t($site->getName(), category: 'site'),
                ]);
            }
        }

        return $title;
    }

    #[Override]
    public function getBodyHtml(): string
    {
        $params = [];

        if (is_numeric($this->section)) {
            $params['sectionId'] = (int) $this->section;
        }

        InternalAssets::register('recent-entries');
        $js = 'new Craft.RecentEntriesWidget('.$this->id.', '.Json::encode($params).');';
        HtmlStack::js($js);

        $entries = $this->getEntries();

        return template('_components/widgets/RecentEntries/body', [
            'entries' => $entries->all(),
        ]);
    }

    /**
     * Returns the recent entries, based on the widget settings and user permissions.
     */
    private function getEntries(): ElementCollection
    {
        $targetSiteId = $this->getTargetSiteId();

        if ($targetSiteId === null) {
            // Hopeless
            return new ElementCollection;
        }

        // Normalize the target section ID value.
        $editableSectionIds = $this->getEditableSectionIds();
        $targetSectionId = $this->section;

        if (! $targetSectionId || $targetSectionId === '*' || ! in_array($targetSectionId, $editableSectionIds)) {
            $targetSectionId = array_merge($editableSectionIds);
        }

        if (! $targetSectionId) {
            return new ElementCollection;
        }

        return Entry::find()
            ->sectionId($targetSectionId)
            ->editable()
            ->status(null)
            ->siteId($targetSiteId)
            ->limit($this->limit ?: 100)
            ->with(['author'])
            ->orderByDesc('dateCreated')
            ->get();
    }

    /**
     * Returns the Channel and Structure section IDs that the user is allowed to edit.
     */
    private function getEditableSectionIds(): array
    {
        $sectionIds = [];

        foreach (Sections::getEditableSections() as $section) {
            if ($section->type !== SectionType::Single) {
                $sectionIds[] = $section->id;
            }
        }

        return $sectionIds;
    }

    /**
     * Returns the target site ID for the widget.
     */
    private function getTargetSiteId(): ?int
    {
        if (! Sites::isMultiSite()) {
            return $this->siteId;
        }

        // Make sure that the user is actually allowed to edit entries in the current site. Otherwise grab entries in
        // their first editable site.

        // Figure out which sites the user is actually allowed to edit
        $editableSiteIds = Sites::getEditableSiteIds();

        // If they aren't allowed to edit *any* sites, return false
        if ($editableSiteIds->isEmpty()) {
            return null;
        }

        // Figure out which site was selected in the settings
        $targetSiteId = $this->siteId;

        // Only use that site if it still exists and they're allowed to edit it.
        // Otherwise go with the first site that they are allowed to edit.
        if ($editableSiteIds->doesntContain($targetSiteId)) {
            return $editableSiteIds[0];
        }

        return $targetSiteId;
    }
}
