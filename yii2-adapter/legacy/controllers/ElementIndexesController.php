<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Component;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementExporterInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Element\Queries\ExcludeDescendantIdsExpression;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Conditions;
use CraftCms\Cms\Support\Facades\ElementActions;
use CraftCms\Cms\Support\Facades\ElementExporters;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Typecast;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Throwable;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;
use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * The ElementIndexesController class is a controller that handles various element index related actions.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.0.0
 */
class ElementIndexesController extends BaseElementsController
{
    /**
     * @var class-string<ElementInterface>
     */
    protected string $elementType;

    protected string $context;

    protected ?string $sourceKey = null;

    protected ?array $source = null;

    /**
     * @var FieldLayout[]|null
     * @since 5.9.18
     */
    protected ?array $fieldLayouts = null;

    /**
     * @var ElementConditionInterface|null
     * @since 4.0.0
     */
    protected ?ElementConditionInterface $condition = null;

    protected ?array $viewState = null;

    protected ElementQueryInterface|null $elementQuery = null;

    /**
     * @since 5.0.0
     */
    protected ElementQueryInterface|null $unfilteredElementQuery = null;

    /**
     * @var \CraftCms\Cms\Element\Contracts\ElementActionInterface[]|null
     */
    protected ?array $actions = null;

    /**
     * @var ElementExporterInterface[]|null
     */
    protected ?array $exporters = null;

    /**
     * {@inheritdoc}
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->requireAcceptsJson();

        $this->elementType = $this->elementType();
        $this->context = $this->context();
        $this->sourceKey = $this->request->getParam('source') ?: null;
        $this->source = $this->source();
        $this->fieldLayouts = $this->fieldLayouts();
        $this->condition = $this->condition();

        if (!in_array($action->id, ['filter-hud', 'save-elements'])) {
            $this->viewState = $this->viewState();
            $this->elementQuery = $this->elementQuery();

            if (
                in_array($action->id, ['get-elements', 'get-more-elements'], true) &&
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
        $elementSources = app(ElementSources::class);

        if ($this->sourceKey) {
            $sortOptions = $elementSources->getSourceSortOptions($this->elementType, $this->sourceKey)
                ->map(fn(array $option) => [
                    'label' => $option['label'],
                    'attr' => $option['attribute'] ?? $option['orderBy'],
                    'defaultDir' => $option['defaultDir'] ?? 'asc',
                ])
                ->values()
                ->all();

            $tableColumns = $elementSources->getSourceTableAttributes($this->elementType, $this->sourceKey)
                ->map(fn(array $attribute, string $key) => [
                    ...$attribute,
                    'attr' => $key,
                ])
                ->values()
                ->all();

            $defaultTableColumns = Collection::make($elementSources->getTableAttributes(
                elementType: $this->elementType,
                sourceKey: $this->sourceKey,
                fieldLayouts: $this->fieldLayouts
            ))
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
     */
    public function actionGetElements(): Response
    {
        $responseData = $this->elementResponseData(true, $this->isAdministrative());

        return $this->asJson($responseData);
    }

    /**
     * Renders and returns a subsequent batch of elements for an element index.
     */
    public function actionGetMoreElements(): Response
    {
        $responseData = $this->elementResponseData(false, false);

        return $this->asJson($responseData);
    }

