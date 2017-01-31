<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\base;

/**
 * TaskTrait implements the common methods and properties for background task classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
trait TaskTrait
{
    // Properties
    // =========================================================================

    /**
     * @var int|null The task’s level within its structure
     */
    public $level;

    /**
     * @var string|null The task’s description
     */
    public $description;

    /**
     * @var int|null The parent task’s ID
     */
    public $parentId;

    /**
     * @var int|null The total number of steps the task will take
     */
    public $totalSteps;

    /**
     * @var int|null The current step that the task is taking
     */
    public $currentStep;

    /**
     * @var string The task’s status
     */
    public $status = 'pending';
}
