<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Class Widget record.
 *
 * @property int    $id        ID
 * @property int    $userId    User ID
 * @property string $type      Type
 * @property string $sortOrder Sort order
 * @property int    $colspan   Colspan
 * @property array  $settings  Settings
 * @property bool   $enabled   Enabled
 * @property User   $user      User
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
        return $this->hasOne(User::class, ['id' => 'userId']);
    }
}
