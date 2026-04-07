<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\Element\ElementEagerLoader;

use CraftCms\Cms\Element\Contracts\ExpirableElementInterface;
use DateTime;

class TestElementEagerLoaderExpirableTargetElement extends TestElementEagerLoaderElement implements ExpirableElementInterface
{
    public ?DateTime $expiryDate = null;

    public function getExpiryDate(): ?DateTime
    {
        return $this->expiryDate;
    }
}
