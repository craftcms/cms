<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Data;

use CraftCms\Cms\Form\FormPayload;

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
        public ?FormPayload $settingsForm,
    ) {}
}
