<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Contracts;

use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;

interface Identifiable
{
    /**
     * Returns the ID of the component, which should be used as the value of hidden inputs.
     */
    #[AllowedInSandbox]
    public function getId(): string|int|null;
}
