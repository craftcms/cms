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

		$entryCriteria = blx()->entries->getEntryCriteria($type, $settings);
		$entries = blx()->entries->findEntries($entryCriteria);

		$this->renderTemplate('_components/fieldtypes/Links/modalbody', array(
			'name'        => $name,
			'entries'     => $entries,
			'selectedIds' => $selectedIds,
		));
	}
}
