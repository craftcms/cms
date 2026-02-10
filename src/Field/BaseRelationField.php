<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Closure;
use Craft;
use craft\base\conditions\ConditionInterface;
use craft\base\ElementInterface;
use craft\base\NestedElementInterface;
use craft\behaviors\CustomFieldBehavior;
use craft\elements\conditions\ElementCondition;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementRelationParamParser;
use craft\fieldlayoutelements\BaseField;
use craft\fieldlayoutelements\CustomField;
use craft\fields\conditions\RelationalFieldConditionRule;
use craft\helpers\Cp;
use craft\helpers\ElementHelper;
use craft\web\assets\cp\CpAsset;
use CraftCms\Cms\Database\Expressions\FixedOrderExpression;
use CraftCms\Cms\Database\Expressions\OrderByPlaceholderExpression;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\Events\DefineElementCriteria;
use CraftCms\Cms\Element\Jobs\LocalizeRelations;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\EagerLoadingFieldInterface;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Contracts\RelationalFieldInterface;
use CraftCms\Cms\Field\Contracts\ThumbableFieldInterface;
use CraftCms\Cms\Site\Exceptions\SiteNotFoundException;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Structures;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;
use Override;
use Tpetry\QueryExpressions\Language\Alias;
use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\db\Schema;

use function CraftCms\Cms\t;

/**
 * BaseRelationField is the base class for classes representing a relational field.
 */
abstract class BaseRelationField extends Field implements CrossSiteCopyableFieldInterface, EagerLoadingFieldInterface, InlineEditableFieldInterface, MergeableFieldInterface, RelationalFieldInterface, ThumbableFieldInterface
{
    /**
     * @event ElementCriteriaEvent The event that is triggered when defining the selection criteria for this field.
     */
    public const EVENT_DEFINE_SELECTION_CRITERIA = 'defineSelectionCriteria';

    public const VIEW_MODE_LIST = 'list';

    public const VIEW_MODE_LIST_INLINE = 'list-inline';

    public const VIEW_MODE_THUMBS = 'thumbs';

    public const VIEW_MODE_CARDS = 'cards';

    public const VIEW_MODE_CARDS_GRID = 'cards-grid';

    public const DEFAULT_PLACEMENT_BEGINNING = 'beginning';

    public const DEFAULT_PLACEMENT_END = 'end';

    private static bool $validatingRelatedElements = false;

    /**
     * Returns the element class associated with this field type.
     *
     * @return class-string<ElementInterface> The Element class name
     */
    abstract public static function elementType(): string;

    /**
     * Returns whether the “Show the site menu” setting should be shown for the field.
     */
    protected static function canShowSiteMenu(): bool
    {
        return static::elementType()::isLocalized();
    }

