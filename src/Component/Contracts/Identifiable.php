<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Contracts;

interface Identifiable
{
    /**
     * Returns the ID of the component, which should be used as the value of hidden inputs.
     */
    public function getId(): string|int|null;
}
