<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
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

/**
 * EntryType model class.
 *
 * @mixin FieldLayoutBehavior
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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
        return [
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
     * @return Section|null
     */
    public function getSection()
    {
        if ($this->sectionId) {
            return Craft::$app->getSections()->getSectionById($this->sectionId);
        }

        return null;
    }
}
