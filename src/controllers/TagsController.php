<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\enums\ElementType;
use craft\app\errors\HttpException;
use craft\app\helpers\DbHelper;
use craft\app\helpers\StringHelper;
use craft\app\helpers\UrlHelper;
use craft\app\models\Tag as TagModel;
use craft\app\models\TagGroup as TagGroupModel;
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
	 * @return null
	 */
	public function actionIndex()
	{
		$this->requireAdmin();

		$tagGroups = Craft::$app->tags->getAllTagGroups();

		$this->renderTemplate('settings/tags/index', [
			'tagGroups' => $tagGroups
		]);
	}

	/**
	 * Edit a tag group.
	 *
	 * @param array $variables
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function actionEditTagGroup(array $variables = [])
	{
		$this->requireAdmin();

		// Breadcrumbs
		$variables['crumbs'] = [
			['label' => Craft::t('app', 'Settings'), 'url' => UrlHelper::getUrl('settings')],
			['label' => Craft::t('app', 'Tags'),  'url' => UrlHelper::getUrl('settings/tags')]
		];

		if (!empty($variables['tagGroupId']))
		{
			if (empty($variables['tagGroup']))
			{
				$variables['tagGroup'] = Craft::$app->tags->getTagGroupById($variables['tagGroupId']);

				if (!$variables['tagGroup'])
				{
					throw new HttpException(404);
				}
			}

			$variables['title'] = $variables['tagGroup']->name;
		}
		else
		{
			if (empty($variables['tagGroup']))
			{
				$variables['tagGroup'] = new TagGroupModel();
			}

			$variables['title'] = Craft::t('app', 'Create a new tag group');
		}

		$variables['tabs'] = [
			'settings'    => ['label' => Craft::t('app', 'Settings'), 'url' => '#taggroup-settings'],
			'fieldLayout' => ['label' => Craft::t('app', 'Field Layout'), 'url' => '#taggroup-fieldlayout']
		];

		$this->renderTemplate('settings/tags/_edit', $variables);
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

		$tagGroup = new TagGroupModel();

		// Set the simple stuff
		$tagGroup->id     = Craft::$app->getRequest()->getBodyParam('tagGroupId');
		$tagGroup->name   = Craft::$app->getRequest()->getBodyParam('name');
		$tagGroup->handle = Craft::$app->getRequest()->getBodyParam('handle');

		// Set the field layout
		$fieldLayout = Craft::$app->fields->assembleLayoutFromPost();
		$fieldLayout->type = ElementType::Tag;
		$tagGroup->setFieldLayout($fieldLayout);

		// Save it
		if (Craft::$app->tags->saveTagGroup($tagGroup))
		{
			Craft::$app->getSession()->setNotice(Craft::t('app', 'Tag group saved.'));
			$this->redirectToPostedUrl($tagGroup);
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save the tag group.'));
		}

		// Send the tag group back to the template
		Craft::$app->getUrlManager()->setRouteVariables([
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

		Craft::$app->tags->deleteTagGroupById($sectionId);
		$this->returnJson(['success' => true]);
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

		$notIds = ['and'];

		foreach ($excludeIds as $id)
		{
			$notIds[] = 'not '.$id;
		}

		$criteria = Craft::$app->elements->getCriteria(ElementType::Tag);
		$criteria->groupId = $tagGroupId;
		$criteria->title   = DbHelper::escapeParam($search).'*';
		$criteria->id      = $notIds;
		$tags = $criteria->find();

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

		$this->returnJson([
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

		$tag = new TagModel();
		$tag->groupId = Craft::$app->getRequest()->getRequiredBodyParam('groupId');
		$tag->getContent()->title = Craft::$app->getRequest()->getRequiredBodyParam('title');

		if (Craft::$app->tags->saveTag($tag))
		{
			$this->returnJson([
				'success' => true,
				'id'      => $tag->id
			]);
		}
		else
		{
			$this->returnJson([
				'success' => false
			]);
		}
	}
}
