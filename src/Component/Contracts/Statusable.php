<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Contracts;

use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;

/**
 * Statusable defines the common interface to be implemented by components that
 * can have statuses within the control panel.
 */
interface Statusable
{
    /**
     * Returns all the possible statuses that components may have.
     *
     * It should return an array whose keys are the status values, and values are the human-facing status labels, or an array
     * with the following keys:
     *
     * - **`label`** – The human-facing status label.
     * - **`color`** – The status color. See {@see Color} for possible values.
     */
    public static function statuses(): array;

    /**
     * Returns the component’s status.
     */
    #[AllowedInSandbox]
    public function getStatus(): ?string;
}
