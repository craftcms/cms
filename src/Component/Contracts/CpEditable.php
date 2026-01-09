<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Contracts;

use craft\web\twig\AllowedInSandbox;

/**
 * CpEditable defines the common interface to be implemented by components
 * that have a dedicated edit page in the control panel.
 */
interface CpEditable
{
    /**
     * Returns the URL to the component’s edit page in the control panel.
     */
    #[AllowedInSandbox]
    public function getCpEditUrl(): ?string;
}
