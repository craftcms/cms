<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\base;

use Craft;
use /** @noinspection PhpUndefinedClassInspection */
    craft\app\behaviors\ContentBehavior;
use /** @noinspection PhpUndefinedClassInspection */
    craft\app\behaviors\ContentTrait;
use craft\app\dates\DateTime;
use craft\app\db\Query;
use craft\app\elements\db\ElementQuery;
use craft\app\elements\db\ElementQueryInterface;
use craft\app\events\Event;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\Html;
use craft\app\helpers\Template;
use craft\app\helpers\Url;
use craft\app\i18n\Locale;
use craft\app\models\FieldLayout;
use craft\app\models\Site;
use craft\app\validators\DateTimeValidator;
use craft\app\validators\SiteIdValidator;
use craft\app\web\UploadedFile;
use yii\base\Exception;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\base\UnknownPropertyException;

/**
 * Element is the base class for classes representing elements in terms of objects.
 *
 * @property FieldLayout|null      $fieldLayout         The field layout used by this element
 * @property integer[]             $supportedSiteIds    The site IDs this element is available in
 * @property string|null           $uriFormat           The URI format used to generate this element’s URL
 * @property string|null           $url                 The element’s full URL
 * @property \Twig_Markup|null     $link                An anchor pre-filled with this element’s URL and title
 * @property string|null           $ref                 The reference string to this element
 * @property boolean               $isEditable          Whether the current user can edit the element
 * @property string|null           $cpEditUrl           The element’s CP edit URL
 * @property string|null           $thumbUrl            The URL to the element’s thumbnail, if there is one
 * @property string|null           $iconUrl             The URL to the element’s icon image, if there is one
 * @property string|null           $status              The element’s status
 * @property Element               $next                The next element relative to this one, from a given set of criteria
 * @property Element               $prev                The previous element relative to this one, from a given set of criteria
 * @property Element               $parent              The element’s parent
 * @property integer|null          $structureId         The ID of the structure that the element is associated with, if any
 * @property ElementQueryInterface $ancestors           The element’s ancestors
 * @property ElementQueryInterface $descendants         The element’s descendants
 * @property ElementQueryInterface $children            The element’s children
 * @property ElementQueryInterface $siblings            All of the element’s siblings
 * @property Element               $prevSibling         The element’s previous sibling
 * @property Element               $nextSibling         The element’s next sibling
 * @property boolean               $hasDescendants      Whether the element has descendants
 * @property integer               $totalDescendants    The total number of descendants that the element has
 * @property string                $title               The element’s title
 * @property array                 $contentFromPost     The raw content from the post data, as it was given to [[setFieldValuesFromPost]]
 * @property string|null           $contentPostLocation The location in POST that the content was pulled from
 * @property string                $contentTable        The name of the table this element’s content is stored in
 * @property string                $fieldColumnPrefix   The field column prefix this element’s content uses
 * @property string                $fieldContext        The field context this element’s content uses
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
abstract class Element extends Component implements ElementInterface
{
    // Traits
    // =========================================================================

    use ElementTrait;
    use /** @noinspection PhpUndefinedClassInspection */
        ContentTrait;

    // Constants
    // =========================================================================

    const STATUS_ENABLED = 'enabled';
    const STATUS_DISABLED = 'disabled';
    const STATUS_ARCHIVED = 'archived';

    /**
     * @event Event The event that is triggered before the element is saved
     *
     * You may set [[Event::isValid]] to `false` to prevent the element from getting saved.
     */
    const EVENT_BEFORE_SAVE = 'beforeSave';

    /**
     * @event Event The event that is triggered after the element is saved
     */
    const EVENT_AFTER_SAVE = 'afterSave';

    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function hasContent()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_ENABLED => Craft::t('app', 'Enabled'),
            self::STATUS_DISABLED => Craft::t('app', 'Disabled')
        ];
    }

    /**
     * @inheritdoc
     *
     * @return ElementQueryInterface
     */
    public static function find()
    {
        return new ElementQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public static function findOne($criteria = null)
    {
        return static::findByCondition($criteria, true);
    }

    /**
     * @inheritdoc
     */
    public static function findAll($criteria = null)
    {
        return static::findByCondition($criteria, false);
    }

    /**
     * @inheritdoc
     */
    public static function getSources($context = null)
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function getSourceByKey($key, $context = null)
    {
        $contextKey = ($context ? $context : '*');

        if (!isset(self::$_sourcesByContext[$contextKey])) {
            self::$_sourcesByContext[$contextKey] = static::getSources($context);
        }

        return static::_findSource($key, self::$_sourcesByContext[$contextKey]);
    }

    /**
     * @inheritdoc
     */
    public static function getAvailableActions($source = null)
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function defineSearchableAttributes()
    {
        return [];
    }

    // Element index methods
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public static function getIndexHtml($elementQuery, $disabledElementIds, $viewState, $sourceKey, $context, $includeContainer, $showCheckboxes)
    {
        $variables = [
            'viewMode' => $viewState['mode'],
            'context' => $context,
            'elementType' => new static(),
            'disabledElementIds' => $disabledElementIds,
            'collapsedElementIds' => Craft::$app->getRequest()->getParam('collapsedElementIds'),
            'showCheckboxes' => $showCheckboxes,
        ];

        // Special case for sorting by structure
        if (isset($viewState['order']) && $viewState['order'] == 'structure') {
            $source = static::getSourceByKey($sourceKey, $context);

            if (isset($source['structureId'])) {
                $elementQuery->orderBy('lft asc');
                $variables['structure'] = Craft::$app->getStructures()->getStructureById($source['structureId']);

                // Are they allowed to make changes to this structure?
                if ($context == 'index' && $variables['structure'] && !empty($source['structureEditable'])) {
                    $variables['structureEditable'] = true;

                    // Let StructuresController know that this user can make changes to the structure
                    Craft::$app->getSession()->authorize('editStructure:'.$variables['structure']->id);
                }
            } else {
                unset($viewState['order']);
            }
        } else if (!empty($viewState['order']) && $viewState['order'] == 'score') {
            $elementQuery->orderBy('score');
        } else {
            $sortableAttributes = static::defineSortableAttributes();

            if ($sortableAttributes) {
                $order = (!empty($viewState['order']) && isset($sortableAttributes[$viewState['order']])) ? $viewState['order'] : ArrayHelper::getFirstKey($sortableAttributes);
                $sort = (!empty($viewState['sort']) && in_array($viewState['sort'],
                        ['asc', 'desc'])) ? $viewState['sort'] : 'asc';

                // Combine them, accounting for the possibility that $order could contain multiple values,
                // and be defensive about the possibility that the first value actually has "asc" or "desc"

                // typeId             => typeId [sort]
                // typeId, title      => typeId [sort], title
                // typeId, title desc => typeId [sort], title desc
                // typeId desc        => typeId [sort]

                $elementQuery->orderBy(preg_replace('/^(.*?)(?:\s+(?:asc|desc))?(,.*)?$/i', "$1 {$sort}$2", $order));
            }
        }

        switch ($viewState['mode']) {
            case 'table': {
                // Get the table columns
                $variables['attributes'] = static::getTableAttributesForSource($sourceKey);

                // Give each attribute a chance to modify the criteria
                foreach ($variables['attributes'] as $attribute) {
                    static::prepElementQueryForTableAttribute($elementQuery, $attribute[0]);
                }

                break;
            }
        }

        $variables['elements'] = $elementQuery->all();

        $template = '_elements/'.$viewState['mode'].'view/'.($includeContainer ? 'container' : 'elements');

        return Craft::$app->getView()->renderTemplate($template, $variables);
    }

    /**
     * @inheritdoc
     */
    public static function defineSortableAttributes()
    {
        $tableAttributes = Craft::$app->getElementIndexes()->getAvailableTableAttributes(static::class);
        $sortableAttributes = [];

        foreach ($tableAttributes as $key => $labelInfo) {
            $sortableAttributes[$key] = $labelInfo['label'];
        }

        return $sortableAttributes;
    }

    /**
     * @inheritdoc
     */
    public static function defineAvailableTableAttributes()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultTableAttributes($source = null)
    {
        $availableTableAttributes = static::defineAvailableTableAttributes();

        return array_keys($availableTableAttributes);
    }

    /**
     * @inheritdoc
     */
    public static function getTableAttributeHtml(ElementInterface $element, $attribute)
    {
        /** @var Element $element */
        switch ($attribute) {
            case 'link': {
                $url = $element->getUrl();

                if ($url) {
                    return '<a href="'.$url.'" target="_blank" data-icon="world" title="'.Craft::t('app', 'Visit webpage').'"></a>';
                }

                return '';
            }

            case 'uri': {
                $url = $element->getUrl();

                if ($url) {
                    $value = $element->uri;

                    if ($value == '__home__') {
                        $value = '<span data-icon="home" title="'.Craft::t('app',
                                'Homepage').'"></span>';
                    } else {
                        // Add some <wbr> tags in there so it doesn't all have to be on one line
                        $find = ['/'];
                        $replace = ['/<wbr>'];

                        $wordSeparator = Craft::$app->getConfig()->get('slugWordSeparator');

                        if ($wordSeparator) {
                            $find[] = $wordSeparator;
                            $replace[] = $wordSeparator.'<wbr>';
                        }

                        $value = str_replace($find, $replace, $value);
                    }

                    return '<a href="'.$url.'" target="_blank" class="go" title="'.Craft::t('app', 'Visit webpage').'"><span dir="ltr">'.$value.'</span></a>';
                }

                return '';
            }

            default: {
                // Is this a custom field?
                if (preg_match('/^field:(\d+)$/', $attribute, $matches)) {
                    $fieldId = $matches[1];
                    $field = Craft::$app->getFields()->getFieldById($fieldId);

                    if ($field) {
                        /** @var Field $field */
                        if ($field instanceof PreviewableFieldInterface) {
                            // Was this field value eager-loaded?
                            if ($field instanceof EagerLoadingFieldInterface && $element->hasEagerLoadedElements($field->handle)) {
                                $value = $element->getEagerLoadedElements($field->handle);
                            } else {
                                $value = $element->getFieldValue($field->handle);
                            }

                            return $field->getTableAttributeHtml($value, $element);
                        }
                    }

                    return '';
                }

                $value = $element->$attribute;

                if ($value instanceof DateTime) {
                    $formatter = Craft::$app->getFormatter();

                    return '<span title="'.$formatter->asDatetime($value, Locale::LENGTH_SHORT).'">'.$formatter->asTimestamp($value, Locale::LENGTH_SHORT).'</span>';
                }

                return Html::encode($value);
            }
        }
    }

    /**
     * Returns the attributes that should be shown for the given source.
     *
     * @param string $sourceKey The source key
     *
     * @return array The attributes that should be shown for the given source
     */
    protected static function getTableAttributesForSource($sourceKey)
    {
        $elementType = static::class;

        // Give plugins a chance to customize them
        $pluginAttributes = Craft::$app->getPlugins()->callFirst('getTableAttributesForSource', [
            $elementType,
            $sourceKey
        ], true);

        if ($pluginAttributes !== null) {
            return $pluginAttributes;
        }

        return Craft::$app->getElementIndexes()->getTableAttributes($elementType, $sourceKey);
    }

    // Methods for customizing the content table
    // -----------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public static function getFieldsForElementsQuery(ElementQueryInterface $query)
    {
        $contentService = Craft::$app->getContent();
        $originalFieldContext = $contentService->fieldContext;
        $contentService->fieldContext = 'global';
        $fields = Craft::$app->getFields()->getAllFields();
        $contentService->fieldContext = $originalFieldContext;

        return $fields;
    }

    // Methods for customizing element queries
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public static function getElementQueryStatusCondition(ElementQueryInterface $query, $status)
    {
    }

    /**
     * @inheritdoc
     */
    public static function getEagerLoadingMap($sourceElements, $handle)
    {
        // Eager-loading descendants or direct children?
        if ($handle == 'descendants' || $handle == 'children') {
            // Get the source element IDs
            $sourceElementIds = [];

            foreach ($sourceElements as $sourceElement) {
                $sourceElementIds[] = $sourceElement->id;
            }

            // Get the structure data for these elements
            // @todo: case sql is MySQL-specific
            $selectSql = 'structureId, elementId, lft, rgt';

            if ($handle == 'children') {
                $selectSql .= ', level';
            }

            $structureData = (new Query())
                ->select($selectSql)
                ->from('{{%structureelements}}')
                ->where(['in', 'elementId', $sourceElementIds])
                ->all();

            $conditions = ['or'];
            $params = [];
            $sourceSelectSql = '(CASE';

            foreach ($structureData as $i => $elementStructureData) {
                $thisElementConditions = [
                    'and',
                    'structureId=:structureId'.$i,
                    'lft>:lft'.$i,
                    'rgt<:rgt'.$i
                ];

                if ($handle == 'children') {
                    $thisElementConditions[] = 'level=:level'.$i;
                    $params[':level'.$i] = $elementStructureData['level'] + 1;
                }

                $conditions[] = $thisElementConditions;
                $sourceSelectSql .= " WHEN structureId=:structureId{$i} AND lft>:lft{$i} AND rgt<:rgt{$i} THEN :sourceId{$i}";
                $params[':structureId'.$i] = $elementStructureData['structureId'];
                $params[':lft'.$i] = $elementStructureData['lft'];
                $params[':rgt'.$i] = $elementStructureData['rgt'];
                $params[':sourceId'.$i] = $elementStructureData['elementId'];
            }

            $sourceSelectSql .= ' END) as source';

            // Return any child elements
            $map = (new Query())
                ->select($sourceSelectSql.', elementId as target')
                ->from('structureelements')
                ->where($conditions, $params)
                ->orderBy('structureId, lft')
                ->all();

            return [
                'elementType' => static::class,
                'map' => $map
            ];
        }

        // Is $handle a custom field handle?
        // (Leave it up to the extended class to set the field context, if it shouldn't be 'global')
        $field = Craft::$app->getFields()->getFieldByHandle($handle);

        if ($field) {
            if ($field instanceof EagerLoadingFieldInterface) {
                return $field->getEagerLoadingMap($sourceElements);
            }
        }

        return false;
    }

    /**
     * Preps the element criteria for a given table attribute
     *
     * @param ElementQueryInterface $elementQuery
     * @param string                $attribute
     *
     * @return void
     */
    protected static function prepElementQueryForTableAttribute(ElementQueryInterface $elementQuery, $attribute)
    {
        /** @var ElementQuery $elementQuery */
        // Is this a custom field?
        if (preg_match('/^field:(\d+)$/', $attribute, $matches)) {
            $fieldId = $matches[1];
            $field = Craft::$app->getFields()->getFieldById($fieldId);

            if ($field) {
                /** @var Field $field */
                if ($field instanceof EagerLoadingFieldInterface) {
                    $with = $elementQuery->with ?: [];
                    $with[] = $field->handle;
                    $elementQuery->with = $with;
                }
            }
        }
    }

    // Element methods

    /**
     * @inheritdoc
     */
    public static function getEditorHtml(ElementInterface $element)
    {
        /** @var Element $element */
        $html = '';

        $fieldLayout = $element->getFieldLayout();

        if ($fieldLayout) {
            $originalNamespace = Craft::$app->getView()->getNamespace();
            $namespace = Craft::$app->getView()->namespaceInputName('fields', $originalNamespace);
            Craft::$app->getView()->setNamespace($namespace);

            foreach ($fieldLayout->getFields() as $field) {
                $fieldHtml = Craft::$app->getView()->renderTemplate('_includes/field',
                    [
                        'element' => $element,
                        'field' => $field,
                        'required' => $field->required
                    ]);

                $html .= Craft::$app->getView()->namespaceInputs($fieldHtml, 'fields');
            }

            Craft::$app->getView()->setNamespace($originalNamespace);
        }

        return $html;
    }

    /**
     * @inheritdoc
     */
    public static function saveElement(ElementInterface $element, $params)
    {
        /** @var Element $element */
        return Craft::$app->getElements()->saveElement($element);
    }

    /**
     * @inheritdoc
     */
    public static function getElementRoute(ElementInterface $element)
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function onAfterMoveElementInStructure(ElementInterface $element, $structureId)
    {
    }

    // Properties
    // =========================================================================

    /**
     * @var array
     */
    private static $_sourcesByContext;

    /**
     * @var
     */
    private $_fieldsByHandle;

    /**
     * @var
     */
    private $_contentPostLocation;

    /**
     * @var
     */
    private $_rawPostContent;

    /**
     * @var array Stores a record of the fields that have already prepared their values
     */
    private $_preparedFields;

    /**
     * @var
     */
    private $_nextElement;

    /**
     * @var
     */
    private $_prevElement;

    /**
     * @var integer|boolean The structure ID that the element is associated with
     * @see getStructureId()
     * @see setStructureId()
     */
    private $_structureId;

    /**
     * @var
     */
    private $_parent;

    /**
     * @var
     */
    private $_prevSibling;

    /**
     * @var
     */
    private $_nextSibling;

    /**
     * @var
     */
    private $_eagerLoadedElements;

    // Public Methods
    // =========================================================================

    /**
     * Returns the string representation of the element.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->title;
    }

    /**
     * Checks if a property is set.
     *
     * This method will check if $name is one of the following:
     *
     * - "title"
     * - a magic property supported by [[\yii\base\Component::__isset()]]
     * - a custom field handle
     *
     * @param string $name The property name
     *
     * @return boolean Whether the property is set
     */
    public function __isset($name)
    {
        if ($name == 'title' || $this->hasEagerLoadedElements($name) || parent::__isset($name) || $this->getFieldByHandle($name)) {
            return true;
        }

        return false;
    }

    /**
     * Returns a property value.
     *
     * This method will check if $name is one of the following:
     *
     * - a magic property supported by [[\yii\base\Component::__isset()]]
     * - a custom field handle
     *
     * @param string $name The property name
     *
     * @return mixed The property value
     * @throws UnknownPropertyException if the property is not defined
     * @throws InvalidCallException if the property is write-only.
     */
    public function __get($name)
    {
        if ($name == 'locale') {
            Craft::$app->getDeprecator()->log('Element::locale', 'The “locale” element property has been deprecated. Use “siteId” instead.');

            return $this->getSite()->handle;
        }

        // Is $name a set of eager-loaded elements?
        if ($this->hasEagerLoadedElements($name)) {
            return $this->getEagerLoadedElements($name);
        }

        // Give custom fields priority over other getters so we have a chance to prepare their values
        $field = $this->getFieldByHandle($name);
        if ($field !== null) {
            return $this->getFieldValue($name);
        }

        return parent::__get($name);
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        /** @noinspection PhpUndefinedClassInspection */
        return [
            'customFields' => ContentBehavior::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (!$this->siteId) {
            $this->siteId = Craft::$app->getSites()->getPrimarySite()->id;
        }
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        $names = parent::attributes();

        // Include custom field handles
        /** @noinspection PhpUndefinedClassInspection */
        $class = new \ReflectionClass(ContentBehavior::class);

        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();

            if ($name !== 'owner' && !in_array($name, $names)) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'title' => Craft::t('app', 'Title'),
            'slug' => Craft::t('app', 'Slug'),
            'uri' => Craft::t('app', 'URI'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [
            [['id', 'contentId', 'root', 'lft', 'rgt', 'level'], 'number', 'integerOnly' => true],
            [['siteId'], SiteIdValidator::class],
            [['dateCreated', 'dateUpdated'], DateTimeValidator::class],
            [['title'], 'string', 'max' => 255],
        ];

        // Require the title?
        if ($this::hasTitles()) {
            $rules[] = [['title'], 'required'];
        }

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout()
    {
        return Craft::$app->getFields()->getLayoutByType($this->getType());
    }

    /**
     * @inheritdoc
     */
    public function getSupportedSites()
    {
        if (static::isLocalized()) {
            return Craft::$app->getSites()->getAllSiteIds();
        }

        return [Craft::$app->getSites()->getPrimarySite()->id];
    }

    /**
     * @inheritdoc
     */
    public function getUriFormat()
    {
    }

    /**
     * @inheritdoc
     */
    public function getUrl()
    {
        if ($this->uri !== null) {
            $path = ($this->uri == '__home__') ? '' : $this->uri;
            $url = Url::getSiteUrl($path, null, null, $this->siteId);

            return $url;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getLink()
    {
        $url = $this->getUrl();

        if ($url !== null) {
            $link = '<a href="'.$url.'">'.Html::encode($this->__toString()).'</a>';

            return Template::getRaw($link);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getRef()
    {
    }

    /**
     * @inheritdoc
     */
    public function getIsEditable()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl()
    {
    }

    /**
     * @inheritdoc
     */
    public function getThumbUrl($size = null)
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        if ($this->archived) {
            return self::STATUS_ARCHIVED;
        }

        if (!$this->enabled || !$this->enabledForSite) {
            return self::STATUS_DISABLED;
        }

        return self::STATUS_ENABLED;
    }

    /**
     * @inheritdoc
     */
    public function getNext($criteria = false)
    {
        if ($criteria !== false || !isset($this->_nextElement)) {
            return $this->_getRelativeElement($criteria, 1);
        }

        if ($this->_nextElement !== false) {
            return $this->_nextElement;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getPrev($criteria = false)
    {
        if ($criteria !== false || !isset($this->_prevElement)) {
            return $this->_getRelativeElement($criteria, -1);
        }

        if ($this->_prevElement !== false) {
            return $this->_prevElement;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function setNext($element)
    {
        $this->_nextElement = $element;
    }

    /**
     * @inheritdoc
     */
    public function setPrev($element)
    {
        $this->_prevElement = $element;
    }

    /**
     * @inheritdoc
     */
    public function getParent()
    {
        if ($this->_parent === null) {
            $this->_parent = $this->getAncestors(1)
                ->status(null)
                ->enabledForSite(false)
                ->one();

            if ($this->_parent === null) {
                $this->_parent = false;
            }
        }

        return $this->_parent ?: null;
    }

    /**
     * @inheritdoc
     */
    public function setParent($parent)
    {
        $this->_parent = $parent;

        if ($parent) {
            $this->level = $parent->level + 1;
        } else {
            $this->level = 1;
        }
    }

    /**
     * @inheritdoc
     */
    public function getStructureId()
    {
        if ($this->_structureId === null) {
            $this->setStructureId($this->resolveStructureId());
        }

        if ($this->_structureId !== false) {
            return $this->_structureId;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function setStructureId($structureId)
    {
        if (!empty($structureId)) {
            $this->_structureId = $structureId;
        } else {
            $this->_structureId = false;
        }
    }

    /**
     * @inheritdoc
     *
     * @return ElementQueryInterface
     */
    public function getAncestors($dist = null)
    {
        return static::find()
            ->structureId($this->getStructureId())
            ->ancestorOf($this)
            ->siteId($this->siteId)
            ->ancestorDist($dist);
    }

    /**
     * @inheritdoc
     *
     * @return ElementQueryInterface
     */
    public function getDescendants($dist = null)
    {
        // Eager-loaded?
        if ($this->hasEagerLoadedElements('descendants')) {
            return $this->getEagerLoadedElements('descendants');
        }

        return static::find()
            ->structureId($this->getStructureId())
            ->descendantOf($this)
            ->siteId($this->siteId)
            ->descendantDist($dist);
    }

    /**
     * @inheritdoc
     *
     * @return ElementQueryInterface
     */
    public function getChildren()
    {
        // Eager-loaded?
        if ($this->hasEagerLoadedElements('children')) {
            return $this->getEagerLoadedElements('children');
        }

        return $this->getDescendants(1);
    }

    /**
     * @inheritdoc
     *
     * @return ElementQueryInterface
     */
    public function getSiblings()
    {
        return static::find()
            ->structureId($this->getStructureId())
            ->siblingOf($this)
            ->siteId($this->siteId);
    }

    /**
     * @inheritdoc
     *
     * @return ElementQueryInterface
     */
    public function getPrevSibling()
    {
        if ($this->_prevSibling === null) {
            /** @var ElementQuery $query */
            $query = $this->_prevSibling = static::find();
            $query->structureId = $this->getStructureId();
            $query->prevSiblingOf = $this;
            $query->siteId = $this->siteId;
            $query->status = null;
            $query->enabledForSite = false;
            $this->_prevSibling = $query->one();

            if ($this->_prevSibling === null) {
                $this->_prevSibling = false;
            }
        }

        return $this->_prevSibling ?: null;
    }

    /**
     * @inheritdoc
     *
     * @return ElementQueryInterface
     */
    public function getNextSibling()
    {
        if ($this->_nextSibling === null) {
            /** @var ElementQuery $query */
            $query = $this->_nextSibling = static::find();
            $query->structureId = $this->getStructureId();
            $query->nextSiblingOf = $this;
            $query->siteId = $this->siteId;
            $query->status = null;
            $query->enabledForSite = false;
            $this->_nextSibling = $query->one();

            if ($this->_nextSibling === null) {
                $this->_nextSibling = false;
            }
        }

        return $this->_nextSibling ?: null;
    }

    /**
     * @inheritdoc
     */
    public function getHasDescendants()
    {
        return ($this->lft && $this->rgt && $this->rgt > $this->lft + 1);
    }

    /**
     * @inheritdoc
     */
    public function getTotalDescendants()
    {
        if ($this->getHasDescendants()) {
            return ($this->rgt - $this->lft - 1) / 2;
        }

        return 0;
    }

    /**
     * @inheritdoc
     */
    public function isAncestorOf(ElementInterface $element)
    {
        /** @var Element $element */
        return ($this->root == $element->root && $this->lft < $element->lft && $this->rgt > $element->rgt);
    }

    /**
     * @inheritdoc
     */
    public function isDescendantOf(ElementInterface $element)
    {
        /** @var Element $element */
        return ($this->root == $element->root && $this->lft > $element->lft && $this->rgt < $element->rgt);
    }

    /**
     * @inheritdoc
     */
    public function isParentOf(ElementInterface $element)
    {
        /** @var Element $element */
        return ($this->root == $element->root && $this->level == $element->level - 1 && $this->isAncestorOf($element));
    }

    /**
     * @inheritdoc
     */
    public function isChildOf(ElementInterface $element)
    {
        /** @var Element $element */
        return ($this->root == $element->root && $this->level == $element->level + 1 && $this->isDescendantOf($element));
    }

    /**
     * @inheritdoc
     */
    public function isSiblingOf(ElementInterface $element)
    {
        /** @var Element $element */
        if ($this->root == $element->root && $this->level && $this->level == $element->level) {
            if ($this->level == 1 || $this->isPrevSiblingOf($element) || $this->isNextSiblingOf($element)) {
                return true;
            }

            $parent = $this->getParent();

            if ($parent) {
                return $element->isDescendantOf($parent);
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function isPrevSiblingOf(ElementInterface $element)
    {
        /** @var Element $element */
        return ($this->root == $element->root && $this->level == $element->level && $this->rgt == $element->lft - 1);
    }

    /**
     * @inheritdoc
     */
    public function isNextSiblingOf(ElementInterface $element)
    {
        /** @var Element $element */
        return ($this->root == $element->root && $this->level == $element->level && $this->lft == $element->rgt + 1);
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        if ($offset == 'title' || $this->hasEagerLoadedElements($offset) || parent::offsetExists($offset) || $this->getFieldByHandle($offset)) {
            return true;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function getFieldValues($fieldHandles = null, $except = [])
    {
        $values = [];

        foreach ($this->getFields() as $field) {
            if ($fieldHandles === null || in_array($field->handle, $fieldHandles)) {
                $values[$field->handle] = $this->getFieldValue($field->handle);
            }
        }

        foreach ($except as $handle) {
            unset($values[$handle]);
        }

        return $values;
    }

    /**
     * @inheritdoc
     */
    public function setFieldValues($values)
    {
        foreach ($values as $fieldHandle => $value) {
            $this->setFieldValue($fieldHandle, $value);
        }
    }

    /**
     * @inheritdoc
     */
    public function getFieldValue($fieldHandle)
    {
        // Is this the first time this field value has been accessed?
        if (!isset($this->_preparedFields[$fieldHandle])) {
            $this->prepareFieldValue($fieldHandle);
        }

        $behavior = $this->getBehavior('customFields');

        return $behavior->$fieldHandle;
    }

    /**
     * @inheritdoc
     */
    public function setFieldValue($fieldHandle, $value)
    {
        $behavior = $this->getBehavior('customFields');
        $behavior->$fieldHandle = $value;
    }

    /**
     * @inheritdoc
     */
    public function setFieldValuesFromPost($values)
    {
        if (is_string($values)) {
            // Keep track of where the post data is coming from, in case any field types need to know where to
            // look in $_FILES
            $this->setContentPostLocation($values);
            $values = Craft::$app->getRequest()->getBodyParam($values, []);
        }

        foreach ($this->getFields() as $field) {
            // Do we have any post data for this field?
            if (isset($values[$field->handle])) {
                $value = $values[$field->handle];
            } else if (!empty($this->_contentPostLocation) && UploadedFile::getInstancesByName($this->_contentPostLocation.'.'.$field->handle)) {
                // A file was uploaded for this field
                $value = null;
            } else {
                continue;
            }
            $this->setFieldValue($field->handle, $value);
            $this->setRawPostValueForField($field->handle, $value);
        }
    }

    /**
     * Sets a field’s raw post content.
     *
     * @param string       $handle The field handle.
     * @param string|array The     posted field value.
     */
    public function setRawPostValueForField($handle, $value)
    {
        $this->_rawPostContent[$handle] = $value;
    }

    /**
     * @inheritdoc
     */
    public function getContentFromPost()
    {
        if (isset($this->_rawPostContent)) {
            return $this->_rawPostContent;
        }

        return [];
    }

    /**
     * @inheritdoc
     */
    public function getContentPostLocation()
    {
        return $this->_contentPostLocation;
    }

    /**
     * @inheritdoc
     */
    public function setContentPostLocation($contentPostLocation)
    {
        $this->_contentPostLocation = $contentPostLocation;
    }

    /**
     * @inheritdoc
     */
    public function getContentTable()
    {
        return Craft::$app->getContent()->contentTable;
    }

    /**
     * @inheritdoc
     */
    public function getFieldColumnPrefix()
    {
        return Craft::$app->getContent()->fieldColumnPrefix;
    }

    /**
     * @inheritdoc
     */
    public function getFieldContext()
    {
        return Craft::$app->getContent()->fieldContext;
    }

    /**
     * Returns whether elements have been eager-loaded with a given handle.
     *
     * @param string $handle The handle of the eager-loaded elements
     *
     * @return boolean Whether elements have been eager-loaded with the given handle
     */
    public function hasEagerLoadedElements($handle)
    {
        return isset($this->_eagerLoadedElements[$handle]);
    }

    /**
     * Returns some eager-loaded elements on a given handle.
     *
     * @param string $handle The handle of the eager-loaded elements
     *
     * @return ElementInterface[]|null The eager-loaded elements, or null
     */
    public function getEagerLoadedElements($handle)
    {
        if (isset($this->_eagerLoadedElements[$handle])) {
            return $this->_eagerLoadedElements[$handle];
        }

        return null;
    }

    /**
     * Sets some eager-loaded elements on a given handle.
     *
     * @param string             $handle   The handle to load the elements with in the future
     * @param ElementInterface[] $elements The eager-loaded elements
     *
     * @return void
     */
    public function setEagerLoadedElements($handle, $elements)
    {
        $this->_eagerLoadedElements[$handle] = $elements;
    }

    /**
     * @inheritdoc
     */
    public function getHasFreshContent()
    {
        return (!$this->contentId && !$this->hasErrors());
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function beforeSave()
    {
        // Tell the fields about it
        foreach ($this->getFields() as $field) {
            $field->beforeElementSave($this);
        }

        // Trigger a 'beforeSave' event
        $event = new Event();
        $this->trigger(self::EVENT_BEFORE_SAVE, $event);

        return $event->isValid;
    }

    /**
     * @inheritdoc
     */
    public function afterSave()
    {
        // Tell the fields about it
        foreach ($this->getFields() as $field) {
            $field->afterElementSave($this);
        }

        // Trigger an 'afterSave' event
        $this->trigger(self::EVENT_AFTER_SAVE, new Event());
    }

    // Protected Methods
    // =========================================================================

    /**
     * Prepares a field’s value for use.
     *
     * @param string $fieldHandle The field handle
     *
     * @return void
     * @throws Exception if there is no field with the handle $fieldValue
     */
    protected function prepareFieldValue($fieldHandle)
    {
        $field = $this->getFieldByHandle($fieldHandle);

        if (!$field) {
            throw new Exception('Invalid field handle: '.$fieldHandle);
        }

        $behavior = $this->getBehavior('customFields');
        $behavior->$fieldHandle = $field->prepareValue($behavior->$fieldHandle, $this);
        $this->_preparedFields[$fieldHandle] = true;
    }

    /**
     * Finds Element instance(s) by the given condition.
     *
     * This method is internally called by [[findOne()]] and [[findAll()]].
     *
     * @param mixed   $criteria Refer to [[findOne()]] and [[findAll()]] for the explanation of this parameter
     * @param boolean $one      Whether this method is called by [[findOne()]] or [[findAll()]]
     *
     * @return $this|$this[]
     */
    protected static function findByCondition($criteria, $one)
    {
        if ($criteria !== null && !ArrayHelper::isAssociative($criteria)) {
            $criteria = ['id' => $criteria];
        }

        /** @var ElementQueryInterface $query */
        $query = static::find()->configure($criteria);

        if ($one) {
            /** @var Element $result */
            $result = $query->one();
        } else {
            /** @var Element[] $result */
            $result = $query->all();
        }

        return $result;
    }

    /**
     * Returns the field with a given handle.
     *
     * @param string $handle
     *
     * @return Field|null
     */
    protected function getFieldByHandle($handle)
    {
        if (!isset($this->_fieldsByHandle) || !array_key_exists($handle,
                $this->_fieldsByHandle)
        ) {
            $contentService = Craft::$app->getContent();

            $originalFieldContext = $contentService->fieldContext;
            $contentService->fieldContext = $this->getFieldContext();

            $this->_fieldsByHandle[$handle] = Craft::$app->getFields()->getFieldByHandle($handle);

            $contentService->fieldContext = $originalFieldContext;
        }

        return $this->_fieldsByHandle[$handle];
    }

    /**
     * Returns each of this element’s fields.
     *
     * @return Field[] This element’s fields
     */
    protected function getFields()
    {
        $fieldLayout = $this->getFieldLayout();

        if ($fieldLayout) {
            return $fieldLayout->getFields();
        }

        return [];
    }

    /**
     * Returns the site the element is associated with.
     *
     * @return Site
     * @throws InvalidConfigException if [[siteId]] is invalid
     */
    public function getSite()
    {
        if ($this->siteId) {
            $site = Craft::$app->getSites()->getSiteById($this->siteId);
        }

        if (empty($site)) {
            throw new InvalidConfigException('Invalid site ID: '.$this->siteId);
        }

        return $site;
    }

    /**
     * Returns the ID of the structure that the element is inherently associated with, if any.
     *
     * @return integer|null
     * @see getStructureId()
     */
    protected function resolveStructureId()
    {
        return null;
    }

    // Private Methods
    // =========================================================================

    /**
     * Finds a source by its key, even if it's nested.
     *
     * @param array  $sources
     * @param string $key
     *
     * @return array|null
     */
    private static function _findSource($key, $sources)
    {
        if (isset($sources[$key])) {
            return $sources[$key];
        }

        // Look through any nested sources
        foreach ($sources as $source) {
            if (!empty($source['nested']) && ($nestedSource = static::_findSource($key, $source['nested']))) {
                return $nestedSource;
            }
        }

        return null;
    }

    /**
     * Returns an element right before/after this one, from a given set of criteria.
     *
     * @param mixed   $criteria
     * @param integer $dir
     *
     * @return ElementInterface|null
     */
    private function _getRelativeElement($criteria, $dir)
    {
        if ($this->id) {
            if ($criteria instanceof ElementQueryInterface) {
                $query = $criteria;
            } else {
                $query = static::find()
                    ->siteId($this->siteId)
                    ->configure($criteria);
            }

            /** @var ElementQuery $query */
            $elementIds = $query->ids();
            $key = array_search($this->id, $elementIds);

            if ($key !== false && isset($elementIds[$key + $dir])) {
                return static::find()
                    ->id($elementIds[$key + $dir])
                    ->siteId($query->siteId)
                    ->one();
            }
        }

        return null;
    }
}
