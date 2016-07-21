<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\records;

use yii\db\ActiveQueryInterface;
use craft\app\db\ActiveRecord;

/**
 * Class Widget record.
 *
 * @property integer $id        ID
 * @property integer $userId    User ID
 * @property string  $type      Type
 * @property string  $sortOrder Sort order
 * @property integer $colspan   Colspan
 * @property array   $settings  Settings
 * @property boolean $enabled   Enabled
 * @property User    $user      User
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Widget extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type'], 'required'],
            [['type'], 'string', 'max' => 150],
        ];
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%widgets}}';
    }

    /**
     * Returns the widget’s user.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'userId']);
    }
}
