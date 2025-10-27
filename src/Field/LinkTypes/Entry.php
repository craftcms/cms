<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\LinkTypes;

use Craft;
use craft\elements\Entry as EntryElement;
use craft\helpers\Cp;
use craft\services\ElementSources;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Support\Collection;

use function CraftCms\Cms\t;

/**
 * Entry link type.
 */
final class Entry extends BaseElementLinkType
{
    /**
     * @var bool Whether to show input sources for sections the user doesn’t have permission to view
     *
     * @since 5.7.0
     */
    public bool $showUnpermittedSections = false;

    /**
     * @var bool Whether to show entries the user doesn’t have permission to view,
     *           per the “View other users’ entries” permission.
     *
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
        if (! empty($config) && ! isset($config['showUnpermittedSections'])) {
            $config['showUnpermittedSections'] = true;
            $config['showUnpermittedEntries'] = true;
        }

        parent::__construct($config);
    }

    #[\Override]
    public function getSettingsHtml(): string
    {
        return
            parent::getSettingsHtml().
            Cp::lightswitchFieldHtml([
                'label' => t('Show unpermitted sections'),
                'instructions' => t('Whether to show sections that the user doesn’t have permission to view.'),
                'id' => 'showUnpermittedSections',
                'name' => 'showUnpermittedSections',
                'on' => $this->showUnpermittedSections,
            ]).
            Cp::lightswitchFieldHtml([
                'label' => t('Show unpermitted entries'),
                'instructions' => t('Whether to show entries that the user doesn’t have permission to view, per the “View other users’ entries” permission.'),
                'id' => 'showUnpermittedEntries',
                'name' => 'showUnpermittedEntries',
                'on' => $this->showUnpermittedEntries,
            ]);
    }

    #[\Override]
    protected function availableSourceKeys(): array
    {
        // find the sections that don't have a URL format in any site
        $sections = Sections::getAllSections();
        $sites = Sites::getAllSites();
        $excludeKeys = [];

        foreach ($sections as $section) {
            if ($section->type !== SectionType::Single) {
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
        $sources = Collection::make(Craft::$app->getElementSources()->getSources(self::elementType(), ElementSources::CONTEXT_FIELD))
            ->filter(fn ($s) => (
                $s['type'] === ElementSources::TYPE_NATIVE &&
                ! isset($excludeKeys[$s['key']])
            ))
            ->pluck('key')
            ->all();

        // if we have sources, but not the all ('*') option - add it
        if (! empty($sources) && ! in_array('*', $sources)) {
            array_unshift($sources, '*');
        }

        return array_values(array_unique($sources));
    }

    #[\Override]
    protected function selectionCriteria(): array
    {
        $criteria = parent::selectionCriteria();

        if (! $this->showUnpermittedEntries) {
            $criteria['editable'] = true;
        }

        return $criteria;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function elementSelectConfig(): array
    {
        $config = parent::elementSelectConfig();

        if (! $this->showUnpermittedSections) {
            // get all the native & custom sources that user has permissions to view
            $permittedSources = Collection::make(Craft::$app->getElementSources()->getSources(EntryElement::class))
                ->filter(fn ($source) => $source['type'] !== ElementSources::TYPE_HEADING)
                ->pluck('key')
                ->flip()
                ->all();

            $sourceKeys = $this->sources ?? Collection::make($this->availableSources())
                ->map(fn (array $source) => $source['key'])
                ->all();

            $config['sources'] = Collection::make((array) $sourceKeys)
                ->filter(fn (string $sourceKey) => isset($permittedSources[$sourceKey]))
                ->all();
        }

        return $config;
    }
}
