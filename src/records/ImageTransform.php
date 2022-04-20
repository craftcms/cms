<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\records;

use craft\db\ActiveRecord;
use craft\db\Table;

/**
 * Class ImageTransform record.
 *
 * @property int $id ID
 * @property string $name Name
 * @property string $handle Handle
 * @property string $mode Mode
 * @property string $position Position
 * @property int $height Height
 * @property int $width Width
 * @property string $format Format
 * @property string $interlace Interlace
 * @property int $quality Quality
 * @property string|null $parameterChangeTime Critical parameter change time
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class ImageTransform extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return Table::IMAGETRANSFORMS;
    }
}
