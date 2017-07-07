<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\records;

use Craft;
use craft\db\ActiveRecord;
use craft\db\TaskQuery;
use creocoder\nestedsets\NestedSetsBehavior;

/**
 * Class Task record.
 *
 * @property int    $id          ID
 * @property int    $root        Root
 * @property int    $lft         Lft
 * @property int    $rgt         Rgt
 * @property int    $level       Level
 * @property int    $currentStep Current step
 * @property int    $totalSteps  Total steps
 * @property string $status      Status
 * @property string $type        Type
 * @property string $description Description
 * @property array  $settings    Settings
 * @mixin NestedSetsBehavior
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Task extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%tasks}}';
    }

    /**
     * @inheritdoc
     *
     * @return TaskQuery
     */
    public static function find(): TaskQuery
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::createObject(TaskQuery::class, [get_called_class()]);
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'tree' => [
                'class' => NestedSetsBehavior::class,
                'treeAttribute' => 'root',
                'leftAttribute' => 'lft',
                'rightAttribute' => 'rgt',
                'depthAttribute' => 'level',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function transactions()
    {
        return [
            self::SCENARIO_DEFAULT => self::OP_ALL,
        ];
    }
}
