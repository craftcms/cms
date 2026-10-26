<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types;

use CraftCms\Cms\Field\Data\LinkData as FieldLinkData;
use GraphQL\Type\Definition\ResolveInfo;
use Override;

/**
 * @since 6.0.0
 */
class LinkData extends ObjectType
{
    #[Override]
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        // Values of link types that are no longer installed are kept as raw arrays.
        if (! $source instanceof FieldLinkData) {
            return null;
        }

        $fieldName = $resolveInfo->fieldName;

        return match ($fieldName) {
            'type' => $source->getType(),
            'value' => $source->getValue(),
            'label' => $source->getLabel(true),
            'defaultLabel' => $source->getLabel(false),
            'url' => $source->getUrl(),
            'link' => $source->getLink(),
            'elementType' => $source->getElement() ? $source->getElement()::class : null,
            'elementId' => $source->getElement()?->id,
            'elementSiteId' => $source->getElement()?->siteId,
            'elementTitle' => $source->getElement() ? (string) $source->getElement() : null,
            default => $source->$fieldName,
        };
    }
}
