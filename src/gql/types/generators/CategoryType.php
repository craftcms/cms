<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\generators;

use Craft;
use craft\base\Field;
use craft\elements\Category as CategoryElement;
use craft\gql\base\GeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\elements\Category as CategoryInterface;
use craft\gql\TypeManager;
use craft\gql\types\elements\Category;
use craft\helpers\Gql as GqlHelper;
use craft\models\CategoryGroup;

/**
 * Class CategoryType
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class CategoryType implements GeneratorInterface
{
    /**
     * @inheritdoc
     */
    public static function generateTypes($context = null): array
    {
        $categoryGroups = Craft::$app->getCategories()->getAllGroups();
        $gqlTypes = [];

        foreach ($categoryGroups as $categoryGroup) {
            /** @var CategoryGroup $categoryGroup */
            $typeName = CategoryElement::gqlTypeNameByContext($categoryGroup);
            $requiredContexts = CategoryElement::gqlScopesByContext($categoryGroup);

            if (!GqlHelper::isSchemaAwareOf($requiredContexts)) {
                continue;
            }

            $contentFields = $categoryGroup->getFields();
            $contentFieldGqlTypes = [];

            /** @var Field $contentField */
            foreach ($contentFields as $contentField) {
                $contentFieldGqlTypes[$contentField->handle] = $contentField->getContentGqlType();
            }

            $categoryGroupFields = TypeManager::prepareFieldDefinitions(array_merge(CategoryInterface::getFieldDefinitions(), $contentFieldGqlTypes), $typeName);

            // Generate a type for each entry type
            $gqlTypes[$typeName] = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new Category([
                'name' => $typeName,
                'fields' => function() use ($categoryGroupFields) {
                    return $categoryGroupFields;
                }
            ]));
        }

        return $gqlTypes;
    }
}