    /**
     * Returns the default [[selectionLabel]] value.
     *
     * @return string The default selection label
     */
    public static function defaultSelectionLabel(): string
    {
        return t('Choose');
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function phpType(): string
    {
        return sprintf('\\%s|\\%s<\\%s>', ElementQueryInterface::class, ElementCollection::class,
            ElementInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function dbType(): array|string|null
    {
        return Schema::TYPE_JSON;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function modifyQuery(\Illuminate\Contracts\Database\Query\Builder $query, array $instances, mixed $value): \Illuminate\Contracts\Database\Query\Builder
    {
        /** @var self $field */
        $field = reset($instances);

        if (! is_array($value)) {
            $value = [$value];
        }

        if (isset($value[0]) && in_array($value[0], [':notempty:', ':empty:', 'not :empty:'])) {
            $emptyParam = array_shift($value);

            if (self::isQueryConditionFieldMultiInstance($instances)) {
                // look at the JSON values rather than the `relations` table data
                // (see https://github.com/craftcms/cms/issues/17290 + https://github.com/craftcms/cms/pull/18092)
                if (in_array($emptyParam, [':notempty:', 'not :empty:'])) {
                    $query->orWhere(function (Builder $query) use ($instances) {
                        foreach ($instances as $instance) {
                            $valueSql = $instance->getValueSql();
                            $query->orWhere(function (Builder $query) use ($valueSql) {
                                $query->whereNotNull($valueSql)
                                    ->whereNot($valueSql, '[]');
                            });
                        }
                    });
                } else {
                    $query->orWhere(function (Builder $query) use ($instances) {
                        foreach ($instances as $instance) {
                            $valueSql = $instance->getValueSql();
                            $query->where(function (Builder $query) use ($valueSql) {
                                $query->whereNotNull($valueSql)
                                    ->whereNot($valueSql, '[]');
                            });
                        }
                    });
                }
            } elseif (in_array($emptyParam, [':notempty:', 'not :empty:'])) {
                $query->orWhereExists(static::existsQuery($field));
            } else {
                $query->orWhereNotExists(static::existsQuery($field));
            }
        }

        if (! empty($value)) {
            /** @TODO Port to Laravel */
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
                $params = [];
                $sql = Craft::$app->getDb()->getQueryBuilder()->buildCondition($condition, $params);

                // Yii uses named parameters, Laravel uses positional
                $sql = preg_replace('/:qp\d+/', '?', (string) $sql);

                $query->whereRaw($sql, $params);
            }
        }

        return $query;
    }

    /**
     * @param  self[]  $instances
     */
    private static function isQueryConditionFieldMultiInstance(array $instances): bool
    {
        foreach ($instances as $instance) {
            // See if this instance is used multiple times within its field layout
            $allInstances = $instance->layoutElement?->getLayout()->getFields(fn (BaseField $field) => (
                $field instanceof CustomField &&
                $field->getFieldUid() === $instance->uid
            ));

            if ($allInstances && count($allInstances) > 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns a query builder-compatible condition for an element query,
     * limiting the results to only elements where the given relation field has a value.
     *
     * @param  self  $field  The relation field
     * @param  bool  $enabledOnly  Whether to only
     */
    public static function existsQuery(
        self $field,
        bool $enabledOnly = true,
        bool $inTargetSiteOnly = true,
    ): Builder {
        $ns = sprintf('%s_%s', $field->handle, Str::random(5));

        $query = DB::table(\CraftCms\Cms\Database\Table::RELATIONS, "relations_$ns")
            ->join(new Alias(\CraftCms\Cms\Database\Table::ELEMENTS, "elements_$ns"), "elements_$ns.id", '=', "relations_$ns.targetId")
            ->leftJoin(new Alias(\CraftCms\Cms\Database\Table::ELEMENTS_SITES, "elements_sites_$ns"), "elements_sites_$ns.elementId", '=', "elements_$ns.id")
            ->whereColumn("relations_$ns.sourceId", 'elements.id')
            ->where("relations_$ns.fieldId", $field->id)
            ->whereNull("relations_$ns.dateDeleted")
            ->where(function (Builder $query) use ($ns) {
                $query->whereNull("relations_$ns.sourceSiteId")
                    ->orWhereColumn("relations_$ns.sourceSiteId", 'elements_sites.siteId');
            });

        if ($enabledOnly) {
            $query->where("elements_$ns.enabled", true);
            $query->where("elements_sites_$ns.enabled", true);
        }

        if ($inTargetSiteOnly) {
            $query->where("elements_sites_$ns.siteId", $field->_targetSiteId() ?? DB::raw('elements_sites.siteId'));
        }

        return $query;
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
     */
    public bool $showSiteMenu = false;

    /**
     * @var bool Whether to automatically relate structural ancestors.
     */
    public bool $maintainHierarchy = false;

    /**
     * @var int|null Branch limit
     */
    public ?int $branchLimit = null;

    /**
     * @var string Default placement
     *
     * @phpstan-var self::DEFAULT_PLACEMENT_*
     */
    public string $defaultPlacement = self::DEFAULT_PLACEMENT_END;

    /**
     * @var string|null The view mode
     */
    public ?string $viewMode = null;

    /**
     * @var bool Whether cards should be shown in a multi-column grid
     *
     * @deprecated in 5.9.0.
     */
    public bool $showCardsInGrid = false;

    /**
     * @var int|null The maximum number of relations this field can have (used if [[allowLimit]] is set to true).
     */
    public ?int $minRelations = null;

    /**
     * @var int|null The maximum number of relations this field can have (used if [[allowLimit]] is set to true).
     */
    public ?int $maxRelations = null;

    /**
     * @var bool Whether to show a search input.
     */
    public bool $showSearchInput = true;

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
     *
     * @deprecated in 5.3.0
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
     * @phpstan-var ElementConditionInterface|array{class:class-string<ElementConditionInterface>}|null
     *
     * @see getSelectionCondition()
     * @see setSelectionCondition()
     */
    private array|null|ElementConditionInterface $_selectionCondition = null;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $config = [])
    {
        // limit => maxRelations
        if (array_key_exists('limit', $config)) {
            $config['maxRelations'] = Arr::pull($config, 'limit');
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
        if (isset($config['id']) && ! isset($config['showSiteMenu'])) {
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

        if (isset($config['localizeRelations'])) {
            $config['translationMethod'] = $config['localizeRelations'] ? self::TRANSLATION_METHOD_SITE : self::TRANSLATION_METHOD_NONE;
        } else {
            $config['localizeRelations'] = ($config['translationMethod'] ?? self::TRANSLATION_METHOD_NONE) !== self::TRANSLATION_METHOD_NONE;
        }

        $config['viewMode'] ??= self::VIEW_MODE_LIST;

        if (! empty($config['showCardsInGrid']) && $config['viewMode'] === self::VIEW_MODE_CARDS) {
            $config['viewMode'] = self::VIEW_MODE_CARDS_GRID;
        }
        $config['showCardsInGrid'] = $config['viewMode'] === self::VIEW_MODE_CARDS_GRID;

        if ($config['viewMode'] === 'large') {
            $config['viewMode'] = self::VIEW_MODE_THUMBS;
        }

        parent::__construct($config);
    }

    #[Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'minRelations' => ['nullable', 'integer'],
            'maxRelations' => ['nullable', 'integer'],
            'branchLimit' => ['nullable', 'integer'],
        ]);
    }

    /**
     * Ensure only one structured source is selected when maintainHierarchy is true.
     *
     * @todo This needs to be called from somewhere
     */
    public function validateSources(string $attribute): void
    {
        if (! $this->maintainHierarchy) {
            return;
        }

        $inputSources = $this->getInputSources();

        if ($inputSources === null) {
            $this->maintainHierarchy = false;

            return;
        }

        if (is_string($inputSources)) {
            $inputSources = [$inputSources];
        }

        $elementSources = resolve(ElementSources::class)
            ->getSources(static::elementType())
            ->whereIn('key', $inputSources);

        if (count($elementSources) > 1) {
            $this->maintainHierarchy = false;

            return;
        }

        foreach ($elementSources as $elementSource) {
            if (! isset($elementSource['structureId'])) {
                $this->maintainHierarchy = false;

                return;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'allowSelfRelations';
        $attributes[] = 'branchLimit';
        $attributes[] = 'defaultPlacement';
        $attributes[] = 'maintainHierarchy';
        $attributes[] = 'maxRelations';
        $attributes[] = 'minRelations';
        $attributes[] = 'selectionLabel';
        $attributes[] = 'showSearchInput';
        $attributes[] = 'showSiteMenu';
        $attributes[] = 'source';
        $attributes[] = 'sources';
        $attributes[] = 'targetSiteId';
        $attributes[] = 'validateRelatedElements';
        $attributes[] = 'viewMode';

        return $attributes;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getSettingsHtml(): string
    {
        $variables = $this->settingsTemplateVariables();
        $view = Craft::$app->getView();

        $view->registerJsWithVars(fn ($args) => <<<JS
new Craft.ElementFieldSettings(...$args)
JS, [
            [
                $this->allowMultipleSources,
                $view->namespaceInputId('maintain-hierarchy-field'),
                $view->namespaceInputId($this->allowMultipleSources ? 'sources-field' : 'source-field'),
                $view->namespaceInputId('branch-limit-field'),
                $view->namespaceInputId('min-relations-field'),
                $view->namespaceInputId('max-relations-field'),
                $view->namespaceInputId('default-placement-field'),
                $view->namespaceInputId('viewMode-field'),
            ],
        ]);

        return $view->renderTemplate($this->settingsTemplate, $variables);
    }

    #[\Override]
    public function getElementRules(ElementInterface $element): array
    {
        if (! $element->inScenarios(Element::SCENARIO_LIVE)) {
            return [];
        }

        $rules = [
            fn (
                string $attribute,
                ElementQueryInterface|ElementCollection $value,
                Closure $fail,
                Validator $validator,
            ) => $this->validateRelationCount($element, $attribute, $value, $validator),
        ];

        if ($this->validateRelatedElements) {
            $rules[] = fn (
                string $attribute,
                ElementQueryInterface|ElementCollection $value,
                Closure $fail,
                Validator $validator,
            ) => $this->validateRelatedElements($element, $value, $fail);
        }

        return $rules;
    }

    /**
     * Validates that the number of related elements are within the min/max relation bounds.
     */
    public function validateRelationCount(ElementInterface $element, string $attribute, ElementQueryInterface|ElementCollection $value, Validator $validator): void
    {
        if (! $this->allowLimit) {
            return;
        }

        if (! $this->minRelations && ! $this->maxRelations) {
            return;
        }

        if ($value instanceof ElementQueryInterface) {
            $value = $this->_all($value, $element)->eagerly();
        }

        $rules = [
            $attribute => array_filter([
                'integer',
                $this->minRelations ? 'min:'.$this->minRelations : null,
                $this->maxRelations ? 'max:'.$this->maxRelations : null,
            ]),
        ];

        $messages = array_filter([
            $attribute.'.min' => $this->minRelations
                ? t('{attribute} should contain at least {min, number} {min, plural, one{selection} other{selections}}.', [
                    'attribute' => t($this->name, category: 'site'),
                    'min' => $this->minRelations, // Need to pass this in now
                ])
                : null,
            $attribute.'.max' => $this->maxRelations
                ? t('{attribute} should contain at most {max, number} {max, plural, one{selection} other{selections}}.', [
                    'attribute' => t($this->name, category: 'site'),
                    'max' => $this->maxRelations, // Need to pass this in now
                ])
                : null,
        ]);

        $v = ValidatorFacade::make([$attribute => $value->count()], $rules, $messages);

        if ($v->fails()) {
            $validator->errors()->merge($v->errors());
        }
    }

    /**
     * Validates the related elements.
     */
    public function validateRelatedElements(ElementInterface $element, ElementQueryInterface|ElementCollection $value, Closure $fail): void
    {
        // No recursive related element validation
        if (self::$validatingRelatedElements) {
            return;
        }

        if ($value instanceof ElementQueryInterface) {
            $value
                ->site('*')
                ->unique()
                ->preferSites([$this->targetSiteId($element)])
                ->eagerly();
        }

        $errorCount = 0;

        foreach ($value->all() as $i => $target) {
            if (! self::_validateRelatedElement($element, $target)) {
                /** @var Element $target */
                $element->addModelErrors($target, "$this->handle[$i]");
                $errorCount++;
            }
        }

        if ($errorCount) {
            $selectedCount = $value->count();
            $fail(t('The selected {relatedType} {count, plural, =1{contains} other{contain}} validation errors, preventing this {type} from being saved. Edit the {relatedType} to fix them.', [
                'relatedType' => $selectedCount === 1
                    ? static::elementType()::lowerDisplayName()
                    : static::elementType()::pluralLowerDisplayName(),
                'count' => $selectedCount,
                'type' => $element::lowerDisplayName(),
            ]));
        }
    }

    /**
     * Returns whether a related element validates.
     */
    private static function _validateRelatedElement(ElementInterface $source, ElementInterface $target): bool
    {
        if (
            self::$validatingRelatedElements ||
            ! $target->enabled ||
            ! $target->getEnabledForSite() ||
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
     * {@inheritdoc}
     */
    #[Override]
    public function isValueEmpty(mixed $value, ElementInterface $element): bool
    {
        /** @var \CraftCms\Cms\Element\Queries\ElementQuery|ElementCollection $value */
        if ($value instanceof ElementQueryInterface) {
            return ! $this->_all($value, $element)->exists();
        }

        return $value->isEmpty();
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        // If we're propagating a value, and we don't show the site menu,
        // only save relations to elements in the current site.
        // (see https://github.com/craftcms/cms/issues/15459)
        if (
            $value instanceof ElementQueryInterface &&
            $element?->propagating &&
            $element->isNewForSite &&
            ! $element->resaving &&
            ! $element->isNewSite &&
            ! $this->targetSiteId &&
            ! $this->showSiteMenu
        ) {
            $value = $this->_all($value, $element)
                ->siteId($this->targetSiteId($element))
                ->ids();
        }

        if ($value instanceof ElementQueryInterface || $value instanceof ElementCollection) {
            return $value;
        }

        $class = static::elementType();
        // TODO: $class::find()
        $query = new EntryQuery()
            ->siteId($this->targetSiteId($element));

        if (is_array($value) || is_int($value)) {
            $value = collect($value)->filter()->values()->all();
            $query->whereIn('elements.id', $value);
            if (! empty($value)) {
                $query->orderBy(new FixedOrderExpression('elements.id', $value));
            }
        } elseif ($value === null && $element?->id && $this->fetchRelationsFromDbTable($element)) {
            // If $value is null, the element + field haven’t been saved since updating to Craft 5.3+,
            // or since the field was added to the field layout,
            // or the value was added to not first instance of the field.
            // So only actually look at the `relations` table
            // if this is the first instance of the field that was ever added to the field layout
            // and none of the other instances (which would have been added later on) have a value.
            if (! $this->allowMultipleSources && $this->source) {
                $source = ElementHelper::findSource($class, $this->source, ElementSources::CONTEXT_FIELD);

                // Does the source specify any criteria attributes?
                if (isset($source['criteria'])) {
                    Craft::configure($query, $source['criteria']);
                }
            }

            $relationsAlias = sprintf('relations_%s', Str::random(10));

            $query->beforeQuery(function (\CraftCms\Cms\Element\Queries\ElementQuery $elementQuery) use (
                $element,
                $relationsAlias) {
                if ($elementQuery->id !== null) {
                    return;
                }

                // Make these changes directly on the prepared queries, so `sortOrder` doesn't ever make it into
                // the criteria. Otherwise, if the query ends up A) getting executed normally, then B) getting
                // eager-loaded with eagerly(), the `orderBy` value referencing the join table will get applied
                // to the eager-loading query and cause a SQL error.
                /** @var \Illuminate\Database\Query\Builder $q */
                foreach ([$elementQuery->getQuery(), $elementQuery->getSubQuery()] as $q) {
                    $q->join(
                        new Alias(\CraftCms\Cms\Database\Table::RELATIONS, $relationsAlias),
                        function (JoinClause $join) use ($element, $relationsAlias) {
                            $join->whereColumn("$relationsAlias.targetId", 'elements.id')
                                ->where("$relationsAlias.sourceId", $element->id)
                                ->where("$relationsAlias.fieldId", $this->id)
                                ->where(function (JoinClause $join) use ($element, $relationsAlias) {
                                    $join->whereNull("$relationsAlias.sourceSiteId")
                                        ->orWhere("$relationsAlias.sourceSiteId", $element->siteId);
                                });
                        },
                    );

                    if (
                        $this->sortable &&
                        ! $this->maintainHierarchy &&
                        count($q->orderBy ?? []) === 1 &&
                        ($q->orderBy[0]['column'] ?? null) instanceof OrderByPlaceholderExpression
                    ) {
                        $q->orderBy("$relationsAlias.sortOrder");
                    }
                }
            });
        } else {
            $query->id(false);
        }

        // Prepare the query for lazy eager loading, but only when element exists
        if ($element !== null) {
            $query->prepForEagerLoading($this->handle, $element);
        }

        if ($this->allowLimit && $this->maxRelations) {
            $query->limit($this->maxRelations);
        }

        return $query;
    }

    protected function fetchRelationsFromDbTable(?Elementinterface $element): bool
    {
        if ($this->layoutElement?->uid === null) {
            return false;
        }

        // Get all the instances of this field
        /** @var Collection<CustomField> $fieldInstances */
        $fieldInstances = Collection::make($element?->getFieldLayout()?->getCustomFieldElements())
            ->filter(fn (CustomField $layoutElement) => $layoutElement->getField()->id === $this->id)
            ->sortBy(fn (CustomField $layoutElement) => $layoutElement->dateAdded);

        // Only fetch DB relations if this is the first instance
        // (Compare handles here rather than UUIDs, since the UUID will change
        // if we're hot-swapping field layouts, e.g. changing an entry's type)
        /** @var CustomField|null $first */
        $first = $fieldInstances->shift();
        if ($this->handle !== $first?->getField()->handle) {
            return false;
        }

        // Make sure none of the other instances have values
        /** @var CustomFieldBehavior $behavior */
        $behavior = $element->getBehavior('customFields');
        foreach ($fieldInstances as $fieldInstance) {
            /** @var self $field */
            $field = $fieldInstance->getField();
            if (isset($behavior->{$field->handle})) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($this->maintainHierarchy) {
            // Enforce the “Maintain hierarchy” and “Branch Limit” settings
            $value = $this->normalizeValueForInput($value, $element);

            return array_map(fn (ElementInterface $element) => $element->id, $value);
        }

        /** @var ElementQueryInterface|ElementCollection $value */
        if ($value instanceof ElementCollection) {
            return $value->ids()->all();
        }

        return $this->_all($value, $element)->ids();
    }

    /**
     * {@inheritdoc}
     */
    public function getElementConditionRuleType(): array|string
    {
        return RelationalFieldConditionRule::class;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function modifyElementIndexQuery(ElementQueryInterface $query): void
    {
        $criteria = [
            'drafts' => null,
            'status' => null,
        ];

        if (! $this->targetSiteId) {
            $criteria['siteId'] = '*';
            $criteria['unique'] = true;
            // Just to be safe...
            /** @var \CraftCms\Cms\Element\Queries\ElementQuery $query */
            if (is_numeric($query->siteId)) {
                $criteria['preferSites'] = [$query->siteId];
            }
        }

        $query->andWith([$this->handle, $criteria]);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getIsTranslatable(?ElementInterface $element): bool
    {
        return $this->localizeRelations;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return $this->_inputHtml($value, $element, $inline, false);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        return $this->_inputHtml($value, $element, false, true);
    }

    private function _inputHtml(mixed $value, ?ElementInterface $element, bool $inline, bool $static): string
    {
        $value = $this->normalizeValueForInput($value, $element, $initialValue);

        /** @var ElementInterface[] $value */
        $variables = $this->inputTemplateVariables($value, $element);

        if ($inline && ! in_array($variables['viewMode'], [self::VIEW_MODE_LIST_INLINE, self::VIEW_MODE_THUMBS])) {
            $variables['viewMode'] = self::VIEW_MODE_LIST_INLINE;
        }

        if ($static) {
            $variables['disabled'] = true;
            $variables['allowAdd'] = false;
            $template = '_includes/forms/elementSelect.twig';
        } else {
            $template = $this->inputTemplate;

            if ($initialValue !== null) {
                // make sure the field gets updated on save, even if it hasn't changed
                Craft::$app->getView()->setInitialDeltaValue($this->handle, $initialValue);
            }
        }

        return Craft::$app->getView()->renderTemplate($template, $variables);
    }

    /**
     * @param  ElementQueryInterface|ElementCollection  $value
     * @param  array<int|null>|null  $initialValue
     * @return ElementInterface[]
     */
    private function normalizeValueForInput(
        mixed $value,
        ?ElementInterface $element,
        ?array &$initialValue = null,
    ): array {
        if ($element !== null && $element->hasEagerLoadedElements($this->handle)) {
            $value = $element->getEagerLoadedElements($this->handle)->all();
        } else {
            $value = $this->_all($value, $element)->all();
        }

        if ($this->maintainHierarchy) {
            $initialValue = array_map(fn (ElementInterface $element) => $element->id, $value);
            // Fill in any gaps
            Structures::fillGapsInElements($value);
            // Enforce the branch limit
            if ($this->branchLimit) {
                Structures::applyBranchLimitToElements($value, $this->branchLimit);
            }

            if (count($initialValue) === count($value)) {
                $initialValue = null;
            }
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
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
            $titles[] = (string) $relatedElement;
        }

        return parent::searchKeywords($titles, $element);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        /** @var ElementQueryInterface|ElementCollection $value */
        if ($value instanceof ElementQueryInterface) {
            $value = $this->_all($value, $element)->all();
        } else {
            // todo: come up with a way to get the normalized field value ignoring the eager-loaded value
            $rawValue = $element->getBehavior('customFields')->{$this->handle} ?? null;
            if (is_array($rawValue)) {
                $ids = array_flip($rawValue);
                $value = $value->filter(fn (ElementInterface $element) => isset($ids[$element->id]));
            }
        }

        return $this->previewHtml($value);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        $mockup = new (static::elementType());
        $mockup->title = t('Related {type} Title', ['type' => $mockup->displayName()]);

        return Cp::chipHtml($mockup);
    }

    /**
     * Returns the HTML that should be shown for this field in table and card views.
     */
    protected function previewHtml(ElementCollection $elements): string
    {
        return Cp::elementPreviewHtml($elements->all());
    }

    /**
     * {@inheritdoc}
     */
    public function getThumbHtml(mixed $value, ElementInterface $element, int $size): ?string
    {
        /** @var ElementQueryInterface|ElementCollection $value */
        if ($value instanceof ElementQueryInterface) {
            $handle = sprintf('%s-%s-%s', preg_replace('/:+/', '-', __METHOD__), $this->id, $size);
            $value = (clone $value)->eagerly($handle);
        }

        return $value->one()?->getThumbHtml($size);
    }

    /**
     * {@inheritdoc}
     */
    public function getEagerLoadingMap(array $sourceElements): array|null|false
    {
        $sourceSiteId = $sourceElements[0]->siteId;

        $map = [];
        $missingSourceElementIds = [];

        foreach ($sourceElements as $sourceElement) {
            $rawValue = $sourceElement->getBehavior('customFields')->{$this->handle} ?? null;
            if ($rawValue instanceof ElementQuery) {
                $rawValue = $rawValue->where['elements.id'] ?? null;
            }
            if ($rawValue instanceof \CraftCms\Cms\Element\Queries\ElementQuery) {
                $where = Arr::first($rawValue->getSubQuery()->wheres, fn ($where) => ($where['column'] ?? '') === 'elements.id');
                $rawValue = $where['value'] ?? null;
            }
            if (is_array($rawValue)) {
                foreach ($rawValue as $targetElementId) {
                    $map[] = ['source' => $sourceElement->id, 'target' => $targetElementId];
                }
            } elseif ($this->fetchRelationsFromDbTable($sourceElement)) {
                // The relation IDs aren't hardcoded yet and this is the first
                // instance of this field in the field layout, so fetch the relations
                // via the DB table
                $missingSourceElementIds[] = $sourceElement->id;
            }
        }

        // Are there any source elements that don't have hardcoded relation IDs yet?
        if (! empty($missingSourceElementIds)) {
            $missingMappingsQuery = DB::table(\CraftCms\Cms\Database\Table::RELATIONS)
                ->select(['sourceId as source', 'targetId as target'])
                ->where([
                    'fieldId' => $this->id,
                    'sourceId' => $missingSourceElementIds,
                ])
                ->where(fn (Builder $query) => $query
                    ->where('sourceSiteId', $sourceSiteId)
                    ->orWhereNull('sourceSiteId'),
                )
                ->orderBy('sortOrder')
                ->get()
                ->map(fn (object $row) => (array) $row)
                ->all();

            array_push($map, ...$missingMappingsQuery);
        }

        $criteria = [];

        // Is a single target site selected?
        if ($this->targetSiteId && Sites::isMultiSite()) {
            try {
                $criteria['siteId'] = Sites::getSiteByUid($this->targetSiteId)->id;
            } catch (SiteNotFoundException $exception) {
                Log::warning($exception->getMessage(), [__METHOD__]);
            }
        }

        if ($this->maintainHierarchy) {
            $criteria['orderBy'] = ['structureelements.lft' => SORT_ASC];
        }

        /** @phpstan-ignore-next-line */
        return [
            'elementType' => static::elementType(),
            'map' => $map,
            'criteria' => $criteria,
        ];
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getContentGqlMutationArgumentType(): array
    {
        return [
            'name' => $this->handle,
            'type' => Type::listOf(Type::int()),
            'description' => $this->instructions,
        ];
    }

    /**
     * Returns the custom field arguments for the selected source(s).
     */
    protected function gqlFieldArguments(): array
    {
        $elementSourcesService = resolve(ElementSources::class);
        $gqlService = Craft::$app->getGql();
        $fieldLayouts = [];
        $arguments = [];

        foreach ((array) $this->getInputSources() as $source) {
            $sourceFieldLayouts = $elementSourcesService->getFieldLayoutsForSource(static::elementType(), $source);
            foreach ($sourceFieldLayouts as $fieldLayout) {
                $fieldLayouts[$fieldLayout->uid] = $fieldLayout;
            }
        }

        foreach ($fieldLayouts as $fieldLayout) {
            $arguments += $gqlService->getFieldLayoutArguments($fieldLayout);
        }

        return $arguments;
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function afterSave(bool $isNew): void
    {
        // If the propagation method just changed, resave all the elements
        if (isset($this->oldSettings)) {
            $oldLocalizeRelations = (bool) ($this->oldSettings['localizeRelations'] ?? false);
            if ($this->localizeRelations !== $oldLocalizeRelations) {
                dispatch(new LocalizeRelations($this->id));
            }
        }

        parent::afterSave($isNew);
    }

    /**
     * {@inheritdoc}
     */
    public function localizeRelations(): bool
    {
        return $this->localizeRelations;
    }

    /**
     * {@inheritdoc}
     */
    public function forceUpdateRelations(ElementInterface $element): bool
    {
        return $this->maintainHierarchy;
    }

    /**
     * {@inheritdoc}
     */
    public function getRelationTargetIds(ElementInterface $element): array
    {
        /** @var \CraftCms\Cms\Element\Queries\ElementQuery|ElementCollection $value */
        $value = $element->getFieldValue($this->handle);

        // $value will be an element query and its $id will be set if we're saving new relations
        if ($value instanceof ElementCollection) {
            $targetIds = $value->map(fn (ElementInterface $element) => $element->id)->all();
        } elseif (
            is_array($value->id) &&
            Arr::isNumeric($value->id)
        ) {
            $targetIds = $value->id ?: [];
        } elseif (
            $value instanceof \CraftCms\Cms\Element\Queries\ElementQuery &&
            ($where = $value->getWhereForColumn('elements.id')) !== null &&
            Arr::isNumeric($where['values'])
        ) {
            $targetIds = $where['values'] ?? [];
        } elseif (
            $value instanceof ElementQuery &&
            isset($value->where['elements.id']) &&
            Arr::isNumeric($value->where['elements.id'])
        ) {
            $targetIds = $value->where['elements.id'] ?: [];
        } else {
            // just running $this->_all()->ids() will cause the query to get adjusted
            // see https://github.com/craftcms/cms/issues/14674 for details
            $targetIds = $this->_all($value, $element)
                ->get()
                ->pluck('id')
                ->all();
        }

        if ($this->maintainHierarchy) {
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
            Structures::fillGapsInElements($structureElements);

            // Enforce the branch limit
            if ($this->branchLimit) {
                Structures::applyBranchLimitToElements($structureElements, $this->branchLimit);
            }

            $targetIds = array_map(fn (ElementInterface $element) => $element->id, $structureElements);
        }

        return $targetIds;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function afterElementSave(ElementInterface $element, bool $isNew): void
    {
        // Skip if nothing changed, or the element is just propagating and we're not localizing relations
        if (
            ($element->duplicateOf || $element->isFieldDirty($this->handle) || $this->maintainHierarchy) &&
            (! $element->propagating || $this->localizeRelations)
        ) {
            if (! $this->localizeRelations && ElementHelper::shouldTrackChanges($element)) {
                // Mark the field as dirty across all of the element’s sites
                // (this is a little hacky but there’s not really a non-hacky alternative unfortunately.)
                $siteIds = array_map(
                    fn (array $siteInfo) => $siteInfo['siteId'],
                    ElementHelper::supportedSitesForElement($element),
                );
                $siteIds = Arr::where($siteIds, fn ($siteId) => $siteId !== $element->siteId);
                if (! empty($siteIds)) {
                    $userId = Craft::$app->getUser()->getId();
                    $timestamp = now();

                    foreach ($siteIds as $siteId) {
                        DB::table(\CraftCms\Cms\Database\Table::CHANGEDFIELDS)
                            ->upsert([
                                'elementId' => $element->id,
                                'siteId' => $siteId,
                                'fieldId' => $this->id,
                                'layoutElementUid' => $this->layoutElement->uid,
                                'dateUpdated' => $timestamp,
                                'propagated' => $element->propagating,
                                'userId' => $userId,
                            ], ['elementId', 'siteId', 'fieldId', 'layoutElementUid']);
                    }
                }
            }
        }

        parent::afterElementSave($element, $isNew);
    }

    /**
     * Normalizes the available sources into select input options.
     */
    public function getSourceOptions(): array
    {
        return Collection::make($this->availableSources())
            ->map(fn ($s) => [
                'label' => $s['label'],
                'value' => $s['key'],
                'data' => [
                    'structure-id' => $s['structureId'] ?? null,
                ],
            ])
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->all();
    }

    /**
     * Returns the HTML for the Target Site setting.
     */
    public function getTargetSiteFieldHtml(): ?string
    {
        $class = static::elementType();

        if (! Sites::isMultiSite() || ! $class::isLocalized()) {
            return null;
        }

        $type = $class::lowerDisplayName();
        $pluralType = $class::pluralLowerDisplayName();
        $showTargetSite = ! empty($this->targetSiteId);
        $siteOptions = [];

        foreach (Sites::getAllSites() as $site) {
            $siteOptions[] = [
                'label' => t($site->getName(), category: 'site'),
                'value' => $site->uid,
            ];
        }

        $html =
            Cp::checkboxFieldHtml([
                'checkboxLabel' => t('Relate {type} from a specific site?', ['type' => $pluralType]),
                'name' => 'useTargetSite',
                'checked' => $showTargetSite,
                'toggle' => 'target-site-field',
                'reverseToggle' => 'show-site-menu-field',
            ]).
            Cp::selectFieldHtml([
                'fieldClass' => ! $showTargetSite ? ['hidden'] : null,
                'label' => t('Which site should {type} be related from?', ['type' => $pluralType]),
                'id' => 'target-site',
                'name' => 'targetSiteId',
                'options' => $siteOptions,
                'value' => $this->targetSiteId,
            ]);

        if (static::canShowSiteMenu()) {
            $html .= Cp::checkboxFieldHtml([
                'fieldset' => true,
                'fieldClass' => $showTargetSite ? ['hidden'] : null,
                'checkboxLabel' => t('Show the site menu'),
                'instructions' => t('Whether the site menu should be shown for {type} selection modals.',
                    [
                        'type' => $type,
                    ]),
                'warning' => t(
                    'Relations don’t store the selected site, so this should only be enabled if some {type} aren’t propagated to all sites.',
                    [
                        'type' => $pluralType,
                    ]),
                'id' => 'show-site-menu',
                'name' => 'showSiteMenu',
                'checked' => $this->showSiteMenu,
            ]);
        }

        return $html;
    }

    /**
     * Returns the HTML for the View Mode setting.
     */
    public function getViewModeFieldHtml(): ?string
    {
        $supportedViewModes = $this->supportedViewModes();

        if (count($supportedViewModes) === 1) {
            return null;
        }

        if (empty(array_diff(array_keys($supportedViewModes), [
            self::VIEW_MODE_LIST,
            self::VIEW_MODE_LIST_INLINE,
            self::VIEW_MODE_THUMBS,
            self::VIEW_MODE_CARDS,
            self::VIEW_MODE_CARDS_GRID,
        ]))) {
            $html = Html::beginTag('div', ['class' => ['flex', 'items-start', 'gap-l']]);
            $bundle = Craft::$app->getView()->registerAssetBundle(CpAsset::class);
            $baseIconsUrl = "$bundle->baseUrl/images/view-modes";

            foreach ($supportedViewModes as $key => $label) {
                $html .= Html::beginTag('label', ['class' => 'nowrap']).
                    Html::img("$baseIconsUrl/$key.svg", '', [
                        'class' => 'mb-xs',
                        'width' => $key === self::VIEW_MODE_LIST ? 48 : 80,
                        'height' => 60,
                    ]).
                    Html::radio('viewMode', $key === $this->viewMode, [
                        'value' => $key,
                    ]).
                    ' '.$label.
                    Html::endTag('label');
            }

            $html .= Html::endTag('div');
        } else {
            $viewModeOptions = [];

            foreach ($supportedViewModes as $key => $label) {
                $viewModeOptions[] = ['label' => $label, 'value' => $key];
            }

            $html = Cp::selectHtml([
                'id' => 'viewMode',
                'name' => 'viewMode',
                'options' => $viewModeOptions,
                'value' => $this->viewMode,
            ]);
        }

        return Cp::fieldHtml($html, [
            'label' => t('View Mode'),
            'instructions' => t('Choose how the field should look for authors.'),
            'id' => 'viewMode',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function useFieldset(): bool
    {
        return true;
    }

    /**
     * Returns an array of variables that should be passed to the settings template.
     */
    protected function settingsTemplateVariables(): array
    {
        $elementType = static::elementType();

        $selectionCondition = $this->getSelectionCondition() ?? $this->createSelectionCondition();
        if ($selectionCondition) {
            $selectionCondition->mainTag = 'div';
            $selectionCondition->id = 'selection-condition';
            $selectionCondition->name = 'selectionCondition';
            $selectionCondition->forProjectConfig = true;
            $selectionCondition->queryParams[] = 'site';

            $selectionConditionHtml = Cp::fieldHtml($selectionCondition->getBuilderHtml(), [
                'label' => t('Selectable {type} Condition', [
                    'type' => $elementType::pluralDisplayName(),
                ]),
                'instructions' => mb_ucfirst(t('Only allow {type} to be selected if they match the following rules:', [
                    'type' => $elementType::pluralLowerDisplayName(),
                ])),
            ]);
        }

        return [
            'field' => $this,
            'upperElementType' => $elementType::displayName(),
            'elementType' => $elementType::lowerDisplayName(),
            'pluralElementType' => $elementType::pluralLowerDisplayName(),
            'selectionCondition' => $selectionConditionHtml ?? null,
        ];
    }

    /**
     * Returns an array of variables that should be passed to the input template.
     *
     * @param  ElementInterface[]|ElementQueryInterface|null  $value
     */
    protected function inputTemplateVariables(
        array|ElementQueryInterface|null $value = null,
        ?ElementInterface $element = null,
    ): array {
        if ($value instanceof ElementQueryInterface) {
            $value = $value->eagerly()->all();
        } elseif (! is_array($value)) {
            $value = [];
        }

        ElementHelper::loadProvisionalChanges($value);

        if ($this->validateRelatedElements && $element !== null) {
            // Pre-validate related elements
            foreach ($value as $target) {
                self::_validateRelatedElement($element, $target);
            }
        }

        $elementType = static::elementType();
        $selectionCriteria = $this->getInputSelectionCriteria();
        $selectionCriteria['siteId'] = $this->targetSiteId($element);

        $disabledElementIds = [];

        if (! $this->allowSelfRelations && $element) {
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

        $sources = $this->getInputSources($element);
        $searchCriteria = null;

        if ($this->showSearchInput($element)) {
            $source = ElementHelper::findSource($elementType, reset($sources), 'field');
            if (! empty($source['criteria'])) {
                $searchCriteria = $source['criteria'];
            }
        }

        return [
            'jsClass' => $this->inputJsClass,
            'elementType' => $elementType,
            'id' => $this->getInputId(),
            'fieldId' => $this->id,
            'storageKey' => 'field.'.$this->id,
            'describedBy' => $this->describedBy,
            'labelId' => $this->getLabelId(),
            'name' => $this->handle,
            'elements' => $value,
            'sources' => $sources,
            'searchCriteria' => $searchCriteria,
            'condition' => $selectionCondition,
            'referenceElement' => $element,
            'criteria' => $selectionCriteria,
            'showSiteMenu' => ($this->targetSiteId || ! $this->showSiteMenu || ! static::canShowSiteMenu()) ? false : 'auto',
            'allowSelfRelations' => $this->allowSelfRelations,
            'maintainHierarchy' => $this->maintainHierarchy,
            'branchLimit' => $this->branchLimit,
            'sourceElementId' => ! empty($element->id) ? $element->id : null,
            'disabledElementIds' => $disabledElementIds,
            'limit' => $this->allowLimit ? $this->maxRelations : null,
            'defaultPlacement' => $this->defaultPlacement,
            'viewMode' => $this->viewMode(),
            'selectionLabel' => $this->selectionLabel ? t($this->selectionLabel, category: 'site') : static::defaultSelectionLabel(),
            'sortable' => $this->sortable && ! $this->maintainHierarchy,
            'prevalidate' => $this->validateRelatedElements,
            'modalSettings' => [
                'defaultSiteId' => $element->siteId ?? null,
            ],
        ];
    }

    /**
     * Returns whether the search input should be shown.
     */
    protected function showSearchInput(?ElementInterface $element): bool
    {
        if (! $this->showSearchInput) {
            return false;
        }

        if (! $this->allowMultipleSources) {
            return true;
        }

        if ($this->sources === '*') {
            return false;
        }

        $sources = $this->getInputSources($element);

        return is_array($sources) && count($sources) === 1;
    }

    /**
     * Returns an array of the source keys the field should be able to select elements from.
     */
    public function getInputSources(?ElementInterface $element = null): array|string|null
    {
        if ($this->allowMultipleSources) {
            return $this->sources;
        }

        return [$this->source];
    }

    /**
     * Returns any additional criteria parameters limiting which elements the field should be able to select.
     */
    public function getInputSelectionCriteria(): array
    {
        $this->dispatchComponentEvent(
            self::EVENT_DEFINE_SELECTION_CRITERIA,
            $event = new DefineElementCriteria,
        );

        return $event->criteria;
    }

    /**
     * Returns the element condition that should be used to determine which elements are selectable by the field.
     */
    public function getSelectionCondition(): ?ElementConditionInterface
    {
        if ($this->_selectionCondition !== null && ! $this->_selectionCondition instanceof ConditionInterface) {
            $condition = Craft::$app->getConditions()->createCondition($this->_selectionCondition);
            if (! empty($condition->getConditionRules())) {
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
     * @param  ElementConditionInterface|string|array|null  $condition
     *
     * @phpstan-param ElementConditionInterface|string|array{class:string}|null $condition
     */
    public function setSelectionCondition(mixed $condition): void
    {
        if ($condition instanceof ConditionInterface && ! $condition->getConditionRules()) {
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
     */
    protected function createSelectionCondition(): ?ElementConditionInterface
    {
        return null;
    }

    /**
     * Returns whether the field is configured with a selection condition.
     */
    protected function hasSelectionCondition(): bool
    {
        return isset($this->_selectionCondition);
    }

    /**
     * Returns the site ID that target elements should have.
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

        return Sites::getCurrentSite()->id;
    }

    private function _targetSiteId(): ?int
    {
        if ($this->targetSiteId && Sites::isMultiSite()) {
            try {
                return Sites::getSiteByUid($this->targetSiteId)->id;
            } catch (SiteNotFoundException $exception) {
                Log::warning($exception->getMessage(), [__METHOD__]);
            }
        }

        return null;
    }

    /**
     * Returns the field’s supported view modes.
     */
    protected function supportedViewModes(): array
    {
        $viewModes = [
            self::VIEW_MODE_LIST => t('List'),
            self::VIEW_MODE_LIST_INLINE => t('Inline list'),
        ];

        if ($this->allowLargeThumbsView) {
            $viewModes[self::VIEW_MODE_THUMBS] = t('Thumbs');
        }

        $viewModes[self::VIEW_MODE_CARDS] = t('Cards');
        $viewModes[self::VIEW_MODE_CARDS_GRID] = t('Card grid');

        return $viewModes;
    }

    /**
     * Returns the field’s current view mode.
     */
    protected function viewMode(): string
    {
        $supportedViewModes = $this->supportedViewModes();
        $viewMode = $this->viewMode;

        if ($viewMode && isset($supportedViewModes[$viewMode])) {
            return $viewMode;
        }

        return self::VIEW_MODE_LIST;
    }

    /**
     * Returns the sources that should be available to choose from within the field's settings
     */
    protected function availableSources(): array
    {
        return resolve(ElementSources::class)
            ->getSources(static::elementType(), 'modal')
            ->where('type', '!=', ElementSources::TYPE_HEADING)
            ->values()
            ->all();
    }

    /**
     * Returns a clone of the element query value, prepped to include disabled and cross-site elements.
     */
    private function _all(ElementQueryInterface $query, ?ElementInterface $element = null): ElementQueryInterface
    {
        /** @var \CraftCms\Cms\Element\Queries\ElementQuery $query */
        $clone = (clone $query)
            ->drafts(null)
            ->status(null)
            ->site('*')
            ->limit(null)
            ->unique()
            ->eagerly(false);

        if ($element !== null) {
            $clone->preferSites([$this->targetSiteId($element)]);
        }

        return $clone;
    }
}
