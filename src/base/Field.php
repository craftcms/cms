<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use Craft;
use craft\db\Table as DbTable;
use craft\elements\db\ElementQueryInterface;
use craft\enums\AttributeStatus;
use craft\events\DefineFieldHtmlEvent;
use craft\events\DefineFieldKeywordsEvent;
use craft\events\DefineMenuItemsEvent;
use craft\events\FieldElementEvent;
use craft\events\FieldEvent;
use craft\gql\types\QueryArgument;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\models\GqlSchema;
use craft\records\Field as FieldRecord;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use DateTime;
use Exception;
use GraphQL\Type\Definition\Type;
use yii\base\Arrayable;
use yii\base\ErrorHandler;
use yii\base\InvalidArgumentException;
use yii\base\NotSupportedException;
use yii\db\ExpressionInterface;
use yii\db\Schema;

/**
 * Field is the base class for classes representing fields in terms of objects.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
abstract class Field extends SavableComponent implements FieldInterface, Iconic, Actionable
{
    use FieldTrait;

    // Events
    // -------------------------------------------------------------------------

    /**
     * @event FieldElementEvent The event that is triggered before the element is saved.
     *
     * You may set [[\yii\base\ModelEvent::$isValid]] to `false` to prevent the element from getting saved.
     */
    public const EVENT_BEFORE_ELEMENT_SAVE = 'beforeElementSave';

    /**
     * @event FieldElementEvent The event that is triggered after the element is saved.
     */
    public const EVENT_AFTER_ELEMENT_SAVE = 'afterElementSave';

    /**
     * @event FieldElementEvent The event that is triggered after the element is fully saved and propagated to other sites.
     * @since 3.2.0
     */
    public const EVENT_AFTER_ELEMENT_PROPAGATE = 'afterElementPropagate';

    /**
     * @event FieldElementEvent The event that is triggered before the element is deleted.
     *
     * You may set [[\yii\base\ModelEvent::$isValid]] to `false` to prevent the element from getting deleted.
     */
    public const EVENT_BEFORE_ELEMENT_DELETE = 'beforeElementDelete';

    /**
     * @event FieldElementEvent The event that is triggered after the element is deleted.
     */
    public const EVENT_AFTER_ELEMENT_DELETE = 'afterElementDelete';

    /**
     * @event FieldElementEvent The event that is triggered before the element is restored.
     *
     * You may set [[\yii\base\ModelEvent::$isValid]] to `false` to prevent the element from getting restored.
     *
     * @since 3.1.0
     */
    public const EVENT_BEFORE_ELEMENT_RESTORE = 'beforeElementRestore';

    /**
     * @event FieldElementEvent The event that is triggered after the element is restored.
     * @since 3.1.0
     */
    public const EVENT_AFTER_ELEMENT_RESTORE = 'afterElementRestore';

    /**
     * @event DefineFieldKeywordsEvent The event that is triggered when defining the field’s search keywords for an
     * element.
     *
     * Note that you _must_ set [[Event::$handled]] to `true` if you want the field to accept your custom
     * [[DefineFieldKeywordsEvent::$keywords|$keywords]] value.
     *
     * ```php
     * Event::on(
     *     craft\fields\Lightswitch::class,
     *     craft\base\Field::EVENT_DEFINE_KEYWORDS,
     *     function(craft\events\DefineFieldKeywordsEvent $e
     * ) {
     *     // @var craft\fields\Lightswitch $field
     *     $field = $e->sender;
     *
     *     if ($field->handle === 'fooOrBar') {
     *         // Override the keywords depending on whether the lightswitch is enabled or not
     *         $e->keywords = $e->value ? 'foo' : 'bar';
     *         $e->handled = true;
     *     }
     * });
     * ```
     *
     * @since 3.5.0
     */
    public const EVENT_DEFINE_KEYWORDS = 'defineKeywords';

    /**
     * @event DefineFieldHtmlEvent The event that is triggered when defining the field’s input HTML.
     * @since 3.5.0
     */
    public const EVENT_DEFINE_INPUT_HTML = 'defineInputHtml';

    /**
     * @vevent DefineMenuItemsEvent
     * @since 5.7.0
     */
    public const EVENT_DEFINE_ACTION_MENU_ITEMS = 'defineActionMenuItems';

    /**
     * @event FieldEvent The event that is triggered after the field has been merged into another.
     * @see afterMergeInto()
     * @since 5.3.0
     */
    public const EVENT_AFTER_MERGE_INTO = 'afterMergeInto';

    /**
     * @event FieldEvent The event that is triggered after another field has been merged into this one.
     * @see afterMergeFrom()
     * @since 5.3.0
     */
    public const EVENT_AFTER_MERGE_FROM = 'afterMergeFrom';

    // Translation methods
    // -------------------------------------------------------------------------

    public const TRANSLATION_METHOD_NONE = 'none';
    public const TRANSLATION_METHOD_SITE = 'site';
    public const TRANSLATION_METHOD_SITE_GROUP = 'siteGroup';
    public const TRANSLATION_METHOD_LANGUAGE = 'language';
    public const TRANSLATION_METHOD_CUSTOM = 'custom';

    // Reserved handles
    // -------------------------------------------------------------------------

    /** @since 5.8.0 */
    public const RESERVED_HANDLES = [
        'ancestors',
        'archived',
        'attributeLabel',
        'attributes',
        'awaitingFieldValues',
        'behavior',
        'behaviors',
        'canSetProperties',
        'canonical',
        'children',
        'contentTable',
        'dateCreated',
        'dateDeleted',
        'dateLastMerged',
        'dateUpdated',
        'descendants',
        'draftId',
        'duplicateOf',
        'enabled',
        'enabledForSite',
        'error',
        'errorSummary',
        'errors',
        'fieldLayoutId',
        'fieldValue',
        'fieldValues',
        'firstSave',
        'hardDelete',
        'hasMethods',
        'icon',
        'id',
        'isNewForSite',
        'isProvisionalDraft',
        'language',
        'level',
        'lft',
        'link',
        'localized',
        'localized',
        'mergingCanonicalChanges',
        'newSiteIds',
        'next',
        'nextSibling',
        'owner',
        'parent',
        'parents',
        'prev',
        'prevSibling',
        'previewing',
        'propagateAll',
        'propagating',
        'ref',
        'relatedToAssets',
        'relatedToCategories',
        'relatedToEntries',
        'relatedToTags',
        'relatedToUsers',
        'resaving',
        'revisionId',
        'rgt',
        'root',
        'scenario',
        'searchKeywords',
        'searchScore',
        'siblings',
        'site',
        'siteId',
        'siteSettingsId',
        'slug',
        'sortOrder',
        'status',
        'structureId',
        'tempId',
        'title',
        'trashed',
        'uid',
        'updatingFromDerivative',
        'uri',
        'url',
        'viewMode',
        'where',
    ];

    /**
     * @inheritdoc
     */
    public static function get(int|string $id): ?static
    {
        /** @phpstan-ignore-next-line */
        return Craft::$app->getFields()->getFieldById($id);
    }

    /**
     * @inheritdoc
     */
    public static function icon(): string
    {
        return 'i-cursor';
    }

    /**
     * @inheritdoc
     */
    public static function isMultiInstance(): bool
    {
        return static::dbType() !== null;
    }

    /**
     * @inheritdoc
     */
    public static function isRequirable(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function supportedTranslationMethods(): array
    {
        if (static::dbType() === null) {
            return [
                self::TRANSLATION_METHOD_NONE,
            ];
        }

        return [
            self::TRANSLATION_METHOD_NONE,
            self::TRANSLATION_METHOD_SITE,
            self::TRANSLATION_METHOD_SITE_GROUP,
            self::TRANSLATION_METHOD_LANGUAGE,
            self::TRANSLATION_METHOD_CUSTOM,
        ];
    }

    /**
     * @inheritdoc
     */
    public static function phpType(): string
    {
        return 'mixed';
    }

    /**
     * @inheritdoc
     */
    public static function dbType(): array|string|null
    {
        return Schema::TYPE_TEXT;
    }

    /**
     * @inheritdoc
     */
    public static function queryCondition(
        array $instances,
        mixed $value,
        array &$params,
    ): array|string|ExpressionInterface|false|null {
        $valueSql = static::valueSql($instances);

        if ($valueSql === null) {
            return false;
        }

        $caseInsensitive = false;

        if (is_array($value) && isset($value['value'])) {
            $caseInsensitive = $value['caseInsensitive'] ?? $caseInsensitive;
            $value = $value['value'];
        }

        return Db::parseParam($valueSql, $value, caseInsensitive: $caseInsensitive, columnType: Schema::TYPE_JSON);
    }

    /**
     * Returns a coalescing value SQL expression for the given field instances.
     *
     * @param static[] $instances
     * @param string|null $key The data key to fetch, if this field stores multiple values
     * @return string|null
     * @since 5.0.0
     */
    protected static function valueSql(array $instances, string $key = null): ?string
    {
        $valuesSql = array_filter(
            array_map(fn(self $field) => $field->getValueSql($key), $instances),
            fn(?string $valueSql) => $valueSql !== null,
        );

        if (empty($valuesSql)) {
            return null;
        }

        if (count($valuesSql) === 1) {
            return reset($valuesSql);
        }

        return sprintf('COALESCE(%s)', implode(',', $valuesSql));
    }

    /**
     * @var bool Whether the field handle’s uniqueness should be validated.
     * @since 5.0.0
     */
    public bool $validateHandleUniqueness = true;

    /**
     * @var bool|null Whether the field is fresh.
     * @see isFresh()
     * @see setIsFresh()
     */
    private ?bool $_isFresh = null;

    /**
     * @var array<string,string|false>
     * @see getValueSql()
     */
    private array $_valueSql;

    /**
     * Constructor
     */
    public function __construct($config = [])
    {
        // remove unused settings
        unset($config['columnPrefix']);

        parent::__construct($config);
    }

    /**
     * Use the translated field name as the string representation.
     *
     * @return string
     * @noinspection PhpInconsistentReturnPointsInspection
     */
    public function __toString(): string
    {
        try {
            return Craft::t('site', $this->name) ?: static::class;
        } catch (Exception $e) {
            ErrorHandler::convertExceptionToError($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        // Validate the translation method
        $supportedTranslationMethods = static::supportedTranslationMethods() ?: [self::TRANSLATION_METHOD_NONE];
        if (!in_array($this->translationMethod, $supportedTranslationMethods, true)) {
            $this->translationMethod = reset($supportedTranslationMethods);
        }

        if ($this->translationMethod !== self::TRANSLATION_METHOD_CUSTOM) {
            $this->translationKeyFormat = null;
        }
    }

    /**
     * @inheritdoc
     */
    public function attributes(): array
    {
        $names = parent::attributes();
        ArrayHelper::removeValue($names, 'validateHandleUniqueness');
        ArrayHelper::removeValue($names, 'layoutElement');
        ArrayHelper::removeValue($names, 'static');
        return $names;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'handle' => Craft::t('app', 'Handle'),
            'name' => Craft::t('app', 'Name'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['name', 'handle', 'translationMethod'], 'required'];

        $rules[] = [
            ['translationMethod'],
            'in',
            'range' => [
                self::TRANSLATION_METHOD_NONE,
                self::TRANSLATION_METHOD_SITE,
                self::TRANSLATION_METHOD_SITE_GROUP,
                self::TRANSLATION_METHOD_LANGUAGE,
                self::TRANSLATION_METHOD_CUSTOM,
            ],
        ];

        $rules[] = [['handle'], HandleValidator::class, 'reservedWords' => self::RESERVED_HANDLES];

        if ($this->validateHandleUniqueness) {
            $rules[] = [
                ['handle'],
                UniqueValidator::class,
                'targetClass' => FieldRecord::class,
                'targetAttribute' => ['handle', 'context'],
                'message' => Craft::t('yii', '{attribute} "{value}" has already been taken.'),
            ];
        }

        // Only validate the ID if it’s not a new field
        if (!$this->getIsNew()) {
            $rules[] = [['id'], 'number', 'integerOnly' => true];
        }

        if ($this->translationMethod === self::TRANSLATION_METHOD_CUSTOM) {
            $rules[] = [['translationKeyFormat'], 'required'];
        }

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getUiLabel(): string
    {
        return Craft::t('site', $this->name);
    }

    /**
     * @inheritdoc
     */
    public function getHandle(): ?string
    {
        return $this->handle;
    }

    /**
     * @inheritdoc
     */
    public function getIcon(): ?string
    {
        return static::icon();
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl(): ?string
    {
        if (!$this->id || !Craft::$app->getUser()->getIsAdmin()) {
            return null;
        }
        return UrlHelper::cpUrl("settings/fields/edit/$this->id");
    }

    /**
     * @inheritdoc
     */
    public function getActionMenuItems(): array
    {
        $items = $this->actionMenuItems();

        // Fire a 'defineActionMenuItems' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_ACTION_MENU_ITEMS)) {
            $event = new DefineMenuItemsEvent([
                'items' => $items,
            ]);
            $this->trigger(self::EVENT_DEFINE_ACTION_MENU_ITEMS, $event);
            return $event->items;
        }

        return $items;
    }

    protected function actionMenuItems(): array
    {
        $items = [];
        $userSessionService = Craft::$app->getUser();

        if ($this->id && $userSessionService->getIsAdmin()) {
            $view = Craft::$app->getView();

            if (Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
                // Edit field
                $editId = sprintf('action-edit-%s', mt_rand());
                $items[] = [
                    'id' => $editId,
                    'icon' => 'gear',
                    'label' => Craft::t('app', 'Field settings'),
                ];
                $view->registerJsWithVars(fn($id, $params) => <<<JS
(() => {
  $('#' + $id).on('activate', () => {
    new Craft.CpScreenSlideout('fields/edit-field', {
      params: $params,
    });
  });
})();
JS, [
                    $view->namespaceInputId($editId),
                    ['fieldId' => $this->id],
                ]);
            }

            // Copy field handle
            if (!$userSessionService->getIdentity()->getPreference('showFieldHandles')) {
                $copyId = sprintf('action-copy-handle-%s', mt_rand());
                $items[] = [
                    'id' => $copyId,
                    'icon' => 'clipboard',
                    'label' => Craft::t('app', 'Copy field handle'),
                ];
                $view->registerJsWithVars(fn($id, $attribute) => <<<JS
(() => {
  $('#' + $id).on('activate', () => {
    Craft.ui.createCopyTextPrompt({
      label: Craft.t('app', 'Field Handle'),
      value: $attribute,
    });
  });
})();
JS, [
                    $view->namespaceInputId($copyId),
                    $this->handle,
                ]);
            }
        }

        return $items;
    }

    /**
     * @inheritdoc
     */
    public function getOrientation(?ElementInterface $element): string
    {
        if (!Craft::$app->getIsMultiSite()) {
            // Only one site so use its language
            $locale = Craft::$app->getSites()->getPrimarySite()->getLocale();
        } elseif (!$element || !$this->getIsTranslatable($element)) {
            // Not translatable, so use the user’s language
            $locale = Craft::$app->getLocale();
        } else {
            // Use the site’s language
            $locale = $element->getSite()->getLocale();
        }

        return $locale->getOrientation();
    }

    /**
     * @inheritdoc
     */
    public function getIsTranslatable(?ElementInterface $element): bool
    {
        if ($this->translationMethod === self::TRANSLATION_METHOD_CUSTOM) {
            return $element === null || $this->getTranslationKey($element) !== '';
        }
        return $this->translationMethod !== self::TRANSLATION_METHOD_NONE;
    }

    /**
     * @inheritdoc
     */
    public function getTranslationDescription(?ElementInterface $element): ?string
    {
        if (!$this->getIsTranslatable($element)) {
            return null;
        }

        return ElementHelper::translationDescription($this->translationMethod);
    }

    /**
     * @inheritdoc
     */
    public function getTranslationKey(ElementInterface $element): string
    {
        return ElementHelper::translationKey($element, $this->translationMethod, $this->translationKeyFormat);
    }

    /**
     * @inheritdoc
     */
    public function showStatus(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getStatus(ElementInterface $element): ?array
    {
        if ($element->isFieldModified($this->handle)) {
            return [
                AttributeStatus::Modified,
                Craft::t('app', 'This field has been modified.'),
            ];
        }

        if ($element->isFieldOutdated($this->handle)) {
            return [
                AttributeStatus::Outdated,
                Craft::t('app', 'This field was updated in the Current revision.'),
            ];
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getInputId(): string
    {
        return Html::id($this->handle);
    }

    /**
     * @inheritdoc
     */
    public function getLabelId(): string
    {
        return sprintf('%s-label', $this->getInputId());
    }

    /**
     * @inheritdoc
     */
    public function useFieldset(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        return $value;
    }

    /**
     * @inheritdoc
     */
    public function normalizeValueFromRequest(mixed $value, ?ElementInterface $element): mixed
    {
        return $this->normalizeValue($value, $element);
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml(mixed $value, ?ElementInterface $element): string
    {
        $html = $this->inputHtml($value, $element, false);

        // Fire a 'defineInputHtml' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_INPUT_HTML)) {
            $event = new DefineFieldHtmlEvent([
                'value' => $value,
                'element' => $element,
                'inline' => false,
                'html' => $html,
            ]);
            $this->trigger(self::EVENT_DEFINE_INPUT_HTML, $event);
            return $event->html;
        }

        return $html;
    }

    /**
     * @see InlineEditableFieldInterface::getInlineInputHtml()
     * @since 5.0.0
     */
    public function getInlineInputHtml(mixed $value, ?ElementInterface $element): string
    {
        $html = $this->inputHtml($value, $element, true);

        // Fire a 'defineInputHtml' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_INPUT_HTML)) {
            $event = new DefineFieldHtmlEvent([
                'value' => $value,
                'element' => $element,
                'inline' => true,
                'html' => $html,
            ]);
            $this->trigger(self::EVENT_DEFINE_INPUT_HTML, $event);
            return $event->html;
        }

        return $html;
    }

    /**
     * Returns the field’s input HTML.
     *
     * @param mixed $value The field’s value. This will either be the [[normalizeValue()|normalized value]],
     * raw POST data (i.e. if there was a validation error), or null
     * @param ElementInterface|null $element The element the field is associated with, if there is one
     * @param bool $inline Whether this is for an inline edit form.
     * @return string The input HTML.
     * @see getInputHtml()
     * @since 3.5.0
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Html::textarea($this->handle, $value);
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        // Just return the input HTML with disabled inputs by default
        return Html::disableInputs(fn() => $this->getInputHtml($value, $element));
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function isValueEmpty(mixed $value, ElementInterface $element): bool
    {
        // Default to yii\validators\Validator::isEmpty()'s behavior
        return $value === null || $value === [] || $value === '';
    }

    /**
     * @inheritdoc
     */
    public function getSearchKeywords(mixed $value, ElementInterface $element): string
    {
        // Fire a 'defineKeywords' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_KEYWORDS)) {
            $event = new DefineFieldKeywordsEvent([
                'value' => $value,
                'element' => $element,
            ]);
            $this->trigger(self::EVENT_DEFINE_KEYWORDS, $event);
            if ($event->handled) {
                return $event->keywords;
            }
        }

        return $this->searchKeywords($value, $element);
    }

    /**
     * Returns the search keywords that should be associated with this field.
     *
     * The keywords can be separated by commas and/or whitespace; it doesn’t really matter. [[\craft\services\Search]]
     * will be able to find the individual keywords in whatever string is returned, and normalize them for you.
     *
     * @param mixed $value The field’s value
     * @param ElementInterface $element The element the field is associated with, if there is one
     * @return string A string of search keywords.
     * @since 3.5.0
     */
    protected function searchKeywords(mixed $value, ElementInterface $element): string
    {
        return StringHelper::toString($value, ' ');
    }

    /**
     * @see PreviewableFieldInterface::getPreviewHtml()
     * @since 5.0.0
     */
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        return ElementHelper::attributeHtml($value);
    }

    /**
     * @see PreviewableFieldInterface::previewPlaceholderHtml()
     * @since 5.5.0
     */
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (!$this instanceof PreviewableFieldInterface) {
            return '';
        }

        if ($value !== null) {
            return $value;
        }

        if ($element !== null) {
            return $element->getFieldValue($this->handle);
        }

        return $this->getUiLabel();
    }

    /**
     * @see SortableFieldInterface::getSortOption()
     * @since 3.2.0
     */
    public function getSortOption(): array
    {
        $dbType = static::dbType();
        if ($dbType === null || !isset($this->layoutElement)) {
            throw new NotSupportedException('getSortOption() not supported by ' . $this->name);
        }

        $orderBy = $this->getValueSql();

        // for mysql, we have to make sure text column type is cast to char, otherwise it won't be sorted correctly
        // see https://github.com/craftcms/cms/issues/15609
        $db = Craft::$app->getDb();
        if ($db->getIsMysql() && is_string($dbType) && Db::parseColumnType($dbType) === Schema::TYPE_TEXT) {
            $orderBy = "CAST($orderBy AS CHAR(255))";
        }

        // The attribute name should match the table attribute name,
        // per ElementSources::getTableAttributesForFieldLayouts()
        return [
            'label' => Craft::t('site', $this->name),
            'orderBy' => $orderBy,
            'attribute' => isset($this->layoutElement->handle)
                ? "fieldInstance:{$this->layoutElement->uid}"
                : "field:$this->uid",
        ];
    }

    /**
     * @see MergeableFieldInterface::canMergeInto()
     * @since 5.3.0
     */
    public function canMergeInto(FieldInterface $persistingField, ?string &$reason): bool
    {
        // Go with whether the DB types are compatible by default
        return Craft::$app->getFields()->areFieldTypesCompatible(static::class, $persistingField::class);
    }

    /**
     * @see MergeableFieldInterface::canMergeFrom()
     * @since 5.3.0
     */
    public function canMergeFrom(FieldInterface $outgoingField, ?string &$reason): bool
    {
        // Go with whether the DB types are compatible by default
        return Craft::$app->getFields()->areFieldTypesCompatible(static::class, $outgoingField::class);
    }

    /**
     * @see MergeableFieldInterface::afterMergeInto()
     * @since 5.3.0
     */
    public function afterMergeInto(FieldInterface $persistingField)
    {
        // Fire an 'afterMergeInto' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_MERGE_INTO)) {
            $this->trigger(self::EVENT_AFTER_MERGE_INTO, new FieldEvent(['field' => $persistingField]));
        }
    }

    /**
     * @see MergeableFieldInterface::afterMergeFrom()
     * @since 5.3.0
     */
    public function afterMergeFrom(FieldInterface $outgoingField)
    {
        if ($this instanceof RelationalFieldInterface) {
            Db::update(DbTable::RELATIONS, ['fieldId' => $this->id], ['fieldId' => $outgoingField->id]);
        }

        // Fire an 'afterMergeFrom' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_MERGE_FROM)) {
            $this->trigger(self::EVENT_AFTER_MERGE_FROM, new FieldEvent(['field' => $outgoingField]));
        }
    }

    /**
     * @inheritdoc
     */
    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        // If the object explicitly defines its savable value, use that
        if ($value instanceof Serializable) {
            return $value->serialize();
        }

        // If it's "arrayable", convert to array
        if ($value instanceof Arrayable) {
            return $value->toArray();
        }

        // Only DateTime objects and ISO-8601 strings should automatically be detected as dates
        if ($value instanceof DateTime || DateTimeHelper::isIso8601($value)) {
            return DateTimeHelper::toIso8601($value);
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function serializeValueForDb(mixed $value, ElementInterface $element): mixed
    {
        // Dates should be stored in UTC w/o the time zone
        if ($value instanceof DateTime || DateTimeHelper::isIso8601($value)) {
            return Db::prepareDateForDb($value);
        }

        return $this->serializeValue($value, $element);
    }

    /**
     * @inheritdoc
     */
    public function copyValue(ElementInterface $from, ElementInterface $to): void
    {
        $value = $this->serializeValue($from->getFieldValue($this->handle), $from);
        $to->setFieldValue($this->handle, $value);
    }

    /**
     * @see CrossSiteCopyableFieldInterface::copyCrossSiteValue()
     * @since 5.6.0
     */
    public function copyCrossSiteValue(ElementInterface $from, ElementInterface $to): void
    {
        $this->copyValue($from, $to);
    }

    /**
     * @inheritdoc
     */
    public function getElementConditionRuleType(): array|string|null
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getValueSql(?string $key = null): ?string
    {
        if (!isset($this->layoutElement)) {
            return null;
        }

        $cacheKey = $key ?? '*';
        $this->_valueSql[$cacheKey] ??= $this->_valueSql($key) ?? false;
        return $this->_valueSql[$cacheKey] ?: null;
    }

    private function _valueSql(?string $key): ?string
    {
        $dbType = $this->dbTypeForValueSql();

        if ($dbType === null) {
            return null;
        }

        if ($key !== null && (!is_array($dbType) || !isset($dbType[$key]))) {
            throw new InvalidArgumentException(sprintf('%s doesn’t store values under the key “%s”.', self::class, $key));
        }

        $db = Craft::$app->getDb();
        $qb = $db->getQueryBuilder();
        $sql = $qb->jsonExtract('elements_sites.content', [$this->layoutElement->uid]);

        if (is_array($dbType)) {
            // Get the primary value by default
            $key ??= array_key_first($dbType);
            $dbType = $dbType[$key];
            $sql = sprintf('COALESCE(%s, %s)', $qb->jsonExtract(
                'elements_sites.content',
                [$this->layoutElement->uid, $key],
            ), $sql);
        }

        $castType = null;
        if ($db->getIsMysql()) {
            // If the field uses an optimized DB type, cast it so its values can be indexed
            // (see "Functional Key Parts" on https://dev.mysql.com/doc/refman/8.0/en/create-index.html)
            $castType = match (Db::parseColumnType($dbType)) {
                Schema::TYPE_CHAR,
                Schema::TYPE_STRING,
                'varchar' => 'CHAR(255)',
                // only reliable way to compare booleans is as 'true'/'false' strings :(
                Schema::TYPE_BOOLEAN => 'CHAR(5)',
                Schema::TYPE_DATE => 'DATE',
                Schema::TYPE_DATETIME => 'DATETIME',
                Schema::TYPE_DECIMAL => 'DECIMAL',
                Schema::TYPE_DOUBLE => 'DOUBLE',
                Schema::TYPE_FLOAT => 'FLOAT',
                Schema::TYPE_TINYINT,
                Schema::TYPE_SMALLINT,
                Schema::TYPE_INTEGER,
                Schema::TYPE_BIGINT => 'SIGNED',
                SCHEMA::TYPE_TIME => 'TIME',
                default => null,
            };
        }

        // for pgsql, we have to make sure decimals column type is cast to decimal, otherwise they won't be sorted correctly
        // see https://github.com/craftcms/cms/issues/15828, https://github.com/craftcms/cms/issues/15973
        if ($db->getIsPgsql()) {
            $castType = match (Db::parseColumnType($dbType)) {
                Schema::TYPE_DECIMAL => 'DECIMAL',
                Schema::TYPE_INTEGER => 'INTEGER',
                default => null,
            };
        }

        if ($castType !== null) {
            // if a length was specified, replace the default with that
            $length = Db::parseColumnLength($dbType);
            if ($length) {
                $castType = preg_replace('/\(\d+\)/', "($length)", $castType);
            } elseif ($castType === 'DECIMAL') {
                [$precision, $scale] = Db::parseColumnPrecisionAndScale($dbType) ?? [null, null];
                if ($precision && $scale) {
                    $castType .= "($precision,$scale)";
                }
            }

            $sql = "CAST($sql AS $castType)";
        }

        return $sql;
    }

    /**
     * Returns the DB data type(s) that this field will store within the `elements_sites.content` column.
     *
     * @see dbType()
     * @return string|string[]|null The data type(s).
     * @since 5.6.0
     */
    protected function dbTypeForValueSql(): array|string|null
    {
        return static::dbType();
    }

    /**
     * @inheritdoc
     */
    public function modifyElementIndexQuery(ElementQueryInterface $query): void
    {
        if ($this instanceof EagerLoadingFieldInterface) {
            $query->andWith($this->handle);
        }
    }

    /**
     * @inheritdoc
     */
    public function setIsFresh(?bool $isFresh = null): void
    {
        $this->_isFresh = $isFresh;
    }

    /**
     * @inheritdoc
     */
    public function includeInGqlSchema(GqlSchema $schema): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getContentGqlType(): Type|array
    {
        return Type::string();
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function getContentGqlMutationArgumentType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => Type::string(),
            'description' => $this->instructions,
        ];
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function getContentGqlQueryArgumentType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => Type::listOf(QueryArgument::getType()),
        ];
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function beforeSave(bool $isNew): bool
    {
        // Set the field context if it’s not set
        if (!$this->context) {
            $this->context = Craft::$app->getFields()->fieldContext;
        }

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function beforeElementSave(ElementInterface $element, bool $isNew): bool
    {
        // Fire a 'beforeElementSave' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_ELEMENT_SAVE)) {
            $event = new FieldElementEvent([
                'element' => $element,
                'isNew' => $isNew,
            ]);
            $this->trigger(self::EVENT_BEFORE_ELEMENT_SAVE, $event);
            return $event->isValid;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterElementSave(ElementInterface $element, bool $isNew): void
    {
        // Fire an 'afterElementSave' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_ELEMENT_SAVE)) {
            $this->trigger(self::EVENT_AFTER_ELEMENT_SAVE, new FieldElementEvent([
                'element' => $element,
                'isNew' => $isNew,
            ]));
        }
    }

    /**
     * @inheritdoc
     */
    public function afterElementPropagate(ElementInterface $element, bool $isNew): void
    {
        // Fire an 'afterElementPropagate' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_ELEMENT_PROPAGATE)) {
            $this->trigger(self::EVENT_AFTER_ELEMENT_PROPAGATE, new FieldElementEvent([
                'element' => $element,
                'isNew' => $isNew,
            ]));
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeElementDelete(ElementInterface $element): bool
    {
        // Fire a 'beforeElementDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_ELEMENT_DELETE)) {
            $event = new FieldElementEvent(['element' => $element]);
            $this->trigger(self::EVENT_BEFORE_ELEMENT_DELETE, $event);
            return $event->isValid;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterElementDelete(ElementInterface $element): void
    {
        // Fire an 'afterElementDelete' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_ELEMENT_DELETE)) {
            $this->trigger(self::EVENT_AFTER_ELEMENT_DELETE, new FieldElementEvent([
                'element' => $element,
            ]));
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeElementDeleteForSite(ElementInterface $element): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterElementDeleteForSite(ElementInterface $element): void
    {
        // carry on
    }

    /**
     * @inheritdoc
     */
    public function beforeElementRestore(ElementInterface $element): bool
    {
        // Fire a 'beforeElementRestore' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_ELEMENT_RESTORE)) {
            $event = new FieldElementEvent(['element' => $element]);
            $this->trigger(self::EVENT_BEFORE_ELEMENT_RESTORE, $event);
            return $event->isValid;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterElementRestore(ElementInterface $element): void
    {
        // Fire an 'afterElementRestore' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_ELEMENT_RESTORE)) {
            $this->trigger(self::EVENT_AFTER_ELEMENT_RESTORE, new FieldElementEvent([
                'element' => $element,
            ]));
        }
    }

    /**
     * @see EagerLoadingFieldInterface::getEagerLoadingGqlConditions()
     * @since 3.3.0
     */
    public function getEagerLoadingGqlConditions(): ?array
    {
        // No restrictions
        return [];
    }

    /**
     * Returns the field’s param name on the request.
     *
     * @param ElementInterface $element The element this field is associated with
     * @return string|null The field’s param name on the request
     */
    protected function requestParamName(ElementInterface $element): ?string
    {
        $namespace = $element->getFieldParamNamespace();
        return ($namespace ? $namespace . '.' : '') . $this->handle;
    }

    /**
     * Returns whether this is the first time the element’s content has been edited.
     *
     * @param ElementInterface|null $element
     * @return bool
     */
    protected function isFresh(?ElementInterface $element = null): bool
    {
        if (isset($this->_isFresh)) {
            return $this->_isFresh;
        }

        if ($element) {
            return $element->getIsFresh();
        }

        return true;
    }
}
