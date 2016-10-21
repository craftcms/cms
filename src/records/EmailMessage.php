<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\records;

use yii\db\ActiveQueryInterface;
use craft\app\db\ActiveRecord;
use craft\app\validators\SiteIdValidator;

/**
 * Class EmailMessage record.
 *
 * @property integer $id      ID
 * @property integer $siteId  Site ID
 * @property string  $key     Key
 * @property string  $subject Subject
 * @property string  $body    Body
 * @property Site    $site    Site
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
            [['siteId'], SiteIdValidator::class],
            [['key'], 'unique', 'targetAttribute' => ['key', 'siteId']],
            [['key', 'siteId', 'subject', 'body'], 'required'],
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
     * Returns the associated site
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSite()
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }
}
