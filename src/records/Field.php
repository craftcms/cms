<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\records;

use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;
use craft\db\Table;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * Class Field record.
 *
 * @property int $id ID
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
 * @mixin SoftDeleteBehavior
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Field extends ActiveRecord
{
    use SoftDeleteTrait;

    /**
     * @var string|null
     */
    private ?string $_oldHandle = null;

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
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::FIELDS;
    }
}
