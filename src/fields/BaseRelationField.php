<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\BlockElementInterface;
use craft\base\conditions\ConditionInterface;
use craft\base\EagerLoadingFieldInterface;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\db\Query;
use craft\db\QueryAbortedException;
use craft\db\Table as DbTable;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\ElementRelationParamParser;
use craft\elements\ElementCollection;
use craft\errors\SiteNotFoundException;
use craft\events\ElementCriteriaEvent;
use craft\events\ElementEvent;
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
use Illuminate\Support\Collection;
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
abstract class BaseRelationField extends Field implements PreviewableFieldInterface, EagerLoadingFieldInterface
{
    /**
     * @event ElementCriteriaEvent The event that is triggered when defining the selection criteria for this field.
     * @since 3.4.16
     */
    public const EVENT_DEFINE_SELECTION_CRITERIA = 'defineSelectionCriteria';

    /**
     * @inheritdoc
     */
    public static function hasContentColumn(): bool
    {
        return false;
    }

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
    public static function valueType(): string
    {
        return sprintf('\\%s|\\%s<\\%s>', ElementQueryInterface::class, ElementCollection::class, ElementInterface::class);
    }

    /**
     * @var array Related elements that have been validated
     * @see _validateRelatedElement()
     */
    private static array $_relatedElementValidates = [];

