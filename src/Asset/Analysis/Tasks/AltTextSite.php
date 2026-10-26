<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Analysis\Tasks;

/**
 * A site that {@see GenerateAltText} writes alt text for, standing in for the sites that share its alt text.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class AltTextSite
{
    public function __construct(
        public int $siteId,
        public string $language,
        public ?string $alt,
    ) {}
}
