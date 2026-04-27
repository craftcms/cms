<?php

declare(strict_types=1);
namespace CraftCms\Yii2Adapter\Mixins;

use Closure;
use Craft;
use craft\elements\Category;
use craft\elements\db\CategoryQuery;
use craft\elements\db\GlobalSetQuery;
use craft\elements\db\TagQuery;
use craft\elements\GlobalSet;
use craft\elements\Tag;
use craft\web\twig\variables\Rebrand;
use CraftCms\Cms\Support\Typecast;

class CraftVariableMixin
{
    public function rebrand(): Closure
    {
        return function() {
            return app()->make(Rebrand::class);
        };
    }

    public function app(): Closure
    {
        return function() {
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
        return function(array $criteria = []) {
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
            $query = Tag::find();
            Typecast::configure($query, $criteria);
            return $query;
        };
    }
}
