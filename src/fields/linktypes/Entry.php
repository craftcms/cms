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
use craft\services\ElementSources;
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
        // find the sections that don't have a URL format in any site
        $sections = Craft::$app->getEntries()->getAllSections();
        $sites = Craft::$app->getSites()->getAllSites();
        $excludeKeys = [];

        foreach ($sections as $section) {
            if ($section->type !== Section::TYPE_SINGLE) {
                $sectionSiteSettings = $section->getSiteSettings();
                foreach ($sites as $site) {
                    if (isset($sectionSiteSettings[$site->id]) && $sectionSiteSettings[$site->id]->hasUrls) {
                        continue 2;
                    }
                }
                // exclude it
                $excludeKeys["section:$section->uid"] = true;
            }
        }

        // Get all the native source keys, excluding URL-less sections
        $sources = Collection::make(Craft::$app->getElementSources()->getSources(static::elementType(), ElementSources::CONTEXT_FIELD))
            ->filter(fn($s) => (
                $s['type'] === ElementSources::TYPE_NATIVE &&
                !isset($excludeKeys[$s['key']])
            ))
            ->pluck('key')
            ->all();

        // if we have sources, but not the all ('*') option - add it
        if (!empty($sources) && !in_array('*', $sources)) {
            array_unshift($sources, '*');
        }

        return array_values(array_unique($sources));
    }

    protected function selectionCriteria(): array
    {
        $criteria = parent::selectionCriteria();

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
            // get all the native & custom sources that user has permissions to view
            $permittedSources = Collection::make(Craft::$app->getElementSources()->getSources(EntryElement::class))
                ->filter(fn($source) => $source['type'] !== ElementSources::TYPE_HEADING)
                ->pluck('key')
                ->flip()
                ->all();

            $sourceKeys = $this->sources ?? Collection::make($this->availableSources())
                ->map(fn(array $source) => $source['key'])
                ->all();

            $config['sources'] = Collection::make((array)$sourceKeys)
                ->filter(fn(string $sourceKey) => isset($permittedSources[$sourceKey]))
                ->all();
        }

        return $config;
    }
}
