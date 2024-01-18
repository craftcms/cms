<?php

namespace craft\ui\concerns;

use Craft;

trait HasLabel
{
    /**
     * @var string|null Raw label
     */
    protected ?string $label = null;

    /**
     * @var string Translation category
     */
    protected string $translationCategory = 'app';

    /**
     * Set the translation category.
     *
     * @param string $value Category to use for translations
     */
    public function translationCategory(string $value): static
    {
        $this->translationCategory = $value;
        return $this;
    }

    /**
     * Get the current translation category
     *
     * @return string
     */
    public function getTranslationCategory(): string
    {
        return $this->translationCategory;
    }

    /**
     * Set the label property
     *
     * @param ?string $label
     * @return $this
     */
    public function label(?string $label): static
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Get the label. If a translation category is set, the label will be translated.
     *
     * @return string|null
     */
    public function getLabel(): ?string
    {
        if ($this->getTranslationCategory()) {
            return Craft::t($this->getTranslationCategory(), $this->label);
        }

        return $this->label;
    }
}
