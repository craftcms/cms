<?php
namespace Blocks;

/**
 * Handles asset source tasks
 */
class AssetSourcesController extends BaseController
{
	/**
	 * Saves an asset source.
	 */
	public function actionSaveSource()
	{
		$this->requirePostRequest();

		$source = new AssetSourceModel();
		$source->id = blx()->request->getPost('sourceId');
		$source->name = blx()->request->getPost('name');

		if (Blocks::hasPackage('Cloud'))
		{
			$source->type = blx()->request->getPost('type');
		}

		$typeSettings = blx()->request->getPost('types');
		if (isset($typeSettings[$source->type]))
		{
			$source->settings = $typeSettings[$source->type];
		}

		// Did it save?
		if ($source->save())
		{
			blx()->user->setNotice(Blocks::t('Source saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			blx()->user->setError(Blocks::t('Couldn’t save source.'));
		}

		// Reload the original template
		$this->renderRequestedTemplate(array(
			'source' => new AssetSourceVariable($source)
		));
	}

	/**
	 * Reorders asset sources.
	 */
	public function actionReorderSources()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$sourceIds = JsonHelper::decode(blx()->request->getRequiredPost('ids'));
		blx()->assetSources->reorderSources($sourceIds);
		$this->returnJson(array('success' => true));
	}

	/**
	 * Deletes an asset source.
	 */
	public function actionDeleteSource()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$sourceId = blx()->request->getRequiredPost('id');

		blx()->assetSources->deleteSourceById($sourceId);

		$this->returnJson(array('success' => true));
	}
}
