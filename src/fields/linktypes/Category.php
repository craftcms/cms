<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\linktypes;

use Craft;
use craft\elements\Category as CategoryElement;
use craft\helpers\Cp;
use craft\models\CategoryGroup;
use Illuminate\Support\Collection;

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
        return Collection::make(Craft::$app->getCategories()->getAllGroups())
            ->filter(fn(CategoryGroup $group) => $group->getSiteSettings()[Cp::requestedSite()->id]?->hasUrls ?? false)
            ->map(fn(CategoryGroup $group) => "group:$group->uid")
            ->values()
            ->all();
    }
}
