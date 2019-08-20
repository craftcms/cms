<?php
namespace craft\gql\interfaces\elements;

use craft\elements\GlobalSet as GlobalSetElement;
use craft\gql\interfaces\Element;
use craft\gql\GqlEntityRegistry;
use craft\gql\TypeLoader;
use craft\gql\types\generators\GlobalSetType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * Class GlobalSet
 */
class GlobalSet extends Element
{
    /**
     * @inheritdoc
     */
    public static function getTypeGenerator(): string
    {
        return GlobalSetType::class;
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
            'description' => 'This is the interface implemented by all global sets.',
            'resolveType' => function (GlobalSetElement $value) {
                return GqlEntityRegistry::getEntity($value->getGqlTypeName());
            }
        ]));

        foreach (GlobalSetType::generateTypes() as $typeName => $generatedType) {
            TypeLoader::registerType($typeName, function () use ($generatedType) { return $generatedType ;});
        }

        return $type;
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'GlobalSetInterface';
    }

    /**
     * @inheritdoc
     */
    public static function getFields(): array {
        return array_merge(parent::getFields(), [
            'name' => [
                'name' => 'name',
                'type' => Type::string(),
                'description' => 'The name of the global set.'
            ],
            'handle' => [
                'name' => 'handle',
                'type' => Type::string(),
                'description' => 'The handle of the global set.'
            ],
        ]);
    }
}
