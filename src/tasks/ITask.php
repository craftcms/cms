<?php
namespace Craft;

/**
 * Interface ITask
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.tasks
 * @since     2.0
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
