<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types;

use CraftCms\Cms\Asset\Analysis\Data\ImageAnalysis as ImageAnalysisData;
use GraphQL\Type\Definition\ResolveInfo;
use Override;

/**
 * @since 6.0.0
 */
class ImageAnalysis extends ObjectType
{
    #[Override]
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        /** @var ImageAnalysisData $source */
        return match ($resolveInfo->fieldName) {
            'type' => $source->type?->value,
            default => $source->{$resolveInfo->fieldName},
        };
    }
}
