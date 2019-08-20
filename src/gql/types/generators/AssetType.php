<?php
namespace craft\gql\types\generators;

use Craft;
use craft\base\Field;
use craft\base\Volume;
use craft\elements\Asset as AssetElement;
use craft\gql\base\GeneratorInterface;
use craft\gql\interfaces\elements\Asset as AssetInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\elements\Asset;
use craft\helpers\Gql as GqlHelper;

/**
 * Class AssetType
 */
class AssetType implements GeneratorInterface
{
    /**
     * @inheritdoc
     */
    public static function generateTypes($context = null): array
    {
        $volumes = Craft::$app->getVolumes()->getAllVolumes();
        $gqlTypes = [];

        foreach ($volumes as $volume) {
            /** @var Volume $volume */
            $typeName = AssetElement::getGqlTypeNameByContext($volume);
            $requiredContexts = AssetElement::getGqlScopesByContext($volume);

            if (!GqlHelper::isTokenAwareOf($requiredContexts)) {
                continue;
            }

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
}
