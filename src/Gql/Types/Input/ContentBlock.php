<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Input;

use CraftCms\Cms\Field\ContentBlock as ContentBlockField;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use GraphQL\Type\Definition\InputObjectType;

class ContentBlock extends InputObjectType
{
    public static function getType(ContentBlockField $context): mixed
    {
        $typeName = $context->handle.'_ContentBlockInput';

        return GqlEntityRegistry::getOrCreate($typeName, fn () => new InputObjectType([
            'name' => $typeName,
            'description' => sprintf('Defines the content within the “%s” Content Block field’s data.', $context->name),
            'fields' => function () use ($context) {
                $fields = [];

                // Get the field input types
                foreach ($context->getFieldLayout()->getCustomFields() as $field) {
                    /** @var Field $field */
                    $fields[$field->handle] = $field->getContentGqlMutationArgumentType();
                }

                return $fields;
            },
            'normalizeValue' => [self::class, 'normalizeValue'],
        ]));
    }

    public static function normalizeValue(mixed $value): mixed
    {
        return $value ? ['fields' => $value] : [];
    }
}
