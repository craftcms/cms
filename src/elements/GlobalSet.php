<?php
/**
 * @link      http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license
 */

namespace craft\app\elements;

use Craft;
use craft\app\base\Element;
use craft\app\elements\db\GlobalSetQuery;
use craft\app\helpers\UrlHelper;

/**
 * GlobalSet represents a global set element.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class GlobalSet extends Element
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName()
    {
        return Craft::t('app', 'Global Set');
    }

    /**
     * @inheritdoc
     */
    public static function hasContent()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized()
    {
        return true;
    }

    /**
     * @inheritdoc
     *
     * @return GlobalSetQuery The newly created [[GlobalSetQuery]] instance.
     */
    public static function find()
    {
        return new GlobalSetQuery(get_called_class());
    }

    // Properties
    // =========================================================================

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
     * Use the global set's name as its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'fieldLayout' => [
                'class' => 'craft\app\behaviors\FieldLayoutBehavior',
                'elementType' => 'craft\app\elements\GlobalSet'
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();

        $rules[] = [
            ['handle'],
            'craft\\app\\validators\\Handle',
            'reservedWords' => [
                'id',
                'dateCreated',
                'dateUpdated',
                'uid',
                'title'
            ]
        ];
        $rules[] = [
            ['fieldLayoutId'],
            'number',
            'min' => -2147483648,
            'max' => 2147483647,
            'integerOnly' => true
        ];
        $rules[] = [['name', 'handle'], 'string', 'max' => 255];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl()
    {
        return UrlHelper::getCpUrl('globals/'.$this->handle);
    }
}
