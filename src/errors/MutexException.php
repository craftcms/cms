<?php

namespace craft\errors;

use Throwable;
use yii\base\Exception;

/**
 * Mutex Exception
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.5.12
 */
class MutexException extends Exception
{
    /**
     * Constructor
     *
     * @param string $name
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        public string $name,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'Mutex Exception';
    }
}
