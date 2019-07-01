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
                return $value->getGqlTypeName();
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
            'title' => Type::string(),
            'slug' => Type::string(),
            'uri' => Type::string(),
            'enabled' => Type::boolean(),
            'archived' => Type::boolean(),
            'siteUid' => Type::boolean(),
            'searchScore' => Type::string(),
            'trashed' => Type::boolean(),
            'elementType' => Type::string(),
            'status' => Type::string()
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
