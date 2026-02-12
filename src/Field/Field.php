<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Craft;
use craft\base\ElementInterface;
use craft\base\Serializable;
use craft\gql\types\QueryArgument;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db as DbHelper;
use craft\helpers\ElementHelper;
use craft\helpers\UrlHelper;
use craft\models\GqlSchema;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Component\Concerns\ConfigurableComponent;
use CraftCms\Cms\Component\Concerns\HasComponentEvents;
use CraftCms\Cms\Component\Concerns\SavableComponent;
use CraftCms\Cms\Component\Contracts\Actionable;
use CraftCms\Cms\Component\Contracts\Iconic;
use CraftCms\Cms\Component\Events\ComponentEvent;
use CraftCms\Cms\Database\Expressions\Cast;
use CraftCms\Cms\Database\Expressions\JsonExtract;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Enums\AttributeStatus;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Field\Contracts\EagerLoadingFieldInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Contracts\PreviewableFieldInterface;
use CraftCms\Cms\Field\Contracts\RelationalFieldInterface;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\Field\Events\DefineFieldActionMenuItems;
use CraftCms\Cms\Field\Events\DefineFieldHtml;
use CraftCms\Cms\Field\Events\DefineFieldKeywords;
use CraftCms\Cms\Field\Events\FieldElementEvent;
use CraftCms\Cms\Field\Events\FieldEvent;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Validation\Rules\HandleRule;
use DateTime;
use GraphQL\Type\Definition\Type;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use RuntimeException;
use Stringable;
use Tpetry\QueryExpressions\Function\Conditional\Coalesce;

use function CraftCms\Cms\t;

abstract class Field extends Component implements Actionable, FieldInterface, Iconic, Stringable
{
    use ConfigurableComponent;
    use HasComponentEvents;
    use Macroable;
    use SavableComponent;

    // Translation methods
    // @TODO: Replace const with the enum everywhere
    // -------------------------------------------------------------------------

    public const string TRANSLATION_METHOD_NONE = TranslationMethod::None->value;

    public const string TRANSLATION_METHOD_SITE = TranslationMethod::Site->value;

    public const string TRANSLATION_METHOD_SITE_GROUP = TranslationMethod::SiteGroup->value;

    public const string TRANSLATION_METHOD_LANGUAGE = TranslationMethod::Language->value;

    public const string TRANSLATION_METHOD_CUSTOM = TranslationMethod::Custom->value;

    // Component events
    // -------------------------------------------------------------------------

    /**
     * @event DefineFieldHtml The event that is triggered when defining the field’s input HTML.
     */
    public const string EVENT_DEFINE_INPUT_HTML = 'defineInputHtml';

    /**
     * @vevent DefineFieldActionMenuItems
     */
    public const string EVENT_DEFINE_ACTION_MENU_ITEMS = 'defineActionMenuItems';

    /**
     * @event DefineFieldKeywordsEvent The event that is triggered when defining the field’s search keywords for an
     * element.
     *
     * Note that you _must_ set [[Event::$handled]] to `true` if you want the field to accept your custom
     * [[DefineFieldKeywordsEvent::$keywords|$keywords]] value.
     *
     * ```php
     * \CraftCms\Cms\Field\Lightswitch::listen(
     *     \CraftCms\Cms\Field\Lightswitch::EVENT_DEFINE_KEYWORDS,
     *     function(\CraftCms\Cms\Field\Events\DefineFieldKeywords $e
     * ) {
     *     // @var craft\fields\Lightswitch $field
     *     $field = $e->field;
     *
     *     if ($field->handle === 'fooOrBar') {
     *         // Override the keywords depending on whether the lightswitch is enabled or not
     *         $e->keywords = $e->value ? 'foo' : 'bar';
     *         $e->handled = true;
     *     }
     * });
     * ```
     */
    public const string EVENT_DEFINE_KEYWORDS = 'defineKeywords';

    /**
     * @event FieldEvent The event that is triggered after the field has been merged into another.
     *
     * @see afterMergeInto()
     */
    public const string EVENT_AFTER_MERGE_INTO = 'afterMergeInto';

