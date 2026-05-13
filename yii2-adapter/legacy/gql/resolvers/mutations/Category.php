<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\mutations;

use craft\elements\Category as CategoryElement;
use craft\gql\base\ElementMutationResolver;
use craft\gql\base\StructureMutationTrait;
use craft\models\CategoryGroup;
use CraftCms\Cms\Support\Facades\Elements;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Class Category
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 * @deprecated in 6.0.0
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

        if ($canIdentify) {
            if (!empty($arguments['uid'])) {
                $category = Elements::createElementQuery(CategoryElement::class)->uid($arguments['uid'])->one();
            } else {
                $category = Elements::getElementById($arguments['id'], CategoryElement::class);
            }

            if (!$category) {
                throw new Error('No such category exists');
            }
        } else {
            $category = Elements::createElement(['type' => CategoryElement::class, 'groupId' => $categoryGroup->id]);
        }

        /** @var \craft\elements\Category $category */
        if ($category->groupId != $categoryGroup->id) {
            throw new Error('Impossible to change the group of an existing category');
        }

        $this->requireSchemaAction('categorygroups.' . $categoryGroup->uid, 'save');

        $category = $this->populateElementWithData($category, $arguments, $resolveInfo);

        $category = $this->saveElement($category);

        $this->performStructureOperations($category, $arguments);

        /** @var ?CategoryElement $category */
        $category = Elements::getElementById($category->id, CategoryElement::class);

        return $category;
    }

    /**
     * Delete a category identified by the arguments.
     *
     * @param mixed $source
     * @param array $arguments
     * @param mixed $context
     * @param ResolveInfo $resolveInfo
     * @return bool
     * @throws Throwable if reasons.
     */
    public function deleteCategory(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): bool
    {
        $categoryId = $arguments['id'];
        $hardDelete = $arguments['hardDelete'] ?? false;

        $category = Elements::getElementById($categoryId, CategoryElement::class);

        if (!$category) {
            return false;
        }

        $categoryGroupUid = DB::table('categorygroups')->uidById($category->groupId);
        $this->requireSchemaAction('categorygroups.' . $categoryGroupUid, 'delete');

        return Elements::deleteElementById($categoryId, hardDelete: $hardDelete);
    }
}
