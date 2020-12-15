<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\BlockElementInterface;
use craft\base\EagerLoadingFieldInterface;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\db\Query;
use craft\db\Table as DbTable;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\ElementRelationParamParser;
use craft\errors\SiteNotFoundException;
use craft\events\ElementCriteriaEvent;
use craft\events\ElementEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\Queue;
use craft\helpers\StringHelper;
use craft\queue\jobs\LocalizeRelations;
use craft\services\Elements;
use craft\validators\ArrayValidator;
use GraphQL\Type\Definition\Type;
use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;

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
    const EVENT_DEFINE_SELECTION_CRITERIA = 'defineSelectionCriteria';

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
     * @throws NotSupportedException if the method hasn't been implemented by the subclass
     */
    protected static function elementType(): string
    {
        throw new NotSupportedException('"elementType()" is not implemented.');
    }

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
        return ElementQueryInterface::class;
    }

    /**
     * @var array Related elements that have been validated
     * @see _validateRelatedElement()
     */
    private static $_relatedElementValidates = [];

    /**
     * @var bool Whether we're listening for related element saves yet
     * @see _validateRelatedElement()
     */
    private static $_listeningForRelatedElementSave = false;

    /**
     * @var string|string[]|null The source keys that this field can relate elements from (used if [[allowMultipleSources]] is set to true)
     */
    public $sources = '*';

    /**
     * @var string|null The source key that this field can relate elements from (used if [[allowMultipleSources]] is set to false)
     */
    public $source;

    /**
     * @var string|null The UID of the site that this field should relate elements from
     */
    public $targetSiteId;

    /**
     * @var bool Whether the site menu should be shown in element selector modals.
     *
     * @since 3.5.0
     */
    public $showSiteMenu = false;

    /**
     * @var string|null The view mode
     */
    public $viewMode;

    /**
     * @var int|null The maximum number of relations this field can have (used if [[allowLimit]] is set to true)
     */
    public $limit;

    /**
     * @var string|null The label that should be used on the selection input
     */
    public $selectionLabel;

    /**
     * @var bool Whether related elements should be validated when the source element is saved.
     */
    public $validateRelatedElements = false;

    /**
     * @var int Whether each site should get its own unique set of relations
     */
    public $localizeRelations = false;

    /**
     * @var bool Whether to allow multiple source selection in the settings
     */
    public $allowMultipleSources = true;

    /**
     * @var bool Whether to allow the Limit setting
     */
    public $allowLimit = true;

    /**
     * @var bool Whether elements should be allowed to relate themselves.
     * @since 3.4.21
     */
    public $allowSelfRelations = false;

    /**
     * @var bool Whether to allow the “Large Thumbnails” view mode
     */
    protected $allowLargeThumbsView = false;

    /**
     * @var string Template to use for settings rendering
     */
    protected $settingsTemplate = '_components/fieldtypes/elementfieldsettings';

    /**
     * @var string Template to use for field rendering
     */
    protected $inputTemplate = '_includes/forms/elementSelect';

    /**
     * @var string|null The JS class that should be initialized for the input
     */
    protected $inputJsClass;

    /**
     * @var bool Whether the elements have a custom sort order
     */
    protected $sortable = true;

    /**
     * @inheritdoc
     */
    public function __construct(array $config = [])
    {
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
     */
    public function init()
    {
        parent::init();

        // Not possible to have no sources selected
        if (!$this->sources) {
            $this->sources = '*';
        }

        $this->validateRelatedElements = (bool)$this->validateRelatedElements;
        $this->allowSelfRelations = (bool)$this->allowSelfRelations;
        $this->showSiteMenu = (bool)$this->showSiteMenu;
        $this->localizeRelations = (bool)$this->localizeRelations;
    }

    /**
     * @inheritdoc
     * @since 3.4.9
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['limit'], 'number', 'integerOnly' => true];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'sources';
        $attributes[] = 'source';
        $attributes[] = 'targetSiteId';
        $attributes[] = 'viewMode';
        $attributes[] = 'limit';
        $attributes[] = 'selectionLabel';
        $attributes[] = 'showSiteMenu';
        $attributes[] = 'localizeRelations';
        $attributes[] = 'validateRelatedElements';
        $attributes[] = 'allowSelfRelations';

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
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
            [
                ArrayValidator::class,
                'max' => $this->allowLimit && $this->limit ? $this->limit : null,
                'tooMany' => Craft::t('app', '{attribute} should contain at most {max, number} {max, plural, one{selection} other{selections}}.'),
            ],
        ];

        if ($this->validateRelatedElements) {
            $rules[] = ['validateRelatedElements', 'on' => [Element::SCENARIO_LIVE]];
        }

        return $rules;
    }

    /**
     * Validates the related elements.
     *
     * @param ElementInterface $element
     */
    public function validateRelatedElements(ElementInterface $element)
    {
        // Prevent circular relations from worrying about this entry
        $sourceId = $element->getSourceId();
        $sourceValidates = self::$_relatedElementValidates[$sourceId][$element->siteId] ?? null;
        self::$_relatedElementValidates[$sourceId][$element->siteId] = true;

        /** @var ElementQueryInterface $query */
        $query = $element->getFieldValue($this->handle);
        $errorCount = 0;

        foreach ($query->all() as $i => $related) {
            if ($related->enabled && $related->getEnabledForSite()) {
                if (!self::_validateRelatedElement($related)) {
                    $element->addModelErrors($related, "{$this->handle}[{$i}]");
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
    public function isValueEmpty($value, ElementInterface $element): bool
    {
        /** @var ElementQueryInterface|ElementInterface[] $value */
        if ($value instanceof ElementQueryInterface) {
            return !$this->_all($value, $element)->exists();
        }

        return empty($value);
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        if ($value instanceof ElementQueryInterface) {
            return $value;
        }

        /** @var ElementInterface $class */
        $class = static::elementType();
        /** @var ElementQuery $query */
        $query = $class::find()
            ->siteId($this->targetSiteId($element));

        // $value will be an array of element IDs if there was a validation error or we're loading a draft/version.
        if (is_array($value)) {
            $query
                ->id(array_values(array_filter($value)))
                ->fixedOrder();
        } else if ($value !== '' && $element && $element->id) {
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
                        ['relations.sourceSiteId' => $element->siteId]
                    ]
                ]
            );

            if ($this->sortable) {
                $query->orderBy(['relations.sortOrder' => SORT_ASC]);
            }

            if (!$this->allowMultipleSources && $this->source) {
                $source = ElementHelper::findSource($class, $this->source);

                // Does the source specify any criteria attributes?
                if (isset($source['criteria'])) {
                    Craft::configure($query, $source['criteria']);
                }
            }
        } else {
            $query->id(false);
        }

        if ($this->allowLimit && $this->limit) {
            $query->limit($this->limit);
        }

        return $query;
    }

    /**
     * @inheritdoc
     */
    public function serializeValue($value, ElementInterface $element = null)
    {
        /** @var ElementQueryInterface $value */
        return $this->_all($value, $element)->ids();
    }

    /**
     * @inheritdoc
     */
    public function modifyElementsQuery(ElementQueryInterface $query, $value)
    {
        if (empty($value)) {
            return null;
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
                    ->leftJoin(["elements_sites_$ns" => DbTable::ELEMENTS_SITES], [
                        'and',
                        "[[elements_sites_$ns.elementId]] = [[elements_$ns.id]]",
                        ["elements_sites_$ns.siteId" => $query->siteId],
                    ])
                    ->where("[[relations_$ns.sourceId]] = [[elements.id]]")
                    ->andWhere([
                        "relations_$ns.fieldId" => $this->id,
                        "elements_$ns.enabled" => true,
                        "elements_$ns.dateDeleted" => null,
                    ])
                    ->andWhere(['not', ["elements_sites_$ns.enabled" => false]])
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
            return false;
        }

        array_unshift($conditions, 'or');
        $query->subQuery->andWhere($conditions);

        return null;
    }

    /**
     * @inheritdoc
     */
    public function modifyElementIndexQuery(ElementQueryInterface $query)
    {
        $criteria = [
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
    public function getIsTranslatable(ElementInterface $element = null): bool
    {
        return $this->localizeRelations;
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml($value, ElementInterface $element = null): string
    {
        if ($element !== null && $element->hasEagerLoadedElements($this->handle)) {
            $value = $element->getEagerLoadedElements($this->handle);
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
    protected function searchKeywords($value, ElementInterface $element): string
    {
        /** @var ElementQuery $value */
        $titles = [];

        foreach ($value->all() as $relatedElement) {
            $titles[] = (string)$relatedElement;
        }

        return parent::searchKeywords($titles, $element);
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml($value, ElementInterface $element): string
    {
        $value = $this->_all($value, $element)->all();

        if (empty($value)) {
            return '<p class="light">' . Craft::t('app', 'Nothing selected.') . '</p>';
        }

        $view = Craft::$app->getView();
        $id = Html::id($this->handle);
        $html = "<div id='{$id}' class='elementselect'><div class='elements'>";

        foreach ($value as $relatedElement) {
            $html .= Cp::elementHtml($relatedElement);
        }

        $html .= '</div></div>';

        $nsId = $view->namespaceInputId($id);
        $js = <<<JS
(new Craft.ElementThumbLoader()).load($('#{$nsId}'));
JS;
        $view->registerJs($js);

        return $html;
    }

    /**
     * @inheritdoc
     */
    public function getTableAttributeHtml($value, ElementInterface $element): string
    {
        if ($value instanceof ElementQueryInterface) {
            $value = $this->_all($value, $element)->all();
        }

        if (empty($value)) {
            return '';
        }

        $first = array_shift($value);
        $html = $this->elementPreviewHtml($first);

        if (!empty($value)) {
            $otherHtml = '';
            foreach ($value as $other) {
                $otherHtml .= $this->elementPreviewHtml($other);
            }
            $html .= Html::tag('span', '+' . Craft::$app->getFormatter()->asInteger(count($value)), [
                'title' => implode(', ', ArrayHelper::getColumn($value, 'title')),
                'class' => 'btn small',
                'role' => 'button',
                'onclick' => 'jQuery(this).replaceWith(' . Json::encode($otherHtml) . ')',
            ]);
        }

        return $html;
    }

    /**
     * Renders a related element’s HTML for the element index.
     *
     * @param ElementInterface $element
     * @return string
     * @since 3.5.11
     */
    protected function elementPreviewHtml(ElementInterface $element): string
    {
        return Cp::elementHtml($element);
    }

    /**
     * @inheritdoc
     */
    public function getEagerLoadingMap(array $sourceElements)
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
                    ['sourceSiteId' => null]
                ]
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
    public function getContentGqlMutationArgumentType()
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
    public function afterSave(bool $isNew)
    {
        // If the propagation method just changed, resave all the Matrix blocks
        if ($this->oldSettings !== null) {
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
    public function afterElementSave(ElementInterface $element, bool $isNew)
    {
        // Skip if nothing changed, or the element is just propagating and we're not localizing relations
        if (
            $element->isFieldDirty($this->handle) &&
            (!$element->propagating || $this->localizeRelations)
        ) {
            /** @var ElementQuery $value */
            $value = $element->getFieldValue($this->handle);

            // $id will be set if we're saving new relations
            if ($value->id !== null) {
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
        $options = [];
        $optionNames = [];

        foreach ($this->availableSources() as $source) {
            // Make sure it's not a heading
            if (!isset($source['heading'])) {
                $options[] = [
                    'label' => $source['label'],
                    'value' => $source['key']
                ];
                $optionNames[] = $source['label'];
            }
        }

        // Sort alphabetically
        array_multisort($optionNames, SORT_NATURAL | SORT_FLAG_CASE, $options);

        return $options;
    }

    /**
     * Returns the HTML for the Target Site setting.
     *
     * @return string|null
     */
    public function getTargetSiteFieldHtml()
    {
        /** @var ElementInterface|string $class */
        $class = static::elementType();

        if (!Craft::$app->getIsMultiSite() || !$class::isLocalized()) {
            return null;
        }

        $view = Craft::$app->getView();
        $type = $class::lowerDisplayName();
        $pluralType = $class::pluralLowerDisplayName();
        $showTargetSite = !empty($this->targetSiteId);
        $siteOptions = [];

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $siteOptions[] = [
                'label' => Craft::t('site', $site->name),
                'value' => $site->uid
            ];
        }

        return
            $view->renderTemplateMacro('_includes/forms', 'checkboxField', [
                [
                    'label' => Craft::t('app', 'Relate {type} from a specific site?', ['type' => $pluralType]),
                    'name' => 'useTargetSite',
                    'checked' => $showTargetSite,
                    'toggle' => 'target-site-field',
                    'reverseToggle' => 'show-site-menu-field',
                ]
            ]) .
            $view->renderTemplateMacro('_includes/forms', 'selectField', [
                [
                    'fieldClass' => !$showTargetSite ? 'hidden' : null,
                    'label' => Craft::t('app', 'Which site should {type} be related from?', ['type' => $pluralType]),
                    'id' => 'target-site',
                    'name' => 'targetSiteId',
                    'options' => $siteOptions,
                    'value' => $this->targetSiteId,
                ]
            ]) .
            $view->renderTemplateMacro('_includes/forms', 'checkboxField', [
                [
                    'fieldClass' => $showTargetSite ? 'hidden' : null,
                    'label' => Craft::t('app', 'Show the site menu'),
                    'instructions' => Craft::t('app', 'Whether the site menu should be shown for {type} selection modals.', [
                        'type' => $type,
                    ]),
                    'warning' => Craft::t('app', 'Relations don’t store the selected site, so this should only be enabled if some {type} aren’t propagated to all sites.', [
                        'type' => $pluralType,
                    ]),
                    'id' => 'show-site-menu',
                    'name' => 'showSiteMenu',
                    'checked' => $this->showSiteMenu,
                ]
            ]);
    }

    /**
     * Returns the HTML for the View Mode setting.
     *
     * @return string|null
     */
    public function getViewModeFieldHtml()
    {
        $supportedViewModes = $this->supportedViewModes();

        if (count($supportedViewModes) === 1) {
            return null;
        }

        $viewModeOptions = [];

        foreach ($supportedViewModes as $key => $label) {
            $viewModeOptions[] = ['label' => $label, 'value' => $key];
        }

        return Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'selectField', [
            [
                'label' => Craft::t('app', 'View Mode'),
                'instructions' => Craft::t('app', 'Choose how the field should look for authors.'),
                'id' => 'viewMode',
                'name' => 'viewMode',
                'options' => $viewModeOptions,
                'value' => $this->viewMode
            ]
        ]);
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

        return [
            'field' => $this,
            'elementType' => $elementType::lowerDisplayName(),
            'pluralElementType' => $elementType::pluralLowerDisplayName(),
        ];
    }

    /**
     * Returns an array of variables that should be passed to the input template.
     *
     * @param ElementQueryInterface|array|null $value
     * @param ElementInterface|null $element
     * @return array
     */
    protected function inputTemplateVariables($value = null, ElementInterface $element = null): array
    {
        if ($value instanceof ElementQueryInterface) {
            $value = $value
                ->anyStatus()
                ->all();
        } else if (!is_array($value)) {
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

        $selectionCriteria = $this->inputSelectionCriteria();
        $selectionCriteria['siteId'] = $this->inputSiteId($element);

        $disabledElementIds = [];

        if (!$this->allowSelfRelations && $element) {
            if ($element->id) {
                $disabledElementIds[] = $element->getSourceId();
            }
            if ($element instanceof BlockElementInterface) {
                $el = $element;
                do {
                    try {
                        $el = $el->getOwner();
                        $disabledElementIds[] = $el->getSourceId();
                    } catch (InvalidConfigException $e) {
                        break;
                    }
                } while ($el instanceof BlockElementInterface);
            }
        }

        return [
            'jsClass' => $this->inputJsClass,
            'elementType' => static::elementType(),
            'id' => Html::id($this->handle),
            'fieldId' => $this->id,
            'storageKey' => 'field.' . $this->id,
            'name' => $this->handle,
            'elements' => $value,
            'sources' => $this->inputSources($element),
            'criteria' => $selectionCriteria,
            'showSiteMenu' => ($this->targetSiteId || !$this->showSiteMenu) ? false : 'auto',
            'allowSelfRelations' => (bool)$this->allowSelfRelations,
            'sourceElementId' => !empty($element->id) ? $element->id : null,
            'disabledElementIds' => $disabledElementIds,
            'limit' => $this->allowLimit ? $this->limit : null,
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
     * @return array|string
     */
    protected function inputSources(ElementInterface $element = null)
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
    protected function inputSelectionCriteria(): array
    {
        // Fire a defineSelectionCriteria event
        $event = new ElementCriteriaEvent();
        $this->trigger(self::EVENT_DEFINE_SELECTION_CRITERIA, $event);
        return $event->criteria;
    }

    /**
     * Returns the site ID that the input should select elements from.
     *
     * @param ElementInterface|null $element
     * @return int|null
     * @since 3.4.19
     * @deprecated in 3.5.16
     */
    protected function inputSiteId(ElementInterface $element = null)
    {
        return $this->targetSiteId($element);
    }

    /**
     * Returns the site ID that target elements should have.
     *
     * @param ElementInterface|null $element
     * @return int
     */
    protected function targetSiteId(ElementInterface $element = null): int
    {
        if (Craft::$app->getIsMultiSite()) {
            if ($this->targetSiteId) {
                try {
                    return Craft::$app->getSites()->getSiteByUid($this->targetSiteId)->id;
                } catch (SiteNotFoundException $exception) {
                    Craft::warning($exception->getMessage(), __METHOD__);
                }
            }

            if ($element !== null) {
                return $element->siteId;
            }
        }

        return Craft::$app->getSites()->getCurrentSite()->id;
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
        return Craft::$app->getElementIndexes()->getSources(static::elementType(), 'modal');
    }

    /**
     * Returns a clone of the element query value, prepped to include disabled and cross-site elements.
     *
     * @param ElementQueryInterface $query
     * @param ElementInterface|null $element
     * @return ElementQueryInterface
     */
    private function _all(ElementQueryInterface $query, ElementInterface $element = null): ElementQueryInterface
    {
        $clone = clone $query;
        $clone
            ->anyStatus()
            ->siteId('*')
            ->unique();
        if ($element !== null) {
            $clone->preferSites([$this->targetSiteId($element)]);
        }
        return $clone;
    }
}
