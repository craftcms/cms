<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use Craft;
use craft\behaviors\ContentBehavior;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\events\ElementStructureEvent;
use craft\events\ModelEvent;
use craft\events\RegisterElementActionsEvent;
use craft\events\RegisterElementDefaultTableAttributesEvent;
use craft\events\RegisterElementHtmlAttributesEvent;
use craft\events\RegisterElementSearchableAttributesEvent;
use craft\events\RegisterElementSortOptionsEvent;
use craft\events\RegisterElementSourcesEvent;
use craft\events\RegisterElementTableAttributesEvent;
use craft\events\SetElementRouteEvent;
use craft\events\SetElementTableAttributeHtmlEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\i18n\Locale;
use craft\models\FieldLayout;
use craft\models\Site;
use craft\validators\DateTimeValidator;
use craft\validators\ElementUriValidator;
use craft\validators\SiteIdValidator;
use craft\validators\SlugValidator;
use craft\validators\StringValidator;
use craft\web\UploadedFile;
use DateTime;
use yii\base\Event;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\InvalidValueException;
use yii\validators\NumberValidator;
use yii\validators\Validator;

/**
 * Element is the base class for classes representing elements in terms of objects.
 *
 * @property ElementQueryInterface $ancestors The element’s ancestors
 * @property ElementQueryInterface $children The element’s children
 * @property string $contentTable The name of the table this element’s content is stored in
 * @property string|null $cpEditUrl The element’s CP edit URL
 * @property ElementQueryInterface $descendants The element’s descendants
 * @property string $editorHtml The HTML for the element’s editor HUD
 * @property string $fieldColumnPrefix The field column prefix this element’s content uses
 * @property string $fieldContext The field context this element’s content uses
 * @property FieldLayout|null $fieldLayout The field layout used by this element
 * @property array $fieldParamNamespace The namespace used by custom field params on the request
 * @property array $fieldValues The element’s normalized custom field values, indexed by their handles
 * @property bool $hasDescendants Whether the element has descendants
 * @property bool $hasFreshContent Whether the element’s content is "fresh" (unsaved and without validation errors)
 * @property array $htmlAttributes Any attributes that should be included in the element’s DOM representation in the Control Panel
 * @property bool $isEditable Whether the current user can edit the element
 * @property \Twig_Markup|null $link An anchor pre-filled with this element’s URL and title
 * @property Element|null $next The next element relative to this one, from a given set of criteria
 * @property Element|null $nextSibling The element’s next sibling
 * @property Element|null $parent The element’s parent
 * @property Element|null $prev The previous element relative to this one, from a given set of criteria
 * @property Element|null $prevSibling The element’s previous sibling
 * @property string|null $ref The reference string to this element
 * @property mixed $route The route that should be used when the element’s URI is requested
 * @property string|null $serializedFieldValues Array of the element’s serialized custom field values, indexed by their handles
 * @property ElementQueryInterface $siblings All of the element’s siblings
 * @property Site $site Site the element is associated with
 * @property string|null $status The element’s status
 * @property int[]|array $supportedSites The sites this element is associated with
 * @property int $totalDescendants The total number of descendants that the element has
 * @property string|null $uriFormat The URI format used to generate this element’s URL
 * @property string|null $url The element’s full URL
 * @mixin ContentBehavior
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class Element extends Component implements ElementInterface
{
    // Traits
    // =========================================================================

    use ElementTrait;

    // Constants
    // =========================================================================

    // Statuses
    // -------------------------------------------------------------------------

    const STATUS_ENABLED = 'enabled';
    const STATUS_DISABLED = 'disabled';
    const STATUS_ARCHIVED = 'archived';

    // Validation scenarios
    // -------------------------------------------------------------------------

    const SCENARIO_ESSENTIALS = 'essentials';
    const SCENARIO_LIVE = 'live';

    // Events
    // -------------------------------------------------------------------------

    /**
     * @event RegisterElementSourcesEvent The event that is triggered when registering the available sources for the element type.
     */
    const EVENT_REGISTER_SOURCES = 'registerSources';

    /**
     * @event RegisterElementActionsEvent The event that is triggered when registering the available actions for the element type.
     */
    const EVENT_REGISTER_ACTIONS = 'registerActions';

    /**
     * @event RegisterElementSearchableAttributesEvent The event that is triggered when registering the searchable attributes for the element type.
     */
    const EVENT_REGISTER_SEARCHABLE_ATTRIBUTES = 'registerSearchableAttributes';

    /**
     * @event RegisterElementSortOptionsEvent The event that is triggered when registering the sort options for the element type.
     */
    const EVENT_REGISTER_SORT_OPTIONS = 'registerSortOptions';

    /**
     * @event RegisterElementTableAttributesEvent The event that is triggered when registering the table attributes for the element type.
     */
    const EVENT_REGISTER_TABLE_ATTRIBUTES = 'registerTableAttributes';

    /**
     * @event RegisterElementTableAttributesEvent The event that is triggered when registering the table attributes for the element type.
     */
    const EVENT_REGISTER_DEFAULT_TABLE_ATTRIBUTES = 'registerDefaultTableAttributes';

    /**
     * @event SetElementTableAttributeHtmlEvent The event that is triggered when defining the HTML to represent a table attribute.
     */
    const EVENT_SET_TABLE_ATTRIBUTE_HTML = 'setTableAttributeHtml';

    /**
     * @event RegisterElementHtmlAttributesEvent The event that is triggered when registering the HTML attributes that should be included in the element’s DOM representation in the Control Panel.
     */
    const EVENT_REGISTER_HTML_ATTRIBUTES = 'registerHtmlAttributes';

    /**
     * @event SetElementRouteEvent The event that is triggered when defining the route that should be used when this element’s URL is requested
     *
     * ```php
     * Event::on(craft\elements\Entry::class, craft\base\Element::EVENT_SET_ROUTE, function(craft\events\SetElementRouteEvent $e) {
     *     // @var craft\elements\Entry $entry
     *     $entry = $e->sender;
     *
     *     if ($entry->uri === 'pricing') {
     *         $e->route = 'module/pricing/index';
     *     }
     * }
     * ```
     */
    const EVENT_SET_ROUTE = 'setRoute';

    /**
     * @event ModelEvent The event that is triggered before the element is saved
     * You may set [[ModelEvent::isValid]] to `false` to prevent the element from getting saved.
     */
    const EVENT_BEFORE_SAVE = 'beforeSave';

    /**
     * @event ModelEvent The event that is triggered after the element is saved
     */
    const EVENT_AFTER_SAVE = 'afterSave';

    /**
     * @event ModelEvent The event that is triggered before the element is deleted
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
    public static function refHandle()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function hasUris(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_ENABLED => Craft::t('app', 'Enabled'),
            self::STATUS_DISABLED => Craft::t('app', 'Disabled')
        ];
    }

    /**
     * @inheritdoc
     * @return ElementQueryInterface
     */
    public static function find(): ElementQueryInterface
    {
        return new ElementQuery(static::class);
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
    public static function findAll($criteria = null): array
    {
        return static::findByCondition($criteria, false);
    }

    /**
     * @inheritdoc
     */
    public static function sources(string $context = null): array
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
    public static function actions(string $source): array
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
    public static function searchableAttributes(): array
    {
        $attributes = static::defineSearchableAttributes();

        // Give plugins a chance to modify them
        $event = new RegisterElementSearchableAttributesEvent([
            'attributes' => $attributes
        ]);
        Event::trigger(static::class, self::EVENT_REGISTER_SEARCHABLE_ATTRIBUTES, $event);

        return $event->attributes;
    }

    /**
     * Defines the sources that elements of this type may belong to.
     *
     * @param string|null $context The context ('index' or 'modal').
     * @return array The sources.
     * @see sources()
     */
    protected static function defineSources(string $context = null): array
    {
        return [];
    }

    /**
     * Defines the available element actions for a given source (if one is provided).
     *
     * @param string|null $source The selected source’s key, if any.
     * @return array The available element actions.
     * @see actions()
     */
    protected static function defineActions(string $source = null): array
    {
        return [];
    }

    /**
     * Defines which element attributes should be searchable.
     *
     * @return string[] The element attributes that should be searchable
     * @see searchableAttributes()
     */
    protected static function defineSearchableAttributes(): array
    {
        return [];
    }

    // Element index methods
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public static function indexHtml(ElementQueryInterface $elementQuery, array $disabledElementIds = null, array $viewState, string $sourceKey = null, string $context = null, bool $includeContainer, bool $showCheckboxes): string
    {
        $variables = [
            'viewMode' => $viewState['mode'],
            'context' => $context,
            'disabledElementIds' => $disabledElementIds,
            'collapsedElementIds' => Craft::$app->getRequest()->getParam('collapsedElementIds'),
            'showCheckboxes' => $showCheckboxes,
        ];

        // Special case for sorting by structure
        if (isset($viewState['order']) && $viewState['order'] === 'structure') {
            $source = ElementHelper::findSource(static::class, $sourceKey, $context);

            if (isset($source['structureId'])) {
                $elementQuery->orderBy(['lft' => SORT_ASC]);
                $variables['structure'] = Craft::$app->getStructures()->getStructureById($source['structureId']);

                // Are they allowed to make changes to this structure?
                if ($context === 'index' && $variables['structure'] && !empty($source['structureEditable'])) {
                    $variables['structureEditable'] = true;

                    // Let StructuresController know that this user can make changes to the structure
                    Craft::$app->getSession()->authorize('editStructure:' . $variables['structure']->id);
                }
            } else {
                unset($viewState['order']);
            }
        } else {
            $orderBy = self::_indexOrderBy($viewState);
            if ($orderBy !== false) {
                $elementQuery->orderBy($orderBy);
            }
        }

        if ($viewState['mode'] === 'table') {
            // Get the table columns
            $variables['attributes'] = Craft::$app->getElementIndexes()->getTableAttributes(static::class, $sourceKey);

            // Give each attribute a chance to modify the criteria
            foreach ($variables['attributes'] as $attribute) {
                static::prepElementQueryForTableAttribute($elementQuery, $attribute[0]);
            }
        }

        $variables['elements'] = $elementQuery->all();

        $template = '_elements/' . $viewState['mode'] . 'view/' . ($includeContainer ? 'container' : 'elements');

        return Craft::$app->getView()->renderTemplate($template, $variables);
    }

    /**
     * @inheritdoc
     */
    public static function sortOptions(): array
    {
        $sortOptions = static::defineSortOptions();

        // Give plugins a chance to modify them
        $event = new RegisterElementSortOptionsEvent([
            'sortOptions' => $sortOptions
        ]);
        Event::trigger(static::class, self::EVENT_REGISTER_SORT_OPTIONS, $event);

        return $event->sortOptions;
    }

    /**
     * @inheritdoc
     */
    public static function tableAttributes(): array
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
    public static function defaultTableAttributes(string $source): array
    {
        $tableAttributes = static::defineDefaultTableAttributes($source);

        // Give plugins a chance to modify them
        $event = new RegisterElementDefaultTableAttributesEvent([
            'source' => $source,
            'tableAttributes' => $tableAttributes
        ]);
        Event::trigger(static::class, self::EVENT_REGISTER_DEFAULT_TABLE_ATTRIBUTES, $event);

        return $event->tableAttributes;
    }

    /**
     * Returns the sort options for the element type.
     *
     * @return array The attributes that elements can be sorted by
     * @see sortOptions()
     */
    protected static function defineSortOptions(): array
    {
        // Default to the available table attributes
        $tableAttributes = Craft::$app->getElementIndexes()->getAvailableTableAttributes(static::class);
        $sortOptions = [];

        foreach ($tableAttributes as $key => $labelInfo) {
            $sortOptions[$key] = $labelInfo['label'];
        }

        return $sortOptions;
    }

    /**
     * Defines all of the available columns that can be shown in table views.
     *
     * @return array The table attributes.
     * @see tableAttributes()
     */
    protected static function defineTableAttributes(): array
    {
        return [];
    }

    /**
     * Returns the list of table attribute keys that should be shown by default.
     *
     * @param string $source The selected source’s key
     * @return string[] The table attributes.
     * @see defaultTableAttributes()
     * @see tableAttributes()
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        // Return all of them by default
        $availableTableAttributes = static::tableAttributes();

        return array_keys($availableTableAttributes);
    }

    // Methods for customizing element queries
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle)
    {
        // Eager-loading descendants or direct children?
        if ($handle === 'descendants' || $handle === 'children') {
            // Get the source element IDs
            $sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');

            // Get the structure data for these elements
            $selectColumns = ['structureId', 'elementId', 'lft', 'rgt'];

            if ($handle === 'children') {
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

                if ($handle === 'children') {
                    $thisElementCondition[] = ['level' => $elementStructureData['level'] + 1];
                }

                $condition[] = $thisElementCondition;
                $sourceSelectSql .= ' WHEN ' .
                    $qb->buildCondition(
                        [
                            'and',
                            ['structureId' => $elementStructureData['structureId']],
                            ['>', 'lft', $elementStructureData['lft']],
                            ['<', 'rgt', $elementStructureData['rgt']]
                        ],
                        $query->params) .
                    " THEN :sourceId{$i}";
                $query->params[':sourceId' . $i] = $elementStructureData['elementId'];
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
     * @param string $attribute
     */
    protected static function prepElementQueryForTableAttribute(ElementQueryInterface $elementQuery, string $attribute)
    {
        /** @var ElementQuery $elementQuery */
        // Is this a custom field?
        if (preg_match('/^field:(\d+)$/', $attribute, $matches)) {
            $fieldId = $matches[1];
            $field = Craft::$app->getFields()->getFieldById($fieldId);

            if ($field) {
                $field->modifyElementIndexQuery($elementQuery);
            }
        }
    }

    /**
     * Returns the orderBy value for element indexes
     *
     * @param array $viewState
     * @return array|false
     */
    private static function _indexOrderBy(array $viewState)
    {
        // Define the available sort attribute/option pairs
        $sortOptions = [];
        foreach (static::sortOptions() as $key => $sortOption) {
            if (is_string($key)) {
                // Shorthand syntax
                $sortOptions[$key] = $key;
            } else {
                if (!isset($sortOption['orderBy'])) {
                    throw new InvalidValueException('Sort options must specify an orderBy value');
                }
                $attribute = $sortOption['attribute'] ?? $sortOption['orderBy'];
                $sortOptions[$attribute] = $sortOption['orderBy'];
            }
        }
        $sortOptions['score'] = 'score';

        if (!empty($viewState['order']) && isset($sortOptions[$viewState['order']])) {
            $columns = $sortOptions[$viewState['order']];
        } else if (count($sortOptions) > 1) {
            $columns = reset($sortOptions);
        } else {
            return false;
        }

        // Borrowed from QueryTrait::normalizeOrderBy()
        $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
        $result = [];
        foreach ($columns as $i => $column) {
            if ($i === 0) {
                // The first column's sort direction is always user-defined
                $result[$column] = !empty($viewState['sort']) && strcasecmp($viewState['sort'], 'desc') ? SORT_ASC : SORT_DESC;
            } else if (preg_match('/^(.*?)\s+(asc|desc)$/i', $column, $matches)) {
                $result[$matches[1]] = strcasecmp($matches[2], 'desc') ? SORT_ASC : SORT_DESC;
            } else {
                $result[$column] = SORT_ASC;
            }
        }

        return $result;
    }

    // Properties
    // =========================================================================

    /**
     * @var
     */
    private $_fieldsByHandle;

    /**
     * @var
     */
    private $_fieldParamNamePrefix;

    /**
     * @var array|null Record of the fields whose values have already been normalized
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
     * @var ElementInterface[]|null
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
        if ($this->title) {
            return (string)$this->title;
        }
        return (string)$this->id ?: static::class;
    }

    /**
     * Checks if a property is set.
     *
     * This method will check if $name is one of the following:
     * - "title"
     * - a magic property supported by [[\yii\base\Component::__isset()]]
     * - a custom field handle
     *
     * @param string $name The property name
     * @return bool Whether the property is set
     */
    public function __isset($name): bool
    {
        // Is this the "field:handle" syntax?
        if (strncmp($name, 'field:', 6) === 0) {
            return $this->fieldByHandle(substr($name, 6)) !== null;
        }

        return $name === 'title' || $this->hasEagerLoadedElements($name) || parent::__isset($name) || $this->fieldByHandle($name);
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        if ($name === 'locale') {
            Craft::$app->getDeprecator()->log('Element::locale', 'The “locale” element property has been deprecated. Use “siteId” instead.');

            return $this->getSite()->handle;
        }

        // Is $name a set of eager-loaded elements?
        if ($this->hasEagerLoadedElements($name)) {
            return $this->getEagerLoadedElements($name);
        }

        // Is this the "field:handle" syntax?
        if (strncmp($name, 'field:', 6) === 0) {
            return $this->getFieldValue(substr($name, 6));
        }

        // If this is a field, make sure the value has been normalized before returning the ContentBehavior value
        if ($this->fieldByHandle($name) !== null) {
            $this->normalizeFieldValue($name);
        }

        return parent::__get($name);
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        // Is this the "field:handle" syntax?
        if (strncmp($name, 'field:', 6) === 0) {
            $this->setFieldValue(substr($name, 6), $value);
            return;
        }

        parent::__set($name, $value);
    }

    /**
     * @inheritdoc
     */
    public function __call($name, $params)
    {
        if (strncmp($name, 'isFieldEmpty:', 13) === 0) {
            return $this->isFieldEmpty(substr($name, 13));
        }

        return parent::__call($name, $params);
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['customFields'] = ContentBehavior::class;
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->siteId === null && Craft::$app->getIsInstalled()) {
            $this->siteId = Craft::$app->getSites()->getPrimarySite()->id;
        }
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        $names = parent::attributes();

        if (!$this->structureId) {
            ArrayHelper::removeValue($names, 'structureId');
            ArrayHelper::removeValue($names, 'root');
            ArrayHelper::removeValue($names, 'lft');
            ArrayHelper::removeValue($names, 'rgt');
            ArrayHelper::removeValue($names, 'level');
        }

        ArrayHelper::removeValue($names, 'searchScore');
        ArrayHelper::removeValue($names, 'awaitingFieldValues');
        ArrayHelper::removeValue($names, 'propagating');

        $names[] = 'hasDescendants';
        $names[] = 'ref';
        $names[] = 'status';
        $names[] = 'structureId';
        $names[] = 'totalDescendants';
        $names[] = 'url';

        // Include custom field handles
        if (static::hasContent() && ($fieldLayout = $this->getFieldLayout()) !== null) {
            foreach ($fieldLayout->getFields() as $field) {
                /** @var Field $field */
                $names[] = $field->handle;
            }
        }

        // In case there are any field handles that had the same name as an existing property
        return array_unique($names);
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'ancestors',
            'children',
            'descendants',
            'next',
            'nextSibling',
            'parent',
            'prev',
            'prevSibling',
            'siblings',
            'site',
        ];
    }

    /**
     * @inheritdoc
     */
    public function getAttributeLabel($attribute)
    {
        // Is this the "field:handle" syntax?
        if (strncmp($attribute, 'field:', 6) === 0) {
            $attribute = substr($attribute, 6);
        }

        return parent::getAttributeLabel($attribute);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        $labels = [
            'dateCreated' => Craft::t('app', 'Date Created'),
            'dateUpdated' => Craft::t('app', 'Date Updated'),
            'id' => Craft::t('app', 'ID'),
            'slug' => Craft::t('app', 'Slug'),
            'title' => Craft::t('app', 'Title'),
            'uid' => Craft::t('app', 'UID'),
            'uri' => Craft::t('app', 'URI'),
        ];

        if (Craft::$app->getIsInstalled()) {
            $layout = $this->getFieldLayout();

            if ($layout !== null) {
                foreach ($layout->getFields() as $field) {
                    /** @var Field $field */
                    $labels[$field->handle] = Craft::t('site', $field->name);
                }
            }
        }

        return $labels;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [
            [['id', 'contentId', 'root', 'lft', 'rgt', 'level'], 'number', 'integerOnly' => true, 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE]],
            [['siteId'], SiteIdValidator::class, 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE, self::SCENARIO_ESSENTIALS]],
            [['dateCreated', 'dateUpdated'], DateTimeValidator::class, 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE]],
        ];

        if (static::hasTitles()) {
            $rules[] = [['title'], StringValidator::class, 'max' => 255, 'disallowMb4' => true, 'trim' => true, 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE]];
            $rules[] = [['title'], 'required', 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE]];
        }

        if (static::hasUris()) {
            $rules[] = [['slug'], SlugValidator::class, 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE, self::SCENARIO_ESSENTIALS]];
            $rules[] = [['slug'], 'string', 'max' => 255, 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE, self::SCENARIO_ESSENTIALS]];
            $rules[] = [['uri'], ElementUriValidator::class, 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE, self::SCENARIO_ESSENTIALS]];
        }

        // Are we validating custom fields?
        if (static::hasContent() && Craft::$app->getIsInstalled() && $fieldLayout = $this->getFieldLayout()) {
            $fieldsWithColumns = [];

            foreach ($fieldLayout->getFields() as $field) {
                /** @var Field $field */
                $attribute = 'field:' . $field->handle;
                $isEmpty = [$this, 'isFieldEmpty:' . $field->handle];

                if ($field->required) {
                    // Only validate required custom fields on the LIVE scenario
                    $rules[] = [[$attribute], 'required', 'isEmpty' => $isEmpty, 'on' => self::SCENARIO_LIVE];
                }

                if ($field::hasContentColumn()) {
                    $fieldsWithColumns[] = $field->handle;
                }

                foreach ($field->getElementValidationRules() as $rule) {
                    if ($rule instanceof Validator) {
                        $rules[] = $rule;
                    } else {
                        if (is_string($rule)) {
                            // "Validator" syntax
                            $rule = [$attribute, $rule, 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE]];
                        }

                        if (!is_array($rule) || !isset($rule[0])) {
                            throw new InvalidConfigException('Invalid validation rule for custom field "' . $field->handle . '".');
                        }

                        if (isset($rule[1])) {
                            // Make sure the attribute name starts with 'field:'
                            if ($rule[0] === $field->handle) {
                                $rule[0] = $attribute;
                            }
                        } else {
                            // ["Validator"] syntax
                            array_unshift($rule, $attribute);
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
                                    $rule['params'] ?? null,
                                ]
                            ];
                        }

                        // Set 'isEmpty' to the field's isEmpty() method by default
                        if (!array_key_exists('isEmpty', $rule)) {
                            $rule['isEmpty'] = $isEmpty;
                        }

                        // Set 'on' to the main scenarios by default
                        if (!array_key_exists('on', $rule)) {
                            $rule['on'] = [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE];
                        }

                        $rules[] = $rule;
                    }
                }
            }

            if (!empty($fieldsWithColumns)) {
                $rules[] = [$fieldsWithColumns, 'validateCustomFieldContentSize', 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE]];
            }
        }

        return $rules;
    }

    /**
     * Calls a custom validation function on a custom field.
     *
     * This will be called by [[\yii\validators\InlineValidator]] if a custom field specified
     * a closure or the name of a class-level method as the validation type.
     *
     * @param string $attribute The field handle
     * @param array|null $params
     */
    public function validateCustomFieldAttribute(string $attribute, array $params = null)
    {
        /** @var Field $field */
        /** @var array|null $params */
        list($field, $method, $fieldParams) = $params;

        if (is_string($method)) {
            $method = [$field, $method];
        }

        $method($this, $fieldParams);
    }

    /**
     * Returns whether a field is empty.
     *
     * @param string $handle
     * @return bool
     */
    public function isFieldEmpty(string $handle): bool
    {
        if (
            ($fieldLayout = $this->getFieldLayout()) === null ||
            ($field = $fieldLayout->getFieldByHandle($handle)) === null
        ) {
            return true;
        }

        return $field->isValueEmpty($this->getFieldValue($handle), $this);
    }

    /**
     * Validates that the content size is going to fit within the field’s database column.
     *
     * @param string $attribute
     */
    public function validateCustomFieldContentSize(string $attribute)
    {
        $field = $this->fieldByHandle($attribute);
        $columnType = $field->getContentColumnType();
        $simpleColumnType = Db::getSimplifiedColumnType($columnType);

        if (!in_array($simpleColumnType, [Db::SIMPLE_TYPE_NUMERIC, Db::SIMPLE_TYPE_TEXTUAL], true)) {
            return;
        }

        $value = Db::prepareValueForDb($field->serializeValue($this->getFieldValue($attribute), $this));

        // Ignore empty values
        if ($value === null || $value === '') {
            return;
        }

        if ($simpleColumnType === Db::SIMPLE_TYPE_NUMERIC) {
            $validator = new NumberValidator([
                'min' => Db::getMinAllowedValueForNumericColumn($columnType) ?: null,
                'max' => Db::getMaxAllowedValueForNumericColumn($columnType) ?: null,
            ]);
        } else {
            $validator = new StringValidator([
                // Don't count multibyte characters as a single char
                'encoding' => '8bit',
                'max' => Db::getTextualColumnStorageCapacity($columnType) ?: null,
                'disallowMb4' => true,
            ]);
        }

        if (!$validator->validate($value, $error)) {
            $error = str_replace(Craft::t('yii', 'the input value'), Craft::t('site', $field->name), $error);
            $this->addError($attribute, $error);
        }
    }

    /**
     * @inheritdoc
     */
    public function addError($attribute, $error = '')
    {
        if (strncmp($attribute, 'field:', 6) === 0) {
            $attribute = substr($attribute, 6);
        }

        parent::addError($attribute, $error);
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
        if ($this->fieldLayoutId) {
            return Craft::$app->getFields()->getLayoutById($this->fieldLayoutId);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getSupportedSites(): array
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
    public function getSearchKeywords(string $attribute): string
    {
        return StringHelper::toString($this->$attribute);
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
            $path = ($this->uri === '__home__') ? '' : $this->uri;

            return UrlHelper::siteUrl($path, null, null, $this->siteId);
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
            $link = '<a href="' . $url . '">' . Html::encode($this->__toString()) . '</a>';

            return Template::raw($link);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getRef()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getIsEditable(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getThumbUrl(int $size)
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
        if ($criteria !== false || $this->_nextElement === null) {
            return $this->_getRelativeElement($criteria, 1);
        }

        if ($this->_nextElement === false) {
            return null;
        }

        return $this->_nextElement;
    }

    /**
     * @inheritdoc
     */
    public function getPrev($criteria = false)
    {
        if ($criteria !== false || $this->_prevElement === null) {
            return $this->_getRelativeElement($criteria, -1);
        }

        if ($this->_prevElement === false) {
            return null;
        }

        return $this->_prevElement;
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
                ->anyStatus()
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
    public function setParent(ElementInterface $parent = null)
    {
        /** @var Element $parent */
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
    public function getAncestors(int $dist = null)
    {
        return static::find()
            ->structureId($this->structureId)
            ->ancestorOf($this)
            ->siteId($this->siteId)
            ->ancestorDist($dist);
    }

    /**
     * @inheritdoc
     */
    public function getDescendants(int $dist = null)
    {
        // Eager-loaded?
        if (($descendants = $this->getEagerLoadedElements('descendants')) !== null) {
            return $descendants;
        }

        return static::find()
            ->structureId($this->structureId)
            ->descendantOf($this)
            ->siteId($this->siteId)
            ->descendantDist($dist);
    }

    /**
     * @inheritdoc
     */
    public function getChildren()
    {
        // Eager-loaded?
        if (($children = $this->getEagerLoadedElements('children')) !== null) {
            return $children;
        }

        return $this->getDescendants(1);
    }

    /**
     * @inheritdoc
     */
    public function getSiblings()
    {
        return static::find()
            ->structureId($this->structureId)
            ->siblingOf($this)
            ->siteId($this->siteId);
    }

    /**
     * @inheritdoc
     */
    public function getPrevSibling()
    {
        if ($this->_prevSibling === null) {
            /** @var ElementQuery $query */
            $query = $this->_prevSibling = static::find();
            $query->structureId = $this->structureId;
            $query->prevSiblingOf = $this;
            $query->siteId = $this->siteId;
            $query->anyStatus();
            $this->_prevSibling = $query->one();

            if ($this->_prevSibling === null) {
                $this->_prevSibling = false;
            }
        }

        return $this->_prevSibling ?: null;
    }

    /**
     * @inheritdoc
     */
    public function getNextSibling()
    {
        if ($this->_nextSibling === null) {
            /** @var ElementQuery $query */
            $query = $this->_nextSibling = static::find();
            $query->structureId = $this->structureId;
            $query->nextSiblingOf = $this;
            $query->siteId = $this->siteId;
            $query->anyStatus();
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
    public function getHasDescendants(): bool
    {
        return ($this->lft !== null && $this->rgt !== null && $this->rgt > $this->lft + 1);
    }

    /**
     * @inheritdoc
     */
    public function getTotalDescendants(): int
    {
        if ($this->getHasDescendants()) {
            return ($this->rgt - $this->lft - 1) / 2;
        }

        return 0;
    }

    /**
     * @inheritdoc
     */
    public function isAncestorOf(ElementInterface $element): bool
    {
        /** @var Element $element */
        return ($this->root == $element->root && $this->lft < $element->lft && $this->rgt > $element->rgt);
    }

    /**
     * @inheritdoc
     */
    public function isDescendantOf(ElementInterface $element): bool
    {
        /** @var Element $element */
        return ($this->root == $element->root && $this->lft > $element->lft && $this->rgt < $element->rgt);
    }

    /**
     * @inheritdoc
     */
    public function isParentOf(ElementInterface $element): bool
    {
        /** @var Element $element */
        return ($this->root == $element->root && $this->level == $element->level - 1 && $this->isAncestorOf($element));
    }

    /**
     * @inheritdoc
     */
    public function isChildOf(ElementInterface $element): bool
    {
        /** @var Element $element */
        return ($this->root == $element->root && $this->level == $element->level + 1 && $this->isDescendantOf($element));
    }

    /**
     * @inheritdoc
     */
    public function isSiblingOf(ElementInterface $element): bool
    {
        /** @var Element $element */
        if ($this->root == $element->root && $this->level !== null && $this->level == $element->level) {
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
    public function isPrevSiblingOf(ElementInterface $element): bool
    {
        /** @var Element $element */
        return ($this->root == $element->root && $this->level == $element->level && $this->rgt == $element->lft - 1);
    }

    /**
     * @inheritdoc
     */
    public function isNextSiblingOf(ElementInterface $element): bool
    {
        /** @var Element $element */
        return ($this->root == $element->root && $this->level == $element->level && $this->lft == $element->rgt + 1);
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return $offset === 'title' || $this->hasEagerLoadedElements($offset) || parent::offsetExists($offset) || $this->fieldByHandle($offset);
    }

    /**
     * @inheritdoc
     */
    public function getFieldValues(array $fieldHandles = null): array
    {
        $values = [];

        foreach ($this->fieldLayoutFields() as $field) {
            if ($fieldHandles === null || in_array($field->handle, $fieldHandles, true)) {
                $values[$field->handle] = $this->getFieldValue($field->handle);
            }
        }

        return $values;
    }

    /**
     * @inheritdoc
     */
    public function getSerializedFieldValues(array $fieldHandles = null): array
    {
        $serializedValues = [];

        foreach ($this->fieldLayoutFields() as $field) {
            if ($fieldHandles === null || in_array($field->handle, $fieldHandles, true)) {
                $value = $this->getFieldValue($field->handle);
                $serializedValues[$field->handle] = $field->serializeValue($value, $this);
            }
        }

        return $serializedValues;
    }

    /**
     * @inheritdoc
     */
    public function setFieldValues(array $values)
    {
        foreach ($values as $fieldHandle => $value) {
            $this->setFieldValue($fieldHandle, $value);
        }
    }

    /**
     * @inheritdoc
     */
    public function getFieldValue(string $fieldHandle)
    {
        // Make sure the value has been normalized
        $this->normalizeFieldValue($fieldHandle);

        return $this->getBehavior('customFields')->$fieldHandle;
    }

    /**
     * @inheritdoc
     */
    public function setFieldValue(string $fieldHandle, $value)
    {
        $behavior = $this->getBehavior('customFields');
        $behavior->$fieldHandle = $value;

        // Don't assume that $value has been normalized
        unset($this->_normalizedFieldValues[$fieldHandle]);
    }

    /**
     * @inheritdoc
     */
    public function setFieldValuesFromRequest(string $paramNamespace = '')
    {
        $this->setFieldParamNamespace($paramNamespace);
        $values = Craft::$app->getRequest()->getBodyParam($paramNamespace, []);

        foreach ($this->fieldLayoutFields() as $field) {
            // Do we have any post data for this field?
            if (isset($values[$field->handle])) {
                $value = $values[$field->handle];
            } else if (!empty($this->_fieldParamNamePrefix) && UploadedFile::getInstancesByName($this->_fieldParamNamePrefix . '.' . $field->handle)) {
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
    public function setFieldParamNamespace(string $namespace)
    {
        $this->_fieldParamNamePrefix = $namespace;
    }

    /**
     * @inheritdoc
     */
    public function getContentTable(): string
    {
        return Craft::$app->getContent()->contentTable;
    }

    /**
     * @inheritdoc
     */
    public function getFieldColumnPrefix(): string
    {
        return Craft::$app->getContent()->fieldColumnPrefix;
    }

    /**
     * @inheritdoc
     */
    public function getFieldContext(): string
    {
        return Craft::$app->getContent()->fieldContext;
    }

    /**
     * Returns whether elements have been eager-loaded with a given handle.
     *
     * @param string $handle The handle of the eager-loaded elements
     * @return bool Whether elements have been eager-loaded with the given handle
     */
    public function hasEagerLoadedElements(string $handle): bool
    {
        return isset($this->_eagerLoadedElements[$handle]);
    }

    /**
     * Returns the eager-loaded elements for a given handle.
     *
     * @param string $handle The handle of the eager-loaded elements
     * @return ElementInterface[]|null The eager-loaded elements, or null
     */
    public function getEagerLoadedElements(string $handle)
    {
        if (!isset($this->_eagerLoadedElements[$handle])) {
            return null;
        }

        ElementHelper::setNextPrevOnElements($this->_eagerLoadedElements[$handle]);

        return $this->_eagerLoadedElements[$handle];
    }

    /**
     * Sets some eager-loaded elements on a given handle.
     *
     * @param string $handle The handle to load the elements with in the future
     * @param ElementInterface[] $elements The eager-loaded elements
     */
    public function setEagerLoadedElements(string $handle, array $elements)
    {
        $this->_eagerLoadedElements[$handle] = $elements;
    }

    /**
     * @inheritdoc
     */
    public function getHasFreshContent(): bool
    {
        return ($this->contentId === null && !$this->hasErrors());
    }

    // Indexes, etc.
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function getHtmlAttributes(string $context): array
    {
        $htmlAttributes = $this->htmlAttributes($context);

        // Give plugins a chance to modify them
        $event = new RegisterElementHtmlAttributesEvent([
            'htmlAttributes' => $htmlAttributes
        ]);
        $this->trigger(self::EVENT_REGISTER_HTML_ATTRIBUTES, $event);

        return $event->htmlAttributes;
    }

    /**
     * @inheritdoc
     */
    public function getTableAttributeHtml(string $attribute): string
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
    public function getEditorHtml(): string
    {
        $html = '';

        $fieldLayout = $this->getFieldLayout();
        $view = Craft::$app->getView();

        if ($fieldLayout) {
            $originalNamespace = $view->getNamespace();
            $namespace = $view->namespaceInputName('fields', $originalNamespace);
            $view->setNamespace($namespace);

            foreach ($fieldLayout->getFields() as $field) {
                $fieldHtml = $view->renderTemplate('_includes/field', [
                    'element' => $this,
                    'field' => $field,
                    'required' => $field->required
                ]);

                $html .= $view->namespaceInputs($fieldHtml, 'fields');
            }

            Craft::$app->getView()->setNamespace($originalNamespace);

            $html .= '<input type="hidden" name="fieldLayoutId" value="' . $fieldLayout->id . '">';
        }

        return $html;
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function beforeSave(bool $isNew): bool
    {
        // Tell the fields about it
        foreach ($this->fieldLayoutFields() as $field) {
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
    public function afterSave(bool $isNew)
    {
        // Tell the fields about it
        foreach ($this->fieldLayoutFields() as $field) {
            $field->afterElementSave($this, $isNew);
        }

        // Trigger an 'afterSave' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE)) {
            $this->trigger(self::EVENT_AFTER_SAVE, new ModelEvent([
                'isNew' => $isNew,
            ]));
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete(): bool
    {
        // Tell the fields about it
        foreach ($this->fieldLayoutFields() as $field) {
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
        foreach ($this->fieldLayoutFields() as $field) {
            $field->afterElementDelete($this);
        }

        // Trigger an 'afterDelete' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE)) {
            $this->trigger(self::EVENT_AFTER_DELETE);
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeMoveInStructure(int $structureId): bool
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
    public function afterMoveInStructure(int $structureId)
    {
        // Trigger an 'afterMoveInStructure' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_MOVE_IN_STRUCTURE)) {
            $this->trigger(self::EVENT_AFTER_MOVE_IN_STRUCTURE, new ElementStructureEvent([
                'structureId' => $structureId,
            ]));
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * Normalizes a field’s value.
     *
     * @param string $fieldHandle The field handle
     * @throws Exception if there is no field with the handle $fieldValue
     */
    protected function normalizeFieldValue(string $fieldHandle)
    {
        // Have we already normalized this value?
        if (isset($this->_normalizedFieldValues[$fieldHandle])) {
            return;
        }

        $field = $this->fieldByHandle($fieldHandle);

        if (!$field) {
            throw new Exception('Invalid field handle: ' . $fieldHandle);
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
     * @param mixed $criteria Refer to [[findOne()]] and [[findAll()]] for the explanation of this parameter
     * @param bool $one Whether this method is called by [[findOne()]] or [[findAll()]]
     * @return static|static[]|null
     */
    protected static function findByCondition($criteria, bool $one)
    {
        /** @var ElementQueryInterface $query */
        $query = static::find();

        if ($criteria !== null) {
            if (!ArrayHelper::isAssociative($criteria)) {
                $criteria = ['id' => $criteria];
            }
            Craft::configure($query, $criteria);
        }

        if ($one) {
            /** @var Element|null $result */
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
     * @return Field|null
     */
    protected function fieldByHandle(string $handle)
    {
        if ($this->_fieldsByHandle !== null && array_key_exists($handle, $this->_fieldsByHandle)) {
            return $this->_fieldsByHandle[$handle];
        }

        $contentService = Craft::$app->getContent();
        $originalFieldContext = $contentService->fieldContext;
        $contentService->fieldContext = $this->getFieldContext();
        $fieldLayout = $this->getFieldLayout();
        $this->_fieldsByHandle[$handle] = $fieldLayout ? $fieldLayout->getFieldByHandle($handle) : null;
        $contentService->fieldContext = $originalFieldContext;

        return $this->_fieldsByHandle[$handle];
    }

    /**
     * Returns each of this element’s fields.
     *
     * @return Field[] This element’s fields
     */
    protected function fieldLayoutFields(): array
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
    public function getSite(): Site
    {
        if ($this->siteId !== null) {
            $site = Craft::$app->getSites()->getSiteById($this->siteId);
        }

        if (empty($site)) {
            throw new InvalidConfigException('Invalid site ID: ' . $this->siteId);
        }

        return $site;
    }

    /**
     * Returns the HTML that should be shown for a given attribute in Table View.
     *
     * @param string $attribute The attribute name.
     * @return string The HTML that should be shown for a given attribute in Table View.
     * @see getTableAttributeHtml()
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'link':
                $url = $this->getUrl();

                if ($url !== null) {
                    return '<a href="' . $url . '" target="_blank" data-icon="world" title="' . Craft::t('app', 'Visit webpage') . '"></a>';
                }

                return '';

            case 'uri':
                $url = $this->getUrl();

                if ($url !== null) {
                    $value = $this->uri;

                    if ($value === '__home__') {
                        $value = '<span data-icon="home" title="' . Craft::t('app', 'Homepage') . '"></span>';
                    } else {
                        // Add some <wbr> tags in there so it doesn't all have to be on one line
                        $find = ['/'];
                        $replace = ['/<wbr>'];

                        $wordSeparator = Craft::$app->getConfig()->getGeneral()->slugWordSeparator;

                        if ($wordSeparator) {
                            $find[] = $wordSeparator;
                            $replace[] = $wordSeparator . '<wbr>';
                        }

                        $value = str_replace($find, $replace, $value);
                    }

                    return '<a href="' . $url . '" target="_blank" class="go" title="' . Craft::t('app', 'Visit webpage') . '"><span dir="ltr">' . $value . '</span></a>';
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
                                // The field might not actually belong to this element
                                try {
                                    $value = $this->getFieldValue($field->handle);
                                } catch (\Throwable $e) {
                                    $value = $field->normalizeValue(null);
                                }
                            }

                            return $field->getTableAttributeHtml($value, $this);
                        }
                    }

                    return '';
                }

                $value = $this->$attribute;

                if ($value instanceof DateTime) {
                    $formatter = Craft::$app->getFormatter();

                    return '<span title="' . $formatter->asDatetime($value, Locale::LENGTH_SHORT) . '">' . $formatter->asTimestamp($value, Locale::LENGTH_SHORT) . '</span>';
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

    /**
     * Returns any attributes that should be included in the element’s DOM representation in the Control Panel.
     *
     * @param string $context The context that the element is being rendered in ('index', 'field', etc.)
     * @return array
     * @see getHtmlAttributes()
     */
    protected function htmlAttributes(string $context): array
    {
        return [];
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns an element right before/after this one, from a given set of criteria.
     *
     * @param mixed $criteria
     * @param int $dir
     * @return ElementInterface|null
     */
    private function _getRelativeElement($criteria, int $dir)
    {
        if ($this->id === null) {
            return null;
        }

        if ($criteria instanceof ElementQueryInterface) {
            /** @var ElementQuery $criteria */
            $query = clone $criteria;
        } else {
            $query = static::find()
                ->siteId($this->siteId);

            if ($criteria) {
                Craft::configure($query, $criteria);
            }
        }

        /** @var ElementQuery $query */
        $elementIds = $query->ids();
        $key = array_search($this->id, $elementIds, false);

        if ($key === false || !isset($elementIds[$key + $dir])) {
            return null;
        }

        return $query
            ->id($elementIds[$key + $dir])
            ->one();
    }
}