    /**
     * @var bool Whether we're listening for related element saves yet
     * @see _validateRelatedElement()
     */
    private static bool $_listeningForRelatedElementSave = false;

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
     * @var string|null The view mode
     */
    public ?string $viewMode = null;

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

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     * @since 3.4.9
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['minRelations', 'maxRelations'], 'number', 'integerOnly' => true];
        return $rules;
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

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function getSettings(): array
    {
        $settings = parent::getSettings();

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
        return Craft::$app->getView()->renderTemplate($this->settingsTemplate, $variables);
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
            /** @var ElementQueryInterface|Collection $value */
            $value = $element->getFieldValue($this->handle);

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
        // Prevent circular relations from worrying about this entry
        $sourceId = $element->getCanonicalId();
        $sourceValidates = self::$_relatedElementValidates[$sourceId][$element->siteId] ?? null;
        self::$_relatedElementValidates[$sourceId][$element->siteId] = true;

        /** @var ElementQueryInterface|Collection $value */
        $value = $element->getFieldValue($this->handle);
        $errorCount = 0;

        foreach ($value->all() as $i => $related) {
            /** @var Element $related */
            if ($related->enabled && $related->getEnabledForSite()) {
                if (!self::_validateRelatedElement($related)) {
                    $element->addModelErrors($related, "$this->handle[$i]");
                    $errorCount++;
                }
            }
        }

        // Reset self::$_relatedElementValidates[$sourceId][$element->siteId] to its original value
        if ($sourceValidates !== null) {
            self::$_relatedElementValidates[$sourceId][$element->siteId] = $sourceValidates;
        } else {
            unset(self::$_relatedElementValidates[$sourceId][$element->siteId]);
        }

        if ($errorCount) {
            /** @var ElementInterface|string $elementType */
            $elementType = static::elementType();
            $element->addError($this->handle, Craft::t('app', 'Fix validation errors on the related {type}.', [
                'type' => $errorCount === 1 ? $elementType::lowerDisplayName() : $elementType::pluralLowerDisplayName(),
            ]));
        }
    }

    /**
     * Returns whether a related element validates.
     *
     * @param ElementInterface $element
     * @return bool
     */
    private static function _validateRelatedElement(ElementInterface $element): bool
    {
        if (isset(self::$_relatedElementValidates[$element->id][$element->siteId])) {
            return self::$_relatedElementValidates[$element->id][$element->siteId];
        }

        // If this is the first time we are validating a related element,
        // listen for future element saves so we can clear our cache
        if (!self::$_listeningForRelatedElementSave) {
            Event::on(Elements::class, Elements::EVENT_AFTER_SAVE_ELEMENT, function(ElementEvent $e) {
                $element = $e->element;
                unset(self::$_relatedElementValidates[$element->id][$element->siteId]);
            });
            self::$_listeningForRelatedElementSave = true;
        }

        // Prevent an infinite loop if there are circular relations
        self::$_relatedElementValidates[$element->id][$element->siteId] = true;

        $element->setScenario(Element::SCENARIO_LIVE);
        return self::$_relatedElementValidates[$element->id][$element->siteId] = $element->validate();
    }

    /**
     * @inheritdoc
     */
    public function isValueEmpty(mixed $value, ElementInterface $element): bool
    {
        /** @var ElementQueryInterface|Collection $value */
        if ($value instanceof ElementQueryInterface) {
            return !$this->_all($value, $element)->exists();
        }

        return $value->isEmpty();
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if ($value instanceof ElementQueryInterface) {
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
            $query->innerJoin(
                ['relations' => DbTable::RELATIONS],
                [
                    'and',
                    '[[relations.targetId]] = [[elements.id]]',
                    [
                        'relations.sourceId' => $element->id,
                        'relations.fieldId' => $this->id,
                    ],
                    [
                        'or',
                        ['relations.sourceSiteId' => null],
                        ['relations.sourceSiteId' => $element->siteId],
                    ],
                ]
            );

            if ($this->sortable) {
                $query->orderBy(['relations.sortOrder' => SORT_ASC]);
            }

            if (!$this->allowMultipleSources && $this->source) {
                $source = ElementHelper::findSource($class, $this->source, ElementSources::CONTEXT_FIELD);

                // Does the source specify any criteria attributes?
                if (isset($source['criteria'])) {
                    Craft::configure($query, $source['criteria']);
                }
            }
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
    public function serializeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        /** @var ElementQueryInterface|Collection $value */
        if ($value instanceof Collection) {
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
    public function modifyElementsQuery(ElementQueryInterface $query, mixed $value): void
    {
        if (empty($value)) {
            return;
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        /** @var ElementQuery $query */
        $conditions = [];

        if (isset($value[0]) && in_array($value[0], [':notempty:', ':empty:', 'not :empty:'])) {
            $emptyCondition = array_shift($value);
            if ($emptyCondition === 'not :empty:') {
                $emptyCondition = ':notempty:';
            }

            $ns = $this->handle . '_' . StringHelper::randomString(5);
            $condition = [
                'exists', (new Query())
                    ->from(["relations_$ns" => DbTable::RELATIONS])
                    ->innerJoin(["elements_$ns" => DbTable::ELEMENTS], "[[elements_$ns.id]] = [[relations_$ns.targetId]]")
                    ->leftJoin(["elements_sites_$ns" => DbTable::ELEMENTS_SITES], "[[elements_sites_$ns.elementId]] = [[elements_$ns.id]]")
                    ->where("[[relations_$ns.sourceId]] = [[elements.id]]")
                    ->andWhere([
                        'or',
                        ["relations_$ns.sourceSiteId" => null],
                        ["relations_$ns.sourceSiteId" => new Expression('[[elements_sites.siteId]]')],
                    ])
                    ->andWhere([
                        "relations_$ns.fieldId" => $this->id,
                        "elements_$ns.enabled" => true,
                        "elements_$ns.dateDeleted" => null,
                        "elements_sites_$ns.siteId" => $this->_targetSiteId() ?? new Expression('[[elements_sites.siteId]]'),
                        "elements_sites_$ns.enabled" => true,
                    ]),
            ];

            if ($emptyCondition === ':notempty:') {
                $conditions[] = $condition;
            } else {
                $conditions[] = ['not', $condition];
            }
        }

        if (!empty($value)) {
            $parser = new ElementRelationParamParser([
                'fields' => [
                    $this->handle => $this,
                ],
            ]);
            $condition = $parser->parse([
                'targetElement' => $value,
                'field' => $this->handle,
            ]);
            if ($condition !== false) {
                $conditions[] = $condition;
            }
        }

        if (empty($conditions)) {
            throw new QueryAbortedException();
        }

        array_unshift($conditions, 'or');
        $query->subQuery->andWhere($conditions);
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
    public function getIsTranslatable(?ElementInterface $element = null): bool
    {
        return $this->localizeRelations;
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        if ($element !== null && $element->hasEagerLoadedElements($this->handle)) {
            $value = $element->getEagerLoadedElements($this->handle)->all();
        } else {
            /** @var ElementQueryInterface $value */
            $value = $this->_all($value, $element);
        }

        /** @var ElementQuery|array $value */
        $variables = $this->inputTemplateVariables($value, $element);

        return Craft::$app->getView()->renderTemplate($this->inputTemplate, $variables);
    }

    /**
     * @inheritdoc
     */
    protected function searchKeywords(mixed $value, ElementInterface $element): string
    {
        /** @var ElementQuery|Collection $value */
        $titles = [];

        foreach ($this->_all($value, $element)->all() as $relatedElement) {
            $titles[] = (string)$relatedElement;
        }

        return parent::searchKeywords($titles, $element);
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        /** @var ElementQueryInterface|Collection $value */
        if ($value instanceof Collection) {
            $value = $value->all();
        } else {
            $value = $this->_all($value, $element)->all();
        }

        if (empty($value)) {
            return '<p class="light">' . Craft::t('app', 'Nothing selected.') . '</p>';
        }

        $view = Craft::$app->getView();
        $id = $this->getInputId();
        $html = "<div id='$id' class='elementselect'><div class='elements'>";

        foreach ($value as $relatedElement) {
            $html .= Cp::elementHtml($relatedElement);
        }

        $html .= '</div></div>';

        $nsId = $view->namespaceInputId($id);
        $js = <<<JS
(new Craft.ElementThumbLoader()).load($('#$nsId'));
JS;
        $view->registerJs($js);

        return $html;
    }

    /**
     * @inheritdoc
     */
    public function getTableAttributeHtml(mixed $value, ElementInterface $element): string
    {
        /** @var ElementQueryInterface|Collection $value */
        if ($value instanceof ElementQueryInterface) {
            $value = $this->_all($value, $element)->collect();
        }

        return $this->tableAttributeHtml($value);
    }

    /**
     * Returns the HTML that should be shown for this field in Table View.
     *
     * @param Collection $elements
     * @return string
     * @since 3.6.3
     */
    protected function tableAttributeHtml(Collection $elements): string
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
        $sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id', false);

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
            $element->isFieldDirty($this->handle) &&
            (!$element->propagating || $this->localizeRelations)
        ) {
            /** @var ElementQueryInterface|Collection $value */
            $value = $element->getFieldValue($this->handle);

            // $value will be an element query and its $id will be set if we're saving new relations
            if ($value instanceof Collection) {
                $targetIds = $value->map(fn(ElementInterface $element) => $element->id)->all();
            } elseif (
                is_array($value->id) &&
                ArrayHelper::isNumeric($value->id)
            ) {
                $targetIds = $value->id ?: [];
            } else {
                $targetIds = $this->_all($value, $element)->ids();
            }

            /** @var int|int[]|false|null $targetIds */
            Craft::$app->getRelations()->saveRelations($this, $element, $targetIds);

            // Reset the field value if this is a new element
            if ($isNew) {
                $element->setFieldValue($this->handle, null);
            }

            if (!$this->localizeRelations && ElementHelper::shouldTrackChanges($element)) {
                // Mark the field as dirty across all of the element’s sites
                // (this is a little hacky but there’s not really a non-hacky alternative unfortunately.)
                $siteIds = ArrayHelper::getColumn(ElementHelper::supportedSitesForElement($element), 'siteId');
                $siteIds = ArrayHelper::withoutValue($siteIds, $element->siteId);
                if (!empty($siteIds)) {
                    $userId = Craft::$app->getUser()->getId();
                    $timestamp = Db::prepareDateForDb(new DateTime());

                    foreach ($siteIds as $siteId) {
                        Db::upsert(DbTable::CHANGEDFIELDS, [
                            'elementId' => $element->id,
                            'siteId' => $siteId,
                            'fieldId' => $this->id,
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
     * Normalizes the available sources into select input options.
     *
     * @return array
     */
    public function getSourceOptions(): array
    {
        $options = array_map(
            fn($s) => ['label' => $s['label'], 'value' => $s['key']],
            $this->availableSources()
        );
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

        if ($this->validateRelatedElements) {
            // Pre-validate related elements
            foreach ($value as $related) {
                if ($related->enabled && $related->getEnabledForSite()) {
                    $related->setScenario(Element::SCENARIO_LIVE);
                    $related->validate();
                }
            }
        }

        $selectionCriteria = $this->getInputSelectionCriteria();
        $selectionCriteria['siteId'] = $this->targetSiteId($element);

        $disabledElementIds = [];

        if (!$this->allowSelfRelations && $element) {
            if ($element->id) {
                $disabledElementIds[] = $element->getCanonicalId();
            }
            if ($element instanceof BlockElementInterface) {
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
                } while ($el instanceof BlockElementInterface);
            }
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
            'condition' => $this->getSelectionCondition(),
            'criteria' => $selectionCriteria,
            'showSiteMenu' => ($this->targetSiteId || !$this->showSiteMenu) ? false : 'auto',
            'allowSelfRelations' => $this->allowSelfRelations,
            'sourceElementId' => !empty($element->id) ? $element->id : null,
            'disabledElementIds' => $disabledElementIds,
            'limit' => $this->allowLimit ? $this->maxRelations : null,
            'viewMode' => $this->viewMode(),
            'selectionLabel' => $this->selectionLabel ? Craft::t('site', $this->selectionLabel) : static::defaultSelectionLabel(),
            'sortable' => $this->sortable,
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
        // Fire a defineSelectionCriteria event
        $event = new ElementCriteriaEvent();
        $this->trigger(self::EVENT_DEFINE_SELECTION_CRITERIA, $event);
        return $event->criteria;
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
            $this->_selectionCondition = Craft::$app->getConditions()->createCondition($this->_selectionCondition);
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
        // Avoids an infinite recursion bug (ElementCondition::conditionRuleTypes() => getAllFields() => setSelectionCondition() => ...)
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
