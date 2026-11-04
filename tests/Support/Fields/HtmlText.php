<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support\Fields;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Controls\Textarea;
use Override;
use Twig\Markup;

/**
 * A field whose values are HTML, as a rich text field’s are: `Twig\Markup` objects, saved and edited as HTML.
 */
class HtmlText extends PlainText
{
    #[Override]
    public static function displayName(): string
    {
        return 'HTML Text';
    }

    #[Override]
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        $value = $value instanceof Markup ? (string) $value : parent::normalizeValue($value, $element);

        return is_string($value) ? new Markup($value, 'UTF-8') : null;
    }

    #[Override]
    public function normalizeValueFromRequest(mixed $value, ?ElementInterface $element): mixed
    {
        return $this->normalizeValue($value, $element);
    }

    #[Override]
    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        return $value instanceof Markup ? (string) $value : $value;
    }

    #[Override]
    public function uiControl(FieldContext $context): Control
    {
        return Textarea::make($context->path)->value($context->value === null ? null : (string) $context->value);
    }

    #[Override]
    public function getElementRules(ElementInterface $element): array
    {
        return [];
    }
}
