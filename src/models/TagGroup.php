<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\Model;

/**
 * TagGroup model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class TagGroup extends Model
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
                'class' => 'craft\app\behaviors\FieldLayoutBehavior',
                'elementType' => 'craft\app\elements\Tag'
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                ['id'],
                'number',
                'min' => -2147483648,
                'max' => 2147483647,
                'integerOnly' => true
            ],
            [
                ['fieldLayoutId'],
                'number',
                'min' => -2147483648,
                'max' => 2147483647,
                'integerOnly' => true
            ],
            [
                ['id', 'name', 'handle', 'fieldLayoutId'],
                'safe',
                'on' => 'search'
            ],
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
