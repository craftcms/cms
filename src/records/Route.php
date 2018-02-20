<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\records;

use craft\db\ActiveRecord;
use craft\validators\SiteIdValidator;
use yii\db\ActiveQueryInterface;

/**
 * Class Route record.
 *
 * @property int $id ID
 * @property int $siteId Site ID
 * @property string $uriParts URI parts
 * @property string $uriPattern URI pattern
 * @property string $template Template
 * @property int $sortOrder Sort order
 * @property Site $site Site
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
            [['siteId'], SiteIdValidator::class],
            [['uriParts', 'uriPattern', 'template'], 'required'],
        ];
    }

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%routes}}';
    }

    /**
     * Returns the associated site.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSite(): ActiveQueryInterface
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }
}
