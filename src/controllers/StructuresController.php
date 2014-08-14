<?php
namespace Craft;

/**
 * The StructuresController class is a controller that handles structure related tasks such as moving an element within
 * a structure.
 *
 * Note that all actions in the controller require an authenticated Craft session via {@link BaseController::allowAnonymous}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.controllers
 * @since     2.0
 */
class StructuresController extends BaseController
{
	// Public Methods
	// =========================================================================

	/**
	 * Moves an element within a structure.
	 *
	 * @param array $variables
	 *
	 * @return null
	 */
	public function actionMoveElement(array $variables = array())
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$structureId     = craft()->request->getRequiredPost('structureId');
		$elementId       = craft()->request->getRequiredPost('elementId');
		$localeId        = craft()->request->getRequiredPost('locale');
		$parentElementId = craft()->request->getPost('parentId');
		$prevElementId   = craft()->request->getPost('prevId');

		$structure = craft()->structures->getStructureById($structureId);

		// Make sure they have permission to be doing this
		if ($structure->movePermission)
		{
			craft()->userSession->requirePermission($structure->movePermission);
		}

		$element = craft()->elements->getElementById($elementId, null, $localeId);

		if ($prevElementId)
		{
			$prevElement = craft()->elements->getElementById($prevElementId, null, $localeId);
			$success = craft()->structures->moveAfter($structure->id, $element, $prevElement, 'auto', true);
		}
		else if ($parentElementId)
		{
			$parentElement = craft()->elements->getElementById($parentElementId, null, $localeId);
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
