<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Actionable;
use craft\base\Chippable;
use craft\base\Colorable;
use craft\base\CpEditable;
use craft\base\Describable;
use craft\base\ElementContainerFieldInterface;
use craft\base\Field;
use craft\base\FieldLayoutProviderInterface;
use craft\base\GqlInlineFragmentInterface;
use craft\base\Iconic;
use craft\base\Indicative;
use craft\base\Model;
use craft\behaviors\FieldLayoutBehavior;
use craft\elements\Entry;
use craft\enums\Color;
use craft\helpers\Inflector;
use craft\helpers\UrlHelper;
use craft\records\EntryType as EntryTypeRecord;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

/**
 * EntryType model class.
 *
 * @mixin FieldLayoutBehavior
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class EntryType extends Model implements
    FieldLayoutProviderInterface,
    GqlInlineFragmentInterface,
    Chippable,
    CpEditable,
    Iconic,
    Indicative,
    Colorable,
    Actionable,
    Describable
{
    /**
     * @inheritdoc
     */
    public static function get(int|string $id): ?static
    {
        /** @phpstan-ignore-next-line */
        return Craft::$app->getEntries()->getEntryTypeById($id);
    }

    /**
     * @var int|null ID
     */
    public ?int $id = null;

    /**
     * @var int|null Field layout ID
     */
    public ?int $fieldLayoutId = null;

    /**
     * @var string|null Name
     */
    public ?string $name = null;

    /**
     * @var string|null Handle
     */
    public ?string $handle = null;

    /**
     * @var string|null Description
     * @since 5.8.0
     */
    public ?string $description = null;

    /**
     * @var string|null Icon
     * @since 5.0.0
     */
    public ?string $icon = null;

    /**
     * @var Color|null Color
     * @since 5.0.0
     */
    public ?Color $color = null;

    /**
     * @var bool Has title field
     */
    public bool $hasTitleField = true;

    /**
     * @var string Title translation method
     * @phpstan-var Field::TRANSLATION_METHOD_NONE|Field::TRANSLATION_METHOD_SITE|Field::TRANSLATION_METHOD_SITE_GROUP|Field::TRANSLATION_METHOD_LANGUAGE|Field::TRANSLATION_METHOD_CUSTOM
     * @since 3.5.0
     */
    public string $titleTranslationMethod = Field::TRANSLATION_METHOD_SITE;

    /**
     * @var string|null Title translation key format
     * @since 3.5.0
     */
    public ?string $titleTranslationKeyFormat = null;

    /**
     * @var string|null Title format
     */
    public ?string $titleFormat = null;

    /**
     * @var bool Whether to show the Slug field
     * @since 5.0.0
     */
    public bool $showSlugField = true;

    /**
     * @var string Slug translation method
     * @phpstan-var Field::TRANSLATION_METHOD_NONE|Field::TRANSLATION_METHOD_SITE|Field::TRANSLATION_METHOD_SITE_GROUP|Field::TRANSLATION_METHOD_LANGUAGE|Field::TRANSLATION_METHOD_CUSTOM
     * @since 4.5.0
     */
    public string $slugTranslationMethod = Field::TRANSLATION_METHOD_SITE;

    /**
     * @var string|null Slug translation key format
     * @since 4.5.0
     */
    public ?string $slugTranslationKeyFormat = null;

    /**
     * @var bool Whether to show the Status field
     * @since 4.5.0
     */
    public bool $showStatusField = true;

    /**
     * @var string|null UID
     */
    public ?string $uid = null;

    /**
     * @var bool Whether the entry type handle’s uniqueness should be validated.
     * @since 5.6.0
     */
    public bool $validateHandleUniqueness = true;

    /**
     * @var string|null The group name
     * @since 5.8.0
     */
    public ?string $group = null;

    /**
     * @var ?self The original entry type without an overridden name and handle
     * @since 5.6.0
     */
    public ?self $original = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if ($this->titleFormat === '') {
            $this->titleFormat = null;
        }

        if ($this->titleTranslationKeyFormat === '') {
            $this->titleTranslationKeyFormat = null;
        }

        if ($this->slugTranslationKeyFormat === '') {
            $this->slugTranslationKeyFormat = null;
        }
    }

    /**
     * @inheritdoc
     */
    public function attributes(): array
    {
        $names = array_flip(parent::attributes());
        unset($names['validateHandleUniqueness']);
        unset($names['original']);
        return array_keys($names);
    }

    /**
     * @inheritdoc
     */
    protected function defineBehaviors(): array
    {
        return [
            'fieldLayout' => [
                'class' => FieldLayoutBehavior::class,
                'elementType' => Entry::class,
            ],
        ];
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
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @inheritdoc
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * @inheritdoc
     */
    public function getIndicators(): array
    {
        $indicators = [];

        if (isset($this->original)) {
            $attributes = array_values(array_filter([
                $this->name !== $this->original->name ? Craft::t('app', 'Name') : null,
                $this->handle !== $this->original->handle ? Craft::t('app', 'Handle') : null,
                $this->description !== $this->original->description ? Craft::t('app', 'Description') : null,
            ]));
            if (!empty($attributes)) {
                array_unshift($indicators, [
                    'label' => Craft::t('app', 'This entry type’s {attributes} {totalAttributes, plural, =1{has} other{have}} been overridden.', [
                        'attributes' => mb_strtolower(Inflector::sentence($attributes)),
                        'totalAttributes' => count($attributes),
                    ]),
                    'icon' => 'pencil',
                    'iconColor' => 'teal',
                ]);
            }
        }

        return $indicators;
    }

    /**
     * @inheritdoc
     */
    public function getColor(): ?Color
    {
        return $this->color;
    }

    /**
     * @inheritdoc
     */
    public function getActionMenuItems(): array
    {
        $items = [];

        if (
            $this->id &&
            Craft::$app->getUser()->getIsAdmin() &&
            Craft::$app->getConfig()->getGeneral()->allowAdminChanges
        ) {
            $editId = sprintf('action-edit-%s', mt_rand());
            $items[] = [
                'id' => $editId,
                'icon' => 'gear',
                'label' => Craft::t('app', 'Entry type settings'),
            ];

            $view = Craft::$app->getView();
            $view->registerJsWithVars(fn($id, $params) => <<<JS
$('#' + $id).on('click', () => {
  new Craft.CpScreenSlideout('entry-types/edit', {
    params: $params,
  });
});
JS, [
                $view->namespaceInputId($editId),
                ['entryTypeId' => $this->id],
            ]);
        }

        return $items;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'handle' => Craft::t('app', 'Handle'),
            'name' => Craft::t('app', 'Name'),
            'titleFormat' => Craft::t('app', 'Default Title Format'),
            'showStatusField' => Craft::t('app', 'Show the Status field'),
            'showSlugField' => Craft::t('app', 'Show the Slug field'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id', 'fieldLayoutId'], 'number', 'integerOnly' => true];
        $rules[] = [['name', 'handle'], 'required'];
        $rules[] = [['name', 'handle'], 'string', 'max' => 255];
        $rules[] = [
            ['handle'],
            HandleValidator::class,
            'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title'],
        ];

        if ($this->validateHandleUniqueness) {
            $rules[] = [
                ['handle'],
                UniqueValidator::class,
                'targetClass' => EntryTypeRecord::class,
                'targetAttribute' => 'handle',
                'message' => Craft::t('yii', '{attribute} "{value}" has already been taken.'),
            ];
        }

        $rules[] = [['fieldLayout'], 'validateFieldLayout'];

        return $rules;
    }

    /**
     * Validates the field layout.
     *
     * @since 3.7.0
     */
    public function validateFieldLayout(): void
    {
        $fieldLayout = $this->getFieldLayout();
        $fieldLayout->reservedFieldHandles = [
            'author',
            'authorId',
            'authorIds',
            'authors',
            'section',
            'sectionId',
            'type',
            'postDate',
        ];

        if (!$fieldLayout->validate()) {
            $this->addModelErrors($fieldLayout, 'fieldLayout');
        }
    }

    /**
     * Use the handle as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->handle ?: static::class;
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
    public function getFieldLayout(): FieldLayout
    {
        /** @var FieldLayoutBehavior $behavior */
        $behavior = $this->getBehavior('fieldLayout');
        return $behavior->getFieldLayout();
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getFieldContext(): string
    {
        return 'global';
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getEagerLoadingPrefix(): string
    {
        return $this->handle;
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl(): ?string
    {
        if (!$this->id || !Craft::$app->getUser()->getIsAdmin()) {
            return null;
        }
        return UrlHelper::cpUrl("settings/entry-types/$this->id");
    }

    /**
     * Returns the entry type’s config.
     *
     * @return array
     * @since 3.5.0
     */
    public function getConfig(): array
    {
        $config = [
            'name' => $this->name,
            'handle' => $this->handle,
            'description' => $this->description ?: null,
            'icon' => $this->icon || $this->icon === '0' ? $this->icon : null,
            'color' => $this->color?->value,
            'hasTitleField' => $this->hasTitleField,
            'titleTranslationMethod' => $this->titleTranslationMethod,
            'titleTranslationKeyFormat' => $this->titleTranslationKeyFormat ?: null,
            'titleFormat' => $this->titleFormat ?: null,
            'showSlugField' => $this->showSlugField,
            'slugTranslationMethod' => $this->slugTranslationMethod,
            'slugTranslationKeyFormat' => $this->slugTranslationKeyFormat ?: null,
            'showStatusField' => $this->showStatusField,
        ];

        $fieldLayout = $this->getFieldLayout();

        if ($fieldLayoutConfig = $fieldLayout->getConfig()) {
            $config['fieldLayouts'] = [
                $fieldLayout->uid => $fieldLayoutConfig,
            ];
        }

        return $config;
    }

    /**
     * Returns the entry type’s usage info, possibly with name and handle override values.
     *
     * @return array
     * @since 5.6.0
     */
    public function getUsageConfig(): array
    {
        $config = ['uid' => $this->uid];
        if (isset($this->original)) {
            if ($this->name !== $this->original->name) {
                $config['name'] = $this->name;
            }
            if ($this->handle !== $this->original->handle) {
                $config['handle'] = $this->handle;
            }
            if ($this->description !== $this->original->description) {
                $config['description'] = $this->description;
            }
            if (isset($this->group)) {
                $config['group'] = $this->group;
            }
        }
        return $config;
    }

    /**
     * Returns an array of sections and custom fields that make use of this entry type.
     *
     * @return array<Section|ElementContainerFieldInterface>
     * @since 5.0.0
     */
    public function findUsages(): array
    {
        if (!isset($this->id)) {
            return [];
        }

        $usages = [];

        // Sections
        foreach (Craft::$app->getEntries()->getAllSections() as $section) {
            foreach ($section->getEntryTypes() as $entryType) {
                if ($entryType->id === $this->id) {
                    $usages[] = $section;
                    break;
                }
            }
        }

        // Fields
        $fieldsService = Craft::$app->getFields();
        foreach ($fieldsService->getNestedEntryFieldTypes() as $type) {
            /** @var ElementContainerFieldInterface[] $fields */
            $fields = $fieldsService->getFieldsByType($type);
            foreach ($fields as $field) {
                foreach ($field->getFieldLayoutProviders() as $provider) {
                    if ($provider instanceof EntryType && $provider->id === $this->id) {
                        $usages[] = $field;
                    }
                }
            }
        }

        return $usages;
    }
}
