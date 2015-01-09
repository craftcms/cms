<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\elementactions\ElementActionInterface;
use craft\app\elementtypes\ElementTypeInterface;
use craft\app\errors\HttpException;
use craft\app\models\ElementCriteria as ElementCriteriaModel;

/**
 * The ElementIndexController class is a controller that handles various element index related actions.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[BaseController::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ElementIndexController extends BaseElementsController
{
	// Properties
	// =========================================================================

	/**
	 * @var ElementTypeInterface
	 */
	private $_elementType;

	/**
	 * @var string
	 */
	private $_context;

	/**
	 * @var string
	 */
	private $_sourceKey;

	/**
	 * @var array|null
	 */
	private $_source;

	/**
	 * @var array
	 */
	private $_viewState;

	/**
	 * @var ElementCriteriaModel
	 */
	private $_criteria;

	/**
	 * @var array|null
	 */
	private $_actions;

	// Public Methods
	// =========================================================================

	/**
	 * Initializes the application component.
	 *
	 * @return null
	 */
	public function init()
	{
		parent::init();

		$this->_elementType = $this->getElementType();
		$this->_context     = $this->getContext();
		$this->_sourceKey   = Craft::$app->request->getParam('source');
		$this->_source      = $this->_getSource();
		$this->_viewState   = $this->_getViewState();
		$this->_criteria    = $this->_getCriteria();

		if ($this->_context == 'index')
		{
			$this->_actions = $this->_getAvailableActions();
		}
	}

	/**
	 * Returns the criteria that’s defining which elements will be returned in the current request.
	 *
	 * Other components can fetch this like so:
	 *
	 * ```php
	 * $criteria = Craft::$app->getController()->getElementCriteria();
	 * ```
	 *
	 * @return ElementCriteriaModel
	 */
	public function getElementCriteria()
	{
		return $this->_criteria;
	}

	/**
	 * Renders and returns an element index container, plus its first batch of elements.
	 *
	 * @return null
	 */
	public function actionGetElements()
	{
		// Get the action head/foot HTML before any more is added to it from the element HTML
		if ($this->_context == 'index')
		{
			$responseData['actions']  = $this->_getActionData();
			$responseData['actionsHeadHtml'] = Craft::$app->templates->getHeadHtml();
			$responseData['actionsFootHtml'] = Craft::$app->templates->getFootHtml();
		}

		$responseData['html'] = $this->_getElementHtml(true);

		$this->_respond($responseData, true);
	}

	/**
	 * Renders and returns a subsequent batch of elements for an element index.
	 *
	 * @return null
	 */
	public function actionGetMoreElements()
	{
		$this->_respond([
			'html' => $this->_getElementHtml(false),
		], true);
	}

	/**
	 * Performs an action on one or more selected elements.
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function actionPerformAction()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$requestService = Craft::$app->request;

		$actionHandle = $requestService->getRequiredPost('elementAction');
		$elementIds = $requestService->getRequiredPost('elementIds');

		// Find that action from the list of available actions for the source
		if ($this->_actions)
		{
			foreach ($this->_actions as $availableAction)
			{
				if ($actionHandle == $availableAction->getClassHandle())
				{
					$action = $availableAction;
					break;
				}
			}
		}

		if (!isset($action))
		{
			throw new HttpException(400);
		}

		// Check for any params in the post data
		$params = $action->getParams();

		foreach ($params->attributeNames() as $paramName)
		{
			$paramValue = $requestService->getPost($paramName);

			if ($paramValue !== null)
			{
				$params->setAttribute($paramName, $paramValue);
			}
		}

		// Make sure they validate
		if (!$params->validate())
		{
			throw new HttpException(400);
		}

		// Perform the action
		$actionCriteria = $this->_criteria->copy();
		$actionCriteria->offset = 0;
		$actionCriteria->limit = null;
		$actionCriteria->order = null;
		$actionCriteria->positionedAfter = null;
		$actionCriteria->positionedBefore = null;
		$actionCriteria->id = $elementIds;

		$success = $action->performAction($actionCriteria);

		// Respond
		$response = [
			'success' => $success,
			'message' => $action->getMessage(),
		];

		if ($success)
		{
			// Send a new set of elements
			$response['html'] = $this->_getElementHtml(false);
			$includeLoadMoreInfo = true;
		}
		else
		{
			$includeLoadMoreInfo = false;
		}

		$this->_respond($response, $includeLoadMoreInfo);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the selected source info.
	 *
	 * @throws HttpException
	 * @return array|null
	 */
	private function _getSource()
	{
		if ($this->_sourceKey)
		{
			$source = $this->_elementType->getSource($this->_sourceKey, $this->_context);

			if (!$source)
			{
				// That wasn't a valid source, or the user doesn't have access to it in this context
				throw new HttpException(404);
			}

			return $source;
		}
	}

	/**
	 * Returns the current view state.
	 *
	 * @return array
	 */
	private function _getViewState()
	{
		$viewState = Craft::$app->request->getParam('viewState', []);

		if (empty($viewState['mode']))
		{
			$viewState['mode'] = 'table';
		}

		return $viewState;
	}

	/**
	 * Returns the element criteria based on the current params.
	 *
	 * @return ElementCriteriaModel
	 */
	private function _getCriteria()
	{
		$criteria = Craft::$app->elements->getCriteria(
			$this->_elementType->getClassHandle(),
			Craft::$app->request->getPost('criteria')
		);

		$criteria->limit = 50;
		$criteria->offset = Craft::$app->request->getParam('offset');
		$criteria->search = Craft::$app->request->getParam('search');

		// Does the source specify any criteria attributes?
		if (!empty($this->_source['criteria']))
		{
			$criteria->setAttributes($this->_source['criteria']);
		}

		// Exclude descendants of the collapsed element IDs
		if (!$criteria->id)
		{
			$collapsedElementIds = Craft::$app->request->getParam('collapsedElementIds');

			if ($collapsedElementIds)
			{
				// Get the actual elements
				$collapsedElementCriteria = $criteria->copy();
				$collapsedElementCriteria->id = $collapsedElementIds;
				$collapsedElementCriteria->offset = 0;
				$collapsedElementCriteria->limit = null;
				$collapsedElementCriteria->order = 'lft asc';
				$collapsedElementCriteria->positionedAfter = null;
				$collapsedElementCriteria->positionedBefore = null;
				$collapsedElements = $collapsedElementCriteria->find();

				if ($collapsedElements)
				{
					$descendantIds = [];

					$descendantCriteria = $criteria->copy();
					$descendantCriteria->offset = 0;
					$descendantCriteria->limit = null;
					$descendantCriteria->order = null;
					$descendantCriteria->positionedAfter = null;
					$descendantCriteria->positionedBefore = null;

					foreach ($collapsedElements as $element)
					{
						// Make sure we haven't already excluded this one, because its ancestor is collapsed as well
						if (in_array($element->id, $descendantIds))
						{
							continue;
						}

						$descendantCriteria->descendantOf = $element;
						$descendantIds = array_merge($descendantIds, $descendantCriteria->ids());
					}

					if ($descendantIds)
					{
						$idsParam = ['and'];

						foreach ($descendantIds as $id)
						{
							$idsParam[] = 'not '.$id;
						}

						$criteria->id = $idsParam;
					}
				}
			}
		}

		return $criteria;
	}

	/**
	 * Returns the element HTML to be returned to the client.
	 *
	 * @param bool $includeContainer Whether the element container should be included in the HTML.
	 *
	 * @return string
	 */
	private function _getElementHtml($includeContainer)
	{
		$disabledElementIds = Craft::$app->request->getParam('disabledElementIds', []);
		$showCheckboxes = !empty($this->_actions);

		return $this->_elementType->getIndexHtml(
			$this->_criteria,
			$disabledElementIds,
			$this->_viewState,
			$this->_sourceKey,
			$this->_context,
			$includeContainer,
			$showCheckboxes
		);
	}

	/**
	 * Returns the available actions for the current source.
	 *
	 * @return array|null
	 */
	private function _getAvailableActions()
	{
		if (Craft::$app->request->isMobileBrowser())
		{
			return;
		}

		$actions = $this->_elementType->getAvailableActions($this->_sourceKey);

		if ($actions)
		{
			foreach ($actions as $i => $action)
			{
				if (is_string($action))
				{
					$actions[$i] = $action = Craft::$app->elements->getAction($action);
				}

				if (!($action instanceof ElementActionInterface))
				{
					unset($actions[$i]);
				}
			}

			return array_values($actions);
		}
	}

	/**
	 * Returns the data for the available actions.
	 *
	 * @return array
	 */
	private function _getActionData()
	{
		if ($this->_actions)
		{
			$actionData = [];

			foreach ($this->_actions as $action)
			{
				$actionData[] = [
					'handle'      => $action->getClassHandle(),
					'name'        => $action->getName(),
					'destructive' => $action->isDestructive(),
					'trigger'     => $action->getTriggerHtml(),
					'confirm'     => $action->getConfirmationMessage(),
				];
			}

			return $actionData;
		}
	}

	/**
	 * Responds to the request.
	 *
	 * @param array $responseData
	 * @param bool  $includeLoadMoreInfo
	 *
	 * @return null
	 */
	private function _respond($responseData, $includeLoadMoreInfo)
	{
		if ($includeLoadMoreInfo)
		{
			$totalElementsInBatch = count($this->_criteria);
			$responseData['totalVisible'] = $this->_criteria->offset + $totalElementsInBatch;

			if ($this->_criteria->limit)
			{
				// We'll risk a pointless additional Ajax request in the unlikely event that there are exactly a factor of
				// 50 elements, rather than running two separate element queries
				$responseData['more'] = ($totalElementsInBatch == $this->_criteria->limit);
			}
			else
			{
				$responseData['more'] = false;
			}
		}

		$responseData['headHtml'] = Craft::$app->templates->getHeadHtml();
		$responseData['footHtml'] = Craft::$app->templates->getFootHtml();

		$this->returnJson($responseData);
	}
}
