<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Elements;

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Gql\Interfaces\Elements\ContentBlock as ContentBlockInterface;
use GraphQL\Type\Definition\ResolveInfo;
use Override;

class ContentBlock extends Element
{
    public function __construct(array $config)
    {
        $config['interfaces'] = [
            ContentBlockInterface::getType(),
        ];

        parent::__construct($config);
    }

    #[Override]
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        /** @var EntryElement $source */
        $fieldName = $resolveInfo->fieldName;

        return match ($fieldName) {
            'fieldId' => $source->fieldId,
            'ownerId' => $source->ownerId,
            'fieldHandle' => $source->getField()?->handle,
            default => parent::resolve($source, $arguments, $context, $resolveInfo),
        };
    }
}
