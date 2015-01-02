<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\tasks;

use craft\app\components\SavableComponentTypeInterface;

/**
 * Interface TaskInterface
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
