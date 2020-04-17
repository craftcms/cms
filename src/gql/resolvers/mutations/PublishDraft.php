<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\mutations;

use Craft;
use craft\elements\Entry;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class PublishDraft
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class PublishDraft extends CreateDraft
{
    /**
     * @inheritdoc
     */
    public function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        $draft = Entry::find()->anyStatus()->draftId($arguments['id'])->one();

        if (!$draft) {
            throw new Error('Unable to perform the action.');
        }

        $this->performSchemaCheck($draft);

        /** @var Entry $draft */
        $draft = Craft::$app->getDrafts()->applyDraft($draft);

        return $draft->id;
    }
}
