<?php
namespace craft\gql\interfaces\elements;

use craft\elements\Asset as AssetElement;
use craft\gql\TypeLoader;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\DateTimeType;
use craft\gql\types\generators\AssetTypeGenerator;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * Class Asset
 */
class Asset extends BaseElement
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
            'resolveType' => function (AssetElement $value) {
                return GqlEntityRegistry::getEntity(AssetTypeGenerator::getName($value->getVolume()));
            }
        ]));

        foreach (AssetTypeGenerator::generateTypes() as $typeName => $generatedType) {
            TypeLoader::registerType($typeName, function () use ($generatedType) { return $generatedType ;});
        }

        return $type;
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'AssetInterface';
    }

    /**
     * @inheritdoc
     */
    public static function getFields(): array {
        // Todo nest nestable things. Such as volume data under volume subtype.
        return array_merge(parent::getCommonFields(), [
            'volumeUid' => Type::string(),
            'volumeId' => Type::int(),
            'folderUid' => Type::string(),
            'folderId' => Type::int(),
            'folderPath' => Type::string(),
            'filename' => Type::string(),
            'extension' => Type::string(),
            'hasFocalPoint' => Type::boolean(),
            'focalPoint' => Type::listOf(Type::float()),
            'kind' => Type::string(),
            'size' => Type::string(),
            'height' => Type::int(),
            'width' => Type::int(),
            'img' => Type::string(),
            'url' => Type::string(),
            'mimeType' => Type::string(),
            'path' => Type::string(),
            'dateModified' => DateTimeType::getType(),
        ]);
    }
}
