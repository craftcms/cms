<?php

/**
 * @link https://github.com/yii2tech
 *
 * @copyright Copyright (c) 2019 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace CraftCms\Yii2Adapter\Log;

use Illuminate\Support\Facades\Log;
use yii\base\Component;
use yii\helpers\VarDumper;

class Logger extends \yii\log\Logger
{
    use LogTrait;

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        Component::init(); // skip parent init, avoiding `register_shutdown_function()` call.
    }

    /**
     * {@inheritdoc}
     */
    public function log($message, $level, $category = 'application'): void
    {
        $level = $this->convertLogLevel($level);

        $context = [
            'category' => $category,
        ];

        if (!is_string($message)) {
            // exceptions may not be serializable if in the call stack somewhere is a Closure
            if ($message instanceof \Throwable) {
                $context['exception'] = $message;
                $message = (string) $message;
            } else {
                $message = VarDumper::export($message);
            }
        }

        Log::log($level, $message, $context);
    }
}
