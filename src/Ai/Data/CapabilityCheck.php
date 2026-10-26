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
     * @param  bool  $transient  Whether Craft couldn’t tell because of a problem that can be fixed right away, such as
     *                           the provider being unreachable or rejecting its API key. Such errors aren’t cached.
     */
    public function __construct(
        public Capability $capability,
        public string $provider,
        public string $model,
        public ?bool $supported,
        public ?string $error = null,
        public bool $transient = false,
    ) {}
}
