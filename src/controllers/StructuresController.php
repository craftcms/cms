<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\errors\Exception;
use craft\app\errors\HttpException;
use craft\app\models\BaseElementModel;
use craft\app\models\Structure as StructureModel;

/**
 * The StructuresController class is a controller that handles structure related tasks such as moving an element within
 * a structure.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[BaseController::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
		if (!Craft::$app->request->getIsCpRequest())
		{
			throw new HttpException(403);
		}

		$structureId = Craft::$app->request->getRequiredBodyParam('structureId');
		$elementId   = Craft::$app->request->getRequiredBodyParam('elementId');
		$localeId    = Craft::$app->request->getRequiredBodyParam('locale');

		// Make sure they have permission to edit this structure
		$this->requireAuthorization('editStructure:'.$structureId);

		$this->_structure = Craft::$app->structures->getStructureById($structureId);

		if (!$this->_structure)
		{
			throw new Exception(Craft::t('No structure exists with the ID “{id}”.', ['id' => $structureId]));
		}

		$this->_element = Craft::$app->elements->getElementById($elementId, null, $localeId);

		if (!$this->_element)
		{
			throw new Exception(Craft::t('No element exists with the ID “{id}.”', ['id' => $elementId]));
		}
	}

	/**
	 * Returns the descendant level delta for a given element.
	 *
	 * @return null
	 */
	public function actionGetElementLevelDelta()
	{
		$delta = Craft::$app->structures->getElementLevelDelta($this->_structure->id, $this->_element);

		$this->returnJson([
			'delta' => $delta
		]);
	}

	/**
	 * Moves an element within a structure.
	 *
	 * @return null
	 */
	public function actionMoveElement()
	{
		$parentElementId = Craft::$app->request->getBodyParam('parentId');
		$prevElementId   = Craft::$app->request->getBodyParam('prevId');

		if ($prevElementId)
		{
			$prevElement = Craft::$app->elements->getElementById($prevElementId, null, $this->_element->locale);
			$success = Craft::$app->structures->moveAfter($this->_structure->id, $this->_element, $prevElement, 'auto', true);
		}
		else if ($parentElementId)
		{
			$parentElement = Craft::$app->elements->getElementById($parentElementId, null, $this->_element->locale);
			$success = Craft::$app->structures->prepend($this->_structure->id, $this->_element, $parentElement, 'auto', true);
		}
		else
		{
			$success = Craft::$app->structures->prependToRoot($this->_structure->id, $this->_element, 'auto', true);
		}

		$this->returnJson([
			'success' => $success
		]);
	}
}
