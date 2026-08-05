<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Data;

use CraftCms\Cms\Validation\Rules\HandleRule;
use InvalidArgumentException;

readonly class CustomWidgetDefinition
{
    public string $id;

    public function __construct(
        public string $filename,
        public ?string $handle,
        public ?string $label,
        public ?string $icon,
        public ?int $maxColspan,
        public ?string $title,
        public bool $titleFromLabel,
        public ?string $subtitle,
        public bool $showByDefault,
        public string $body,
    ) {
        if ($handle !== null) {
            new HandleRule()->validate('handle', $handle, fn () => throw new InvalidArgumentException("Custom widget file [$filename] has an invalid handle."));
        }

        if ($maxColspan !== null && ($maxColspan < 1 || $maxColspan > 4)) {
            throw new InvalidArgumentException("Custom widget file [$filename] frontmatter property [maxColspan] must be an integer between 1 and 4, or null.");
        }

        $this->id = $handle ? "handle:$handle" : "path:$filename";
    }

    public function type(): string
    {
        return self::class.':'.$this->id;
    }
}