    /**
     * Returns the total number of elements that match the current criteria.
     *
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
     * Returns the source tree HTML for an element index.
     */
    public function actionGetSourceTreeHtml(): Response
    {
        $this->requireAcceptsJson();

        $sources = app(ElementSources::class)->getSources($this->elementType, $this->context);

        return $this->asJson([
            'html' => template('_elements/sources', [
                'elementType' => $this->elementType,
                'sources' => $sources->all(),
            ]),
        ]);
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

        if (!$conditionConfig && $serialized) {
            parse_str($serialized, $conditionConfig);
            $conditionConfig = $conditionConfig['condition'];
        }

        if ($conditionConfig) {
            /** @var ElementConditionInterface $condition */
            $condition = Conditions::createCondition($conditionConfig);
        } else {
            $condition = $this->elementType()::createCondition();
        }

        if (!empty($this->fieldLayouts)) {
            $condition->setFieldLayouts($this->fieldLayouts);
        }

        $condition->mainTag = 'div';
        $condition->id = $id;
        $condition->addRuleLabel = t('Add a filter');

        // Filter out any condition rules that touch the same query params as the source criteria
        if ($this->source['type'] === ElementSources::TYPE_NATIVE) {
            $condition->queryParams = array_keys($this->source['criteria'] ?? []);
            $condition->sourceKey = $this->sourceKey;
        } else {
            /** @var ElementConditionInterface $sourceCondition */
            $sourceCondition = Conditions::createCondition($this->source['condition']);
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

        return $this->asJson([
            'hudHtml' => $html,
            'headHtml' => HtmlStack::headHtml(),
            'bodyHtml' => HtmlStack::bodyHtml(),
        ]);
    }

    /**
     * Saves inline-edited elements.
     *
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

        // get all the elements
        $elementIds = array_map(
            fn(string $key) => (int) Str::chopStart($key, 'element-'),
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
            Gate::authorize('save', $element);
        }

        // set attributes and validate everything
        $errors = [];
        foreach ($elements as $element) {
            $attributes = Arr::except($data["element-$element->id"], 'fields');
            if (!empty($attributes)) {
                $scenario = $element->ruleset->getScenario();
                $element->ruleset->useScenario(ElementRules::SCENARIO_LIVE);
                $element->setAttributesFromRequest($attributes);
                $element->ruleset->useScenario($scenario);
            }

            $element->setFieldValuesFromRequest("$namespace.element-$element->id.fields");

            if ($element->getIsUnpublishedDraft()) {
                $element->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);
            } elseif ($element->enabled && $element->getEnabledForSite()) {
                $element->ruleset->useScenario(ElementRules::SCENARIO_LIVE);
            }

            $names = array_merge(
                array_keys($attributes),
                array_map(fn(string $handle) => "field:$handle", array_keys($data["element-$element->id"]['fields'] ?? [])),
            );

            if (!$element->validate($names)) {
                $errors[$element->getCanonicalId()] = $element->errors()->getMessages();
            }
        }

        if (!empty($errors)) {
            return $this->asJson([
                'errors' => $errors,
            ]);
        }

        // now save everything
        DB::beginTransaction();

        try {
            foreach ($elements as $element) {
                if (!Elements::saveElement($element)) {
                    Log::error("Couldn’t save element $element->id: " . implode(', ', $element->getFirstErrors()));
                    throw new ServerErrorHttpException("Couldn’t save element $element->id");
                }
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $this->asSuccess();
    }

    /**
     * Returns whether the element index has an administrative context (`index` or `embedded-index`).
     *
     * @since 5.0.0
     */
    protected function isAdministrative(): bool
    {
        return in_array($this->context, ['index', 'embedded-index']);
    }

    /**
     * Returns the selected source info.
     *
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
                'label' => t('All elements'),
                'hasThumbs' => $this->elementType::hasThumbs(),
            ];
        }

        $source = app(ElementSources::class)->findSource($this->elementType, $this->sourceKey, $this->context);

        if ($source === null) {
            // That wasn't a valid source, or the user doesn't have access to it in this context
            $this->sourceKey = null;
        }

        return $source;
    }

    private function fieldLayouts(): ?array
    {
        $fieldLayouts = $this->request->getBodyParam('fieldLayouts');

        if (empty($fieldLayouts)) {
            return null;
        }

        return array_map(
            fn(array $config) => FieldLayout::createFromConfig($config),
            Component::cleanseConfig($fieldLayouts),
        );
    }

    /**
     * Returns the current view state.
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
     */
    protected function elementQuery(): ElementQueryInterface
    {
        $query = $this->elementType::find();

        if (!$this->source) {
            $query->id(false);

            return $query;
        }

        // Does the source specify any criteria attributes?
        if ($this->source['type'] === ElementSources::TYPE_CUSTOM) {
            /** @var ElementConditionInterface $sourceCondition */
            $sourceCondition = Conditions::createCondition($this->source['condition']);
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
                    $criteria['draftOf'] = (int) $criteria['draftOf'];
                } else {
                    $criteria['draftOf'] = filter_var($criteria['draftOf'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                }
            }

            // Remove unsupported criteria attributes
            $criteria = ElementHelper::cleanseQueryCriteria($criteria);

            Typecast::configure($query, $criteria);

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
            $filterCondition = Conditions::createCondition($filterConditionConfig);
            $filterCondition->modifyQuery($query);
            $hasFilters = true;
        }

        // Exclude descendants of the collapsed element IDs
        $collapsedElementIds = $this->request->getParam('collapsedElementIds');

        if ($collapsedElementIds) {
            /** @var ElementQuery $query */
            $descendantQuery = (clone $query)
                ->offset(null)
                ->limit(null)
                ->reorder()
                ->positionedAfter(null)
                ->positionedBefore(null)
                ->status(null);

            // Get the actual elements
            $collapsedElements = (clone $descendantQuery)
                ->id($collapsedElementIds)
                ->orderBy('lft')
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
                    $query->where(new ExcludeDescendantIdsExpression($descendantIds));

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
     * @param  bool  $includeContainer  Whether the element container should be included in the response data
     * @param  bool  $includeActions  Whether info about the available actions should be included in the response data
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
                [
                    ...$this->viewState,
                    'fieldLayouts' => $this->fieldLayouts,
                ],
                $this->sourceKey,
                $this->context,
                $includeContainer,
                $selectable,
                $sortable,
            );

            $responseData['headHtml'] = $view->getHeadHtml();
            $responseData['bodyHtml'] = $view->getBodyHtml();
        } else {
            $responseData['html'] = Html::tag('div', t('Nothing yet.'), [
                'class' => ['zilch', 'small'],
            ]);
        }

        return $responseData;
    }

