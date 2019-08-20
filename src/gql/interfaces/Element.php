<?php
namespace craft\gql\interfaces;

use craft\base\ElementInterface;
use craft\gql\base\InterfaceType;
use craft\gql\GqlEntityRegistry;
use craft\gql\TypeLoader;
use craft\gql\types\generators\ElementType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InterfaceType as GqlInterfaceType;

/**
 * Class Element
 */
class Element extends InterfaceType
{
    /**
     * @inheritdoc
     */
    public static function getTypeGenerator(): string
    {
        return ElementType::class;
    }

    /**
     * @inheritdoc
     */
    public static function getType($fields = null): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::class)) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::class, new GqlInterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFields',
            'description' => 'This is the interface implemented by all elements.',
            'resolveType' => function (ElementInterface $value) {
                return GqlEntityRegistry::getEntity($value->getGqlTypeName());
            }
        ]));

        foreach (ElementType::generateTypes() as $typeName => $generatedType) {
            TypeLoader::registerType($typeName, function () use ($generatedType) { return $generatedType ;});
        }

        return $type;
    }

    /**
     * @inheritdoc
     */
    public static function getFields(): array
    {
        return array_merge(parent::getFields(), [
            'title' => [
                'name' => 'title',
                'type' => Type::string(),
                'description' => 'The element’s title.'
            ],
            'slug' => [
                'name' => 'slug',
                'type' => Type::string(),
                'description' => 'The element’s slug.'
            ],
            'uri' => [
                'name' => 'uri',
                'type' => Type::string(),
                'description' => 'The element’s URI.'
            ],
            'enabled' => [
                'name' => 'enabled',
                'type' => Type::boolean(),
                'description' => 'Whether the element is enabled or not.'
            ],
            'archived' => [
                'name' => 'archived',
                'type' => Type::boolean(),
                'description' => 'Whether the element is archived or not.'
            ],
            'siteId' => [
                'name' => 'siteId',
                'type' => Type::int(),
                'description' => 'The ID of the site the element is associated with.'
            ],
            'searchScore' => [
                'name' => 'searchScore',
                'type' => Type::string(),
                'description' => 'The element’s search score, if the `search` parameter was used when querying for the element.'
            ],
            'trashed' => [
                'name' => 'trashed',
                'type' => Type::boolean(),
                'description' => 'Whether the element has been soft-deleted or not.'
            ],
            'status' => [
                'name' => 'status',
                'type' => Type::string(),
                'description' => 'The element\'s status.'
            ]
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'ElementInterface';
    }
}
