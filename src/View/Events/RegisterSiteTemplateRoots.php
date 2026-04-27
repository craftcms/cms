<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\Events;

/**
 * @event RegisterSiteTemplateRoots The event that is triggered when registering site template roots
 */
class RegisterSiteTemplateRoots
{
    /**
     * @var array<string, string|string[]> The registered template roots. Each key should be a root template path, and values should be the
     *                                     corresponding directory path, or an array of directory paths.
     */
    public array $roots = [];
}
