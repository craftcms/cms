<?php
namespace craft\gql\interfaces\elements;

use craft\base\ElementInterface;
use craft\gql\common\SchemaObject;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\BaseInterface;
use craft\gql\TypeLoader;
use craft\gql\types\generators\ElementType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * Class Element
 */
class Element extends BaseInterface
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

        $type = GqlEntityRegistry::createEntity(self::class, new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFields',
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
    public static function getCommonFields(): array
    {
        return array_merge(parent::getCommonFields(), [
            'title' => [
                'name' => 'title',
                'type' => Type::string(),
                'description' => 'The element’s title'
            ],
            'slug' => [
                'name' => 'slug',
                'type' => Type::string(),
                'description' => 'The element’s slug'
            ],
            'uri' => [
                'name' => 'uri',
                'type' => Type::string(),
                'description' => 'The element’s URI'
            ],
            'enabled' => [
                'name' => 'enabled',
                'type' => Type::boolean(),
                'description' => 'Whether the element is enabled'
            ],
            'archived' => [
                'name' => 'archived',
                'type' => Type::boolean(),
                'description' => 'Whether the element is archived'
            ],
            'siteId' => [
                'name' => 'siteId',
                'type' => Type::int(),
                'description' => 'The site ID the element is associated with'
            ],
            'searchScore' => [
                'name' => 'searchScore',
                'type' => Type::string(),
                'description' => 'The element’s search score, if the `search` parameter was used when querying for the element'
            ],
            'trashed' => [
                'name' => 'trashed',
                'type' => Type::boolean(),
                'description' => 'Whether the element has been soft-deleted'
            ],
            'status' => [
                'name' => 'status',
                'type' => Type::string(),
                'description' => 'status'
            ]
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getFields(): array
    {
        return static::getCommonFields();
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'ElementInterface';
    }
}
