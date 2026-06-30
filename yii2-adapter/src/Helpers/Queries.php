<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Helpers\Queries;

use Craft;
use CraftCms\Cms\Support\Str;

/**
 * @return array{0:string,1:array|null}
 */
function buildCondition(mixed $condition): array
{
    $params = [];
    $sql = Craft::$app->getDb()->getQueryBuilder()->buildCondition($condition, $params);

    if ($sql !== '') {
        [$sql, $bindings] = convertBindings($sql, $params);
    } else {
        $bindings = null;
    }

    return [$sql, $bindings];
}

/**
 * Converts param bindings from PDO-style used by Yii (`:name`) to Laravel-style (`?`).
 *
 * @return array{0:string,1:array}
 */
function convertBindings(string $sql, array $params): array
{
    // ensure all param names start with `:`
    $params = collect($params)->keyBy(fn($value, $name) => Str::start($name, ':'));

    $re = sprintf('/(%s)\b/', $params->keys()->map(fn($name) => preg_quote($name, '/'))->join('|'));

    $bindings = [];

    $sql = preg_replace_callback($re, function($match) use ($params, &$bindings) {
        $bindings[] = $params->get($match[1]);
        return '?';
    }, $sql);

    return [$sql, $bindings];
}
