<?php

declare(strict_types=1);

namespace CraftCms\Cms\SystemMessage\Data;

final readonly class RenderedSystemMessage
{
    public function __construct(
        public string $key,
        public string $language,
        public string $subject,
        public string $textBody,
        public string $htmlBody,
        public ?int $siteId,
        public array $variables,
    ) {}
}
