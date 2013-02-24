<?php
namespace Craft;

/**
 * Globals controller class
 */
class GlobalsController extends BaseController
{
	/**
	 * Saves the global field layout.
	 */
	public function actionSaveFieldLayout()
	{
		$this->requirePostRequest();
		craft()->userSession->requireAdmin();

		// Set the field layout
		$fieldLayout = craft()->fields->assembleLayoutFromPost(false);
		$fieldLayout->type = ElementType::Globals;
		craft()->fields->deleteLayoutsByType(ElementType::Globals);

		if (craft()->fields->saveLayout($fieldLayout, false))
		{
			craft()->userSession->setNotice(Craft::t('Global fields saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save global fields.'));
		}

		$this->renderRequestedTemplate();
	}

	/**
	 * Saves the global fields.
	 */
	public function actionSaveContent()
	{
		$this->requirePostRequest();
		craft()->userSession->requirePermission('editGlobals');

		$content = craft()->globals->getGlobalContent();
		$content->setContent(craft()->request->getPost('fields'));

		if (craft()->globals->saveGlobalContent($content))
		{
			craft()->userSession->setNotice(Craft::t('Global fields saved.'));

			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save global fields.'));

			$this->renderRequestedTemplate(array(
				'globals' => $content,
			));
		}
	}
}
