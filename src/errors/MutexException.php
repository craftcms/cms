<?php

namespace craft\errors;

use yii\base\Exception;

class MutexException extends Exception
{
    /**
     * The name of the mutex lock being acquired or released
     */
    public string $name;

    public function __construct(string $name, string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        $this->name = $name;
        parent::__construct($message, $code, $previous);
    }
}
