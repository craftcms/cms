<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\mutations;

use Craft;
use craft\db\Table;
use craft\elements\Category as CategoryElement;
use craft\gql\base\ElementMutationResolver;
use craft\gql\base\StructureMutationTrait;
use craft\helpers\Db;
use craft\models\CategoryGroup;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use Throwable;

/**
 * Class Category
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class Category extends ElementMutationResolver
{
    use StructureMutationTrait;

    /** @inheritdoc */
    protected array $immutableAttributes = ['id', 'uid', 'groupId'];

    /**
     * Save a category using the passed arguments.
     *
     * @param mixed $source
     * @param array $arguments
     * @param mixed $context
     * @param ResolveInfo $resolveInfo
     * @return CategoryElement
     * @throws Throwable if reasons.
     */
    public function saveCategory(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): CategoryElement
    {
        /** @var CategoryGroup $categoryGroup */
        $categoryGroup = $this->getResolutionData('categoryGroup');
        $canIdentify = !empty($arguments['id']) || !empty($arguments['uid']);
        $elementService = Craft::$app->getElements();

        if ($canIdentify) {
            if (!empty($arguments['uid'])) {
                $category = $elementService->createElementQuery(CategoryElement::class)->uid($arguments['uid'])->one();
            } else {
                $category = $elementService->getElementById($arguments['id'], CategoryElement::class);
            }

            if (!$category) {
                throw new Error('No such category exists');
            }
        } else {
            $category = $elementService->createElement(['type' => CategoryElement::class, 'groupId' => $categoryGroup->id]);
        }

        if ($category->groupId != $categoryGroup->id) {
            throw new Error('Impossible to change the group of an existing category');
        }

        $this->requireSchemaAction('categorygroups.' . $categoryGroup->uid, 'save');

        $category = $this->populateElementWithData($category, $arguments, $resolveInfo);

        $category = $this->saveElement($category);

        $this->performStructureOperations($category, $arguments);

        return $elementService->getElementById($category->id, CategoryElement::class);
    }

    /**
     * Delete a category identified by the arguments.
     *
     * @param mixed $source
     * @param array $arguments
     * @param mixed $context
     * @param ResolveInfo $resolveInfo
     * @throws Throwable if reasons.
     */
    public function deleteCategory(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): void
    {
        $categoryId = $arguments['id'];

        $elementService = Craft::$app->getElements();
        $category = $elementService->getElementById($categoryId, CategoryElement::class);

        if (!$category) {
            return;
        }

        $categoryGroupUid = Db::uidById(Table::CATEGORYGROUPS, $category->groupId);
        $this->requireSchemaAction('categorygroups.' . $categoryGroupUid, 'delete');

        $elementService->deleteElementById($categoryId);
    }
}
