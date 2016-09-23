<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\records;

use yii\db\ActiveQueryInterface;
use craft\app\db\ActiveRecord;

/**
 * Class Route record.
 *
 * @property integer $id         ID
 * @property Locale  $locale     Locale
 * @property string  $urlParts   URL parts
 * @property string  $urlPattern URL pattern
 * @property string  $template   Template
 * @property string  $sortOrder  Sort order
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Route extends ActiveRecord
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
            [['urlPattern'], 'unique'],
            [['urlParts', 'urlPattern', 'template'], 'required'],
        ];
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%routes}}';
    }

    /**
     * Returns the route’s locale.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getLocale()
    {
        return $this->hasOne(Locale::className(), ['id' => 'locale']);
    }
}
