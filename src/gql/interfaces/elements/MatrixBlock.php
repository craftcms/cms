<?php
namespace craft\gql\interfaces\elements;

use craft\elements\MatrixBlock as MatrixBlockElement;
use craft\gql\TypeLoader;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\elements\Element as ElementInterface;
use craft\gql\types\generators\MatrixBlockType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * Class MatrixBlock
 */
class MatrixBlock extends Element
{
    /**
     * @inheritdoc
     */
    public static function getTypeGenerator(): string
    {
        return MatrixBlockType::class;
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
            'description' => 'This is the interface implemented by all matrix blocks.',
            'resolveType' => function (MatrixBlockElement $value) {
                return GqlEntityRegistry::getEntity($value->getGqlTypeName());
            }
        ]));

        foreach (MatrixBlockType::generateTypes() as $typeName => $generatedType) {
            TypeLoader::registerType($typeName, function () use ($generatedType) { return $generatedType ;});
        }

        return $type;
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'MatrixBlockInterface';
    }

    /**
     * @inheritdoc
     */
    public static function getFields(): array {
        // Todo nest nestable things. Such as field data under field subtype.
        return array_merge(parent::getCommonFields(), [
            'fieldUid' => [
                'name' => 'fieldUid',
                'type' => Type::string(),
                'description' => 'fieldUid'
            ],
            'fieldId' => [
                'name' => 'fieldId',
                'type' => Type::int(),
                'description' => 'Field ID'
            ],
            'ownerUid' => [
                'name' => 'ownerUid',
                'type' => Type::string(),
                'description' => 'ownerUid'
            ],
            'ownerId' => [
                'name' => 'ownerId',
                'type' => Type::int(),
                'description' => 'Owner ID'
            ],
            'typeUid' => [
                'name' => 'typeUid',
                'type' => Type::string(),
                'description' => 'typeUid'
            ],
            'typeId' => [
                'name' => 'typeId',
                'type' => Type::int(),
                'description' => 'Type ID'
            ],
            'typeHandle' => [
                'name' => 'typeHandle',
                'type' => Type::string(),
                'description' => 'typeHandle'
            ],
            'sortOrder' => [
                'name' => 'sortOrder',
                'type' => Type::int(),
                'description' => 'Sort order'
            ],
        ]);
    }
}
