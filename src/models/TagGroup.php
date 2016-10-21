<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\Model;
use craft\app\behaviors\FieldLayoutBehavior;
use craft\app\behaviors\FieldLayoutTrait;
use craft\app\elements\Tag;
use craft\app\records\TagGroup as TagGroupRecord;
use craft\app\validators\HandleValidator;
use craft\app\validators\UniqueValidator;

/**
 * TagGroup model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class TagGroup extends Model
{
    // Traits
    // =========================================================================

    use FieldLayoutTrait;

    // Properties
    // =========================================================================

    /**
     * @var integer ID
     */
    public $id;

    /**
     * @var string Name
     */
    public $name;

    /**
     * @var string Handle
     */
    public $handle;

    /**
     * @var integer Field layout ID
     */
    public $fieldLayoutId;

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
                'elementType' => Tag::class
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'fieldLayoutId'], 'number', 'integerOnly' => true],
            [['handle'], HandleValidator::class, 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']],
            [['name', 'handle'], UniqueValidator::class, 'targetClass' => TagGroupRecord::class],
            [['name', 'handle'], 'required'],
            [['name', 'handle'], 'string', 'max' => 255],
        ];
    }

    /**
     * Use the translated tag group's name as the string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return Craft::t('site', $this->name);
    }
}
