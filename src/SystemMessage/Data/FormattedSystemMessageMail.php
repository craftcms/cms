<?php

declare(strict_types=1);

namespace CraftCms\Cms\SystemMessage\Data;

readonly class FormattedSystemMessageMail
{
    public function __construct(
        public bool $usesCustomTemplate,
        public string $htmlBody,
        public array $viewData,
    ) {}
}
