<?php

declare(strict_types=1);

namespace CraftCms\Cms\Queue\Contracts;

/**
 * Interface for jobs that have a human-readable description.
 *
 * The description is displayed in the Control Panel Queue Manager
 * to help users understand what a job is doing.
 */
interface DescribableJob
{
    /**
     * Returns the job's description.
     */
    public function getDescription(): string;
}
