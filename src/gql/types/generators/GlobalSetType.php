<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\generators;

use Craft;
use craft\base\Field;
use craft\elements\GlobalSet as GlobalSetElement;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\ObjectType;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\elements\GlobalSet as GlobalSetInterface;
use craft\gql\TypeManager;
use craft\gql\types\elements\GlobalSet;
use craft\helpers\Gql;
use craft\helpers\Gql as GqlHelper;

/**
 * Class GlobalSetType
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class GlobalSetType implements GeneratorInterface, SingleGeneratorInterface
{
    /**
     * @inheritdoc
     */
    public static function generateTypes($context = null): array
    {
        $globalSets = Craft::$app->getGlobals()->getAllSets();
        $gqlTypes = [];

        foreach ($globalSets as $globalSet) {
            $requiredContexts = GlobalSetElement::gqlScopesByContext($globalSet);

            if (!GqlHelper::isSchemaAwareOf($requiredContexts)) {
                continue;
            }

            // Generate a type for each global set
            $type = static::generateType($globalSet);
            $gqlTypes[$type->name] = $type;
        }

        return $gqlTypes;
    }

    /**
     * @inheritdoc
     */
    public static function getName($context = null): string
    {
        /** @var GlobalSetElement $context */
        return $context->handle . '_GlobalSet';
    }

    /**
     * @inheritdoc
     */
    public static function generateType($context): ObjectType
    {
        /** @var GlobalSetElement $globalSet */
        $typeName = self::getName($context);
        $contentFields = $context->getFields();
        $contentFieldGqlTypes = [];

        /** @var Field $contentField */
        foreach ($contentFields as $contentField) {
            if (!$contentField instanceof GqlSchemaAwareFieldInterface || $contentField->getExistsForCurrentGqlSchema()) {
                $contentFieldGqlTypes[$contentField->handle] = $contentField->getContentGqlType();
            }
        }

        $globalSetFields = TypeManager::prepareFieldDefinitions(array_merge(GlobalSetInterface::getFieldDefinitions(), $contentFieldGqlTypes), $typeName);

        return GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new GlobalSet([
            'name' => $typeName,
            'fields' => function() use ($globalSetFields) {
                return $globalSetFields;
            }
        ]));
    }

}
