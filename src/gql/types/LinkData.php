<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types;

use craft\fields\data\LinkData as FieldLinkData;
use craft\gql\base\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class LinkData
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.6.0
 */
class LinkData extends ObjectType
{
    /**
     * @inheritdoc
     */
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
            'elementTitle' => $source->getElement() ? (string)$source->getElement() : null,
            default => $source->$fieldName,
        };
    }
}
