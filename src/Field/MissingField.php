<?php

namespace CraftCms\Cms\Field;

use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\MissingComponentInterface;
use craft\base\MissingComponentTrait;
use CraftCms\Cms\Field\Contracts\FieldInterface;

/**
 * MissingField represents a field with an invalid class.
 *
 * @property class-string<FieldInterface> $expectedType
 *
 * @since 6.0.0
 */
final class MissingField extends Field implements MissingComponentInterface
{
    use MissingComponentTrait;

    /**
     * {@inheritdoc}
     */
    public static function icon(): string
    {
        return 'question';
    }

    /**
     * {@inheritdoc}
     */
    public static function dbType(): array|string|null
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return $this->getPlaceholderHtml();
    }
}
