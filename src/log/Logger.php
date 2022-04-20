<?php

namespace craft\log;

use samdark\log\PsrMessage;

class Logger extends \yii\log\Logger
{
    public const DEFAULT_CATEGORY = 'application';

    /**
     * @inheritdoc
     * @param string|array $category
     */
    public function log($message, $level, $category = self::DEFAULT_CATEGORY): void
    {
        if (is_array($category)) {
            $message = new PsrMessage($message, $category);
            $category = self::DEFAULT_CATEGORY;
        }

        parent::log($message, $level, $category);
    }
}
