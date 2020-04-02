<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\mutations;

use Craft;
use craft\db\Table;
use craft\elements\Tag;
use craft\gql\base\MutationResolver;
use craft\helpers\Db;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class DeleteTag
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class DeleteTag extends MutationResolver
{
    /**
     * @inheritdoc
     */
    public function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        $tagId = $arguments['id'];

        $tag = Tag::findOne($tagId);

        if (!$tag) {
            return true;
        }

        $tagGroupUid = Db::uidById(Table::TAGGROUPS, $tag->groupId);
        $this->requireSchemaAction('taggroups.' . $tagGroupUid, 'delete');

        Craft::$app->getElements()->deleteElementById($tagId);

        return true;
    }
}
