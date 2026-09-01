<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Component\Concerns\ConfigurableComponent;
use CraftCms\Cms\Component\Concerns\SavableComponent;
use CraftCms\Cms\Component\Contracts\Actionable;
use CraftCms\Cms\Component\Contracts\Iconic;
use CraftCms\Cms\Database\Expressions\Cast;
use CraftCms\Cms\Database\Expressions\JsonExtract;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementAttributeRenderer;
use CraftCms\Cms\Element\Enums\AttributeStatus;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Field\Concerns\LegacyFieldConstants;
use CraftCms\Cms\Field\Contracts\EagerLoadingFieldInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Contracts\PreviewableFieldInterface;
use CraftCms\Cms\Field\Contracts\RelationalFieldInterface;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\Field\Events\FieldActionMenuItemsResolving;
use CraftCms\Cms\Field\Events\FieldDeletionApplying;
use CraftCms\Cms\Field\Events\FieldElementDeleted;
use CraftCms\Cms\Field\Events\FieldElementDeletedForSite;
use CraftCms\Cms\Field\Events\FieldElementDeleting;
use CraftCms\Cms\Field\Events\FieldElementPropagated;
use CraftCms\Cms\Field\Events\FieldElementRestored;
use CraftCms\Cms\Field\Events\FieldElementRestoring;
use CraftCms\Cms\Field\Events\FieldElementSaved;
use CraftCms\Cms\Field\Events\FieldElementSaving;
use CraftCms\Cms\Field\Events\FieldHtmlResolving;
use CraftCms\Cms\Field\Events\FieldKeywordsResolving;
use CraftCms\Cms\Field\Events\FieldLifecycleDeleted;
use CraftCms\Cms\Field\Events\FieldLifecycleDeleting;
use CraftCms\Cms\Field\Events\FieldLifecycleSaved;
use CraftCms\Cms\Field\Events\FieldLifecycleSaving;
use CraftCms\Cms\Field\Events\FieldMergeFromCompleted;
use CraftCms\Cms\Field\Events\FieldMergeIntoCompleted;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Types\QueryArgument;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Shared\Contracts\Serializable;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Validation\Rules\HandleRule;
use DateTimeInterface;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectField;
use GraphQL\Type\Definition\Type;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use LogicException;
use Override;
use RuntimeException;
use Stringable;
use Tpetry\QueryExpressions\Function\Conditional\Coalesce;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

/**
 * @phpstan-import-type FieldDefinitionConfig from FieldDefinition
 * @phpstan-import-type InputObjectFieldConfig from InputObjectField
 */
abstract class Field extends Component implements Actionable, FieldInterface, Iconic, Stringable
{
    use ConfigurableComponent;
    use LegacyFieldConstants;
    use SavableComponent;

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
     * @see FieldInterface::formControl()
     */
    public ?string $describedBy = null;

    public string $translationMethod {
        get => $this->_translationMethod->value;
        set(string|TranslationMethod $value) {
            $translationMethod = $value instanceof TranslationMethod
                ? $value
                : TranslationMethod::tryFrom($value);

            if ($translationMethod === null) {
                $supportedTranslationMethods = static::supportedTranslationMethods();
                $translationMethod = reset($supportedTranslationMethods) ?: TranslationMethod::None;
            }

            $this->_translationMethod = $translationMethod;
        }
    }

    /** @var string|null The field’s translation key format, if [[translationMethod]] is "custom" */
    public ?string $translationKeyFormat = null;

    public string $translationMethodValue {
        get => $this->_translationMethod->value;
    }

    /** @var list<string> */
    public array $supportedTranslationMethodValues {
        get => array_map(
            static fn (TranslationMethod $translationMethod) => $translationMethod->value,
            static::supportedTranslationMethods(),
        );
    }

    /** @var string|null The field’s previous handle */
    public ?string $oldHandle = null;

    /** @var array<string, mixed>|null The field’s previous settings */
    public ?array $oldSettings = null;

    /** @var string|null The field's UID */
    public ?string $uid = null;

    /** @var DateTimeInterface|null The date that the field was trashed */
    public ?DateTimeInterface $dateDeleted = null;

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

    protected TranslationMethod $_translationMethod = TranslationMethod::None;

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
        parent::__construct(Arr::except($config, ['fieldLimit', 'limitUnit']));

