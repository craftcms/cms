<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Resolvers\Elements;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Gql\Resolvers\Resolver;
use GraphQL\Type\Definition\ResolveInfo;

class ContentBlock extends Resolver
{
    /** @param array<string, mixed> $arguments */
    public static function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        $fieldName = GqlHelper::getFieldNameWithAlias($resolveInfo, $source, $context);

        /** @var ElementInterface $source */
        return $source->$fieldName;
    }
}
