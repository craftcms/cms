<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\tasks;

use craft\app\components\BaseSavableComponentType;

/**
 * Task base class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class BaseTask extends BaseSavableComponentType implements TaskInterface
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
	 * @inheritDoc TaskInterface::getDescription()
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return $this->getName();
	}

	/**
	 * @inheritDoc TaskInterface::getTotalSteps()
	 *
	 * @return int
	 */
	public function getTotalSteps()
	{
		return 0;
	}

	/**
	 * @inheritDoc TaskInterface::runStep()
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
