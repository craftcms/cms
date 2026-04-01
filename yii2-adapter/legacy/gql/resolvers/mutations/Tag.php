<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\mutations;

use craft\elements\Tag as TagElement;
use craft\gql\base\ElementMutationResolver;
use craft\models\TagGroup;
use CraftCms\Cms\Support\Facades\Elements;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Class SaveTag
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 * @deprecated in 6.0.0
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

        if ($canIdentify) {
            if (!empty($arguments['uid'])) {
                $tag = Elements::createElementQuery(TagElement::class)->uid($arguments['uid'])->one();
            } else {
                $tag = Elements::getElementById($arguments['id'], TagElement::class);
            }

            if (!$tag) {
                throw new Error('No such tag exists');
            }
        } else {
            $tag = Elements::createElement(['type' => TagElement::class, 'groupId' => $tagGroup->id]);
        }

        /** @var \craft\elements\Tag $tag */
        if ($tag->groupId != $tagGroup->id) {
            throw new Error('Impossible to change the group of an existing tag');
        }

        $this->requireSchemaAction('taggroups.' . $tagGroup->uid, 'save');

        $tag = $this->populateElementWithData($tag, $arguments, $resolveInfo);
        $tag = $this->saveElement($tag);

        return Elements::getElementById($tag->id, TagElement::class);
    }

    /**
     * Delete a tag identified by the arguments.
     *
     * @param mixed $source
     * @param array $arguments
     * @param mixed $context
     * @param ResolveInfo $resolveInfo
     * @return bool
     * @throws Throwable if reasons.
     */
    public function deleteTag(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): bool
    {
        $tagId = $arguments['id'];

        $tag = Elements::getElementById($tagId, TagElement::class);

        if (!$tag) {
            return false;
        }

        $tagGroupUid = DB::table('taggroups')->uidById($tag->groupId);
        $this->requireSchemaAction('taggroups.' . $tagGroupUid, 'delete');

        return Elements::deleteElementById($tagId);
    }
}
