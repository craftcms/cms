<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments\Mutations;

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\Type;

/**
 * @phpstan-import-type ArgumentConfig from Argument
 *
 * @since 6.0.0
 */
class NestedEntry extends Entry
{
    /** @return array<string, ArgumentConfig> */
    #[\Override]
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), static::propertyArguments(EntryElement::class, ['ownerId', 'sortOrder']));
    }
}
