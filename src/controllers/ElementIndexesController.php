<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Element;
use craft\base\ElementAction;
use craft\base\ElementActionInterface;
use craft\base\ElementInterface;
use craft\elements\actions\Restore;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\events\ElementActionEvent;
use craft\helpers\ElementHelper;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * The ElementIndexesController class is a controller that handles various element index related actions.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ElementIndexesController extends BaseElementsController
{
    // Properties
    // =========================================================================

    /**
     * @var string|null
     */
    protected $elementType;

    /**
     * @var string|null
     */
    protected $context;

    /**
     * @var string|null
     */
    protected $sourceKey;

    /**
     * @var array|null
     */
    protected $source;

    /**
     * @var array|null
     */
    protected $viewState;

    /**
     * @var ElementQueryInterface|ElementQuery|null
     */
    protected $elementQuery;

    /**
     * @var ElementActionInterface[]|null
     */
    protected $actions;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->elementType = $this->elementType();
        $this->context = $this->context();
        $this->sourceKey = Craft::$app->getRequest()->getParam('source');
        $this->source = $this->source();
        $this->viewState = $this->viewState();
        $this->elementQuery = $this->elementQuery();

        if ($this->includeActions() && $this->sourceKey !== null) {
            $this->actions = $this->availableActions();
        }

        return true;
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
    public function getElementQuery(): ElementQueryInterface
    {
        return $this->elementQuery;
    }

    /**
     * Renders and returns an element index container, plus its first batch of elements.
     *
     * @return Response
     */
    public function actionGetElements(): Response
    {
        $responseData = $this->elementResponseData(true, $this->includeActions());

        return $this->asJson($responseData);
    }

    /**
     * Renders and returns a subsequent batch of elements for an element index.
     *
     * @return Response
     */
    public function actionGetMoreElements(): Response
    {
        $responseData = $this->elementResponseData(false, false);

        return $this->asJson($responseData);
    }

    /**
     * Performs an action on one or more selected elements.
     *
     * @return Response
     * @throws BadRequestHttpException if the requested element action is not supported by the element type, or its parameters didn’t validate
     */
    public function actionPerformAction(): Response
    {
        $this->requirePostRequest();

        $requestService = Craft::$app->getRequest();
        $elementsService = Craft::$app->getElements();

        $actionClass = $requestService->getRequiredBodyParam('elementAction');
        $elementIds = $requestService->getRequiredBodyParam('elementIds');

        // Find that action from the list of available actions for the source
        if (!empty($this->actions)) {
            /** @var ElementAction $availableAction */
            foreach ($this->actions as $availableAction) {
                if ($actionClass === get_class($availableAction)) {
                    $action = $availableAction;
                    break;
                }
            }
        }

        /** @noinspection UnSafeIsSetOverArrayInspection - FP */
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
        $actionCriteria = clone $this->elementQuery;
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
            $responseData = array_merge($responseData, $this->elementResponseData(true, true));
        }

        return $this->asJson($responseData);
    }

    /**
     * Returns the source tree HTML for an element index.
     */
    public function actionGetSourceTreeHtml()
    {
        $this->requireAcceptsJson();

        $sources = Craft::$app->getElementIndexes()->getSources($this->elementType, $this->context);

        return $this->asJson([
            'html' => $this->getView()->renderTemplate('_elements/sources', [
                'sources' => $sources
            ])
        ]);
    }

    // Protected Methods
    // =========================================================================

    /**
     * Identify whether index actions should be included in the element index
     *
     * @return bool
     */
    protected function includeActions(): bool
    {
        return $this->context === 'index';
    }

    /**
     * Returns the selected source info.
     *
     * @return array|null
     * @throws ForbiddenHttpException if the user is not permitted to access the requested source
     */
    protected function source()
    {
        if ($this->sourceKey === null) {
            return null;
        }

        $source = ElementHelper::findSource($this->elementType, $this->sourceKey, $this->context);

        if ($source === null) {
            // That wasn't a valid source, or the user doesn't have access to it in this context
            throw new ForbiddenHttpException('User not permitted to access this source');
        }

        return $source;
    }

    /**
     * Returns the current view state.
     *
     * @return array
     */
    protected function viewState(): array
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
    protected function elementQuery(): ElementQueryInterface
    {
        /** @var string|ElementInterface $elementType */
        $elementType = $this->elementType;
        $query = $elementType::find();

        $request = Craft::$app->getRequest();

        // Does the source specify any criteria attributes?
        if (isset($this->source['criteria'])) {
            Craft::configure($query, $this->source['criteria']);
        }

        // Override with the request's params
        if ($criteria = $request->getBodyParam('criteria')) {
            if (isset($criteria['trashed'])) {
                $criteria['trashed'] = (bool)$criteria['trashed'];
            }
            Craft::configure($query, $criteria);
        }

        // Exclude descendants of the collapsed element IDs
        $collapsedElementIds = $request->getParam('collapsedElementIds');

        if ($collapsedElementIds) {
            $descendantQuery = (clone $query)
                ->offset(null)
                ->limit(null)
                ->orderBy(null)
                ->positionedAfter(null)
                ->positionedBefore(null)
                ->anyStatus();

            // Get the actual elements
            /** @var Element[] $collapsedElements */
            $collapsedElements = (clone $descendantQuery)
                ->id($collapsedElementIds)
                ->orderBy(['lft' => SORT_ASC])
                ->all();

            if (!empty($collapsedElements)) {
                $descendantIds = [];

                foreach ($collapsedElements as $element) {
                    // Make sure we haven't already excluded this one, because its ancestor is collapsed as well
                    if (in_array($element->id, $descendantIds, false)) {
                        continue;
                    }

                    $elementDescendantIds = (clone $descendantQuery)
                        ->descendantOf($element)
                        ->ids();

                    $descendantIds = array_merge($descendantIds, $elementDescendantIds);
                }

                if (!empty($descendantIds)) {
                    $query->andWhere(['not', ['elements.id' => $descendantIds]]);
                }
            }
        }

        return $query;
    }

    /**
     * Returns the element data to be returned to the client.
     *
     * @param bool $includeContainer Whether the element container should be included in the response data
     * @param bool $includeActions Whether info about the available actions should be included in the response data
     * @return array
     */
    protected function elementResponseData(bool $includeContainer, bool $includeActions): array
    {
        $responseData = [];

        $view = $this->getView();

        // Get the action head/foot HTML before any more is added to it from the element HTML
        if ($includeActions) {
            $responseData['actions'] = $this->actionData();
            $responseData['actionsHeadHtml'] = $view->getHeadHtml();
            $responseData['actionsFootHtml'] = $view->getBodyHtml();
        }

        $disabledElementIds = Craft::$app->getRequest()->getParam('disabledElementIds', []);
        $showCheckboxes = !empty($this->actions);
        /** @var string|ElementInterface $elementType */
        $elementType = $this->elementType;

        $responseData['html'] = $elementType::indexHtml(
            $this->elementQuery,
            $disabledElementIds,
            $this->viewState,
            $this->sourceKey,
            $this->context,
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
    protected function availableActions()
    {
        if (Craft::$app->getRequest()->isMobileBrowser()) {
            return null;
        }

        /** @var string|ElementInterface $elementType */
        $elementType = $this->elementType;
        $actions = $elementType::actions($this->sourceKey);

        foreach ($actions as $i => $action) {
            // $action could be a string or config array
            if (!$action instanceof ElementActionInterface) {
                $actions[$i] = $action = Craft::$app->getElements()->createAction($action);

                if ($actions[$i] === null) {
                    unset($actions[$i]);
                }
            }

            if ($this->elementQuery->trashed) {
                if (!$action instanceof Restore) {
                    unset($actions[$i]);
                }
            } else if ($action instanceof Restore) {
                unset($actions[$i]);
            }

            /** @var ElementActionInterface $action */
            $action->setElementType($elementType);
        }

        return array_values($actions);
    }

    /**
     * Returns the data for the available actions.
     *
     * @return array|null
     */
    protected function actionData()
    {
        if (empty($this->actions)) {
            return null;
        }

        $actionData = [];

        /** @var ElementAction $action */
        foreach ($this->actions as $action) {
            $actionData[] = [
                'type' => get_class($action),
                'destructive' => $action->isDestructive(),
                'name' => $action->getTriggerLabel(),
                'trigger' => $action->getTriggerHtml(),
                'confirm' => $action->getConfirmationMessage(),
            ];
        }

        return $actionData;
    }
}
