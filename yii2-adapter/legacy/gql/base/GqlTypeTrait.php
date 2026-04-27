<?php

declare(strict_types=1);

namespace craft\gql\base;

use CraftCms\Cms\Gql\Concerns\HasGqlType;

/**
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Gql\Concerns\HasGqlType} instead.
 * @phpstan-ignore-next-line
 */
trait GqlTypeTrait
{
    use HasGqlType;
}
