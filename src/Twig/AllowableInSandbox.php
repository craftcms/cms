<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig;

/**
 * Interface AllowableInSandbox
 *
 * @since 6.0.0
 */
interface AllowableInSandbox
{
    /**
     * Returns whether the given method is allowed to be called by sandboxed Twig templates.
     */
    public function methodAllowedInSandbox(string $method): bool;

    /**
     * Returns whether the given property is allowed to be referenced by sandboxed Twig templates.
     */
    public function propertyAllowedInSandbox(string $property): bool;
}
