<?php
namespace Craft;

/**
 * Task base class
 */
abstract class BaseTask extends BaseSavableComponentType implements ITask
{
	/**
	 * @access protected
	 * @var string The type of component this is
	 */
	protected $componentType = 'Task';

	/**
	 * Returns the default description for this task.
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return $this->getName();
	}

	/**
	 * Returns the total number of steps for this task.
	 *
	 * @return int
	 */
	public function getTotalSteps()
	{
		return 0;
	}

	/**
	 * Runs a task step.
	 *
	 * @param int $step
	 * @return bool
	 */
	public function runStep($step)
	{
		return true;
	}

	/**
	 * Creates and runs a subtask.
	 *
	 * @access protected
	 * @param             $taskClassName
	 * @param string|null $taskDescription
	 * @param array|null  $settings
	 * @return bool
	 */
	protected function runSubTask($taskClassName, $taskDescription = null, $settings = null)
	{
		$task = craft()->tasks->createTask($taskClassName, $taskDescription, $settings, $this->model->id);
		return craft()->tasks->runTask($task);
	}
}