    /**
     * Returns the available actions for the current source.
     *
     * @return \CraftCms\Cms\Element\Contracts\ElementActionInterface[]|null
     */
    protected function availableActions(): ?array
    {
        return ElementActions::availableActions(
            elementType: $this->elementType,
            sourceKey: $this->sourceKey,
            elementQuery: $this->elementQuery,
        );
    }

    /**
     * Returns the available exporters for the current source.
     *
     * @return ElementExporterInterface[]|null
     *
     * @since 3.4.0
     */
    protected function availableExporters(): ?array
    {
        if ($this->request->isMobileBrowser()) {
            return null;
        }

        return ElementExporters::availableExporters($this->elementType, $this->sourceKey);
    }

    /**
     * Returns the data for the available actions.
     */
    protected function actionData(): ?array
    {
        if (empty($this->actions)) {
            return null;
        }

        return ElementActions::serializeActions($this->actions);
    }

    /**
     * Returns the data for the available exporters.
     *
     * @since 3.4.0
     */
    protected function exporterData(): ?array
    {
        if (empty($this->exporters)) {
            return null;
        }

        return ElementExporters::serializeExporters($this->exporters);
    }

    /**
     * Returns the updated table attribute HTML for an element.
     *
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     */
    public function actionElementTableHtml(): Response
    {
        $this->requireAcceptsJson();

        if (!$this->sourceKey) {
            throw new BadRequestHttpException('Request missing required body param');
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
            elementType: $this->elementType,
            sourceKey: $this->sourceKey,
            customAttributes: $this->viewState['tableColumns'] ?? null,
            fieldLayouts: $this->fieldLayouts,
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
