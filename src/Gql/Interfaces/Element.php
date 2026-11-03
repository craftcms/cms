<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Interfaces;

use CraftCms\Cms\Element\Attributes\ElementProperty;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element as ElementElement;
use CraftCms\Cms\Gql\Attributes\Gql as GqlAttribute;
use CraftCms\Cms\Gql\Contracts\SingularTypeInterface;
use CraftCms\Cms\Gql\ElementSchemaTypes;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\GqlHelper;
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
        return static::withElementTypeFields(Gql::prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), GqlHelper::canSchema('settings.sites') ? [
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
            ...static::gqlPropertyFields(ElementElement::class),
            'siteHandle' => [
                'name' => 'siteHandle',
                'type' => Type::string(),
                'description' => 'The handle of the site the element is associated with.',
            ],
            'siteSettingsId' => [
                'name' => 'siteSettingsId',
                'type' => Type::id(),
                'description' => 'The unique identifier for an element-site relation.',
            ],
        ]), self::getName()));
    }

    /**
     * Returns the element type whose readable properties should be added as fields automatically.
     *
     * Every readable property that the element type declares with {@see ElementProperty} and doesn’t exclude
     * with {@see GqlAttribute} is added, if it applies to the element type and its gate allows it.
     *
     * @return class-string<ElementInterface>|null
     */
    protected static function elementType(): ?string
    {
        return null;
    }

    /**
     * Adds fields for the readable properties of the element type that {@see ElementType()} returns.
     *
     * @param  array<string, FieldDefinitionConfig>  $fields
     * @return array<string, FieldDefinitionConfig>
     */
    protected static function withElementTypeFields(array $fields): array
    {
        $elementType = static::elementType();

        return $elementType ? [...$fields, ...static::gqlPropertyFields($elementType)] : $fields;
    }

    /** @return array<string, FieldDefinitionConfig> */
    public static function getDraftFieldDefinitions(): array
    {
        return GqlHelper::canQueryReleases() ? [
            'release' => [
                'name' => 'release',
                'type' => Release::getType(),
                'description' => 'The unpublished release the draft is attached to.',
                'complexity' => GqlHelper::nPlus1Complexity(),
            ],
        ] : [];
    }

    public static function getName(): string
    {
        return 'ElementInterface';
    }

    /**
     * Returns field definitions for the readable properties that apply to an element type, that {@see GqlAttribute}
     * doesn’t exclude, and whose gate allows the element type.
     *
     * For the base {@see ElementElement} type, these are the properties it declares. For other element types, they’re
     * the properties that the element type declares beyond those, plus base properties with gates or required capabilities, which the base
     * element interface leaves out.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @return array<string, FieldDefinitionConfig>
     */
    protected static function gqlPropertyFields(string $elementType): array
    {
        $baseProperties = $elementType === ElementElement::class ? [] : ElementProperty::all(ElementElement::class);

        $properties = array_filter(
            ElementProperty::readable($elementType),
            function (ElementProperty $property, string $name) use ($elementType, $baseProperties): bool {
                $gql = GqlAttribute::of($property);

                // Base properties without a gate or required capability are already fields of the base element interface.
                return $gql?->allows($elementType) && (! isset($baseProperties[$name]) || $gql->gate !== null || $property->requires !== null);
            },
            ARRAY_FILTER_USE_BOTH,
        );

        return static::propertyFields($elementType, array_keys($properties));
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

        foreach (ElementProperty::readable($elementType, $names) as $name => $property) {
            $type = ElementSchemaTypes::property($property->type, input: false);

            $fields[$name] = [
                'name' => $name,
                'type' => $property->nonNull ? Type::nonNull($type) : $type,
                'description' => $property->descriptionFor($elementType),
            ];
        }

        return $fields;
    }
}