    /**
     * @event FieldEvent The event that is triggered after another field has been merged into this one.
     *
     * @see afterMergeFrom()
     */
    public const string EVENT_AFTER_MERGE_FROM = 'afterMergeFrom';

    /**
     * @event ComponentEvent The event that is triggered before the component is saved.
     *
     * You may set [[ComponentEvent::$isValid]] to `false` to prevent the component from getting saved.
     */
    public const string EVENT_BEFORE_SAVE = 'beforeSave';

    /**
     * @event ComponentEvent The event that is triggered after the component is saved.
     */
    public const string EVENT_AFTER_SAVE = 'afterSave';

    /**
     * @event FieldElementEvent The event that is triggered before the element is saved.
     *
     * You may set [[\yii\base\ModelEvent::$isValid]] to `false` to prevent the element from getting saved.
     */
    public const string EVENT_BEFORE_ELEMENT_SAVE = 'beforeElementSave';

    /**
     * @event FieldElementEvent The event that is triggered after the element is saved.
     */
    public const string EVENT_AFTER_ELEMENT_SAVE = 'afterElementSave';

    /**
     * @event FieldElementEvent The event that is triggered after the element is fully saved and propagated to other sites.
     */
    public const string EVENT_AFTER_ELEMENT_PROPAGATE = 'afterElementPropagate';

    /**
     * @event FieldElementEvent The event that is triggered before the element is deleted.
     *
     * You may set [[\yii\base\ModelEvent::$isValid]] to `false` to prevent the element from getting deleted.
     */
    public const string EVENT_BEFORE_ELEMENT_DELETE = 'beforeElementDelete';

    /**
     * @event FieldElementEvent The event that is triggered after the element is deleted.
     */
    public const string EVENT_AFTER_ELEMENT_DELETE = 'afterElementDelete';

    /**
     * @event FieldElementEvent The event that is triggered before the element is restored.
     *
     * You may set [[\yii\base\ModelEvent::$isValid]] to `false` to prevent the element from getting restored.
     */
    public const string EVENT_BEFORE_ELEMENT_RESTORE = 'beforeElementRestore';

    /**
     * @event FieldElementEvent The event that is triggered after the element is restored.
     */
    public const string EVENT_AFTER_ELEMENT_RESTORE = 'afterElementRestore';

    // Properties
    // -------------------------------------------------------------------------

    /** @var string|null The field’s name */
    public ?string $name = null;

    /** @var string|null The field’s handle */
    public ?string $handle = null;

    /** @var string|null The field’s context */
    public ?string $context = null;

    /** @var string|null The field’s instructions */
    public ?string $instructions = null;

    /** @var bool Whether the field's values should be registered as search keywords on the elements. */
    public bool $searchable = false;

    /**
     * @var string|null The `aria-describedby` attribute value that should be set on the focusable input(s).
     *
     * @see FieldInterface::getInputHtml()
     */
    public ?string $describedBy = null;

    /**
     * @var string The field’s translation method
     *
     * @phpstan-var self::TRANSLATION_METHOD_*
     */
    public string $translationMethod = self::TRANSLATION_METHOD_NONE;

    /** @var string|null The field’s translation key format, if [[translationMethod]] is "custom" */
    public ?string $translationKeyFormat = null;

    /** @var string|null The field’s previous handle */
    public ?string $oldHandle = null;

    /** @var array|null The field’s previous settings */
    public ?array $oldSettings = null;

    /** @var string|null The field's UID */
    public ?string $uid = null;

    /** @var DateTime|null The date that the field was trashed */
    public ?DateTime $dateDeleted = null;

    /** @var CustomField|null The field layout element */
    public ?CustomField $layoutElement = null;

    /** @var bool Whether the field is being displayed statically. */
    public bool $static = false;

