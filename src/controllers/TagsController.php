<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\errors\HttpException;
use craft\app\helpers\DbHelper;
use craft\app\helpers\SearchHelper;
use craft\app\helpers\StringHelper;
use craft\app\helpers\UrlHelper;
use craft\app\elements\Tag;
use craft\app\models\TagGroup;
use craft\app\web\Controller;

/**
 * The TagsController class is a controller that handles various tag and tag group related tasks such as displaying,
 * saving, deleting, searching and creating tags and tag groups in the control panel.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class TagsController extends Controller
{
	// Public Methods
	// =========================================================================

	/**
	 * Called before displaying the tag settings index page.
	 *
	 * @return string The rendering result
	 */
	public function actionIndex()
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
	 * @param int      $tagGroupId The tag group’s ID, if any.
	 * @param TagGroup $tagGroup   The tag group being edited, if there were any validation errors.
	 * @return string The rendering result
	 * @throws HttpException
	 */
	public function actionEditTagGroup($tagGroupId = null, TagGroup $tagGroup = null)
	{
		$this->requireAdmin();

		if ($tagGroupId !== null)
		{
			if ($tagGroup === null)
			{
				$tagGroup = Craft::$app->getTags()->getTagGroupById($tagGroupId);

				if (!$tagGroup)
				{
					throw new HttpException(404);
				}
			}

			$title = $tagGroup->name;
		}
		else
		{
			if ($tagGroup === null)
			{
				$tagGroup = new TagGroup();
			}

			$title = Craft::t('app', 'Create a new tag group');
		}

		// Breadcrumbs
		$crumbs = [
			['label' => Craft::t('app', 'Settings'), 'url' => UrlHelper::getUrl('settings')],
			['label' => Craft::t('app', 'Tags'),  'url' => UrlHelper::getUrl('settings/tags')]
		];

		// Tabs
		$tabs = [
			'settings'    => ['label' => Craft::t('app', 'Settings'), 'url' => '#taggroup-settings'],
			'fieldLayout' => ['label' => Craft::t('app', 'Field Layout'), 'url' => '#taggroup-fieldlayout']
		];

		return $this->renderTemplate('settings/tags/_edit', [
			'tagGroupId' => $tagGroupId,
			'tagGroup' => $tagGroup,
			'title' => $title,
			'crumbs' => $crumbs,
			'tabs' => $tabs
		]);
	}

	/**
	 * Save a tag group.
	 *
	 * @return null
	 */
	public function actionSaveTagGroup()
	{
		$this->requirePostRequest();
		$this->requireAdmin();

		$tagGroup = new TagGroup();

		// Set the simple stuff
		$tagGroup->id     = Craft::$app->getRequest()->getBodyParam('tagGroupId');
		$tagGroup->name   = Craft::$app->getRequest()->getBodyParam('name');
		$tagGroup->handle = Craft::$app->getRequest()->getBodyParam('handle');

		// Set the field layout
		$fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
		$fieldLayout->type = Tag::className();
		$tagGroup->setFieldLayout($fieldLayout);

		// Save it
		if (Craft::$app->getTags()->saveTagGroup($tagGroup))
		{
			Craft::$app->getSession()->setNotice(Craft::t('app', 'Tag group saved.'));
			return $this->redirectToPostedUrl($tagGroup);
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save the tag group.'));
		}

		// Send the tag group back to the template
		Craft::$app->getUrlManager()->setRoute([
			'tagGroup' => $tagGroup
		]);
	}

	/**
	 * Deletes a tag group.
	 *
	 * @return null
	 */
	public function actionDeleteTagGroup()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();
		$this->requireAdmin();

		$sectionId = Craft::$app->getRequest()->getRequiredBodyParam('id');

		Craft::$app->getTags()->deleteTagGroupById($sectionId);
		return $this->asJson(['success' => true]);
	}

	/**
	 * Searches for tags.
	 *
	 * @return null
	 */
	public function actionSearchForTags()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$search = Craft::$app->getRequest()->getBodyParam('search');
		$tagGroupId = Craft::$app->getRequest()->getBodyParam('tagGroupId');
		$excludeIds = Craft::$app->getRequest()->getBodyParam('excludeIds', []);

		$tags = Tag::find()
			->groupId($tagGroupId)
			->title(DbHelper::escapeParam($search).'*')
			->where(['not in', 'elements.id', $excludeIds])
			->all();

		$return          = [];
		$exactMatches    = [];
		$tagTitleLengths = [];
		$exactMatch      = false;

		$normalizedSearch = SearchHelper::normalizeKeywords($search);

		foreach ($tags as $tag)
		{
			$return[] = [
				'id'    => $tag->id,
				'title' => $tag->getContent()->title
			];

			$tagTitleLengths[] = StringHelper::length($tag->getContent()->title);

			$normalizedTitle = SearchHelper::normalizeKeywords($tag->getContent()->title);

			if ($normalizedTitle == $normalizedSearch)
			{
				$exactMatches[] = 1;
				$exactMatch = true;
			}
			else
			{
				$exactMatches[] = 0;
			}
		}

		array_multisort($exactMatches, SORT_DESC, $tagTitleLengths, $return);

		return $this->asJson([
			'tags'       => $return,
			'exactMatch' => $exactMatch
		]);
	}

	/**
	 * Creates a new tag.
	 *
	 * @return null
	 */
	public function actionCreateTag()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();

		$tag = new Tag();
		$tag->groupId = Craft::$app->getRequest()->getRequiredBodyParam('groupId');
		$tag->getContent()->title = Craft::$app->getRequest()->getRequiredBodyParam('title');

		if (Craft::$app->getTags()->saveTag($tag))
		{
			return $this->asJson([
				'success' => true,
				'id'      => $tag->id
			]);
		}
		else
		{
			return $this->asJson([
				'success' => false
			]);
		}
	}
}
