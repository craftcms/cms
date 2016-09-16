<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\base\Element;
use craft\app\base\ElementAction;
use craft\app\base\ElementActionInterface;
use craft\app\elements\db\ElementQuery;
use craft\app\elements\db\ElementQueryInterface;
use craft\app\base\ElementInterface;
use craft\app\events\ElementActionEvent;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * The ElementIndexesController class is a controller that handles various element index related actions.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class ElementIndexesController extends BaseElementsController
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
     * @var ElementActionInterface[]
     */
    private $_actions;

    // Public Methods
    // =========================================================================

    /**
     * Initializes the application component.
     *
     * @return void
     */
    public function init()
    {
        parent::init();

        $this->_elementType = $this->getElementType();
        $this->_context = $this->getContext();
        $this->_sourceKey = Craft::$app->getRequest()->getParam('source');
        $this->_source = $this->_getSource();
        $this->_viewState = $this->_getViewState();
        $this->_elementQuery = $this->_getElementQuery();

        if ($this->_context == 'index') {
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
        $includeActions = ($this->_context == 'index');
        $responseData = $this->_getElementResponseData(true, $includeActions);

        return $this->asJson($responseData);
    }

    /**
     * Renders and returns a subsequent batch of elements for an element index.
     *
     * @return Response
     */
    public function actionGetMoreElements()
    {
        $responseData = $this->_getElementResponseData(false, false);

        return $this->asJson($responseData);
    }

    /**
     * Performs an action on one or more selected elements.
     *
     * @return Response
     * @throws BadRequestHttpException if the requested element action is not supported by the element type, or its parameters didn’t validate
     */
    public function actionPerformAction()
    {
        $this->requirePostRequest();

        $requestService = Craft::$app->getRequest();
        $elementsService = Craft::$app->getElements();

        $actionClass = $requestService->getRequiredBodyParam('elementAction');
        $elementIds = $requestService->getRequiredBodyParam('elementIds');

        // Find that action from the list of available actions for the source
        if ($this->_actions) {
            /** @var ElementAction $availableAction */
            foreach ($this->_actions as $availableAction) {
                if ($actionClass == $availableAction::className()) {
                    $action = $availableAction;
                    break;
                }
            }
        }

        if (!isset($action)) {
            throw new BadRequestHttpException('Element action is not supported by the element type');
        }

        // Check for any params in the post data
        foreach ($action->settingsAttributes() as $paramName) {
            $paramValue = $requestService->getBodyParam($paramName);

            if ($paramValue !== null) {
                $action->$paramName = $paramValue;
            }
        }

        // Make sure the action validates
        if (!$action->validate()) {
            throw new BadRequestHttpException('Element action params did not validate');
        }

        // Perform the action
        /** @var ElementQuery $actionCriteria */
        $actionCriteria = clone $this->_elementQuery;
        $actionCriteria->offset = 0;
        $actionCriteria->limit = null;
        $actionCriteria->orderBy = null;
        $actionCriteria->positionedAfter = null;
        $actionCriteria->positionedBefore = null;
        $actionCriteria->id = $elementIds;

        // Fire a 'beforePerformAction' event
        $event = new ElementActionEvent([
            'action' => $action,
            'criteria' => $actionCriteria
        ]);

        $elementsService->trigger($elementsService::EVENT_BEFORE_PERFORM_ACTION, $event);

        if ($event->isValid) {
            $success = $action->performAction($actionCriteria);
            $message = $action->getMessage();

            if ($success) {
                // Fire an 'afterPerformAction' event
                $elementsService->trigger($elementsService::EVENT_AFTER_PERFORM_ACTION, new ElementActionEvent([
                    'action' => $action,
                    'criteria' => $actionCriteria
                ]));
            }
        } else {
            $success = false;
            $message = $event->message;
        }

        // Respond
        $responseData = [
            'success' => $success,
            'message' => $message,
        ];

        if ($success) {
            // Send a new set of elements
            $responseData = array_merge($responseData, $this->_getElementResponseData(true, true));
        }

        return $this->asJson($responseData);
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the selected source info.
     *
     * @return array|null
     * @throws ForbiddenHttpException if the user is not permitted to access the requested source
     */
    private function _getSource()
    {
        if ($this->_sourceKey) {
            $elementType = $this->_elementType;
            $source = $elementType::getSourceByKey($this->_sourceKey, $this->_context);

            if (!$source) {
                // That wasn't a valid source, or the user doesn't have access to it in this context
                throw new ForbiddenHttpException('User not permitted to access this source');
            }

            return $source;
        }

        return null;
    }

    /**
     * Returns the current view state.
     *
     * @return array
     */
    private function _getViewState()
    {
        $viewState = Craft::$app->getRequest()->getParam('viewState', []);

        if (empty($viewState['mode'])) {
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
        $query = $elementType::find();

        $request = Craft::$app->getRequest();

        // Does the source specify any criteria attributes?
        if (isset($this->_source['criteria'])) {
            $query->configure($this->_source['criteria']);
        }

        // Override with the request's params
        $query->configure($request->getBodyParam('criteria'));

        // Exclude descendants of the collapsed element IDs
        $collapsedElementIds = $request->getParam('collapsedElementIds');

        if ($collapsedElementIds) {
            // Get the actual elements
            $collapsedElementQuery = clone $query;
            /** @var Element[] $collapsedElements */
            $collapsedElements = $collapsedElementQuery
                ->id($collapsedElementIds)
                ->offset(0)
                ->limit(null)
                ->orderBy('lft asc')
                ->positionedAfter(null)
                ->positionedBefore(null)
                ->all();

            if ($collapsedElements) {
                $descendantIds = [];

                $descendantQuery = clone $query;
                $descendantQuery
                    ->offset(0)
                    ->limit(null)
                    ->orderBy(null)
                    ->positionedAfter(null)
                    ->positionedBefore(null);

                foreach ($collapsedElements as $element) {
                    // Make sure we haven't already excluded this one, because its ancestor is collapsed as well
                    if (in_array($element->id, $descendantIds)) {
                        continue;
                    }

                    $descendantQuery->descendantOf($element);
                    $descendantIds = array_merge($descendantIds, $descendantQuery->ids());
                }

                if ($descendantIds) {
                    $query->andWhere(['not in', 'element.id', $descendantIds]);
                }
            }
        }

        return $query;
    }

    /**
     * Returns the element data to be returned to the client.
     *
     * @param boolean $includeContainer Whether the element container should be included in the response data
     * @param boolean $includeActions   Whether info about the available actions should be included in the response data
     *
     * @return array
     */
    private function _getElementResponseData($includeContainer, $includeActions)
    {
        $responseData = [];

        $view = Craft::$app->getView();

        // Get the action head/foot HTML before any more is added to it from the element HTML
        if ($includeActions) {
            $responseData['actions'] = $this->_getActionData();
            $responseData['actionsHeadHtml'] = $view->getHeadHtml();
            $responseData['actionsFootHtml'] = $view->getBodyHtml();
        }

        $disabledElementIds = Craft::$app->getRequest()->getParam('disabledElementIds', []);
        $showCheckboxes = !empty($this->_actions);
        $elementType = $this->_elementType;

        $responseData['html'] = $elementType::getIndexHtml(
            $this->_elementQuery,
            $disabledElementIds,
            $this->_viewState,
            $this->_sourceKey,
            $this->_context,
            $includeContainer,
            $showCheckboxes
        );

        $responseData['headHtml'] = $view->getHeadHtml();
        $responseData['footHtml'] = $view->getBodyHtml();

        return $responseData;
    }

    /**
     * Returns the available actions for the current source.
     *
     * @return ElementActionInterface[]|null
     */
    private function _getAvailableActions()
    {
        if (Craft::$app->getRequest()->isMobileBrowser()) {
            return null;
        }

        /** @var Element $elementType */
        $elementType = $this->_elementType;
        $actions = $elementType::getAvailableActions($this->_sourceKey);

        if ($actions) {
            foreach ($actions as $i => $action) {
                // $action could be a string or config array
                if (!$action instanceof ElementActionInterface) {
                    $actions[$i] = $action = Craft::$app->getElements()->createAction($action);

                    if ($actions[$i] === null) {
                        unset($actions[$i]);
                    }
                }
            }

            return array_values($actions);
        }

        return null;
    }

    /**
     * Returns the data for the available actions.
     *
     * @return array|null
     */
    private function _getActionData()
    {
        if ($this->_actions) {
            $actionData = [];

            /** @var ElementAction $action */
            foreach ($this->_actions as $action) {
                $actionData[] = [
                    'type' => $action::className(),
                    'destructive' => $action->isDestructive(),
                    'name' => $action->getTriggerLabel(),
                    'trigger' => $action->getTriggerHtml(),
                    'confirm' => $action->getConfirmationMessage(),
                ];
            }

            return $actionData;
        }

        return null;
    }
}
