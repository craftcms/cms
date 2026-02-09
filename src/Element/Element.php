<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use ArrayIterator;
use BadMethodCallException;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\web\twig\AllowedInSandbox;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Utils;
use CraftCms\Cms\Validation\Attributes\Ruleset;
use CraftCms\Cms\Validation\Concerns\ValidatesWithRuleset;
use DateTime;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Validation\Validator as LaravelValidator;
use Override;
use Throwable;
use Traversable;
use yii\base\ArrayableTrait;
use yii\base\InvalidCallException;
use yii\base\UnknownPropertyException;

use function CraftCms\Cms\t;

/**
 * Element is the base class for classes representing elements in terms of objects.
 */
#[Ruleset(ElementRules::class)]
abstract class Element extends Component implements ElementInterface
{
    use ArrayableTrait {
        toArray as traitToArray;
    }
    use Concerns\Cacheable;
    use Concerns\DisplayedInIndex;
    use Concerns\Draftable;
    use Concerns\Eagerloadable;
    use Concerns\Exportable;
    use Concerns\HasActions;
    use Concerns\HasAuthorization;
    use Concerns\HasCanonical;
    use Concerns\HasControlPanelUI;
    use Concerns\HasCustomFields;
    use Concerns\HasGqlType;
    use Concerns\HasLifecycleHooks;
    use Concerns\HasPreviewTargets;
    use Concerns\HasRoutesAndUrls;
    use Concerns\HasSources;
    use Concerns\HasStatuses;
    use Concerns\HasThumbnails;
    use Concerns\Localizable;
    use Concerns\Queryable;
    use Concerns\Renderable;
    use Concerns\Revisionable;
    use Concerns\Searchable;
    use Concerns\Structurable;
    use Concerns\TracksChanges;
    use Macroable {
        __call as macroCall;
    }
    use ValidatesWithRuleset;

    /**
     * @since 3.3.6
     */
    public const string HOMEPAGE_URI = '__home__';

    // Validation scenarios
    // -------------------------------------------------------------------------

    public const string SCENARIO_DEFAULT = 'default';

    public const string SCENARIO_ESSENTIALS = 'essentials';

    public const string SCENARIO_LIVE = 'live';

    /**
     * @var int|null The element's ID
     */
    #[AllowedInSandbox]
    public ?int $id = null;

    /**
     * @var string|null The element’s temporary ID (only used if the element’s URI format contains {id})
     */
    public ?string $tempId = null;

    /**
     * @var string|null The element’s UID
     */
    #[AllowedInSandbox]
    public ?string $uid = null;

    /**
     * @var int|null The ID of the element’s record in the `elements_sites` table
     *
     * @since 3.5.2
     */
    public ?int $siteSettingsId = null;

    /**
     * @var string|null The element’s title
     */
    #[AllowedInSandbox]
    public ?string $title = null;

    /**
     * @var string|null The element’s slug
     */
    #[AllowedInSandbox]
    public ?string $slug = null;

    /**
     * @var DateTime|null The date that the element was created
     */
    #[AllowedInSandbox]
    public ?DateTime $dateCreated = null;

    /**
     * @var DateTime|null The date that the element was last updated
     */
    #[AllowedInSandbox]
    public ?DateTime $dateUpdated = null;

    /**
     * @var DateTime|null The date that the element was trashed
     *
     * @since 3.2.0
     */
    #[AllowedInSandbox]
    public ?DateTime $dateDeleted = null;

    /**
     * @var bool|null Whether the element was deleted along with its owner
     *
     * @since 5.0.0
     */
    public ?bool $deletedWithOwner = null;

    /**
     * @var bool Whether the element has been soft-deleted.
     */
    #[AllowedInSandbox]
    public bool $trashed = false;

    /**
     * @var bool Whether the element is being resaved by a ResaveElement job or a `resave` console command.
     *
     * @since 3.1.22
     */
    public bool $resaving = false;

    /**
     * @var ElementInterface|null The element that this element is duplicating.
     */
    public ?ElementInterface $duplicateOf = null;

    /**
     * @var bool Whether the element is being saved for the first time in a normal state (not as a draft or revision).
     *
     * @since 3.7.5
     */
    public bool $firstSave = false;

