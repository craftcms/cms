<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue;

use yii\helpers\Console;
use yii\queue\cli\Action;

/**
 * Info about queue status.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 * @since 3.0
 */
class InfoAction extends Action
{
    /**
     * @var Queue
     */
    public $queue;

    /**
     * Info about queue status.
     */
    public function run()
    {
        Console::output($this->format('Jobs', Console::FG_GREEN));

        Console::stdout($this->format('- waiting: ', Console::FG_YELLOW));
        Console::output($this->queue->getTotalWaiting());

        Console::stdout($this->format('- delayed: ', Console::FG_YELLOW));
        Console::output($this->queue->getTotalDelayed());

        Console::stdout($this->format('- reserved: ', Console::FG_YELLOW));
        Console::output($this->queue->getTotalReserved());

        Console::stdout($this->format('- failed: ', Console::FG_YELLOW));
        Console::output($this->queue->getTotalFailed());
    }
}
