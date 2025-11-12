<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use Illuminate\Database\Query\Builder;

abstract class Field extends \CraftCms\Cms\Field\Field
{
    public static function modifyQuery(Builder $query, array $instances, mixed $value): Builder
    {
        if (!method_exists(static::class, 'queryCondition')) {
            return $query;
        }

        $params = [];

        $condition = self::queryCondition($instances, $value, $params);

        if ($condition === null || $condition === false) {
            return $query;
        }

        $db = \Craft::$app->getDb();
        $sql = $db->getQueryBuilder()->buildCondition($condition, $params);

        // Yii uses named parameters, Laravel uses positional
        $sql = preg_replace('/:qp\d+/', '?', $sql);

        return $query->whereRaw($sql, array_values($params));
    }
}
