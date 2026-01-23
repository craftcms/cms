<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use Craft;
use Illuminate\Contracts\Database\Query\Builder;

/**
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Field} instead.
 */
abstract class Field extends \CraftCms\Cms\Field\Field
{
    public static function modifyQuery(Builder $query, array $instances, mixed $value): Builder
    {
        if (!method_exists(static::class, 'queryCondition')) {
            return $query;
        }

        $params = [];

        $condition = static::queryCondition($instances, $value, $params);

        if ($condition === null || $condition === false) {
            return $query;
        }

        $db = Craft::$app->getDb();
        $sql = $db->getQueryBuilder()->buildCondition($condition, $params);

        // Yii uses named parameters, Laravel uses positional
        $sql = preg_replace('/:qp\d+/', '?', $sql);

        return $query->whereRaw($sql, array_values($params));
    }
}
