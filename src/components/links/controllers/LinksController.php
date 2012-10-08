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
		$type = blx()->request->getRequiredPost('type');
		$name = blx()->request->getRequiredPost('name');
		$settings = blx()->request->getPost('settings', array());
		$selectedIds = blx()->request->getPost('selectedIds', array());

		$linkType = blx()->links->getLinkType($type);
		if (!$linkType)
		{
			throw new Exception(Blocks::t('No link type exists with the class “{class}”', array('class' => $class)));
		}

		$entities = $linkType->getLinkableEntities($settings);

		$this->renderTemplate('_components/blocktypes/Links/modalbody', array(
			'name' => 'blocks['.$name.']',
			'entities' => $entities,
			'selectedIds' => $selectedIds,
		));
	}
}
