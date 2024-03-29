<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\records;

use craft\db\ActiveRecord;
use craft\db\Table;
use yii\db\ActiveQueryInterface;

/**
 * Element_SiteSettings record class.
 *
 * @property int $id ID
 * @property int $elementId Element ID
 * @property int $siteId Site ID
 * @property string|null $title Title
 * @property string|null $slug Slug
 * @property string|null $uri URI
 * @property array|string|null $content Content
 * @property bool $enabled Enabled
 * @property Element $element Element
 * @property Site $site Site
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Element_SiteSettings extends ActiveRecord
{
    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::ELEMENTS_SITES;
    }

    /**
     * Returns the associated element.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'elementId']);
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
