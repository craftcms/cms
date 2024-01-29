<?php

namespace craft\ui\concerns;

use Craft;

trait HasOrientation
{
    /**
     * @var string|null The input orientation
     */
    protected ?string $orientation = null;

    public function orientation(?string $orientation): static
    {
        $this->orientation = $orientation;
        return $this;
    }

    public function getOrientation(): ?string
    {
        return $this->orientation ?? ($this->getSite() ? $this->getSite()->getLocale() : Craft::$app->getLocale())->getOrientation();
    }
}