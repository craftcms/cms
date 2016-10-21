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
 * Class CategoryGroup_SiteSettings record.
 *
 * @property integer       $id                       ID
 * @property integer       $groupId                  Group ID
 * @property integer       $siteId                   Site ID
 * @property boolean       $hasUrls                  Has URLs
 * @property string        $uriFormat                URI format
 * @property string        $template                 Template
 * @property CategoryGroup $group                    Group
 * @property Site          $site                     Site
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class CategoryGroup_SiteSettings extends ActiveRecord
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
        return '{{%categorygroups_i18n}}';
    }

    /**
     * Returns the associated category group.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getGroup()
    {
        return $this->hasOne(CategoryGroup::class, ['id' => 'groupId']);
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
