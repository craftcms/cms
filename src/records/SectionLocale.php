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
 * Class SectionLocale record.
 *
 * @property integer $id               ID
 * @property integer $sectionId        Section ID
 * @property Locale  $locale           Locale
 * @property boolean $enabledByDefault Enabled by default
 * @property string  $urlFormat        URL format
 * @property string  $nestedUrlFormat  Nested URL format
 * @property Section $section          Section
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class SectionLocale extends ActiveRecord
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
            [
                ['sectionId'],
                'unique',
                'targetAttribute' => ['sectionId', 'locale']
            ],
            [['locale'], 'required'],
            [
                ['urlFormat', 'nestedUrlFormat'],
                'craft\\app\\validators\\UrlFormat'
            ],
        ];
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%sections_i18n}}';
    }

    /**
     * Returns the section locale’s section.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSection()
    {
        return $this->hasOne(Section::className(), ['id' => 'sectionId']);
    }

    /**
     * Returns the section locale’s locale.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getLocale()
    {
        return $this->hasOne(Locale::className(), ['id' => 'locale']);
    }
}
