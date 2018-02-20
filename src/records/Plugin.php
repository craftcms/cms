<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\records;

use craft\db\ActiveRecord;
use craft\validators\DateTimeValidator;
use yii\db\ActiveQueryInterface;

/**
 * Class Plugin record.
 *
 * @property int $id ID
 * @property string $class Class
 * @property string $version Version
 * @property bool $enabled Enabled
 * @property array $settings Settings
 * @property \DateTime $installDate Install date
 * @property Migration[] $migrations Migrations
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Plugin extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
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
        return '{{%plugins}}';
    }

    /**
     * Returns the plugin’s migrations.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getMigrations(): ActiveQueryInterface
    {
        return $this->hasMany(Migration::class, ['pluginId' => 'id']);
    }
}
