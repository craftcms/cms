<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Class Asset record.
 *
 * @todo Create save function which calls parent::save and then updates the meta data table (keywords, author, etc)
 * @property int $id ID
 * @property int $volumeId Volume ID
 * @property int $folderId Folder ID
 * @property string $filename Filename
 * @property string $kind Kind
 * @property int $width Width
 * @property int $height Height
 * @property int $size Size
 * @property string $focalPoint Focal point coordinates
 * @property \DateTime $dateModified Date modified
 * @property Element $element Element
 * @property Volume $volume Volume
 * @property VolumeFolder $folder Folder
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Asset extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%assets}}';
    }

    /**
     * Returns the asset file’s element.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    /**
     * Returns the asset file’s volume.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getVolume(): ActiveQueryInterface
    {
        return $this->hasOne(Volume::class, ['id' => 'volumeId']);
    }

    /**
     * Returns the asset file’s folder.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getFolder(): ActiveQueryInterface
    {
        return $this->hasOne(VolumeFolder::class, ['id' => 'folderId']);
    }
}
