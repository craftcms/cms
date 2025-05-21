<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\linktypes;

use Craft;
use craft\elements\Entry as EntryElement;
use craft\helpers\Cp;
use craft\models\Section;
use Illuminate\Support\Collection;

/**
 * Entry link type.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
class Entry extends BaseElementLinkType
{
    /**
     * @var bool Whether to show input sources for sections the user doesn’t have permission to view
     * @since 5.7.0
     */
    public bool $showUnpermittedSections = false;

    /**
     * @var bool Whether to show entries the user doesn’t have permission to view,
     * per the “View other users’ entries” permission.
     * @since 5.7.0
     */
    public bool $showUnpermittedEntries = false;

    protected static function elementType(): string
    {
        return EntryElement::class;
    }

    public function __construct(array $config = [])
    {
        // Default showUnpermittedSections and showUnpermittedEntries to true for existing Entries fields
        if (!empty($config) && !isset($config['showUnpermittedSections'])) {
            $config['showUnpermittedSections'] = true;
            $config['showUnpermittedEntries'] = true;
        }

        parent::__construct($config);
    }

    public function getSettingsHtml(): ?string
    {
        return
            parent::getSettingsHtml() .
            Cp::lightswitchFieldHtml([
                'label' => Craft::t('app', 'Show unpermitted sections'),
                'instructions' => Craft::t('app', 'Whether to show sections that the user doesn’t have permission to view.'),
                'id' => 'showUnpermittedSections',
                'name' => 'showUnpermittedSections',
                'on' => $this->showUnpermittedSections,
            ]) .
            Cp::lightswitchFieldHtml([
                'label' => Craft::t('app', 'Show unpermitted entries'),
                'instructions' => Craft::t('app', 'Whether to show entries that the user doesn’t have permission to view, per the “View other users’ entries” permission.'),
                'id' => 'showUnpermittedEntries',
                'name' => 'showUnpermittedEntries',
                'on' => $this->showUnpermittedEntries,
            ]);
    }

    protected function availableSourceKeys(): array
    {
        $sources = [];
        $sections = Craft::$app->getEntries()->getAllSections();
        $sites = Craft::$app->getSites()->getAllSites();
        $showSingles = false;

        foreach ($sections as $section) {
            if ($section->type === Section::TYPE_SINGLE) {
                $showSingles = true;
            } else {
                $sectionSiteSettings = $section->getSiteSettings();
                foreach ($sites as $site) {
                    if (isset($sectionSiteSettings[$site->id]) && $sectionSiteSettings[$site->id]->hasUrls) {
                        $sources[] = "section:$section->uid";
                        break;
                    }
                }
            }
        }

        $sources = array_values(array_unique($sources));

        if ($showSingles) {
            array_unshift($sources, 'singles');
        }

        if (!empty($sources)) {
            array_unshift($sources, '*');
        }

        return $sources;
    }

    protected function selectionCriteria(): array
    {
        $criteria = [];

        if (!$this->showUnpermittedEntries) {
            $criteria['editable'] = true;
        }

        return $criteria;
    }

    /**
     * @inheritdoc
     */
    protected function elementSelectConfig(): array
    {
        $config = parent::elementSelectConfig();

        if (!$this->showUnpermittedSections) {
            $sourceKeys = $this->sources ?? Collection::make($this->availableSources())
                ->map(fn(array $source) => $source['key'])
                ->all();
            $userService = Craft::$app->getUser();
            $config['sources'] = Collection::make((array)$sourceKeys)
                ->filter(function(string $source) use ($userService) {
                    // If it’s not a section, let it through
                    if (!str_starts_with($source, 'section:')) {
                        return true;
                    }
                    // Only show it if they have permission to view it
                    $sectionUid = explode(':', $source)[1];
                    return $userService->checkPermission("viewEntries:$sectionUid");
                })
                ->all();
        }

        return $config;
    }
}
