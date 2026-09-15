<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Data;

use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\View\HtmlFragment;

readonly class WidgetData
{
    /**
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>|null  $data
     */
    public function __construct(
        public int $id,
        public string $type,
        public int $colspan,
        public int $maxColspan,
        public ?string $title,
        public ?string $subtitle,
        public string $name,
        public array $settings,
        public ?string $component,
        public ?array $data,
        public HtmlFragment $fragment,
        public ?FormPayload $settingsForm,
    ) {}
}
