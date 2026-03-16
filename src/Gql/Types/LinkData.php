<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types;

use CraftCms\Cms\Field\Data\LinkData as FieldLinkData;
use GraphQL\Type\Definition\ResolveInfo;
use Override;

class LinkData extends ObjectType
{
    #[Override]
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        /** @var FieldLinkData $source */
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
