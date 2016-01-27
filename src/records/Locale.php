<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\records;

use craft\app\db\ActiveRecord;

/**
 * Stores the locales.
 *
 * @property string $locale    Locale
 * @property string $sortOrder Sort order
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Locale extends ActiveRecord
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
            [['locale'], 'required'],
        ];
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%locales}}';
    }

    /**
     * @return string
     */
    public static function primaryKey()
    {
        return ['locale'];
    }
}
