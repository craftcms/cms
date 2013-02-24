<?php
namespace Craft;

/**
 *
 */
class LinksController extends BaseController
{
	/**
	 * Returns the modal body HTML.
	 */
	public function actionGetModalBody()
	{
		$type        = craft()->request->getRequiredPost('type');
		$name        = craft()->request->getRequiredPost('name');
		$settings    = JsonHelper::decode(craft()->request->getPost('settings'));
		$selectedIds = JsonHelper::decode(craft()->request->getPost('selectedIds'));

		$elementCriteria = craft()->elements->getCriteria($type, $settings);
		$elements = craft()->elements->findElements($elementCriteria);

		$this->renderTemplate('_components/fieldtypes/Links/modalbody', array(
			'name'        => $name,
			'elements'    => $elements,
			'selectedIds' => $selectedIds,
		));
	}
}
