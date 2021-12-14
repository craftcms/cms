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
 * Class Volume record.
 *
 * @property int $id ID
 * @property string $name Name
 * @property string $handle Handle
 * @property string $type Type
 * @property bool $hasUrls Whether Volume has URLs
 * @property string $url URL
 * @property array $settings Settings
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Filesystem extends ActiveRecord
{
    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::FILESYSTEMS;
    }
}
