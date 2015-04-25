<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\base\ElementInterface;
use craft\app\errors\Exception;
use craft\app\errors\HttpException;
use craft\app\models\Structure as StructureModel;
use craft\app\web\Controller;

/**
 * The StructuresController class is a controller that handles structure related tasks such as moving an element within
 * a structure.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class StructuresController extends Controller
{
	// Properties
	// =========================================================================

	/**
	 * @var StructureModel
	 */
	private $_structure;

	/**
	 * @var ElementInterface
	 */
	private $_element;

	// Public Methods
	// =========================================================================

	/**
	 * Initializes the application component.
	 *
	 * @throws HttpException
	 */
	public function init()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		// This controller is only available to the Control Panel
		if (!Craft::$app->getRequest()->getIsCpRequest())
		{
			throw new HttpException(403);
		}

		$structureId = Craft::$app->getRequest()->getRequiredBodyParam('structureId');
		$elementId   = Craft::$app->getRequest()->getRequiredBodyParam('elementId');
		$localeId    = Craft::$app->getRequest()->getRequiredBodyParam('locale');

		// Make sure they have permission to edit this structure
		$this->requireAuthorization('editStructure:'.$structureId);

		$this->_structure = Craft::$app->getStructures()->getStructureById($structureId);

		if (!$this->_structure)
		{
			throw new Exception(Craft::t('app', 'No structure exists with the ID “{id}”.', ['id' => $structureId]));
		}

		$this->_element = Craft::$app->getElements()->getElementById($elementId, null, $localeId);

		if (!$this->_element)
		{
			throw new Exception(Craft::t('app', 'No element exists with the ID “{id}.”', ['id' => $elementId]));
		}
	}

	/**
	 * Returns the descendant level delta for a given element.
	 *
	 * @return null
	 */
	public function actionGetElementLevelDelta()
	{
		$delta = Craft::$app->getStructures()->getElementLevelDelta($this->_structure->id, $this->_element);

		return $this->asJson([
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
		$parentElementId = Craft::$app->getRequest()->getBodyParam('parentId');
		$prevElementId   = Craft::$app->getRequest()->getBodyParam('prevId');

		if ($prevElementId)
		{
			$prevElement = Craft::$app->getElements()->getElementById($prevElementId, null, $this->_element->locale);
			$success = Craft::$app->getStructures()->moveAfter($this->_structure->id, $this->_element, $prevElement, 'auto', true);
		}
		else if ($parentElementId)
		{
			$parentElement = Craft::$app->getElements()->getElementById($parentElementId, null, $this->_element->locale);
			$success = Craft::$app->getStructures()->prepend($this->_structure->id, $this->_element, $parentElement, 'auto', true);
		}
		else
		{
			$success = Craft::$app->getStructures()->prependToRoot($this->_structure->id, $this->_element, 'auto', true);
		}

		return $this->asJson([
			'success' => $success
		]);
	}
}
