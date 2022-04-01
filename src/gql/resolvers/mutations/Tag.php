<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\mutations;

use Craft;
use craft\db\Table;
use craft\elements\Tag as TagElement;
use craft\gql\base\ElementMutationResolver;
use craft\helpers\Db;
use craft\models\TagGroup;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use Throwable;

/**
 * Class SaveTag
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class Tag extends ElementMutationResolver
{
    /** @inheritdoc */
    protected array $immutableAttributes = ['id', 'uid', 'groupId'];

    /**
     * Save a tag using the passed arguments.
     *
     * @param mixed $source
     * @param array $arguments
     * @param mixed $context
     * @param ResolveInfo $resolveInfo
     * @return TagElement
     * @throws Throwable if reasons.
     */
    public function saveTag(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): TagElement
    {
        /** @var TagGroup $tagGroup */
        $tagGroup = $this->getResolutionData('tagGroup');
        $canIdentify = !empty($arguments['id']) || !empty($arguments['uid']);
        $elementService = Craft::$app->getElements();

        if ($canIdentify) {
            if (!empty($arguments['uid'])) {
                $tag = $elementService->createElementQuery(TagElement::class)->uid($arguments['uid'])->one();
            } else {
                $tag = $elementService->getElementById($arguments['id'], TagElement::class);
            }

            if (!$tag) {
                throw new Error('No such tag exists');
            }
        } else {
            $tag = $elementService->createElement(['type' => TagElement::class, 'groupId' => $tagGroup->id]);
        }

        if ($tag->groupId != $tagGroup->id) {
            throw new Error('Impossible to change the group of an existing tag');
        }

        $this->requireSchemaAction('taggroups.' . $tagGroup->uid, 'save');

        $tag = $this->populateElementWithData($tag, $arguments, $resolveInfo);
        $tag = $this->saveElement($tag);

        return $elementService->getElementById($tag->id, TagElement::class);
    }

    /**
     * Delete a tag identified by the arguments.
     *
     * @param mixed $source
     * @param array $arguments
     * @param mixed $context
     * @param ResolveInfo $resolveInfo
     * @throws Throwable if reasons.
     */
    public function deleteTag(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): void
    {
        $tagId = $arguments['id'];

        $elementService = Craft::$app->getElements();
        $tag = $elementService->getElementById($tagId, TagElement::class);

        if (!$tag) {
            return;
        }

        $tagGroupUid = Db::uidById(Table::TAGGROUPS, $tag->groupId);
        $this->requireSchemaAction('taggroups.' . $tagGroupUid, 'delete');

        $elementService->deleteElementById($tagId);
    }
}
