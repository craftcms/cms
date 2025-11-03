<?php

declare(strict_types=1);

namespace craft\fields\linktypes;

use Craft;
use craft\elements\Category as CategoryElement;
use CraftCms\Cms\Support\Facades\Sites;

/**
 * Category link type.
 * @deprecated in 6.0.0
 */
final class Category extends \CraftCms\Cms\Field\LinkTypes\BaseElementLinkType
{
    protected static function elementType(): string
    {
        return CategoryElement::class;
    }

    #[\Override]
    protected function availableSourceKeys(): array
    {
        $sources = [];
        $groups = Craft::$app->getCategories()->getAllGroups();
        $sites = Sites::getAllSites();

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

        if (!empty($sources)) {
            array_unshift($sources, '*');
        }

        return $sources;
    }
}
