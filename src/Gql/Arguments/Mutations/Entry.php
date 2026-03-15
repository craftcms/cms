<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments\Mutations;

use CraftCms\Cms\Gql\Arguments\ElementMutationArguments;
use CraftCms\Cms\Gql\Types\DateTime;
use GraphQL\Type\Definition\Type;

class Entry extends ElementMutationArguments
{
    #[\Override]
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            'authorId' => [
                'name' => 'authorId',
                'type' => Type::id(),
                'description' => 'The entry author’s ID.',
            ],
            'authorIds' => [
                'name' => 'authorIds',
                'type' => Type::listOf(Type::id()),
                'description' => 'The entry authors’ IDs.',
            ],
            'postDate' => [
                'name' => 'postDate',
                'type' => DateTime::getType(),
                'description' => 'When should the entry be posted.',
            ],
            'expiryDate' => [
                'name' => 'expiryDate',
                'type' => DateTime::getType(),
                'description' => 'When should the entry expire.',
            ],
            'slug' => [
                'name' => 'slug',
                'type' => Type::string(),
                'description' => 'Narrows the query results based on the elements’ slugs.',
            ],
            'siteId' => [
                'name' => 'siteId',
                'type' => Type::int(),
                'description' => 'Determines which site the elements should be saved to. Defaults to the primary site.',
            ],
            'revisionNotes' => [
                'name' => 'revisionNotes',
                'type' => Type::string(),
                'description' => 'Sets the revision notes for the entry.',
            ],
        ]);
    }
}
