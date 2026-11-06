<?php

namespace craft\mcp;

use Craft;
use Psr\Log\AbstractLogger;
use Stringable;
use Throwable;
use yii\log\Logger as YiiLogger;

class Logger extends AbstractLogger
{
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $level = match ($level) {
            'emergency', 'alert', 'critical', 'error' => YiiLogger::LEVEL_ERROR,
            'warning' => YiiLogger::LEVEL_WARNING,
            'debug' => YiiLogger::LEVEL_TRACE,
            default => YiiLogger::LEVEL_INFO,
        };

        $message = (string)$message;
        if (($context['exception'] ?? null) instanceof Throwable) {
            $message .= "\n" . $context['exception'];
        }

        Craft::getLogger()->log($message, $level, 'mcp');
    }
}
