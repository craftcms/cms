<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\elements;

use craft\behaviors\DraftBehavior;
use craft\elements\Entry as EntryElement;
use craft\gql\interfaces\elements\ContentBlock as ContentBlockInterface;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class ContentBlock
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.8.0
 */
class ContentBlock extends Element
{
    /**
     * @inheritdoc
     */
    public function __construct(array $config)
    {
        $config['interfaces'] = [
            ContentBlockInterface::getType(),
        ];

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        /** @var EntryElement|DraftBehavior $source */
        $fieldName = $resolveInfo->fieldName;

        return match ($fieldName) {
            'fieldId' => $source->fieldId,
            'ownerId' => $source->ownerId,
            'fieldHandle' => $source->getField()?->handle,
            default => parent::resolve($source, $arguments, $context, $resolveInfo),
        };
    }
}
