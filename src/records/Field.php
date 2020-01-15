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
 * Class Field record.
 *
 * @property int $id ID
 * @property int $groupId Group ID
 * @property string $name Name
 * @property string $handle Handle
 * @property string $context Context
 * @property string $instructions Instructions
 * @property bool $searchable Searchable
 * @property string $translationMethod Translation method
 * @property string $translationKeyFormat Translation key format
 * @property string $type Type
 * @property array $settings Settings
 * @property FieldGroup $group Group
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Field extends ActiveRecord
{
    /**
     * @var string|null
     */
    private $_oldHandle;

    /**
     * Initializes the application component.
     */
    public function init()
    {
        parent::init();

        // Store the old handle in case it's ever requested.
        $this->on(self::EVENT_AFTER_FIND, [$this, 'storeOldHandle']);
    }

    /**
     * Store the old handle.
     */
    public function storeOldHandle()
    {
        $this->_oldHandle = $this->handle;
    }

    /**
     * Returns the old handle.
     *
     * @return string|null
     */
    public function getOldHandle()
    {
        return $this->_oldHandle;
    }

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::FIELDS;
    }

    /**
     * Returns the field’s group.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getGroup(): ActiveQueryInterface
    {
        return $this->hasOne(FieldGroup::class, ['id' => 'groupId']);
    }
}
