<?php

declare(strict_types=1);

namespace CraftCms\Cms\SystemMessage\Data;

readonly class FormattedSystemMessageMail
{
    /**
     * @param  array<string, mixed>  $viewData
     */
    public function __construct(
        public bool $usesCustomTemplate,
        public string $htmlBody,
        public array $viewData,
    ) {}
}
