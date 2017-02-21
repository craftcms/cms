<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\fields;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ElementHelper;
use craft\helpers\StringHelper;
use craft\tasks\LocalizeRelations;
use craft\validators\ArrayValidator;
use yii\base\NotSupportedException;

/**
 * BaseRelationField is the base class for classes representing a relational field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
abstract class BaseRelationField extends Field implements PreviewableFieldInterface, EagerLoadingFieldInterface
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function hasContentColumn(): bool
    {
        return false;
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

    // Properties
    // =========================================================================

    /**
     * @var string[]|null The source keys that this field can relate elements from (used if [[allowMultipleSources]] is set to true)
     */
    public $sources;

    /**
     * @var string|null The source key that this field can relate elements from (used if [[allowMultipleSources]] is set to false)
     */
    public $source;

    /**
     * @var int|null The site that this field should relate elements from
     */
    public $targetSiteId;

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
     * @var bool Whether to allow the “Large Thumbnails” view mode
     */
    protected $allowLargeThumbsView = false;

    /**
     * @var string Temlpate to use for settings rendering
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
     * @var bool Whether existing relations should be made translatable after the field is saved
     */
    private $_makeExistingRelationsTranslatable = false;

    // Public Methods
    // =========================================================================

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
        $attributes[] = 'localizeRelations';

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate($this->settingsTemplate, [
            'field' => $this,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        // Don't call parent::getElementValidationRules() here - we'll do our own required validation
        return [
            [
                ArrayValidator::class,
                'min' => $this->required ? 1 : null,
                'max' => $this->allowLimit && $this->limit ? $this->limit : null,
                'tooFew' => Craft::t('app', '{attribute} should contain at least {min, number} {min, plural, one{selection} other{selections}}.'),
                'tooMany' => Craft::t('app', '{attribute} should contain at most {max, number} {max, plural, one{selection} other{selections}}.'),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        /** @var Element $element */
        /** @var Element $class */
        $class = static::elementType();
        /** @var ElementQuery $query */
        $query = $class::find()
            ->siteId($this->targetSiteId($element));

        // $value will be an array of element IDs if there was a validation error or we're loading a draft/version.
        if (is_array($value)) {
            $query
                ->id(array_values(array_filter($value)))
                ->fixedOrder();
        } else if ($value !== '' && !empty($element->id)) {
            $query->relatedTo([
                'sourceElement' => $element->id,
                'sourceSite' => $element->siteId,
                'field' => $this->id
            ]);

            if ($this->sortable) {
                $query->orderBy(['sortOrder' => SORT_ASC]);
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
    public function modifyElementsQuery(ElementQueryInterface $query, $value)
    {
        /** @var ElementQuery $query */
        if ($value === 'not :empty:') {
            $value = ':notempty:';
        }

        if ($value === ':notempty:' || $value === ':empty:') {
            $alias = 'relations_'.$this->handle;
            $operator = ($value === ':notempty:' ? '!=' : '=');
            $paramHandle = ':fieldId'.StringHelper::randomString(8);

            $query->subQuery->andWhere(
                "(select count([[{$alias}.id]]) from {{%relations}} {{{$alias}}} where [[{$alias}.sourceId]] = [[elements.id]] and [[{$alias}.fieldId]] = {$paramHandle}) {$operator} 0",
                [$paramHandle => $this->id]
            );
        } else if ($value !== null) {
            return false;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        /** @var ElementQuery $value */
        $variables = $this->inputTemplateVariables($value, $element);

        return Craft::$app->getView()->renderTemplate($this->inputTemplate, $variables);
    }

    /**
     * @inheritdoc
     */
    public function getSearchKeywords($value, ElementInterface $element): string
    {
        /** @var ElementQuery $value */
        $titles = [];

        foreach ($value->all() as $relatedElement) {
            $titles[] = (string)$relatedElement;
        }

        return parent::getSearchKeywords($titles, $element);
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml($value, ElementInterface $element): string
    {
        /** @var ElementQuery $value */
        if (count($value)) {
            $html = '<div class="elementselect"><div class="elements">';

            foreach ($value as $relatedElement) {
                $html .= Craft::$app->getView()->renderTemplate('_elements/element',
                    [
                        'element' => $relatedElement
                    ]);
            }

            $html .= '</div></div>';

            return $html;
        }

        return '<p class="light">'.Craft::t('app', 'Nothing selected.').'</p>';
    }

    /**
     * @inheritdoc
     */
    public function getTableAttributeHtml($value, ElementInterface $element): string
    {
        if ($value instanceof ElementQueryInterface) {
            $element = $value->first();
        } else {
            $element = $value[0] ?? null;
        }

        if ($element) {
            return Craft::$app->getView()->renderTemplate('_elements/element', [
                'element' => $element
            ]);
        }

        return '';
    }

    /**
     * @inheritdoc
     */
    public function getEagerLoadingMap(array $sourceElements)
    {
        /** @var Element|null $firstElement */
        $firstElement = $sourceElements[0] ?? null;

        // Get the source element IDs
        $sourceElementIds = [];

        foreach ($sourceElements as $sourceElement) {
            $sourceElementIds[] = $sourceElement->id;
        }

        // Return any relation data on these elements, defined with this field
        $map = (new Query())
            ->select(['sourceId as source', 'targetId as target'])
            ->from(['{{%relations}}'])
            ->where([
                'and',
                [
                    'fieldId' => $this->id,
                    'sourceId' => $sourceElementIds,
                ],
                [
                    'or',
                    ['sourceSiteId' => $firstElement ? $firstElement->siteId : null],
                    ['sourceSiteId' => null]
                ]
            ])
            ->orderBy(['sortOrder' => SORT_ASC])
            ->all();

        // Figure out which target site to use
        $targetSite = $this->targetSiteId($firstElement);

        return [
            'elementType' => static::elementType(),
            'map' => $map,
            'criteria' => [
                'siteId' => $targetSite
            ],
        ];
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function beforeSave(bool $isNew): bool
    {
        $this->_makeExistingRelationsTranslatable = false;

        if ($this->id && $this->localizeRelations) {
            /** @var Field $existingField */
            $existingField = Craft::$app->getFields()->getFieldById($this->id);

            if ($existingField && $existingField instanceof BaseRelationField && !$existingField->localizeRelations) {
                $this->_makeExistingRelationsTranslatable = true;
            }
        }

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew)
    {
        if ($this->_makeExistingRelationsTranslatable) {
            Craft::$app->getTasks()->queueTask([
                'type' => LocalizeRelations::class,
                'fieldId' => $this->id,
            ]);
        }

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function afterElementSave(ElementInterface $element, bool $isNew)
    {
        $value = $element->getFieldValue($this->handle);

        if ($value instanceof ElementQueryInterface && $value->id !== null) {
            /** @var ElementQuery $value */
            $value = $value->id ?: [];
            /** @var int|int[]|false|null $value */
            Craft::$app->getRelations()->saveRelations($this, $element, $value);
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
        /** @var Element $class */
        $class = static::elementType();

        if (Craft::$app->getIsMultiSite() && $class::isLocalized()) {
            $siteOptions = [
                ['label' => Craft::t('app', 'Same as source'), 'value' => null]
            ];

            foreach (Craft::$app->getSites()->getAllSites() as $site) {
                $siteOptions[] = [
                    'label' => Craft::t('site', $site->name),
                    'value' => $site->id
                ];
            }

            return Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'selectField',
                [
                    [
                        'label' => Craft::t('app', 'Target Site'),
                        'instructions' => Craft::t('app', 'Which site do you want to select {type} in?', ['type' => StringHelper::toLowerCase(static::displayName())]),
                        'id' => 'targetSiteId',
                        'name' => 'targetSiteId',
                        'options' => $siteOptions,
                        'value' => $this->targetSiteId
                    ]
                ]);
        }

        return null;
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

    // Protected Methods
    // =========================================================================

    /**
     * Returns an array of variables that should be passed to the input template.
     *
     * @param ElementQueryInterface|null $selectedElementsQuery
     * @param ElementInterface|null      $element
     *
     * @return array
     */
    protected function inputTemplateVariables(ElementQueryInterface $selectedElementsQuery = null, ElementInterface $element = null): array
    {
        if (!($selectedElementsQuery instanceof ElementQueryInterface)) {
            /** @var Element $class */
            $class = static::elementType();
            $selectedElementsQuery = $class::find()
                ->id(false);
        } else {
            $selectedElementsQuery
                ->status(null)
                ->enabledForSite(false);
        }

        $selectionCriteria = $this->inputSelectionCriteria();
        $selectionCriteria['enabledForSite'] = null;
        $selectionCriteria['siteId'] = $this->targetSiteId($element);

        return [
            'jsClass' => $this->inputJsClass,
            'elementType' => static::elementType(),
            'id' => Craft::$app->getView()->formatInputId($this->handle),
            'fieldId' => $this->id,
            'storageKey' => 'field.'.$this->id,
            'name' => $this->handle,
            'elements' => $selectedElementsQuery,
            'sources' => $this->inputSources($element),
            'criteria' => $selectionCriteria,
            'sourceElementId' => !empty($element->id) ? $element->id : null,
            'limit' => $this->allowLimit ? $this->limit : null,
            'viewMode' => $this->viewMode(),
            'selectionLabel' => $this->selectionLabel ? Craft::t('site', $this->selectionLabel) : static::defaultSelectionLabel(),
        ];
    }

    /**
     * Returns an array of the source keys the field should be able to select elements from.
     *
     * @param ElementInterface|null $element
     *
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
        return [];
    }

    /**
     * Returns the site ID that target elements should have.
     *
     * @param ElementInterface|null $element
     *
     * @return int
     */
    protected function targetSiteId(ElementInterface $element = null): int
    {
        /** @var Element|null $element */
        if (Craft::$app->getIsMultiSite()) {
            if ($this->targetSiteId) {
                return $this->targetSiteId;
            }

            if ($element !== null) {
                return $element->siteId;
            }
        }

        return Craft::$app->getSites()->currentSite->id;
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
}
