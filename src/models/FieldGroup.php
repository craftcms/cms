<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\FieldInterface;
use craft\base\Model;
use craft\records\FieldGroup as FieldGroupRecord;
use craft\validators\UniqueValidator;

/**
 * FieldGroup model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FieldGroup extends Model
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

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'name' => Craft::t('app', 'Name'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'number', 'integerOnly' => true],
            [['name'], 'string', 'max' => 255],
            [['name'], UniqueValidator::class, 'targetClass' => FieldGroupRecord::class],
            [['name'], 'required'],
        ];
    }

    /**
     * Use the group name as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->name;
    }

    /**
     * Returns the group's fields.
     *
     * @return FieldInterface[]
     */
    public function getFields(): array
    {
        return Craft::$app->getFields()->getFieldsByGroupId($this->id);
    }
}