    public const array RESERVED_HANDLES = [
        'ancestors',
        'applyingDraft',
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
        'propagateRequired',
        'propagating',
        'ref',
        'relatedToAssets',
        'relatedToEntries',
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
     * @var bool Whether the field handle’s uniqueness should be validated.
     */
    public bool $validateHandleUniqueness = true;

    /**
     * @var bool|null Whether the field is fresh.
     *
     * @see isFresh()
     * @see setIsFresh()
     */
    private ?bool $_isFresh = null;

    /**
     * @var array<string,string|false>
     *
     * @see getValueSql()
     */
    private array $_valueSql;

    /**
     * Create a new field instance
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        // Validate the translation method
        $supportedTranslationMethods = static::supportedTranslationMethods() ?: [self::TRANSLATION_METHOD_NONE];
        if (! in_array($this->translationMethod, $supportedTranslationMethods, true)) {
            $this->translationMethod = reset($supportedTranslationMethods);
        }

        if ($this->translationMethod !== self::TRANSLATION_METHOD_CUSTOM) {
            $this->translationKeyFormat = null;
        }
    }

    /** {@inheritdoc} */
    public static function get(int|string $id): ?static
    {
        /** @var ?static $field */
        $field = Fields::getFieldById($id);

        return $field;
    }

    /** {@inheritdoc} */
    public static function icon(): string
    {
        return 'i-cursor';
    }

    /** {@inheritdoc} */
    public static function isMultiInstance(): bool
    {
        return static::dbType() !== null;
    }

    /** {@inheritdoc} */
    public static function isRequirable(): bool
    {
        return true;
    }

    /** {@inheritdoc} */
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
     * {@inheritdoc}
     */
    public static function phpType(): string
    {
        return 'mixed';
    }

    /**
     * {@inheritdoc}
     */
    public static function dbType(): array|string|null
    {
        return Query::TYPE_TEXT;
    }

    public static function modifyQuery(Builder $query, array $instances, mixed $value): Builder
    {
        $valueSql = static::valueSql($instances);

        if ($valueSql === null) {
            return $query;
        }

        $caseInsensitive = false;

        if (is_array($value) && isset($value['value'])) {
            $caseInsensitive = $value['caseInsensitive'] ?? $caseInsensitive;
            $value = $value['value'];
        }

        return $query->whereParam(
            column: $valueSql,
            param: $value,
            caseInsensitive: $caseInsensitive,
            columnType: Query::TYPE_JSON,
        );
    }

    /**
     * Returns a coalescing value SQL expression for the given field instances.
     *
     * @param  static[]  $instances
     * @param  string|null  $key  The data key to fetch, if this field stores multiple values
     */
    protected static function valueSql(array $instances, ?string $key = null): string|Expression|null
    {
        $valuesSql = array_filter(
            array_map(fn (self $field) => $field->getValueSql($key), $instances),
            fn (string|Expression|null $valueSql) => $valueSql !== null,
        );

        if (empty($valuesSql)) {
            return null;
        }

        if (count($valuesSql) === 1) {
            return reset($valuesSql);
        }

        return new Coalesce($valuesSql);
    }

    /**
     * Use the translated field name as the string representation.
     */
    public function __toString(): string
    {
        return t($this->name, category: 'site') ?: static::class;
    }

    public function attributes(): array
    {
        return Collection::make($this->settingsAttributes())
            ->reject(fn ($name): bool => in_array($name, [
                'validateHandleUniqueness',
                'layoutElement',
                'static',
            ]))
            ->all();
    }

    public function attributeLabels(): array
    {
        return [
            'handle' => t('Handle'),
            'name' => t('Name'),
        ];
    }

    public function getRules(): array
    {
        return [
            'name' => ['required'],
            'handle' => [
                'required',
                new HandleRule(self::RESERVED_HANDLES),
            ],
            'translationMethod' => [
                'required',
                Rule::in([
                    self::TRANSLATION_METHOD_NONE,
                    self::TRANSLATION_METHOD_SITE,
                    self::TRANSLATION_METHOD_SITE_GROUP,
                    self::TRANSLATION_METHOD_LANGUAGE,
                    self::TRANSLATION_METHOD_CUSTOM,
                ]),
            ],
            'translationKeyFormat' => [
                'nullable',
                'required_if:translationMethod,'.self::TRANSLATION_METHOD_CUSTOM,
            ],
        ];
    }

    /** {@inheritdoc} */
    public function getId(): ?int
    {
        return $this->id;
    }

    /** {@inheritdoc} */
    public function getUiLabel(): string
    {
        return t($this->name, category: 'site');
    }

    /** {@inheritdoc} */
    public function getHandle(): ?string
    {
        return $this->handle;
    }

    /** {@inheritdoc} */
    public function getIcon(): ?string
    {
        return static::icon();
    }

    /** {@inheritdoc} */
    public function getCpEditUrl(): ?string
    {
        if (! $this->id || ! Auth::user()?->isAdmin()) {
            return null;
        }

        return UrlHelper::cpUrl("settings/fields/edit/$this->id");
    }

    /** {@inheritdoc} */
    public function getActionMenuItems(): array
    {
        $items = $this->actionMenuItems();

        $this->dispatchComponentEvent(self::EVENT_DEFINE_ACTION_MENU_ITEMS, $event = new DefineFieldActionMenuItems($this, $items));

        return $event->items;
    }

    protected function actionMenuItems(): array
    {
        $items = [];

        if (! $this->id) {
            return $items;
        }

        if (! Auth::user()?->isAdmin()) {
            return $items;
        }

        $view = Craft::$app->getView();

        if (Cms::config()->allowAdminChanges) {
            // Edit field
            $editId = sprintf('action-edit-%s', mt_rand());
            $items[] = [
                'id' => $editId,
                'icon' => 'gear',
                'label' => t('Field settings'),
            ];
            $view->registerJsWithVars(fn ($id, $params) => <<<JS
(() => {
$('#' + $id).on('activate', () => {
new Craft.CpScreenSlideout('fields/edit-field', {
  params: $params,
})
});
})();
JS, [
                $view->namespaceInputId($editId),
                ['fieldId' => $this->id],
            ]);
        }

        return $items;
    }

    /** {@inheritdoc} */
    public function getOrientation(?ElementInterface $element): string
    {
        $locale = match (true) {
            // Only one site so use its language
            ! Sites::isMultiSite() => Sites::getPrimarySite()->getLocale(),
            // Not translatable, so use the user’s language
            ! $element || ! $this->getIsTranslatable($element) => I18N::getLocale(),
            // Use the site’s language
            default => $element->getSite()->getLocale(),
        };

        return $locale->getOrientation();
    }

    /** {@inheritdoc} */
    public function getIsTranslatable(?ElementInterface $element): bool
    {
        if ($this->translationMethod === self::TRANSLATION_METHOD_CUSTOM) {
            return $element === null || $this->getTranslationKey($element) !== '';
        }

        return $this->translationMethod !== self::TRANSLATION_METHOD_NONE;
    }

    /** {@inheritdoc} */
    public function getTranslationDescription(?ElementInterface $element): ?string
    {
        if (! $this->getIsTranslatable($element)) {
            return null;
        }

        return ElementHelper::translationDescription($this->translationMethod);
    }

    /** {@inheritdoc} */
    public function getTranslationKey(ElementInterface $element): string
    {
        return ElementHelper::translationKey($element, $this->translationMethod, $this->translationKeyFormat);
    }

    /** {@inheritdoc} */
    public function showStatus(): bool
    {
        return true;
    }

    /** {@inheritdoc} */
    public function getStatus(ElementInterface $element): ?array
    {
        if ($element->isFieldModified($this->handle)) {
            return [
                AttributeStatus::Modified,
                t('This field has been modified.'),
            ];
        }

        if ($element->isFieldOutdated($this->handle)) {
            return [
                AttributeStatus::Outdated,
                t('This field was updated in the Current revision.'),
            ];
        }

        return null;
    }

    /** {@inheritdoc} */
    public function getInputId(): string
    {
        return Html::id($this->handle);
    }

    /** {@inheritdoc} */
    public function getLabelId(): string
    {
        return sprintf('%s-label', $this->getInputId());
    }

    /** {@inheritdoc} */
    public function useFieldset(): bool
    {
        return false;
    }

    /** {@inheritdoc} */
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        return $value;
    }

