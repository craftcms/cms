<?php

declare(strict_types=1);

namespace CraftCms\Cms\SystemMessage\Data;

/**
 * @since 6.0.0
 */
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
