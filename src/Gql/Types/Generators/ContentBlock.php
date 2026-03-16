<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Generators;

use CraftCms\Cms\Field\ContentBlock as ContentBlockField;
use CraftCms\Cms\Field\Elements\ContentBlock as ContentBlockElement;
use CraftCms\Cms\Gql\Contracts\GeneratorInterface;
use CraftCms\Cms\Gql\Contracts\SingleGeneratorInterface;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\Interfaces\Elements\ContentBlock as ContentBlockInterface;
use CraftCms\Cms\Gql\Types\Elements\ContentBlock as ContentBlockType;
use CraftCms\Cms\Gql\Types\ObjectType;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Gql;

class ContentBlock extends Generator implements GeneratorInterface, SingleGeneratorInterface
{
    public static function generateTypes(mixed $context = null): array
    {
        if ($context instanceof ContentBlockField) {
            $fields = [$context];
        } else {
            $fields = Fields::getFieldsByType(ContentBlockField::class);
        }

        $gqlTypes = [];

        foreach ($fields as $field) {
            $type = static::generateType($field);
            $gqlTypes[$type->name] = $type;
        }

        return $gqlTypes;
    }

    public static function generateType(mixed $context): ObjectType
    {
        /** @var ContentBlockField $context */
        $typeName = ContentBlockElement::gqlTypeName($context);

        return GqlEntityRegistry::getOrCreate($typeName, fn () => new ContentBlockType([
            'name' => $typeName,
            'fields' => function () use ($context, $typeName) {
                $contentFieldGqlTypes = self::getContentFields($context->getFieldLayout());
                $contentBlockFields = array_merge(ContentBlockInterface::getFieldDefinitions(), $contentFieldGqlTypes);

                return Gql::prepareFieldDefinitions($contentBlockFields, $typeName);
            },
        ]));
    }
}
