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

		$tagSets = craft()->tags->getAllTagSets();

		$this->renderTemplate('settings/tags/index', array(
			'tagSets' => $tagSets
		));
	}

	/**
	 * Edit a tag set.
	 *
	 * @param array $variables
	 */
	public function actionEditTagSet(array $variables = array())
	{
		craft()->userSession->requireAdmin();

		if (empty($variables['tagSet']))
		{
			if (!empty($variables['tagSetId']))
			{
				$variables['tagSet'] = craft()->tags->getTagSetById($variables['tagSetId']);

				if (!$variables['tagSet'])
				{
					throw new HttpException(404);
				}
			}
			else
			{
				$variables['tagSet'] = new TagSetModel();
			}
		}

		if ($variables['tagSet']->id)
		{
			$variables['title'] = $variables['tagSet']->name;
		}
		else
		{
			$variables['title'] = Craft::t('Create a new tag set');
		}

		$variables['tabs'] = array(
			'settings'    => array('label' => Craft::t('Settings'), 'url' => '#tagset-settings'),
			'fieldLayout' => array('label' => Craft::t('Field Layout'), 'url' => '#tagset-fieldlayout')
		);

		$this->renderTemplate('settings/tags/_edit', $variables);
	}

	/**
	 * Save a tag set.
	 */
	public function actionSaveTagSet()
	{
		$this->requirePostRequest();
		craft()->userSession->requireAdmin();

		$tagSet = new TagSetModel();

		// Set the simple stuff
		$tagSet->id     = craft()->request->getPost('tagSetId');
		$tagSet->name   = craft()->request->getPost('name');
		$tagSet->handle = craft()->request->getPost('handle');

		// Set the field layout
		$fieldLayout = craft()->fields->assembleLayoutFromPost(false);
		$fieldLayout->type = ElementType::Tag;
		$tagSet->setFieldLayout($fieldLayout);

		// Save it
		if (craft()->tags->saveTagSet($tagSet))
		{
			craft()->userSession->setNotice(Craft::t('Tag set saved.'));
			$this->redirectToPostedUrl($tagSet);
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save the tag set.'));
		}

		// Send the tag set back to the template
		craft()->urlManager->setRouteVariables(array(
			'tagSet' => $tagSet
		));
	}

	/**
	 * Deletes a tag set.
	 */
	public function actionDeleteTagSet()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();
		craft()->userSession->requireAdmin();

		$sectionId = craft()->request->getRequiredPost('id');

		craft()->tags->deleteTagSetById($sectionId);
		$this->returnJson(array('success' => true));
	}
}
