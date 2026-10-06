<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types;

use CraftCms\Cms\Image\Data\ImageColors as ImageColorsData;
use GraphQL\Type\Definition\ResolveInfo;
use Override;

/**
 * @since 6.0.0
 */
class ImageColors extends ObjectType
{
    #[Override]
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        /** @var ImageColorsData $source */
        return match ($resolveInfo->fieldName) {
            'left' => $source->left(),
            'right' => $source->right(),
            'top' => $source->top(),
            'bottom' => $source->bottom(),
            default => $source->{$resolveInfo->fieldName},
        };
    }
}
