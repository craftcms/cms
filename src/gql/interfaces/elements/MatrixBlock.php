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
    public static function getType($fields = null): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::class)) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::class, new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFields',
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
            'fieldUid' => Type::string(),
            'fieldId' => Type::int(),
            'ownerUid' => Type::string(),
            // Todo make it possible to travel upwards (make element interface not abstract)
            'owner' => ElementInterface::getType(),
            'ownerId' => Type::int(),
            'ownerSiteId' => Type::int(),
            'typeUid' => Type::string(),
            'typeId' => Type::int(),
            'typeHandle' => Type::string(),
            'sortOrder' => Type::int(),
        ]);
    }
}
