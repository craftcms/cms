<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\generators;

use Craft;
use craft\elements\GlobalSet as GlobalSetElement;
use craft\gql\base\Generator;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\ObjectType;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\elements\GlobalSet as GlobalSetInterface;
use craft\gql\types\elements\GlobalSet;
use craft\helpers\Gql as GqlHelper;

/**
 * Class GlobalSetType
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class GlobalSetType extends Generator implements GeneratorInterface, SingleGeneratorInterface
{
    /**
     * @inheritdoc
     */
    public static function generateTypes(mixed $context = null): array
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
     * Returns the generator name.
     */
    public static function getName($context = null): string
    {
        /** @var GlobalSetElement $context */
        return $context->handle . '_GlobalSet';
    }

    /**
     * @inheritdoc
     */
    public static function generateType(mixed $context): ObjectType
    {
        $typeName = self::getName($context);

        $contentFieldGqlTypes = self::getContentFields($context);

        $globalSetFields = array_merge(GlobalSetInterface::getFieldDefinitions(), $contentFieldGqlTypes);

        return GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new GlobalSet([
            'name' => $typeName,
            'fields' => function() use ($globalSetFields, $typeName) {
                return Craft::$app->getGql()->prepareFieldDefinitions($globalSetFields, $typeName);
            },
        ]));
    }
}
