<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Arguments\Elements;

use CraftCms\Cms\Field\ContentBlock as ContentBlockField;
use CraftCms\Cms\Field\Elements\ContentBlock as ContentBlockElement;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Gql\Arguments\ElementArguments;
use CraftCms\Cms\Gql\Types\QueryArgument;
use CraftCms\Cms\Support\Facades\Gql;
use GraphQL\Type\Definition\Type;

class ContentBlock extends ElementArguments
{
    #[\Override]
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            'field' => [
                'name' => 'field',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the field the content blocks are contained by.',
            ],
            'fieldId' => [
                'name' => 'fieldId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the field the content blocks are contained by, per the fields’ IDs.',
            ],
            'primaryOwnerId' => [
                'name' => 'primaryOwnerId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the primary owner element of the content blocks, per the owners’ IDs.',
            ],
            'ownerId' => [
                'name' => 'ownerId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the owner element of the content blocks, per the owners’ IDs.',
            ],
        ]);
    }

    #[\Override]
    public static function getContentArguments(): array
    {
        return Gql::getOrSetContentArguments(ContentBlockElement::class, function (): array {
            /** @var ContentBlockField[] $fields */
            $fields = app(Fields::class)->getFieldsByType(ContentBlockField::class);

            $arguments = [];
            foreach ($fields as $field) {
                $arguments += Gql::getFieldLayoutArguments($field->getFieldLayout());
            }

            return $arguments;
        });
    }
}
