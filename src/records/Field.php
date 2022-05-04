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
 * @property string|null $columnSuffix
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
    private ?string $_oldHandle = null;

    /**
     * @var string|null
     */
    private ?string $_oldColumnSuffix = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        // Store the old handle in case it's ever requested.
        $this->on(self::EVENT_AFTER_FIND, [$this, 'storeOldData']);
    }

    /**
     * Store the old handle.
     */
    public function storeOldData(): void
    {
        $this->_oldHandle = $this->handle;
        $this->_oldColumnSuffix = $this->columnSuffix;
    }

    /**
     * Returns the old handle.
     *
     * @return string|null
     */
    public function getOldHandle(): ?string
    {
        return $this->_oldHandle;
    }

    /**
     * Returns the old column suffix.
     *
     * @return string|null
     * @since 3.7.0
     */
    public function getOldColumnSuffix(): ?string
    {
        return $this->_oldColumnSuffix;
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
