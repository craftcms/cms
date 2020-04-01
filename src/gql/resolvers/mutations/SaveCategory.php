<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\mutations;

use Craft;
use craft\base\Element;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;
use craft\elements\Entry as EntryElement;
use craft\elements\Category;
use craft\errors\GqlException;
use craft\gql\base\MutationResolver;
use craft\helpers\Gql;
use craft\models\EntryType;
use craft\models\Section;
use craft\models\CategoryGroup;
use GraphQL\Error\Error;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class SaveCategory
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class SaveCategory extends MutationResolver
{
    /**
     * @inheritdoc
     */
    public function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        /** @var CategoryGroup $categoryGroup */
        $categoryGroup = $this->_getData('categoryGroup');
        $canIdentify = !empty($arguments['id']);

        if ($canIdentify) {
            $category = Category::findOne($arguments['id']);
        } else {
            $category = new Category(['groupId' => $categoryGroup->id]);
        }

        if ($category->groupId != $categoryGroup->id) {
            throw new Error('Impossible to change the group of an existing category');
        }

        $this->requireSchemaAction('categorygroups.' . $categoryGroup->uid, 'save');

        $category = $this->populateElementWithData($category, $arguments);

        $this->saveElement($category);

        return Category::find()->anyStatus()->id($category->id)->one();
    }
}
