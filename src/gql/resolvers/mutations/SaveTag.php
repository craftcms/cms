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
use craft\elements\Tag;
use craft\errors\GqlException;
use craft\gql\base\MutationResolver;
use craft\helpers\Gql;
use craft\models\EntryType;
use craft\models\Section;
use craft\models\TagGroup;
use GraphQL\Error\Error;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class SaveTag
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class SaveTag extends MutationResolver
{
    /**
     * @inheritdoc
     */
    public function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        /** @var TagGroup $tagGroup */
        $tagGroup = $this->_getData('tagGroup');
        $canIdentify = !empty($arguments['id']);

        if ($canIdentify) {
            $tag = Tag::findOne($arguments['id']);
        } else {
            $tag = new Tag(['groupId' => $tagGroup->id]);
        }

        if ($tag->groupId != $tagGroup->id) {
            throw new Error('Impossible to change the group of an existing tag');
        }

        $this->requireSchemaAction('taggroups.' . $tagGroup->uid, 'save');

        $tag = $this->populateElementWithData($tag, $arguments);

        $this->saveElement($tag);

        return Tag::find()->anyStatus()->id($tag->id)->one();
    }
}
