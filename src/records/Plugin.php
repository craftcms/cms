<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\records;

use craft\db\ActiveRecord;
use craft\db\Table;
use craft\validators\DateTimeValidator;

/**
 * Class Plugin record.
 *
 * @property int $id ID
 * @property string $class Class
 * @property string $version Version
 * @property bool $enabled Enabled
 * @property string $installDate Install date
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Plugin extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['installDate'], DateTimeValidator::class],
            [['class', 'version', 'installDate'], 'required'],
            [['class'], 'string', 'max' => 150],
            [['version'], 'string', 'max' => 15],
        ];
    }

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::PLUGINS;
    }
}
