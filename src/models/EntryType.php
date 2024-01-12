<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Field;
use craft\base\FieldLayoutProviderInterface;
use craft\base\Model;
use craft\behaviors\FieldLayoutBehavior;
use craft\elements\Entry;
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
class EntryType extends Model implements FieldLayoutProviderInterface
{
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
    public function attributeLabels(): array
    {
        return [
            'handle' => Craft::t('app', 'Handle'),
            'name' => Craft::t('app', 'Name'),
            'titleFormat' => Craft::t('app', 'Title Format'),
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
        $rules[] = [
            ['name'],
            UniqueValidator::class,
            'targetClass' => EntryTypeRecord::class,
            'targetAttribute' => 'name',
            'message' => Craft::t('yii', '{attribute} "{value}" has already been taken.'),
        ];
        $rules[] = [
            ['handle'],
            UniqueValidator::class,
            'targetClass' => EntryTypeRecord::class,
            'targetAttribute' => 'handle',
            'message' => Craft::t('yii', '{attribute} "{value}" has already been taken.'),
        ];
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
            'section',
            'type',
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
     * Returns the entry’s edit URL in the control panel.
     *
     * @return string
     */
    public function getCpEditUrl(): string
    {
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
            'hasTitleField' => $this->hasTitleField,
            'titleTranslationMethod' => $this->titleTranslationMethod,
            'titleTranslationKeyFormat' => $this->titleTranslationKeyFormat,
            'titleFormat' => $this->titleFormat,
            'showSlugField' => $this->showSlugField,
            'slugTranslationMethod' => $this->slugTranslationMethod,
            'slugTranslationKeyFormat' => $this->slugTranslationKeyFormat,
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
}
