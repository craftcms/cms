<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\Model;

/**
 * Class Structure model.
 *
 * @property boolean $isSortable whether elements in this structure can be sorted by the current user
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Structure extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var integer ID
     */
    public $id;

    /**
     * @var integer Max levels
     */
    public $maxLevels;

    // Public Methods
    // =========================================================================

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
                ['maxLevels'],
                'number',
                'min' => -2147483648,
                'max' => 2147483647,
                'integerOnly' => true
            ],
            [['id', 'maxLevels'], 'safe', 'on' => 'search'],
        ];
    }

    /**
     * Returns whether elements in this structure can be sorted by the current user.
     *
     * @return boolean
     */
    public function getIsSortable()
    {
        return Craft::$app->getSession()->checkAuthorization('editStructure:'.$this->id);
    }
}
