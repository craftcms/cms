<?php

namespace craft\ui\concerns;

use Craft;

trait HasLabel
{
    protected ?string $label = null;
    protected string $translationCategory = 'app';

    public function translationCategory(string $value): self
    {
        $this->translationCategory = $value;
        return $this;
    }

    public function getTranslationCategory(): string
    {
        return $this->translationCategory;
    }

    public function label(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function getLabel(): ?string
    {
        if ($this->getTranslationCategory()) {
            return Craft::t($this->getTranslationCategory(), $this->label);
        }

        return $this->label;
    }
}
