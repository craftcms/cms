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
use craft\base\ElementExporterInterface;
use craft\base\ElementInterface;
use craft\db\ExcludeDescendantIdsExpression;
use craft\elements\actions\DeleteActionInterface;
use craft\elements\actions\Restore;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\exporters\Raw;
use craft\events\ElementActionEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Component;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\services\ElementSources;
use Illuminate\Support\Collection;
use Throwable;
use yii\base\InvalidValueException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

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
     * @var class-string<ElementInterface>
     */
    protected string $elementType;

    /**
     * @var string
     */
    protected string $context;

    /**
     * @var string|null
     */
    protected ?string $sourceKey = null;

    /**
     * @var array|null
     */
    protected ?array $source = null;

    /**
     * @var ElementConditionInterface|null
     * @since 4.0.0
     */
    protected ?ElementConditionInterface $condition = null;

    /**
     * @var array|null
     */
    protected ?array $viewState = null;

    /**
     * @var ElementQueryInterface|null
     */
    protected ?ElementQueryInterface $elementQuery = null;

    /**
     * @var ElementQueryInterface|null
     * @since 5.0.0
     */
    protected ?ElementQueryInterface $unfilteredElementQuery = null;

    /**
     * @var ElementActionInterface[]|null
     */
    protected ?array $actions = null;

    /**
     * @var ElementExporterInterface[]|null
     */
    protected ?array $exporters = null;

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
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
        $this->condition = $this->condition();

        if (!in_array($action->id, ['filter-hud', 'save-elements'])) {
            $this->viewState = $this->viewState();
            $this->elementQuery = $this->elementQuery();

            if (
                in_array($action->id, ['get-elements', 'get-more-elements', 'perform-action', 'export']) &&
                $this->isAdministrative() &&
                isset($this->sourceKey)
            ) {
                $this->actions = $this->availableActions();
                $this->exporters = $this->availableExporters();
            }
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
     * Returns the source path for the given source key, step key, and context.
     *
     * @since 4.4.12
     */
    public function actionSourcePath(): Response
    {
        $stepKey = $this->request->getRequiredBodyParam('stepKey');
        $sourcePath = $this->elementType::sourcePath($this->sourceKey, $stepKey, $this->context);

        return $this->asJson([
            'sourcePath' => $sourcePath,
        ]);
    }

    /**
     * Returns attribute info for the current source.
     *
     * @since 5.9.0
     */
    public function actionSourceAttributeInfo(): Response
    {
        $elementSources = Craft::$app->getElementSources();

        if ($this->sourceKey) {
            $sortOptions = Collection::make($elementSources->getSourceSortOptions($this->elementType, $this->sourceKey))
                ->map(fn(array $option) => [
                    'label' => $option['label'],
                    'attr' => $option['attribute'] ?? $option['orderBy'],
                    'defaultDir' => $option['defaultDir'] ?? 'asc',
                ])
                ->values()
                ->all();

            $tableColumns = Collection::make($elementSources->getSourceTableAttributes($this->elementType, $this->sourceKey))
                ->map(fn(array $attribute, string $key) => [
                    ...$attribute,
                    'attr' => $key,
                ])
                ->values()
                ->all();

            $defaultTableColumns = Collection::make($elementSources->getTableAttributes($this->elementType, $this->sourceKey))
                ->map(fn(array $attribute) => $attribute[0])
                ->filter(fn(string $attribute) => $attribute !== 'title')
                ->values()
                ->all();
        } else {
            $sortOptions = [];
            $tableColumns = [];
            $defaultTableColumns = [];
        }

        return $this->asJson(compact(
            'sortOptions',
            'tableColumns',
            'defaultTableColumns',
        ));
    }

    /**
     * Renders and returns an element index container, plus its first batch of elements.
     *
     * @return Response
     */
    public function actionGetElements(): Response
    {
        $responseData = $this->elementResponseData(true, $this->isAdministrative());
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
        $total = $this->elementType::indexElementCount($this->elementQuery, $this->sourceKey);

        if (isset($this->unfilteredElementQuery)) {
            $unfilteredTotal = $this->elementType::indexElementCount($this->unfilteredElementQuery, $this->sourceKey);
        } else {
            $unfilteredTotal = $total;
        }

        return $this->asJson([
            'resultSet' => $this->request->getParam('resultSet'),
            'total' => $total,
            'unfilteredTotal' => $unfilteredTotal,
        ]);
    }

    /**
     * Performs an action on one or more selected elements.
     *
     * @return Response|null
     * @throws BadRequestHttpException if the requested element action is not supported by the element type, or its parameters didn’t validate
     */
    public function actionPerformAction(): ?Response
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
        $actionCriteria = (clone $this->elementQuery)
            ->offset(0)
            ->limit(null)
            ->orderBy([])
            ->positionedAfter(null)
            ->positionedBefore(null)
            ->id($elementIds);

        // Fire a 'beforePerformAction' event
        $event = new ElementActionEvent([
            'action' => $action,
            'criteria' => $actionCriteria,
        ]);

        $elementsService->trigger($elementsService::EVENT_BEFORE_PERFORM_ACTION, $event);

        if ($event->isValid) {
            $success = $action->performAction($actionCriteria);
            $message = $action->getMessage();

            if ($success) {
                // Fire an 'afterPerformAction' event
                $elementsService->trigger($elementsService::EVENT_AFTER_PERFORM_ACTION, new ElementActionEvent([
                    'action' => $action,
                    'criteria' => $actionCriteria,
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

        if (!$success) {
            return $this->asFailure($message);
        }

        // Send a new set of elements
        $responseData = $this->elementResponseData(true, true);

        // Send updated badge counts
        $formatter = Craft::$app->getFormatter();
        foreach (Craft::$app->getElementSources()->getSources($this->elementType, $this->context) as $source) {
            if (isset($source['key'])) {
                if (isset($source['badgeCount'])) {
                    $responseData['badgeCounts'][$source['key']] = $formatter->asDecimal($source['badgeCount'], 0);
                } else {
                    $responseData['badgeCounts'][$source['key']] = null;
                }
            }
        }

        return $this->asSuccess($message, data: $responseData);
    }

    /**
     * Returns the source tree HTML for an element index.
     *
     * @return Response
     */
    public function actionGetSourceTreeHtml(): Response
    {
        $this->requireAcceptsJson();

        $sources = Craft::$app->getElementSources()->getSources($this->elementType, $this->context);

        return $this->asJson([
            'html' => $this->getView()->renderTemplate('_elements/sources.twig', [
                'elementType' => $this->elementType,
                'sources' => $sources,
            ]),
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
            // Handle being passed in a generator function or other callable
            if (is_callable($export)) {
                $export = $export();
            }
            if (!is_iterable($export)) {
                throw new InvalidValueException(get_class($exporter) . '::export() must return an array or generator function since isFormattable() returns true.');
            }

            $this->response->data = $export;

            switch ($this->response->format) {
                case Response::FORMAT_JSON:
                    $this->response->formatters[Response::FORMAT_JSON]['prettyPrint'] = true;
                    break;
                case Response::FORMAT_XML:
                    Craft::$app->language = 'en-US';
                    $this->response->formatters[Response::FORMAT_XML]['rootTag'] = StringHelper::toCamelCase($this->elementType::pluralLowerDisplayName());
                    break;
            }
        } elseif (
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

        if (!$this->isAdministrative()) {
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
     * Creates a filter HUD’s contents.
     *
     * @since 4.0.0
     */
    public function actionFilterHud(): Response
    {
        $id = $this->request->getRequiredBodyParam('id');
        $conditionConfig = $this->request->getBodyParam('conditionConfig');
        $serialized = $this->request->getBodyParam('serialized');
        $fieldLayouts = $this->request->getBodyParam('fieldLayouts');

        $conditionsService = Craft::$app->getConditions();

        if ($conditionConfig) {
            $conditionConfig = Component::cleanseConfig($conditionConfig);
            /** @var ElementConditionInterface $condition */
            $condition = $conditionsService->createCondition($conditionConfig);
        } elseif ($serialized) {
            parse_str($serialized, $conditionConfig);
            /** @var ElementConditionInterface $condition */
            $condition = $conditionsService->createCondition($conditionConfig['condition']);
        } else {
            /** @var ElementConditionInterface $condition */
            $condition = $this->elementType()::createCondition();
        }

        if (!empty($fieldLayouts)) {
            $condition->setFieldLayouts(array_map(fn(array $config) => FieldLayout::createFromConfig($config), $fieldLayouts));
        }

        $condition->mainTag = 'div';
        $condition->id = $id;
        $condition->addRuleLabel = Craft::t('app', 'Add a filter');

        // Filter out any condition rules that touch the same query params as the source criteria
        if ($this->source['type'] === ElementSources::TYPE_NATIVE) {
            $condition->queryParams = array_keys($this->source['criteria'] ?? []);
            $condition->sourceKey = $this->sourceKey;
        } else {
            /** @var ElementConditionInterface $sourceCondition */
            $sourceCondition = $conditionsService->createCondition($this->source['condition']);
            $condition->queryParams = [];
            foreach ($sourceCondition->getConditionRules() as $rule) {
                /** @var ElementConditionRuleInterface $rule */
                $params = $rule->getExclusiveQueryParams();
                foreach ($params as $param) {
                    $condition->queryParams[] = $param;
                }
            }
        }

        if ($this->condition) {
            foreach ($this->condition->getConditionRules() as $rule) {
                /** @var ElementConditionRuleInterface $rule */
                $params = $rule->getExclusiveQueryParams();
                foreach ($params as $param) {
                    $condition->queryParams[] = $param;
                }
            }
        }

        $condition->queryParams[] = 'site';
        $condition->queryParams[] = 'status';

        $html = $condition->getBuilderHtml();

        $view = Craft::$app->getView();
        return $this->asJson([
            'hudHtml' => $html,
            'headHtml' => $view->getHeadHtml(),
            'bodyHtml' => $view->getBodyHtml(),
        ]);
    }

    /**
     * Saves inline-edited elements.
     *
     * @return Response
     * @since 5.0.0
     */
    public function actionSaveElements(): Response
    {
        $siteId = $this->request->getRequiredBodyParam('siteId');
        $namespace = $this->request->getRequiredBodyParam('namespace');
        $data = $this->request->getRequiredBodyParam($namespace);

        if (empty($data)) {
            throw new BadRequestHttpException('No element data provided.');
        }

        $elementsService = Craft::$app->getElements();
        $user = static::currentUser();

        // get all the elements
        $elementIds = array_map(
            fn(string $key) => (int)StringHelper::removeLeft($key, 'element-'),
            array_keys($data),
        );
        $elements = $this->elementType()::find()
            ->id($elementIds)
            ->status(null)
            ->drafts(null)
            ->provisionalDrafts(null)
            ->siteId($siteId)
            ->all();

        if (empty($elements)) {
            throw new BadRequestHttpException('No valid element IDs provided.');
        }

        // make sure they're editable
        foreach ($elements as $element) {
            if (!$elementsService->canSave($element, $user)) {
                throw new ForbiddenHttpException('User not authorized to save this element.');
            }
        }

        // set attributes and validate everything
        $errors = [];
        foreach ($elements as $element) {
            $attributes = ArrayHelper::without($data["element-$element->id"], 'fields');
            if (!empty($attributes)) {
                $scenario = $element->getScenario();
                $element->setScenario(Element::SCENARIO_LIVE);
                $element->setAttributesFromRequest($attributes);
                $element->setScenario($scenario);
            }

            $element->setFieldValuesFromRequest("$namespace.element-$element->id.fields");

            if ($element->getIsUnpublishedDraft()) {
                $element->setScenario(Element::SCENARIO_ESSENTIALS);
            } elseif ($element->enabled && $element->getEnabledForSite()) {
                $element->setScenario(Element::SCENARIO_LIVE);
            }

            $names = array_merge(
                array_keys($attributes),
                array_map(fn(string $handle) => "field:$handle", array_keys($data["element-$element->id"]['fields'] ?? [])),
            );

            if (!$element->validate($names)) {
                $errors[$element->getCanonicalId()] = $element->getErrors();
            }
        }

        if (!empty($errors)) {
            return $this->asJson([
                'errors' => $errors,
            ]);
        }

        // now save everything
        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            foreach ($elements as $element) {
                if (!$elementsService->saveElement($element)) {
                    Craft::error("Couldn’t save element $element->id: " . implode(', ', $element->getFirstErrors()));
                    throw new ServerErrorHttpException("Couldn’t save element $element->id");
                }
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        return $this->asSuccess();
    }

    /**
     * Returns whether the element index has an administrative context (`index` or `embedded-index`).
     *
     * @return bool
     * @since 5.0.0
     */
    protected function isAdministrative(): bool
    {
        return in_array($this->context, ['index', 'embedded-index']);
    }

    /**
     * Returns the selected source info.
     *
     * @return array|null
     * @throws ForbiddenHttpException if the user is not permitted to access the requested source
     */
    protected function source(): ?array
    {
        if (!isset($this->sourceKey)) {
            return null;
        }

        if ($this->sourceKey === '__IMP__') {
            return [
                'type' => ElementSources::TYPE_NATIVE,
                'key' => '__IMP__',
                'label' => Craft::t('app', 'All elements'),
                'hasThumbs' => $this->elementType::hasThumbs(),
            ];
        }

        $source = ElementHelper::findSource($this->elementType, $this->sourceKey, $this->context);

        if ($source === null) {
            // That wasn't a valid source, or the user doesn't have access to it in this context
            $this->sourceKey = null;
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
        $query = $this->elementType::find();
        $conditionsService = Craft::$app->getConditions();

        if (!$this->source) {
            $query->id(false);
            return $query;
        }

        // Does the source specify any criteria attributes?
        if ($this->source['type'] === ElementSources::TYPE_CUSTOM) {
            /** @var ElementConditionInterface $sourceCondition */
            $sourceCondition = $conditionsService->createCondition($this->source['condition']);
            $sourceCondition->modifyQuery($query);
        }

        $applyCriteria = function(array $criteria) use ($query): bool {
            if (!$criteria) {
                return false;
            }

            if (isset($criteria['trashed'])) {
                $criteria['trashed'] = filter_var($criteria['trashed'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            }
            if (isset($criteria['drafts'])) {
                $criteria['drafts'] = filter_var($criteria['drafts'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            }
            if (isset($criteria['draftOf'])) {
                if (is_numeric($criteria['draftOf']) && $criteria['draftOf'] != 0) {
                    $criteria['draftOf'] = (int)$criteria['draftOf'];
                } else {
                    $criteria['draftOf'] = filter_var($criteria['draftOf'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                }
            }

            // Remove unsupported criteria attributes
            unset(
                $criteria['where'],
                $criteria['orderBy'],
                $criteria['indexBy'],
                $criteria['select'],
                $criteria['selectOption'],
                $criteria['from'],
                $criteria['groupBy'],
                $criteria['join'],
                $criteria['having'],
                $criteria['union'],
                $criteria['withQueries'],
                $criteria['params'],
            );

            Craft::configure($query, Component::cleanseConfig($criteria));
            return true;
        };

        $applyCriteria($this->request->getBodyParam('baseCriteria') ?? []);

        // Now we move onto things the user could have modified...
        $unfilteredQuery = (clone $query);
        $hasFilters = false;

        // Was a condition provided?
        if (isset($this->condition)) {
            $this->condition->modifyQuery($query);
            $hasFilters = true;
        }

        if ($applyCriteria($this->request->getBodyParam('criteria') ?? [])) {
            $hasFilters = true;
        }

        // Override with the custom filters
        $filterConditionConfig = $this->request->getBodyParam('filterConfig');
        if (!$filterConditionConfig) {
            $filterConditionStr = $this->request->getBodyParam('filters');
            if ($filterConditionStr) {
                parse_str($filterConditionStr, $filterConditionConfig);
                $filterConditionConfig = $filterConditionConfig['condition'];
            }
        }
        if ($filterConditionConfig) {
            /** @var ElementConditionInterface $filterCondition */
            $filterCondition = $conditionsService->createCondition(Component::cleanseConfig($filterConditionConfig));
            $filterCondition->modifyQuery($query);
            $hasFilters = true;
        }

        // Exclude descendants of the collapsed element IDs
        $collapsedElementIds = $this->request->getParam('collapsedElementIds');

        if ($collapsedElementIds) {
            $descendantQuery = (clone $query)
                ->offset(null)
                ->limit(null)
                ->orderBy([])
                ->positionedAfter(null)
                ->positionedBefore(null)
                ->status(null);

            // Get the actual elements
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
                    /** @phpstan-ignore-next-line */
                    $query->andWhere(new ExcludeDescendantIdsExpression($descendantIds));
                    $hasFilters = true;
                }
            }
        }

        // Only set unfilteredElementQuery if there were any filters,
        // so we know there weren't any filters in play if it's null
        if ($hasFilters) {
            $this->unfilteredElementQuery = $unfilteredQuery;
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
            $responseData['actions'] = $this->viewState['static'] === true ? [] : $this->actionData();
            $responseData['actionsHeadHtml'] = $view->getHeadHtml();
            $responseData['actionsBodyHtml'] = $view->getBodyHtml();
            $responseData['exporters'] = $this->exporterData();
        }

        $disabledElementIds = $this->request->getParam('disabledElementIds', []);
        $selectable = (
            (!empty($this->actions) || $this->request->getParam('selectable')) &&
            empty($this->viewState['inlineEditing'])
        );
        $sortable = $this->isAdministrative() && $this->request->getParam('sortable');

        if ($this->sourceKey) {
            $responseData['html'] = $this->elementType::indexHtml(
                $this->elementQuery,
                $disabledElementIds,
                $this->viewState,
                $this->sourceKey,
                $this->context,
                $includeContainer,
                $selectable,
                $sortable,
            );

            $responseData['headHtml'] = $view->getHeadHtml();
            $responseData['bodyHtml'] = $view->getBodyHtml();
        } else {
            $responseData['html'] = Html::tag('div', Craft::t('app', 'Nothing yet.'), [
                'class' => ['zilch', 'small'],
            ]);
        }

        return $responseData;
    }

    /**
     * Returns the available actions for the current source.
     *
     * @return ElementActionInterface[]|null
     */
    protected function availableActions(): ?array
    {
        $actions = $this->elementType::actions($this->sourceKey);

        foreach ($actions as $i => $action) {
            // $action could be a string or config array
            if ($action instanceof ElementActionInterface) {
                $action->setElementType($this->elementType);
            } else {
                if (is_string($action)) {
                    $action = ['type' => $action];
                }
                /** @var array $action */
                /** @phpstan-var array{type:class-string<ElementActionInterface>} $action */
                $action['elementType'] = $this->elementType;
                $actions[$i] = $action = Craft::$app->getElements()->createAction($action);
            }

            if ($this->elementQuery->trashed) {
                if ($action instanceof DeleteActionInterface && $action->canHardDelete()) {
                    $action->setHardDelete();
                } elseif (!$action instanceof Restore) {
                    unset($actions[$i]);
                }
            } elseif ($action instanceof Restore) {
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
    protected function availableExporters(): ?array
    {
        if ($this->request->isMobileBrowser()) {
            return null;
        }

        $exporters = $this->elementType::exporters($this->sourceKey);

        foreach ($exporters as $i => $exporter) {
            // $action could be a string or config array
            if ($exporter instanceof ElementExporterInterface) {
                $exporter->setElementType($this->elementType);
            } else {
                if (is_string($exporter)) {
                    $exporter = ['type' => $exporter];
                }
                $exporter['elementType'] = $this->elementType;
                $exporters[$i] = Craft::$app->getElements()->createExporter($exporter);
            }
        }

        return array_values($exporters);
    }

    /**
     * Returns the data for the available actions.
     *
     * @return array|null
     */
    protected function actionData(): ?array
    {
        if (empty($this->actions)) {
            return null;
        }

        $actionData = [];

        /** @var ElementAction $action */
        foreach ($this->actions as $action) {
            $actionData[] = ElementHelper::actionConfig($action);
        }

        return $actionData;
    }

    /**
     * Returns the data for the available exporters.
     *
     * @return array|null
     * @since 3.4.0
     */
    protected function exporterData(): ?array
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

    /**
     * Returns the updated table attribute HTML for an element.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     */
    public function actionElementTableHtml(): Response
    {
        $this->requireAcceptsJson();

        if (!$this->sourceKey) {
            throw new BadRequestHttpException("Request missing required body param");
        }

        $id = $this->request->getRequiredBodyParam('id');
        if (!$id || !is_numeric($id)) {
            throw new BadRequestHttpException("Invalid element ID: $id");
        }

        // check for a provisional draft first
        /** @var ElementInterface|null $element */
        $element = (clone $this->elementQuery)
            ->draftOf($id)
            ->draftCreator(static::currentUser())
            ->provisionalDrafts()
            ->status(null)
            ->one();

        if (!$element) {
            /** @var ElementInterface|null $element */
            $element = (clone $this->elementQuery)
                ->id($id)
                ->status(null)
                ->one();
        }

        if (!$element) {
            throw new BadRequestHttpException("Invalid element ID: $id");
        }

        $attributes = Craft::$app->getElementSources()->getTableAttributes(
            $this->elementType,
            $this->sourceKey,
            $this->viewState['tableColumns'] ?? null,
        );
        $attributeHtml = [];

        foreach ($attributes as [$attribute]) {
            $attributeHtml[$attribute] = $element->getAttributeHtml($attribute);
        }

        return $this->asJson([
            'attributeHtml' => $attributeHtml,
        ]);
    }
}
