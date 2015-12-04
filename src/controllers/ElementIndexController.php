<?php
namespace Craft;

/**
 * The ElementIndexController class is a controller that handles various element index related actions.
 *
 * Note that all actions in the controller require an authenticated Craft session via {@link BaseController::allowAnonymous}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.controllers
 * @since     2.3
 */
class ElementIndexController extends BaseElementsController
{
	// Properties
	// =========================================================================

	/**
	 * @var IElementType
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
		$this->_sourceKey   = craft()->request->getParam('source');
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
	 * $criteria = craft()->getController()->getElementCriteria();
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
		$includeActions = ($this->_context == 'index');
		$responseData = $this->_getElementResponseData(true, $includeActions);
		$this->returnJson($responseData);
	}

	/**
	 * Renders and returns a subsequent batch of elements for an element index.
	 *
	 * @return null
	 */
	public function actionGetMoreElements()
	{
		$responseData = $this->_getElementResponseData(false, false);
		$this->returnJson($responseData);
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

		$requestService = craft()->request;

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

		// Fire an 'onBeforePerformAction' event
		$event = new ElementActionEvent($this, array(
			'action'   => $action,
			'criteria' => $actionCriteria
		));

		craft()->elements->onBeforePerformAction($event);

		if ($event->performAction)
		{
			$success = $action->performAction($actionCriteria);
			$message = $action->getMessage();

			if ($success)
			{
				// Fire an 'onPerformAction' event
				craft()->elements->onPerformAction(new Event($this, array(
					'action'   => $action,
					'criteria' => $actionCriteria
				)));
			}
		}
		else
		{
			$success = false;
			$message = $event->message;
		}

		// Respond
		$responseData = array(
			'success' => $success,
			'message' => $message
		);

		if ($success)
		{
			// Send a new set of elements
			$responseData = array_merge($responseData, $this->_getElementResponseData(true, true));
		}

		$this->returnJson($responseData);
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
		$viewState = craft()->request->getParam('viewState', array());

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
		$criteria = craft()->elements->getCriteria($this->_elementType->getClassHandle());

		// Does the source specify any criteria attributes?
		if (!empty($this->_source['criteria']))
		{
			$criteria->setAttributes($this->_source['criteria']);
		}

		// Override with the request's params
		$criteria->setAttributes(craft()->request->getPost('criteria'));

		// Exclude descendants of the collapsed element IDs
		if (!$criteria->id)
		{
			$collapsedElementIds = craft()->request->getParam('collapsedElementIds');

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
					$descendantIds = array();

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
						$idsParam = array('and');

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
	 * @param bool $includeContainer Whether the element container should be included in the response data
	 * @param bool $includeActions   Whether info about the available actions should be included in the response data
	 *
	 * @return string
	 */
	private function _getElementResponseData($includeContainer, $includeActions)
	{
		$responseData = array();

		// Get the action head/foot HTML before any more is added to it from the element HTML
		if ($includeActions)
		{
			$responseData['actions'] = $this->_getActionData();
			$responseData['actionsHeadHtml'] = craft()->templates->getHeadHtml();
			$responseData['actionsFootHtml'] = craft()->templates->getFootHtml();
		}

		$disabledElementIds = craft()->request->getParam('disabledElementIds', array());
		$showCheckboxes = !empty($this->_actions);

		$responseData['html'] = $this->_elementType->getIndexHtml(
			$this->_criteria,
			$disabledElementIds,
			$this->_viewState,
			$this->_sourceKey,
			$this->_context,
			$includeContainer,
			$showCheckboxes
		);

		$responseData['headHtml'] = craft()->templates->getHeadHtml();
		$responseData['footHtml'] = craft()->templates->getFootHtml();

		return $responseData;
	}

	/**
	 * Returns the available actions for the current source.
	 *
	 * @return array|null
	 */
	private function _getAvailableActions()
	{
		if (craft()->request->isMobileBrowser())
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
					$actions[$i] = $action = craft()->elements->getAction($action);
				}

				if (!($action instanceof IElementAction))
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
			$actionData = array();

			foreach ($this->_actions as $action)
			{
				$actionData[] = array(
					'handle'      => $action->getClassHandle(),
					'name'        => $action->getName(),
					'destructive' => $action->isDestructive(),
					'trigger'     => $action->getTriggerHtml(),
					'confirm'     => $action->getConfirmationMessage(),
				);
			}

			return $actionData;
		}
	}
}
