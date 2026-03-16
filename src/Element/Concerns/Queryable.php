<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use Craft;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Typecast;

/**
 * Queryable provides element query factory methods.
 *
 * This trait contains static methods for finding elements by various criteria,
 * including find(), findOne(), findAll(), and get() methods.
 *
 * @internal
 */
trait Queryable
{
    /**
     * @return ElementQuery<static>
     */
    public static function find(): ElementQueryInterface
    {
        return new ElementQuery(static::class);
    }

    public static function findOne(mixed $criteria = null): ?static
    {
        return static::findByCondition($criteria, true);
    }

    public static function findAll(mixed $criteria = null): array
    {
        return static::findByCondition($criteria, false);
    }

    /**
     * @interitdoc
     */
    public static function get(int|string $id): ?static
    {
        return static::find()
            ->id($id)
            ->fixedOrder()
            ->drafts(null)
            ->provisionalDrafts(null)
            ->revisions(null)
            ->status(null)
            ->first();
    }

    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(ElementCondition::class, [static::class]);
    }

    /**
     * Finds Element instance(s) by the given condition.
     *
     * This method is internally called by [[findOne()]] and [[findAll()]].
     *
     * @param  mixed  $criteria  Refer to [[findOne()]] and [[findAll()]] for the explanation of this parameter
     * @param  bool  $one  Whether this method is called by [[findOne()]] or [[findAll()]]
     * @return static|static[]|null
     */
    protected static function findByCondition(mixed $criteria, bool $one): array|static|null
    {
        /** @var ElementQuery<static> $query */
        $query = static::find();

        if ($criteria !== null) {
            if (! is_array($criteria) || Arr::isList($criteria)) {
                $criteria = ['id' => $criteria];
            }
            Typecast::configure($query, $criteria);
        }

        if ($one) {
            return $query->first();
        }

        return $query->all();
    }
}
