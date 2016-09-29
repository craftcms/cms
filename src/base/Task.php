<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\base;

use Craft;

/**
 * Task is the base class for classes representing background tasks in terms of objects.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
abstract class Task extends SavableComponent implements TaskInterface
{
    // Traits
    // =========================================================================

    use TaskTrait;

    // Constants
    // =========================================================================

    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_ERROR = 'error';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'currentStep', 'totalSteps'], 'number', 'integerOnly' => true],
            [['status'], 'in', 'range' => [self::STATUS_PENDING, self::STATUS_RUNNING, self::STATUS_ERROR]],
            [['type'], 'required'],
            [['type'], 'string', 'max' => 150],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return $this->description ?: $this->getDefaultDescription();
    }

    /**
     * @inheritdoc
     */
    public function getTotalSteps()
    {
        return 0;
    }

    /**
     * @inheritdoc
     */
    public function getProgress()
    {
        if ($this->totalSteps !== null && $this->currentStep !== null) {
            return $this->currentStep / $this->totalSteps;
        }

        return 0;
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();
        $fields['description'] = [$this, 'getDescription'];
        $fields['progress'] = [$this, 'getProgress'];

        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function runStep($step)
    {
        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Returns a default description for [[getDescription()]], if [[description]] isn’t set.
     *
     * @return string The default task description
     */
    protected function getDefaultDescription()
    {
        return static::displayName();
    }

    /**
     * Creates and runs a subtask.
     *
     * @param TaskInterface|array|string $task The task, the task’s class name, or its config, with a `type` value and optionally a `settings` value
     *
     * @return boolean
     */
    protected function runSubTask($task)
    {
        $tasksService = Craft::$app->getTasks();

        if (!$task instanceof TaskInterface) {
            $task = $tasksService->createTask($task);
        }

        /** @var Task $task */
        $task->parentId = $this->id;

        if ($tasksService->saveTask($task)) {
            return $tasksService->runTask($task);
        }

        return false;
    }
}
