<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\base;

/**
 * TaskInterface defines the common interface to be implemented by background task classes.
 *
 * A class implementing this interface should also use [[SavableComponentTrait]] and [[TaskTrait]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
interface TaskInterface extends SavableComponentInterface
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the total number of steps for this task.
     *
     * @return int The total number of steps for this task
     */
    public function getTotalSteps(): int;

    /**
     * Returns the task’s current progress as a number between 0 and 1.
     *
     * @return float The task’s current progress
     */
    public function getProgress(): float;

    /**
     * Runs a task step.
     *
     * @param int $step The step to run
     *
     * @return bool|string True if the step was successful, false or an error message if not
     */
    public function runStep(int $step);
}
