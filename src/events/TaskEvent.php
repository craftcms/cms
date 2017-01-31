<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\events;

use craft\base\TaskInterface;
use yii\base\Event;

/**
 * TaskEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class TaskEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var TaskInterface|null The task associated with this event.
     */
    public $task;

    /**
     * @var bool Whether the task is brand new
     */
    public $isNew = false;
}
