<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Exceptions;

use InvalidArgumentException;

class InvalidHtmlTagException extends InvalidArgumentException
{
    /**
     * Constructor.
     *
     * @param  string  $message  The error message
     * @param  string|null  $type  The tag type
     * @param  array|null  $attributes  The tag attributes
     * @param  int|null  $start  The tag’s starting position
     * @param  int|null  $htmlStart  The tag’s inner HTML starting position
     */
    public function __construct(string $message, public ?string $type = null, public ?array $attributes = null, public ?int $start = null, public ?int $htmlStart = null)
    {
        parent::__construct($message);
    }
}
