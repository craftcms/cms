<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Data;

use CraftCms\Cms\Ai\Enums\Capability;

/**
 * Whether a provider’s model has a {@see Capability}, as Craft found when it checked.
 *
 * @since 6.0.0
 */
readonly class CapabilityCheck
{
    /**
     * @param  bool|null  $supported  `null` when Craft couldn’t tell, such as when the provider returned an error.
     * @param  string|null  $error  Why Craft couldn’t tell.
     */
    public function __construct(
        public Capability $capability,
        public string $provider,
        public string $model,
        public ?bool $supported,
        public ?string $error = null,
    ) {}
}
