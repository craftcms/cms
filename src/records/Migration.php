<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\records;

use yii\db\ActiveQueryInterface;
use craft\app\db\ActiveRecord;
use craft\app\validators\DateTimeValidator;

/**
 * Class Migration record.
 *
 * @property integer   $id        ID
 * @property integer   $pluginId  Plugin ID
 * @property string    $version   Version
 * @property \DateTime $applyTime Apply time
 * @property Plugin    $plugin    Plugin
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Migration extends ActiveRecord
{
    // Public Methods
    // =========================================================================

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
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%migrations}}';
    }

    /**
     * Returns the migration’s plugin.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getPlugin()
    {
        return $this->hasOne(Plugin::class, ['id' => 'pluginId']);
    }
}
