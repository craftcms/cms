<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Exceptions;

use craft\base\ElementInterface;
use Throwable;

final class UnsupportedSiteException extends ElementException
{
    public function __construct(
        ElementInterface $element,
        public readonly int $siteId,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($element, $message ?: "The element “{$element}” doesn't support site {$siteId}.", $code, $previous);
    }
}
