<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Elements;

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Gql\Interfaces\Elements\Entry as EntryInterface;
use GraphQL\Type\Definition\ResolveInfo;
use Override;

class Entry extends Element
{
    public function __construct(array $config)
    {
        $config['interfaces'] = [
            EntryInterface::getType(),
        ];

        parent::__construct($config);
    }

    #[Override]
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        /** @var EntryElement $source */
        $fieldName = $resolveInfo->fieldName;

        return match ($fieldName) {
            'sectionId' => $source->sectionId,
            'fieldId' => $source->fieldId,
            'ownerId' => $source->ownerId,
            'sortOrder' => $source->sortOrder,
            'typeId' => $source->getTypeId(),
            'sectionHandle' => $source->getSection()?->handle,
            'fieldHandle' => $source->getField()?->handle,
            'typeHandle' => $source->getType()->handle,
            'draftName', 'draftNotes' => $source->getIsDraft() ? $source->{$fieldName} : null,
            'draftCreator' => $source->getIsDraft() ? $source->getDraftCreator() : null,
            'revisionCreator' => $source->getIsRevision() ? $source->getRevisionCreator() : null,
            default => parent::resolve($source, $arguments, $context, $resolveInfo),
        };
    }
}
