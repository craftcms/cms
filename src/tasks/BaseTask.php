<?php
namespace Craft;

/**
 * Task base class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.tasks
 * @since     2.0
 */
abstract class BaseTask extends BaseSavableComponentType implements ITask
{
	// Properties
	// =========================================================================

	/**
	 * @var string The type of component this is
	 */
	protected $componentType = 'Task';

	// Public Methods
	// =========================================================================

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
	 *
	 * @return bool
	 */
	public function runStep($step)
	{
		return true;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Creates and runs a subtask.
	 *
	 * @param string      $taskClassName
	 * @param string|null $taskDescription
	 * @param array|null  $settings
	 *
	 * @return bool
	 */
	protected function runSubTask($taskClassName, $taskDescription = null, $settings = null)
	{
		$task = craft()->tasks->createTask($taskClassName, $taskDescription, $settings, $this->model->id);
		return craft()->tasks->runTask($task);
	}
}