    /**
     * @var bool Whether the element should definitely be saved, if it’s a nested element being considered
     *           for saving by [[NestedElementManager]].
     *
     * @since 5.0.0
     */
    public bool $forceSave = false;

    /**
     * @var bool Whether the element is being hard-deleted.
     *
     * @since 3.2.0
     */
    public bool $hardDelete = false;

    /**
     * {@inheritdoc}
     *
     * @return array<string, array<string>|null>
     */
    #[Override]
    public function scenarios(): array
    {
        return [
            self::SCENARIO_DEFAULT => null,
            self::SCENARIO_LIVE => null,
            self::SCENARIO_ESSENTIALS => null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function displayName(): string
    {
        return t('Element');
    }

    /**
     * {@inheritdoc}
     */
    public static function lowerDisplayName(): string
    {
        return mb_strtolower(static::displayName());
    }

    /**
     * {@inheritdoc}
     */
    public static function pluralDisplayName(): string
    {
        return t('Elements');
    }

    /**
     * {@inheritdoc}
     */
    public static function pluralLowerDisplayName(): string
    {
        return mb_strtolower(static::pluralDisplayName());
    }

    /**
     * {@inheritdoc}
     */
    public static function refHandle(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public static function hasTitles(): bool
    {
        return false;
    }

    /**
     * @var array<string,int>|null
     *
     * @see validate()
     */
    private ?array $_attributeNames = null;

    private bool $_initialized = false;

    /**
     * @see toArray()
     */
    private $_serializeFields = false;

    /**
     * {@inheritdoc}
     */
    public function __construct($config = [])
    {
        // Make sure the field layout ID is set before any custom fields
        if (isset($config['fieldLayoutId'])) {
            $config = ['fieldLayoutId' => $config['fieldLayoutId']] + $config;
        }

        parent::__construct($config);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function __clone()
    {
        parent::__clone();

        // Mark all fields as dirty
        $this->_allDirty = true;
        $this->_hasNewParent = null;
    }

    /**
     * Returns the string representation of the element.
     */
    public function __toString(): string
    {
        if (isset($this->title) && $this->title !== '') {
            return $this->title;
        }

        if (! $this->id || $this->getIsUnpublishedDraft()) {
            return t('New {type}', [
                'type' => static::lowerDisplayName(),
            ]);
        }

        return sprintf('%s %s', static::displayName(), $this->id);
    }

    /**
     * Checks if a property is set.
     *
     * This method will check if $name is one of the following:
     * - "title"
     * - a magic property supported by [[\yii\base\Component::__isset()]]
     * - a custom field handle
     *
     * @param  string  $name  The property name
     * @return bool Whether the property is set
     */
    #[Override]
    public function __isset($name): bool
    {
        // Is this the "field:handle" syntax?
        if (str_starts_with($name, 'field:')) {
            return $this->fieldByHandle(substr($name, 6)) !== null;
        }
        if ($name === 'title') {
            return true;
        }
        if ($this->hasEagerLoadedElements($name)) {
            return true;
        }
        if (parent::__isset($name)) {
            return true;
        }

        return (bool) $this->fieldByHandle($name);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function __get($name)
    {
        // Is $name a set of eager-loaded elements?
        if ($this->hasEagerLoadedElements($name) && ! ($this->_lazyEagerLoadedElements[$name] ?? false)) {
            return $this->getEagerLoadedElements($name);
        }

        // Is this the "field:handle" syntax?
        if (str_starts_with($name, 'field:')) {
            return $this->getFieldValue(substr($name, 6));
        }

        // If this is a field, make sure the value has been normalized before returning it
        if ($this->fieldByHandle($name) !== null) {
            return $this->clonedFieldValue($name);
        }

        if (isset($this->_generatedFieldValues) && array_key_exists($name, $this->_generatedFieldValues)) {
            return $this->_generatedFieldValues[$name];
        }

        return parent::__get($name);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function __set($name, $value)
    {
        // Is this the "field:handle" syntax?
        if (str_starts_with($name, 'field:')) {
            $this->setFieldValue(substr($name, 6), $value);

            return;
        }

        try {
            parent::__set($name, $value);
        } catch (InvalidCallException|UnknownPropertyException $e) {
            // Is this is a field?
            if ($this->fieldByHandle($name) !== null) {
                $this->setFieldValue($name, $value);
            } else {
                throw $e;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function __call($name, $params)
    {
        if (str_starts_with($name, 'isFieldEmpty:')) {
            return $this->isFieldEmpty(substr($name, 13));
        }

        try {
            return $this->macroCall($name, $params);
        } catch (BadMethodCallException) {
            return parent::__call($name, $params);
        }
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected function defineBehaviors(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function init(): void
    {
        parent::init();

        if (! isset($this->siteId) && Cms::isInstalled()) {
            $this->siteId = Sites::getPrimarySite()->id;
        }

        if (static::hasTitles()) {
            $this->_savedTitle = $this->title;
        }

        $this->_initialized = true;
    }

    /**
     * @TODO: Remove parameters once Element no longer extends Yii Model
     */
    #[Override]
    public function getAttributes($names = null, $except = []): array
    {
        $attributes = $this->attributes();
        $values = [];

        try {
            foreach ($attributes as $attribute) {
                $values[$attribute] = $this->$attribute;
            }
        } catch (Throwable) {
            // Skip attributes that throw errors during access (e.g., lazy-loaded relations that fail)
            // This is expected for attributes that may not be accessible in all contexts
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function attributes(): array
    {
        $names = array_flip(Utils::getPublicAttributes($this));

        if ($this->structureId) {
            $names['parentId'] = true;
        } else {
            unset(
                $names['level'],
                $names['lft'],
                $names['rgt'],
                $names['root'],
                $names['structureId'],
            );
        }

        unset(
            $names['applyingDraft'],
            $names['awaitingFieldValues'],
            $names['duplicateOf'],
            $names['elementQueryResult'],
            $names['firstSave'],
            $names['hardDelete'],
            $names['mergingCanonicalChanges'],
            $names['newSiteIds'],
            $names['isNewForSite'],
            $names['isNewSite'],
            $names['previewing'],
            $names['propagateAll'],
            $names['propagateRequired'],
            $names['propagating'],
            $names['propagatingFrom'],
            $names['resaving'],
            $names['saveOwnership'],
            $names['searchScore'],
            $names['updateSearchIndexForOwner'],
            $names['updateSearchIndexImmediately'],
            $names['updatingFromDerivative'],
            $names['viewMode'],
        );

        $names['canonicalId'] = true;
        $names['cpEditUrl'] = true;
        $names['isDraft'] = true;
        $names['isRevision'] = true;
        $names['isUnpublishedDraft'] = true;
        $names['ref'] = true;
        $names['status'] = true;
        $names['structureId'] = true;
        $names['url'] = true;

        return array_keys($names);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function fields(): array
    {
        $fields = parent::fields();

        foreach ($this->fieldLayoutFields() as $field) {
            if (! isset($fields[$field->handle])) {
                if ($this->_serializeFields) {
                    $fields[$field->handle] = function () use ($field) {
                        $value = $this->getFieldValue($field->handle);

                        return $field->serializeValue($value, $this);
                    };
                } else {
                    $fields[$field->handle] = fn () => $this->clonedFieldValue($field->handle);
                }
            }
        }

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(array $fields = [], array $expand = [], $recursive = true): array
    {
        if ($recursive) {
            $this->_serializeFields = true;
        }

        $arr = $this->traitToArray($fields, $expand, $recursive);

        if ($recursive) {
            $this->_serializeFields = false;
        }

        return $arr;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function extraFields(): array
    {
        return [
            ...parent::extraFields(),
            'ancestors',
            'canonical',
            'canonicalUid',
            'children',
            'descendants',
            'hasDescendants',
            'next',
            'nextSibling',
            'parent',
            'prev',
            'prevSibling',
            'siblings',
            'site',
            'totalDescendants',
        ];
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getIterator(): Traversable
    {
        $attributes = $this->getAttributes();

        // Include custom fields
        $fieldLayout = $this->getFieldLayout();

        if ($fieldLayout !== null) {
            foreach ($fieldLayout->getCustomFieldElements() as $layoutElement) {
                $field = $layoutElement->getField();
                if (! isset($attributes[$field->handle])) {
                    $attributes[$field->handle] = $this->getFieldValue($field->handle);
                }
            }
        }

        return new ArrayIterator($attributes);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getAttributeLabel($attribute): string
    {
        // Is this the "field:handle" syntax?
        if (str_starts_with($attribute, 'field:')) {
            $attribute = substr($attribute, 6);
        }

        return parent::getAttributeLabel($attribute);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function attributeLabels(): array
    {
        $labels = [
            'dateCreated' => t('Date Created'),
            'dateUpdated' => t('Date Updated'),
            'id' => t('ID'),
            'slug' => t('Slug'),
            'title' => t('Title'),
            'uid' => t('UID'),
            'uri' => t('URI'),
        ];

        if (Cms::isInstalled()) {
            $layout = $this->getFieldLayout();

            if ($layout !== null) {
                foreach ($layout->getTabs() as $tab) {
                    foreach ($tab->getElements() as $layoutElement) {
                        if ($layoutElement instanceof BaseField && ($label = $layoutElement->label()) !== null) {
                            $labels[$layoutElement->attribute()] = $label;
                        }
                    }
                }
            }
        }

        return $labels;
    }

    /**
     * Returns whether the element's `title` attribute should be validated
     *
     * @since 5.0.0
     */
    public function shouldValidateTitle(): bool
    {
        return static::hasTitles();
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function afterValidate(?LaravelValidator $validator = null): void
    {
        $this->validateCustomFields();

        if (request()->isCpRequest()) {
            $this->formatControlPanelErrors();
        }
    }

    protected function validateCustomFields(): void
    {
        if (! Cms::isInstalled() || ! ($fieldLayout = $this->getFieldLayout())) {
            return;
        }

        $scenario = $this->getScenario();
        $layoutElements = $fieldLayout->getEditableCustomFieldElements($this);

        foreach ($layoutElements as $layoutElement) {
            $field = $layoutElement->getField();
            $attribute = "field:$field->handle";

            if (isset($this->_attributeNames) && ! isset($this->_attributeNames[$attribute])) {
                continue;
            }

            $isEmpty = fn () => $field->isValueEmpty($this->getFieldValue($field->handle), $this);

            $rules = [];
            if ($scenario === self::SCENARIO_LIVE && $layoutElement->required) {
                $rules[] = function ($attribute, $value, $fail) use ($isEmpty) {
                    if ($isEmpty()) {
                        $fail(t('validation.required'));
                    }
                };
            } else {
                $rules[] = ['nullable'];
            }

            $rules = array_merge($rules, $field->getElementRules($this));

            $value = $field->prepareForElementValidation(
                $this->getFieldValue($field->handle),
            );

            $this->setFieldValue($field->handle, $value);

            $validator = ValidatorFacade::make(
                data: [$attribute => $value],
                rules: [$attribute => $rules],
                attributes: [$attribute => $field->getUiLabel()]
            );

            if ($validator->fails()) {
                /**
                 * Map errors from `field:attribute` -> `attribute`
                 */
                $errors = collect($validator->errors())
                    ->mapWithKeys(fn (array $errors, string $attribute) => [
                        Str::after($attribute, 'field:') => $errors,
                    ])
                    ->all();

                $this->errors()->merge($errors);
            }
        }
    }

    protected function formatControlPanelErrors(): void
    {
        $allErrors = $this->errors()->getMessages();

        /**
         * Clear our all errors as we're mapping them
         * to bold the field attribute label.
         */
        foreach ($this->errors()->getMessages() as $attribute => $errors) {
            $this->errors()->forget($attribute);
        }

        $this->errors()->merge(collect($allErrors)->map(function (array $errors, string $attribute) {
            $label = $this->getAttributeLabel($attribute);

            foreach ($errors as &$error) {
                $error = str_replace($label, "*$label*", $error);
            }

            return $errors;
        })->all());
    }

    /**
     * {@inheritdoc}
     */
    public function createAnother(): ?ElementInterface
    {
        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @param  string|int  $offset
     */
    #[Override]
    public function offsetExists($offset): bool
    {
        if (parent::offsetExists($offset)) {
            return true;
        }

        return (bool) $this->fieldByHandle($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function setAttributesFromRequest(array $values): void
    {
        $this->setAttributes($values);
    }

    #[Override]
    public function safeAttributes(): array
    {
        return array_keys($this->getRuleset()->rules());
    }
}
