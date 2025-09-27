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
 * Class UserPermission record.
 *
 * @property int $id ID
 * @property string $name Name
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class UserPermission extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['name'], 'unique'],
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::USERPERMISSIONS;
    }
}
