<?php
namespace Craft;

/**
 * Task base class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.tasks
 * @since     2.0
 */
abstract class BaseTask extends BaseSavableComponentType implements ITask
{
	// Properties
	// =========================================================================

	/**
	 * The type of component, e.g. "Plugin", "Widget", "FieldType", etc. Defined by the component type's base class.
	 *
	 * @var string
	 */
	protected $componentType = 'Task';

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ITask::getDescription()
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return $this->getName();
	}

	/**
	 * @inheritDoc ITask::getTotalSteps()
	 *
	 * @return int
	 */
	public function getTotalSteps()
	{
		return 0;
	}

	/**
	 * @inheritDoc ITask::runStep()
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
		$task
			= craft()->tasks->createTask($taskClassName, $taskDescription, $settings, $this->model->id);
		return craft()->tasks->runTask($task);
	}
}
