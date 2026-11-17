<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Data;

/**
 * @since 6.0.0
 */
readonly class WidgetTypeData
{
    public function __construct(
        public ?string $iconSvg,
        public string $name,
        public ?int $maxColspan,
        public bool $selectable,
        /** Whether widgets of the type have settings, whose UI the dashboard loads when a widget is added. */
        public bool $hasSettings,
    ) {}
}
