<?php
namespace Blocks;

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
		blx()->userSession->requireAdmin();

		// Set the field layout
		$fieldLayout = blx()->fields->assembleLayoutFromPost(false);
		$fieldLayout->type = 'Globals';
		blx()->fields->deleteLayoutsByType('Globals');

		if (blx()->fields->saveLayout($fieldLayout, false))
		{
			blx()->userSession->setNotice(Blocks::t('Global fields saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			blx()->userSession->setError(Blocks::t('Couldn’t save global fields.'));
		}

		$this->renderRequestedTemplate();
	}

	/**
	 * Saves the global fields.
	 */
	public function actionSaveContent()
	{
		$this->requirePostRequest();
		blx()->userSession->requirePermission('editGlobals');

		$content = blx()->globals->getGlobalContent();
		$content->setContent(blx()->request->getPost('fields'));

		if (blx()->globals->saveGlobalContent($content))
		{
			blx()->userSession->setNotice(Blocks::t('Global fields saved.'));

			$this->redirectToPostedUrl();
		}
		else
		{
			blx()->userSession->setError(Blocks::t('Couldn’t save global fields.'));

			$this->renderRequestedTemplate(array(
				'globals' => $content,
			));
		}
	}
}
