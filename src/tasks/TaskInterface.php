<?php
namespace craft\app\tasks;

/**
 * Interface TaskInterface
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.tasks
 * @since     2.0
 */
interface TaskInterface extends SavableComponentTypeInterface
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns the default description for this task.
	 *
	 * @return string
	 */
	public function getDescription();

	/**
	 * Returns the total number of steps for this task.
	 *
	 * @return int
	 */
	public function getTotalSteps();

	/**
	 * Runs a task step.
	 *
	 * @param int $step
	 *
	 * @return bool
	 */
	public function runStep($step);
}
