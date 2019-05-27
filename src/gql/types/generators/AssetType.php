<?php
namespace craft\gql\types\generators;

use Craft;
use craft\base\Field;
use craft\base\Volume;
use craft\gql\interfaces\elements\Asset as AssetInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\Asset;

/**
 * Class AssetTypeGenerator
 */
class AssetType
{
    public static function generateTypes(): array
    {
        $volumes = Craft::$app->getVolumes()->getAllVolumes();

        $gqlTypes = [];

        foreach ($volumes as $volume) {
            /** @var Volume $volume */
            $typeName = self::getName($volume);
            $contentFields = $volume->getFields();
            $contentFieldGqlTypes = [];

            /** @var Field $contentField */
            foreach ($contentFields as $contentField) {
                $contentFieldGqlTypes[$contentField->handle] = $contentField->getContentGqlType();
            }

            $assetFields = array_merge(AssetInterface::getFields(), $contentFieldGqlTypes);

            // Generate a type for each entry type
            $gqlTypes[$typeName] = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new Asset([
                'name' => $typeName,
                'fields' => function () use ($assetFields) {
                    return $assetFields;
                }
            ]));
        }

        return $gqlTypes;
    }

    /**
     * Return an asset's GQL type name by volume
     *
     * @param Volume $volume
     * @return string
     */
    public static function getName(Volume $volume)
    {
        return $volume->handle . '_Asset';
    }
}
