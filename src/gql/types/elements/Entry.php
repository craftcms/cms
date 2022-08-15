<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\elements;

use craft\behaviors\DraftBehavior;
use craft\elements\Entry as EntryElement;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class Entry
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Entry extends Element
{
    /**
     * @inheritdoc
     */
    public function __construct(array $config)
    {
        $config['interfaces'] = [
            EntryInterface::getType(),
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
            'sectionId' => $source->sectionId,
            'typeId' => $source->getTypeId(),
            'sectionHandle' => $source->getSection()->handle,
            'typeHandle' => $source->getType()->handle,
            'draftName', 'draftNotes' => $source->getIsDraft() ? $source->{$fieldName} : null,
            'draftCreator' => $source->getIsDraft() ? $source->getCreator() : null,
            'revisionCreator' => $source->getIsRevision() ? $source->getCreator() : null,
            default => parent::resolve($source, $arguments, $context, $resolveInfo),
        };
    }
}
