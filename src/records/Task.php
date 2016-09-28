<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\records;

use Craft;
use craft\app\db\ActiveRecord;
use craft\app\db\NestedSetsTrait;
use craft\app\db\TaskQuery;
use creocoder\nestedsets\NestedSetsBehavior;

/**
 * Class Task record.
 *
 * @property integer $id          ID
 * @property integer $root        Root
 * @property integer $lft         Lft
 * @property integer $rgt         Rgt
 * @property integer $level       Level
 * @property integer $currentStep Current step
 * @property integer $totalSteps  Total steps
 * @property string  $status      Status
 * @property string  $type        Type
 * @property string  $description Description
 * @property array   $settings    Settings
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Task extends ActiveRecord
{
    // Traits
    // =========================================================================

    use NestedSetsTrait;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%tasks}}';
    }

    /**
     * @inheritdoc
     *
     * @return TaskQuery
     */
    public static function find()
    {
        /** @var TaskQuery $query */
        $query = Craft::createObject(TaskQuery::class, [get_called_class()]);

        return $query;
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
            static::SCENARIO_DEFAULT => static::OP_ALL,
        ];
    }
}
