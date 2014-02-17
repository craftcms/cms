<?php
namespace Craft;

/**
 * Handles structure management tasks
 */
class StructuresController extends BaseController
{
	/**
	 * Moves an element within a structure.
	 */
	public function actionMoveElement(array $variables = array())
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$structureId     = craft()->request->getRequiredPost('structureId');
		$elementId       = craft()->request->getRequiredPost('elementId');
		$parentElementId = craft()->request->getPost('parentId');
		$prevElementId   = craft()->request->getPost('prevId');

		$structure = craft()->structures->getStructureById($structureId);

		// Make sure they have permission to be doing this
		if ($structure->movePermission)
		{
			craft()->userSession->requirePermission($structure->movePermission);
		}

		$element = craft()->elements->getElementById($elementId);

		if ($prevElementId)
		{
			$prevElement = craft()->elements->getElementById($prevElementId);
			$success = craft()->structures->moveAfter($structure->id, $element, $prevElement, 'auto', true);
		}
		else if ($parentElementId)
		{
			$parentElement = craft()->elements->getElementById($parentElementId);
			$success = craft()->structures->prepend($structure->id, $element, $parentElement, 'auto', true);
		}
		else
		{
			$success = craft()->structures->prependToRoot($structure->id, $element, 'auto', true);
		}

		$this->returnJson(array(
			'success' => $success
		));
	}
}
