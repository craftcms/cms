<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuth\Data;

readonly class ButtonData
{
    public function __construct(
        public ProviderDefinition $provider,
        public bool $isCpRequest,
        public string $url,
        public string $label,
    ) {}
}
