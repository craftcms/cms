<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\base;

/**
 * TaskTrait implements the common methods and properties for background task classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
trait TaskTrait
{
	// Properties
	// =========================================================================

	/**
	 * @var integer The task’s level within its structure
	 */
	public $level;

	/**
	 * @var string The task’s description
	 */
	public $description;

	/**
	 * @var integer The parent task’s ID
	 */
	public $parentId;

	/**
	 * @var integer The total number of steps the task will take
	 */
	public $totalSteps;

	/**
	 * @var integer The current step that the task is taking
	 */
	public $currentStep;

	/**
	 * @var string The task’s status
	 */
	public $status = 'pending';
}
