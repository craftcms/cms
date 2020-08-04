<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\elements\Tag;
use craft\helpers\Db;
use craft\helpers\Search;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\models\TagGroup;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The TagsController class is a controller that handles various tag and tag group related tasks such as displaying,
 * saving, deleting, searching and creating tags and tag groups in the control panel.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class TagsController extends Controller
{
    /**
     * Called before displaying the tag settings index page.
     *
     * @return Response
     */
    public function actionIndex(): Response
    {
        $this->requireAdmin();

        $tagGroups = Craft::$app->getTags()->getAllTagGroups();

        return $this->renderTemplate('settings/tags/index', [
            'tagGroups' => $tagGroups
        ]);
    }

    /**
     * Edit a tag group.
     *
     * @param int|null $tagGroupId The tag group’s ID, if any.
     * @param TagGroup|null $tagGroup The tag group being edited, if there were any validation errors.
     * @return Response
     * @throws NotFoundHttpException if the requested tag group cannot be found
     */
    public function actionEditTagGroup(int $tagGroupId = null, TagGroup $tagGroup = null): Response
    {
        $this->requireAdmin();

        if ($tagGroupId !== null) {
            if ($tagGroup === null) {
                $tagGroup = Craft::$app->getTags()->getTagGroupById($tagGroupId);

                if (!$tagGroup) {
                    throw new NotFoundHttpException('Tag group not found');
                }
            }

            $title = trim($tagGroup->name) ?: Craft::t('app', 'Edit Tag Group');
        } else {
            if ($tagGroup === null) {
                $tagGroup = new TagGroup();
            }

            $title = Craft::t('app', 'Create a new tag group');
        }

        // Breadcrumbs
        $crumbs = [
            [
                'label' => Craft::t('app', 'Settings'),
                'url' => UrlHelper::url('settings')
            ],
            [
                'label' => Craft::t('app', 'Tags'),
                'url' => UrlHelper::url('settings/tags')
            ]
        ];

        return $this->renderTemplate('settings/tags/_edit', [
            'tagGroupId' => $tagGroupId,
            'tagGroup' => $tagGroup,
            'title' => $title,
            'crumbs' => $crumbs,
        ]);
    }

    /**
     * Save a tag group.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     */
    public function actionSaveTagGroup()
    {
        $this->requirePostRequest();
        $this->requireAdmin();

        $tagsService = Craft::$app->getTags();
        $groupId = $this->request->getBodyParam('tagGroupId');

        if ($groupId) {
            $group = $tagsService->getTagGroupById($groupId);
            if (!$group) {
                throw new BadRequestHttpException("Invalid tag group ID: $groupId");
            }
        } else {
            $group = new TagGroup();
        }

        // Set the simple stuff
        $group->name = $this->request->getBodyParam('name');
        $group->handle = $this->request->getBodyParam('handle');

        // Set the field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = Tag::class;
        $group->setFieldLayout($fieldLayout);

        // Save it
        if (!Craft::$app->getTags()->saveTagGroup($group)) {
            $this->setFailFlash(Craft::t('app', 'Couldn’t save the tag group.'));

            // Send the tag group back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'tagGroup' => $group
            ]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('app', 'Tag group saved.'));
        return $this->redirectToPostedUrl($group);
    }

    /**
     * Deletes a tag group.
     *
     * @return Response
     */
    public function actionDeleteTagGroup(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireAdmin();

        $groupId = $this->request->getRequiredBodyParam('id');

        Craft::$app->getTags()->deleteTagGroupById($groupId);

        return $this->asJson(['success' => true]);
    }

    /**
     * Searches for tags.
     *
     * @return Response
     */
    public function actionSearchForTags(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $search = trim($this->request->getBodyParam('search'));
        $tagGroupId = $this->request->getBodyParam('tagGroupId');
        $excludeIds = $this->request->getBodyParam('excludeIds', []);
        $allowSimilarTags = Craft::$app->getConfig()->getGeneral()->allowSimilarTags;

        $tags = Tag::find()
            ->groupId($tagGroupId)
            ->title(Db::escapeParam($search) . '*')
            ->all();

        $return = [];
        $exactMatches = [];
        $excludes = [];
        $tagTitleLengths = [];
        $exactMatch = false;

        if ($allowSimilarTags) {
            $search = Search::normalizeKeywords($search, [], false);
        } else {
            $search = Search::normalizeKeywords($search);
        }

        foreach ($tags as $tag) {
            $exclude = in_array($tag->id, $excludeIds, false);

            $return[] = [
                'id' => $tag->id,
                'title' => $tag->title,
                'exclude' => $exclude,
            ];

            $tagTitleLengths[] = StringHelper::length($tag->title);

            if ($allowSimilarTags) {
                $title = Search::normalizeKeywords($tag->title, [], false);
            } else {
                $title = Search::normalizeKeywords($tag->title);
            }

            if ($title == $search) {
                $exactMatches[] = 1;
                $exactMatch = true;
            } else {
                $exactMatches[] = 0;
            }

            $excludes[] = $exclude ? 1 : 0;
        }

        array_multisort($excludes, SORT_ASC, $exactMatches, SORT_DESC, $tagTitleLengths, $return);

        return $this->asJson([
            'tags' => $return,
            'exactMatch' => $exactMatch
        ]);
    }

    /**
     * Creates a new tag.
     *
     * @return Response
     * @throws BadRequestHttpException if the groupId param is missing or invalid
     */
    public function actionCreateTag(): Response
    {
        $this->requireAcceptsJson();

        $groupId = $this->request->getRequiredBodyParam('groupId');
        if (($group = Craft::$app->getTags()->getTagGroupById($groupId)) === null) {
            throw new BadRequestHttpException('Invalid tag group ID: ' . $groupId);
        }

        $tag = new Tag();
        $tag->groupId = $group->id;
        $tag->title = trim($this->request->getRequiredBodyParam('title'));

        // Don't validate required custom fields
        if (!Craft::$app->getElements()->saveElement($tag)) {
            return $this->asJson([
                'success' => false
            ]);
        }

        return $this->asJson([
            'success' => true,
            'id' => $tag->id
        ]);
    }
}
