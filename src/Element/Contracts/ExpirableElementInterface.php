<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Contracts;

use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use DateTimeInterface;

interface ExpirableElementInterface
{
    /**
     * Returns the element’s expiration date/time.
     */
    #[AllowedInSandbox]
    public function getExpiryDate(): ?DateTimeInterface;
}
