<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;

/**
 * TaskInterface defines the common interface to be implemented by background task classes.
 *
 * A class implementing this interface should also use [[SavableComponentTrait]] and [[TaskTrait]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
interface TaskInterface extends SavableComponentInterface
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns the task’s description.
	 *
	 * @return string The task’s description
	 */
	public function getDescription();

	/**
	 * Returns the total number of steps for this task.
	 *
	 * @return integer The total number of steps for this task
	 */
	public function getTotalSteps();

	/**
	 * Returns the task’s current progress as a number between 0 and 1.
	 *
	 * @return float The task’s current progress
	 */
	public function getProgress();

	/**
	 * Runs a task step.
	 *
	 * @param integer $step The step to run
	 * @return boolean Whether the step was successful
	 */
	public function runStep($step);
}
