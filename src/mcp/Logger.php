<?php

namespace craft\mcp;

use Craft;
use Psr\Log\AbstractLogger;
use Stringable;
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

        Craft::getLogger()->log((string)$message, $level, 'mcp');
    }
}
