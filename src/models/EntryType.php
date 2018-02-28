<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Model;
use craft\behaviors\FieldLayoutBehavior;
use craft\elements\Entry;
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
 * @since 3.0
 */
class EntryType extends Model
{
    // Properties
    // =========================================================================

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
     * @var bool Has title field
     */
    public $hasTitleField = true;

    /**
     * @var string Title label
     */
    public $titleLabel = 'Title';

    /**
     * @var string|null Title format
     */
    public $titleFormat;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'fieldLayout' => [
                'class' => FieldLayoutBehavior::class,
                'elementType' => Entry::class
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [
            [['id', 'sectionId', 'fieldLayoutId'], 'number', 'integerOnly' => true],
            [['name', 'handle'], 'required'],
            [['name', 'handle'], 'string', 'max' => 255],
            [
                ['handle'],
                HandleValidator::class,
                'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']
            ],
            [
                ['name'],
                UniqueValidator::class,
                'targetClass' => EntryTypeRecord::class,
                'targetAttribute' => ['name', 'sectionId'],
                'comboNotUnique' => Craft::t('yii', '{attribute} "{value}" has already been taken.'),
            ],
            [
                ['handle'],
                UniqueValidator::class,
                'targetClass' => EntryTypeRecord::class,
                'targetAttribute' => ['handle', 'sectionId'],
                'comboNotUnique' => Craft::t('yii', '{attribute} "{value}" has already been taken.'),
            ],
        ];

        if ($this->hasTitleField) {
            $rules[] = [['titleLabel'], 'required'];
        } else {
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
        return (string)$this->handle;
    }

    /**
     * Returns the entry’s CP edit URL.
     *
     * @return string
     */
    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('settings/sections/'.$this->sectionId.'/entrytypes/'.$this->id);
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
            throw new InvalidConfigException('Invalid section ID: '.$this->sectionId);
        }

        return $section;
    }
}
