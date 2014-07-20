<?php
namespace Craft;

/**
 * Interface ITask
 *
 * @package craft.app.tasks
 */
interface ITask extends ISavableComponentType
{
	/**
	 * @return string
	 */
	public function getDescription();

	/**
	 * @return int
	 */
	public function getTotalSteps();

	/**
	 * @param int $step
	 * @return bool
	 */
	public function runStep($step);
}
