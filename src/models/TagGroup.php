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
use craft\elements\Tag;
use craft\records\TagGroup as TagGroupRecord;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

/**
 * TagGroup model.
 *
 * @mixin FieldLayoutBehavior
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class TagGroup extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var string|null Name
     */
    public $name;

    /**
     * @var string|null Handle
     */
    public $handle;

    /**
     * @var int|null Field layout ID
     */
    public $fieldLayoutId;

    /**
     * @var int|null Field layout ID
     */
    public $uid;

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
    public function attributeLabels()
    {
        return [
            'handle' => Craft::t('app', 'Handle'),
            'name' => Craft::t('app', 'Name'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['id', 'fieldLayoutId'], 'number', 'integerOnly' => true];
        $rules[] = [['handle'], HandleValidator::class, 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']];
        $rules[] = [['name', 'handle'], UniqueValidator::class, 'targetClass' => TagGroupRecord::class];
        $rules[] = [['name', 'handle'], 'required'];
        $rules[] = [['name', 'handle'], 'string', 'max' => 255];
        return $rules;
    }

    /**
     * Use the translated tag group's name as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return Craft::t('site', $this->name) ?: static::class;
    }
}
