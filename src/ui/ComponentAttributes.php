<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\ui;

use craft\helpers\Html;

class ComponentAttributes
{
    public array $attributes = [];

    public function __construct(array $attributes = [])
    {
        $this->attributes = Html::normalizeTagAttributes($attributes);
    }

    public function __toString(): string
    {
        return Html::renderTagAttributes($this->attributes);
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->attributes;
    }

    /**
     * Set default attributes. These are used if they are not already
     * defined. "class" is special, these defaults are prepended to
     * the existing "class" attribute (if available).
     */
    public function defaults(array $attributes): self
    {
        $normalized = Html::normalizeTagAttributes($attributes);

        foreach ($this->attributes as $key => $value) {
            if (isset($normalized[$key]) && 'class' === $key) {
                $normalized[$key] = array_merge(self::toCssClasses($normalized[$key]), $value);
            } else {
                $normalized[$key] = $value;
            }
        }

        return new self($normalized);
    }

    /**
     * Extract only these attributes.
     */
    public function only(string ...$keys): self
    {
        $attributes = [];

        foreach ($this->attributes as $key => $value) {
            if (\in_array($key, $keys, true)) {
                $attributes[$key] = $value;
            }
        }

        return new self($attributes);
    }

    /**
     * Extract all but these attributes.
     */
    public function without(string ...$keys): self
    {
        $clone = clone $this;

        foreach ($keys as $key) {
            unset($clone->attributes[$key]);
        }

        return $clone;
    }

    public function addClass(string ...$classes): self
    {
        $classArray = $this->attributes['class'] ?? [];

        foreach ($classes as $class) {
            $classArray[] = $class;
        }

        $attributes = array_merge($this->attributes, ['class' => $classArray]);
        return new self($attributes);
    }

    /**
     * Updates classes
     *
     * @param mixed $value
     * @return string Class string
     */
    public function class(mixed $value): string
    {
        $currentClasses = $this->attributes['class'] ?? [];

        // Normalize to an array
        $classList = Html::explodeClass($value);
        $classString = self::toCssClasses($classList);

        return implode(' ', array_merge($currentClasses, $classString));
    }

    /**
     * Conditionally compile classes from an array into a CSS class list.
     *
     * @param array $classList
     * @return array
     */
    private static function toCssClasses(array $classList): array
    {
        $classes = [];

        foreach ($classList as $class => $constraint) {
            if (is_numeric($class)) {
                $classes[] = $constraint;
            } elseif ($constraint) {
                $classes[] = $class;
            }
        }

        return $classes;
    }
}
