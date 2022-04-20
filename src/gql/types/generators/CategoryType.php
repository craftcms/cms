<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\generators;

use Craft;
use craft\elements\Category as CategoryElement;
use craft\gql\base\Generator;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\ObjectType;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\elements\Category as CategoryInterface;
use craft\gql\types\elements\Category;
use craft\helpers\Gql as GqlHelper;

/**
 * Class CategoryType
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class CategoryType extends Generator implements GeneratorInterface, SingleGeneratorInterface
{
    /**
     * @inheritdoc
     */
    public static function generateTypes(mixed $context = null): array
    {
        $categoryGroups = Craft::$app->getCategories()->getAllGroups();
        $gqlTypes = [];

        foreach ($categoryGroups as $categoryGroup) {
            $requiredContexts = CategoryElement::gqlScopesByContext($categoryGroup);

            if (!GqlHelper::isSchemaAwareOf($requiredContexts)) {
                continue;
            }

            // Generate a type for each category group
            $type = static::generateType($categoryGroup);
            $gqlTypes[$type->name] = $type;
        }

        return $gqlTypes;
    }

    /**
     * @inheritdoc
     */
    public static function generateType(mixed $context): ObjectType
    {
        $typeName = CategoryElement::gqlTypeNameByContext($context);
        $contentFieldGqlTypes = self::getContentFields($context);

        $categoryGroupFields = array_merge(CategoryInterface::getFieldDefinitions(), $contentFieldGqlTypes);

        return GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new Category([
            'name' => $typeName,
            'fields' => function() use ($categoryGroupFields, $typeName) {
                return Craft::$app->getGql()->prepareFieldDefinitions($categoryGroupFields, $typeName);
            },
        ]));
    }
}
