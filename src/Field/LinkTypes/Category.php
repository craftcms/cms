<?php

namespace CraftCms\Cms\Field\LinkTypes;

use Craft;
use craft\elements\Category as CategoryElement;

/**
 * Category link type.
 *
 * @since 6.0.0
 */
final class Category extends BaseElementLinkType
{
    protected static function elementType(): string
    {
        return CategoryElement::class;
    }

    protected function availableSourceKeys(): array
    {
        $sources = [];
        $groups = Craft::$app->getCategories()->getAllGroups();
        $sites = Craft::$app->getSites()->getAllSites();

        foreach ($groups as $group) {
            $siteSettings = $group->getSiteSettings();
            foreach ($sites as $site) {
                if (isset($siteSettings[$site->id]) && $siteSettings[$site->id]->hasUrls) {
                    $sources[] = "group:$group->uid";
                    break;
                }
            }
        }

        $sources = array_values(array_unique($sources));

        if (! empty($sources)) {
            array_unshift($sources, '*');
        }

        return $sources;
    }
}
