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
use craft\app\events\ElementStructureEvent;
use craft\app\events\Event;
use craft\app\events\ModelEvent;
use craft\app\events\RegisterElementActionsEvent;
use craft\app\events\RegisterElementSortableAttributesEvent;
use craft\app\events\RegisterElementSourcesEvent;
use craft\app\events\RegisterElementTableAttributesEvent;
use craft\app\events\SetElementRouteEvent;
use craft\app\events\SetElementTableAttributeHtmlEvent;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\ElementHelper;
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
use yii\validators\Validator;

/**
 * Element is the base class for classes representing elements in terms of objects.
 *
 * @property FieldLayout|null      $fieldLayout           The field layout used by this element
 * @property array                 $htmlAttributes        Any attributes that should be included in the element’s DOM representation in the Control Panel
 * @property integer[]             $supportedSiteIds      The site IDs this element is available in
 * @property string|null           $uriFormat             The URI format used to generate this element’s URL
 * @property string|null           $url                   The element’s full URL
 * @property \Twig_Markup|null     $link                  An anchor pre-filled with this element’s URL and title
 * @property string|null           $ref                   The reference string to this element
 * @property string                $indexHtml             The element index HTML
 * @property boolean               $isEditable            Whether the current user can edit the element
 * @property string|null           $cpEditUrl             The element’s CP edit URL
 * @property string|null           $thumbUrl              The URL to the element’s thumbnail, if there is one
 * @property string|null           $iconUrl               The URL to the element’s icon image, if there is one
 * @property string|null           $status                The element’s status
 * @property Element               $next                  The next element relative to this one, from a given set of criteria
 * @property Element               $prev                  The previous element relative to this one, from a given set of criteria
 * @property Element               $parent                The element’s parent
 * @property mixed                 $route                 The route that should be used when the element’s URI is requested
 * @property integer|null          $structureId           The ID of the structure that the element is associated with, if any
 * @property ElementQueryInterface $ancestors             The element’s ancestors
 * @property ElementQueryInterface $descendants           The element’s descendants
 * @property ElementQueryInterface $children              The element’s children
 * @property ElementQueryInterface $siblings              All of the element’s siblings
 * @property Element               $prevSibling           The element’s previous sibling
 * @property Element               $nextSibling           The element’s next sibling
 * @property boolean               $hasDescendants        Whether the element has descendants
 * @property integer               $totalDescendants      The total number of descendants that the element has
 * @property string                $title                 The element’s title
 * @property string|null           $serializedFieldValues Array of the element’s serialized custom field values, indexed by their handles
 * @property array                 $fieldParamNamespace   The namespace used by custom field params on the request
 * @property string                $contentTable          The name of the table this element’s content is stored in
 * @property string                $fieldColumnPrefix     The field column prefix this element’s content uses
 * @property string                $fieldContext          The field context this element’s content uses
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
     * @event RegisterElementSourcesEvent The event that is triggered when registering the available sources for the element type.
     */
    const EVENT_REGISTER_SOURCES = 'registerSources';

    /**
     * @event RegisterElementActionsEvent The event that is triggered when registering the available actions for the element type.
     */
    const EVENT_REGISTER_ACTIONS = 'registerActions';

    /**
     * @event RegisterElementSortableAttributesEvent The event that is triggered when registering the sortable attributes for the element type.
     */
    const EVENT_REGISTER_SORTABLE_ATTRIBUTES = 'registerSortableAttributes';

    /**
     * @event RegisterElementTableAttributesEvent The event that is triggered when registering the table attributes for the element type.
     */
    const EVENT_REGISTER_TABLE_ATTRIBUTES = 'registerTableAttributes';

    /**
     * @event SetElementTableAttributeHtmlEvent The event that is triggered when defining the HTML to represent a table attribute.
     */
    const EVENT_SET_TABLE_ATTRIBUTE_HTML = 'setTableAttributeHtml';

    /**
     * @event SetElementRouteEvent The event that is triggered when defining the route that should be used when this element’s URL is requested
     */
    const EVENT_SET_ROUTE = 'setRoute';

    /**
     * @event ModelEvent The event that is triggered before the element is saved
     *
     * You may set [[ModelEvent::isValid]] to `false` to prevent the element from getting saved.
     */
    const EVENT_BEFORE_SAVE = 'beforeSave';

    /**
     * @event ModelEvent The event that is triggered after the element is saved
     */
    const EVENT_AFTER_SAVE = 'afterSave';

    /**
     * @event ModelEvent The event that is triggered before the element is deleted
     *
     * You may set [[ModelEvent::isValid]] to `false` to prevent the element from getting deleted.
     */
    const EVENT_BEFORE_DELETE = 'beforeDelete';

    /**
     * @event \yii\base\Event The event that is triggered after the element is deleted
     */
    const EVENT_AFTER_DELETE = 'afterDelete';

    /**
     * @event ElementStructureEvent The event that is triggered before the element is moved in a structure.
     *
     * You may set [[ElementStructureEvent::isValid]] to `false` to prevent the element from getting moved.
     */
    const EVENT_BEFORE_MOVE_IN_STRUCTURE = 'beforeMoveInStructure';

    /**
     * @event ElementStructureEvent The event that is triggered after the element is moved in a structure.
     */
    const EVENT_AFTER_MOVE_IN_STRUCTURE = 'afterMoveInStructure';

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
    public static function statuses()
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
    public static function sources($context = null)
    {
        $sources = static::defineSources($context);

        // Give plugins a chance to modify them
        $event = new RegisterElementSourcesEvent([
            'context' => $context,
            'sources' => $sources
        ]);
        Event::trigger(static::class, self::EVENT_REGISTER_SOURCES, $event);

        return $event->sources;
    }

    /**
     * @inheritdoc
     */
    public static function source($key, $context = null)
    {
        $contextKey = ($context ? $context : '*');

        if (!isset(self::$_sourcesByContext[$contextKey])) {
            self::$_sourcesByContext[$contextKey] = static::sources($context);
        }

        return static::_findSource($key, self::$_sourcesByContext[$contextKey]);
    }

    /**
     * @inheritdoc
     */
    public static function actions($source = null)
    {
        $actions = static::defineActions($source);

        // Give plugins a chance to modify them
        $event = new RegisterElementActionsEvent([
            'source' => $source,
            'actions' => $actions
        ]);
        Event::trigger(static::class, self::EVENT_REGISTER_ACTIONS, $event);

        return $event->actions;
    }

    /**
     * @inheritdoc
     */
    public static function searchableAttributes()
    {
        return [];
    }

    /**
     * Defines the sources that elements of this type may belong to.
     *
     * @param string|null $context The context ('index' or 'modal').
     *
     * @return array The sources.
     * @see sources()
     */
    protected static function defineSources($context = null)
    {
        return [];
    }

    /**
     * Defines the available element actions for a given source (if one is provided).
     *
     * @param string|null $source The selected source’s key, if any.
     *
     * @return array|null The available element actions.
     * @see actions()
     */
    protected static function defineActions($source = null)
    {
        return [];
    }

    // Element index methods
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public static function indexHtml($elementQuery, $disabledElementIds, $viewState, $sourceKey, $context, $includeContainer, $showCheckboxes)
    {
        $variables = [
            'viewMode' => $viewState['mode'],
            'context' => $context,
            'disabledElementIds' => $disabledElementIds,
            'collapsedElementIds' => Craft::$app->getRequest()->getParam('collapsedElementIds'),
            'showCheckboxes' => $showCheckboxes,
        ];

        // Special case for sorting by structure
        if (isset($viewState['order']) && $viewState['order'] == 'structure') {
            $source = static::source($sourceKey, $context);

            if (isset($source['structureId'])) {
                $elementQuery->orderBy(['lft' => SORT_ASC]);
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
            $sortableAttributes = static::sortableAttributes();

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
    public static function sortableAttributes()
    {
        $sortableAttributes = static::defineSortableAttributes();

        // Give plugins a chance to modify them
        $event = new RegisterElementSortableAttributesEvent([
            'sortableAttributes' => $sortableAttributes
        ]);
        Event::trigger(static::class, self::EVENT_REGISTER_SORTABLE_ATTRIBUTES, $event);

        return $event->sortableAttributes;
    }

    /**
     * @inheritdoc
     */
    public static function tableAttributes()
    {
        $tableAttributes = static::defineTableAttributes();

        // Give plugins a chance to modify them
        $event = new RegisterElementTableAttributesEvent([
            'tableAttributes' => $tableAttributes
        ]);
        Event::trigger(static::class, self::EVENT_REGISTER_TABLE_ATTRIBUTES, $event);

        return $event->tableAttributes;
    }

    /**
     * @inheritdoc
     */
    public static function defaultTableAttributes($source = null)
    {
        $availableTableAttributes = static::tableAttributes();

        return array_keys($availableTableAttributes);
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
        return Craft::$app->getElementIndexes()->getTableAttributes(static::class, $sourceKey);
    }

    /**
     * Defines the attributes that elements can be sorted by.
     *
     * @return string[] The attributes that elements can be sorted by
     * @see sortableAttributes()
     */
    protected static function defineSortableAttributes()
    {
        $tableAttributes = Craft::$app->getElementIndexes()->getAvailableTableAttributes(static::class);
        $sortableAttributes = [];

        foreach ($tableAttributes as $key => $labelInfo) {
            $sortableAttributes[$key] = $labelInfo['label'];
        }

        return $sortableAttributes;
    }

    /**
     * Defines all of the available columns that can be shown in table views.
     *
     * @return array The table attributes.
     * @see tableAttributes()
     */
    protected static function defineTableAttributes()
    {
        return [];
    }

    // Methods for customizing element queries
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public static function eagerLoadingMap($sourceElements, $handle)
    {
        // Eager-loading descendants or direct children?
        if ($handle == 'descendants' || $handle == 'children') {
            // Get the source element IDs
            $sourceElementIds = [];

            foreach ($sourceElements as $sourceElement) {
                $sourceElementIds[] = $sourceElement->id;
            }

            // Get the structure data for these elements
            $selectColumns = ['structureId', 'elementId', 'lft', 'rgt'];

            if ($handle == 'children') {
                $selectColumns[] = 'level';
            }

            $structureData = (new Query())
                ->select($selectColumns)
                ->from(['{{%structureelements}}'])
                ->where(['elementId' => $sourceElementIds])
                ->all();

            $db = Craft::$app->getDb();
            $qb = $db->getQueryBuilder();
            $query = new Query();
            $sourceSelectSql = '(CASE';
            $condition = ['or'];

            foreach ($structureData as $i => $elementStructureData) {
                $thisElementCondition = [
                    'and',
                    ['structureId' => $elementStructureData['structureId']],
                    ['>', 'lft', $elementStructureData['lft']],
                    ['<', 'rgt', $elementStructureData['rgt']],
                ];

                if ($handle == 'children') {
                    $thisElementCondition[] = ['level' => $elementStructureData['level'] + 1];
                }

                $condition[] = $thisElementCondition;
                $sourceSelectSql .= ' WHEN '.
                    $qb->buildCondition(
                        [
                            'and',
                            ['structureId' => $elementStructureData['structureId']],
                            ['>', 'lft', $elementStructureData['lft']],
                            ['<', 'rgt', $elementStructureData['rgt']]
                        ],
                        $query->params).
                    " THEN :sourceId{$i}";
                $query->params[':sourceId'.$i] = $elementStructureData['elementId'];
            }

            $sourceSelectSql .= ' END) as source';

            // Return any child elements
            $map = $query
                ->select([$sourceSelectSql, 'elementId as target'])
                ->from(['{{%structureelements}}'])
                ->where($condition)
                ->orderBy(['structureId' => SORT_ASC, 'lft' => SORT_ASC])
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

    // Properties
    // =========================================================================

    /**
     * @var boolean|null Whether custom fields rules should be included when validating the element.
     *
     * Any value besides `true` or `false` will be treated as "auto", meaning that custom fields will only be validated if the element is enabled.
     */
    public $validateCustomFields;

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
    private $_fieldParamNamePrefix;

    /**
     * @var array Record of the fields whose values have already been normalized
     */
    private $_normalizedFieldValues;

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
            [
                ['id', 'contentId', 'root', 'lft', 'rgt', 'level'],
                'number',
                'integerOnly' => true,
                'on' => self::SCENARIO_DEFAULT
            ],
            [['siteId'], SiteIdValidator::class],
            [['dateCreated', 'dateUpdated'], DateTimeValidator::class],
            [['title'], 'string', 'max' => 255],
        ];

        // Require the title?
        if ($this::hasTitles()) {
            $rules[] = [['title'], 'required'];
        }

        // Are we validating custom fields?
        if ($this->validateCustomFields() && ($fieldLayout = $this->getFieldLayout())) {
            foreach ($fieldLayout->getFields() as $field) {
                /** @var Field $field */
                $fieldRules = $field->getElementValidationRules();

                foreach ($fieldRules as $rule) {
                    if ($rule instanceof Validator) {
                        $rules[] = $rule;
                    } else {
                        if (is_string($rule)) {
                            // "Validator" syntax
                            $rule = [$field->handle, $rule];
                        }

                        if (is_array($rule) && isset($rule[0])) {
                            if (!isset($rule[1])) {
                                // ["Validator"] syntax
                                array_unshift($rule, $field->handle);
                            }

                            if ($rule[1] instanceof \Closure || $field->hasMethod($rule[1])) {
                                // InlineValidator assumes that the closure is on the model being validated
                                // so it won’t pass a reference to the element
                                $rule = [
                                    $rule[0],
                                    'validateCustomFieldAttribute',
                                    'params' => [
                                        $field,
                                        $rule[1],
                                        (isset($rule['params']) ? $rule['params'] : null),
                                    ]
                                ];
                            }

                            $rules[] = $rule;
                        } else {
                            throw new InvalidConfigException('Invalid validation rule for custom field "'.$field->handle.'".');
                        }
                    }
                }
            }
        }

        return $rules;
    }

    /**
     * Calls a custom validation function on a custom field.
     *
     * This will be called by [[yii\validators\InlineValidator]] if a custom field specified
     * a closure or the name of a class-level method as the validation type.
     *
     * @param string     $attribute The field handle
     * @param array|null $params
     *
     * @return void
     */
    public function validateCustomFieldAttribute($attribute, $params)
    {
        /** @var Field $field */
        /** @var array|null $params */
        list($field, $method, $fieldParams) = $params;

        if (is_string($method)) {
            $method = [$field, $method];
        }

        call_user_func($method, $this, $fieldParams);
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
        return Craft::$app->getFields()->getLayoutByType(static::class);
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
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getRoute()
    {
        // Give plugins a chance to set this
        $event = new SetElementRouteEvent();
        $this->trigger(self::EVENT_SET_ROUTE, $event);

        if ($event->route !== null) {
            return $event->route;
        }

        return $this->route();
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
    public function getFieldValues($fieldHandles = null)
    {
        $values = [];

        foreach ($this->getFields() as $field) {
            if ($fieldHandles === null || in_array($field->handle, $fieldHandles)) {
                $values[$field->handle] = $this->getFieldValue($field->handle);
            }
        }

        return $values;
    }

    /**
     * @inheritdoc
     */
    public function getSerializedFieldValues($fieldHandles = null)
    {
        $serializedValues = [];

        foreach ($this->getFields() as $field) {
            if ($fieldHandles === null || in_array($field->handle, $fieldHandles)) {
                $value = $this->getFieldValue($field->handle);
                $serializedValues[$field->handle] = $field->serializeValue($value, $this);
            }
        }

        return $serializedValues;
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
        if (!isset($this->_normalizedFieldValues[$fieldHandle])) {
            $this->normalizeFieldValue($fieldHandle);
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

        // Don't assume that $value has been normalized
        unset($this->_normalizedFieldValues[$fieldHandle]);
    }

    /**
     * @inheritdoc
     */
    public function setFieldValuesFromRequest($paramNamespace = '')
    {
        $this->setFieldParamNamespace($paramNamespace);
        $values = Craft::$app->getRequest()->getBodyParam($paramNamespace, []);

        foreach ($this->getFields() as $field) {
            // Do we have any post data for this field?
            if (isset($values[$field->handle])) {
                $value = $values[$field->handle];
            } else if (!empty($this->_fieldParamNamePrefix) && UploadedFile::getInstancesByName($this->_fieldParamNamePrefix.'.'.$field->handle)) {
                // A file was uploaded for this field
                $value = null;
            } else {
                continue;
            }

            $this->setFieldValue($field->handle, $value);
        }
    }

    /**
     * @inheritdoc
     */
    public function getFieldParamNamespace()
    {
        return $this->_fieldParamNamePrefix;
    }

    /**
     * @inheritdoc
     */
    public function setFieldParamNamespace($namespace)
    {
        $this->_fieldParamNamePrefix = $namespace;
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

            ElementHelper::setNextPrevOnElements($this->_eagerLoadedElements[$handle]);

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

    // Indexes, etc.
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function getHtmlAttributes($context)
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getTableAttributeHtml($attribute)
    {
        // Give plugins a chance to set this
        $event = new SetElementTableAttributeHtmlEvent([
            'attribute' => $attribute
        ]);
        $this->trigger(self::EVENT_SET_TABLE_ATTRIBUTE_HTML, $event);

        if ($event->html !== null) {
            return $event->html;
        }

        return $this->tableAttributeHtml($attribute);
    }

    /**
     * @inheritdoc
     */
    public function getEditorHtml()
    {
        $html = '';

        $fieldLayout = $this->getFieldLayout();

        if ($fieldLayout) {
            $originalNamespace = Craft::$app->getView()->getNamespace();
            $namespace = Craft::$app->getView()->namespaceInputName('fields', $originalNamespace);
            Craft::$app->getView()->setNamespace($namespace);

            foreach ($fieldLayout->getFields() as $field) {
                $fieldHtml = Craft::$app->getView()->renderTemplate('_includes/field', [
                    'element' => $this,
                    'field' => $field,
                    'required' => $field->required
                ]);

                $html .= Craft::$app->getView()->namespaceInputs($fieldHtml, 'fields');
            }

            Craft::$app->getView()->setNamespace($originalNamespace);
        }

        return $html;
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function beforeSave($isNew)
    {
        // Tell the fields about it
        foreach ($this->getFields() as $field) {
            if (!$field->beforeElementSave($this, $isNew)) {
                return false;
            }
        }

        // Trigger a 'beforeSave' event
        $event = new ModelEvent([
            'isNew' => $isNew,
        ]);
        $this->trigger(self::EVENT_BEFORE_SAVE, $event);

        return $event->isValid;
    }

    /**
     * @inheritdoc
     */
    public function afterSave($isNew)
    {
        // Tell the fields about it
        foreach ($this->getFields() as $field) {
            $field->afterElementSave($this, $isNew);
        }

        // Trigger an 'afterSave' event
        $this->trigger(self::EVENT_AFTER_SAVE, new ModelEvent([
            'isNew' => $isNew,
        ]));
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete()
    {
        // Tell the fields about it
        foreach ($this->getFields() as $field) {
            if (!$field->beforeElementDelete($this)) {
                return false;
            }
        }

        // Trigger a 'beforeDelete' event
        $event = new ModelEvent();
        $this->trigger(self::EVENT_BEFORE_DELETE, $event);

        return $event->isValid;
    }

    /**
     * @inheritdoc
     */
    public function afterDelete()
    {
        // Tell the fields about it
        foreach ($this->getFields() as $field) {
            $field->afterElementDelete($this);
        }

        // Trigger an 'afterDelete' event
        $this->trigger(self::EVENT_AFTER_DELETE);
    }

    /**
     * @inheritdoc
     */
    public function beforeMoveInStructure($structureId)
    {
        // Trigger a 'beforeMoveInStructure' event
        $event = new ElementStructureEvent([
            'structureId' => $structureId,
        ]);
        $this->trigger(self::EVENT_BEFORE_MOVE_IN_STRUCTURE, $event);

        return $event->isValid;
    }

    /**
     * @inheritdoc
     */
    public function afterMoveInStructure($structureId)
    {
        // Trigger an 'afterMoveInStructure' event
        $this->trigger(self::EVENT_AFTER_MOVE_IN_STRUCTURE, new ElementStructureEvent([
            'structureId' => $structureId,
        ]));
    }

    // Protected Methods
    // =========================================================================

    /**
     * Returns whether custom fields should be validated.
     *
     * @return boolean
     */
    protected function validateCustomFields()
    {
        if (!static::hasContent()) {
            return false;
        }

        if ($this->validateCustomFields === true) {
            return true;
        }

        if ($this->validateCustomFields !== false && $this->enabled && $this->enabledForSite) {
            return true;
        }

        return false;
    }

    /**
     * Normalizes a field’s value.
     *
     * @param string $fieldHandle The field handle
     *
     * @return void
     * @throws Exception if there is no field with the handle $fieldValue
     */
    protected function normalizeFieldValue($fieldHandle)
    {
        $field = $this->getFieldByHandle($fieldHandle);

        if (!$field) {
            throw new Exception('Invalid field handle: '.$fieldHandle);
        }

        $behavior = $this->getBehavior('customFields');
        $behavior->$fieldHandle = $field->normalizeValue($behavior->$fieldHandle, $this);
        $this->_normalizedFieldValues[$fieldHandle] = true;
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

    /**
     * Returns the HTML that should be shown for a given attribute in Table View.
     *
     * @param string $attribute The attribute name.
     *
     * @return string The HTML that should be shown for a given attribute in Table View.
     * @see getTableAttributeHtml()
     */
    protected function tableAttributeHtml($attribute)
    {
        switch ($attribute) {
            case 'link':
                $url = $this->getUrl();

                if ($url) {
                    return '<a href="'.$url.'" target="_blank" data-icon="world" title="'.Craft::t('app', 'Visit webpage').'"></a>';
                }

                return '';

            case 'uri':
                $url = $this->getUrl();

                if ($url) {
                    $value = $this->uri;

                    if ($value == '__home__') {
                        $value = '<span data-icon="home" title="'.Craft::t('app', 'Homepage').'"></span>';
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

            default:
                // Is this a custom field?
                if (preg_match('/^field:(\d+)$/', $attribute, $matches)) {
                    $fieldId = $matches[1];
                    $field = Craft::$app->getFields()->getFieldById($fieldId);

                    if ($field) {
                        /** @var Field $field */
                        if ($field instanceof PreviewableFieldInterface) {
                            // Was this field value eager-loaded?
                            if ($field instanceof EagerLoadingFieldInterface && $this->hasEagerLoadedElements($field->handle)) {
                                $value = $this->getEagerLoadedElements($field->handle);
                            } else {
                                $value = $this->getFieldValue($field->handle);
                            }

                            return $field->getTableAttributeHtml($value, $this);
                        }
                    }

                    return '';
                }

                $value = $this->$attribute;

                if ($value instanceof DateTime) {
                    $formatter = Craft::$app->getFormatter();

                    return '<span title="'.$formatter->asDatetime($value, Locale::LENGTH_SHORT).'">'.$formatter->asTimestamp($value, Locale::LENGTH_SHORT).'</span>';
                }

                return Html::encode($value);
        }
    }

    /**
     * Returns the route that should be used when the element’s URI is requested.
     *
     * @return mixed The route that the request should use, or null if no special action should be taken
     * @see getRoute()
     */
    protected function route()
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
