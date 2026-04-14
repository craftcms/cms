<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use craft\base\ElementInterface;
use CraftCms\Cms\Component\Concerns\MissingComponentTrait;
use CraftCms\Cms\Component\Contracts\MissingComponentInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use Override;

/**
 * MissingField represents a field with an invalid class.
 *
 * @property class-string<FieldInterface> $expectedType
 */
class MissingField extends Field implements MissingComponentInterface
{
    use MissingComponentTrait;

    #[Override]
    public static function icon(): string
    {
        return 'question';
    }

    #[Override]
    public static function dbType(): array|string|null
    {
        return null;
    }

    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return $this->getPlaceholderHtml();
    }
}
