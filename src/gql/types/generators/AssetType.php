<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\generators;

use Craft;
use craft\elements\Asset as AssetElement;
use craft\gql\base\Generator;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\ObjectType;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\elements\Asset as AssetInterface;
use craft\gql\types\elements\Asset;
use craft\helpers\Gql as GqlHelper;
use craft\models\Volume;

/**
 * Class AssetType
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class AssetType extends Generator implements GeneratorInterface, SingleGeneratorInterface
{
    /**
     * @inheritdoc
     */
    public static function generateTypes(mixed $context = null): array
    {
        $volumes = Craft::$app->getVolumes()->getAllVolumes();
        $gqlTypes = [];

        foreach ($volumes as $volume) {
            $requiredContexts = AssetElement::gqlScopesByContext($volume);

            if (!GqlHelper::isSchemaAwareOf($requiredContexts)) {
                continue;
            }

            // Generate a type for each volume
            $type = static::generateType($volume);
            $gqlTypes[$type->name] = $type;
        }

        return $gqlTypes;
    }

    /**
     * @inheritdoc
     */
    public static function generateType(mixed $context): ObjectType
    {
        $typeName = AssetElement::gqlTypeNameByContext($context);
        $contentFieldGqlTypes = self::getContentFields($context);

        $assetFields = array_merge(AssetInterface::getFieldDefinitions(), $contentFieldGqlTypes);

        return GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new Asset([
            'name' => $typeName,
            'fields' => function() use ($assetFields, $typeName) {
                return Craft::$app->getGql()->prepareFieldDefinitions($assetFields, $typeName);
            },
        ]));
    }
}
