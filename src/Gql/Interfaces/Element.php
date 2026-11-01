<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Interfaces;

use CraftCms\Cms\Element\Attributes\ElementProperty;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element as ElementElement;
use CraftCms\Cms\Element\Enums\ElementPropertyType;
use CraftCms\Cms\Gql\Contracts\SingularTypeInterface;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Gql\Types\DateTime;
use CraftCms\Cms\Gql\Types\Generators\ElementType;
use CraftCms\Cms\Gql\Types\InterfaceType;
use CraftCms\Cms\Gql\Types\Release;
use CraftCms\Cms\Gql\Types\Site;
use CraftCms\Cms\Support\Facades\Gql;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InterfaceType as GqlInterfaceType;
use GraphQL\Type\Definition\Type;
use Override;

/**
 * @phpstan-import-type FieldDefinitionConfig from FieldDefinition
 *
 * @since 6.0.0
 */
class Element extends InterfaceType implements SingularTypeInterface
{
    public static function getTypeGenerator(): string
    {
        return ElementType::class;
    }

    public static function getType(): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new GqlInterfaceType([
            'name' => static::getName(),
            'fields' => self::class.'::getFieldDefinitions',
            'description' => 'This is the interface implemented by all elements.',
            'resolveType' => self::class.'::resolveElementTypeName',
        ]));

        ElementType::generateTypes();

        return $type;
    }

    /** @return array<string, FieldDefinitionConfig> */
    #[Override]
    public static function getFieldDefinitions(): array
    {
        return Gql::prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), GqlHelper::canSchema('settings.sites') ? [
            'site' => [
                'name' => 'site',
                'type' => Type::nonNull(Site::getType()),
                'description' => 'The site the element is associated with.',
            ],
        ] : [], [
            Gql::GRAPHQL_COUNT_FIELD => [
                'name' => Gql::GRAPHQL_COUNT_FIELD,
                'type' => Type::int(),
                'args' => [
                    'field' => [
                        'name' => 'field',
                        'type' => Type::nonNull(Type::string()),
                        'description' => 'The handle of the field that holds the relations.',
                    ],
                ],
                'description' => 'Return a number of related elements for a field.',
                'complexity' => GqlHelper::eagerLoadComplexity(),
            ],
            ...static::propertyFields(ElementElement::class, ['title', 'slug', 'uri', 'enabled', 'archived']),
            'siteHandle' => [
                'name' => 'siteHandle',
                'type' => Type::string(),
                'description' => 'The handle of the site the element is associated with.',
            ],
            ...static::propertyFields(ElementElement::class, ['siteId']),
            'siteSettingsId' => [
                'name' => 'siteSettingsId',
                'type' => Type::id(),
                'description' => 'The unique identifier for an element-site relation.',
            ],
            ...static::propertyFields(ElementElement::class, ['language', 'searchScore', 'trashed', 'status', 'dateCreated', 'dateUpdated']),
        ]), self::getName());
    }

    /** @return array<string, FieldDefinitionConfig> */
    public static function getDraftFieldDefinitions(): array
    {
        return array_merge([
            ...static::propertyFields(ElementElement::class, ['isDraft', 'isRevision', 'revisionId', 'revisionNotes', 'draftId', 'isUnpublishedDraft', 'draftName', 'draftNotes']),
        ], GqlHelper::canQueryReleases() ? [
            'release' => [
                'name' => 'release',
                'type' => Release::getType(),
                'description' => 'The unpublished release the draft is attached to.',
                'complexity' => GqlHelper::nPlus1Complexity(),
            ],
        ] : []);
    }

    public static function getName(): string
    {
        return 'ElementInterface';
    }

    /**
     * Returns field definitions for readable properties that an element type declares with {@see ElementProperty}.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @param  list<string>  $names  Property names to return, in this order. Every name must be declared as readable.
     * @return array<string, FieldDefinitionConfig>
     */
    protected static function propertyFields(string $elementType, array $names): array
    {
        $fields = [];

        foreach (ElementProperty::only($elementType, $names, 'readable') as $name => $property) {
            $type = match ($property->type) {
                ElementPropertyType::String => Type::string(),
                ElementPropertyType::Int, ElementPropertyType::Id => Type::int(),
                ElementPropertyType::IdList => Type::listOf(Type::int()),
                ElementPropertyType::Boolean => Type::boolean(),
                ElementPropertyType::DateTime => DateTime::getType(),
                ElementPropertyType::Float => Type::float(),
            };

            $fields[$name] = [
                'name' => $name,
                'type' => $property->nonNull ? Type::nonNull($type) : $type,
                'description' => $property->descriptionFor($elementType),
            ];
        }

        return $fields;
    }
}
