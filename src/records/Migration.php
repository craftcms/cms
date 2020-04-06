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
use yii\db\ActiveQueryInterface;

/**
 * Class Migration record.
 *
 * @property int $id ID
 * @property int $pluginId Plugin ID
 * @property string $version Version
 * @property \DateTime $applyTime Apply time
 * @property Plugin $plugin Plugin
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Migration extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['applyTime'], DateTimeValidator::class],
            [['version'], 'unique'],
            [['version', 'applyTime'], 'required'],
            [['version'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::MIGRATIONS;
    }

    /**
     * Returns the migration’s plugin.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getPlugin(): ActiveQueryInterface
    {
        return $this->hasOne(Plugin::class, ['id' => 'pluginId']);
    }
}
