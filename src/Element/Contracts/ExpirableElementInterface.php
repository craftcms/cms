<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Contracts;

use DateTime;

interface ExpirableElementInterface
{
    /**
     * Returns the element’s expiration date/time.
     */
    public function getExpiryDate(): ?DateTime;
}
