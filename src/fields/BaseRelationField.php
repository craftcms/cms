<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\conditions\ConditionInterface;
use craft\base\EagerLoadingFieldInterface;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\InlineEditableFieldInterface;
use craft\base\NestedElementInterface;
use craft\behaviors\EventBehavior;
use craft\db\Query;
use craft\db\Table as DbTable;
use craft\elements\conditions\ElementCondition;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\ElementRelationParamParser;
use craft\elements\db\OrderByPlaceholderExpression;
use craft\elements\ElementCollection;
use craft\errors\SiteNotFoundException;
use craft\events\CancelableEvent;
use craft\events\ElementCriteriaEvent;
use craft\fields\conditions\RelationalFieldConditionRule;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\Queue;
use craft\helpers\StringHelper;
use craft\queue\jobs\LocalizeRelations;
use craft\services\Elements;
use craft\services\ElementSources;
use DateTime;
use GraphQL\Type\Definition\Type;
use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\db\Expression;
use yii\validators\NumberValidator;

/**
 * BaseRelationField is the base class for classes representing a relational field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
abstract class BaseRelationField extends Field implements InlineEditableFieldInterface, EagerLoadingFieldInterface
{
    /**
     * @event ElementCriteriaEvent The event that is triggered when defining the selection criteria for this field.
     * @since 3.4.16
     */
    public const EVENT_DEFINE_SELECTION_CRITERIA = 'defineSelectionCriteria';

    private static bool $validatingRelatedElements = false;

    /**
     * @inheritdoc
     */
    public static function supportedTranslationMethods(): array
    {
        // Don't ever automatically propagate values to other sites.
        return [
            self::TRANSLATION_METHOD_SITE,
        ];
    }

    /**
     * Returns the element class associated with this field type.
     *
     * @return string The Element class name
     * @phpstan-return class-string<ElementInterface>
     */
    abstract public static function elementType(): string;

    /**
     * Returns the default [[selectionLabel]] value.
     *
     * @return string The default selection label
     */
    public static function defaultSelectionLabel(): string
    {
        return Craft::t('app', 'Choose');
    }

    /**
     * @inheritdoc
     */
    public static function phpType(): string
    {
        return sprintf('\\%s|\\%s<\\%s>', ElementQueryInterface::class, ElementCollection::class, ElementInterface::class);
    }

    /**
     * @inheritdoc
     */
    public static function dbType(): array|string|null
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public static function queryCondition(array $instances, mixed $value, array &$params): array|false
    {
        /** @var self $field */
        $field = reset($instances);

        if (!is_array($value)) {
            $value = [$value];
        }

        $conditions = [];

        if (isset($value[0]) && in_array($value[0], [':notempty:', ':empty:', 'not :empty:'])) {
            $emptyCondition = array_shift($value);
            if (in_array($emptyCondition, [':notempty:', 'not :empty:'])) {
                $conditions[] = static::existsQueryCondition($field);
            } else {
                $conditions[] = ['not', static::existsQueryCondition($field)];
            }
        }

        if (!empty($value)) {
            $parser = new ElementRelationParamParser([
                'fields' => [
                    $field->handle => $field,
                ],
            ]);
            $condition = $parser->parse([
                'targetElement' => $value,
                'field' => $field->handle,
            ]);
            if ($condition !== false) {
                $conditions[] = $condition;
            }
        }

        if (empty($conditions)) {
            return false;
        }

        array_unshift($conditions, 'or');
        return $conditions;
    }

    /**
     * Returns a query builder-compatible condition for an element query,
     * limiting the results to only elements where the given relation field has a value.
     *
     * @param self $field The relation field
     * @param bool $enabledOnly Whether to only
     * @param bool $inTargetSiteOnly
     * @return array
     * @since 4.10.0
     */
    public static function existsQueryCondition(self $field, bool $enabledOnly = true, bool $inTargetSiteOnly = true): array
    {
        $ns = sprintf('%s_%s', $field->handle, StringHelper::randomString(5));

        $query = (new Query())
            ->from(["relations_$ns" => DbTable::RELATIONS])
            ->innerJoin(["elements_$ns" => DbTable::ELEMENTS], "[[elements_$ns.id]] = [[relations_$ns.targetId]]")
            ->leftJoin(["elements_sites_$ns" => DbTable::ELEMENTS_SITES], "[[elements_sites_$ns.elementId]] = [[elements_$ns.id]]")
            ->where([
                'and',
                "[[relations_$ns.sourceId]] = [[elements.id]]",
                [
                    "relations_$ns.fieldId" => $field->id,
                    "elements_$ns.dateDeleted" => null,
                ],
                [
                    'or',
                    ["relations_$ns.sourceSiteId" => null],
                    ["relations_$ns.sourceSiteId" => new Expression('[[elements_sites.siteId]]')],
                ],
            ]);

        if ($enabledOnly) {
            $query->andWhere([
                "elements_$ns.enabled" => true,
                "elements_sites_$ns.enabled" => true,
            ]);
        }

        if ($inTargetSiteOnly) {
            $query->andWhere([
                "elements_sites_$ns.siteId" => $field->_targetSiteId() ?? new Expression('[[elements_sites.siteId]]'),
            ]);
        }

        return ['exists', $query];
    }

    /**
     * @var string|string[]|null The source keys that this field can relate elements from (used if [[allowMultipleSources]] is set to true)
     */
    public string|array|null $sources = '*';

    /**
     * @var string|null The source key that this field can relate elements from (used if [[allowMultipleSources]] is set to false)
     */
    public ?string $source = null;

    /**
     * @var string|null The UID of the site that this field should relate elements from
     */
    public ?string $targetSiteId = null;

    /**
     * @var bool Whether the site menu should be shown in element selector modals.
     * @since 3.5.0
     */
    public bool $showSiteMenu = false;

    /**
     * @var bool Whether to automatically relate structural ancestors.
     * @since 4.4.0
     */
    public bool $maintainHierarchy = false;

    /**
     * @var int|null Branch limit
     *
     * @since 4.4.0
     */
    public ?int $branchLimit = null;

    /**
     * @var string|null The view mode
     */
    public ?string $viewMode = null;

    /**
     * @var bool Whether cards should be shown in a multi-column grid
     * @since 5.0.0
     */
    public bool $showCardsInGrid = false;

    /**
     * @var int|null The maximum number of relations this field can have (used if [[allowLimit]] is set to true).
     * @since 4.0.0
     */
    public ?int $minRelations = null;

    /**
     * @var int|null The maximum number of relations this field can have (used if [[allowLimit]] is set to true).
     * @since 4.0.0
     */
    public ?int $maxRelations = null;

    /**
     * @var string|null The label that should be used on the selection input
     */
    public ?string $selectionLabel = null;

    /**
     * @var bool Whether related elements should be validated when the source element is saved.
     */
    public bool $validateRelatedElements = false;

    /**
     * @var bool Whether each site should get its own unique set of relations
     */
    public bool $localizeRelations = false;

    /**
     * @var bool Whether to allow multiple source selection in the settings
     */
    public bool $allowMultipleSources = true;

    /**
     * @var bool Whether to show the Min Relations and Max Relations settings.
     */
    public bool $allowLimit = true;

    /**
     * @var bool Whether elements should be allowed to relate themselves.
     * @since 3.4.21
     */
    public bool $allowSelfRelations = false;

    /**
     * @var bool Whether to allow the “Large Thumbnails” view mode
     */
    protected bool $allowLargeThumbsView = false;

    /**
     * @var string Template to use for settings rendering
     */
    protected string $settingsTemplate = '_components/fieldtypes/elementfieldsettings.twig';

    /**
     * @var string Template to use for field rendering
     */
    protected string $inputTemplate = '_includes/forms/elementSelect.twig';

    /**
     * @var string|null The JS class that should be initialized for the input
     */
    protected ?string $inputJsClass = null;

    /**
     * @var bool Whether the elements have a custom sort order
     */
    protected bool $sortable = true;

    /**
     * @var ElementConditionInterface|array|null
     * @phpstan-var ElementConditionInterface|array{class:class-string<ElementConditionInterface>}|null
     * @see getSelectionCondition()
     * @see setSelectionCondition()
     */
    private array|null|ElementConditionInterface $_selectionCondition = null;

    /**
     * @inheritdoc
     */
    public function __construct(array $config = [])
    {
        // limit => maxRelations
        if (array_key_exists('limit', $config)) {
            $config['maxRelations'] = ArrayHelper::remove($config, 'limit');
        }

        // Config normalization
        if (($config['source'] ?? null) === '') {
            unset($config['source']);
        }

        if (array_key_exists('sources', $config) && empty($config['sources'])) {
            // Not possible to have no sources selected, so go with the default
            unset($config['sources']);
        }

        // If useTargetSite is in here, but empty, then disregard targetSiteId
        if (array_key_exists('useTargetSite', $config)) {
            if (empty($config['useTargetSite'])) {
                unset($config['targetSiteId']);
            }
            unset($config['useTargetSite']);
        }

        // Default showSiteMenu to true for existing fields
        if (isset($config['id']) && !isset($config['showSiteMenu'])) {
            $config['showSiteMenu'] = true;
        }

        // if relating ancestors, then clear min/max limits, otherwise clear branch limit
        if ($config['maintainHierarchy'] ?? false) {
            $config['maxRelations'] = null;
            $config['minRelations'] = null;
        } else {
            $config['branchLimit'] = null;
        }

        // remove settings that shouldn't be here
        unset($config['allowMultipleSources'], $config['allowLimit'], $config['allowLargeThumbsView'], $config['sortable']);
        if ($this->allowMultipleSources) {
            unset($config['source']);
        } else {
            unset($config['sources']);
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     * @since 3.4.9
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['minRelations', 'maxRelations', 'branchLimit'], 'number', 'integerOnly' => true];
        $rules[] = [['source', 'sources'], 'validateSources'];
        return $rules;
    }

    /**
     * Ensure only one structured source is selected when maintainHierarchy is true.
     *
     * @param string $attribute
     * @since 4.4.0
     */
    public function validateSources(string $attribute): void
    {
        if (!$this->maintainHierarchy) {
            return;
        }

        $inputSources = $this->getInputSources();

        if ($inputSources === null) {
            $this->addError($attribute, Craft::t('app', 'A source is required when relating ancestors.'));
            return;
        }

        if (is_string($inputSources)) {
            $inputSources = [$inputSources];
        }

        $elementSources = ArrayHelper::whereIn(
            Craft::$app->elementSources->getSources(static::elementType()),
            'key',
            $inputSources
        );

        if (count($elementSources) > 1) {
            $this->addError($attribute, Craft::t('app', 'Only one source is allowed when relating ancestors.'));
        }

        foreach ($elementSources as $elementSource) {
            if (!isset($elementSource['structureId'])) {
                $this->addError(
                    $attribute,
                    Craft::t(
                        'app',
                        '{source} is not a structured source. Only structured sources may be used when relating ancestors.',
                        ['source' => $elementSource['label']]
                    )
                );
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'allowSelfRelations';
        $attributes[] = 'localizeRelations';
        $attributes[] = 'maxRelations';
        $attributes[] = 'minRelations';
        $attributes[] = 'selectionLabel';
        $attributes[] = 'showSiteMenu';
        $attributes[] = 'source';
        $attributes[] = 'sources';
        $attributes[] = 'targetSiteId';
        $attributes[] = 'validateRelatedElements';
        $attributes[] = 'viewMode';
        $attributes[] = 'showCardsInGrid';
        $attributes[] = 'allowSelfRelations';
        $attributes[] = 'maintainHierarchy';
        $attributes[] = 'branchLimit';

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function getSettings(): array
    {
        $settings = parent::getSettings();

        // cleanup
        unset($settings['allowMultipleSources'], $settings['allowLimit'], $settings['allowLargeThumbsView'], $settings['sortable']);
        if ($this->allowMultipleSources) {
            unset($settings['source']);
        } else {
            unset($settings['sources']);
        }

        if ($selectionCondition = $this->getSelectionCondition()) {
            $settings['selectionCondition'] = $selectionCondition->getConfig();
        }

        return $settings;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        $variables = $this->settingsTemplateVariables();
        $view = Craft::$app->getView();

        $view->registerJsWithVars(fn($args) => <<<JS
new Craft.ElementFieldSettings(...$args);
JS, [
                [
                    $this->allowMultipleSources,
                    $view->namespaceInputId('maintain-hierarchy-field'),
                    $view->namespaceInputId($this->allowMultipleSources ? 'sources-field' : 'source-field'),
                    $view->namespaceInputId('branch-limit-field'),
                    $view->namespaceInputId('min-relations-field'),
                    $view->namespaceInputId('max-relations-field'),
                    $view->namespaceInputId('viewMode-field'),
                ],
        ]);

        return $view->renderTemplate($this->settingsTemplate, $variables);
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        $rules = [
            ['validateRelationCount', 'on' => [Element::SCENARIO_LIVE], 'skipOnEmpty' => false],
        ];

        if ($this->validateRelatedElements) {
            $rules[] = ['validateRelatedElements', 'on' => [Element::SCENARIO_LIVE]];
        }

        return $rules;
    }

    /**
     * Validates that the number of related elements are within the min/max relation bounds.
     *
     * @param ElementInterface $element
     */
    public function validateRelationCount(ElementInterface $element): void
    {
        if ($this->allowLimit && ($this->minRelations || $this->maxRelations)) {
            /** @var ElementQueryInterface|ElementCollection $value */
            $value = $element->getFieldValue($this->handle);

            if ($value instanceof ElementQueryInterface) {
                $value = $this->_all($value, $element);
            }

            $arrayValidator = new NumberValidator([
                'min' => $this->minRelations,
                'max' => $this->maxRelations,
                'tooSmall' => $this->minRelations ? Craft::t('app', '{attribute} should contain at least {min, number} {min, plural, one{selection} other{selections}}.', [
                    'attribute' => Craft::t('site', $this->name),
                    'min' => $this->minRelations, // Need to pass this in now
                ]) : null,
                'tooBig' => $this->maxRelations ? Craft::t('app', '{attribute} should contain at most {max, number} {max, plural, one{selection} other{selections}}.', [
                    'attribute' => Craft::t('site', $this->name),
                    'max' => $this->maxRelations, // Need to pass this in now
                ]) : null,
                'skipOnEmpty' => false,
            ]);

            if (!$arrayValidator->validate($value->count(), $error)) {
                $element->addError($this->handle, $error);
            }
        }
    }

    /**
     * Validates the related elements.
     *
     * @param ElementInterface $element
     */
    public function validateRelatedElements(ElementInterface $element): void
    {
        // No recursive related element validation
        if (self::$validatingRelatedElements) {
            return;
        }

        /** @var ElementQueryInterface|ElementCollection $value */
        $value = $element->getFieldValue($this->handle);

        if ($value instanceof ElementQueryInterface) {
            $value
                ->site('*')
                ->unique()
                ->preferSites([$this->targetSiteId($element)]);
        }

        $errorCount = 0;

        foreach ($value->all() as $i => $target) {
            if (!self::_validateRelatedElement($element, $target)) {
                /** @phpstan-ignore-next-line */
                $element->addModelErrors($target, "$this->handle[$i]");
                $errorCount++;
            }
        }

        if ($errorCount) {
            /** @var ElementInterface|string $elementType */
            $elementType = static::elementType();
            $element->addError($this->handle, Craft::t('app', 'Validation errors found in {attribute} {type}; please fix them.', [
                'type' => $errorCount === 1 ? $elementType::lowerDisplayName() : $elementType::pluralLowerDisplayName(),
                'attribute' => Craft::t('site', $this->name),
            ]));
        }
    }

    /**
     * Returns whether a related element validates.
     *
     * @param ElementInterface $source
     * @param ElementInterface $target
     * @return bool
     */
    private static function _validateRelatedElement(ElementInterface $source, ElementInterface $target): bool
    {
        if (
            self::$validatingRelatedElements ||
            !$target->enabled ||
            !$target->getEnabledForSite() ||
            $target->getCanonicalId() === $source->getCanonicalId()
        ) {
            return true;
        }

        // Prevent relational fields on this element from enforcing related element validation
        self::$validatingRelatedElements = true;

        $target->setScenario(Element::SCENARIO_LIVE);
        $validates = $target->validate();

        self::$validatingRelatedElements = false;
        return $validates;
    }

    /**
     * @inheritdoc
     */
    public function isValueEmpty(mixed $value, ElementInterface $element): bool
    {
        /** @var ElementQueryInterface|ElementCollection $value */
        if ($value instanceof ElementQueryInterface) {
            return !$this->_all($value, $element)->exists();
        }

        return $value->isEmpty();
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value instanceof ElementQueryInterface || $value instanceof ElementCollection) {
            return $value;
        }

        /** @var string|ElementInterface $class */
        /** @phpstan-var class-string<ElementInterface>|ElementInterface $class */
        $class = static::elementType();
        /** @var ElementQuery $query */
        $query = $class::find()
            ->siteId($this->targetSiteId($element));

        // $value will be an array of element IDs if there was a validation error or we're loading a draft/version.
        if (is_array($value)) {
            $query
                ->id(array_values(array_filter($value)))
                ->fixedOrder();
        } elseif ($value !== '' && $element && $element->id) {
            if (!$this->allowMultipleSources && $this->source) {
                $source = ElementHelper::findSource($class, $this->source, ElementSources::CONTEXT_FIELD);

                // Does the source specify any criteria attributes?
                if (isset($source['criteria'])) {
                    Craft::configure($query, $source['criteria']);
                }
            }

            // Make our query customizations via EVENT_BEFORE_PREPARE/EVENT_AFTER_PREPARE,
            // so they get applied for cloned queries as well

            $query->attachBehavior(sprintf('%s-once', self::class), new EventBehavior([
                ElementQuery::EVENT_BEFORE_PREPARE => function(CancelableEvent  $event, ElementQuery $query) {
                    if ($this->maintainHierarchy && $query->id === null) {
                        $structuresService = Craft::$app->getStructures();

                        $structureElements = (clone($query))
                            ->status(null)
                            ->all();

                        // Fill in any gaps
                        $structuresService->fillGapsInElements($structureElements);

                        // Enforce the branch limit
                        if ($this->branchLimit) {
                            $structuresService->applyBranchLimitToElements($structureElements, $this->branchLimit);
                        }

                        $query->id(array_map(fn(ElementInterface $element) => $element->id, $structureElements));
                    }
                },
            ], true));

            $relationsAlias = sprintf('relations_%s', StringHelper::randomString(10));

            $query->attachBehavior(self::class, new EventBehavior([
                ElementQuery::EVENT_AFTER_PREPARE => function(
                    CancelableEvent $event,
                    ElementQuery $query,
                ) use ($element, $relationsAlias) {
                    // Make these changes directly on the prepared queries, so `sortOrder` doesn't ever make it into
                    // the criteria. Otherwise, if the query ends up A) getting executed normally, then B) getting
                    // eager-loaded with eagerly(), the `orderBy` value referencing the join table will get applied
                    // to the eager-loading query and cause a SQL error.
                    foreach ([$query->query, $query->subQuery] as $q) {
                        $q->innerJoin(
                            [$relationsAlias => DbTable::RELATIONS],
                            [
                                'and',
                                "[[$relationsAlias.targetId]] = [[elements.id]]",
                                [
                                    "$relationsAlias.sourceId" => $element->id,
                                    "$relationsAlias.fieldId" => $this->id,
                                ],
                                [
                                    'or',
                                    ["$relationsAlias.sourceSiteId" => null],
                                    ["$relationsAlias.sourceSiteId" => $element->siteId],
                                ],
                            ]
                        );

                        if (
                            $this->sortable &&
                            !$this->maintainHierarchy &&
                            count($query->orderBy ?? []) === 1 &&
                            ($query->orderBy[0] ?? null) instanceof OrderByPlaceholderExpression
                        ) {
                            $q->orderBy(["$relationsAlias.sortOrder" => SORT_ASC]);
                        }
                    }
                },
            ]));

            // Prepare the query for lazy eager loading
            $query->prepForEagerLoading($this->handle, $element);
        } else {
            $query->id(false);
        }

        if ($this->allowLimit && $this->maxRelations) {
            $query->limit($this->maxRelations);
        }

        return $query;
    }

    /**
     * @inheritdoc
     */
    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        /** @var ElementQueryInterface|ElementCollection $value */
        if ($value instanceof ElementCollection) {
            return $value->map(fn(ElementInterface $element) => $element->id)->all();
        }

        return $this->_all($value, $element)->ids();
    }

    /**
     * @inheritdoc
     */
    public function getElementConditionRuleType(): array|string|null
    {
        return RelationalFieldConditionRule::class;
    }

    /**
     * @inheritdoc
     */
    public function modifyElementIndexQuery(ElementQueryInterface $query): void
    {
        $criteria = [
            'drafts' => null,
            'status' => null,
        ];

        if (!$this->targetSiteId) {
            $criteria['siteId'] = '*';
            $criteria['unique'] = true;
            // Just to be safe...
            if (is_numeric($query->siteId)) {
                $criteria['preferSites'] = [$query->siteId];
            }
        }

        $query->andWith([$this->handle, $criteria]);
    }

    /**
     * @inheritdoc
     */
    public function getIsTranslatable(?ElementInterface $element): bool
    {
        return $this->localizeRelations;
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        if ($element !== null && $element->hasEagerLoadedElements($this->handle)) {
            $value = $element->getEagerLoadedElements($this->handle)->all();
        } else {
            /** @var ElementQueryInterface $value */
            $value = $this->_all($value, $element);
        }

        /** @var ElementQuery|array $value */
        $variables = $this->inputTemplateVariables($value, $element);
        $variables['inline'] = $inline || $variables['viewMode'] === 'large';

        if ($inline) {
            $variables['viewMode'] = 'list';
        }

        return Craft::$app->getView()->renderTemplate($this->inputTemplate, $variables);
    }

    /**
     * @inheritdoc
     */
    protected function searchKeywords(mixed $value, ElementInterface $element): string
    {
        /** @var ElementQuery|ElementCollection $value */
        $titles = [];

        if ($value instanceof ElementCollection) {
            $value = $value->all();
        } else {
            $value = $this->_all($value, $element)->all();
        }

        foreach ($value as $relatedElement) {
            $titles[] = (string)$relatedElement;
        }

        return parent::searchKeywords($titles, $element);
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        /** @var ElementQueryInterface|ElementCollection $value */
        if ($value instanceof ElementCollection) {
            $value = $value->all();
        } else {
            $value = $this->_all($value, $element)->all();
        }

        if (empty($value)) {
            return '<p class="light">' . Craft::t('app', 'Nothing selected.') . '</p>';
        }

        $size = Cp::CHIP_SIZE_SMALL;
        $viewMode = $this->viewMode();
        if ($viewMode == 'large') {
            $size = Cp::CHIP_SIZE_LARGE;
        }

        $id = $this->getInputId();
        $html = "<div id='$id' class='elementselect noteditable'>" .
            "<div class='elements chips" . ($size === Cp::CHIP_SIZE_LARGE ? ' inline-chips' : '') . "'>";

        foreach ($value as $relatedElement) {
            $html .= Cp::elementChipHtml($relatedElement, [
                'size' => $size,
            ]);
        }

        $html .= '</div></div>';
        return $html;
    }

    /**
     * @inheritdoc
     */
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        /** @var ElementQueryInterface|ElementCollection $value */
        if ($value instanceof ElementQueryInterface) {
            $value = $this->_all($value, $element)->collect();
        }

        return $this->previewHtml($value);
    }

    /**
     * Returns the HTML that should be shown for this field in table and card views.
     *
     * @param ElementCollection $elements
     * @return string
     * @since 5.0.0
     */
    protected function previewHtml(ElementCollection $elements): string
    {
        return Cp::elementPreviewHtml($elements->all());
    }

    /**
     * @inheritdoc
     */
    public function getEagerLoadingMap(array $sourceElements): array|null|false
    {
        $sourceSiteId = $sourceElements[0]->siteId;

        // Get the source element IDs
        $sourceElementIds = array_map(fn(ElementInterface $element) => $element->id, $sourceElements);

        // Return any relation data on these elements, defined with this field
        $map = (new Query())
            ->select(['sourceId as source', 'targetId as target'])
            ->from([DbTable::RELATIONS])
            ->where([
                'and',
                [
                    'fieldId' => $this->id,
                    'sourceId' => $sourceElementIds,
                ],
                [
                    'or',
                    ['sourceSiteId' => $sourceSiteId],
                    ['sourceSiteId' => null],
                ],
            ])
            ->orderBy(['sortOrder' => SORT_ASC])
            ->all();

        $criteria = [];

        // Is a single target site selected?
        if ($this->targetSiteId && Craft::$app->getIsMultiSite()) {
            try {
                $criteria['siteId'] = Craft::$app->getSites()->getSiteByUid($this->targetSiteId)->id;
            } catch (SiteNotFoundException $exception) {
                Craft::warning($exception->getMessage(), __METHOD__);
            }
        }

        if ($this->maintainHierarchy) {
            $criteria['orderBy'] = ['structureelements.lft' => SORT_ASC];
        }

        return [
            'elementType' => static::elementType(),
            'map' => $map,
            'criteria' => $criteria,
        ];
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function getContentGqlMutationArgumentType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => Type::listOf(Type::int()),
            'description' => $this->instructions,
        ];
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew): void
    {
        // If the propagation method just changed, resave all the Matrix blocks
        if (isset($this->oldSettings)) {
            $oldLocalizeRelations = (bool)($this->oldSettings['localizeRelations'] ?? false);
            if ($this->localizeRelations !== $oldLocalizeRelations) {
                Queue::push(new LocalizeRelations([
                    'fieldId' => $this->id,
                ]));
            }
        }

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function afterElementSave(ElementInterface $element, bool $isNew): void
    {
        // Skip if nothing changed, or the element is just propagating and we're not localizing relations
        if (
            ($element->duplicateOf || $element->isFieldDirty($this->handle) || $this->maintainHierarchy) &&
            (!$element->propagating || $this->localizeRelations)
        ) {
            /** @var ElementQueryInterface|ElementCollection $value */
            $value = $element->getFieldValue($this->handle);

            // $value will be an element query and its $id will be set if we're saving new relations
            if ($value instanceof ElementCollection) {
                $targetIds = $value->map(fn(ElementInterface $element) => $element->id)->all();
            } elseif (
                is_array($value->id) &&
                ArrayHelper::isNumeric($value->id)
            ) {
                $targetIds = $value->id ?: [];
            } else {
                // just running $this->_all()->ids() will cause the query to get adjusted
                // see https://github.com/craftcms/cms/issues/14674 for details
                $targetIds = $this->_all($value, $element)
                    ->collect()
                    ->map(fn(ElementInterface $element) => $element->id)
                    ->all();
            }

            if ($this->maintainHierarchy) {
                $structuresService = Craft::$app->getStructures();

                /** @var ElementInterface $class */
                $class = static::elementType();

                /** @var ElementInterface[] $structureElements */
                $structureElements = $class::find()
                    ->id($targetIds)
                    ->drafts(null)
                    ->revisions(null)
                    ->provisionalDrafts(null)
                    ->status(null)
                    ->site('*')
                    ->unique()
                    ->all();

                // Fill in any gaps
                $structuresService->fillGapsInElements($structureElements);

                // Enforce the branch limit
                if ($this->branchLimit) {
                    $structuresService->applyBranchLimitToElements($structureElements, $this->branchLimit);
                }

                $targetIds = array_map(fn(ElementInterface $element) => $element->id, $structureElements);
            }

            /** @var int|int[]|false|null $targetIds */
            Craft::$app->getRelations()->saveRelations($this, $element, $targetIds);

            // Reset the field value?
            if ($element->duplicateOf !== null || $element->mergingCanonicalChanges || $isNew) {
                $element->setFieldValue($this->handle, null);
            }

            if (!$this->localizeRelations && ElementHelper::shouldTrackChanges($element)) {
                // Mark the field as dirty across all of the element’s sites
                // (this is a little hacky but there’s not really a non-hacky alternative unfortunately.)
                $siteIds = array_map(
                    fn(array $siteInfo) => $siteInfo['siteId'],
                    ElementHelper::supportedSitesForElement($element),
                );
                $siteIds = ArrayHelper::withoutValue($siteIds, $element->siteId);
                if (!empty($siteIds)) {
                    $userId = Craft::$app->getUser()->getId();
                    $timestamp = Db::prepareDateForDb(new DateTime());

                    foreach ($siteIds as $siteId) {
                        Db::upsert(DbTable::CHANGEDFIELDS, [
                            'elementId' => $element->id,
                            'siteId' => $siteId,
                            'fieldId' => $this->id,
                            'layoutElementUid' => $this->layoutElement->uid,
                            'dateUpdated' => $timestamp,
                            'propagated' => $element->propagating,
                            'userId' => $userId,
                        ]);
                    }
                }
            }
        }

        parent::afterElementSave($element, $isNew);
    }

    /**
     * @inheritdoc
     */
    public function afterElementDeleteForSite(ElementInterface $element): void
    {
        if ($this->localizeRelations) {
            Db::delete(DbTable::RELATIONS, [
                'fieldId' => $this->id,
                'sourceSiteId' => $element->siteId,
                'sourceId' => $element->id,
            ]);
        }

        parent::afterElementDeleteForSite($element);
    }

    /**
     * Normalizes the available sources into select input options.
     *
     * @return array
     */
    public function getSourceOptions(): array
    {
        $options = array_map(fn($s) => [
            'label' => $s['label'],
            'value' => $s['key'],
            'data' => [
                'structure-id' => $s['structureId'] ?? null,
            ],
        ], $this->availableSources());
        ArrayHelper::multisort($options, 'label', SORT_ASC, SORT_NATURAL | SORT_FLAG_CASE);
        return $options;
    }

    /**
     * Returns the HTML for the Target Site setting.
     *
     * @return string|null
     */
    public function getTargetSiteFieldHtml(): ?string
    {
        /** @var ElementInterface|string $class */
        $class = static::elementType();

        if (!Craft::$app->getIsMultiSite() || !$class::isLocalized()) {
            return null;
        }

        $type = $class::lowerDisplayName();
        $pluralType = $class::pluralLowerDisplayName();
        $showTargetSite = !empty($this->targetSiteId);
        $siteOptions = [];

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $siteOptions[] = [
                'label' => Craft::t('site', $site->getName()),
                'value' => $site->uid,
            ];
        }

        return
            Cp::checkboxFieldHtml([
                'checkboxLabel' => Craft::t('app', 'Relate {type} from a specific site?', ['type' => $pluralType]),
                'name' => 'useTargetSite',
                'checked' => $showTargetSite,
                'toggle' => 'target-site-field',
                'reverseToggle' => 'show-site-menu-field',
            ]) .
            Cp::selectFieldHtml([
                'fieldClass' => !$showTargetSite ? ['hidden'] : null,
                'label' => Craft::t('app', 'Which site should {type} be related from?', ['type' => $pluralType]),
                'id' => 'target-site',
                'name' => 'targetSiteId',
                'options' => $siteOptions,
                'value' => $this->targetSiteId,
            ]) .
            Cp::checkboxFieldHtml([
                'fieldset' => true,
                'fieldClass' => $showTargetSite ? ['hidden'] : null,
                'checkboxLabel' => Craft::t('app', 'Show the site menu'),
                'instructions' => Craft::t('app', 'Whether the site menu should be shown for {type} selection modals.', [
                    'type' => $type,
                ]),
                'warning' => Craft::t('app', 'Relations don’t store the selected site, so this should only be enabled if some {type} aren’t propagated to all sites.', [
                    'type' => $pluralType,
                ]),
                'id' => 'show-site-menu',
                'name' => 'showSiteMenu',
                'checked' => $this->showSiteMenu,
            ]);
    }

    /**
     * Returns the HTML for the View Mode setting.
     *
     * @return string|null
     */
    public function getViewModeFieldHtml(): ?string
    {
        $supportedViewModes = $this->supportedViewModes();

        if (count($supportedViewModes) === 1) {
            return null;
        }

        $viewModeOptions = [];

        foreach ($supportedViewModes as $key => $label) {
            $viewModeOptions[] = ['label' => $label, 'value' => $key];
        }

        return Cp::selectFieldHtml([
            'label' => Craft::t('app', 'View Mode'),
            'instructions' => Craft::t('app', 'Choose how the field should look for authors.'),
            'id' => 'viewMode',
            'name' => 'viewMode',
            'options' => $viewModeOptions,
            'value' => $this->viewMode,
            'toggle' => true,
            'targetPrefix' => 'view-mode--',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function useFieldset(): bool
    {
        return true;
    }

    /**
     * Returns an array of variables that should be passed to the settings template.
     *
     * @return array
     * @since 3.2.10
     */
    protected function settingsTemplateVariables(): array
    {
        /** @var ElementInterface|string $elementType */
        $elementType = $this->elementType();

        $selectionCondition = $this->getSelectionCondition() ?? $this->createSelectionCondition();
        if ($selectionCondition) {
            $selectionCondition->mainTag = 'div';
            $selectionCondition->id = 'selection-condition';
            $selectionCondition->name = 'selectionCondition';
            $selectionCondition->forProjectConfig = true;
            $selectionCondition->queryParams[] = 'site';
            $selectionCondition->queryParams[] = 'status';

            $selectionConditionHtml = Cp::fieldHtml($selectionCondition->getBuilderHtml(), [
                'label' => Craft::t('app', 'Selectable {type} Condition', [
                    'type' => $elementType::pluralDisplayName(),
                ]),
                'instructions' => Craft::t('app', 'Only allow {type} to be selected if they match the following rules:', [
                    'type' => $elementType::pluralLowerDisplayName(),
                ]),
            ]);
        }

        return [
            'field' => $this,
            'elementType' => $elementType::lowerDisplayName(),
            'pluralElementType' => $elementType::pluralLowerDisplayName(),
            'selectionCondition' => $selectionConditionHtml ?? null,
        ];
    }

    /**
     * Returns an array of variables that should be passed to the input template.
     *
     * @param array|ElementQueryInterface|null $value
     * @param ElementInterface|null $element
     * @return array
     */
    protected function inputTemplateVariables(array|ElementQueryInterface $value = null, ?ElementInterface $element = null): array
    {
        if ($value instanceof ElementQueryInterface) {
            $value = $value->all();
        } elseif (!is_array($value)) {
            $value = [];
        }

        if ($this->validateRelatedElements && $element !== null) {
            // Pre-validate related elements
            foreach ($value as $target) {
                self::_validateRelatedElement($element, $target);
            }
        }

        $selectionCriteria = $this->getInputSelectionCriteria();
        $selectionCriteria['siteId'] = $this->targetSiteId($element);

        $disabledElementIds = [];

        if (!$this->allowSelfRelations && $element) {
            if ($element->id) {
                $disabledElementIds[] = $element->getCanonicalId();
            }
            if ($element instanceof NestedElementInterface) {
                $el = $element;
                do {
                    try {
                        $el = $el->getOwner();
                        if ($el) {
                            $disabledElementIds[] = $el->getCanonicalId();
                        }
                    } catch (InvalidConfigException) {
                        break;
                    }
                } while ($el instanceof NestedElementInterface);
            }
        }

        $selectionCondition = $this->getSelectionCondition();
        if ($selectionCondition instanceof ElementCondition) {
            $selectionCondition->referenceElement = $element;
        }

        return [
            'jsClass' => $this->inputJsClass,
            'elementType' => static::elementType(),
            'id' => $this->getInputId(),
            'fieldId' => $this->id,
            'storageKey' => 'field.' . $this->id,
            'describedBy' => $this->describedBy,
            'name' => $this->handle,
            'elements' => $value,
            'sources' => $this->getInputSources($element),
            'condition' => $selectionCondition,
            'referenceElement' => $element,
            'criteria' => $selectionCriteria,
            'showSiteMenu' => ($this->targetSiteId || !$this->showSiteMenu) ? false : 'auto',
            'allowSelfRelations' => (bool)$this->allowSelfRelations,
            'maintainHierarchy' => (bool)$this->maintainHierarchy,
            'branchLimit' => $this->branchLimit,
            'sourceElementId' => !empty($element->id) ? $element->id : null,
            'disabledElementIds' => $disabledElementIds,
            'limit' => $this->allowLimit ? $this->maxRelations : null,
            'viewMode' => $this->viewMode(),
            'showCardsInGrid' => $this->showCardsInGrid,
            'selectionLabel' => $this->selectionLabel ? Craft::t('site', $this->selectionLabel) : static::defaultSelectionLabel(),
            'sortable' => $this->sortable && !$this->maintainHierarchy,
            'prevalidate' => $this->validateRelatedElements,
            'modalSettings' => [
                'defaultSiteId' => $element->siteId ?? null,
            ],
        ];
    }

    /**
     * Returns an array of the source keys the field should be able to select elements from.
     *
     * @param ElementInterface|null $element
     * @return array|string|null
     */
    public function getInputSources(?ElementInterface $element = null): array|string|null
    {
        if ($this->allowMultipleSources) {
            $sources = $this->sources;
        } else {
            $sources = [$this->source];
        }

        return $sources;
    }

    /**
     * Returns any additional criteria parameters limiting which elements the field should be able to select.
     *
     * @return array
     */
    public function getInputSelectionCriteria(): array
    {
        // Fire a 'defineSelectionCriteria event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_SELECTION_CRITERIA)) {
            $event = new ElementCriteriaEvent();
            $this->trigger(self::EVENT_DEFINE_SELECTION_CRITERIA, $event);
            return $event->criteria;
        }

        return [];
    }

    /**
     * Returns the element condition that should be used to determine which elements are selectable by the field.
     *
     * @return ElementConditionInterface|null
     * @since 4.0.0
     */
    public function getSelectionCondition(): ?ElementConditionInterface
    {
        if ($this->_selectionCondition !== null && !$this->_selectionCondition instanceof ConditionInterface) {
            $condition = Craft::$app->getConditions()->createCondition($this->_selectionCondition);
            if (!empty($condition->getConditionRules())) {
                $this->_selectionCondition = $condition;
            } else {
                $this->_selectionCondition = null;
            }
        }

        return $this->_selectionCondition;
    }

    /**
     * Sets the element condition that should be used to determine which elements are selectable by the field.
     *
     * @param ElementConditionInterface|string|array|null $condition
     * @phpstan-param ElementConditionInterface|string|array{class:string}|null $condition
     * @since 4.0.0
     */
    public function setSelectionCondition(mixed $condition): void
    {
        if ($condition instanceof ConditionInterface && !$condition->getConditionRules()) {
            $condition = null;
        }

        // Don't instantiate it unless we actually end up needing it.
        // Avoids an infinite recursion bug (ElementCondition::selectableConditionRules() => getAllFields() => setSelectionCondition() => ...)
        $this->_selectionCondition = $condition;
    }

    /**
     * Creates an element condition that should be used to determine which elements are selectable by the field.
     *
     * The condition’s `queryParams` property should be set to any element query params that are already covered by other field settings.
     *
     * @return ElementConditionInterface|null
     * @since 4.0.0
     */
    protected function createSelectionCondition(): ?ElementConditionInterface
    {
        return null;
    }

    /**
     * Returns the site ID that target elements should have.
     *
     * @param ElementInterface|null $element
     * @return int
     */
    protected function targetSiteId(?ElementInterface $element = null): int
    {
        $targetSiteId = $this->_targetSiteId();
        if ($targetSiteId) {
            return $targetSiteId;
        }

        if ($element !== null && $element::isLocalized()) {
            return $element->siteId;
        }

        return Craft::$app->getSites()->getCurrentSite()->id;
    }

    private function _targetSiteId(): ?int
    {
        if ($this->targetSiteId && Craft::$app->getIsMultiSite()) {
            try {
                return Craft::$app->getSites()->getSiteByUid($this->targetSiteId)->id;
            } catch (SiteNotFoundException $exception) {
                Craft::warning($exception->getMessage(), __METHOD__);
            }
        }

        return null;
    }

    /**
     * Returns the field’s supported view modes.
     *
     * @return array
     */
    protected function supportedViewModes(): array
    {
        $viewModes = [
            'list' => Craft::t('app', 'List'),
        ];

        if ($this->allowLargeThumbsView) {
            $viewModes['large'] = Craft::t('app', 'Large Thumbnails');
        }

        $viewModes['cards'] = Craft::t('app', 'Cards');

        return $viewModes;
    }

    /**
     * Returns the field’s current view mode.
     *
     * @return string
     */
    protected function viewMode(): string
    {
        $supportedViewModes = $this->supportedViewModes();
        $viewMode = $this->viewMode;

        if ($viewMode && isset($supportedViewModes[$viewMode])) {
            return $viewMode;
        }

        return 'list';
    }

    /**
     * Returns the sources that should be available to choose from within the field's settings
     *
     * @return array
     */
    protected function availableSources(): array
    {
        return ArrayHelper::where(
            Craft::$app->getElementSources()->getSources(static::elementType(), 'modal'),
            fn($s) => $s['type'] !== ElementSources::TYPE_HEADING
        );
    }

    /**
     * Returns a clone of the element query value, prepped to include disabled and cross-site elements.
     *
     * @param ElementQueryInterface $query
     * @param ElementInterface|null $element
     * @return ElementQueryInterface
     */
    private function _all(ElementQueryInterface $query, ?ElementInterface $element = null): ElementQueryInterface
    {
        $clone = (clone $query)
            ->drafts(null)
            ->status(null)
            ->site('*')
            ->limit(null)
            ->unique();
        if ($element !== null) {
            $clone->preferSites([$this->targetSiteId($element)]);
        }
        return $clone;
    }
}
