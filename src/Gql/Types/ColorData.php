<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types;

use CraftCms\Cms\Field\Data\ColorData as FieldColorData;
use GraphQL\Type\Definition\ResolveInfo;
use Override;

/**
 * @since 6.0.0
 */
class ColorData extends ObjectType
{
    #[Override]
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        if (! $source instanceof FieldColorData) {
            return null;
        }

        $fieldName = $resolveInfo->fieldName;

        return match ($fieldName) {
            'hex' => $source->getHex($arguments['withAlpha'] ?? true),
            'label' => $source->label,
            'contrastRatio' => $source->getContrastRatio($arguments['with']),
            default => $source->{'get'.ucfirst($fieldName)}(),
        };
    }
}
