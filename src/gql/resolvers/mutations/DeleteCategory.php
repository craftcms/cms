<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\mutations;

use Craft;
use craft\db\Table;
use craft\elements\Category;
use craft\gql\base\MutationResolver;
use craft\helpers\Db;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class DeleteCategory
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class DeleteCategory extends MutationResolver
{
    /**
     * @inheritdoc
     */
    public function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        $categoryId = $arguments['id'];

        $category = Category::findOne($categoryId);

        if (!$category) {
            return true;
        }

        $categoryGroupUid = Db::uidById(Table::CATEGORYGROUPS, $category->groupId);
        $this->requireSchemaAction('categorygroups.' . $categoryGroupUid, 'delete');

        Craft::$app->getElements()->deleteElementById($categoryId);

        return true;
    }
}
