<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Contracts;

use CraftCms\Cms\Ai\Enums\Capability;
use CraftCms\Cms\Ai\Enums\Modality;
use CraftCms\Cms\Ai\Task;

/**
 * An AI task that admins map to a provider and model.
 *
 * Extend {@see Task} rather than implementing this directly.
 *
 * @since 6.0.0
 */
interface TaskInterface
{
    /**
     * Returns the task’s globally unique handle, such as `altText:describe`.
     *
     * Prefix it with your plugin handle. It may only contain letters, numbers,
     * underscores, hyphens, and colons, and must start with a letter.
     */
    public static function handle(): string;

    /**
     * Returns the task’s label, shown to admins.
     */
    public static function label(): string;

    /**
     * Returns the kind of operation the task performs, which decides its default provider.
     */
    public static function modality(): Modality;

    /**
     * Returns a description of the task, shown to admins.
     */
    public static function description(): ?string;

    /**
     * Returns what the task needs from its model, beyond its modality, such as vision.
     *
     * Craft checks the chosen model when the AI settings are saved. If the model doesn’t have one, the task is
     * unavailable, and the AI settings explain why.
     *
     * @return list<Capability>
     */
    public static function requirements(): array;
}
