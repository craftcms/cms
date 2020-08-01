<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Field;
use craft\base\Model;
use craft\behaviors\FieldLayoutBehavior;
use craft\db\Table;
use craft\elements\Entry;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\records\EntryType as EntryTypeRecord;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use yii\base\InvalidConfigException;

/**
 * EntryType model class.
 *
 * @mixin FieldLayoutBehavior
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class EntryType extends Model
{
    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var int|null Section ID
     */
    public $sectionId;

    /**
     * @var int|null Field layout ID
     */
    public $fieldLayoutId;

    /**
     * @var string|null Name
     */
    public $name;

    /**
     * @var string|null Handle
     */
    public $handle;

    /**
     * @var int|null Sort order
     * @since 3.5.0
     */
    public $sortOrder;

    /**
     * @var bool Has title field
     */
    public $hasTitleField = true;

    /**
     * @var string Title translation method
     * @since 3.5.0
     */
    public $titleTranslationMethod = Field::TRANSLATION_METHOD_SITE;

    /**
     * @var string|null Title translation key format
     * @since 3.5.0
     */
    public $titleTranslationKeyFormat;

    /**
     * @var string|null Title format
     */
    public $titleFormat;

    /**
     * @var string UID
     */
    public $uid;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['fieldLayout'] = [
            'class' => FieldLayoutBehavior::class,
            'elementType' => Entry::class,
        ];
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'handle' => Craft::t('app', 'Handle'),
            'name' => Craft::t('app', 'Name'),
            'titleFormat' => Craft::t('app', 'Title Format'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id', 'sectionId', 'fieldLayoutId'], 'number', 'integerOnly' => true];
        $rules[] = [['name', 'handle'], 'required'];
        $rules[] = [['name', 'handle'], 'string', 'max' => 255];
        $rules[] = [
            ['handle'],
            HandleValidator::class,
            'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']
        ];
        $rules[] = [
            ['name'],
            UniqueValidator::class,
            'targetClass' => EntryTypeRecord::class,
            'targetAttribute' => ['name', 'sectionId'],
            'comboNotUnique' => Craft::t('yii', '{attribute} "{value}" has already been taken.'),
        ];
        $rules[] = [
            ['handle'],
            UniqueValidator::class,
            'targetClass' => EntryTypeRecord::class,
            'targetAttribute' => ['handle', 'sectionId'],
            'comboNotUnique' => Craft::t('yii', '{attribute} "{value}" has already been taken.'),
        ];

        if (!$this->hasTitleField) {
            $rules[] = [['titleFormat'], 'required'];
        }

        return $rules;
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
     * Returns the entry’s edit URL in the control panel.
     *
     * @return string
     */
    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('settings/sections/' . $this->sectionId . '/entrytypes/' . $this->id);
    }

    /**
     * Returns the entry type’s section.
     *
     * @return Section
     * @throws InvalidConfigException if [[sectionId]] is missing or invalid
     */
    public function getSection(): Section
    {
        if ($this->sectionId === null) {
            throw new InvalidConfigException('Entry type is missing its section ID');
        }

        if (($section = Craft::$app->getSections()->getSectionById($this->sectionId)) === null) {
            throw new InvalidConfigException('Invalid section ID: ' . $this->sectionId);
        }

        return $section;
    }

    /**
     * Returns the field layout config for this entry type.
     *
     * @return array
     * @since 3.5.0
     */
    public function getConfig(): array
    {
        $config = [
            'name' => $this->name,
            'handle' => $this->handle,
            'hasTitleField' => (bool)$this->hasTitleField,
            'titleTranslationMethod' => $this->titleTranslationMethod,
            'titleTranslationKeyFormat' => $this->titleTranslationKeyFormat ?: null,
            'titleFormat' => $this->titleFormat ?: null,
            'sortOrder' => (int)$this->sortOrder,
            'section' => $this->getSection()->uid,
        ];

        $fieldLayout = $this->getFieldLayout();

        if ($fieldLayoutConfig = $fieldLayout->getConfig()) {
            if (!$fieldLayout->uid) {
                $fieldLayout->uid = $fieldLayout->id ? Db::uidById(Table::FIELDLAYOUTS, $fieldLayout->id) : StringHelper::UUID();
            }
            $config['fieldLayouts'] = [
                $fieldLayout->uid => $fieldLayoutConfig,
            ];
        }

        return $config;
    }
}
