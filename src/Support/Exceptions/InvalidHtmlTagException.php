<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Exceptions;

use InvalidArgumentException;

final class InvalidHtmlTagException extends InvalidArgumentException
{
    /**
     * @var string|null The tag type
     */
    public ?string $type = null;

    /**
     * @var array|null The tag attributes
     */
    public ?array $attributes = null;

    /**
     * @var int|null The tag’s starting position
     */
    public ?int $start = null;

    /**
     * @var int|null The tag’s inner HTML starting position
     */
    public ?int $htmlStart = null;

    /**
     * Constructor.
     *
     * @param  string  $message  The error message
     * @param  string|null  $type  The tag type
     * @param  array|null  $attributes  The tag attributes
     * @param  int|null  $start  The tag’s starting position
     * @param  int|null  $htmlStart  The tag’s inner HTML starting position
     */
    public function __construct(string $message, ?string $type = null, ?array $attributes = null, ?int $start = null, ?int $htmlStart = null)
    {
        $this->type = $type;
        $this->attributes = $attributes;
        $this->start = $start;
        $this->htmlStart = $htmlStart;

        parent::__construct($message);
    }
}
