<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\records;

use craft\db\ActiveRecord;
use craft\validators\SiteIdValidator;
use craft\validators\UriValidator;
use yii\db\ActiveQueryInterface;

/**
 * Element_SiteSettings record class.
 *
 * @property int     $id        ID
 * @property int     $elementId Element ID
 * @property int     $siteId    Site ID
 * @property string  $slug      Slug
 * @property string  $uri       URI
 * @property bool    $enabled   Enabled
 * @property Element $element   Element
 * @property Site    $site      Site
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Element_SiteSettings extends ActiveRecord
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
            [['elementId'], 'unique', 'targetAttribute' => ['elementId', 'siteId']],
            [['uri'], 'unique', 'targetAttribute' => ['uri', 'siteId']],
            [['elementId', 'siteId'], 'required'],
            [['uri'], UriValidator::class],
        ];
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%elements_i18n}}';
    }

    /**
     * Returns the associated element.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getElement()
    {
        return $this->hasOne(Element::class, ['id' => 'elementId']);
    }

    /**
     * Returns the associated site.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSite()
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }
}
