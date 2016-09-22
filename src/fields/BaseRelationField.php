<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\fields;

use Craft;
use craft\app\base\EagerLoadingFieldInterface;
use craft\app\base\ElementInterface;
use craft\app\base\Field;
use craft\app\base\Element;
use craft\app\base\PreviewableFieldInterface;
use craft\app\db\Query;
use craft\app\elements\db\ElementQuery;
use craft\app\elements\db\ElementQueryInterface;
use craft\app\helpers\StringHelper;
use craft\app\tasks\LocalizeRelations;
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
    public static function hasContentColumn()
    {
        return false;
    }

    /**
     * Returns the element class associated with this field type.
     *
     * @return string The Element class name
     * @throws NotSupportedException if the method hasn't been implemented by the subclass
     */
    protected static function elementType()
    {
        throw new NotSupportedException('"elementType()" is not implemented.');
    }

    /**
     * Returns the default [[selectionLabel]] value.
     *
     * @return string The default selection label
     */
    public static function defaultSelectionLabel()
    {
        return Craft::t('app', 'Choose');
    }

    // Properties
    // =========================================================================

    /**
     * @var string[] The source keys that this field can relate elements from (used if [[allowMultipleSources]] is set to true)
     */
    public $sources;

    /**
     * @var string The source key that this field can relate elements from (used if [[allowMultipleSources]] is set to false)
     */
    public $source;

    /**
     * @var integer The site that this field should relate elements from
     */
    public $targetSiteId;

    /**
     * @var string The view mode
     */
    public $viewMode;

    /**
     * @var integer The maximum number of relations this field can have (used if [[allowLimit]] is set to true)
     */
    public $limit;

    /**
     * @var string The label that should be used on the selection input
     */
    public $selectionLabel;

    /**
     * @var integer Whether each site should get its own unique set of relations
     */
    public $localizeRelations = false;

    /**
     * @var boolean Whether to allow multiple source selection in the settings
     */
    protected $allowMultipleSources = true;

    /**
     * @var boolean Whether to allow the Limit setting
     */
    protected $allowLimit = true;

    /**
     * @var boolean Whether to allow the “Large Thumbnails” view mode
     */
    protected $allowLargeThumbsView = false;

    /**
     * @var string Template to use for field rendering
     */
    protected $inputTemplate = '_includes/forms/elementSelect';

    /**
     * @var string|null The JS class that should be initialized for the input
     */
    protected $inputJsClass;

    /**
     * @var boolean Whether the elements have a custom sort order
     */
    protected $sortable = true;

    /**
     * @var boolean Whether existing relations should be made translatable after the field is saved
     */
    private $_makeExistingRelationsTranslatable = false;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function settingsAttributes()
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
    public function beforeSave()
    {
        $this->_makeExistingRelationsTranslatable = false;

        if ($this->id && $this->localizeRelations) {
            /** @var Field $existingField */
            $existingField = Craft::$app->getFields()->getFieldById($this->id);

            if ($existingField && $existingField instanceof BaseRelationField && !$existingField->localizeRelations) {
                $this->_makeExistingRelationsTranslatable = true;
            }
        }

        return parent::beforeSave();
    }

    /**
     * @inheritdoc
     */
    public function afterSave()
    {
        if ($this->_makeExistingRelationsTranslatable) {
            Craft::$app->getTasks()->queueTask([
                'type' => LocalizeRelations::class,
                'fieldId' => $this->id,
            ]);
        }

        parent::afterSave();
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/elementfieldsettings',
            [
                'allowMultipleSources' => $this->allowMultipleSources,
                'allowLimit' => $this->allowLimit,
                'sources' => $this->getSourceOptions(),
                'targetSiteFieldHtml' => $this->getTargetSiteFieldHtml(),
                'viewModeFieldHtml' => $this->getViewModeFieldHtml(),
                'field' => $this,
                'displayName' => static::displayName(),
                'defaultSelectionLabel' => static::defaultSelectionLabel(),
            ]);
    }

    /**
     * @inheritdoc
     */
    public function validateValue($value, $element)
    {
        /** @var ElementQuery $value */
        $errors = [];

        // Do we need to validate the number of selections?
        if ($this->required || ($this->allowLimit && $this->limit)) {
            $total = $value->count();

            if ($this->required && $total == 0) {
                $errors[] = Craft::t('yii', '{attribute} cannot be blank.');
            } else if ($this->allowLimit && $this->limit && $total > $this->limit) {
                if ($this->limit == 1) {
                    $errors[] = Craft::t('app', 'There can’t be more than one selection.');
                } else {
                    $errors[] = Craft::t('app', 'There can’t be more than {limit} selections.', ['limit' => $this->limit]);
                }
            }
        }

        return $errors;
    }

    /**
     * @inheritdoc
     */
    public function prepareValue($value, $element)
    {
        /** @var Element $element */
        /** @var Element $class */
        $class = static::elementType();
        /** @var ElementQuery $query */
        $query = $class::find()
            ->siteId($this->getTargetSiteId($element));

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
                $query->orderBy('sortOrder');
            }

            if (!$this->allowMultipleSources && $this->source) {
                $source = $class::getSourceByKey($this->source);

                // Does the source specify any criteria attributes?
                if (isset($source['criteria'])) {
                    $query->configure($source['criteria']);
                }
            }
        } else {
            $query->id(false);
        }

        if ($this->allowLimit && $this->limit) {
            $query->limit($this->limit);
        } else {
            $query->limit(null);
        }

        return $query;
    }

    /**
     * @inheritdoc
     */
    public function modifyElementsQuery(ElementQueryInterface $query, $value)
    {
        /** @var ElementQuery $query */
        if ($value == 'not :empty:') {
            $value = ':notempty:';
        }

        if ($value == ':notempty:' || $value == ':empty:') {
            $alias = 'relations_'.$this->handle;
            $operator = ($value == ':notempty:' ? '!=' : '=');
            $paramHandle = ':fieldId'.StringHelper::randomString(8);

            $query->subQuery->andWhere(
                "(select count({$alias}.id) from {{relations}} {$alias} where {$alias}.sourceId = elements.id and {$alias}.fieldId = {$paramHandle}) {$operator} 0",
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
    public function getInputHtml($value, $element)
    {
        /** @var ElementQuery $value */
        $variables = $this->getInputTemplateVariables($value, $element);

        return Craft::$app->getView()->renderTemplate($this->inputTemplate, $variables);
    }

    /**
     * @inheritdoc
     */
    public function getSearchKeywords($value, $element)
    {
        /** @var ElementQuery $value */
        $titles = [];

        foreach ($value->all() as $element) {
            $titles[] = (string)$element;
        }

        return parent::getSearchKeywords($titles, $element);
    }

    /**
     * @inheritdoc
     */
    public function afterElementSave(ElementInterface $element)
    {
        $value = $this->getElementValue($element);

        if ($value instanceof ElementQueryInterface) {
            $value = $value->id;
        }

        if ($value !== null) {
            Craft::$app->getRelations()->saveRelations($this, $element, $value);
        }
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml($value, $element)
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
    public function getTableAttributeHtml($value, $element)
    {
        if ($value instanceof ElementQueryInterface) {
            $element = $value->first();
        } else {
            $element = isset($value[0]) ? $value[0] : null;
        }

        if ($element) {
            return Craft::$app->getView()->renderTemplate('_elements/element', [
                'element' => $element
            ]);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getEagerLoadingMap($sourceElements)
    {
        /** @var Element|null $firstElement */
        $firstElement = isset($sourceElements[0]) ? $sourceElements[0] : null;

        // Get the source element IDs
        $sourceElementIds = [];

        foreach ($sourceElements as $sourceElement) {
            $sourceElementIds[] = $sourceElement->id;
        }

        // Return any relation data on these elements, defined with this field
        $map = (new Query())
            ->select('sourceId as source, targetId as target')
            ->from('{{%relations}}')
            ->where(
                [
                    'and',
                    'fieldId=:fieldId',
                    ['in', 'sourceId', $sourceElementIds],
                    ['or', 'sourceSiteId=:sourceSiteId', 'sourceSiteId is null']
                ],
                [
                    ':fieldId' => $this->id,
                    ':sourceSiteId' => ($firstElement ? $firstElement->siteId : null),
                ])
            ->orderBy('sortOrder')
            ->all();

        // Figure out which target site to use
        $targetSite = $this->getTargetSiteId($firstElement);

        return [
            'elementType' => static::elementType(),
            'map' => $map,
            'criteria' => [
                'siteId' => $targetSite
            ],
        ];
    }

    // Protected Methods
    // =========================================================================

    /**
     * Returns an array of variables that should be passed to the input template.
     *
     * @param ElementQueryInterface|null $selectedElementsQuery
     * @param ElementInterface           $element
     *
     * @return array
     */
    protected function getInputTemplateVariables($selectedElementsQuery, $element)
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

        $selectionCriteria = $this->getInputSelectionCriteria();
        $selectionCriteria['enabledForSite'] = null;
        $selectionCriteria['siteId'] = $this->getTargetSiteId($element);

        return [
            'jsClass' => $this->inputJsClass,
            'elementType' => static::elementType(),
            'id' => Craft::$app->getView()->formatInputId($this->handle),
            'fieldId' => $this->id,
            'storageKey' => 'field.'.$this->id,
            'name' => $this->handle,
            'elements' => $selectedElementsQuery,
            'sources' => $this->getInputSources($element),
            'criteria' => $selectionCriteria,
            'sourceElementId' => (!empty($element->id) ? $element->id : null),
            'limit' => ($this->allowLimit ? $this->limit : null),
            'viewMode' => $this->getViewMode(),
            'selectionLabel' => ($this->selectionLabel ? Craft::t('site', $this->selectionLabel) : static::defaultSelectionLabel()),
        ];
    }

    /**
     * Returns an array of the source keys the field should be able to select elements from.
     *
     * @param ElementInterface|null $element
     *
     * @return array
     */
    protected function getInputSources($element)
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
    protected function getInputSelectionCriteria()
    {
        return [];
    }

    /**
     * Returns the site ID that target elements should have.
     *
     * @param ElementInterface|null $element
     *
     * @return integer
     */
    protected function getTargetSiteId($element)
    {
        /** @var Element|null $element */
        if (Craft::$app->getIsMultiSite()) {
            if ($this->targetSiteId) {
                return $this->targetSiteId;
            }

            if (!empty($element)) {
                return $element->siteId;
            }
        }

        return Craft::$app->getSites()->currentSite->id;
    }

    /**
     * Returns the HTML for the Target Site setting.
     *
     * @return string|null
     */
    protected function getTargetSiteFieldHtml()
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
     * Normalizes the available sources into select input options.
     *
     * @return array
     */
    protected function getSourceOptions()
    {
        $options = [];
        $optionNames = [];

        foreach ($this->getAvailableSources() as $source) {
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
     * Returns the HTML for the View Mode setting.
     *
     * @return string|null
     */
    protected function getViewModeFieldHtml()
    {
        $supportedViewModes = $this->getSupportedViewModes();

        if (!$supportedViewModes || count($supportedViewModes) == 1) {
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
     * Returns the field’s supported view modes.
     *
     * @return array|null
     */
    protected function getSupportedViewModes()
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
    protected function getViewMode()
    {
        $supportedViewModes = $this->getSupportedViewModes();
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
    protected function getAvailableSources()
    {
        return Craft::$app->getElementIndexes()->getSources($this::elementType(), 'modal');
    }
}
