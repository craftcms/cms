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
 * Class EmailMessage record.
 *
 * @property integer $id      ID
 * @property Locale  $locale  Locale
 * @property string  $key     Key
 * @property string  $subject Subject
 * @property string  $body    Body
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class EmailMessage extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['locale'], 'craft\\app\\validators\\Locale'],
            [['key'], 'unique', 'targetAttribute' => ['key', 'locale']],
            [['key', 'locale', 'subject', 'body'], 'required'],
            [['key'], 'string', 'max' => 150],
            [['subject'], 'string', 'max' => 1000],
        ];
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%emailmessages}}';
    }

    /**
     * Returns the email message’s locale.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getLocale()
    {
        return $this->hasOne(Locale::className(), ['id' => 'locale']);
    }
}
