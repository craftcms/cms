<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Interfaces\Elements;

use CraftCms\Cms\Gql\Arguments\Elements\ContentBlock as ContentBlockArguments;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Gql\Interfaces\Element;
use CraftCms\Cms\Gql\Types\Generators\ContentBlock as ContentBlockGenerator;
use CraftCms\Cms\Support\Facades\Gql;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;
use Override;

class ContentBlock extends Element
{
    #[Override]
    public static function getTypeGenerator(): string
    {
        return ContentBlockGenerator::class;
    }

    #[Override]
    public static function getType(): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class.'::getFieldDefinitions',
            'description' => 'This is the interface implemented by all content block elements.',
            'resolveType' => self::class.'::resolveElementTypeName',
        ]));

        ContentBlockGenerator::generateTypes();

        return $type;
    }

    #[Override]
    public static function getName(): string
    {
        return 'ContentBlockInterface';
    }

    #[Override]
    public static function getFieldDefinitions(): array
    {
        $contentBlockArguments = ContentBlockArguments::getArguments();
        $allFieldArguments = ContentBlockArguments::getContentArguments();

        return Gql::prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), static::getDraftFieldDefinitions(), [
            'canonicalId' => [
                'name' => 'canonicalId',
                'type' => Type::int(),
                'description' => 'Returns the content block’s canonical ID.',
            ],
            'canonicalUid' => [
                'name' => 'canonicalUid',
                'type' => Type::string(),
                'description' => 'Returns the content block’s canonical UUID.',
            ],
            'fieldId' => [
                'name' => 'fieldId',
                'type' => Type::int(),
                'description' => 'The ID of the field that contains the content block.',
            ],
            'fieldHandle' => [
                'name' => 'fieldHandle',
                'type' => Type::string(),
                'description' => 'The handle of the field that contains the content block.',
                'complexity' => GqlHelper::singleQueryComplexity(),
            ],
            'ownerId' => [
                'name' => 'ownerId',
                'type' => Type::int(),
                'description' => 'The ID of the content block’s owner element.',
            ],
            'localized' => [
                'name' => 'localized',
                'args' => [
                    ...$contentBlockArguments,
                    ...$allFieldArguments,
                ],
                'type' => Type::nonNull(Type::listOf(Type::nonNull(static::getType()))),
                'description' => 'The same element in other locales.',
                'complexity' => GqlHelper::eagerLoadComplexity(),
            ],
        ]), self::getName());
    }
}
