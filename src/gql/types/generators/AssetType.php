<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\generators;

use Craft;
use craft\base\Field;
use craft\base\Volume;
use craft\elements\Asset as AssetElement;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\ObjectType;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\elements\Asset as AssetInterface;
use craft\gql\TypeManager;
use craft\gql\types\elements\Asset;
use craft\helpers\Gql;
use craft\helpers\Gql as GqlHelper;

/**
 * Class AssetType
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class AssetType implements GeneratorInterface, SingleGeneratorInterface
{
    /**
     * @inheritdoc
     */
    public static function generateTypes($context = null): array
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
    public static function generateType($context): ObjectType
    {
        /** @var Volume $volume */
        $typeName = AssetElement::gqlTypeNameByContext($context);
        $contentFields = $context->getFields();
        $contentFieldGqlTypes = [];

        /** @var Field $contentField */
        foreach ($contentFields as $contentField) {
            $contentFieldGqlTypes[$contentField->handle] = $contentField->getContentGqlType();
        }

        $assetFields = TypeManager::prepareFieldDefinitions(array_merge(AssetInterface::getFieldDefinitions(), $contentFieldGqlTypes), $typeName);

        return GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new Asset([
            'name' => $typeName,
            'fields' => function() use ($assetFields) {
                return $assetFields;
            }
        ]));
    }
}
