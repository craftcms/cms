<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\linktypes;

use Craft;
use craft\elements\Category as CategoryElement;

/**
 * Category link type.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
class Category extends BaseElementLinkType
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

        if (!empty($sources)) {
            array_unshift($sources, '*');
        }

        return $sources;
    }
}
