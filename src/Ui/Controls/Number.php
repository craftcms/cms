<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Controls;

use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Ui\ControlPayload;
use CraftCms\Cms\Ui\UiHtmlRenderer;

/**
 * @since 6.0.0
 */
class Number extends Text
{
    #[\Override]
    protected string $inputType = 'number';

    private ?int $size = null;

    private ?string $locale = null;

    #[\Override]
    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, UiHtmlRenderer $renderer): string
    {
        if (! isset($control->props['locale'])) {
            return parent::renderHtml($control, $value, $attributes, $renderer);
        }

        $value = is_array($value) ? $value : ['value' => $value];
        $name = $attributes['name'];
        $input = parent::renderHtml($control, $value['value'] ?? null, $name === null ? $attributes : ['name' => "{$name}[value]"] + $attributes, $renderer);

        return $name === null
            ? $input
            : Html::hiddenInput("{$name}[locale]", $value['locale'] ?? $control->props['locale'])->render().$input;
    }

    #[\Override]
    public function component(): string
    {
        return 'craft:number';
    }

    #[\Override]
    public function size(?int $size): static
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Sets the locale the value is formatted in, such as `1,234.5` for `en-US`.
     *
     * The value is then held and posted as `value` and `locale` keys, so it can be read back with
     * `I18N::normalizeNumber()`.
     */
    public function locale(?string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        return array_filter([
            ...parent::props($value),
            'size' => $this->size,
            'locale' => $this->locale,
        ], fn (mixed $value): bool => $value !== null);
    }
}
