<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types;

use CraftCms\Cms\Field\Data\IconData as FieldIconData;
use GraphQL\Type\Definition\ResolveInfo;
use Override;

class IconData extends ObjectType
{
    #[Override]
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        $fieldName = $resolveInfo->fieldName;

        /** @var FieldIconData $source */
        return $source->$fieldName;
    }
}
