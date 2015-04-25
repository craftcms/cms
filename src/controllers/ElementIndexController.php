<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\base\ElementAction;
use craft\app\base\ElementActionInterface;
use craft\app\elements\db\ElementQueryInterface;
use craft\app\base\ElementInterface;
use craft\app\errors\HttpException;
use craft\app\web\Response;

/**
 * The ElementIndexController class is a controller that handles various element index related actions.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ElementIndexController extends BaseElementsController
{
	// Properties
	// =========================================================================

	/**
	 * @var ElementInterface
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
	 * @var ElementQueryInterface
	 */
	private $_elementQuery;

	/**
	 * @var ElementActionInterface[]|ElementAction[]
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
		$this->_context      = $this->getContext();
		$this->_sourceKey    = Craft::$app->getRequest()->getParam('source');
		$this->_source       = $this->_getSource();
		$this->_viewState    = $this->_getViewState();
		$this->_elementQuery = $this->_getElementQuery();

		if ($this->_context == 'index')
		{
			$this->_actions = $this->_getAvailableActions();
		}
	}

	/**
	 * Returns the element query that’s defining which elements will be returned in the current request.
	 *
	 * Other components can fetch this like so:
	 *
	 * ```php
	 * $criteria = Craft::$app->controller->getElementQuery();
	 * ```
	 *
	 * @return ElementQueryInterface
	 */
	public function getElementQuery()
	{
		return $this->_elementQuery;
	}

	/**
	 * Renders and returns an element index container, plus its first batch of elements.
	 *
	 * @return Response
	 */
	public function actionGetElements()
	{
		// Get the action head/foot HTML before any more is added to it from the element HTML
		if ($this->_context == 'index')
		{
			$responseData['actions']  = $this->_getActionData();
			$responseData['actionsHeadHtml'] = Craft::$app->getView()->getHeadHtml();
			$responseData['actionsFootHtml'] = Craft::$app->getView()->getBodyEndHtml(true);
		}

		$responseData['html'] = $this->_getElementHtml(true);

		return $this->_getResponse($responseData, true);
	}

	/**
	 * Renders and returns a subsequent batch of elements for an element index.
	 *
	 * @return Response
	 */
	public function actionGetMoreElements()
	{
		return $this->_getResponse([
			'html' => $this->_getElementHtml(false),
		], true);
	}

	/**
	 * Performs an action on one or more selected elements.
	 *
	 * @return Response
	 * @throws HttpException
	 */
	public function actionPerformAction()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$requestService = Craft::$app->getRequest();

		$actionClass = $requestService->getRequiredBodyParam('elementAction');
		$elementIds = $requestService->getRequiredBodyParam('elementIds');

		// Find that action from the list of available actions for the source
		if ($this->_actions)
		{
			/** @var ElementActionInterface|ElementAction $availableAction */
			foreach ($this->_actions as $availableAction)
			{
				if ($actionClass == $availableAction::className())
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
		foreach ($action->settingsAttributes() as $paramName)
		{
			$paramValue = $requestService->getBodyParam($paramName);

			if ($paramValue !== null)
			{
				$action->$paramName = $paramValue;
			}
		}

		// Make sure the action validates
		if (!$action->validate())
		{
			throw new HttpException(400);
		}

		// Perform the action
		$actionCriteria = clone $this->_elementQuery;
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

		return $this->_getResponse($response, $includeLoadMoreInfo);
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
			$elementType = $this->_elementType;
			$source = $elementType::getSourceByKey($this->_sourceKey, $this->_context);

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
		$viewState = Craft::$app->getRequest()->getParam('viewState', []);

		if (empty($viewState['mode']))
		{
			$viewState['mode'] = 'table';
		}

		return $viewState;
	}

	/**
	 * Returns the element query based on the current params.
	 *
	 * @return ElementQueryInterface
	 */
	private function _getElementQuery()
	{
		$elementType = $this->_elementType;

		$query = $elementType::find()
			->configure(Craft::$app->getRequest()->getBodyParam('criteria'))
			->limit(50)
			->offset(Craft::$app->getRequest()->getParam('offset'))
			->search(Craft::$app->getRequest()->getParam('search'));

		// Does the source specify any criteria attributes?
		if (isset($this->_source['criteria']))
		{
			$query->configure($this->_source['criteria']);
		}

		// Exclude descendants of the collapsed element IDs
		$collapsedElementIds = Craft::$app->getRequest()->getParam('collapsedElementIds');

		if ($collapsedElementIds)
		{
			// Get the actual elements
			$collapsedElementQuery = clone $query;
			$collapsedElements = $collapsedElementQuery
				->id($collapsedElementIds)
				->offset(0)
				->limit(null)
				->orderBy('lft asc')
				->positionedAfter(null)
				->positionedBefore(null)
				->all();

			if ($collapsedElements)
			{
				$descendantIds = [];

				$descendantQuery = clone $query;
				$descendantQuery
					->offset(0)
					->limit(null)
					->orderBy(null)
					->positionedAfter(null)
					->positionedBefore(null);

				foreach ($collapsedElements as $element)
				{
					// Make sure we haven't already excluded this one, because its ancestor is collapsed as well
					if (in_array($element->id, $descendantIds))
					{
						continue;
					}

					$descendantQuery->descendantOf($element);
					$descendantIds = array_merge($descendantIds, $descendantQuery->ids());
				}

				if ($descendantIds)
				{
					$query->andWhere(['not in', 'element.id', $descendantIds]);
				}
			}
		}

		return $query;
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
		$disabledElementIds = Craft::$app->getRequest()->getParam('disabledElementIds', []);
		$showCheckboxes = !empty($this->_actions);
		$elementType = $this->_elementType;

		return $elementType::getIndexHtml(
			$this->_elementQuery,
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
	 * @return ElementActionInterface[]|ElementAction[]|null
	 */
	private function _getAvailableActions()
	{
		if (Craft::$app->getRequest()->getIsMobileBrowser())
		{
			return;
		}

		$elementType = $this->_elementType;
		$actions = $elementType::getAvailableActions($this->_sourceKey);

		if ($actions)
		{
			foreach ($actions as $i => $action)
			{
				// $action could be a string or config array
				if (!$action instanceof ElementActionInterface)
				{
					$actions[$i] = $action = Craft::$app->getElements()->createAction($action);

					if ($actions[$i] === null)
					{
						unset($actions[$i]);
					}
				}
			}

			return array_values($actions);
		}
		else
		{
			return null;
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

			/** @var ElementActionInterface|ElementAction $action */
			foreach ($this->_actions as $action)
			{
				$actionData[] = [
					'type'        => $action::className(),
					'destructive' => $action->isDestructive(),
					'name'        => $action->getTriggerLabel(),
					'trigger'     => $action->getTriggerHtml(),
					'confirm'     => $action->getConfirmationMessage(),
				];
			}

			return $actionData;
		}
	}

	/**
	 * Returns the request response.
	 *
	 * @param array $responseData
	 * @param bool  $includeLoadMoreInfo
	 *
	 * @return Response
	 */
	private function _getResponse($responseData, $includeLoadMoreInfo)
	{
		if ($includeLoadMoreInfo)
		{
			$totalElementsInBatch = count($this->_elementQuery);
			$responseData['totalVisible'] = $this->_elementQuery->offset + $totalElementsInBatch;

			if ($this->_elementQuery->limit)
			{
				// We'll risk a pointless additional Ajax request in the unlikely event that there are exactly a factor of
				// 50 elements, rather than running two separate element queries
				$responseData['more'] = ($totalElementsInBatch == $this->_elementQuery->limit);
			}
			else
			{
				$responseData['more'] = false;
			}
		}

		$responseData['headHtml'] = Craft::$app->getView()->getHeadHtml();
		$responseData['footHtml'] = Craft::$app->getView()->getBodyEndHtml(true);

		return $this->asJson($responseData);
	}
}
