<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Exceptions;

use craft\base\ElementInterface;
use Exception;
use Throwable;

class ElementException extends Exception
{
    public function __construct(
        public ElementInterface $element,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
