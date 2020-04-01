<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\mutations;

use Craft;
use craft\base\Field;
use craft\elements\Category as CategoryElement;
use craft\gql\arguments\mutations\Category as CategoryMutationArguments;
use craft\gql\arguments\mutations\Structure as StructureArguments;
use craft\gql\base\ElementMutationArguments;
use craft\gql\base\Mutation;
use craft\gql\resolvers\mutations\DeleteCategory;
use craft\gql\resolvers\mutations\SaveCategory;
use craft\gql\types\generators\CategoryType;
use craft\helpers\Gql;
use craft\helpers\Gql as GqlHelper;
use craft\models\EntryType as EntryTypeModel;
use craft\models\CategoryGroup;
use GraphQL\Type\Definition\Type;

/**
 * Class Category
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class Category extends Mutation
{
    /**
     * @inheritdoc
     */
    public static function getMutations(): array
    {
        if (!GqlHelper::canMutateCategories()) {
            return [];
        }

        $mutationList = [];

        $createDeleteMutation = false;

        foreach (Craft::$app->getCategories()->getAllGroups() as $categoryGroup) {
            $scope = 'categorygroups.' . $categoryGroup->uid;

            if (Gql::canSchema($scope, 'save')) {
                // Create a mutation for each category group
                foreach (static::createSaveMutation($categoryGroup) as $mutation) {
                    $mutationList[$mutation['name']] = $mutation;
                }
            }

            if (!$createDeleteMutation && Gql::canSchema($scope, 'delete')) {
                $createDeleteMutation = true;
            }
        }

        if ($createDeleteMutation) {
            $mutationList['deleteCategory'] = [
                'name' => 'deleteCategory',
                'args' => ['id' => Type::nonNull(Type::int())],
                'resolve' => [new DeleteCategory(), 'resolve'],
                'description' => 'Delete a category.',
                'type' => Type::boolean()
            ];
        }

        return $mutationList;
    }

    /**
     * Create the per-category-group save mutation.
     *
     * @param EntryTypeModel $categoryGroup
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    protected static function createSaveMutation(CategoryGroup $categoryGroup): array
    {
        $mutationName = CategoryElement::gqlMutationNameByContext($categoryGroup);
        $contentFields = $categoryGroup->getFields();
        $mutationArguments = array_merge(ElementMutationArguments::getArguments(), StructureArguments::getArguments());
        $contentFieldHandles = [];
        $valueNormalizers = [];

        /** @var Field $contentField */
        foreach ($contentFields as $contentField) {
            $contentFieldType = $contentField->getContentGqlArgumentType();
            $mutationArguments[$contentField->handle] = $contentFieldType;
            $contentFieldHandles[$contentField->handle] = true;

            $configArray = is_array($contentFieldType) ? $contentFieldType : $contentFieldType->config;

            if (is_array($configArray) && !empty($configArray['normalizeValue'])) {
                $valueNormalizers[$contentField->handle] = $configArray['normalizeValue'];
            }
        }

        $description = 'Save the “' . $categoryGroup->name . '” category.';

        $resolverData = [
            'categoryGroup' => $categoryGroup,
            'contentFieldHandles' => $contentFieldHandles,
        ];

        $generatedType = CategoryType::generateType($categoryGroup);

        $mutation[] = [
            'name' => $mutationName,
            'description' => $description,
            'args' => $mutationArguments,
            'resolve' => [new SaveCategory($resolverData, $valueNormalizers), 'resolve'],
            'type' => $generatedType
        ];

        return $mutation;
    }
}
