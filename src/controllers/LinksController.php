<?php
namespace Blocks;

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
		$type        = blx()->request->getRequiredPost('type');
		$name        = blx()->request->getRequiredPost('name');
		$settings    = JsonHelper::decode(blx()->request->getPost('settings'));
		$selectedIds = JsonHelper::decode(blx()->request->getPost('selectedIds'));

		$elementCriteria = blx()->elements->getCriteria($type, $settings);
		$elements = blx()->elements->findElements($elementCriteria);

		$this->renderTemplate('_components/fieldtypes/Links/modalbody', array(
			'name'        => $name,
			'elements'    => $elements,
			'selectedIds' => $selectedIds,
		));
	}
}
