<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\ElementAction;
use craft\base\ElementActionInterface;
use craft\base\ElementExporterInterface;
use craft\base\ElementInterface;
use craft\elements\actions\Delete;
use craft\elements\actions\DeleteActionInterface;
use craft\elements\actions\Restore;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\exporters\Raw;
use craft\events\ElementActionEvent;
use craft\helpers\ElementHelper;
use yii\base\InvalidValueException;
use yii\db\Expression;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * The ElementIndexesController class is a controller that handles various element index related actions.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ElementIndexesController extends BaseElementsController
{
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
     * @var bool
     * @deprecated in 3.4.6
     */
    protected $paginated = false;

    /**
     * @var ElementQueryInterface|ElementQuery|null
     */
    protected $elementQuery;

    /**
     * @var ElementActionInterface[]|null
     */
    protected $actions;

    /**
     * @var ElementExporterInterface[]|null
     */
    protected $exporters;

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if (!in_array($action->id, ['export', 'perform-action'], true)) {
            $this->requireAcceptsJson();
        }

        $this->elementType = $this->elementType();
        $this->context = $this->context();
        $this->sourceKey = $this->request->getParam('source') ?: null;
        $this->source = $this->source();
        $this->viewState = $this->viewState();
        $this->paginated = (bool)$this->request->getParam('paginated');
        $this->elementQuery = $this->elementQuery();

        if ($this->includeActions() && $this->sourceKey !== null) {
            $this->actions = $this->availableActions();
            $this->exporters = $this->availableExporters();
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
     * Returns the total number of elements that match the current criteria.
     *
     * @return Response
     * @since 3.4.6
     */
    public function actionCountElements(): Response
    {
        return $this->asJson([
            'resultSet' => $this->request->getParam('resultSet'),
            'count' => (int)$this->elementQuery
                ->select(new Expression('1'))
                ->count(),
        ]);
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

        $elementsService = Craft::$app->getElements();

        $actionClass = $this->request->getRequiredBodyParam('elementAction');
        $elementIds = $this->request->getRequiredBodyParam('elementIds');

        // Find that action from the list of available actions for the source
        if (!empty($this->actions)) {
            /** @var ElementAction $availableAction */
            foreach ($this->actions as $availableAction) {
                if ($actionClass === get_class($availableAction)) {
                    $action = clone $availableAction;
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
            $paramValue = $this->request->getBodyParam($paramName);

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
        if ($action->isDownload()) {
            return $this->response;
        }

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
     *
     * @return Response
     */
    public function actionGetSourceTreeHtml(): Response
    {
        $this->requireAcceptsJson();

        $sources = Craft::$app->getElementIndexes()->getSources($this->elementType, $this->context);

        return $this->asJson([
            'html' => $this->getView()->renderTemplate('_elements/sources', [
                'elementType' => $this->elementType,
                'sources' => $sources
            ])
        ]);
    }

    /**
     * Exports element data.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @since 3.4.4
     */
    public function actionExport(): Response
    {
        $exporter = $this->_exporter();
        $exporter->setElementType($this->elementType);

        // Set the filename header before calling export() in case export() starts outputting the data
        $filename = $exporter->getFilename();
        if ($exporter::isFormattable()) {
            $this->response->format = $this->request->getBodyParam('format', 'csv');
            $filename .= '.' . $this->response->format;
        }
        $this->response->setDownloadHeaders($filename);

        $export = $exporter->export($this->elementQuery);

        if ($exporter::isFormattable()) {
            if (!is_array($export)) {
                throw new InvalidValueException(get_class($exporter) . '::export() must return an array since isFormattable() returns true.');
            }

            $this->response->data = $export;

            switch ($this->response->format) {
                case Response::FORMAT_JSON:
                    $this->response->formatters[Response::FORMAT_JSON]['prettyPrint'] = true;
                    break;
                case Response::FORMAT_XML:
                    Craft::$app->language = 'en-US';
                    /** @var string|ElementInterface $elementType */
                    $elementType = $this->elementType;
                    $this->response->formatters[Response::FORMAT_XML]['rootTag'] = $elementType::pluralLowerDisplayName();
                    break;
            }
        } else if (
            is_callable($export) ||
            is_resource($export) ||
            (is_array($export) && isset($export[0]) && is_resource($export[0]))
        ) {
            $this->response->stream = $export;
        } else {
            $this->response->data = $export;
            $this->response->format = Response::FORMAT_RAW;
        }

        return $this->response;
    }

    /**
     * Returns the exporter for the request.
     *
     * @return ElementExporterInterface
     * @throws BadRequestHttpException
     */
    private function _exporter(): ElementExporterInterface
    {
        if (!$this->sourceKey) {
            throw new BadRequestHttpException('Request missing required body param');
        }

        if ($this->context !== 'index') {
            throw new BadRequestHttpException('Request missing index context');
        }

        // Find that exporter from the list of available exporters for the source
        $exporterClass = $this->request->getBodyParam('type', Raw::class);
        if (!empty($this->exporters)) {
            foreach ($this->exporters as $exporter) {
                if ($exporterClass === get_class($exporter)) {
                    return $exporter;
                }
            }
        }

        throw new BadRequestHttpException('Element exporter is not supported by the element type');
    }

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
        $viewState = $this->request->getParam('viewState', []);

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

        // Does the source specify any criteria attributes?
        if (isset($this->source['criteria'])) {
            Craft::configure($query, $this->source['criteria']);
        }

        // Override with the request's params
        if ($criteria = $this->request->getBodyParam('criteria')) {
            if (isset($criteria['trashed'])) {
                $criteria['trashed'] = filter_var($criteria['trashed'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            }
            if (isset($criteria['drafts'])) {
                $criteria['drafts'] = filter_var($criteria['drafts'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            }
            if (isset($criteria['draftOf'])) {
                if (is_numeric($criteria['draftOf']) && $criteria['draftOf'] != 0) {
                    $criteria['draftOf'] = (int)$criteria['draftOf'];
                } else {
                    $criteria['draftOf'] = filter_var($criteria['draftOf'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                }
            }
            Craft::configure($query, $criteria);
        }

        // Exclude descendants of the collapsed element IDs
        $collapsedElementIds = $this->request->getParam('collapsedElementIds');

        if ($collapsedElementIds) {
            $descendantQuery = clone $query;
            $descendantQuery
                ->offset(null)
                ->limit(null)
                ->orderBy(null)
                ->positionedAfter(null)
                ->positionedBefore(null)
                ->anyStatus();

            // Get the actual elements
            $collapsedElementsQuery = clone $descendantQuery;
            $collapsedElements = $collapsedElementsQuery
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

                    $elementDescendantsQuery = clone $descendantQuery;
                    $elementDescendantIds = $elementDescendantsQuery
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
        /** @var string|ElementInterface $elementType */
        $elementType = $this->elementType;
        $responseData = [];
        $view = $this->getView();

        // Get the action head/foot HTML before any more is added to it from the element HTML
        if ($includeActions) {
            $responseData['actions'] = $this->actionData();
            $responseData['actionsHeadHtml'] = $view->getHeadHtml();
            $responseData['actionsFootHtml'] = $view->getBodyHtml();
            $responseData['exporters'] = $this->exporterData();
        }

        $disabledElementIds = $this->request->getParam('disabledElementIds', []);
        $showCheckboxes = !empty($this->actions);

        if ($this->sourceKey) {
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
        } else {
            $responseData['html'] = '';
        }

        return $responseData;
    }

    /**
     * Returns the available actions for the current source.
     *
     * @return ElementActionInterface[]|null
     */
    protected function availableActions()
    {
        if ($this->request->isMobileBrowser()) {
            return null;
        }

        /** @var string|ElementInterface $elementType */
        $elementType = $this->elementType;
        $actions = $elementType::actions($this->sourceKey);

        foreach ($actions as $i => $action) {
            // $action could be a string or config array
            if ($action instanceof ElementActionInterface) {
                $action->setElementType($elementType);
            } else {
                if (is_string($action)) {
                    $action = ['type' => $action];
                }
                $action['elementType'] = $elementType;
                $actions[$i] = $action = Craft::$app->getElements()->createAction($action);

                if ($actions[$i] === null) {
                    unset($actions[$i]);
                }
            }

            if ($this->elementQuery->trashed) {
                if ($action instanceof DeleteActionInterface && $action->canHardDelete()) {
                    $action->hard = true;
                } else if (!$action instanceof Restore) {
                    unset($actions[$i]);
                }
            } else if ($action instanceof Restore) {
                unset($actions[$i]);
            }
        }

        if ($this->elementQuery->trashed) {
            // Make sure Restore goes first
            usort($actions, function($a, $b): int {
                if ($a instanceof Restore) {
                    return -1;
                }
                if ($b instanceof Restore) {
                    return 1;
                }
                return 0;
            });
        }

        return array_values($actions);
    }

    /**
     * Returns the available exporters for the current source.
     *
     * @return ElementExporterInterface[]|null
     * @since 3.4.0
     */
    protected function availableExporters()
    {
        if ($this->request->isMobileBrowser()) {
            return null;
        }

        /** @var string|ElementInterface $elementType */
        $elementType = $this->elementType;
        $exporters = $elementType::exporters($this->sourceKey);

        foreach ($exporters as $i => $exporter) {
            // $action could be a string or config array
            if ($exporter instanceof ElementExporterInterface) {
                $exporter->setElementType($elementType);
            } else {
                if (is_string($exporter)) {
                    $exporter = ['type' => $exporter];
                }
                $exporter['elementType'] = $elementType;
                $exporters[$i] = $exporter = Craft::$app->getElements()->createExporter($exporter);

                if ($exporters[$i] === null) {
                    unset($exporters[$i]);
                }
            }
        }

        return array_values($exporters);
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
                'download' => $action->isDownload(),
                'name' => $action->getTriggerLabel(),
                'trigger' => $action->getTriggerHtml(),
                'confirm' => $action->getConfirmationMessage(),
                'settings' => $action->getSettings() ?: null,
            ];
        }

        return $actionData;
    }

    /**
     * Returns the data for the available exporters.
     *
     * @return array|null
     * @since 3.4.0
     */
    protected function exporterData()
    {
        if (empty($this->exporters)) {
            return null;
        }

        $exporterData = [];

        foreach ($this->exporters as $exporter) {
            $exporterData[] = [
                'type' => get_class($exporter),
                'name' => $exporter::displayName(),
                'formattable' => $exporter::isFormattable(),
            ];
        }

        return $exporterData;
    }
}
