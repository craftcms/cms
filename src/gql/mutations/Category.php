<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\mutations;

use Craft;
use craft\elements\Category as CategoryElement;
use craft\gql\arguments\mutations\Structure as StructureArguments;
use craft\gql\base\ElementMutationArguments;
use craft\gql\base\ElementMutationResolver;
use craft\gql\base\Mutation;
use craft\gql\resolvers\mutations\Category as CategoryResolver;
use craft\gql\types\generators\CategoryType;
use craft\helpers\Gql;
use craft\helpers\Gql as GqlHelper;
use craft\models\CategoryGroup;
use GraphQL\Type\Definition\Type;
use yii\base\InvalidConfigException;

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
                $mutation = static::createSaveMutation($categoryGroup);
                $mutationList[$mutation['name']] = $mutation;
            }

            if (!$createDeleteMutation && Gql::canSchema($scope, 'delete')) {
                $createDeleteMutation = true;
            }
        }

        if ($createDeleteMutation) {
            $mutationList['deleteCategory'] = [
                'name' => 'deleteCategory',
                'args' => ['id' => Type::nonNull(Type::int())],
                'resolve' => [Craft::createObject(CategoryResolver::class), 'deleteCategory'],
                'description' => 'Delete a category.',
                'type' => Type::boolean(),
            ];
        }

        return $mutationList;
    }

    /**
     * Create the per-category-group save mutation.
     *
     * @param CategoryGroup $categoryGroup
     * @return array
     * @throws InvalidConfigException
     */
    public static function createSaveMutation(CategoryGroup $categoryGroup): array
    {
        $mutationName = CategoryElement::gqlMutationNameByContext($categoryGroup);
        $mutationArguments = array_merge(ElementMutationArguments::getArguments(), StructureArguments::getArguments());
        $generatedType = CategoryType::generateType($categoryGroup);

        /** @var CategoryResolver $resolver */
        $resolver = Craft::createObject(CategoryResolver::class);
        $resolver->setResolutionData('categoryGroup', $categoryGroup);
        static::prepareResolver($resolver, $categoryGroup->getCustomFields());

        $mutationArguments = array_merge($mutationArguments, $resolver->getResolutionData(ElementMutationResolver::CONTENT_FIELD_KEY));

        return [
            'name' => $mutationName,
            'description' => 'Save the “' . $categoryGroup->name . '” category.',
            'args' => $mutationArguments,
            'resolve' => [$resolver, 'saveCategory'],
            'type' => $generatedType,
        ];
    }
}
