<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\records;

use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;
use craft\db\Table;
use yii\db\ActiveQueryInterface;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * Class Section record.
 *
 * @property int $id ID
 * @property int|null $structureId Structure ID
 * @property string $name Name
 * @property string $handle Handle
 * @property string $type Type
 * @property bool $enableVersioning Enable versioning
 * @property bool $propagationMethod Propagation method
 * @property string $defaultPlacement Default placement
 * @property array|null $previewTargets Preview targets
 * @property Section_SiteSettings[] $siteSettings Site settings
 * @property Structure $structure Structure
 * @mixin SoftDeleteBehavior
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Section extends ActiveRecord
{
    use SoftDeleteTrait;

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::SECTIONS;
    }

    /**
     * Returns the associated site settings.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSiteSettings(): ActiveQueryInterface
    {
        return $this->hasMany(Section_SiteSettings::class, ['sectionId' => 'id']);
    }

    /**
     * Returns the section’s structure.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getStructure(): ActiveQueryInterface
    {
        return $this->hasOne(Structure::class, ['id' => 'structureId']);
    }
}
