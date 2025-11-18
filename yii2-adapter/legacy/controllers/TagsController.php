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
use craft\helpers\UrlHelper;
use craft\models\TagGroup;
use craft\web\Controller;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Support\Search;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use function CraftCms\Cms\t;

/**
 * The TagsController class is a controller that handles various tag and tag group related tasks such as displaying,
 * saving, deleting, searching and creating tags and tag groups in the control panel.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 6.0.0
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
        $this->requireAdmin(false);

        $tagGroups = Craft::$app->getTags()->getAllTagGroups();

        return $this->renderTemplate('yii2-adapter/settings/tags/index.twig', [
            'tagGroups' => $tagGroups,
            'readOnly' => !Cms::config()->allowAdminChanges,
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
    public function actionEditTagGroup(?int $tagGroupId = null, ?TagGroup $tagGroup = null): Response
    {
        $this->requireAdmin(false);

        $readOnly = !Cms::config()->allowAdminChanges;

        if ($tagGroupId === null && $readOnly) {
            throw new ForbiddenHttpException('Administrative changes are disallowed in this environment.');
        }


        if ($tagGroupId !== null) {
            if ($tagGroup === null) {
                $tagGroup = Craft::$app->getTags()->getTagGroupById($tagGroupId);

                if (!$tagGroup) {
                    throw new NotFoundHttpException('Tag group not found');
                }
            }

            $title = trim($tagGroup->name) ?: t('Edit Tag Group', category: 'yii2-adapter');
        } else {
            if ($tagGroup === null) {
                $tagGroup = new TagGroup();
            }

            $title = t('Create a new tag group', category: 'yii2-adapter');
        }

        // Breadcrumbs
        $crumbs = [
            [
                'label' => t('Settings'),
                'url' => UrlHelper::url('settings'),
            ],
            [
                'label' => t('Tags', category: 'yii2-adapter'),
                'url' => UrlHelper::url('settings/tags'),
            ],
        ];

        return $this->renderTemplate('yii2-adapter/settings/tags/_edit.twig', [
            'tagGroupId' => $tagGroupId,
            'tagGroup' => $tagGroup,
            'title' => $title,
            'crumbs' => $crumbs,
            'readOnly' => $readOnly,
        ]);
    }

    /**
     * Save a tag group.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     */
    public function actionSaveTagGroup(): ?Response
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
        $fieldLayout = app(Fields::class)->assembleLayoutFromPost();
        $fieldLayout->type = Tag::class;
        $group->setFieldLayout($fieldLayout);

        // Save it
        if (!Craft::$app->getTags()->saveTagGroup($group)) {
            $this->setFailFlash(t('Couldn’t save the tag group.', category: 'yii2-adapter'));

            // Send the tag group back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'tagGroup' => $group,
            ]);

            return null;
        }

        $this->setSuccessFlash(t('Tag group saved.', category: 'yii2-adapter'));
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

        return $this->asSuccess();
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
        $allowSimilarTags = Cms::config()->allowSimilarTags;

        /** @var Tag[] $tags */
        $tags = Tag::find()
            ->groupId($tagGroupId)
            ->title(Db::escapeParam($search) . '*')
            ->orderBy(['LENGTH([[title]])' => SORT_ASC])
            ->limit(5)
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

            $tagTitleLengths[] = mb_strlen($tag->title);

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
            'exactMatch' => $exactMatch,
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
            return $this->asFailure();
        }

        return $this->asSuccess(data: [
            'id' => $tag->id,
        ]);
    }
}
