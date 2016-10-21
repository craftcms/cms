<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\FieldInterface;
use craft\app\base\Model;
use craft\app\records\FieldGroup as FieldGroupRecord;
use craft\app\validators\UniqueValidator;

/**
 * FieldGroup model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class FieldGroup extends Model
{
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

    // Public Methods
    // =========================================================================

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
    public function __toString()
    {
        return $this->name;
    }

    /**
     * Returns the group's fields.
     *
     * @return FieldInterface[]
     */
    public function getFields()
    {
        return Craft::$app->getFields()->getFieldsByGroupId($this->id);
    }
}
