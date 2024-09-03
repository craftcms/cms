<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\FieldInterface;
use craft\base\MissingComponentInterface;
use craft\base\MissingComponentTrait;

/**
 * MissingField represents a field with an invalid class.
 *
 * @property class-string<FieldInterface> $expectedType
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class MissingField extends Field implements MissingComponentInterface
{
    use MissingComponentTrait;

    /**
     * @inheritdoc
     */
    public static function icon(): string
    {
        return 'question';
    }

    /**
     * @inheritdoc
     */
    public static function dbType(): array|string|null
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return $this->getPlaceholderHtml();
    }
}
