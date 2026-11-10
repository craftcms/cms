<?php

declare(strict_types=1);
namespace CraftCms\Yii2Adapter\Mixins;

use Closure;
use Craft;
use craft\elements\Category;
use craft\elements\GlobalSet;
use craft\elements\Tag;
use craft\web\twig\variables\Rebrand;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Yii2Adapter\Element\Queries\CategoryQuery;
use CraftCms\Yii2Adapter\Element\Queries\GlobalSetQuery;
use CraftCms\Yii2Adapter\Element\Queries\TagQuery;
use CraftCms\Yii2Adapter\LooseEnds\LegacyUsage;

class CraftVariableMixin
{
    public function rebrand(): Closure
    {
        return function() {
            LegacyUsage::record('craft-variable.rebrand', '`craft.rebrand` is used. Set the `cpIconUrl` and `cpLogoUrl` general config settings instead.');

            return app()->make(Rebrand::class);
        };
    }

    public function app(): Closure
    {
        return function() {
            LegacyUsage::record('craft-variable.app', '`craft.app` is used. Use Craft’s Twig variables and functions, or `app()`, instead.');

            return Craft::$app;
        };
    }

    public function categories(): Closure
    {
        /**
         * Returns a new [category query](https://craftcms.com/docs/5.x/reference/element-types/categories.html#querying-categories).
         *
         * @param array $criteria
         * @return CategoryQuery
         * @deprecated in 6.0.0
         */
        return function(array $criteria = []): CategoryQuery {
            LegacyUsage::record('craft-variable.categories', '`craft.categories` is used. Query entries with `craft.entries` instead.');
            $query = Category::find();
            Typecast::configure($query, $criteria);
            return $query;
        };
    }

    public function globalSets(): Closure
    {
        /**
         * Returns a new [global set query](https://craftcms.com/docs/5.x/reference/element-types/globals.html#querying-globals).
         *
         * @param array $criteria
         * @return GlobalSetQuery
         * @since 3.0.4
         * @deprecated in 6.0.0
         */
        return function(array $criteria = []): GlobalSetQuery {
            LegacyUsage::record('craft-variable.globalSets', '`craft.globalSets` is used. Query entries with `craft.entries` instead.');
            $query = GlobalSet::find();
            Typecast::configure($query, $criteria);
            return $query;
        };
    }

    public function tags(): Closure
    {
        /**
         * Returns a new [tag query](https://craftcms.com/docs/5.x/reference/element-types/tags.html#querying-tags).
         *
         * @param array $criteria
         * @return TagQuery
         * @deprecated in 6.0.0
         */
        return function(array $criteria = []): TagQuery {
            LegacyUsage::record('craft-variable.tags', '`craft.tags` is used. Query entries with `craft.entries` instead.');
            $query = Tag::find();
            Typecast::configure($query, $criteria);
            return $query;
        };
    }
}