        // Validate the translation method
        $supportedTranslationMethods = static::supportedTranslationMethods() ?: [TranslationMethod::None];
        if (! in_array($this->_translationMethod, $supportedTranslationMethods, true)) {
            $this->_translationMethod = reset($supportedTranslationMethods);
        }

        if ($this->_translationMethod !== TranslationMethod::Custom) {
            $this->translationKeyFormat = null;
        }
    }

    public static function get(int|string $id): ?static
    {
        /** @var ?static $field */
        $field = Fields::getFieldById($id);

        return $field;
    }

    public static function icon(): string
    {
        return 'i-cursor';
    }

    public function formControl(FieldContext $context): Control
    {
        throw new LogicException(sprintf('%s does not provide a Form Control.', static::class));
    }

    /**
     * A warning the field itself needs to show, on top of any the field layout
     * author wrote.
     *
     * For misconfiguration the author can't see from the layout — an Assets
     * field pointed at a volume that no longer exists, say. Returning a string
     * puts it in the field's warning slot; `null` means nothing to say.
     */
    public function formWarning(?ElementInterface $element = null): ?string
    {
        return null;
    }

    public static function isMultiInstance(): bool
    {
        return static::dbType() !== null;
    }

    public static function isRequirable(): bool
    {
        return true;
    }

    public static function supportedTranslationMethods(): array
    {
        if (static::dbType() === null) {
            return [
                TranslationMethod::None,
            ];
        }

        return [
            TranslationMethod::None,
            TranslationMethod::Site,
            TranslationMethod::SiteGroup,
            TranslationMethod::Language,
            TranslationMethod::Custom,
        ];
    }

    public static function phpType(): string
    {
        return 'mixed';
    }

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

    #[Override]
    public function attributeLabels(): array
    {
        return [
            'handle' => t('Handle'),
            'name' => t('Name'),
        ];
    }

    #[Override]
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
                    TranslationMethod::None->value,
                    TranslationMethod::Site->value,
                    TranslationMethod::SiteGroup->value,
                    TranslationMethod::Language->value,
                    TranslationMethod::Custom->value,
                ]),
            ],
            'translationKeyFormat' => [
                'nullable',
                'required_if:translationMethod,'.TranslationMethod::Custom->value,
            ],
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUiLabel(): string
    {
        return t($this->name, category: 'site');
    }

    public function getHandle(): ?string
    {
        return $this->handle;
    }

    public function getIcon(): ?string
    {
        return static::icon();
    }

    public function getCpEditUrl(): ?string
    {
        if (! $this->id || ! currentUser()?->isAdmin()) {
            return null;
        }

        return Url::cpUrl("settings/fields/edit/$this->id");
    }

    public function getActionMenuItems(): array
    {
        $items = $this->actionMenuItems();

        event($event = new FieldActionMenuItemsResolving($this, $items));

        return $event->items;
    }

    public function getFieldLayoutActionMenuItems(FieldLayoutElementContext $context): array
    {
        $this->static = $context->mode !== ControlMode::Editable;

        return $this->fieldLayoutActionMenuItems($context);
    }

    /** @return list<array<string, mixed>> */
    protected function fieldLayoutActionMenuItems(FieldLayoutElementContext $context): array
    {
        return $this->actionMenuItems();
    }

    /** @return list<array<string, mixed>> */
    protected function actionMenuItems(): array
    {
        $items = [];

        if (! $this->id) {
            return $items;
        }

        if (! currentUser()?->isAdmin()) {
            return $items;
        }

        if (Cms::config()->allowAdminChanges) {
            // Edit field. Behavior travels with the item as a declarative
            // action — `craft:edit-field` is handled by the slideout module's
            // window listener, which opens the field's settings slideout and
            // re-announces a save as a bubbling `field-saved` event. Registered
            // JS would never reach an Inertia-rendered page.
            $items[] = [
                // The `action-edit-` prefix is load-bearing: `ElementHtml`
                // marks it `data-edit-action` when the menu is shown on a chip.
                'id' => sprintf('action-edit-%s', mt_rand()),
                'icon' => 'gear',
                'label' => t('Field settings'),
                'action' => [
                    'type' => 'event',
                    'name' => 'craft:edit-field',
                    'detail' => ['fieldId' => $this->id],
                ],
            ];
        }

        return $items;
    }

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

    public function getIsTranslatable(?ElementInterface $element): bool
    {
        if ($this->_translationMethod === TranslationMethod::Custom) {
            return $element === null || $this->getTranslationKey($element) !== '';
        }

        return $this->_translationMethod !== TranslationMethod::None;
    }

    public function getTranslationDescription(?ElementInterface $element): ?string
    {
        if (! $this->getIsTranslatable($element)) {
            return null;
        }

        return $this->_translationMethod->description();
    }

    public function getTranslationKey(ElementInterface $element): string
    {
        return $this->_translationMethod->elementKey($element, $this->translationKeyFormat);
    }

    public function showStatus(): bool
    {
        return true;
    }

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

    public function getInputId(): string
    {
        return Html::id($this->handle);
    }

    public function getLabelId(): string
    {
        return sprintf('%s-label', $this->getInputId());
    }

    public function useFieldset(): bool
    {
        return false;
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        return $value;
    }

    public function normalizeValueFromRequest(mixed $value, ?ElementInterface $element): mixed
    {
        return $this->normalizeValue($value, $element);
    }

    /**
     * @see InlineEditableFieldInterface::getInlineInputHtml()
     */
    public function getInlineInputHtml(mixed $value, ?ElementInterface $element): string
    {
        $html = $this->inputHtml($value, $element, true);

        event($event = new FieldHtmlResolving(
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
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Html::textarea($this->handle, $value)->render();
    }

    public function prepareForElementValidation(mixed $value): mixed
    {
        return $value;
    }

    /** @return list<string|object> */
    public function getElementRules(ElementInterface $element): array
    {
        return [];
    }

    public function isValueEmpty(mixed $value, ElementInterface $element): bool
    {
        // Default to yii\validators\Validator::isEmpty()'s behavior
        return $value === null || $value === [] || $value === '';
    }

    public function getSearchKeywords(mixed $value, ElementInterface $element): string
    {
        event($event = new FieldKeywordsResolving(
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
        return app(ElementAttributeRenderer::class)->attributeHtml($value);
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
    /** @return array{label:string, orderBy:string|Expression|null, attribute:string} */
    public function getSortOption(): array
    {
        $dbType = static::dbType();
        if ($dbType === null || ! isset($this->layoutElement)) {
            throw new RuntimeException('getSortOption() not supported by '.$this->name);
        }

        $orderBy = $this->getValueSql();

        // for mysql, we have to make sure text column type is cast to char, otherwise it won't be sorted correctly
        // see https://github.com/craftcms/cms/issues/15609
        if (DB::isMysql() && is_string($dbType) && Query::parseColumnType($dbType) === Query::TYPE_TEXT) {
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
    public function afterMergeInto(FieldInterface $persistingField): void
    {
        event(new FieldMergeIntoCompleted($this, $persistingField));
    }

    /**
     * @see MergeableFieldInterface::afterMergeFrom()
     */
    public function afterMergeFrom(FieldInterface $outgoingField): void
    {
        if ($this instanceof RelationalFieldInterface) {
            DB::table(Table::RELATIONS)
                ->where('fieldId', $outgoingField->id)
                ->update([
                    'fieldId' => $this->id,
                    'dateUpdated' => now(),
                ]);
        }

        event(new FieldMergeFromCompleted($this, $outgoingField));
    }

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
        if ($value instanceof DateTimeInterface || DateTimeHelper::isIso8601($value)) {
            return DateTimeHelper::toIso8601($value);
        }

        return $value;
    }

    public function serializeValueForDb(mixed $value, ElementInterface $element): mixed
    {
        // Dates should be stored in UTC w/o the time zone
        if ($value instanceof DateTimeInterface || DateTimeHelper::isIso8601($value)) {
            return Query::prepareDateForDb($value);
        }

        return $this->serializeValue($value, $element);
    }

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

    public function getElementConditionRuleType(): array|string|null
    {
        return null;
    }

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
            $castType = match (Query::parseColumnType($dbType)) {
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
            $length = Query::parseColumnLength($dbType);
            if ($length) {
                $castType = preg_replace('/\(\d+\)/', "($length)", $castType);
            } elseif ($castType === 'DECIMAL') {
                [$precision, $scale] = Query::parseColumnPrecisionAndScale($dbType) ?? [null, null];
                if ($precision !== null && $scale !== null) {
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

    public function modifyElementIndexQuery(ElementQueryInterface $query): void
    {
        if ($this instanceof EagerLoadingFieldInterface) {
            $query->andWith($this->handle);
        }
    }

    public function setIsFresh(?bool $isFresh = null): void
    {
        $this->_isFresh = $isFresh;
    }

    public function includeInGqlSchema(GqlSchema $schema): bool
    {
        return true;
    }

    /** @return Type|FieldDefinitionConfig */
    public function getContentGqlType(): Type|array
    {
        return Type::string();
    }

    /** @return Type|InputObjectFieldConfig */
    public function getContentGqlMutationArgumentType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => Type::string(),
            'description' => $this->instructions,
        ];
    }

    /** @return Type|InputObjectFieldConfig */
    public function getContentGqlQueryArgumentType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => Type::listOf(QueryArgument::getType()),
        ];
    }

    // Events
    // -------------------------------------------------------------------------

    public function beforeSave(bool $isNew): bool
    {
        // Set the field context if it’s not set
        if (! $this->context) {
            $this->context = Fields::getFieldContext();
        }

        event($event = new FieldLifecycleSaving($this, $isNew));

        return $event->isValid;
    }

    public function afterSave(bool $isNew): void
    {
        event(new FieldLifecycleSaved($this, $isNew));
    }

    public function beforeDelete(): bool
    {
        event($event = new FieldLifecycleDeleting($this));

        return $event->isValid;
    }

    public function beforeApplyDelete(): void
    {
        event(new FieldDeletionApplying($this));
    }

    public function afterDelete(): void
    {
        event(new FieldLifecycleDeleted($this));
    }

    public function beforeElementSave(ElementInterface $element, bool $isNew): bool
    {
        event($event = new FieldElementSaving(
            field: $this,
            element: $element,
            isNew: $isNew
        ));

        return $event->isValid;
    }

    public function afterElementSave(ElementInterface $element, bool $isNew): void
    {
        event(new FieldElementSaved(
            field: $this,
            element: $element,
            isNew: $isNew
        ));
    }

    public function afterElementPropagate(ElementInterface $element, bool $isNew): void
    {
        event(new FieldElementPropagated(
            field: $this,
            element: $element,
            isNew: $isNew
        ));
    }

    public function beforeElementDelete(ElementInterface $element): bool
    {
        event($event = new FieldElementDeleting(
            field: $this,
            element: $element,
        ));

        return $event->isValid;
    }

    public function afterElementDelete(ElementInterface $element): void
    {
        event(new FieldElementDeleted(
            field: $this,
            element: $element,
        ));
    }

    public function beforeElementDeleteForSite(ElementInterface $element): bool
    {
        return true;
    }

    public function afterElementDeleteForSite(ElementInterface $element): void
    {
        event(new FieldElementDeletedForSite(
            field: $this,
            element: $element,
        ));
    }

    public function beforeElementRestore(ElementInterface $element): bool
    {
        event($event = new FieldElementRestoring(
            field: $this,
            element: $element,
        ));

        return $event->isValid;
    }

    public function afterElementRestore(ElementInterface $element): void
    {
        event(new FieldElementRestored(
            field: $this,
            element: $element,
        ));
    }

    /**
     * @see EagerLoadingFieldInterface::getEagerLoadingGqlConditions()
     */
    /** @return array<string, mixed>|null */
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
    #[Override]
    public static function displayName(): string
    {
        $classNameParts = explode('\\', static::class);

        return array_pop($classNameParts);
    }

    #[Override]
    public static function isSelectable(): bool
    {
        return true;
    }

    public function propagateValue(ElementInterface $from, ElementInterface $to): void
    {
        $to->setFieldValue($this->handle, $from->getFieldValue($this->handle));
    }

    #[Override]
    public function normalizeValueForImport(mixed $value, BaseImporter $importer, ?ElementInterface $rootOwner = null): mixed
    {
        // by default, just return the value we were given
        return $value;
    }
}