    /** {@inheritdoc} */
    public function normalizeValueFromRequest(mixed $value, ?ElementInterface $element): mixed
    {
        return $this->normalizeValue($value, $element);
    }

    /** {@inheritdoc} */
    public function getInputHtml(mixed $value, ?ElementInterface $element): string
    {
        $html = $this->inputHtml($value, $element, false);

        $this->dispatchComponentEvent(static::EVENT_DEFINE_INPUT_HTML, $event = new DefineFieldHtml(
            field: $this,
            value: $value,
            inline: false,
            element: $element,
            html: $html,
        ));

        return $event->html;
    }

    /**
     * @see InlineEditableFieldInterface::getInlineInputHtml()
     */
    public function getInlineInputHtml(mixed $value, ?ElementInterface $element): string
    {
        $html = $this->inputHtml($value, $element, true);

        $this->dispatchComponentEvent(static::EVENT_DEFINE_INPUT_HTML, $event = new DefineFieldHtml(
            field: $this,
            value: $value,
            inline: true,
            element: $element,
            html: $html,
        ));

        return $event->html;
    }

    /**
     * Returns the field’s input HTML.
     *
     * @param  mixed  $value  The field’s value. This will either be the [[normalizeValue()|normalized value]],
     *                        raw POST data (i.e. if there was a validation error), or null
     * @param  ElementInterface|null  $element  The element the field is associated with, if there is one
     * @param  bool  $inline  Whether this is for an inline edit form.
     * @return string The input HTML.
     *
     * @see getInputHtml()
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Html::textarea($this->handle, $value)->render();
    }

    /**
     * {@inheritdoc}
     */
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        // Just return the input HTML with disabled inputs by default
        return Html::disableInputs(fn () => $this->getInputHtml($value, $element));
    }

    /**
     * {@inheritDoc}
     */
    public function prepareForElementValidation(mixed $value): mixed
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getElementRules(ElementInterface $element): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function isValueEmpty(mixed $value, ElementInterface $element): bool
    {
        // Default to yii\validators\Validator::isEmpty()'s behavior
        return $value === null || $value === [] || $value === '';
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchKeywords(mixed $value, ElementInterface $element): string
    {
        $this->dispatchComponentEvent(self::EVENT_DEFINE_KEYWORDS, $event = new DefineFieldKeywords(
            field: $this,
            element: $element,
            value: $value,
        ));

        if ($event->handled) {
            return $event->keywords;
        }

        return $this->searchKeywords($value, $element);
    }

    /**
     * Returns the search keywords that should be associated with this field.
     *
     * The keywords can be separated by commas and/or whitespace; it doesn’t really matter. [[\craft\services\Search]]
     * will be able to find the individual keywords in whatever string is returned, and normalize them for you.
     *
     * @param  mixed  $value  The field’s value
     * @param  ElementInterface  $element  The element the field is associated with, if there is one
     * @return string A string of search keywords.
     */
    protected function searchKeywords(mixed $value, ElementInterface $element): string
    {
        return Str::toString($value, ' ');
    }

    /**
     * @see PreviewableFieldInterface::getPreviewHtml()
     */
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        return ElementHelper::attributeHtml($value);
    }

    /**
     * @see PreviewableFieldInterface::previewPlaceholderHtml()
     */
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (! $this instanceof PreviewableFieldInterface) {
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
     */
    public function getSortOption(): array
    {
        $dbType = static::dbType();
        if ($dbType === null || ! isset($this->layoutElement)) {
            throw new RuntimeException('getSortOption() not supported by '.$this->name);
        }

        $orderBy = $this->getValueSql();

        // for mysql, we have to make sure text column type is cast to char, otherwise it won't be sorted correctly
        // see https://github.com/craftcms/cms/issues/15609
        $db = Craft::$app->getDb();
        if ($db->getIsMysql() && is_string($dbType) && Query::parseColumnType($dbType) === Query::TYPE_TEXT) {
            $orderBy = new Cast($orderBy, 'CHAR(255)');
        }

        // The attribute name should match the table attribute name,
        // per ElementSources::getTableAttributesForFieldLayouts()
        return [
            'label' => t($this->name, category: 'site'),
            'orderBy' => $orderBy,
            'attribute' => isset($this->layoutElement->handle)
                ? "fieldInstance:{$this->layoutElement->uid}"
                : "field:$this->uid",
        ];
    }

    /**
     * @see MergeableFieldInterface::canMergeInto()
     */
    public function canMergeInto(FieldInterface $persistingField, ?string &$reason): bool
    {
        // Go with whether the DB types are compatible by default
        return Fields::areFieldTypesCompatible(static::class, $persistingField::class);
    }

    /**
     * @see MergeableFieldInterface::canMergeFrom()
     */
    public function canMergeFrom(FieldInterface $outgoingField, ?string &$reason): bool
    {
        // Go with whether the DB types are compatible by default
        return Fields::areFieldTypesCompatible(static::class, $outgoingField::class);
    }

    /**
     * @see MergeableFieldInterface::afterMergeInto()
     */
    public function afterMergeInto(FieldInterface $persistingField)
    {
        $this->dispatchComponentEvent(self::EVENT_AFTER_MERGE_INTO, new FieldEvent($persistingField));
    }

    /**
     * @see MergeableFieldInterface::afterMergeFrom()
     */
    public function afterMergeFrom(FieldInterface $outgoingField)
    {
        if ($this instanceof RelationalFieldInterface) {
            DB::table(Table::RELATIONS)
                ->where('fieldId', $outgoingField->id)
                ->update([
                    'fieldId' => $this->id,
                    'dateUpdated' => now(),
                ]);
        }

        $this->dispatchComponentEvent(self::EVENT_AFTER_MERGE_FROM, new FieldEvent($outgoingField));
    }

    /** {@inheritdoc} */
    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        // If the object explicitly defines its savable value, use that
        if ($value instanceof Serializable) {
            return $value->serialize();
        }

        // If it's "arrayable", convert to array
        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        // Only DateTime objects and ISO-8601 strings should automatically be detected as dates
        if ($value instanceof DateTime || DateTimeHelper::isIso8601($value)) {
            return DateTimeHelper::toIso8601($value);
        }

        return $value;
    }

    /** {@inheritdoc} */
    public function serializeValueForDb(mixed $value, ElementInterface $element): mixed
    {
        // Dates should be stored in UTC w/o the time zone
        if ($value instanceof DateTime || DateTimeHelper::isIso8601($value)) {
            return DbHelper::prepareDateForDb($value);
        }

        return $this->serializeValue($value, $element);
    }

    /** {@inheritdoc} */
    public function copyValue(ElementInterface $from, ElementInterface $to): void
    {
        $value = $this->serializeValue($from->getFieldValue($this->handle), $from);

        $to->setFieldValue($this->handle, $value);
    }

    /**
     * @see CrossSiteCopyableFieldInterface::copyCrossSiteValue()
     */
    public function copyCrossSiteValue(ElementInterface $from, ElementInterface $to): void
    {
        $this->copyValue($from, $to);
    }

    /** {@inheritdoc} */
    public function getElementConditionRuleType(): array|string|null
    {
        return null;
    }

    /** {@inheritdoc} */
    public function getValueSql(?string $key = null): string|Expression|null
    {
        if (! isset($this->layoutElement)) {
            return null;
        }

        $cacheKey = $key ?? '*';
        $this->_valueSql[$cacheKey] ??= $this->_valueSql($key) ?? false;

        return $this->_valueSql[$cacheKey] ?: null;
    }

    private function _valueSql(?string $key): ?Expression
    {
        $dbType = $this->dbTypeForValueSql();

        if ($dbType === null) {
            return null;
        }

        if ($key !== null && (! is_array($dbType) || ! isset($dbType[$key]))) {
            throw new InvalidArgumentException(sprintf('%s doesn’t store values under the key “%s”.', self::class, $key));
        }

        $sql = new JsonExtract('elements_sites.content', $this->layoutElement->uid);

        if (is_array($dbType)) {
            // Get the primary value by default
            $key ??= array_key_first($dbType);
            $dbType = $dbType[$key];
            $sql = new Coalesce([
                new JsonExtract(
                    'elements_sites.content',
                    "$.\"{$this->layoutElement->uid}\".\"{$key}\"",
                ),
                $sql,
            ]);
        }

        $castType = null;
        if (DB::isMysql()) {
            // If the field uses an optimized DB type, cast it so its values can be indexed
            // (see "Functional Key Parts" on https://dev.mysql.com/doc/refman/8.0/en/create-index.html)
            $castType = match (DbHelper::parseColumnType($dbType)) {
                Query::TYPE_CHAR,
                Query::TYPE_STRING,
                'varchar' => 'CHAR(255)',
                // only reliable way to compare booleans is as 'true'/'false' strings :(
                Query::TYPE_BOOLEAN => 'CHAR(5)',
                Query::TYPE_DATE => 'DATE',
                Query::TYPE_DATETIME => 'DATETIME',
                Query::TYPE_DECIMAL => 'DECIMAL',
                Query::TYPE_DOUBLE => 'DOUBLE',
                Query::TYPE_FLOAT => 'FLOAT',
                Query::TYPE_TINYINT,
                Query::TYPE_SMALLINT,
                Query::TYPE_INTEGER,
                Query::TYPE_BIGINT => 'SIGNED',
                Query::TYPE_TIME => 'TIME',
                default => null,
            };
        }

        // for pgsql, we have to make sure decimals column type is cast to decimal, otherwise they won't be sorted correctly
        // see https://github.com/craftcms/cms/issues/15828, https://github.com/craftcms/cms/issues/15973
        if (DB::getDriverName() === 'pgsql') {
            $castType = match (Query::parseColumnType($dbType)) {
                Query::TYPE_DECIMAL => 'DECIMAL',
                Query::TYPE_INTEGER => 'INTEGER',
                default => null,
            };
        }

        if ($castType !== null) {
            // if a length was specified, replace the default with that
            $length = DbHelper::parseColumnLength($dbType);
            if ($length) {
                $castType = preg_replace('/\(\d+\)/', "($length)", $castType);
            } elseif ($castType === 'DECIMAL') {
                [$precision, $scale] = DbHelper::parseColumnPrecisionAndScale($dbType) ?? [null, null];
                if ($precision && $scale) {
                    $castType .= "($precision,$scale)";
                }
            }

            $sql = new Cast($sql, $castType);
        }

        return $sql;
    }

    /**
     * Returns the DB data type(s) that this field will store within the `elements_sites.content` column.
     *
     * @see dbType()
     *
     * @return string|string[]|null The data type(s).
     */
    protected function dbTypeForValueSql(): array|string|null
    {
        return static::dbType();
    }

    /** {@inheritdoc} */
    public function modifyElementIndexQuery(ElementQueryInterface $query): void
    {
        if ($this instanceof EagerLoadingFieldInterface) {
            $query->andWith($this->handle);
        }
    }

    /** {@inheritdoc} */
    public function setIsFresh(?bool $isFresh = null): void
    {
        $this->_isFresh = $isFresh;
    }

    /** {@inheritdoc} */
    public function includeInGqlSchema(GqlSchema $schema): bool
    {
        return true;
    }

    /** {@inheritdoc} */
    public function getContentGqlType(): Type|array
    {
        return Type::string();
    }

    /** {@inheritdoc} */
    public function getContentGqlMutationArgumentType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => Type::string(),
            'description' => $this->instructions,
        ];
    }

    /** {@inheritdoc} */
    public function getContentGqlQueryArgumentType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => Type::listOf(QueryArgument::getType()),
        ];
    }

    // Events
    // -------------------------------------------------------------------------

    /** {@inheritdoc} */
    public function beforeSave(bool $isNew): bool
    {
        // Set the field context if it’s not set
        if (! $this->context) {
            $this->context = Fields::getFieldContext();
        }

        $this->dispatchComponentEvent(self::EVENT_BEFORE_SAVE, $event = new ComponentEvent($this, $isNew));

        return $event->isValid;
    }

    public function afterSave(bool $isNew): void
    {
        $this->dispatchComponentEvent(self::EVENT_AFTER_SAVE, new ComponentEvent($this, $isNew));
    }

    /**
     * {@inheritdoc}
     */
    public function beforeElementSave(ElementInterface $element, bool $isNew): bool
    {
        $this->dispatchComponentEvent(self::EVENT_BEFORE_ELEMENT_SAVE, $event = new FieldElementEvent(
            field: $this,
            element: $element,
            isNew: $isNew
        ));

        return $event->isValid;
    }

    /**
     * {@inheritdoc}
     */
    public function afterElementSave(ElementInterface $element, bool $isNew): void
    {
        $this->dispatchComponentEvent(self::EVENT_AFTER_ELEMENT_SAVE, new FieldElementEvent(
            field: $this,
            element: $element,
            isNew: $isNew
        ));
    }

    /** {@inheritdoc} */
    public function afterElementPropagate(ElementInterface $element, bool $isNew): void
    {
        $this->dispatchComponentEvent(self::EVENT_AFTER_ELEMENT_PROPAGATE, new FieldElementEvent(
            field: $this,
            element: $element,
            isNew: $isNew
        ));
    }

    /** {@inheritdoc} */
    public function beforeElementDelete(ElementInterface $element): bool
    {
        $this->dispatchComponentEvent(self::EVENT_BEFORE_ELEMENT_DELETE, $event = new FieldElementEvent(
            field: $this,
            element: $element,
        ));

        return $event->isValid;
    }

    /** {@inheritdoc} */
    public function afterElementDelete(ElementInterface $element): void
    {
        $this->dispatchComponentEvent(self::EVENT_AFTER_ELEMENT_DELETE, new FieldElementEvent(
            field: $this,
            element: $element,
        ));
    }

    /** {@inheritdoc} */
    public function beforeElementDeleteForSite(ElementInterface $element): bool
    {
        return true;
    }

    /** {@inheritdoc} */
    public function afterElementDeleteForSite(ElementInterface $element): void
    {
        // carry on
    }

    /** {@inheritdoc} */
    public function beforeElementRestore(ElementInterface $element): bool
    {
        $this->dispatchComponentEvent(self::EVENT_BEFORE_ELEMENT_RESTORE, $event = new FieldElementEvent(
            field: $this,
            element: $element,
        ));

        return $event->isValid;
    }

    /** {@inheritdoc} */
    public function afterElementRestore(ElementInterface $element): void
    {
        $this->dispatchComponentEvent(self::EVENT_AFTER_ELEMENT_RESTORE, new FieldElementEvent(
            field: $this,
            element: $element,
        ));
    }

    /**
     * @see EagerLoadingFieldInterface::getEagerLoadingGqlConditions()
     */
    public function getEagerLoadingGqlConditions(): ?array
    {
        // No restrictions
        return [];
    }

    /**
     * Returns the field’s param name on the request.
     *
     * @param  ElementInterface  $element  The element this field is associated with
     * @return string|null The field’s param name on the request
     */
    protected function requestParamName(ElementInterface $element): ?string
    {
        $namespace = $element->getFieldParamNamespace();

        return ($namespace ? $namespace.'.' : '').$this->handle;
    }

    /**
     * Returns whether this is the first time the element’s content has been edited.
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

    /**
     * Returns the display name of this class.
     *
     * @return string The display name of this class.
     */
    public static function displayName(): string
    {
        $classNameParts = explode('\\', static::class);

        return array_pop($classNameParts);
    }

    public static function isSelectable(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function propagateValue(ElementInterface $from, ElementInterface $to): void
    {
        $to->setFieldValue($this->handle, $from->getFieldValue($this->handle));
    }
}
