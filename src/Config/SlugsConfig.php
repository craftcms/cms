<?php

declare(strict_types=1);

namespace CraftCms\Cms\Config;

/**
 * Settings for element slugs.
 *
 * @since 6.0.0
 */
class SlugsConfig extends BaseConfig
{
    /** Whether uppercase letters are allowed in slugs. */
    public bool $allowUppercase = false;

    /**
     * Whether non-ASCII characters in auto-generated slugs should be converted to ASCII (`ñ` → `n`).
     *
     * It only affects slugs the control panel generates. Non-ASCII characters can still be entered by hand.
     */
    public bool $limitAutoToAscii = false;

    /** The characters that separate words in slugs. */
    public string $wordSeparator = '-';

    /** The highest number Craft adds to a slug to make it unique, before it gives up and throws an error. */
    public int $maxIncrement = 100;

    public function allowUppercase(bool $value = true): self
    {
        $this->allowUppercase = $value;

        return $this;
    }

    public function limitAutoToAscii(bool $value = true): self
    {
        $this->limitAutoToAscii = $value;

        return $this;
    }

    public function wordSeparator(string $value): self
    {
        $this->wordSeparator = $value;

        return $this;
    }

    public function maxIncrement(int $value): self
    {
        $this->maxIncrement = $value;

        return $this;
    }
}
