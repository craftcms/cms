<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\mutations;

use craft\elements\Category;
use craft\gql\base\ElementMutationResolver;
use craft\gql\base\StructureMutationTrait;
use craft\models\CategoryGroup;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class SaveCategory
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class SaveCategory extends ElementMutationResolver
{
    use StructureMutationTrait;

    /**
     * @inheritdoc
     */
    public function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        /** @var CategoryGroup $categoryGroup */
        $categoryGroup = $this->_getData('categoryGroup');

        $canIdentify = !empty($arguments['id'] || !empty($arguments['uid']));

        if ($canIdentify) {
            if (!empty($arguments['uid'])) {
                $category = Category::findOne(['uid' => $arguments['uid']]);
            } else {
                $category = Category::findOne($arguments['id']);
            }
        } else {
            $category = new Category(['groupId' => $categoryGroup->id]);
        }

        if ($category->groupId != $categoryGroup->id) {
            throw new Error('Impossible to change the group of an existing category');
        }

        $this->requireSchemaAction('categorygroups.' . $categoryGroup->uid, 'save');

        $category = $this->populateElementWithData($category, $arguments);

        $this->saveElement($category);

        $this->performStructureOperations($category, $arguments);

        return Category::find()->anyStatus()->id($category->id)->one();
    }
}
