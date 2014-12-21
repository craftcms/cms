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
	// Properties
	// =========================================================================

	/**
	 * @var StructureModel
	 */
	private $_structure;

	/**
	 * @var BaseElementModel
	 */
	private $_element;

	// Public Methods
	// =========================================================================

	/**
	 * Initializes the application component.
	 *
	 * @return null
	 */
	public function init()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		// This controller is only available to the Control Panel
		if (!craft()->request->isCpRequest())
		{
			throw new HttpException(403);
		}

		$structureId = craft()->request->getRequiredPost('structureId');
		$elementId   = craft()->request->getRequiredPost('elementId');
		$localeId    = craft()->request->getRequiredPost('locale');

		// Make sure they have permission to edit this structure
		craft()->userSession->requireAuthorization('editStructure:'.$structureId);

		$this->_structure = craft()->structures->getStructureById($structureId);

		if (!$this->_structure)
		{
			throw new Exception(Craft::t('No structure exists with the ID “{id}”.', array('id' => $structureId)));
		}

		$this->_element = craft()->elements->getElementById($elementId, null, $localeId);

		if (!$this->_element)
		{
			throw new Exception(Craft::t('No element exists with the ID “{id}.”', array('id' => $elementId)));
		}
	}

	/**
	 * Returns the descendant level delta for a given element.
	 *
	 * @return null
	 */
	public function actionGetElementLevelDelta()
	{
		$delta = craft()->structures->getElementLevelDelta($this->_structure->id, $this->_element);

		$this->returnJson(array(
			'delta' => $delta
		));
	}

	/**
	 * Moves an element within a structure.
	 *
	 * @return null
	 */
	public function actionMoveElement()
	{
		$parentElementId = craft()->request->getPost('parentId');
		$prevElementId   = craft()->request->getPost('prevId');

		if ($prevElementId)
		{
			$prevElement = craft()->elements->getElementById($prevElementId, null, $this->_element->locale);
			$success = craft()->structures->moveAfter($this->_structure->id, $this->_element, $prevElement, 'auto', true);
		}
		else if ($parentElementId)
		{
			$parentElement = craft()->elements->getElementById($parentElementId, null, $this->_element->locale);
			$success = craft()->structures->prepend($this->_structure->id, $this->_element, $parentElement, 'auto', true);
		}
		else
		{
			$success = craft()->structures->prependToRoot($this->_structure->id, $this->_element, 'auto', true);
		}

		$this->returnJson(array(
			'success' => $success
		));
	}
}
