<?php
namespace Craft;

/**
 *
 */
class TagsController extends BaseController
{
	/**
	 * Tag settings index.
	 */
	public function actionIndex()
	{
		craft()->userSession->requireAdmin();

		$tagGroups = craft()->tags->getAllTagGroups();

		$this->renderTemplate('settings/tags/index', array(
			'tagGroups' => $tagGroups
		));
	}

	/**
	 * Edit a tag group.
	 *
	 * @param array $variables
	 */
	public function actionEditTagGroup(array $variables = array())
	{
		craft()->userSession->requireAdmin();

		// Breadcrumbs
		$variables['crumbs'] = array(
			array('label' => Craft::t('Settings'), 'url' => UrlHelper::getUrl('settings')),
			array('label' => Craft::t('Tags'),  'url' => UrlHelper::getUrl('settings/tags'))
		);

		if (!empty($variables['tagGroupId']))
		{
			if (empty($variables['tagGroup']))
			{
				$variables['tagGroup'] = craft()->tags->getTagGroupById($variables['tagGroupId']);

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

			$variables['title'] = Craft::t('Create a new tag group');
		}

		$variables['tabs'] = array(
			'settings'    => array('label' => Craft::t('Settings'), 'url' => '#taggroup-settings'),
			'fieldLayout' => array('label' => Craft::t('Field Layout'), 'url' => '#taggroup-fieldlayout')
		);

		$this->renderTemplate('settings/tags/_edit', $variables);
	}

	/**
	 * Save a tag group.
	 */
	public function actionSaveTagGroup()
	{
		$this->requirePostRequest();
		craft()->userSession->requireAdmin();

		$tagGroup = new TagGroupModel();

		// Set the simple stuff
		$tagGroup->id     = craft()->request->getPost('tagGroupId');
		$tagGroup->name   = craft()->request->getPost('name');
		$tagGroup->handle = craft()->request->getPost('handle');

		// Set the field layout
		$fieldLayout = craft()->fields->assembleLayoutFromPost(false);
		$fieldLayout->type = ElementType::Tag;
		$tagGroup->setFieldLayout($fieldLayout);

		// Save it
		if (craft()->tags->saveTagGroup($tagGroup))
		{
			craft()->userSession->setNotice(Craft::t('Tag group saved.'));
			$this->redirectToPostedUrl($tagGroup);
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save the tag group.'));
		}

		// Send the tag group back to the template
		craft()->urlManager->setRouteVariables(array(
			'tagGroup' => $tagGroup
		));
	}

	/**
	 * Deletes a tag group.
	 */
	public function actionDeleteTagGroup()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();
		craft()->userSession->requireAdmin();

		$sectionId = craft()->request->getRequiredPost('id');

		craft()->tags->deleteTagGroupById($sectionId);
		$this->returnJson(array('success' => true));
	}

	/**
	 * Searches for tags.
	 */
	public function actionSearchForTags()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$search = craft()->request->getPost('search');
		$tagGroupId = craft()->request->getPost('tagGroupId');
		$excludeIds = craft()->request->getPost('excludeIds', array());

		$notIds = array('and');

		foreach ($excludeIds as $id)
		{
			$notIds[] = 'not '.$id;
		}

		$criteria = craft()->elements->getCriteria(ElementType::Tag);
		$criteria->groupId = $tagGroupId;
		$criteria->search  = 'name:'.implode('* name:', preg_split('/\s+/', $search)).'*';
		$criteria->id      = $notIds;
		$tags = $criteria->find();

		$return = array();
		$exactMatches = array();
		$tagNameLengths = array();
		$exactMatch = false;

		$normalizedSearch = StringHelper::normalizeKeywords($search);

		foreach ($tags as $tag)
		{
			$return[] = array(
				'id'   => $tag->id,
				'name' => $tag->name
			);

			$tagNameLengths[] = mb_strlen($tag->name);

			$normalizedName = StringHelper::normalizeKeywords($tag->name);

			if ($normalizedName == $normalizedSearch)
			{
				$exactMatches[] = 1;
				$exactMatch = true;
			}
			else
			{
				$exactMatches[] = 0;
			}
		}

		array_multisort($exactMatches, SORT_DESC, $tagNameLengths, $return);

		$this->returnJson(array(
			'tags'       => $return,
			'exactMatch' => $exactMatch
		));
	}

	/**
	 * Creates a new tag.
	 */
	public function actionCreateTag()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();

		$tag = new TagModel();
		$tag->groupId = craft()->request->getRequiredPost('groupId');
		$tag->name = craft()->request->getRequiredPost('name');

		if (craft()->tags->saveTag($tag))
		{
			$this->returnJson(array(
				'success' => true,
				'id'      => $tag->id
			));
		}
		else
		{
			$this->returnJson(array(
				'success' => false
			));
		}
	}
}
